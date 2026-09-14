<?php

namespace App\Http\Controllers;

use App\Models\{LanguageCollaborator, LanguageCourse, LanguageLead, LanguageProgram, LanguageStudent, User};
use App\Support\{ActivityLogger, CenterCode, ExcelExporter, TextNormalizer};
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LanguageLeadController extends Controller
{
    public function index(Request $request): View
    {
        $query = LanguageLead::with(['program','course','collaborator','consultant'])->latest();
        if ($request->filled('q')) {
            $search = $request->string('q');
            $query->where(fn ($builder) => $builder->where('name','like',"%{$search}%")->orWhere('phone','like',"%{$search}%")->orWhere('code','like',"%{$search}%"));
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('course')) $query->where('language_course_id', $request->integer('course'));
        $this->applyReceivedFilter($query, $request);
        return view('language.leads.index', [
            'items' => $query->paginate(\App\Support\Pagination::perPage())->withQueryString(),
            'courses' => LanguageCourse::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function create(): View { return $this->form(new LanguageLead); }
    public function edit(LanguageLead $languageLead): View { return $this->form($languageLead); }
    public function show(LanguageLead $languageLead): View { return view('language.leads.show', ['item'=>$languageLead->load(['program','course','collaborator','consultant','convertedStudent'])]); }

    public function consulting(Request $request): View
    {
        $canViewAll=$request->user()->isAdmin() || $request->user()->allowed('language_dashboard_all');
        $pendingQuery=LanguageLead::query()
            ->when(! $canViewAll,fn($builder)=>$builder->where('consultant_user_id',$request->user()->id))
            ->whereNotIn('status', ['registered','not_interested']);
        $pendingCount=(clone $pendingQuery)->count();
        $query = (clone $pendingQuery)
            ->with(['program','course','collaborator','consultant','targetSubmissions'])
            ->orderByDesc('created_at')->orderByDesc('id');
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('course')) $query->where('language_course_id', $request->integer('course'));
        $this->applyReceivedFilter($query, $request);
        return view('language.leads.consulting', [
            'items'=>$query->paginate(\App\Support\Pagination::perPage())->withQueryString(),
            'canViewAll'=>$canViewAll,
            'pendingCount'=>$pendingCount,
            'courses'=>LanguageCourse::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->data($request);
        $existingContact = $this->findExistingContact($data['phone']);

        if ($existingContact !== null) {
            return $this->overwriteCourseForExistingContact($existingContact, $data);
        }

        $data['code'] = CenterCode::next('language_leads', 'KH');
        $data['received_at'] ??= now()->toDateString();
        if (filled($data['consultation'] ?? null)) $data['last_consulted_at'] = now();
        $lead = LanguageLead::create($data);
        ActivityLogger::log('language_leads', 'create', 'Tạo khách hàng '.$lead->name, $lead);
        return redirect()->route('language-leads.index')->with('success', 'Đã thêm học viên tiềm năng.');
    }

    public function update(Request $request, LanguageLead $languageLead): RedirectResponse
    {
        $data = $this->data($request);
        if (filled($data['consultation'] ?? null) && ($data['consultation'] !== $languageLead->consultation || $data['status'] !== $languageLead->status)) {
            $data['last_consulted_at'] = now();
        }
        $languageLead->update($data);
        return redirect()->route('language-leads.index')->with('success', 'Đã cập nhật khách hàng.');
    }

    public function destroy(LanguageLead $languageLead): RedirectResponse
    {
        $languageLead->delete();
        return back()->with('success', 'Đã xóa khách hàng.');
    }

    public function convert(LanguageLead $languageLead): RedirectResponse
    {
        if ($languageLead->converted_student_id) return redirect()->route('language-students.show',$languageLead->converted_student_id);
        if ($languageLead->status !== 'registered') {
            return back()->withErrors(['status'=>'Chỉ có thể chuyển thành học viên khi trạng thái tư vấn là “Đã đăng ký”.']);
        }
        $student = LanguageStudent::create(['code'=>CenterCode::next('language_students','HV'),'name'=>$languageLead->name,'date_of_birth'=>$languageLead->date_of_birth,'phone'=>$languageLead->phone,'email'=>$languageLead->email,'registered_at'=>now()->toDateString(),'source'=>$languageLead->source,'status'=>'new','note'=>'Chuyển từ khách hàng '.$languageLead->name]);
        $student->update(['language_course_id'=>$languageLead->language_course_id]);
        $languageLead->update(['converted_student_id'=>$student->id,'status'=>'registered']);
        return redirect()->route('language-students.show',$student)->with('success', 'Đã chuyển thành học viên và mở hồ sơ học viên.');
    }

    /**
     * Find an active learner or prospective learner by a normalized phone number.
     * A guardian's phone is deliberately treated as the learner's contact too.
     *
     * @return array{type: 'student'|'guardian'|'lead', model: LanguageStudent|LanguageLead}|null
     */
    private function findExistingContact(?string $phone): ?array
    {
        $normalizedPhone = TextNormalizer::phone($phone);
        if ($normalizedPhone === null) {
            return null;
        }

        $students = LanguageStudent::query()->with('guardians')->get();
        foreach ($students as $student) {
            if (TextNormalizer::phone($student->phone) === $normalizedPhone) {
                return ['type' => 'student', 'model' => $student];
            }

            if ($student->guardians->contains(
                fn ($guardian) => TextNormalizer::phone($guardian->phone) === $normalizedPhone
            )) {
                return ['type' => 'guardian', 'model' => $student];
            }
        }

        $lead = LanguageLead::query()
            ->orderByDesc('id')
            ->get()
            ->first(fn (LanguageLead $item) => TextNormalizer::phone($item->phone) === $normalizedPhone);

        return $lead ? ['type' => 'lead', 'model' => $lead] : null;
    }

    /**
     * Do not create a second contact record. The newly selected course replaces
     * the course on the matched profile, while the rest of that profile is kept.
     *
     * @param array{type: 'student'|'guardian'|'lead', model: LanguageStudent|LanguageLead} $existingContact
     */
    private function overwriteCourseForExistingContact(array $existingContact, array $data): RedirectResponse
    {
        $course = LanguageCourse::findOrFail($data['language_course_id']);
        $model = $existingContact['model'];

        DB::transaction(function () use ($existingContact, $model, $data): void {
            if ($existingContact['type'] === 'lead') {
                /** @var LanguageLead $model */
                $model->update([
                    'language_course_id' => $data['language_course_id'],
                    'language_program_id' => $data['language_program_id'],
                ]);
                return;
            }

            /** @var LanguageStudent $model */
            $model->update(['language_course_id' => $data['language_course_id']]);
        });

        $contactType = match ($existingContact['type']) {
            'lead' => 'học viên tiềm năng',
            'guardian' => 'người giám hộ của học viên',
            default => 'học viên',
        };
        ActivityLogger::log(
            'language_leads',
            'overwrite_course_for_existing_contact',
            "Không tạo mới học viên tiềm năng do trùng SĐT {$contactType}; đã cập nhật khóa học {$course->name} cho {$model->name}",
            $model
        );

        $message = "Số điện thoại đã tồn tại ở {$contactType} {$model->code} – {$model->name}. Không tạo hồ sơ mới; đã cập nhật khóa học thành “{$course->name}”.";

        return $existingContact['type'] === 'lead'
            ? redirect()->route('language-leads.show', $model)->with('warning', $message)
            : redirect()->route('language-students.show', $model)->with('warning', $message);
    }

    public function export()
    {
        $rows = LanguageLead::with(['course','collaborator','consultant'])->get()->map(fn ($item) => [$item->code,$item->name,$item->phone,$item->email,$item->course?->name,$item->collaborator?->name,$item->consultant?->name,$item->status,$item->created_at?->format('d/m/Y')]);
        return ExcelExporter::download('khach-hang-tiem-nang-'.date('Ymd').'.xlsx', ['Mã','Họ tên','Điện thoại','Email','Khóa học','CTV','Tư vấn viên','Trạng thái','Ngày tiếp nhận'], $rows);
    }

    private function form(LanguageLead $item): View
    {
        $collaborators=LanguageCollaborator::where(function ($query) use ($item) {
            $query->where('active',1);
            if ($item->language_collaborator_id) $query->orWhere('id',$item->language_collaborator_id);
        })->orderBy('name')->get();
        return view('language.leads.form', compact('item','collaborators') + ['programs'=>LanguageProgram::where(fn($query)=>$query->where('active',1)->orWhere('id', $item->language_program_id))->orderBy('name')->get(),'courses'=>LanguageCourse::where(fn($query)=>$query->where('active',1)->orWhere('id', $item->language_course_id))->orderBy('name')->get(),'users'=>User::where(fn($query)=>$query->where('active',1)->orWhere('id', $item->consultant_user_id))->orderBy('name')->get()]);
    }

    private function data(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'], 'date_of_birth' => ['nullable','date'],
            'phone' => ['required','string','max:30'], 'email' => ['nullable','email','max:255'],
            'zalo' => ['nullable','max:100'], 'source' => ['nullable','max:255'], 'received_at'=>['required','date'],
            'language_program_id' => ['nullable','exists:language_programs,id'],
            'language_course_id' => ['required','exists:language_courses,id'],
            'language_collaborator_id' => ['required','exists:language_collaborators,id'],
            'consultant_user_id' => ['nullable','exists:users,id'], 'appointment_at' => ['nullable','date'],
            'status' => ['required', Rule::in(['new','contacted','consulting','placement_test','waiting','waiting_class','registered','not_interested','follow_up'])],
            'consultation' => ['nullable'], 'note' => ['nullable'],
        ], [
            'name.required'=>'Vui lòng nhập họ tên khách hàng.', 'phone.required'=>'Vui lòng nhập số điện thoại.',
            'language_course_id.required'=>'Vui lòng chọn khóa học quan tâm.',
            'language_collaborator_id.required'=>'Vui lòng chọn cộng tác viên giới thiệu.',
            'status.required'=>'Vui lòng chọn trạng thái.',
        ]);
        return $data;
    }

    private function applyReceivedFilter($query, Request $request): void
    {
        if ($request->filled('date')) {
            $query->whereDate('received_at', $request->input('date'));
            return;
        }
        if ($request->filled('year')) $query->whereYear('received_at', $request->integer('year'));
        if ($request->filled('month')) $query->whereMonth('received_at', $request->integer('month'));
    }
}
