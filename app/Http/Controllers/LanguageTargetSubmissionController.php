<?php

namespace App\Http\Controllers;

use App\Models\{LanguageClass,LanguageCollaborator,LanguageCourse,LanguageLead,LanguageTargetSubmission,User};
use App\Support\{CenterCode, TextNormalizer};
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
        $filters=$request->validate([
            'date'=>['nullable','date'],
            'month'=>['nullable','integer','between:1,12'],
            'year'=>['nullable','integer','between:2020,2100'],
        ]);
        $mine=LanguageTargetSubmission::query()->where('submitted_by',$request->user()->id);
        $years=(clone $mine)->selectRaw('YEAR(created_at) as year')->distinct()->pluck('year')
            ->map(fn($year)=>(int)$year)->push((int)now()->year)->unique()->sortDesc()->values();
        $query=(clone $mine)->with(['course','submitter','lead.consultant']);
        $hasMonthOrYear=! empty($filters['month'])||! empty($filters['year']);
        if (! $hasMonthOrYear && ! empty($filters['date'])) {
            $query->whereDate('created_at',$filters['date']);
        } else {
            if (! empty($filters['year'])) $query->whereYear('created_at',$filters['year']);
            if (! empty($filters['month'])) $query->whereMonth('created_at',$filters['month']);
        }
        $query->latest();
        return view('language.target-submissions.index',[
            'items'=>$query->paginate(\App\Support\Pagination::perPage())->withQueryString(),
            'courses'=>LanguageCourse::where('active',1)->orderBy('name')->get(),
            'classes'=>LanguageClass::whereIn('status',['recruiting','upcoming','active'])->with('course')->orderBy('name')->get(),
            'sources'=>LanguageTargetSubmission::SOURCE_LABELS,
            'years'=>$years,
            'hasHistoryFilter'=>filled($filters['date']??null)||filled($filters['month']??null)||filled($filters['year']??null),
        ]);
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
        if (! $request->boolean('is_walk_in')) {
            $request->merge(['source' => null]);
        }

        $data=$request->validate([
            'name'=>'required|string|max:255', 'phone'=>'required|string|max:30',
            'course_choice'=>['required',Rule::in(['existing','other'])],
            'language_course_id'=>'nullable|required_if:course_choice,existing|exists:language_courses,id',
            'language_class_id'=>['nullable', Rule::exists('language_classes', 'id')->where(fn ($query) => $query->whereNull('deleted_at'))],
            'other_course'=>'nullable|required_if:course_choice,other|string|max:255',
            'is_walk_in'=>['nullable','boolean'],
            'source'=>['nullable','required_if:is_walk_in,1',Rule::in(['fanpage','zalo','zalo_oa','web','hotline'])],
            'note'=>'nullable|string|max:2000',
        ],[
            'name.required'=>'Vui lòng nhập họ và tên.', 'phone.required'=>'Vui lòng nhập số điện thoại.',
            'language_course_id.required_if'=>'Vui lòng chọn khóa học quan tâm.',
            'other_course.required_if'=>'Vui lòng nhập khóa học quan tâm khác.',
            'source.required_if'=>'Vui lòng chọn nguồn tự đến của khách hàng.',
            'source.in'=>'Nguồn khách hàng không hợp lệ.',
            'note.max'=>'Ghi chú không được dài quá 2.000 ký tự.',
        ]);
        $data['language_class_id'] ??= null;
        if ($data['course_choice']==='existing') $data['other_course']=null; else $data['language_course_id']=null;
        if (! empty($data['language_class_id'])) {
            $class = LanguageClass::findOrFail($data['language_class_id']);
            if ((int) $class->language_course_id !== (int) $data['language_course_id']) {
                throw ValidationException::withMessages(['language_class_id' => 'Lớp được chọn không thuộc khóa học đã chọn.']);
            }
        }
        $data['phone_normalized']=TextNormalizer::phone($data['phone']) ?: trim($data['phone']);
        $data['course_key']=$data['language_class_id']
            ? 'class:'.$data['language_class_id']
            : ($data['language_course_id']
            ? 'course:'.$data['language_course_id']
            : 'other:'.Str::lower(Str::ascii(Str::squish($data['other_course']))));
        $sender=$request->user()->loadMissing(['personnel','languageCollaborator']);
        $collaborator=$this->senderCollaborator($sender);
        $consultant=$sender->personnel?->is_consultant && $sender->personnel?->active && $sender->active
            ? $sender
            : User::where('active',1)->whereHas('personnel',fn($query)=>$query->where('active',1)->where('is_consultant',1))->orderBy('id')->first();
        if (! $consultant) {
            throw ValidationException::withMessages(['phone'=>'Chưa có nhân sự nào được đánh dấu “Là nhân viên tư vấn” và liên kết tài khoản đang hoạt động. Vui lòng cấu hình nhân sự tư vấn trước.']);
        }
        unset($data['course_choice'],$data['is_walk_in']);
        $sourceLabel=$data['source'] ? LanguageTargetSubmission::SOURCE_LABELS[$data['source']] : null;
        DB::transaction(function () use ($data,$sender,$consultant,$collaborator,$sourceLabel) {
            // The first CTV to submit this phone + course owns the attribution.
            // Lock all registrations of the course to also protect concurrent A/B submissions.
            $duplicateLead = $data['language_course_id']
                ? LanguageLead::with(['course','collaborator','consultant.personnel'])
                    ->where('language_course_id', $data['language_course_id'])
                    ->orderBy('created_at')->orderBy('id')->lockForUpdate()->get()
                    ->first(fn (LanguageLead $lead) => TextNormalizer::phone($lead->phone) === $data['phone_normalized']
                        && ($data['language_class_id'] === null || $lead->language_class_id === null || (int) $lead->language_class_id === (int) $data['language_class_id']))
                : null;
            $duplicateSubmission = LanguageTargetSubmission::with(['course','submitter.personnel','submitter.languageCollaborator'])
                ->when($data['language_course_id'], fn ($query) => $query->where('language_course_id', $data['language_course_id']), fn ($query) => $query->where('course_key', $data['course_key']))
                ->orderBy('created_at')->orderBy('id')->lockForUpdate()->get()
                ->first(fn (LanguageTargetSubmission $submission) => TextNormalizer::phone($submission->phone) === $data['phone_normalized']
                    && ($data['language_class_id'] === null || $submission->language_class_id === null || (int) $submission->language_class_id === (int) $data['language_class_id']));

            if ($duplicateLead || $duplicateSubmission) {
                $owner = $duplicateLead?->collaborator?->name
                    ?? $duplicateSubmission?->submitter?->languageCollaborator?->name
                    ?? $duplicateSubmission?->submitter?->personnel?->name
                    ?? $duplicateSubmission?->submitter?->name
                    ?? $duplicateLead?->consultant?->personnel?->name
                    ?? $duplicateLead?->consultant?->name
                    ?? 'chưa xác định';
                $course = $duplicateLead?->course?->name
                    ?? $duplicateSubmission?->course?->name
                    ?? $duplicateSubmission?->other_course
                    ?? 'khóa/lớp này';
                $firstAt = ($duplicateLead?->created_at ?? $duplicateSubmission?->created_at)?->format('d/m/Y H:i');

                throw ValidationException::withMessages([
                    'phone' => "SĐT này đã được ghi nhận trước cho {$owner}, khóa/lớp {$course}".($firstAt ? " lúc {$firstAt}" : '').'. Hệ thống không ghi trùng và không đổi người được nhận chỉ tiêu.',
                ]);
            }

            $submission=LanguageTargetSubmission::create($data+['submitted_by'=>$sender->id]);
            $lead=LanguageLead::create([
                'code'=>CenterCode::next('language_leads','KH'), 'name'=>$data['name'], 'phone'=>$data['phone'],
                'source'=>$sourceLabel, 'received_at'=>now()->toDateString(),
                'language_course_id'=>$data['language_course_id'], 'language_class_id'=>$data['language_class_id'], 'consultant_user_id'=>$consultant->id,
                'language_collaborator_id'=>$collaborator?->id,
                'status'=>'new', 'consultation'=>$data['other_course'] ? 'Khóa học quan tâm khác: '.$data['other_course'] : null,
                'note'=>($data['note'] ?? null) ?: 'Tự động tạo từ trang Gửi chỉ tiêu bởi '.$sender->name.'.',
            ]);
            $submission->update(['language_lead_id'=>$lead->id]);
        });
        return back()->with('success','Đã gửi chỉ tiêu thành công. Khách hàng đã được chuyển cho nhân viên tư vấn '.$consultant->name.'.');
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
