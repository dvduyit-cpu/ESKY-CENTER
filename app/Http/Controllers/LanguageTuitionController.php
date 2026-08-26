<?php

namespace App\Http\Controllers;

use App\Models\{LanguageClass, LanguageCourse, LanguageDiscountPolicy, LanguageEnrollment, LanguageLead, LanguageMonthlyTargetRecord, LanguageStudent, LanguageTuitionCharge, LanguageTuitionPayment};
use App\Support\{CenterCode, ExcelExporter, LanguageDiscountResolver, LanguageTuitionMonthlySync, LanguageTuitionSpreadsheet, SpreadsheetSupport};
use Illuminate\Database\Eloquent\Builder;
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
use Symfony\Component\HttpFoundation\StreamedResponse;

class LanguageTuitionController extends Controller
{
    public function index(Request $request): View
    {
        $query = $this->filteredTuitionQuery($request, ['student', 'course', 'languageClass', 'discount', 'payments'])
            ->withSum('payments', 'amount');
        return view('language.tuition.index',[
            'items'=>$query->paginate(\App\Support\Pagination::perPage())->withQueryString(),
            'filterYear'=>$request->integer('year',now()->year),
            'classes'=>LanguageClass::query()
                ->whereNull('deleted_at')
                ->whereHas('tuitionCharges')
                ->orderBy('code')
                ->get(['id','code','name']),
        ]);
    }

    public function monthly(Request $request): View
    {
        $month = $this->resolveMonthlyDate($request);
        $query = $this->monthlyPaymentsQuery($request, $month);
        $summaryQuery = (clone $query)->where('receipt_status', 'confirmed');

        $tuitionCollected = (float) (clone $summaryQuery)->sum('amount');
        $bookCollected = (float) (clone $summaryQuery)->sum('book_amount');
        $pendingCount = (clone $query)->where('receipt_status', 'pending')->count();

        return view('language.tuition.monthly', [
            'items' => $query->orderByDesc('paid_at')->paginate(\App\Support\Pagination::perPage())->withQueryString(),
            'month' => $month,
            'tuitionCollected' => $tuitionCollected,
            'bookCollected' => $bookCollected,
            'pendingCount' => $pendingCount,
        ]);
    }

    public function monthlyPdf(Request $request)
    {
        $month = $this->resolveMonthlyDate($request);
        $query = $this->monthlyPaymentsQuery($request, $month);
        $summaryQuery = (clone $query)->where('receipt_status', 'confirmed');
        $items = $query->orderByDesc('paid_at')->get();
        $tuitionCollected = (float) (clone $summaryQuery)->sum('amount');
        $bookCollected = (float) (clone $summaryQuery)->sum('book_amount');
        $pendingCount = (clone $query)->where('receipt_status', 'pending')->count();

        $data = [
            'items' => $items,
            'month' => $month,
            'tuitionCollected' => $tuitionCollected,
            'bookCollected' => $bookCollected,
            'pendingCount' => $pendingCount,
            'filters' => [
                'q' => trim((string) $request->string('q')),
                'receipt_status' => (string) $request->input('receipt_status', ''),
            ],
        ];

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isFontSubsettingEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->setChroot(public_path());

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('language.tuition.monthly-pdf', $data)->render(), 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="thu-hoc-phi-thang-'.$month->format('m-Y').'.pdf"',
        ]);
    }

    public function create(Request $request): View
    {
        $selectedClass = $request->integer('class');
        $selectedCourse = old('language_course_id', $request->integer('course'));
        $selectedMode = old('entry_mode', $request->string('mode')->toString() === 'quick' ? 'quick' : 'class');

        $classes = LanguageClass::whereNull('deleted_at')->where(function ($query) use ($selectedClass) {
            $query->whereIn('status', ['recruiting', 'upcoming', 'active']);
            if ($selectedClass) {
                $query->orWhere('id', $selectedClass);
            }
        })->orderBy('name')->get();

        $enrollments = LanguageEnrollment::with(['student', 'languageClass.course'])
            ->whereIn('status', ['studying', 'paused', 'reserved'])
            ->whereHas('languageClass', fn ($query) => $query
                ->whereNull('language_classes.deleted_at')
                ->whereNotNull('language_course_id'))
            ->whereDoesntHave('student.tuitionCharges', fn ($query) => $query
                ->whereColumn('language_tuition_charges.language_class_id', 'language_enrollments.language_class_id'))
            ->orderByDesc('enrolled_at')
            ->get();

        $students = LanguageStudent::query()
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'phone', 'email', 'address']);

        $courses = LanguageCourse::query()
            ->where(function ($query) use ($selectedCourse) {
                $query->where('active', true);
                if ($selectedCourse) {
                    $query->orWhere('id', $selectedCourse);
                }
            })
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'tuition', 'textbook']);

        return view('language.tuition.form', [
            'item' => new LanguageTuitionCharge,
            'enrollments' => $enrollments,
            'students' => $students,
            'courses' => $courses,
            'bank' => self::bankSettings(),
            'discounts' => LanguageDiscountPolicy::where('active', 1)->orderBy('name')->get(),
            'selectedStudent' => $request->integer('student'),
            'selectedLead' => $request->integer('lead'),
            'selectedClass' => $selectedClass,
            'selectedCourse' => $selectedCourse,
            'selectedMode' => $selectedMode,
        ]);
    }

    public function import(Request $request, LanguageTuitionSpreadsheet $spreadsheet): RedirectResponse|StreamedResponse|\Illuminate\Http\JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ], [
            'file.required' => 'Vui lòng chọn file Excel.',
            'file.mimes' => 'File tải lên phải có định dạng .xlsx, .xls hoặc .csv.',
            'file.max' => 'File Excel không được lớn hơn 10 MB.',
        ]);

        $streamProgress = $request->header('X-Import-Progress') === 'stream';
        $validateOnly = $request->header('X-Import-Validate') === 'preview';
        $file = $request->file('file');
        if (! SpreadsheetSupport::canReadUpload($file)) {
            $message = SpreadsheetSupport::missingZipImportMessage(
                SpreadsheetSupport::uploadedExtension($file)
            );

            if ($streamProgress || $validateOnly) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()->route('language-tuition.index')->withErrors([
                'file' => $message,
            ]);
        }

        if ($validateOnly) {
            try {
                $result = $spreadsheet->import($file, $request->user()?->id, null, true);
            } catch (\Throwable $exception) {
                return response()->json([
                    'message' => 'Không thể kiểm tra file: '.$exception->getMessage(),
                ], 422);
            }

            return response()->json([
                'ok' => $result['failed'] === 0,
                'message' => $result['failed'] === 0
                    ? ($result['success'] > 0
                        ? 'File hợp lệ. Bạn có thể bấm Cập nhật học phí.'
                        : 'File hợp lệ nhưng chưa có dòng nào cần cập nhật.')
                    : "Đã kiểm tra xong, có {$result['failed']} dòng lỗi cần sửa trước khi nhập.",
                'result' => $result,
            ]);
        }

        if ($streamProgress) {
            return response()->stream(function () use ($spreadsheet, $file, $request): void {
                $emit = static function (array $event): void {
                    echo json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";
                    if (ob_get_level() > 0) {
                        @ob_flush();
                    }
                    flush();
                };

                $emit([
                    'type' => 'preparing',
                    'message' => 'Đang đọc và kiểm tra file Excel...',
                ]);

                try {
                    $result = $spreadsheet->import($file, $request->user()?->id, $emit);

                    $emit([
                        'type' => 'complete',
                        'message' => "Đã xử lý {$result['total']} dòng: tạo {$result['created']}, cập nhật {$result['updated']}, lỗi {$result['failed']}.",
                        'result' => $result,
                    ]);
                } catch (\Throwable $exception) {
                    $emit([
                        'type' => 'error',
                        'message' => 'Không thể nhập file: '.$exception->getMessage(),
                    ]);
                }
            }, 200, [
                'Content-Type' => 'application/x-ndjson; charset=UTF-8',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'X-Accel-Buffering' => 'no',
            ]);
        }

        try {
            $result = $spreadsheet->import($file, $request->user()?->id);
        } catch (\Throwable $exception) {
            return back()->withErrors(['file' => 'Không thể nhập file: '.$exception->getMessage()]);
        }

        $redirect = redirect()->route('language-tuition.index')->with(
            $result['failed'] > 0 ? 'warning' : 'success',
            "Đã tạo {$result['created']} phiếu mới, cập nhật {$result['updated']} phiếu, bỏ qua {$result['skipped']} dòng trống. Có {$result['failed']} dòng lỗi."
        );
        if ($result['errors']) {
            $redirect->with('tuition_import_errors', $result['errors']);
        }

        return $redirect;
    }

    public function confirmPendingReceipt(Request $request, LanguageTuitionPayment $languageTuitionPayment): RedirectResponse
    {
        $data = $request->validate([
            'receipt_code' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('language_tuition_payments', 'receipt_code')->ignore($languageTuitionPayment->id),
            ],
        ], [
            'receipt_code.unique' => 'Sá»‘ phiáº¿u thu Ä‘Ã£ tá»“n táº¡i.',
        ]);

        $receiptCode = trim((string) ($data['receipt_code'] ?? $languageTuitionPayment->receipt_code ?? ''));
        if ($receiptCode === '') {
            throw ValidationException::withMessages([
                'receipt_code' => 'Vui lÃ²ng nháº­p sá»‘ phiáº¿u thu.',
            ]);
        }

        DB::transaction(function () use ($languageTuitionPayment, $receiptCode) {
            $payment = LanguageTuitionPayment::lockForUpdate()->findOrFail($languageTuitionPayment->id);
            abort_if($payment->receipt_status === 'confirmed', 422, 'Phiáº¿u thu nÃ y Ä‘Ã£ Ä‘Æ°á»£c xÃ¡c nháº­n.');
            abort_if($payment->receipt_status === 'cancelled', 422, 'Phiáº¿u thu nÃ y Ä‘Ã£ bá»‹ há»§y nÃªn khÃ´ng thá»ƒ xÃ¡c nháº­n láº¡i.');

            $payment->update([
                'receipt_code' => $receiptCode,
                'receipt_status' => 'confirmed',
                'confirmed_at' => now(),
            ]);

            $this->refreshCharge(
                LanguageTuitionCharge::lockForUpdate()->findOrFail($payment->language_tuition_charge_id),
                $payment
            );
        });

        return redirect()
            ->route('language-tuition.show', $languageTuitionPayment->language_tuition_charge_id)
            ->with('success', 'ÄÃ£ bá»• sung vÃ  xÃ¡c nháº­n sá»‘ phiáº¿u thu.')
            ->with('receipt_ready', $languageTuitionPayment->id);
    }

    public function template(LanguageTuitionSpreadsheet $spreadsheet): StreamedResponse
    {
        return $spreadsheet->template();
    }

    public function checkMonthlySync(LanguageTuitionMonthlySync $sync): RedirectResponse
    {
        $scopeDate = request('sync_date');
        $report = $sync->inspect($scopeDate);

        return redirect()
            ->route('language-tuition.index')
            ->with(
                $report['has_issues'] ? 'warning' : 'success',
                $report['has_issues']
                    ? 'Đã kiểm tra xong. Có dữ liệu thu học phí theo tháng cần đồng bộ lại.'
                    : 'Đã kiểm tra xong. Dữ liệu thu học phí và thu học phí theo tháng đã khớp.'
            )
            ->with('tuition_monthly_sync_report', $report);
    }

    public function applyMonthlySync(LanguageTuitionMonthlySync $sync): RedirectResponse
    {
        $scopeDate = request('sync_date');
        $result = $sync->sync($scopeDate);
        $report = $sync->inspect($scopeDate);

        $message = 'Đã cập nhật đồng bộ sang thu học phí theo tháng: '
            .'xác nhận '.$result['confirmed_pending_payments'].' phiếu chờ, '
            .'tạo '.$result['created_monthly_records'].' bản ghi tháng, '
            .'cập nhật '.$result['updated_monthly_records'].' bản ghi tháng, '
            .'xóa '.$result['removed_monthly_records'].' bản ghi dư, '
            .'tính lại '.$result['refreshed_charges'].' khoản thu.';

        return redirect()
            ->route('language-tuition.index')
            ->with($report['has_issues'] ? 'warning' : 'success', $message)
            ->with('tuition_monthly_sync_report', $report);
    }

    public function outstandingSheet(Request $request, LanguageTuitionSpreadsheet $spreadsheet): StreamedResponse
    {
        $charges = $this->filteredTuitionQuery($request, ['student', 'languageClass', 'payments'])
            ->when(
                ! $request->filled('status'),
                fn (Builder $query) => $query->whereIn('status', ['unpaid', 'partial', 'pending_receipt'])
            )
            ->reorder()
            ->orderBy('language_class_id')
            ->orderBy('code')
            ->get();

        return $spreadsheet->outstandingSheet(
            $charges,
            'danh-sach-hoc-phi-con-no-'.now()->format('Ymd-His').'.xlsx'
        );
    }

    public function show(LanguageTuitionCharge $languageTuition): View
    {
        $languageTuition->load(['student','course','languageClass.program','languageClass.level','languageClass.discountPolicy','discount','payments.collector','payments.canceller','incomingTransfers.fromClass','outgoingTransfers.toClass']);
        $keptDiscountIds=array_filter([
            $languageTuition->language_discount_policy_id,
            $languageTuition->student?->language_discount_policy_id,
            $languageTuition->languageClass?->language_discount_policy_id,
        ]);
        return view('language.tuition.show', [
            'item' => $languageTuition,
            'bank' => self::bankSettings(),
            'discounts' => LanguageDiscountPolicy::query()
                ->where(fn ($query) => $query->where('active',1)->when($keptDiscountIds,fn ($active,$ids) => $active->orWhereIn('id',$ids)))
                ->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->input('entry_mode') === 'quick') {
            return $this->storeQuickCollection($request);
        }

        $data=$request->validate([
            'language_student_id'=>'required|exists:language_students,id',
            'language_lead_id'=>'nullable|exists:language_leads,id',
            'language_course_id'=>'required|exists:language_courses,id',
            'language_class_id'=>['required', Rule::exists('language_classes', 'id')->where(fn ($query) => $query->whereNull('deleted_at'))],
            'language_discount_policy_id'=>'nullable|exists:language_discount_policies,id',
            'due_date'=>'nullable|date',
            'note'=>'nullable',
        ]);
        $enrolled=DB::table('language_enrollments')->where('language_student_id',$data['language_student_id'])->where('language_class_id',$data['language_class_id'])->whereIn('status',['studying','paused','reserved'])->exists();
        if(! $enrolled) throw ValidationException::withMessages(['language_class_id'=>'Học viên chưa được xếp vào lớp này nên không thể tạo phiếu thu.']);
        if(LanguageTuitionCharge::where('language_student_id',$data['language_student_id'])->where('language_class_id',$data['language_class_id'])->exists()) throw ValidationException::withMessages(['language_class_id'=>'Học viên này đã có phiếu thu cho lớp đã chọn. Mỗi học viên trong một lớp chỉ tạo một lần.']);
        $class=LanguageClass::findOrFail($data['language_class_id']);
        if(! $class->language_course_id) throw ValidationException::withMessages(['language_class_id'=>'Lớp chưa được liên kết với khóa học.']);
        $data['language_course_id']=$class->language_course_id;
        $course=LanguageCourse::findOrFail($data['language_course_id']);
        $student=LanguageStudent::findOrFail($data['language_student_id']);
        if (empty($data['language_lead_id'])) $data['language_lead_id']=$this->resolveLeadId((int)$data['language_student_id'],(int)$data['language_course_id']);
        $personalDiscountId=$data['language_discount_policy_id']??$student->language_discount_policy_id;
        $studentDiscount=$personalDiscountId?LanguageDiscountPolicy::findOrFail($personalDiscountId):null;
        $classDiscount=$class->language_discount_policy_id?LanguageDiscountPolicy::find($class->language_discount_policy_id):null;
        $discount=LanguageDiscountResolver::highest($classDiscount,$studentDiscount);
        $data['language_discount_policy_id']=$discount?->id;
        $percentage=(float)($discount?->percentage??0); $amount=(float)$class->default_tuition;
        $payableAmount=round($amount*(100-$percentage)/100,2);
        $data+=['code'=>CenterCode::next('language_tuition_charges','HP'),'original_amount'=>$amount,'discount_percentage'=>$percentage,'discount_amount'=>round($amount*$percentage/100,2),'payable_amount'=>$payableAmount,'paid_amount'=>0,'credit_amount'=>0,'status'=>$payableAmount>0?'unpaid':'paid','created_by'=>$request->user()->id];
        LanguageTuitionCharge::create($data);
        $student->update(['language_course_id'=>$data['language_course_id'],'language_discount_policy_id'=>$personalDiscountId]);
        return redirect()->route('language-tuition.index')->with('success','Đã lập khoản thu học phí.');
    }

    private function storeQuickCollection(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'language_student_id' => ['required', 'exists:language_students,id'],
            'language_course_id' => ['required', 'exists:language_courses,id'],
            'language_discount_policy_id' => ['nullable', 'exists:language_discount_policies,id'],
            'original_amount' => ['required', 'numeric', 'gt:0'],
            'collected_amount' => ['required', 'numeric', 'gt:0'],
            'receipt_code' => ['nullable', 'string', 'max:30', 'unique:language_tuition_payments,receipt_code'],
            'paid_at' => ['required', 'date'],
            'payment_method' => ['required', Rule::in(['cash', 'transfer', 'card', 'other'])],
            'reference' => ['nullable', 'max:255'],
            'note' => ['nullable'],
            'submit_action' => ['nullable', Rule::in(['save', 'print'])],
        ], [
            'receipt_code.unique' => 'Số phiếu thu đã tồn tại.',
        ]);

        if ($data['payment_method'] === 'transfer' && ! self::bankSettings()['enabled']) {
            throw ValidationException::withMessages([
                'payment_method' => 'Chưa cấu hình tài khoản ngân hàng nhận học phí.',
            ]);
        }

        $student = LanguageStudent::findOrFail($data['language_student_id']);
        $discount = ! empty($data['language_discount_policy_id'])
            ? LanguageDiscountPolicy::findOrFail($data['language_discount_policy_id'])
            : null;

        $originalAmount = round((float) $data['original_amount'], 2);
        $discountPercentage = (float) ($discount?->percentage ?? 0);
        $discountAmount = round($originalAmount * $discountPercentage / 100, 2);
        $payableAmount = max(0, round($originalAmount - $discountAmount, 2));
        $collectedAmount = round((float) $data['collected_amount'], 2);

        if ($collectedAmount > $payableAmount + 0.001) {
            throw ValidationException::withMessages([
                'collected_amount' => 'Số tiền thu không được lớn hơn số phải thu sau miễn giảm.',
            ]);
        }

        $isPending = blank($data['receipt_code'] ?? null);
        $receiptCode = $data['receipt_code'] ?: null;
        $leadId = $this->resolveLeadId((int) $data['language_student_id'], (int) $data['language_course_id']);
        $chargeId = null;
        $paymentId = null;

        DB::transaction(function () use (
            $collectedAmount,
            $data,
            $discount,
            $discountAmount,
            $discountPercentage,
            $isPending,
            $leadId,
            $originalAmount,
            $payableAmount,
            $receiptCode,
            $request,
            $student,
            &$chargeId,
            &$paymentId
        ) {
            $charge = LanguageTuitionCharge::create([
                'code' => CenterCode::next('language_tuition_charges', 'HP'),
                'language_student_id' => $data['language_student_id'],
                'language_lead_id' => $leadId,
                'language_course_id' => $data['language_course_id'],
                'language_class_id' => null,
                'language_discount_policy_id' => $discount?->id,
                'original_amount' => $originalAmount,
                'discount_percentage' => $discountPercentage,
                'discount_amount' => $discountAmount,
                'payable_amount' => $payableAmount,
                'paid_amount' => 0,
                'credit_amount' => 0,
                'due_date' => Carbon::parse($data['paid_at'])->toDateString(),
                'status' => 'unpaid',
                'note' => $data['note'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            $payment = $charge->payments()->create([
                'receipt_code' => $receiptCode,
                'receipt_status' => $isPending ? 'pending' : 'confirmed',
                'confirmed_at' => $isPending ? null : now(),
                'amount' => $collectedAmount,
                'book_amount' => 0,
                'paid_at' => $data['paid_at'],
                'payment_method' => $data['payment_method'],
                'reference' => $data['reference'] ?? null,
                'note' => $data['note'] ?? null,
                'collected_by' => $request->user()->id,
            ]);

            $this->refreshCharge($charge, $payment);

            $chargeId = $charge->id;
            $paymentId = $payment->id;

            $student->update([
                'language_course_id' => $data['language_course_id'],
                'language_discount_policy_id' => $discount?->id,
            ]);
        });

        if (($data['submit_action'] ?? 'save') === 'print') {
            return redirect()->route('language-tuition.receipt.print', $paymentId);
        }

        return redirect()
            ->route('language-tuition.show', $chargeId)
            ->with('success', $isPending ? 'Đã ghi nhận thu tiền, đang chờ bổ sung số phiếu.' : 'Đã thu học phí và tạo phiếu thu.')
            ->with('receipt_ready', $paymentId);
    }

    public function updateDiscount(Request $request, LanguageTuitionCharge $languageTuition): RedirectResponse
    {
        $data=$request->validate([
            'language_discount_policy_id'=>'nullable|integer|exists:language_discount_policies,id',
            'apply_mode'=>['nullable', Rule::in(['highest', 'student'])],
        ]);
        $applyMode=$data['apply_mode'] ?? 'highest';
        DB::transaction(function()use($applyMode,$data,$languageTuition){
            $charge=LanguageTuitionCharge::lockForUpdate()->findOrFail($languageTuition->id);
            if($charge->outgoingTransfers()->exists())throw ValidationException::withMessages(['language_discount_policy_id'=>'Khoản thu đã quyết toán chuyển lớp nên không thể đổi chế độ miễn giảm.']);
            $personalDiscount=!empty($data['language_discount_policy_id'])?LanguageDiscountPolicy::findOrFail($data['language_discount_policy_id']):null;
            if($personalDiscount&&!$personalDiscount->active&&(int)$personalDiscount->id!==(int)$charge->student?->language_discount_policy_id)throw ValidationException::withMessages(['language_discount_policy_id'=>'Chế độ miễn giảm đã ngừng hoạt động.']);
            $classDiscount=$charge->languageClass?->language_discount_policy_id
                ? LanguageDiscountPolicy::find($charge->languageClass->language_discount_policy_id)
                : null;
            $discount=$this->resolveManualDiscount($applyMode,$classDiscount,$personalDiscount);

            $percentage=(float)($discount?->percentage??0);
            $discountAmount=round((float)$charge->original_amount*$percentage/100,2);
            $payableAmount=max(0,round((float)$charge->original_amount-$discountAmount,2));
            $settledAmount=(float)$charge->paid_amount+(float)$charge->credit_amount;
            if($payableAmount+0.001<$settledAmount)throw ValidationException::withMessages(['language_discount_policy_id'=>'Không thể áp dụng vì số đã thu/chuyển sang lớn hơn học phí sau miễn giảm.']);
            $hasPendingReceipt=$charge->payments()->where('receipt_status','pending')->exists();
            $status=$hasPendingReceipt?'pending_receipt':($settledAmount+0.001>=$payableAmount?'paid':($settledAmount>0?'partial':'unpaid'));
            $charge->update([
                'language_discount_policy_id'=>$discount?->id,
                'discount_percentage'=>$percentage,
                'discount_amount'=>$discountAmount,
                'payable_amount'=>$payableAmount,
                'status'=>$status,
            ]);
            LanguageStudent::whereKey($charge->language_student_id)->update(['language_discount_policy_id'=>$personalDiscount?->id]);
        });

        return back()->with('success','Đã so sánh miễn giảm của lớp và học viên, chỉ áp dụng mức cao hơn để tính lại học phí.');
    }

    public function bulkApplyHighest(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:language_tuition_charges,id'],
        ]);

        $updated = 0;
        $skipped = 0;

        DB::transaction(function () use (&$skipped, &$updated, $data) {
            $charges = LanguageTuitionCharge::query()
                ->with(['student', 'languageClass'])
                ->whereIn('id', $data['ids'])
                ->lockForUpdate()
                ->get();

            foreach ($charges as $charge) {
                if ($charge->outgoingTransfers()->exists()) {
                    $skipped++;
                    continue;
                }

                $studentDiscount = $charge->student?->language_discount_policy_id
                    ? LanguageDiscountPolicy::find($charge->student->language_discount_policy_id)
                    : null;
                $classDiscount = $charge->languageClass?->language_discount_policy_id
                    ? LanguageDiscountPolicy::find($charge->languageClass->language_discount_policy_id)
                    : null;
                $discount = LanguageDiscountResolver::highest($classDiscount, $studentDiscount);
                $percentage = (float) ($discount?->percentage ?? 0);
                $discountAmount = round((float) $charge->original_amount * $percentage / 100, 2);
                $payableAmount = max(0, round((float) $charge->original_amount - $discountAmount, 2));
                $settledAmount = (float) $charge->paid_amount + (float) $charge->credit_amount;

                if ($payableAmount + 0.001 < $settledAmount) {
                    $skipped++;
                    continue;
                }

                $hasPendingReceipt = $charge->payments()->where('receipt_status', 'pending')->exists();
                $status = $hasPendingReceipt
                    ? 'pending_receipt'
                    : ($settledAmount + 0.001 >= $payableAmount ? 'paid' : ($settledAmount > 0 ? 'partial' : 'unpaid'));

                $charge->update([
                    'language_discount_policy_id' => $discount?->id,
                    'discount_percentage' => $percentage,
                    'discount_amount' => $discountAmount,
                    'payable_amount' => $payableAmount,
                    'status' => $status,
                ]);

                $updated++;
            }
        });

        $message = "Da ap dung muc mien giam cao nhat cho {$updated} khoan thu.";
        if ($skipped > 0) {
            return back()->with('warning', $message." Bo qua {$skipped} khoan thu khong the cap nhat.");
        }

        return back()->with('success', $message);
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
            abort_if($payment->receipt_status==='cancelled',422,'Phiếu thu này đã bị hủy nên không thể xác nhận lại.');
            $payment->update(['receipt_code'=>$data['receipt_code'],'receipt_status'=>'confirmed','confirmed_at'=>now()]);
            $this->refreshCharge(LanguageTuitionCharge::lockForUpdate()->findOrFail($payment->language_tuition_charge_id),$payment);
        });
        return redirect()->route('language-tuition.show',$languageTuitionPayment->language_tuition_charge_id)->with('success','Đã bổ sung và xác nhận số phiếu thu.')->with('receipt_ready',$languageTuitionPayment->id);
    }

    public function cancelReceipt(Request $request, LanguageTuitionPayment $languageTuitionPayment): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Chỉ admin mới được hủy phiếu thu.');

        $data = $request->validate([
            'cancel_reason' => ['required', 'string', 'max:1000'],
        ], [
            'cancel_reason.required' => 'Vui lòng nhập lý do hủy phiếu thu.',
            'cancel_reason.max' => 'Lý do hủy không được vượt quá 1000 ký tự.',
        ]);

        $chargeId = $languageTuitionPayment->language_tuition_charge_id;

        DB::transaction(function () use ($data, $languageTuitionPayment, $request) {
            $payment = LanguageTuitionPayment::lockForUpdate()->findOrFail($languageTuitionPayment->id);
            abort_if($payment->receipt_status === 'cancelled', 422, 'Phiếu thu này đã được hủy trước đó.');

            $payment->update([
                'receipt_status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => $request->user()->id,
                'cancel_reason' => trim((string) $data['cancel_reason']),
            ]);

            LanguageMonthlyTargetRecord::where('language_tuition_payment_id', $payment->id)->delete();

            $this->refreshCharge(
                LanguageTuitionCharge::lockForUpdate()->findOrFail($payment->language_tuition_charge_id),
                $payment
            );
        });

        return redirect()
            ->route('language-tuition.show', $chargeId)
            ->with('success', 'Đã hủy phiếu thu và hoàn nguyên số tiền đã ghi nhận.');
    }

    public function receiptPrint(Request $request, LanguageTuitionPayment $languageTuitionPayment): View
    {
        abort_if($languageTuitionPayment->receipt_status === 'cancelled', 422, 'Phiếu thu này đã bị hủy.');

        return view('language.tuition.receipt',$this->receiptData($languageTuitionPayment)+['pdfMode'=>false,'autoPrint'=>!$request->boolean('preview')]);
    }

    public function receiptPdf(LanguageTuitionPayment $languageTuitionPayment)
    {
        abort_if($languageTuitionPayment->receipt_status === 'cancelled', 422, 'Phiếu thu này đã bị hủy.');

        $data=$this->receiptData($languageTuitionPayment)+['pdfMode'=>true,'autoPrint'=>false];
        $options=new Options(); $options->set('defaultFont','DejaVu Sans'); $options->set('isFontSubsettingEnabled',true); $options->set('isRemoteEnabled',false); $options->setChroot(public_path());
        $dompdf=new Dompdf($options); $dompdf->loadHtml(view('language.tuition.receipt',$data)->render(),'UTF-8'); $dompdf->setPaper('A5','landscape'); $dompdf->render();
        $receiptLabel=$languageTuitionPayment->receipt_code ?: 'tam-'.$languageTuitionPayment->id;
        $filename='phieu-thu-'.preg_replace('/[^A-Za-z0-9_-]/','-',$receiptLabel).'.pdf';
        return response($dompdf->output(),200,['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="'.$filename.'"']);
    }

    private function receiptData(LanguageTuitionPayment $payment): array
    {
        $payment->load(['collector','charge.student.guardians','charge.course','charge.languageClass','charge.discount']);
        $charge=$payment->charge;
        $student=$charge->student;
        $previousPayments=$charge->payments()
            ->where(function($query)use($payment){
                $query->where('paid_at','<',$payment->paid_at)
                    ->orWhere(fn($sameTime)=>$sameTime->where('paid_at',$payment->paid_at)->where('id','<',$payment->id));
            });
        $paymentSequence=(clone $previousPayments)->count()+1;
        $primaryGuardian=$student->guardians->firstWhere('is_primary',true)??$student->guardians->first();
        $receiptFonts=[];
        foreach(['regular'=>'DejaVuSans.ttf','bold'=>'DejaVuSans-Bold.ttf'] as $style=>$filename){
            $fontPath=base_path('vendor/dompdf/dompdf/lib/fonts/'.$filename);
            if(is_file($fontPath))$receiptFonts[$style]='data:font/truetype;base64,'.base64_encode(file_get_contents($fontPath));
        }
        $logoPath=SystemSetting::valueOf('logo_path'); $logoData=null;
        if(!$logoPath||!str_starts_with($logoPath,'uploads/branding/')||!is_file(public_path($logoPath))){
            $logoPath='uploads/branding/logo-20260722101948.png';
        }
        if(str_starts_with($logoPath,'uploads/branding/')){
            $fullPath=public_path($logoPath);
            if(is_file($fullPath)){
                $logoBinary=file_get_contents($fullPath);
                if(function_exists('imagecreatefromstring')&&$logoImage=@imagecreatefromstring($logoBinary)){
                    $logoWidth=imagesx($logoImage); $logoHeight=imagesy($logoImage);
                    $logoCanvas=imagecreatetruecolor($logoWidth,$logoHeight);
                    $white=imagecolorallocate($logoCanvas,255,255,255);
                    imagefill($logoCanvas,0,0,$white);
                    imagealphablending($logoCanvas,true);
                    imagesavealpha($logoCanvas,false);
                    imagecopy($logoCanvas,$logoImage,0,0,0,0,$logoWidth,$logoHeight);
                    imagefilter($logoCanvas,IMG_FILTER_GRAYSCALE);
                    ob_start(); imagepng($logoCanvas); $logoBinary=ob_get_clean();
                    imagedestroy($logoCanvas); imagedestroy($logoImage);
                    $logoMime='image/png';
                }else $logoMime=mime_content_type($fullPath)?:'image/png';
                $logoData='data:'.$logoMime.';base64,'.base64_encode($logoBinary);
            }
        }
        $totalAmount=(float)$payment->amount+(float)$payment->book_amount;
        return compact('payment','charge','student','primaryGuardian','paymentSequence','receiptFonts','logoData','totalAmount')+[
            'softwareName'=>SystemSetting::valueOf('software_name','E-SKY CENTER'),
            'amountInWords'=>$this->amountInVietnameseWords((int)round($totalAmount)),
        ];
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
        $query = $this->filteredTuitionQuery($request, ['student', 'course', 'languageClass']);
        return ExcelExporter::download('thu-hoc-phi-'.date('Ymd').'.xlsx',['Mã khoản thu','Học viên','Khóa học','Mã lớp','Học phí','Giảm %','Phải thu','Đã thu','Chuyển sang','Còn lại','Trạng thái','Hạn đóng'],$query->get()->map(fn($item)=>[$item->code,$item->student->name,$item->course->name,$item->languageClass?->code,$item->original_amount,$item->discount_percentage,$item->payable_amount,$item->paid_amount,$item->credit_amount,$item->remainingAmount(),$item->status,$item->due_date?->format('d/m/Y')]));
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

    private function resolveManualDiscount(
        string $applyMode,
        ?LanguageDiscountPolicy $classDiscount,
        ?LanguageDiscountPolicy $personalDiscount
    ): ?LanguageDiscountPolicy {
        if ($applyMode === 'student') {
            if (! $personalDiscount) {
                throw ValidationException::withMessages([
                    'language_discount_policy_id' => 'Vui long chon mien giam rieng cua hoc vien de ap dung.',
                ]);
            }

            return $personalDiscount;
        }

        return LanguageDiscountResolver::highest($classDiscount, $personalDiscount);
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

    private function resolveMonthlyDate(Request $request): Carbon
    {
        try {
            return $request->filled('month')
                ? Carbon::createFromFormat('!Y-m', (string) $request->input('month'))->startOfMonth()
                : now()->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }

    private function monthlyPaymentsQuery(Request $request, Carbon $month)
    {
        $query = LanguageTuitionPayment::query()
            ->with(['collector', 'charge.student.guardians', 'charge.course', 'charge.languageClass'])
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

        return $query;
    }

    private function filteredTuitionQuery(Request $request, array $with): Builder
    {
        $query = LanguageTuitionCharge::query()
            ->with($with)
            ->where(fn ($builder) => $builder
                ->whereNull('language_class_id')
                ->orWhereHas('languageClass', fn ($class) => $class->whereNull('language_classes.deleted_at')))
            ->latest();

        if ($request->filled('q')) {
            $search = $request->string('q');
            $query->where(fn ($builder) => $builder->where('code', 'like', "%{$search}%")
                ->orWhereHas('student', fn ($student) => $student->where('name', 'like', "%{$search}%"))
                ->orWhereHas('languageClass', fn ($class) => $class->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")));
        }

        if ($request->filled('class')) {
            $query->where('language_class_id', $request->integer('class'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $this->applyPeriod($query, $request);

        return $query;
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
        $activePayments = $charge->payments()->where('receipt_status', '!=', 'cancelled');
        $confirmedPayments = $charge->payments()->where('receipt_status', 'confirmed');
        $paidAmount = (float) (clone $confirmedPayments)->sum('amount');
        $hasPendingReceipt = (clone $activePayments)->where('receipt_status', 'pending')->exists();
        $settledAmount = $paidAmount + (float) $charge->credit_amount;
        $status = $hasPendingReceipt
            ? 'pending_receipt'
            : ($settledAmount >= (float) $charge->payable_amount ? 'paid' : ($settledAmount > 0 ? 'partial' : 'unpaid'));

        $charge->update(['paid_amount' => $paidAmount, 'status' => $status]);

        if ($payment->receipt_status !== 'confirmed') {
            LanguageMonthlyTargetRecord::where('language_tuition_payment_id', $payment->id)->delete();
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
