<?php

namespace App\Http\Controllers;

use App\Models\{LanguageClass, LanguageCourse, LanguageEnrollment, LanguageLevel, LanguageProgram, LanguageStudent, LanguageStudentMonthlyProgress, LanguageStudentScore, User};
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class LanguageClassController extends Controller
{
    public function index(Request $request): View
    {
        $query=LanguageClass::with(['program','level','teacher'])->withCount(['enrollments as enrollments_count'=>fn($q)=>$q->where('status','studying')])->latest();
        if($request->filled('q')){$search=$request->string('q');$query->where(fn($q)=>$q->where('name','like',"%{$search}%")->orWhere('code','like',"%{$search}%"));}
        if($request->filled('status'))$query->where('status',$request->status);
        return view('language.classes.index',['items'=>$query->paginate(\App\Support\Pagination::perPage())->withQueryString()]);
    }

    public function teacherIndex(Request $request): View
    {
        $user=$request->user();
        $query=LanguageClass::with(['program','level','teacher'])->withCount(['enrollments as enrollments_count'=>fn($q)=>$q->where('status','studying')]);
        if(!($user->isAdmin()||$user->allowed('language_classes','update')))$query->where('teacher_user_id',$user->id);
        $request->boolean('history')?$query->whereIn('status',['completed','cancelled']):$query->whereNotIn('status',['completed','cancelled']);
        return view('language.classes.teacher-index',['items'=>$query->orderByDesc('start_date')->get()]);
    }

    public function gradebook(Request $request, LanguageClass $languageClass): View
    {
        $this->authorizeManagement($request,$languageClass);
        $month=$request->date('month')?->startOfMonth()?:now()->startOfMonth();
        $languageClass->load(['program','level','teacher','enrollments'=>fn($q)=>$q->with(['student','monthlyProgress'=>fn($p)=>$p->whereDate('month',$month),'scores'=>fn($s)=>$s->whereYear('test_date',$month->year)->whereMonth('test_date',$month->month)->orderBy('test_date')])->orderBy('enrolled_at')]);
        $availableStudents=LanguageStudent::whereIn('status',['new','waiting_class','studying'])->whereDoesntHave('enrollments',fn($q)=>$q->where('language_class_id',$languageClass->id))->orderBy('name')->get(['id','code','name']);
        $tuitionCheck=$this->tuitionCompletionCheck($languageClass);
        return view('language.classes.gradebook',compact('languageClass','month','availableStudents','tuitionCheck'));
    }

    public function updateCompletedSessions(Request $request, LanguageClass $languageClass): RedirectResponse
    {
        $this->authorizeManagement($request,$languageClass);
        $data=$request->validate(['completed_sessions'=>'required|integer|min:0|max:10000']);
        $languageClass->update($data);
        $languageClass->refresh();
        if($languageClass->isCompletionDue()) return back()->with('success','Đã cập nhật số buổi. Lớp đã đủ điều kiện để giáo viên gửi đề nghị hoàn thành.');
        return back()->with('success','Đã cập nhật số buổi thực tế.');
        return redirect()->route('teacher-classes.index')->with('success',$completed?'Lớp đã đủ số buổi và được chuyển vào lịch sử.':'Đã cập nhật số buổi thực tế.');
    }

    public function requestCompletion(Request $request, LanguageClass $languageClass): RedirectResponse
    {
        $this->authorizeManagement($request,$languageClass);
        abort_if(in_array($languageClass->status,['completed','cancelled'],true),422,'Lớp đã kết thúc.');
        if(! $languageClass->isCompletionDue()) throw ValidationException::withMessages(['completion'=>'Lớp chưa đủ số buổi và chưa đến ngày kết thúc dự kiến.']);
        $data=$request->validate(['completion_note'=>'nullable|string|max:2000']);
        $languageClass->update(['completion_requested_at'=>now(),'completion_requested_by'=>$request->user()->id,'completion_note'=>$data['completion_note']??null]);
        return back()->with('success','Đã gửi đề nghị hoàn thành. Giáo vụ sẽ kiểm tra học phí trước khi đóng lớp.');
    }

    public function closeClass(Request $request, LanguageClass $languageClass): RedirectResponse
    {
        $user=$request->user();
        abort_unless($user->isAdmin()||$user->allowed('language_classes','update'),403,'Chỉ giáo vụ hoặc quản trị viên được đóng lớp.');
        if(! $languageClass->completion_requested_at) throw ValidationException::withMessages(['completion'=>'Giáo viên chưa gửi đề nghị hoàn thành lớp.']);
        $check=$this->tuitionCompletionCheck($languageClass);
        if($check['blockers']) throw ValidationException::withMessages(['completion'=>'Không thể đóng lớp: '.implode('; ',array_map(fn($item)=>$item['student'].' — '.$item['reason'],$check['blockers']))]);
        DB::transaction(function()use($languageClass,$user){
            $languageClass->update(['status'=>'completed','completed_at'=>now(),'completed_by'=>$user->id]);
            $languageClass->enrollments()->whereIn('status',['studying','paused','reserved'])->update(['status'=>'completed','ended_at'=>now()->toDateString(),'exit_reason'=>'Lớp đã được giáo vụ xác nhận hoàn thành']);
        });
        return redirect()->route('teacher-classes.index',['history'=>1])->with('success','Đã kiểm tra học phí và đóng lớp. Toàn bộ điểm, đánh giá và lịch sử học viên được giữ lại.');
    }

    private function tuitionCompletionCheck(LanguageClass $languageClass): array
    {
        $enrollments=$languageClass->enrollments()->whereIn('status',['studying','paused','reserved'])->with(['student','student.tuitionCharges'=>fn($q)=>$q->where('language_class_id',$languageClass->id)->with('payments')])->get();
        $blockers=[];
        foreach($enrollments as $enrollment){
            $charges=$enrollment->student?->tuitionCharges??collect();
            if($charges->isEmpty()){$blockers[]=['student'=>$enrollment->student?->name??'Học viên','reason'=>'chưa có khoản học phí của lớp'];continue;}
            $remaining=max(0,(float)$charges->sum('payable_amount')-(float)$charges->sum('paid_amount'));
            $pending=$charges->contains(fn($charge)=>$charge->payments->contains(fn($payment)=>$payment->receipt_status==='pending'));
            if($pending)$blockers[]=['student'=>$enrollment->student?->name??'Học viên','reason'=>'có phiếu thu đang chờ xác nhận'];
            elseif($remaining>0.009)$blockers[]=['student'=>$enrollment->student?->name??'Học viên','reason'=>'còn thiếu '.number_format($remaining,0,',','.').' đ'];
        }
        return ['total'=>$enrollments->count(),'blockers'=>$blockers,'ready'=>count($blockers)===0];
    }

    public function teacherEnroll(Request $request, LanguageClass $languageClass): RedirectResponse
    {
        $user=$request->user();
        abort_unless($user->isAdmin()||$user->allowed('language_classes','update'),403,'Giáo viên không được xếp thêm học viên vào lớp.');
        $data=$request->validate(['language_student_id'=>'required|exists:language_students,id','enrolled_at'=>'required|date']);
        if($languageClass->enrollments()->where('status','studying')->count()>=$languageClass->max_students)return back()->withErrors(['class'=>'Lớp đã đủ sĩ số.']);
        $tuition=(float)(LanguageCourse::find($languageClass->language_course_id)?->tuition??0);
        $languageClass->enrollments()->updateOrCreate(['language_student_id'=>$data['language_student_id']],$data+['tuition'=>$tuition,'discount'=>0,'status'=>'studying','ended_at'=>null,'exit_reason'=>null]);
        return back()->with('success','Đã xếp học viên vào lớp.');
    }

    public function saveMonthlyProgress(Request $request, LanguageClass $languageClass): RedirectResponse
    {
        $this->authorizeManagement($request,$languageClass);
        $data=$request->validate(['month'=>'required|date_format:Y-m','progress'=>'required|array','progress.*.planned_sessions'=>'nullable|integer|min:0|max:100','progress.*.attended_sessions'=>'nullable|integer|min:0|max:100','progress.*.participation_score'=>'nullable|numeric|min:0|max:10','progress.*.homework_score'=>'nullable|numeric|min:0|max:10','progress.*.assessment'=>'nullable|max:2000','progress.*.learning_note'=>'nullable|max:2000']);
        $month=$data['month'].'-01';
        DB::transaction(function()use($data,$month,$languageClass,$request){foreach($data['progress'] as $enrollmentId=>$row){$enrollment=$languageClass->enrollments()->findOrFail($enrollmentId);LanguageStudentMonthlyProgress::updateOrCreate(['language_enrollment_id'=>$enrollment->id,'month'=>$month],$row+['teacher_user_id'=>$request->user()->id]);}});
        return back()->with('success','Đã lưu quá trình học theo tháng.');
    }

    public function storeScore(Request $request, LanguageClass $languageClass, LanguageEnrollment $enrollment): RedirectResponse
    {
        $this->authorizeManagement($request,$languageClass);
        abort_unless($enrollment->language_class_id===$languageClass->id,404);
        $data=$request->validate(['test_date'=>'required|date','test_name'=>'required|max:255','test_type'=>['required',Rule::in(['regular','midterm','final','oral','other'])],'score'=>'required|numeric|min:0','max_score'=>'required|numeric|gt:0','note'=>'nullable|max:2000']);
        if((float)$data['score']>(float)$data['max_score'])return back()->withErrors(['score'=>'Điểm không được lớn hơn điểm tối đa.']);
        $enrollment->scores()->create($data+['teacher_user_id'=>$request->user()->id]);
        return back()->with('success','Đã thêm điểm kiểm tra.');
    }

    public function destroyScore(Request $request, LanguageClass $languageClass, LanguageEnrollment $enrollment, LanguageStudentScore $score): RedirectResponse
    {
        $this->authorizeManagement($request,$languageClass);
        abort_unless($enrollment->language_class_id===$languageClass->id&&$score->language_enrollment_id===$enrollment->id,404);
        $score->delete(); return back()->with('success','Đã xóa điểm kiểm tra.');
    }

    public function updateEnrollmentStatus(Request $request, LanguageClass $languageClass, LanguageEnrollment $enrollment): RedirectResponse
    {
        $this->authorizeManagement($request,$languageClass);
        abort_unless($enrollment->language_class_id===$languageClass->id,404);
        $data=$request->validate(['status'=>['required',Rule::in(['studying','paused','reserved','completed','dropped'])],'ended_at'=>'nullable|date|after_or_equal:'.$enrollment->enrolled_at->format('Y-m-d'),'exit_reason'=>'nullable|max:255']);
        $data['ended_at']=$data['status']==='studying'?null:($data['ended_at']??now()->toDateString());
        $enrollment->update($data);
        return back()->with('success','Đã cập nhật trạng thái học; điểm và đánh giá vẫn được giữ.');
    }

    public function create():View{return $this->form(new LanguageClass);}
    public function store(Request $r):RedirectResponse{LanguageClass::create($this->data($r));return redirect()->route('language-classes.index')->with('success','Đã tạo lớp học.');}
    public function edit(LanguageClass $languageClass):View{return $this->form($languageClass->load('enrollments.student'));}
    public function update(Request $r,LanguageClass $languageClass):RedirectResponse{$languageClass->update($this->data($r,$languageClass));return redirect()->route('language-classes.index')->with('success','Đã cập nhật lớp học.');}
    public function destroy(LanguageClass $languageClass):RedirectResponse{$languageClass->delete();return back()->with('success','Đã xóa lớp học.');}
    public function enroll(Request $r,LanguageClass $languageClass):RedirectResponse{$d=$r->validate(['language_student_id'=>'required|exists:language_students,id','enrolled_at'=>'required|date']);if($languageClass->enrollments()->where('status','studying')->count()>=$languageClass->max_students)return back()->withErrors(['class'=>'Lớp đã đủ sĩ số.']);$course=LanguageCourse::findOrFail($languageClass->language_course_id);$languageClass->enrollments()->updateOrCreate(['language_student_id'=>$d['language_student_id']],$d+['tuition'=>$course->tuition,'discount'=>0,'status'=>'studying','ended_at'=>null,'exit_reason'=>null]);return back()->with('success','Đã xếp học viên vào lớp.');}
    public function unenroll(LanguageClass $languageClass,LanguageEnrollment $enrollment):RedirectResponse{abort_unless($enrollment->language_class_id===$languageClass->id,404);$enrollment->update(['status'=>'dropped','ended_at'=>now()->toDateString(),'exit_reason'=>'Đưa khỏi lớp']);return back()->with('success','Đã cho học viên thôi học và giữ nguyên lịch sử.');}

    private function authorizeManagement(Request $request,LanguageClass $class):void{$user=$request->user();abort_unless($user->isAdmin()||$user->allowed('language_classes','update')||$class->teacher_user_id===$user->id,403,'Bạn không được phân công quản lý lớp này.');}
    private function form(LanguageClass $item):View{return view('language.classes.form',compact('item')+['courses'=>LanguageCourse::where('active',1)->with(['program','level'])->orderBy('name')->get(),'teachers'=>User::where('active',1)->orderBy('name')->get(),'students'=>LanguageStudent::whereIn('status',['new','waiting_class','studying'])->orderBy('name')->get()]);}
    private function data(Request $r,?LanguageClass $m=null):array{$data=$r->validate(['code'=>['required','max:30',Rule::unique('language_classes')->ignore($m)],'name'=>'required|max:255','language_course_id'=>'required|exists:language_courses,id','teacher_user_id'=>'nullable|exists:users,id','room'=>'nullable|max:255','start_date'=>'nullable|date','expected_end_date'=>'nullable|date|after_or_equal:start_date','max_students'=>'required|integer|min:1','status'=>['required',Rule::in(['planned','recruiting','upcoming','active','paused','completed','cancelled'])],'schedule_note'=>'nullable','note'=>'nullable']);$course=LanguageCourse::with('level')->findOrFail($data['language_course_id']);if(!$course->language_program_id||!$course->language_level_id)throw ValidationException::withMessages(['language_course_id'=>'Khóa học chưa được liên kết đầy đủ với chương trình và cấp độ.']);return $data+['language_program_id'=>$course->language_program_id,'language_level_id'=>$course->language_level_id,'expected_sessions'=>$course->sessions,'default_tuition'=>$course->tuition];}
}
