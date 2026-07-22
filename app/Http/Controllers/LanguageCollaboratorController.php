<?php

namespace App\Http\Controllers;

use App\Models\{LanguageCollaborator,User};
use App\Support\{CenterCode,ExcelExporter};
use Illuminate\Http\{JsonResponse,RedirectResponse,Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LanguageCollaboratorController extends Controller
{
    public function index(Request $request): View
    {
        $query=LanguageCollaborator::with(['user'])->withCount('leads')->latest();
        if ($request->filled('q')) {
            $search=$request->string('q');
            $query->where(fn($builder)=>$builder->where('name','like',"%{$search}%")->orWhere('phone','like',"%{$search}%"));
        }
        return view('language.collaborators.index',['items'=>$query->paginate(\App\Support\Pagination::perPage())->withQueryString()]);
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

    private function form(LanguageCollaborator $item): View
    {
        $linkedUserId=$item->user?->id;
        $users=User::with('personnel')->where('active',true)->whereNull('deleted_at')
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
        if ($user?->personnel_id) $validated['personnel_id']=$user->personnel_id;
        return [$validated,$user];
    }

    private function syncUser(LanguageCollaborator $collaborator,?User $user): void
    {
        User::where('language_collaborator_id',$collaborator->id)->when($user,fn($query)=>$query->whereKeyNot($user->id))->update(['language_collaborator_id'=>null]);
        if ($user) $user->update(['language_collaborator_id'=>$collaborator->id]);
    }
}
