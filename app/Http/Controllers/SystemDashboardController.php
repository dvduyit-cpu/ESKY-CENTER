<?php

namespace App\Http\Controllers;

use App\Models\{ActivityLog,ExcessPayment,ImportBatch,LanguageClass,LanguageLead,LanguageStudent,LanguageTuitionCharge,LanguageTuitionPayment,Personnel,User,WorkTask,WorkTaskAssignee};
use App\Support\{ExcelExporter,KpiCalculator};
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemDashboardController extends Controller
{
    public function __construct(private readonly KpiCalculator $calculator) {}

    public function index(Request $request): View
    {
        $user=$request->user()->load('personnel');
        $canViewAll=$user->isAdmin()||$user->allowed('system_dashboard');
        [$start,$end,$period]=$this->period($request);
        $year=$request->integer('year',now()->year);
        $kpiFilters=['year'=>$year,'period_type'=>'year','period_value'=>0];
        if(!$canViewAll)$kpiFilters['personnel_id']=$user->personnel_id?:-1;
        $report=$this->calculator->report($kpiFilters);

        $students=LanguageStudent::query(); $leads=LanguageLead::query(); $classes=LanguageClass::query();
        $activities=ActivityLog::with('user')->whereBetween('created_at',[$start,$end])->latest();
        $imports=ImportBatch::with('user')->whereBetween('created_at',[$start,$end])->latest();
        $charges=LanguageTuitionCharge::whereBetween('created_at',[$start,$end]);
        $receipts=LanguageTuitionPayment::whereBetween('paid_at',[$start,$end]);
        $expenses=ExcessPayment::where('status','paid')->whereBetween('paid_at',[$start,$end]);
        if(!$canViewAll){
            $students->whereHas('enrollments.languageClass',fn($q)=>$q->where('teacher_user_id',$user->id));
            $leads->where('consultant_user_id',$user->id); $classes->where('teacher_user_id',$user->id);
            $activities->where('user_id',$user->id); $imports->where('imported_by',$user->id);
            $charges->whereHas('student.enrollments.languageClass',fn($q)=>$q->where('teacher_user_id',$user->id));
            $receipts->whereHas('charge.student.enrollments.languageClass',fn($q)=>$q->where('teacher_user_id',$user->id));
            $expenses->where('personnel_id',$user->personnel_id?:0);
        }
        $periodLeads=(clone $leads)->whereBetween('created_at',[$start,$end]);
        $periodStudents=(clone $students)->whereBetween('registered_at',[$start,$end]);
        $registeredLeads=(clone $periodLeads)->where('status','registered')->count();
        $financial=['receivable'=>(float)(clone $charges)->sum('payable_amount'),'collected'=>(float)(clone $receipts)->sum('amount'),'outstanding'=>(float)(clone $charges)->selectRaw('COALESCE(SUM(GREATEST(payable_amount-paid_amount-credit_amount,0)),0) total')->value('total'),'expense'=>(float)(clone $expenses)->sum('payment_amount')];
        $financial['net']=$financial['collected']-$financial['expense'];
        $workTasks=WorkTask::query()->whereBetween('created_at',[$start,$end]);
        if(!$canViewAll){
            $workTasks->where(fn($query)=>$query->where('created_by_id',$user->id)
                ->orWhereHas('assignees',fn($assignees)=>$assignees->where('user_id',$user->id)));
        }
        $workAssignments=WorkTaskAssignee::query()->whereIn('work_task_id',(clone $workTasks)->select('id'));
        if(!$canViewAll)$workAssignments->where('user_id',$user->id);
        $workTaskStats=[
            'total'=>(clone $workTasks)->count(),
            'assignments'=>(clone $workAssignments)->count(),
            'acknowledged'=>(clone $workAssignments)->whereNotNull('acknowledged_at')->count(),
            'awaiting_acknowledgement'=>(clone $workAssignments)->whereNull('acknowledged_at')->whereNull('completed_at')->count(),
            'in_progress'=>(clone $workAssignments)->whereNotNull('acknowledged_at')->whereNull('completed_at')->count(),
            'completed'=>(clone $workAssignments)->whereNotNull('completed_at')->count(),
            'overdue'=>(clone $workTasks)->whereNull('closed_at')->where('due_at','<',now())
                ->whereHas('assignees',fn($query)=>$query->whereNull('completed_at'))->count(),
        ];
        $taskRecipientStats=$canViewAll
            ? WorkTaskAssignee::query()
                ->select('user_id')
                ->selectRaw('COUNT(*) AS total_tasks')
                ->selectRaw('SUM(CASE WHEN acknowledged_at IS NOT NULL THEN 1 ELSE 0 END) AS acknowledged_tasks')
                ->selectRaw('SUM(CASE WHEN completed_at IS NOT NULL THEN 1 ELSE 0 END) AS completed_tasks')
                ->selectRaw('SUM(CASE WHEN acknowledged_at IS NULL THEN 1 ELSE 0 END) AS unacknowledged_tasks')
                ->whereIn('work_task_id',(clone $workTasks)->select('id'))
                ->with('user:id,name,email')
                ->groupBy('user_id')
                ->orderByDesc('total_tasks')
                ->get()
            : collect();

        return view('system-dashboard',[
            'year'=>$year,'periodType'=>$request->input('period_type','year'),'period'=>$period,'start'=>$start,'end'=>$end,'canViewAll'=>$canViewAll,'currentUser'=>$user,
            'kpiTotals'=>$report['totals'],'activeUsers'=>$canViewAll?User::where('active',true)->count():1,'activePersonnel'=>$canViewAll?Personnel::where('active',true)->count():($user->personnel_id?1:0),
            'students'=>(clone $periodStudents)->count(),'leads'=>(clone $periodLeads)->count(),'consulted'=>(clone $periodLeads)->where(fn($q)=>$q->whereNotNull('consultation')->orWhere('status','!=','new'))->count(),'registeredLeads'=>$registeredLeads,
            'conversionRate'=>$periodLeads->count()?round($registeredLeads/$periodLeads->count()*100,1):0,
            'activeClasses'=>(clone $classes)->where('status','active')->count(),'upcomingClasses'=>(clone $classes)->whereIn('status',['recruiting','upcoming'])->count(),
            'leadStatuses'=>(clone $periodLeads)->selectRaw('status,COUNT(*) total')->groupBy('status')->pluck('total','status'),
            'studentStatuses'=>(clone $periodStudents)->selectRaw('status,COUNT(*) total')->groupBy('status')->pluck('total','status'),
            'classStatuses'=>(clone $classes)->selectRaw('status,COUNT(*) total')->groupBy('status')->pluck('total','status'),
            'tuitionStatuses'=>(clone $charges)->selectRaw('status,COUNT(*) total')->groupBy('status')->pluck('total','status'),
            'financial'=>$financial,'workTaskStats'=>$workTaskStats,'taskRecipientStats'=>$taskRecipientStats,
            'recentActivities'=>$canViewAll?$activities->limit(8)->get():collect(),'recentImports'=>$imports->limit(6)->get(),
        ]);
    }

    public function export(Request $request)
    {
        $data=$this->index($request)->getData(); $period=$data['period'];
        $rows=[['Hệ thống','Tài khoản hoạt động',$data['activeUsers'],'tài khoản',$period],['Hệ thống','Nhân sự hoạt động',$data['activePersonnel'],'nhân sự',$period],['KPI','Chỉ tiêu năm',$data['kpiTotals']['target_quantity'],'KPI','Năm '.$data['year']],['KPI','Đã thực hiện',$data['kpiTotals']['actual_quantity'],'KPI','Năm '.$data['year']],['Tuyển sinh','Khách hàng mới',$data['leads'],'lượt',$period],['Tuyển sinh','Đã tư vấn',$data['consulted'],'lượt',$period],['Tuyển sinh','Đã đăng ký',$data['registeredLeads'],'lượt',$period],['Tuyển sinh','Tỷ lệ chuyển đổi',$data['conversionRate'],'%',$period],['Học viên','Học viên mới',$data['students'],'học viên',$period],['Lớp học','Đang hoạt động',$data['activeClasses'],'lớp',$period],['Lớp học','Đang/sắp tuyển',$data['upcomingClasses'],'lớp',$period],['Tài chính','Phải thu',$data['financial']['receivable'],'đ',$period],['Tài chính','Đã thu',$data['financial']['collected'],'đ',$period],['Tài chính','Còn nợ',$data['financial']['outstanding'],'đ',$period],['Tài chính','Đã chi',$data['financial']['expense'],'đ',$period],['Tài chính','Thu ròng',$data['financial']['net'],'đ',$period]];
        array_splice($rows,2,0,[
            ['Công việc','Tổng công việc đã giao',$data['workTaskStats']['total'],'công việc',$period],
            ['Công việc','Lượt phân công',$data['workTaskStats']['assignments'],'lượt',$period],
            ['Công việc','Chưa xác nhận nhận việc',$data['workTaskStats']['awaiting_acknowledgement'],'lượt',$period],
            ['Công việc','Đã nhận việc, đang thực hiện',$data['workTaskStats']['in_progress'],'lượt',$period],
            ['Công việc','Đã nhận việc',$data['workTaskStats']['acknowledged'],'lượt',$period],
            ['Công việc','Đã hoàn thành',$data['workTaskStats']['completed'],'lượt',$period],
        ]);
        foreach($data['leadStatuses'] as $status=>$total)$rows[]=['Trạng thái tư vấn',$status,$total,'khách hàng',$period];
        foreach($data['studentStatuses'] as $status=>$total)$rows[]=['Trạng thái học viên',$status,$total,'học viên',$period];
        foreach($data['classStatuses'] as $status=>$total)$rows[]=['Trạng thái lớp học',$status,$total,'lớp',$period];
        foreach($data['tuitionStatuses'] as $status=>$total)$rows[]=['Trạng thái học phí',$status,$total,'khoản thu',$period];
        return ExcelExporter::download('tong-quan-toan-he-thong-'.date('Ymd-His').'.xlsx',['Nhóm','Chỉ số','Giá trị','Đơn vị','Kỳ lọc'],$rows);
    }

    private function period(Request $request): array
    {
        $year=max(2020,min(2100,$request->integer('year',now()->year))); $type=$request->input('period_type','year');
        if($type==='range'&&$request->filled(['from_date','to_date'])){$start=Carbon::parse($request->from_date)->startOfDay();$last=Carbon::parse($request->to_date)->startOfDay();if($start->gt($last))[$start,$last]=[$last,$start];return[$start,$last->copy()->endOfDay(),'Từ '.$start->format('d/m/Y').' đến '.$last->format('d/m/Y')];}
        if($type==='week'){$week=max(1,min(53,$request->integer('week',now()->isoWeek())));$start=Carbon::now()->setISODate($year,$week)->startOfWeek();return[$start,$start->copy()->endOfWeek(),'Tuần '.$week.'/'.$year];}
        if($type==='month'){$month=max(1,min(12,$request->integer('month',now()->month)));$start=Carbon::create($year,$month)->startOfMonth();return[$start,$start->copy()->endOfMonth(),'Tháng '.$month.'/'.$year];}
        if($type==='quarter'){$quarter=max(1,min(4,$request->integer('quarter',now()->quarter)));$start=Carbon::create($year,($quarter-1)*3+1)->startOfMonth();return[$start,$start->copy()->addMonths(3)->subSecond(),'Quý '.$quarter.'/'.$year];}
        $start=Carbon::create($year)->startOfYear();return[$start,$start->copy()->endOfYear(),'Năm '.$year];
    }
}
