<?php

namespace App\Http\Controllers;

use App\Models\{LanguageCollaborator,LanguageCourse,LanguageLead,LanguageTargetSubmission,User};
use App\Support\CenterCode;
use Illuminate\Http\{RedirectResponse,Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LanguageTargetSubmissionController extends Controller
{
    public function index(Request $request): View
    {
        $query=LanguageTargetSubmission::with(['course','submitter','lead.consultant'])->where('submitted_by',$request->user()->id)->latest();
        return view('language.target-submissions.index',['items'=>$query->paginate(20)->withQueryString(),'courses'=>LanguageCourse::where('active',1)->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Normalize the searchable select before validation. This also keeps the
        // request valid if a browser submits the special "other" option itself.
        if ($request->input('language_course_id') === '__other__') {
            $request->merge(['course_choice' => 'other', 'language_course_id' => null]);
        } elseif ($request->filled('language_course_id')) {
            $request->merge(['course_choice' => 'existing']);
        }

        $data=$request->validate([
            'name'=>'required|string|max:255', 'phone'=>'required|string|max:30',
            'course_choice'=>['required',Rule::in(['existing','other'])],
            'language_course_id'=>'nullable|required_if:course_choice,existing|exists:language_courses,id',
            'other_course'=>'nullable|required_if:course_choice,other|string|max:255',
        ],[
            'name.required'=>'Vui lòng nhập họ và tên.', 'phone.required'=>'Vui lòng nhập số điện thoại.',
            'language_course_id.required_if'=>'Vui lòng chọn khóa học quan tâm.',
            'other_course.required_if'=>'Vui lòng nhập khóa học quan tâm khác.',
        ]);
        if ($data['course_choice']==='existing') $data['other_course']=null; else $data['language_course_id']=null;
        $data['phone_normalized']=preg_replace('/\D+/', '', $data['phone']) ?: trim($data['phone']);
        $data['course_key']=$data['language_course_id']
            ? 'course:'.$data['language_course_id']
            : 'other:'.Str::lower(Str::ascii(Str::squish($data['other_course'])));
        $duplicateSubmission=LanguageTargetSubmission::with(['course','submitter.personnel'])
            ->where('phone_normalized',$data['phone_normalized'])->where('course_key',$data['course_key'])->latest()->first();
        $duplicateLead=null;
        if ($data['language_course_id']) {
            $duplicateLead=LanguageLead::with(['course','collaborator','consultant.personnel'])
                ->where('language_course_id',$data['language_course_id'])->latest()->get()
                ->first(fn($lead)=>(preg_replace('/\D+/', '', $lead->phone) ?: trim($lead->phone))===$data['phone_normalized']);
        }
        $allowNew=$duplicateLead?->status==='not_interested';
        if (! $allowNew && ($duplicateLead || $duplicateSubmission)) {
            if ($duplicateLead) {
                $owner=$duplicateLead->collaborator?->name ?? $duplicateLead->consultant?->personnel?->name ?? $duplicateLead->consultant?->name ?? 'Chưa xác định';
                $course=$duplicateLead->course?->name ?? 'Chưa xác định';
                $status=$duplicateLead->status==='registered' ? 'Đã đăng ký khóa học' : match($duplicateLead->status) {
                    'new'=>'Đang chờ tiếp nhận','contacted'=>'Đã liên hệ, đang chờ xử lý','consulting'=>'Đang tư vấn',
                    'placement_test'=>'Đang chờ kiểm tra','waiting'=>'Đang chờ phản hồi','follow_up'=>'Đang chăm sóc lại',
                    default=>'Đang chờ xử lý',
                };
            } else {
                $owner=$duplicateSubmission->submitter?->personnel?->name ?? $duplicateSubmission->submitter?->name ?? 'Chưa xác định';
                $course=$duplicateSubmission->course?->name ?? $duplicateSubmission->other_course ?? 'Chưa xác định';
                $status='Đang chờ tiếp nhận';
            }
            throw ValidationException::withMessages(['phone'=>"Số điện thoại này đã được ghi nhận cho {$owner}. Khóa học: {$course}. Trạng thái: {$status}. Hệ thống không thêm trùng."]);
        }
        $sender=$request->user()->loadMissing(['personnel','languageCollaborator']);
        $collaborator=$this->senderCollaborator($sender);
        $consultant=$sender->personnel?->is_consultant && $sender->personnel?->active && $sender->active
            ? $sender
            : User::where('active',1)->whereHas('personnel',fn($query)=>$query->where('active',1)->where('is_consultant',1))->orderBy('id')->first();
        if (! $consultant) {
            throw ValidationException::withMessages(['phone'=>'Chưa có nhân sự nào được đánh dấu “Là nhân viên tư vấn” và liên kết tài khoản đang hoạt động. Vui lòng cấu hình nhân sự tư vấn trước.']);
        }
        unset($data['course_choice']);
        DB::transaction(function () use ($data,$sender,$consultant,$collaborator) {
            $submission=LanguageTargetSubmission::create($data+['submitted_by'=>$sender->id]);
            $lead=LanguageLead::create([
                'code'=>CenterCode::next('language_leads','KH'), 'name'=>$data['name'], 'phone'=>$data['phone'],
                'source'=>'Gửi chỉ tiêu bởi '.$sender->name, 'received_at'=>now()->toDateString(),
                'language_course_id'=>$data['language_course_id'], 'consultant_user_id'=>$consultant->id,
                'language_collaborator_id'=>$collaborator?->id,
                'status'=>'new', 'consultation'=>$data['other_course'] ? 'Khóa học quan tâm khác: '.$data['other_course'] : null,
                'note'=>'Tự động tạo từ trang Gửi chỉ tiêu.',
            ]);
            $submission->update(['language_lead_id'=>$lead->id]);
        });
        $prefix=$allowNew?'Hồ sơ cũ có trạng thái Không quan tâm. Đã ghi nhận mới. ':'Đã gửi chỉ tiêu thành công. ';
        return back()->with('success',$prefix.'Khách hàng đã được chuyển cho nhân viên tư vấn '.$consultant->name.'.');
    }

    private function senderCollaborator(User $sender): ?LanguageCollaborator
    {
        if ($sender->languageCollaborator) {
            if ($sender->languageCollaborator->trashed()) $sender->languageCollaborator->restore();
            if (! $sender->languageCollaborator->active) $sender->languageCollaborator->update(['active'=>true]);
            return $sender->languageCollaborator;
        }
        $personnel=$sender->personnel;
        if (! $personnel) return null;

        $collaborator=LanguageCollaborator::withTrashed()->where('personnel_id',$personnel->id)->first();
        if ($collaborator) {
            if ($collaborator->trashed()) $collaborator->restore();
            if (! $collaborator->active) $collaborator->update(['active'=>true]);
            $sender->update(['language_collaborator_id'=>$collaborator->id]);
            return $collaborator;
        }

        $collaborator=LanguageCollaborator::where(function ($query) use ($personnel) {
            $query->where('name',$personnel->name);
            if ($personnel->phone) $query->orWhere('phone',$personnel->phone);
            if ($personnel->email) $query->orWhere('email',$personnel->email);
        })->first();

        if ($collaborator) {
            $collaborator->update(['personnel_id'=>$personnel->id]);
            return $collaborator;
        }

        return LanguageCollaborator::create([
            'personnel_id'=>$personnel->id, 'code'=>CenterCode::next('language_collaborators','CTV'),
            'name'=>$personnel->name, 'phone'=>$personnel->phone, 'email'=>$personnel->email,
            'commission_rate'=>0, 'active'=>true, 'note'=>'Tự động liên kết từ account gửi chỉ tiêu.',
        ]);
    }
}
