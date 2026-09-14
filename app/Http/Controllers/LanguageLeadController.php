<?php

namespace App\Http\Controllers;

use App\Models\{LanguageClass, LanguageCollaborator, LanguageCourse, LanguageLead, LanguageProgram, LanguageStudent, User};
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
    public function show(LanguageLead $languageLead): View
    {
        $item = $languageLead->load(['program','course','collaborator','consultant','convertedStudent']);

        return view('language.leads.show', [
            'item' => $item,
            'existingStudent' => $item->converted_student_id ? null : $this->findExistingStudentByPhone($item->phone),
        ]);
    }

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
        $result = DB::transaction(function () use ($data): array {
            // A lead is one registration for one course. Lock every existing
            // registration of that course so two CTVs cannot claim the same
            // phone/course at the same time.
            $duplicate = $this->findExistingRegistration(
                $data['phone'],
                (int) $data['language_course_id'],
                $data['language_class_id'] ? (int) $data['language_class_id'] : null,
                true
            );
            if ($duplicate) {
                return ['duplicate' => $duplicate];
            }

            $createData = $data;
            $createData['code'] = CenterCode::next('language_leads', 'KH');
            $createData['received_at'] ??= now()->toDateString();
            if (filled($createData['consultation'] ?? null)) {
                $createData['last_consulted_at'] = now();
            }

            return ['lead' => LanguageLead::create($createData)];
        });

        if (isset($result['duplicate'])) {
            /** @var LanguageLead $duplicate */
            $duplicate = $result['duplicate'];
            $owner = $duplicate->collaborator?->name ?: 'chưa xác định';
            $course = $duplicate->course?->name ?: 'khóa/lớp đã chọn';

            return redirect()->route('language-leads.show', $duplicate)->with(
                'warning',
                "SĐT này đã được ghi nhận trước cho {$owner}, khóa/lớp {$course} (hồ sơ {$duplicate->code}). Hệ thống không tạo trùng và không đổi người được ghi nhận."
            );
        }

        /** @var LanguageLead $lead */
        $lead = $result['lead'];
        ActivityLogger::log('language_leads', 'create', 'Tạo khách hàng '.$lead->name, $lead);
        $knownStudent = $this->findExistingStudentByPhone($lead->phone);

        return redirect()->route('language-leads.show', $lead)->with(
            $knownStudent ? 'warning' : 'success',
            $knownStudent
                ? "Đã ghi nhận lượt đăng ký khóa/lớp mới. SĐT này đã thuộc học viên {$knownStudent->code} – {$knownStudent->name}; sau khi tư vấn xong, chọn “Chuyển thành học viên” để liên kết lượt đăng ký này với hồ sơ có sẵn."
                : 'Đã thêm học viên tiềm năng.'
        );
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
        $student = $this->findExistingStudentByPhone($languageLead->phone);
        $wasExistingStudent = $student !== null;
        if (! $student) {
            $student = LanguageStudent::create(['code'=>CenterCode::next('language_students','HV'),'name'=>$languageLead->name,'date_of_birth'=>$languageLead->date_of_birth,'phone'=>$languageLead->phone,'email'=>$languageLead->email,'registered_at'=>now()->toDateString(),'source'=>$languageLead->source,'status'=>'new','note'=>'Chuyển từ khách hàng '.$languageLead->name]);
            $student->update(['language_course_id'=>$languageLead->language_course_id]);
        }
        $languageLead->update(['converted_student_id'=>$student->id,'status'=>'registered']);
        return redirect()->route('language-students.show',$student)->with(
            'success',
            $wasExistingStudent
                ? 'Đã liên kết lượt đăng ký này với hồ sơ học viên có sẵn.'
                : 'Đã chuyển thành học viên và mở hồ sơ học viên.'
        );
    }

    /**
     * A registration is unique by normalized phone and course. The oldest one
     * owns the CTV attribution; later submissions must never overwrite it.
     */
    private function findExistingRegistration(?string $phone, int $courseId, ?int $classId = null, bool $lock = false): ?LanguageLead
    {
        $normalizedPhone = TextNormalizer::phone($phone);
        if ($normalizedPhone === null) {
            return null;
        }

        $query = LanguageLead::query()
            ->with(['collaborator', 'course'])
            ->where('language_course_id', $courseId)
            ->orderBy('created_at')
            ->orderBy('id');
        if ($lock) {
            $query->lockForUpdate();
        }

        return $query
            ->get()
            ->first(fn (LanguageLead $item) => TextNormalizer::phone($item->phone) === $normalizedPhone
                && ($classId === null || $item->language_class_id === null || (int) $item->language_class_id === $classId));
    }

    /**
     * Match a student's own number and every guardian number. This lets a later
     * registration link to the same learner without creating a second student.
     */
    private function findExistingStudentByPhone(?string $phone): ?LanguageStudent
    {
        $normalizedPhone = TextNormalizer::phone($phone);
        if ($normalizedPhone === null) {
            return null;
        }

        return LanguageStudent::query()
            ->with('guardians')
            ->get()
            ->first(function (LanguageStudent $student) use ($normalizedPhone): bool {
                return TextNormalizer::phone($student->phone) === $normalizedPhone
                    || $student->guardians->contains(
                        fn ($guardian) => TextNormalizer::phone($guardian->phone) === $normalizedPhone
                    );
            });
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
        return view('language.leads.form', compact('item','collaborators') + ['programs'=>LanguageProgram::where(fn($query)=>$query->where('active',1)->orWhere('id', $item->language_program_id))->orderBy('name')->get(),'courses'=>LanguageCourse::where(fn($query)=>$query->where('active',1)->orWhere('id', $item->language_course_id))->orderBy('name')->get(),'classes'=>LanguageClass::where(fn($query)=>$query->whereIn('status',['recruiting','upcoming','active'])->orWhere('id',$item->language_class_id))->with('course')->orderBy('name')->get(),'users'=>User::where(fn($query)=>$query->where('active',1)->orWhere('id', $item->consultant_user_id))->orderBy('name')->get()]);
    }

    private function data(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'], 'date_of_birth' => ['nullable','date'],
            'phone' => ['required','string','max:30'], 'email' => ['nullable','email','max:255'],
            'zalo' => ['nullable','max:100'], 'source' => ['nullable','max:255'], 'received_at'=>['required','date'],
            'language_program_id' => ['nullable','exists:language_programs,id'],
            'language_course_id' => ['required','exists:language_courses,id'],
            'language_class_id' => ['nullable', Rule::exists('language_classes', 'id')->where(fn ($query) => $query->whereNull('deleted_at'))],
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
        $data['language_class_id'] ??= null;
        if (! empty($data['language_class_id'])) {
            $class = LanguageClass::findOrFail($data['language_class_id']);
            if ((int) $class->language_course_id !== (int) $data['language_course_id']) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'language_class_id' => 'Lớp được chọn không thuộc khóa học đã chọn.',
                ]);
            }
        }
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
