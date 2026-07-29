<?php

namespace App\Http\Controllers;

use App\Models\{LanguageCollaborator, LanguageCourse, LanguageLead, LanguageProgram, LanguageStudent, User};
use App\Support\{ActivityLogger, CenterCode, ExcelExporter};
use Illuminate\Http\{RedirectResponse, Request};
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
        $this->applyReceivedFilter($query, $request);
        return view('language.leads.index', ['items' => $query->paginate(\App\Support\Pagination::perPage())->withQueryString()]);
    }

    public function create(): View { return $this->form(new LanguageLead); }
    public function edit(LanguageLead $languageLead): View { return $this->form($languageLead); }
    public function show(LanguageLead $languageLead): View { return view('language.leads.show', ['item'=>$languageLead->load(['program','course','collaborator','consultant','convertedStudent'])]); }

    public function consulting(Request $request): View
    {
        $canViewAll=$request->user()->isAdmin() || $request->user()->allowed('language_dashboard_all');
        $query = LanguageLead::with(['program','course','collaborator','consultant','targetSubmissions'])
            ->when(! $canViewAll,fn($builder)=>$builder->where('consultant_user_id',$request->user()->id))
            ->whereNotIn('status', ['registered','not_interested'])
            ->orderByDesc('created_at')->orderByDesc('id');
        if ($request->filled('status')) $query->where('status', $request->status);
        $this->applyReceivedFilter($query, $request);
        return view('language.leads.consulting', ['items'=>$query->paginate(\App\Support\Pagination::perPage())->withQueryString(),'canViewAll'=>$canViewAll]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->data($request);
        $data['code'] = CenterCode::next('language_leads', 'KH');
        $data['received_at'] ??= now()->toDateString();
        if (filled($data['consultation'] ?? null)) $data['last_consulted_at'] = now();
        $lead = LanguageLead::create($data);
        ActivityLogger::log('language_leads', 'create', 'Tạo khách hàng '.$lead->name, $lead);
        return redirect()->route('language-leads.index')->with('success', 'Đã thêm học viên tiềm năng.');
    }

    public function update(Request $request, LanguageLead $languageLead): RedirectResponse
    {
        $data = $this->data($request, $languageLead);
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
        if ($languageLead->converted_student_id) return redirect()->route('language-tuition.create', ['lead'=>$languageLead->id,'student'=>$languageLead->converted_student_id]);
        if ($languageLead->status !== 'registered') {
            return back()->withErrors(['status'=>'Chỉ có thể chuyển thành học viên khi trạng thái tư vấn là “Đã đăng ký”.']);
        }
        $student = LanguageStudent::create(['code'=>CenterCode::next('language_students','HV'),'name'=>$languageLead->name,'date_of_birth'=>$languageLead->date_of_birth,'phone'=>$languageLead->phone,'email'=>$languageLead->email,'registered_at'=>now()->toDateString(),'source'=>$languageLead->source,'status'=>'new','note'=>'Chuyển từ khách hàng '.$languageLead->code]);
        $student->update(['language_course_id'=>$languageLead->language_course_id]);
        $languageLead->update(['converted_student_id'=>$student->id,'status'=>'registered']);
        return redirect()->route('language-tuition.create', ['lead'=>$languageLead->id,'student'=>$student->id,'course'=>$languageLead->language_course_id])->with('success', 'Đã tạo học viên. Vui lòng lập khoản thu học phí.');
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

    private function data(Request $request, ?LanguageLead $lead = null): array
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'], 'date_of_birth' => ['nullable','date'],
            'phone' => ['required','string','max:30'], 'email' => ['nullable','email','max:255'],
            'zalo' => ['nullable','max:100'], 'source' => ['nullable','max:255'], 'received_at'=>['required','date'],
            'language_program_id' => ['nullable','exists:language_programs,id'],
            'language_course_id' => ['required','exists:language_courses,id'],
            'language_collaborator_id' => ['required','exists:language_collaborators,id'],
            'consultant_user_id' => ['nullable','exists:users,id'], 'appointment_at' => ['nullable','date'],
            'status' => ['required', Rule::in(['new','contacted','consulting','placement_test','waiting','registered','not_interested','follow_up'])],
            'consultation' => ['nullable'], 'note' => ['nullable'],
        ], [
            'name.required'=>'Vui lòng nhập họ tên khách hàng.', 'phone.required'=>'Vui lòng nhập số điện thoại.',
            'language_course_id.required'=>'Vui lòng chọn khóa học quan tâm.',
            'language_collaborator_id.required'=>'Vui lòng chọn cộng tác viên giới thiệu.',
            'status.required'=>'Vui lòng chọn trạng thái.',
        ]);
        $normalizedPhone=preg_replace('/\D+/','',$data['phone']);
        $duplicate=LanguageLead::where('language_course_id',$data['language_course_id'])
            ->where('status','!=','not_interested')
            ->when($lead,fn($query)=>$query->whereKeyNot($lead->id))
            ->latest()->get()->first(fn($item)=>preg_replace('/\D+/','',$item->phone)===$normalizedPhone);
        if ($duplicate) {
            $status=[
                'new'=>'Mới tiếp nhận','contacted'=>'Đã liên hệ','consulting'=>'Đang tư vấn',
                'placement_test'=>'Hẹn kiểm tra','waiting'=>'Chờ phản hồi','registered'=>'Đã đăng ký',
                'follow_up'=>'Chăm sóc lại',
            ][$duplicate->status]??$duplicate->status;
            $studentNote=$duplicate->converted_student_id?'Hồ sơ này đã chuyển thành học viên chính thức.':'Hồ sơ này hiện vẫn là học viên tiềm năng, chưa chuyển thành học viên chính thức.';
            throw \Illuminate\Validation\ValidationException::withMessages([
                'phone'=>"Số điện thoại này đang có ở khách hàng {$duplicate->code} – {$duplicate->name}, trạng thái: {$status}. {$studentNote} Vui lòng mở và cập nhật hồ sơ khách hàng này.",
            ]);
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
