<?php

namespace App\Http\Controllers;

use App\Models\{LanguageClass, LanguageCourse, LanguageDiscountPolicy, LanguageEnrollment, LanguageLead, LanguageMonthlyTargetRecord, LanguageStudent, LanguageTuitionCharge, LanguageTuitionPayment};
use App\Support\{CenterCode, ExcelExporter};
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Models\SystemSetting;
use Carbon\Carbon;

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
        return view('language.tuition.index',['items'=>$query->paginate(\App\Support\Pagination::perPage())->withQueryString(),'filterYear'=>$request->integer('year',now()->year)]);
    }

    public function monthly(Request $request): View
    {
        try {
            $month = $request->filled('month')
                ? Carbon::createFromFormat('!Y-m', (string) $request->input('month'))->startOfMonth()
                : now()->startOfMonth();
        } catch (\Throwable) {
            $month = now()->startOfMonth();
        }

        $query = LanguageTuitionPayment::query()
            ->with(['collector','charge.student.guardians','charge.course','charge.languageClass'])
            ->whereBetween('paid_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()]);
        if ($request->filled('q')) {
            $search = (string) $request->string('q')->trim();
            $query->where(function ($builder) use ($search) {
                $builder->where('receipt_code', 'like', "%{$search}%")
                    ->orWhereHas('charge.student', fn ($student) => $student
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereHas('guardians', fn ($guardians) => $guardians->where('phone', 'like', "%{$search}%")))
                    ->orWhereHas('charge.languageClass', fn ($class) => $class
                        ->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%"));
            });
        }
        if ($request->filled('receipt_status')) {
            $query->where('receipt_status', $request->input('receipt_status'));
        }

        $tuitionCollected = (float) (clone $query)->sum('amount');
        $bookCollected = (float) (clone $query)->sum('book_amount');
        $pendingCount = (clone $query)->where('receipt_status', 'pending')->count();

        return view('language.tuition.monthly', [
            'items' => $query->orderByDesc('paid_at')->paginate(\App\Support\Pagination::perPage())->withQueryString(),
            'month' => $month,
            'tuitionCollected' => $tuitionCollected,
            'bookCollected' => $bookCollected,
            'pendingCount' => $pendingCount,
        ]);
    }

    public function create(Request $request): View
    {
        $selectedClass=$request->integer('class');
        $classes=LanguageClass::where(function ($query) use ($selectedClass) {
            $query->whereIn('status',['recruiting','upcoming','active']);
            if ($selectedClass) $query->orWhere('id',$selectedClass);
        })->orderBy('name')->get();
        $enrollments=LanguageEnrollment::with(['student','languageClass.course'])->whereIn('status',['studying','paused','reserved'])->whereHas('languageClass',fn($q)=>$q->whereNotNull('language_course_id'))->whereDoesntHave('student.tuitionCharges',fn($q)=>$q->whereColumn('language_tuition_charges.language_class_id','language_enrollments.language_class_id'))->orderByDesc('enrolled_at')->get();
        return view('language.tuition.form',['item'=>new LanguageTuitionCharge,'enrollments'=>$enrollments,'leads'=>LanguageLead::whereNotNull('converted_student_id')->orderBy('name')->get(),'discounts'=>LanguageDiscountPolicy::where('active',1)->orderBy('name')->get(),'selectedStudent'=>$request->integer('student'),'selectedLead'=>$request->integer('lead'),'selectedClass'=>$selectedClass]);
    }

    public function show(LanguageTuitionCharge $languageTuition): View
    {
        return view('language.tuition.show', [
            'item' => $languageTuition->load(['student','course','languageClass.program','languageClass.level','discount','payments','incomingTransfers.fromClass','outgoingTransfers.toClass']),
            'bank' => self::bankSettings(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data=$request->validate(['language_student_id'=>'required|exists:language_students,id','language_lead_id'=>'nullable|exists:language_leads,id','language_course_id'=>'required|exists:language_courses,id','language_class_id'=>'required|exists:language_classes,id','language_discount_policy_id'=>'nullable|exists:language_discount_policies,id','due_date'=>'nullable|date','note'=>'nullable']);
        $enrolled=DB::table('language_enrollments')->where('language_student_id',$data['language_student_id'])->where('language_class_id',$data['language_class_id'])->whereIn('status',['studying','paused','reserved'])->exists();
        if(! $enrolled) throw ValidationException::withMessages(['language_class_id'=>'Học viên chưa được xếp vào lớp này nên không thể tạo phiếu thu.']);
        if(LanguageTuitionCharge::where('language_student_id',$data['language_student_id'])->where('language_class_id',$data['language_class_id'])->exists()) throw ValidationException::withMessages(['language_class_id'=>'Học viên này đã có phiếu thu cho lớp đã chọn. Mỗi học viên trong một lớp chỉ tạo một lần.']);
        $class=LanguageClass::findOrFail($data['language_class_id']);
        if(! $class->language_course_id) throw ValidationException::withMessages(['language_class_id'=>'Lớp chưa được liên kết với khóa học.']);
        $data['language_course_id']=$class->language_course_id;
        $course=LanguageCourse::findOrFail($data['language_course_id']);
        if (empty($data['language_lead_id'])) $data['language_lead_id']=$this->resolveLeadId((int)$data['language_student_id'],(int)$data['language_course_id']);
        $discount=!empty($data['language_discount_policy_id'])?LanguageDiscountPolicy::find($data['language_discount_policy_id']):null;
        $percentage=(float)($discount?->percentage??0); $amount=(float)$course->tuition;
        $data+=['code'=>CenterCode::next('language_tuition_charges','HP'),'original_amount'=>$amount,'discount_percentage'=>$percentage,'discount_amount'=>round($amount*$percentage/100,2),'payable_amount'=>round($amount*(100-$percentage)/100,2),'paid_amount'=>0,'credit_amount'=>0,'status'=>'unpaid','created_by'=>$request->user()->id];
        LanguageTuitionCharge::create($data);
        LanguageStudent::whereKey($data['language_student_id'])->update(['language_course_id'=>$data['language_course_id'],'language_discount_policy_id'=>$data['language_discount_policy_id'] ?? null]);
        return redirect()->route('language-tuition.index')->with('success','Đã lập khoản thu học phí.');
    }

    public function pay(Request $request, LanguageTuitionCharge $languageTuition): RedirectResponse
    {
        $data=$request->validate(['receipt_code'=>'nullable|string|max:30|unique:language_tuition_payments,receipt_code','amount'=>'required|numeric|gt:0','book_amount'=>'nullable|numeric|min:0','paid_at'=>'required|date','payment_method'=>['required',Rule::in(['cash','transfer','card','other'])],'reference'=>'nullable|max:255','note'=>'nullable'],['receipt_code.unique'=>'Số phiếu thu đã tồn tại.']);
        $data['book_amount'] = (float) ($data['book_amount'] ?? 0);
        if ($data['payment_method'] === 'transfer' && ! self::bankSettings()['enabled']) {
            throw ValidationException::withMessages(['payment_method' => 'Chưa cấu hình tài khoản ngân hàng nhận học phí.']);
        }
        $isPending=blank($data['receipt_code'] ?? null);
        $data['receipt_code']=$data['receipt_code'] ?: null;
        $data['receipt_status']=$isPending?'pending':'confirmed';
        $data['confirmed_at']=$isPending?null:now();
        $paymentId=null;
        DB::transaction(function () use ($data,$request,$languageTuition,&$paymentId) {
            $charge=LanguageTuitionCharge::lockForUpdate()->findOrFail($languageTuition->id);
            $remaining=$charge->remainingAmount();
            if ($data['amount']>$remaining+0.001) throw \Illuminate\Validation\ValidationException::withMessages(['amount'=>'Số tiền thu vượt quá công nợ còn lại '.number_format($remaining).'đ.']);
            $payment=$charge->payments()->create($data+['collected_by'=>$request->user()->id]);
            $paymentId=$payment->id;
            $this->refreshCharge($charge,$payment);
        });
        $redirect=redirect()->route('language-tuition.show',$languageTuition)
            ->with('success',$isPending?'Đã ghi nhận tiền ở trạng thái chờ bổ sung phiếu thu.':'Đã ghi nhận phiếu thu học phí.');
        $redirect->with('receipt_ready',$paymentId);
        return $redirect;
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
        return redirect()->route('language-tuition.show',$languageTuitionPayment->language_tuition_charge_id)->with('success','Đã bổ sung và xác nhận số phiếu thu.')->with('receipt_ready',$languageTuitionPayment->id);
    }

    public function receiptPrint(Request $request, LanguageTuitionPayment $languageTuitionPayment): View
    {
        return view('language.tuition.receipt',$this->receiptData($languageTuitionPayment)+['pdfMode'=>false,'autoPrint'=>!$request->boolean('preview')]);
    }

    public function receiptPdf(LanguageTuitionPayment $languageTuitionPayment)
    {
        $data=$this->receiptData($languageTuitionPayment)+['pdfMode'=>true,'autoPrint'=>false];
        $options=new Options(); $options->set('defaultFont','DejaVu Sans'); $options->set('isRemoteEnabled',false); $options->setChroot(public_path());
        $dompdf=new Dompdf($options); $dompdf->loadHtml(view('language.tuition.receipt',$data)->render(),'UTF-8'); $dompdf->setPaper('A5','landscape'); $dompdf->render();
        $receiptLabel=$languageTuitionPayment->receipt_code ?: 'tam-'.$languageTuitionPayment->id;
        $filename='phieu-thu-'.preg_replace('/[^A-Za-z0-9_-]/','-',$receiptLabel).'.pdf';
        return response($dompdf->output(),200,['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="'.$filename.'"']);
    }

    private function receiptData(LanguageTuitionPayment $payment): array
    {
        $payment->load(['collector','charge.student','charge.course','charge.languageClass']);
        $logoPath=SystemSetting::valueOf('logo_path'); $logoData=null;
        if($logoPath&&str_starts_with($logoPath,'uploads/branding/')){$fullPath=public_path($logoPath);if(is_file($fullPath))$logoData='data:'.(mime_content_type($fullPath)?:'image/png').';base64,'.base64_encode(file_get_contents($fullPath));}
        return ['payment'=>$payment,'charge'=>$payment->charge,'student'=>$payment->charge->student,'softwareName'=>SystemSetting::valueOf('software_name','E-SKY CENTER'),'logoData'=>$logoData,'totalAmount'=>(float)$payment->amount+(float)$payment->book_amount,'amountInWords'=>$this->amountInVietnameseWords((int)round((float)$payment->amount+(float)$payment->book_amount))];
    }

    private function amountInVietnameseWords(int $number): string
    {
        if($number===0)return 'Không đồng'; $digits=['không','một','hai','ba','bốn','năm','sáu','bảy','tám','chín'];$units=['','nghìn','triệu','tỷ','nghìn tỷ','triệu tỷ'];$groups=[];
        for($n=$number;$n>0;$n=intdiv($n,1000))$groups[]=$n%1000;$parts=[];
        for($i=count($groups)-1;$i>=0;$i--){$g=$groups[$i];if($g===0)continue;$hundreds=intdiv($g,100);$tens=intdiv($g%100,10);$ones=$g%10;$words=[];$full=$i<count($groups)-1;if($hundreds>0||$full){$words[]=$digits[$hundreds];$words[]='trăm';}if($tens>1){$words[]=$digits[$tens];$words[]='mươi';}elseif($tens===1)$words[]='mười';elseif($ones>0&&($hundreds>0||$full))$words[]='lẻ';if($ones>0)$words[]=$ones===1&&$tens>1?'mốt':($ones===5&&$tens>0?'lăm':$digits[$ones]);if($units[$i]!=='')$words[]=$units[$i];$parts[]=implode(' ',$words);}
        return ucfirst(implode(' ',$parts)).' đồng';
    }
    public function export(Request $request)
    {
        $query=LanguageTuitionCharge::with(['student','course']); $this->applyPeriod($query,$request);
        return ExcelExporter::download('thu-hoc-phi-'.date('Ymd').'.xlsx',['Mã khoản thu','Học viên','Khóa học','Học phí','Giảm %','Phải thu','Đã thu','Chuyển sang','Còn lại','Trạng thái','Hạn đóng'],$query->get()->map(fn($item)=>[$item->code,$item->student->name,$item->course->name,$item->original_amount,$item->discount_percentage,$item->payable_amount,$item->paid_amount,$item->credit_amount,$item->remainingAmount(),$item->status,$item->due_date?->format('d/m/Y')]));
    }

    public function downloadQr(Request $request, LanguageTuitionCharge $languageTuition)
    {
        $languageTuition->load(['student','languageClass']);
        abort_unless(self::bankSettings()['enabled'], 422, 'Chưa cấu hình tài khoản ngân hàng nhận học phí.');
        $remaining = $languageTuition->remainingAmount();
        $tuitionAmount = $request->filled('amount') ? min($remaining, max(1, $request->integer('amount'))) : $remaining;
        $bookAmount = max(0, $request->integer('book_amount'));
        $totalAmount = $tuitionAmount + $bookAmount;
        $content = trim(Str::limit(Str::ascii($languageTuition->student->name.' '.($languageTuition->languageClass?->code ?? 'CHUA XEP LOP')), 50, ''));
        $response = Http::timeout(15)->get(self::qrUrl($totalAmount, $content));
        abort_unless($response->successful(), 502, 'Không thể tải ảnh VietQR. Vui lòng thử lại.');
        return response($response->body(), 200, [
            'Content-Type' => $response->header('Content-Type', 'image/png'),
            'Content-Disposition' => 'attachment; filename="vietqr-'.$languageTuition->code.'.png"',
        ]);
    }
    public static function qrUrl(float $amount, string $content): string
    {
        $bank = self::bankSettings();
        abort_unless($bank['enabled'], 422, 'Chưa cấu hình tài khoản ngân hàng nhận học phí.');
        return 'https://img.vietqr.io/image/'.$bank['bin'].'-'.$bank['account_number'].'-compact2.png?'.http_build_query([
            'amount' => (int) round($amount),
            'addInfo' => $content,
            'accountName' => $bank['account_name'],
        ]);
    }

    public static function bankSettings(): array
    {
        $enabled = SystemSetting::valueOf('bank_enabled', '0') === '1';
        $bank = [
            'enabled' => $enabled,
            'bin' => trim((string) SystemSetting::valueOf('bank_bin', '')),
            'name' => trim((string) SystemSetting::valueOf('bank_name', '')),
            'account_number' => trim((string) SystemSetting::valueOf('bank_account_number', '')),
            'account_name' => trim((string) SystemSetting::valueOf('bank_account_name', '')),
            'branch' => trim((string) SystemSetting::valueOf('bank_branch', '')),
        ];
        $bank['enabled'] = $bank['enabled'] && $bank['bin'] !== '' && $bank['account_number'] !== '' && $bank['account_name'] !== '';
        return $bank;
    }

    private function applyPeriod($query, Request $request): void
    {
        $year = max(2020, min(2100, $request->integer('year', now()->year)));
        $query->whereYear('created_at', $year);

        if ($request->filled('month')) {
            $query->whereMonth('created_at', max(1, min(12, $request->integer('month'))));
            return;
        }

        if ($request->filled('quarter')) {
            $quarter = max(1, min(4, $request->integer('quarter')));
            $firstMonth = (($quarter - 1) * 3) + 1;
            $query->whereMonth('created_at', '>=', $firstMonth)
                ->whereMonth('created_at', '<=', $firstMonth + 2);
        }
    }

    private function resolveLeadId(int $studentId, int $courseId): ?int
    {
        return LanguageLead::where('converted_student_id', $studentId)
            ->orderByRaw('language_course_id = ? desc', [$courseId])
            ->latest('id')
            ->value('id');
    }

    private function refreshCharge(LanguageTuitionCharge $charge, LanguageTuitionPayment $payment): void
    {
        $paidAmount = (float) $charge->payments()->sum('amount');
        $hasPendingReceipt = $charge->payments()->where('receipt_status', 'pending')->exists();
        $settledAmount = $paidAmount + (float) $charge->credit_amount;
        $status = $hasPendingReceipt
            ? 'pending_receipt'
            : ($settledAmount >= (float) $charge->payable_amount ? 'paid' : ($settledAmount > 0 ? 'partial' : 'unpaid'));

        $charge->update(['paid_amount' => $paidAmount, 'status' => $status]);

        if ($payment->receipt_status !== 'confirmed') {
            return;
        }

        $charge->loadMissing('lead');
        LanguageMonthlyTargetRecord::updateOrCreate(
            ['language_tuition_payment_id' => $payment->id],
            [
                'record_year' => $payment->paid_at->year,
                'record_month' => $payment->paid_at->month,
                'language_student_id' => $charge->language_student_id,
                'language_lead_id' => $charge->language_lead_id,
                'language_collaborator_id' => $charge->lead?->language_collaborator_id,
                'language_course_id' => $charge->language_course_id,
                'quantity' => 1,
                'revenue' => (float) $payment->amount + (float) $payment->book_amount,
                'note' => 'Thu học phí '.$charge->code,
            ]
        );
    }
}
