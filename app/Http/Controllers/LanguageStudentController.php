<?php

namespace App\Http\Controllers;

use App\Models\{LanguageClass, LanguageCourse, LanguageDiscountPolicy, LanguageEnrollment, LanguageGuardian, LanguageStudent};
use App\Support\ActivityLogger;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LanguageStudentController extends Controller
{
    public function index(Request $request): View
    {
        $query = LanguageStudent::with(['guardians','course','discountPolicy','enrollments.languageClass.program','enrollments.languageClass.level'])->withSum('tuitionCharges','payable_amount')->withSum('tuitionCharges','paid_amount')->latest();
        if ($request->filled('q')) {
            $search = $request->string('q');
            $query->where(fn ($builder) => $builder->where('name','like',"%{$search}%")->orWhere('code','like',"%{$search}%")->orWhere('phone','like',"%{$search}%"));
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('course')) $query->where('language_course_id', $request->integer('course'));
        return view('language.students.index', ['items'=>$query->paginate(\App\Support\Pagination::perPage())->withQueryString(),'courses'=>LanguageCourse::where('active',1)->orderBy('name')->get()]);
    }

    public function create(): View { return $this->form(new LanguageStudent); }

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
            'courses'=>LanguageCourse::where('active',1)->orderBy('name')->get(),
            'discounts'=>LanguageDiscountPolicy::where('active',1)->orderBy('name')->get(),
            'classes'=>LanguageClass::whereIn('status',['recruiting','upcoming','active'])->with(['program','level'])->orderBy('name')->get(),
        ]);
    }

    private function data(Request $request, ?LanguageStudent $student = null): array
    {
        return $request->validate([
            'code'=>['nullable','max:30',Rule::unique('language_students')->ignore($student)], 'name'=>'required|max:255',
            'gender'=>'nullable|in:male,female,other', 'date_of_birth'=>'nullable|date', 'school'=>'nullable|max:255',
            'school_class'=>'nullable|max:100', 'phone'=>'nullable|max:30', 'email'=>'nullable|email|max:255', 'address'=>'nullable|max:255',
            'registered_at'=>'required|date', 'official_enrollment_date'=>'nullable|date|after_or_equal:registered_at',
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
        $class = LanguageClass::findOrFail($data['language_class_id']);
        LanguageEnrollment::updateOrCreate(
            ['language_class_id'=>$class->id,'language_student_id'=>$student->id],
            ['enrolled_at'=>$student->official_enrollment_date ?? now()->toDateString(),'tuition'=>$class->default_tuition,'discount'=>0,'status'=>'studying']
        );
    }
}
