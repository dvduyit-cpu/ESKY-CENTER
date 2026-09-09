<?php

namespace App\Http\Controllers;

use App\Models\{LanguageCollaborator,LanguageCourse,LanguageLead,User};
use App\Support\{CenterCode,ExcelExporter};
use Illuminate\Http\{JsonResponse,RedirectResponse,Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LanguageCollaboratorController extends Controller
{
    public function index(Request $request): View
    {
        $filters=$request->validate([
            'q'=>['nullable','string','max:255'],
            'date'=>['nullable','date'],
            'month'=>['nullable','integer','between:1,12'],
            'year'=>['nullable','integer','between:2020,2100'],
        ]);
        $applyLeadPeriod=function ($query) use ($filters): void {
            $hasMonthOrYear=! empty($filters['month'])||! empty($filters['year']);
            if (! $hasMonthOrYear && ! empty($filters['date'])) {
                $query->whereDate('received_at',$filters['date']);
                return;
            }
            if (! empty($filters['year'])) $query->whereYear('received_at',$filters['year']);
            if (! empty($filters['month'])) $query->whereMonth('received_at',$filters['month']);
        };
        $query=LanguageCollaborator::query()
            ->withCount(['leads as referred_students_count'=>$applyLeadPeriod])
            ->latest();
        if ($request->filled('q')) {
            $search=trim((string)$request->input('q'));
            $query->where(fn($builder)=>$builder->where('name','like',"%{$search}%")->orWhere('phone','like',"%{$search}%"));
        }
        $years=LanguageLead::query()->whereNotNull('language_collaborator_id')->whereNotNull('received_at')
            ->selectRaw('YEAR(received_at) as year')->distinct()->pluck('year')
            ->map(fn($year)=>(int)$year)->push((int)now()->year)->unique()->sortDesc()->values();
        return view('language.collaborators.index',[
            'items'=>$query->paginate(\App\Support\Pagination::perPage())->withQueryString(),
            'years'=>$years,
            'hasPeriodFilter'=>filled($filters['date']??null)||filled($filters['month']??null)||filled($filters['year']??null),
        ]);
    }

    public function create(): View { return $this->form(new LanguageCollaborator); }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        [$data,$user]=$this->data($request);
        $data['code']=CenterCode::next('language_collaborators','CTV');
        $collaborator=DB::transaction(function () use ($data,$user) {
            $collaborator=LanguageCollaborator::create($data);
            $this->syncUser($collaborator,$user);
            return $collaborator;
        });
        if ($request->boolean('quick_create')&&$request->expectsJson()) return response()->json(['id'=>$collaborator->id,'label'=>$collaborator->code.' · '.$collaborator->name.' · '.($collaborator->phone?:'Chưa có SĐT'),'message'=>'Đã thêm cộng tác viên '.$collaborator->name.'.']);
        if ($request->boolean('quick_create')) return back()->with('success','Đã thêm cộng tác viên '.$collaborator->name.'.')->with('selected_collaborator',$collaborator->id);
        return redirect()->route('language-collaborators.index')->with('success','Đã thêm cộng tác viên.');
    }

    public function edit(LanguageCollaborator $languageCollaborator): View { return $this->form($languageCollaborator); }

    public function update(Request $request,LanguageCollaborator $languageCollaborator): RedirectResponse
    {
        [$data,$user]=$this->data($request,$languageCollaborator);
        DB::transaction(function () use ($languageCollaborator,$data,$user) {
            $languageCollaborator->update($data);
            $this->syncUser($languageCollaborator,$user);
        });
        return redirect()->route('language-collaborators.index')->with('success','Đã cập nhật cộng tác viên.');
    }

    public function destroy(LanguageCollaborator $languageCollaborator): RedirectResponse
    {
        if ($languageCollaborator->user()->exists()) return back()->withErrors(['collaborator'=>'CTV đang liên kết với account '.$languageCollaborator->user?->name.'. Hãy bỏ liên kết trước khi xóa.']);
        $languageCollaborator->delete();
        return back()->with('success','Đã xóa cộng tác viên.');
    }

    public function export()
    {
        return ExcelExporter::download('cong-tac-vien-'.date('Ymd').'.xlsx',['Mã','Họ tên','Điện thoại','Email','Hoa hồng %','Trạng thái'],LanguageCollaborator::withTrashed()->get()->map(fn($item)=>[$item->code,$item->name,$item->phone,$item->email,$item->commission_rate,$item->active?'Hoạt động':'Ngừng']));
    }

    public function show(Request $request, LanguageCollaborator $languageCollaborator): View
    {
        $filters=$this->referralFilters($request);
        $query=$this->referralQuery($languageCollaborator,$filters);
        // Bỏ ORDER BY của danh sách trước khi chạy aggregate. MySQL strict mode
        // (ONLY_FULL_GROUP_BY) không cho phép ORDER BY received_at/id trong truy vấn COUNT/SUM không GROUP BY.
        $summary=(clone $query)
            ->reorder()
            ->selectRaw("COUNT(*) as referral_count, SUM(CASE WHEN status = 'registered' THEN 1 ELSE 0 END) as registered_count, SUM(CASE WHEN converted_student_id IS NOT NULL THEN 1 ELSE 0 END) as converted_count")
            ->first();
        $monthlySummary=$languageCollaborator->leads()
            ->whereYear('received_at',$filters['year'])
            ->when($filters['status'],fn($query,$status)=>$query->where('status',$status))
            ->when($filters['course'],fn($query,$course)=>$query->where('language_course_id',$course))
            ->when($filters['q'],fn($query,$search)=>$query->where(fn($builder)=>$builder
                ->where('name','like',"%{$search}%")
                ->orWhere('code','like',"%{$search}%")
                ->orWhere('phone','like',"%{$search}%")))
            ->selectRaw("MONTH(received_at) as month, COUNT(*) as referral_count, SUM(CASE WHEN status = 'registered' THEN 1 ELSE 0 END) as registered_count, SUM(CASE WHEN converted_student_id IS NOT NULL THEN 1 ELSE 0 END) as converted_count")
            ->groupByRaw('MONTH(received_at)')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        return view('language.collaborators.show',[
            // Trang chi tiết không sử dụng quan hệ account. Không eager-load để vẫn
            // xem được dữ liệu CTV trên hệ thống đang nâng cấp dở cột liên kết user.
            'item'=>$languageCollaborator,
            'items'=>$query->paginate(\App\Support\Pagination::perPage())->withQueryString(),
            'filters'=>$filters,
            'summary'=>$summary,
            'monthlySummary'=>$monthlySummary,
            'years'=>$this->referralYears($languageCollaborator),
            'courses'=>LanguageCourse::withTrashed()->whereIn('id',$languageCollaborator->leads()->whereNotNull('language_course_id')->select('language_course_id'))->orderBy('name')->get(['id','name']),
        ]);
    }

    public function exportReferrals(Request $request, LanguageCollaborator $languageCollaborator)
    {
        $filters=$this->referralFilters($request);
        $statusLabels=['new'=>'Mới tiếp nhận','contacted'=>'Đã liên hệ','consulting'=>'Đang tư vấn','placement_test'=>'Hẹn kiểm tra','waiting'=>'Chờ phản hồi','registered'=>'Đã đăng ký','not_interested'=>'Không quan tâm','follow_up'=>'Chăm sóc lại'];
        $rows=$this->referralQuery($languageCollaborator,$filters)->get()->map(fn($lead)=>[
            $lead->code,
            $lead->name,
            $lead->phone,
            $lead->email,
            $lead->received_at?->format('d/m/Y'),
            $lead->course?->name,
            $lead->consultant?->name,
            $statusLabels[$lead->status]??$lead->status,
            $lead->convertedStudent?->code,
            $lead->convertedStudent?->name,
        ]);
        $period=$filters['year'].($filters['month']?'-'.str_pad((string)$filters['month'],2,'0',STR_PAD_LEFT):'');

        return ExcelExporter::download(
            'hoc-vien-gioi-thieu-'.$languageCollaborator->code.'-'.$period.'.xlsx',
            ['Mã khách','Họ tên','Điện thoại','Email','Ngày tiếp nhận','Khóa học','Tư vấn viên','Trạng thái','Mã học viên','Học viên chính thức'],
            $rows
        );
    }

    private function referralFilters(Request $request): array
    {
        $validated=$request->validate([
            'q'=>['nullable','string','max:255'],
            'month'=>['nullable','integer','between:1,12'],
            'year'=>['nullable','integer','between:2020,2100'],
            'status'=>['nullable','in:new,contacted,consulting,placement_test,waiting,registered,not_interested,follow_up'],
            'course'=>['nullable','integer'],
        ]);

        return [
            'q'=>trim((string)($validated['q']??'')),
            'month'=>isset($validated['month'])?(int)$validated['month']:null,
            'year'=>isset($validated['year'])?(int)$validated['year']:(int)now()->year,
            'status'=>$validated['status']??null,
            'course'=>isset($validated['course'])?(int)$validated['course']:null,
        ];
    }

    private function referralQuery(LanguageCollaborator $languageCollaborator, array $filters)
    {
        return $languageCollaborator->leads()
            ->with(['course','consultant','convertedStudent'])
            ->whereYear('received_at',$filters['year'])
            ->when($filters['month'],fn($query,$month)=>$query->whereMonth('received_at',$month))
            ->when($filters['status'],fn($query,$status)=>$query->where('status',$status))
            ->when($filters['course'],fn($query,$course)=>$query->where('language_course_id',$course))
            ->when($filters['q'],fn($query,$search)=>$query->where(fn($builder)=>$builder
                ->where('name','like',"%{$search}%")
                ->orWhere('code','like',"%{$search}%")
                ->orWhere('phone','like',"%{$search}%")))
            ->orderByDesc('received_at')
            ->orderByDesc('id');
    }

    private function referralYears(LanguageCollaborator $languageCollaborator)
    {
        return $languageCollaborator->leads()->whereNotNull('received_at')
            ->selectRaw('YEAR(received_at) as year')->distinct()->pluck('year')
            ->map(fn($year)=>(int)$year)->push((int)now()->year)->unique()->sortDesc()->values();
    }

    private function form(LanguageCollaborator $item): View
    {
        $linkedUserId=$item->user?->id;
        $users=User::with('personnel')->where(fn($query)=>$query->where('active',true)->when($linkedUserId,fn($q)=>$q->orWhere('id',$linkedUserId)))
            ->where(fn($query)=>$query->whereNull('language_collaborator_id')->when($linkedUserId,fn($q)=>$q->orWhere('id',$linkedUserId)))
            ->orderBy('name')->get();
        return view('language.collaborators.form',compact('item','users','linkedUserId'));
    }

    private function data(Request $request,?LanguageCollaborator $collaborator=null): array
    {
        $user=$request->filled('user_id')?User::with('personnel')->find($request->integer('user_id')):null;
        if ($user) $request->merge([
            'name'=>$request->input('name')?:($user->personnel?->name?:$user->name),
            'phone'=>$request->input('phone')?:$user->personnel?->phone,
            'email'=>$request->input('email')?:($user->personnel?->email?:$user->email),
        ]);
        $validated=$request->validate(['user_id'=>'nullable|exists:users,id','name'=>'required|max:255','phone'=>'nullable|max:30','email'=>'nullable|email','address'=>'nullable|max:255','commission_rate'=>'required|numeric|min:0|max:100','note'=>'nullable']);
        if ($user && $user->language_collaborator_id && $user->language_collaborator_id!==$collaborator?->id) throw ValidationException::withMessages(['user_id'=>'Account này đã liên kết với một cộng tác viên khác.']);
        if ($user?->personnel_id && LanguageCollaborator::where('personnel_id',$user->personnel_id)->when($collaborator,fn($query)=>$query->whereKeyNot($collaborator->id))->exists()) throw ValidationException::withMessages(['user_id'=>'Hồ sơ nhân sự của account này đã liên kết với một cộng tác viên khác.']);
        unset($validated['user_id']);
        $validated['active']=$request->boolean('active');
        $validated['personnel_id']=$user?->personnel_id;
        return [$validated,$user];
    }

    private function syncUser(LanguageCollaborator $collaborator,?User $user): void
    {
        User::where('language_collaborator_id',$collaborator->id)->when($user,fn($query)=>$query->whereKeyNot($user->id))->update(['language_collaborator_id'=>null]);
        if ($user) $user->update(['language_collaborator_id'=>$collaborator->id]);
    }
}
