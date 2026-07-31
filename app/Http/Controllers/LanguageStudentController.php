<?php

namespace App\Http\Controllers;

use App\Models\{LanguageClass, LanguageCourse, LanguageDiscountPolicy, LanguageEnrollment, LanguageGuardian, LanguageStudent};
use App\Support\ActivityLogger;
use App\Support\LanguageStudentSpreadsheet;
use App\Support\LanguageEnrollmentManager;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LanguageStudentController extends Controller
{
    public function __construct(private readonly LanguageEnrollmentManager $enrollmentManager) {}

    public function index(Request $request): View
    {
        $query = LanguageStudent::with([
            'guardians' => fn ($guardians) => $guardians->orderByDesc('is_primary')->orderBy('id'),
        ])->latest();
        if ($request->filled('q')) {
            $search = (string) $request->string('q')->trim();
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('guardians', fn ($guardians) => $guardians
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"));
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        return view('language.students.index', [
            'items' => $query->paginate(\App\Support\Pagination::perPage())->withQueryString(),
        ]);
    }

    public function create(): View { return $this->form(new LanguageStudent); }

    public function import(Request $request, LanguageStudentSpreadsheet $spreadsheet): RedirectResponse
    {
        abort_unless($request->user()?->isRegistrar(), 403, 'Chỉ giáo vụ hoặc quản trị viên được nhập danh sách và xếp lớp học viên.');
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ], [
            'file.required' => 'Vui lòng chọn file Excel.',
            'file.mimes' => 'File tải lên phải có định dạng .xlsx hoặc .xls.',
            'file.max' => 'File Excel không được lớn hơn 10 MB.',
        ]);

        if (! class_exists(\ZipArchive::class)) {
            return redirect()->route('language-students.index')->withErrors([
                'file' => 'Máy chủ chưa bật PHP ZipArchive nên chưa thể đọc file Excel. Vui lòng liên hệ quản trị viên để bật extension=zip.',
            ]);
        }

        try {
            $result = $spreadsheet->import($request->file('file'));
        } catch (\Throwable $exception) {
            return back()->withErrors(['file' => 'Không thể nhập file: '.$exception->getMessage()]);
        }

        ActivityLogger::log(
            'language_students',
            'import',
            "Nhập {$result['success']}/{$result['total']} học viên từ Excel"
        );

        $redirect = redirect()->route('language-students.index')->with(
            $result['failed'] > 0 ? 'warning' : 'success',
            "Đã nhập {$result['success']}/{$result['total']} học viên. Có {$result['failed']} dòng lỗi."
        );
        if ($result['errors']) {
            $redirect->with('student_import_errors', $result['errors']);
        }

        return $redirect;
    }

    public function template(LanguageStudentSpreadsheet $spreadsheet): StreamedResponse|RedirectResponse
    {
        if (! class_exists(\ZipArchive::class)) {
            return redirect()->route('language-students.index')->withErrors([
                'file' => 'Máy chủ chưa bật PHP ZipArchive nên chưa thể tạo file Excel mẫu. Vui lòng liên hệ quản trị viên để bật extension=zip.',
            ]);
        }

        return $spreadsheet->template();
    }

    public function show(LanguageStudent $languageStudent): View
    {
        $languageStudent->load([
            'guardians', 'course', 'discountPolicy',
            'enrollments'=>fn($query)=>$query->with([
                'languageClass.program', 'languageClass.level', 'languageClass.teacher',
                'monthlyProgress'=>fn($progress)=>$progress->with('teacher')->orderByDesc('month'),
                'scores'=>fn($scores)=>$scores->with('teacher')->orderByDesc('test_date'),
            ])->orderByDesc('enrolled_at'),
            'tuitionCharges'=>fn($query)=>$query->with(['course','languageClass','payments.collector'])->orderByDesc('created_at'),
            'classTransfers'=>fn($query)=>$query->with(['fromClass','toClass','creator'])->orderByDesc('effective_date'),
        ]);
        return view('language.students.show',['item'=>$languageStudent]);
    }

    public function store(Request $request): RedirectResponse
    {
        $student = DB::transaction(function () use ($request) {
            $student = LanguageStudent::create($this->data($request));
            $this->syncGuardians($request, $student);
            $this->syncCurrentClass($request, $student);
            return $student;
        });
        ActivityLogger::log('language_students','create','Tạo học viên '.$student->name,$student);
        return redirect()->route('language-students.index')->with('success','Đã thêm học viên.');
    }

    public function edit(LanguageStudent $languageStudent): View { return $this->form($languageStudent->load(['guardians','enrollments.languageClass.program','enrollments.languageClass.level','enrollments.monthlyProgress','enrollments.scores'])); }

    public function update(Request $request, LanguageStudent $languageStudent): RedirectResponse
    {
        DB::transaction(function () use ($request, $languageStudent) {
            $languageStudent->update($this->data($request, $languageStudent));
            $this->syncGuardians($request, $languageStudent);
            $this->syncCurrentClass($request, $languageStudent);
        });
        return redirect()->route('language-students.index')->with('success','Đã cập nhật học viên.');
    }

    public function destroy(LanguageStudent $languageStudent): RedirectResponse { $languageStudent->delete(); return back()->with('success','Đã xóa học viên.'); }

    private function form(LanguageStudent $item): View
    {
        return view('language.students.form', compact('item') + [
            'courses'=>LanguageCourse::where(fn($query)=>$query->where('active',1)->orWhere('id', $item->language_course_id))->orderBy('name')->get(),
            'discounts'=>LanguageDiscountPolicy::where(fn($query)=>$query->where('active',1)->orWhere('id', $item->language_discount_policy_id))->orderBy('name')->get(),
            'classes'=>LanguageClass::whereIn('status',['recruiting','upcoming','active'])->with(['program','level'])->orderBy('name')->get(),
        ]);
    }

    private function data(Request $request, ?LanguageStudent $student = null): array
    {
        return $request->validate([
            'code'=>['nullable','max:30',Rule::unique('language_students')->ignore($student)], 'name'=>'required|max:255',
            'gender'=>'nullable|in:male,female,other', 'date_of_birth'=>'nullable|date', 'school'=>'nullable|max:255',
            'school_class'=>'nullable|max:100', 'phone'=>'nullable|max:30', 'email'=>'nullable|email|max:255', 'address'=>'nullable|max:255',
            'registered_at'=>'nullable|date',
            'official_enrollment_date'=>[
                'nullable', 'date',
                Rule::when($request->filled('registered_at'), ['after_or_equal:registered_at']),
            ],
            'source'=>'nullable|max:255', 'language_course_id'=>'nullable|exists:language_courses,id',
            'language_discount_policy_id'=>'nullable|exists:language_discount_policies,id',
            'status'=>['required',Rule::in(['new','placement_test','waiting_class','studying','paused','reserved','completed','dropped'])], 'note'=>'nullable',
        ]);
    }

    private function syncGuardians(Request $request, LanguageStudent $student): void
    {
        $rows = $request->validate([
            'guardians'=>'nullable|array|max:3', 'guardians.*.name'=>'nullable|max:255',
            'guardians.*.relationship'=>'nullable|in:father,mother,guardian', 'guardians.*.phone'=>'nullable|max:30',
            'guardians.*.email'=>'nullable|email|max:255',
        ])['guardians'] ?? [];
        $student->guardians()->delete();
        foreach ($rows as $index => $row) {
            if (blank($row['name'] ?? null)) continue;
            $student->guardians()->create($row + ['phone'=>$row['phone'] ?? '', 'is_primary'=>$index === 0]);
        }
    }

    private function syncCurrentClass(Request $request, LanguageStudent $student): void
    {
        $data = $request->validate(['language_class_id'=>'nullable|exists:language_classes,id']);
        if (empty($data['language_class_id'])) return;
        $user = $request->user();
        abort_unless($user?->isRegistrar(), 403, 'Chỉ tài khoản được đánh dấu Giáo vụ hoặc quản trị viên mới được xếp học viên vào lớp.');
        $class = LanguageClass::findOrFail($data['language_class_id']);
        $this->enrollmentManager->enroll(
            $class,
            $student,
            $student->official_enrollment_date ?? $student->registered_at ?? now(),
            $request->user()?->id,
            in_array($student->status, ['paused', 'reserved', 'completed', 'dropped'], true)
                ? $student->status
                : 'studying'
        );
    }
}
