<?php

namespace App\Http\Controllers;

use App\Models\{LanguageClass, LanguageCourse, LanguageDiscountPolicy, LanguageLead, LanguageMonthlyTargetRecord, LanguageStudent, LanguageTuitionCharge, LanguageTuitionPayment};
use App\Support\{CenterCode, ExcelExporter};
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LanguageTuitionController extends Controller
{
    public function index(Request $request): View
    {
        $query = LanguageTuitionCharge::with(['student','course','languageClass','discount','payments'])->withSum('payments','amount')->latest();
        if ($request->filled('q')) {
            $search = $request->string('q');
            $query->where(fn ($builder) => $builder->where('code','like',"%{$search}%")->orWhereHas('student',fn ($student) => $student->where('name','like',"%{$search}%")));
        }
        if ($request->filled('status')) $query->where('status',$request->status);
        $this->applyPeriod($query,$request);
        return view('language.tuition.index',['items'=>$query->paginate(20)->withQueryString(),'filterYear'=>$request->integer('year',now()->year)]);
    }

    public function create(Request $request): View
    {
        $selectedClass=$request->integer('class');
        $classes=LanguageClass::where(function ($query) use ($selectedClass) {
            $query->whereIn('status',['recruiting','upcoming','active']);
            if ($selectedClass) $query->orWhere('id',$selectedClass);
        })->orderBy('name')->get();
        return view('language.tuition.form',['item'=>new LanguageTuitionCharge,'students'=>LanguageStudent::orderBy('name')->get(),'leads'=>LanguageLead::whereNotNull('converted_student_id')->orderBy('name')->get(),'courses'=>LanguageCourse::where('active',1)->orderBy('name')->get(),'classes'=>$classes,'discounts'=>LanguageDiscountPolicy::where('active',1)->orderBy('name')->get(),'selectedStudent'=>$request->integer('student'),'selectedLead'=>$request->integer('lead'),'selectedCourse'=>$request->integer('course'),'selectedClass'=>$selectedClass]);
    }

    public function show(LanguageTuitionCharge $languageTuition): View
    {
        return view('language.tuition.show',['item'=>$languageTuition->load(['student','course','languageClass.program','languageClass.level','discount','payments'])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data=$request->validate(['language_student_id'=>'required|exists:language_students,id','language_lead_id'=>'nullable|exists:language_leads,id','language_course_id'=>'required|exists:language_courses,id','language_class_id'=>'nullable|exists:language_classes,id','language_discount_policy_id'=>'nullable|exists:language_discount_policies,id','due_date'=>'nullable|date','note'=>'nullable']);
        $course=LanguageCourse::findOrFail($data['language_course_id']);
        if (empty($data['language_lead_id'])) $data['language_lead_id']=$this->resolveLeadId((int)$data['language_student_id'],(int)$data['language_course_id']);
        $discount=!empty($data['language_discount_policy_id'])?LanguageDiscountPolicy::find($data['language_discount_policy_id']):null;
        $percentage=(float)($discount?->percentage??0); $amount=(float)$course->tuition;
        $data+=['code'=>CenterCode::next('language_tuition_charges','HP'),'original_amount'=>$amount,'discount_percentage'=>$percentage,'discount_amount'=>round($amount*$percentage/100,2),'payable_amount'=>round($amount*(100-$percentage)/100,2),'paid_amount'=>0,'status'=>'unpaid','created_by'=>$request->user()->id];
        LanguageTuitionCharge::create($data);
        LanguageStudent::whereKey($data['language_student_id'])->update(['language_course_id'=>$data['language_course_id'],'language_discount_policy_id'=>$data['language_discount_policy_id'] ?? null]);
        return redirect()->route('language-tuition.index')->with('success','Đã lập khoản thu học phí.');
    }

    public function pay(Request $request, LanguageTuitionCharge $languageTuition): RedirectResponse
    {
        $data=$request->validate(['receipt_code'=>'nullable|string|max:30|unique:language_tuition_payments,receipt_code','amount'=>'required|numeric|gt:0','paid_at'=>'required|date','payment_method'=>['required',Rule::in(['cash','transfer','card','other'])],'reference'=>'nullable|max:255','note'=>'nullable'],['receipt_code.unique'=>'Số phiếu thu đã tồn tại.']);
        $isPending=blank($data['receipt_code'] ?? null);
        $data['receipt_code']=$data['receipt_code'] ?: null;
        $data['receipt_status']=$isPending?'pending':'confirmed';
        $data['confirmed_at']=$isPending?null:now();
        DB::transaction(function () use ($data,$request,$languageTuition) {
            $charge=LanguageTuitionCharge::lockForUpdate()->findOrFail($languageTuition->id);
            $remaining=(float)$charge->payable_amount-(float)$charge->paid_amount;
            if ($data['amount']>$remaining+0.001) throw \Illuminate\Validation\ValidationException::withMessages(['amount'=>'Số tiền thu vượt quá công nợ còn lại '.number_format($remaining).'đ.']);
            $payment=$charge->payments()->create($data+['collected_by'=>$request->user()->id]);
            $this->refreshCharge($charge,$payment);
        });
        return back()->with('success',$isPending?'Đã ghi nhận tiền ở trạng thái chờ bổ sung phiếu thu.':'Đã ghi nhận phiếu thu học phí.');
    }

    public function confirmReceipt(Request $request, LanguageTuitionPayment $languageTuitionPayment): RedirectResponse
    {
        $data=$request->validate(['receipt_code'=>'required|string|max:30|unique:language_tuition_payments,receipt_code'],['receipt_code.required'=>'Vui lòng nhập số phiếu thu.','receipt_code.unique'=>'Số phiếu thu đã tồn tại.']);
        DB::transaction(function () use ($data,$languageTuitionPayment) {
            $payment=LanguageTuitionPayment::lockForUpdate()->findOrFail($languageTuitionPayment->id);
            abort_if($payment->receipt_status==='confirmed',422,'Phiếu thu này đã được xác nhận.');
            $payment->update(['receipt_code'=>$data['receipt_code'],'receipt_status'=>'confirmed','confirmed_at'=>now()]);
            $this->refreshCharge(LanguageTuitionCharge::lockForUpdate()->findOrFail($payment->language_tuition_charge_id),$payment);
        });
        return back()->with('success','Đã bổ sung và xác nhận số phiếu thu.');
    }

    public function export(Request $request)
    {
        $query=LanguageTuitionCharge::with(['student','course']); $this->applyPeriod($query,$request);
        return ExcelExporter::download('thu-hoc-phi-'.date('Ymd').'.xlsx',['Mã khoản thu','Học viên','Khóa học','Học phí','Giảm %','Phải thu','Đã thu','Còn lại','Trạng thái','Hạn đóng'],$query->get()->map(fn($item)=>[$item->code,$item->student->name,$item->course->name,$item->original_amount,$item->discount_percentage,$item->payable_amount,$item->paid_amount,(float)$item->payable_amount-(float)$item->paid_amount,$item->status,$item->due_date?->format('d/m/Y')]));
    }

    public function downloadQr(Request $request, LanguageTuitionCharge $languageTuition)
    {
        $languageTuition->load(['student','languageClass']);
        $remaining=max(0,(float)$languageTuition->payable_amount-(float)$languageTuition->paid_amount);
        $amount=$request->filled('amount') ? min($remaining,max(1,$request->integer('amount'))) : $remaining;
        $response = Http::timeout(15)->get($this->qrUrl($languageTuition,$amount));
        abort_unless($response->successful(), 502, 'Không thể tải ảnh VietQR. Vui lòng thử lại.');
        return response($response->body(), 200, [
            'Content-Type'=>$response->header('Content-Type','image/png'),
            'Content-Disposition'=>'attachment; filename="vietqr-'.$languageTuition->code.'.png"',
        ]);
    }

    public static function qrUrl(LanguageTuitionCharge $charge, float|int|null $requestedAmount=null): string
    {
        $amount=(int)round($requestedAmount ?? max(0,(float)$charge->payable_amount-(float)$charge->paid_amount));
        $description=Str::limit(trim(Str::ascii($charge->student->name.' '.($charge->languageClass?->code ?? 'CHUA XEP LOP'))),50,'');
        return 'https://img.vietqr.io/image/970428-683939339-compact2.png?'.http_build_query([
            'amount'=>$amount,
            'addInfo'=>$description,
            'accountName'=>'PHAN HIEU TRUONG DAI HOC BINH DUONG TAI CA MAU',
        ],'', '&', PHP_QUERY_RFC3986);
    }

    private function refreshCharge(LanguageTuitionCharge $charge, LanguageTuitionPayment $triggerPayment): void
    {
        $paid=(float)$charge->payments()->sum('amount');
        $hasPending=$charge->payments()->where('receipt_status','pending')->exists();
        $status=$hasPending?'pending_receipt':($paid+0.001>=(float)$charge->payable_amount?'paid':'partial');
        $charge->update(['paid_amount'=>$paid,'status'=>$status]);
        if ($status!=='paid') return;
        if (! $charge->language_lead_id) {
            $charge->update(['language_lead_id'=>$this->resolveLeadId($charge->language_student_id,$charge->language_course_id)]);
            $charge->refresh();
        }
        $lead=$charge->lead;
        if ($lead) $lead->update(['status'=>'registered','converted_student_id'=>$charge->language_student_id]);
        LanguageMonthlyTargetRecord::firstOrCreate(['language_tuition_payment_id'=>$triggerPayment->id],['record_year'=>$triggerPayment->paid_at->year,'record_month'=>$triggerPayment->paid_at->month,'language_student_id'=>$charge->language_student_id,'language_lead_id'=>$charge->language_lead_id,'language_collaborator_id'=>$lead?->language_collaborator_id,'language_course_id'=>$charge->language_course_id,'quantity'=>1,'revenue'=>$charge->payable_amount,'note'=>'Tự động ghi nhận khi hoàn tất học phí '.$charge->code]);
    }

    private function resolveLeadId(int $studentId,int $courseId): ?int
    {
        $lead=LanguageLead::where('converted_student_id',$studentId)
            ->orderByRaw('language_course_id = ? desc',[$courseId])->latest()->first();
        if ($lead) return $lead->id;
        $student=LanguageStudent::find($studentId);
        if (! $student?->phone) return null;
        $phone=preg_replace('/\D+/','',$student->phone);
        return LanguageLead::where('language_course_id',$courseId)->latest()->get()
            ->first(fn($item)=>preg_replace('/\D+/','',$item->phone)===$phone)?->id;
    }

    private function applyPeriod($query,Request $request): void
    {
        $year=max(2020,min(2100,$request->integer('year',now()->year))); $query->whereYear('created_at',$year);
        if ($request->filled('month')) $query->whereMonth('created_at',$request->integer('month'));
        elseif ($request->filled('quarter')) { $quarter=max(1,min(4,$request->integer('quarter'))); $query->whereMonth('created_at','>=',($quarter-1)*3+1)->whereMonth('created_at','<=',$quarter*3); }
    }
}
