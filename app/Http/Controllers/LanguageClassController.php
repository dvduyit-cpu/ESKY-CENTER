<?php

namespace App\Http\Controllers;

use App\Models\{LanguageClass, LanguageClassLesson, LanguageCourse, LanguageEnrollment, LanguageLevel, LanguageProgram, LanguageStudent, LanguageStudentMonthlyProgress, LanguageStudentScore, User};
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;
use App\Support\LanguageEnrollmentManager;

class LanguageClassController extends Controller
{
    public function __construct(private readonly LanguageEnrollmentManager $enrollmentManager) {}

    public function index(Request $request): View
    {
        $query=LanguageClass::with(['program','level','teacher'])->withCount(['enrollments as enrollments_count'=>fn($q)=>$q->where('status','studying')])->latest();
        if($request->user()->isTeacher())$query->where('teacher_user_id',$request->user()->id);
        if($request->filled('q')){$search=$request->string('q');$query->where(fn($q)=>$q->where('name','like',"%{$search}%")->orWhere('code','like',"%{$search}%"));}
        if($request->filled('status'))$query->where('status',$request->status);
        return view('language.classes.index',['items'=>$query->paginate(\App\Support\Pagination::perPage())->withQueryString()]);
    }

    public function teacherIndex(Request $request): View
    {
        $user=$request->user();
        $query=LanguageClass::with(['program','level','teacher'])->withCount(['enrollments as enrollments_count'=>fn($q)=>$q->where('status','studying')]);
        if($user->canTeach()||!($user->isAdmin()||$user->allowed('language_classes','update')))$query->where('teacher_user_id',$user->id);
        $request->boolean('history')?$query->whereIn('status',['completed','cancelled']):$query->whereNotIn('status',['completed','cancelled']);
        return view('language.classes.teacher-index',['items'=>$query->orderByDesc('start_date')->get()]);
    }

    public function gradebook(Request $request, LanguageClass $languageClass): View
    {
        $this->authorizeManagement($request,$languageClass);
        $month=$request->date('month')?->startOfMonth()?:now()->startOfMonth();
        $languageClass->load(['program','level','teacher','enrollments'=>fn($q)=>$q->where('status','!=','dropped')->with(['student','monthlyProgress'=>fn($p)=>$p->whereDate('month',$month),'scores'=>fn($s)=>$s->whereYear('test_date',$month->year)->whereMonth('test_date',$month->month)->orderBy('test_date')])->orderBy('enrolled_at')]);
        $lessons=$languageClass->lessons()->with(['teacher','attendances.enrollment.student'])->orderByDesc('lesson_date')->orderByDesc('start_time')->get();
        $selectedLesson=$request->filled('lesson')?$languageClass->lessons()->with('attendances')->findOrFail($request->integer('lesson')):null;
        $availableStudents=LanguageStudent::with('guardians')->whereIn('status',['new','waiting_class','studying','dropped'])->whereDoesntHave('enrollments',fn($q)=>$q->where('language_class_id',$languageClass->id)->where('status','!=','dropped'))->orderBy('name')->get();
        $tuitionCheck=$this->tuitionCompletionCheck($languageClass);
        return view('language.classes.gradebook',compact('languageClass','month','lessons','selectedLesson','availableStudents','tuitionCheck'));
    }

    public function printLessonBook(Request $request, LanguageClass $languageClass): View
    {
        $this->authorizeManagement($request, $languageClass);
        $languageClass->load([
            'course', 'program', 'level', 'teacher',
            'enrollments' => fn ($query) => $query
                ->where('status', '!=', 'dropped')
                ->with(['student.guardians'])
                ->orderBy('enrolled_at'),
            'lessons' => fn ($query) => $query
                ->with('attendances')
                ->orderBy('lesson_date')
                ->orderBy('start_time'),
        ]);

        $sessionCount = (int) $languageClass->expected_sessions;
        if ($sessionCount < 1) {
            $sessionCount = max(1, $languageClass->lessons->count());
        }
        $sessionLessons = collect(range(1, $sessionCount))->map(
            fn (int $number) => [
                'number' => $number,
                'lesson' => $languageClass->lessons->get($number - 1),
            ]
        );

        return view('language.classes.print-lesson-book', compact('languageClass', 'sessionCount', 'sessionLessons'));
    }

    public function storeAttendance(Request $request, LanguageClass $languageClass): RedirectResponse
    {
        $this->authorizeManagement($request,$languageClass);
        $data=$request->validate([
            'lesson_id'=>'nullable|integer|exists:language_class_lessons,id',
            'lesson_date'=>'required|date|before_or_equal:today',
            'start_time'=>'required|date_format:H:i',
            'end_time'=>'required|date_format:H:i|after:start_time',
            'attendance'=>'required|array|min:1',
            'attendance.*.status'=>['required',Rule::in(['present','absent','late','excused'])],
            'attendance.*.note'=>'nullable|string|max:500',
        ]);
        $enrollments=$languageClass->enrollments()->whereIn('status',['studying','paused','reserved'])->get();
        if($enrollments->isEmpty())throw ValidationException::withMessages(['attendance'=>'Lớp chưa có học viên đang học để điểm danh.']);
        foreach($enrollments as $enrollment){
            if(!isset($data['attendance'][$enrollment->id]))throw ValidationException::withMessages(['attendance'=>'Vui lòng điểm danh đầy đủ tất cả học viên đang có trong lớp.']);
        }

        $oldMonth=null;
        $lesson=DB::transaction(function()use($request,$languageClass,$data,$enrollments,&$oldMonth){
            if(!empty($data['lesson_id'])){
                $lesson=$languageClass->lessons()->lockForUpdate()->findOrFail($data['lesson_id']);
                $oldMonth=$lesson->lesson_date->copy()->startOfMonth()->toDateString();
                $lesson->update(['lesson_date'=>$data['lesson_date'],'start_time'=>$data['start_time'],'end_time'=>$data['end_time'],'teacher_user_id'=>$request->user()->id]);
            }else{
                $lesson=$languageClass->lessons()->firstOrCreate(
                    ['lesson_date'=>$data['lesson_date'],'start_time'=>$data['start_time']],
                    ['end_time'=>$data['end_time'],'teacher_user_id'=>$request->user()->id]
                );
                $lesson->update(['end_time'=>$data['end_time'],'teacher_user_id'=>$lesson->teacher_user_id?:$request->user()->id]);
            }
            foreach($enrollments as $enrollment){
                $row=$data['attendance'][$enrollment->id];
                $lesson->attendances()->updateOrCreate(
                    ['language_enrollment_id'=>$enrollment->id],
                    ['status'=>$row['status'],'note'=>$row['note']??null,'marked_by'=>$request->user()->id]
                );
            }
            $lesson->update(['attendance_marked_at'=>now(),'attendance_marked_by'=>$request->user()->id]);
            return $lesson;
        });

        $months=collect([$oldMonth,$lesson->lesson_date->copy()->startOfMonth()->toDateString()])->filter()->unique();
        foreach($months as $attendanceMonth)$this->syncMonthlyAttendance($languageClass,$attendanceMonth,$request->user()->id);
        $languageClass->update(['completed_sessions'=>$languageClass->lessons()->whereNotNull('attendance_marked_at')->whereDate('lesson_date','<=',today())->count()]);

        return redirect()->route('teacher-classes.gradebook',[$languageClass,'month'=>$lesson->lesson_date->format('Y-m')])->with('success','Đã lưu điểm danh và cập nhật chuyên cần tháng.');
    }

    public function storeLessonBook(Request $request, LanguageClass $languageClass): RedirectResponse
    {
        $this->authorizeManagement($request,$languageClass);
        $data=$request->validate([
            'lesson_id'=>'nullable|integer|exists:language_class_lessons,id',
            'lesson_date'=>'required|date|before_or_equal:today',
            'start_time'=>'required|date_format:H:i',
            'end_time'=>'required|date_format:H:i|after:start_time',
            'content'=>'required|string|max:5000',
            'evaluation'=>'nullable|string|max:3000',
            'teacher_signature'=>'required|string|max:255',
            'note'=>'nullable|string|max:3000',
        ]);
        $oldMonth=null;
        $lesson=DB::transaction(function()use($request,$languageClass,$data,&$oldMonth){
            if(!empty($data['lesson_id'])){
                $lesson=$languageClass->lessons()->lockForUpdate()->findOrFail($data['lesson_id']);
                $oldMonth=$lesson->lesson_date->copy()->startOfMonth()->toDateString();
            }else $lesson=$languageClass->lessons()->firstOrNew(['lesson_date'=>$data['lesson_date'],'start_time'=>$data['start_time']]);
            $lesson->fill([
                'lesson_date'=>$data['lesson_date'],'start_time'=>$data['start_time'],'end_time'=>$data['end_time'],
                'content'=>$data['content'],'evaluation'=>$data['evaluation']??null,'teacher_signature'=>$data['teacher_signature'],
                'note'=>$data['note']??null,'teacher_user_id'=>$request->user()->id,
            ]);
            $lesson->save();
            return $lesson;
        });
        if($lesson->attendance_marked_at){
            $months=collect([$oldMonth,$lesson->lesson_date->copy()->startOfMonth()->toDateString()])->filter()->unique();
            foreach($months as $attendanceMonth)$this->syncMonthlyAttendance($languageClass,$attendanceMonth,$request->user()->id);
        }

        return redirect()->route('teacher-classes.gradebook',[$languageClass,'month'=>$lesson->lesson_date->format('Y-m')])->with('success','Đã lưu sổ đầu bài của buổi học.');
    }

    public function destroyLesson(Request $request, LanguageClass $languageClass, LanguageClassLesson $lesson): RedirectResponse
    {
        $this->authorizeManagement($request,$languageClass);
        abort_unless((int)$lesson->language_class_id===(int)$languageClass->id,404);

        $attendanceMonth=$lesson->lesson_date->copy()->startOfMonth()->toDateString();
        DB::transaction(fn()=> $lesson->delete());
        $this->syncMonthlyAttendance($languageClass,$attendanceMonth,$request->user()->id);
        $languageClass->update([
            'completed_sessions'=>$languageClass->lessons()->whereNotNull('attendance_marked_at')->whereDate('lesson_date','<=',today())->count(),
        ]);

        return back()->with('success','Đã xóa buổi học, dữ liệu điểm danh và cập nhật lại chuyên cần.');
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
        abort_unless($user->isRegistrar(),403,'Chỉ giáo vụ hoặc quản trị viên được đóng lớp.');
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
            $remaining=(float)$charges->sum(fn($charge)=>$charge->remainingAmount());
            $pending=$charges->contains(fn($charge)=>$charge->payments->contains(fn($payment)=>$payment->receipt_status==='pending'));
            if($pending)$blockers[]=['student'=>$enrollment->student?->name??'Học viên','reason'=>'có phiếu thu đang chờ xác nhận'];
            elseif($remaining>0.009)$blockers[]=['student'=>$enrollment->student?->name??'Học viên','reason'=>'còn thiếu '.number_format($remaining,0,',','.').' đ'];
        }
        return ['total'=>$enrollments->count(),'blockers'=>$blockers,'ready'=>count($blockers)===0];
    }

    public function teacherEnroll(Request $request, LanguageClass $languageClass): RedirectResponse
    {
        $this->authorizeRegistrar($request);
        $count=$this->enrollSelectedStudents($request,$languageClass);
        return back()->with('success','Đã xếp '.$count.' học viên vào lớp và đồng bộ khoản thu học phí.');
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
    public function edit(Request $request,LanguageClass $languageClass):View{$this->authorizeManagement($request,$languageClass);return $this->form($languageClass->load(['enrollments'=>fn($q)=>$q->where('status','!=','dropped')->with('student')]));}
    public function update(Request $r,LanguageClass $languageClass):RedirectResponse{$this->authorizeManagement($r,$languageClass);$languageClass->update($this->data($r,$languageClass));return redirect()->route('language-classes.index')->with('success','Đã cập nhật lớp học.');}
    public function destroy(Request $request,LanguageClass $languageClass):RedirectResponse{$this->authorizeManagement($request,$languageClass);$languageClass->delete();return back()->with('success','Đã xóa lớp học.');}
    public function enroll(Request $r,LanguageClass $languageClass):RedirectResponse{$this->authorizeRegistrar($r);$count=$this->enrollSelectedStudents($r,$languageClass);return back()->with('success','Đã xếp '.$count.' học viên vào lớp và đồng bộ khoản thu học phí.');}
    public function unenroll(Request $request,LanguageClass $languageClass,LanguageEnrollment $enrollment):RedirectResponse{$this->authorizeRegistrar($request);$chargeDeleted=$this->enrollmentManager->unenroll($languageClass,$enrollment);$message=$chargeDeleted?'Đã đưa học viên khỏi lớp và xóa khoản học phí chưa thu.':'Đã đưa học viên khỏi lớp. Khoản đã thu hoặc học phí chuyển sang được giữ để quyết toán.';return back()->with('success',$message);}

    public function transferForm(Request $request, LanguageClass $languageClass, LanguageEnrollment $enrollment): View
    {
        $this->authorizeRegistrar($request);
        abort_unless($enrollment->language_class_id === $languageClass->id, 404);
        abort_unless(in_array($enrollment->status, ['studying', 'paused', 'reserved'], true), 422, 'Học viên không còn trong lớp này.');
        $enrollment->load(['student', 'languageClass.course']);
        $sourceCharge = $enrollment->student->tuitionCharges()
            ->where('language_class_id', $languageClass->id)
            ->with('payments')
            ->first();
        $targetClasses = LanguageClass::query()
            ->with('course')
            ->withCount(['enrollments as studying_count' => fn ($query) => $query->where('status', 'studying')])
            ->whereKeyNot($languageClass->id)
            ->whereIn('status', ['recruiting', 'upcoming', 'active'])
            ->whereNotNull('language_course_id')
            ->whereDoesntHave('enrollments', fn ($query) => $query->where('language_student_id', $enrollment->language_student_id))
            ->orderBy('name')
            ->get()
            ->filter(fn ($class) => $class->studying_count < $class->max_students);

        return view('language.classes.transfer', compact('languageClass', 'enrollment', 'sourceCharge', 'targetClasses'));
    }

    public function transfer(Request $request, LanguageClass $languageClass, LanguageEnrollment $enrollment): RedirectResponse
    {
        $this->authorizeRegistrar($request);
        abort_unless($enrollment->language_class_id === $languageClass->id, 404);
        $maxSessions = max(0, (int) $languageClass->expected_sessions);
        $data = $request->validate([
            'to_language_class_id' => ['required', 'integer', 'exists:language_classes,id', Rule::notIn([$languageClass->id])],
            'effective_date' => ['required', 'date', 'after_or_equal:'.$enrollment->enrolled_at->format('Y-m-d')],
            'sessions_used' => ['required', 'integer', 'min:0', 'max:'.$maxSessions],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        $transfer = $this->enrollmentManager->transfer(
            $languageClass,
            $enrollment,
            LanguageClass::findOrFail($data['to_language_class_id']),
            $data['effective_date'],
            (int) $data['sessions_used'],
            $request->user()->id,
            $data['note'] ?? null
        );
        $message = 'Đã chuyển học viên sang lớp mới và khấu trừ '.number_format((float) $transfer->applied_amount, 0, ',', '.').'đ học phí.';
        if ((float) $transfer->surplus_amount > 0) {
            $message .= ' Còn '.number_format((float) $transfer->surplus_amount, 0, ',', '.').'đ chờ hoàn hoặc bảo lưu.';
        }

        return redirect()->route('language-students.show', $enrollment->language_student_id)->with('success', $message);
    }

    private function syncMonthlyAttendance(LanguageClass $languageClass,string $month,int $teacherId):void
    {
        $monthDate=\Carbon\Carbon::parse($month)->startOfMonth();
        $enrollments=$languageClass->enrollments()->get();
        foreach($enrollments as $enrollment){
            $records=$enrollment->attendances()->whereHas('lesson',fn($query)=>$query->whereYear('lesson_date',$monthDate->year)->whereMonth('lesson_date',$monthDate->month))->get();
            $progress=$enrollment->monthlyProgress()->whereDate('month',$monthDate)->first();
            if($records->isEmpty()&&!$progress)continue;
            $enrollment->monthlyProgress()->updateOrCreate(
                ['month'=>$monthDate->toDateString()],
                ['planned_sessions'=>$records->count(),'attended_sessions'=>$records->whereIn('status',['present','late'])->count(),'teacher_user_id'=>$teacherId]
            );
        }
    }

    private function authorizeManagement(Request $request,LanguageClass $class):void
    {
        $user=$request->user();
        $teacherWorkspace=$request->routeIs('teacher-classes.*');
        $allowed=$user->isTeacher()||($teacherWorkspace&&$user->canTeach())
            ? $class->teacher_user_id===$user->id
            : $user->isAdmin()||$user->allowed('language_classes','update')||$class->teacher_user_id===$user->id;
        abort_unless($allowed,403,'Bạn không được phân công quản lý lớp này.');
    }
    private function authorizeRegistrar(Request $request):void{$user=$request->user();abort_unless($user->isRegistrar(),403,'Chỉ tài khoản được đánh dấu Giáo vụ hoặc quản trị viên mới được xếp và chuyển học viên.');}
    private function form(LanguageClass $item):View{$students=LanguageStudent::with('guardians')->whereIn('status',['new','waiting_class','studying','dropped']);if($item->exists)$students->whereDoesntHave('enrollments',fn($q)=>$q->where('language_class_id',$item->id)->where('status','!=','dropped'));$teachers=User::query()->where(fn($query)=>$query->where(fn($active)=>$active->where('active',1)->instructors())->when($item->teacher_user_id,fn($query,$teacherId)=>$query->orWhere('id',$teacherId)))->orderBy('name')->get();return view('language.classes.form',compact('item','teachers')+['courses'=>LanguageCourse::where(fn($query)=>$query->where('active',1)->orWhere('id', $item->language_course_id))->with(['program','level'])->orderBy('name')->get(),'students'=>$students->orderBy('name')->get()]);}

    private function enrollSelectedStudents(Request $request,LanguageClass $languageClass):int
    {
        if(!$request->has('language_student_ids')&&$request->filled('language_student_id'))$request->merge(['language_student_ids'=>[$request->input('language_student_id')]]);
        $data=$request->validate(['language_student_ids'=>'required|array|min:1','language_student_ids.*'=>'required|integer|distinct|exists:language_students,id','enrolled_at'=>'required|date'],['language_student_ids.required'=>'Vui lòng chọn ít nhất một học viên.','language_student_ids.min'=>'Vui lòng chọn ít nhất một học viên.']);
        $ids=collect($data['language_student_ids'])->map(fn($id)=>(int)$id)->unique()->values();
        $students=LanguageStudent::whereKey($ids->all())->whereIn('status',['new','waiting_class','studying','dropped'])->get()->keyBy('id');
        if($students->count()!==$ids->count())throw ValidationException::withMessages(['language_student_ids'=>'Có học viên không còn đủ điều kiện xếp lớp. Vui lòng tải lại danh sách.']);
        DB::transaction(function()use($ids,$students,$languageClass,$data,$request){foreach($ids as $id)$this->enrollmentManager->enroll($languageClass,$students->get($id),$data['enrolled_at'],$request->user()->id);});
        return $ids->count();
    }
    private function data(Request $r,?LanguageClass $m=null):array{$data=$r->validate(['code'=>['required','max:30',Rule::unique('language_classes')->ignore($m)],'name'=>'required|max:255','language_course_id'=>'required|exists:language_courses,id','teacher_user_id'=>'nullable|exists:users,id','room'=>'nullable|max:255','start_date'=>'nullable|date','expected_end_date'=>'nullable|date|after_or_equal:start_date','max_students'=>'required|integer|min:1','status'=>['required',Rule::in(['planned','recruiting','upcoming','active','paused','completed','cancelled'])],'schedule_note'=>'nullable','note'=>'nullable']);if(!empty($data['teacher_user_id'])&&(int)$data['teacher_user_id']!==(int)$m?->teacher_user_id){$teacher=User::findOrFail($data['teacher_user_id']);if(!$teacher->active||!$teacher->canTeach())throw ValidationException::withMessages(['teacher_user_id'=>'Chỉ được phân công tài khoản Giáo viên hoặc nhân viên đã bật Kiêm giảng dạy.']);}$course=LanguageCourse::with('level')->findOrFail($data['language_course_id']);if(!$course->language_program_id||!$course->language_level_id)throw ValidationException::withMessages(['language_course_id'=>'Khóa học chưa được liên kết đầy đủ với chương trình và cấp độ.']);return $data+['language_program_id'=>$course->language_program_id,'language_level_id'=>$course->language_level_id,'expected_sessions'=>$course->sessions,'default_tuition'=>$course->tuition];}
}
