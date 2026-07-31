<?php

namespace App\Http\Controllers;

use App\Models\LanguageClass;
use App\Models\LanguageLead;
use App\Models\LanguageProgram;
use App\Models\LanguageStudent;
use App\Models\LanguageTuitionCharge;
use App\Models\LanguageTuitionPayment;
use App\Models\ExcessPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Support\ExcelExporter;

class LanguageDashboardController extends Controller
{
    public function export(Request $request)
    {
        $data=$this->index($request)->getData();
        $period=$data['selectedPeriod'];
        $rows=collect([
            ['Học phí thu - chi','Phải thu',$data['financial']['receivable'],'đ',$period],
            ['Học phí thu - chi','Đã thu',$data['financial']['collected'],'đ',$period],
            ['Học phí thu - chi','Còn phải thu',$data['financial']['outstanding'],'đ',$period],
            ['Học phí thu - chi','Đã chi',$data['financial']['expense'],'đ',$period],
            ['Học phí thu - chi','Thu ròng',$data['financial']['net'],'đ',$period],
            ['Tư vấn tuyển sinh','Tổng khách hàng',$data['totalLeads'],'lượt',$period],
            ['Tư vấn tuyển sinh','Tỷ lệ chuyển đổi',$data['conversionRate'],'%',$period],
            ['Học viên','Tổng học viên',$data['totalStudents'],'học viên',$period],
            ['Lớp học','Lớp đang hoạt động',$data['activeClasses'],'lớp',$period],
            ['Lớp học','Lớp đang tuyển/sắp mở',$data['recruitingClasses'],'lớp',$period],
            ['Chương trình','Chương trình hoạt động',$data['programCount'],'chương trình',$period],
        ]);
        foreach ($data['leadStatusLabels'] as $key=>$label) $rows->push(['Trạng thái tư vấn',$label,(int)($data['leadStatuses'][$key]??0),'khách hàng',$period]);
        foreach ($data['studentStatusLabels'] as $key=>$label) $rows->push(['Trạng thái học viên',$label,(int)($data['studentStatuses'][$key]??0),'học viên',$period]);
        foreach ($data['monthly'] as $month) {
            $rows->push(['Theo tháng','Khách hàng tháng '.$month['month'],$month['leads'],'lượt','Năm '.$data['year']]);
            $rows->push(['Theo tháng','Học viên tháng '.$month['month'],$month['students'],'lượt','Năm '.$data['year']]);
        }
        return ExcelExporter::download('tong-quan-trung-tam-'.date('Ymd-His').'.xlsx',['Nhóm tổng quan','Chỉ số','Giá trị','Đơn vị','Kỳ lọc'],$rows);
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || collect(['language_leads','language_students','language_programs','language_classes'])->contains(fn ($module) => $user->allowed($module)), 403);
        $canViewAll = $user->isAdmin() || $user->allowed('language_dashboard_all');

        $year = max(2020, min(2100, $request->integer('year', now()->year)));
        $periodType=$request->string('period_type')->toString();
        $month = $periodType==='month' ? max(1, min(12, $request->integer('month',now()->month))) : null;
        $quarter = $periodType==='quarter' ? max(1, min(4, $request->integer('quarter',now()->quarter))) : null;
        $week = $periodType==='week' ? max(1, min(53, $request->integer('week',now()->isoWeek()))) : null;
        $fromDate=$periodType==='range'&&$request->filled('from_date')?Carbon::parse($request->input('from_date'))->startOfDay():null;
        $toDate=$periodType==='range'&&$request->filled('to_date')?Carbon::parse($request->input('to_date'))->startOfDay():null;
        if (! in_array($periodType,['range','week','month','quarter','year'],true)) {
            $periodType=$request->filled('month')?'month':($request->filled('quarter')?'quarter':'year');
            $month=$periodType==='month'?max(1,min(12,$request->integer('month'))):null;
            $quarter=$periodType==='quarter'?max(1,min(4,$request->integer('quarter'))):null;
        }
        if ($periodType==='range' && $fromDate && $toDate) {
            if ($fromDate->gt($toDate)) [$fromDate,$toDate]=[$toDate,$fromDate];
            $filterStart=$fromDate->copy(); $filterEnd=$toDate->copy()->addDay();
            $selectedPeriod='từ '.$fromDate->format('d/m/Y').' đến '.$toDate->format('d/m/Y');
        } elseif ($week) {
            $filterStart=Carbon::now()->setISODate($year,$week)->startOfWeek(); $filterEnd=$filterStart->copy()->addWeek();
            $selectedPeriod='tuần '.$week.'/'.$year;
        } elseif ($month) {
            $filterStart = Carbon::create($year, $month)->startOfMonth(); $filterEnd = $filterStart->copy()->addMonth();
            $selectedPeriod = 'tháng '.$month.'/'.$year;
        } elseif ($quarter) {
            $filterStart = Carbon::create($year, ($quarter - 1) * 3 + 1)->startOfMonth(); $filterEnd = $filterStart->copy()->addMonths(3);
            $selectedPeriod = 'quý '.$quarter.'/'.$year;
        } else {
            $filterStart = Carbon::create($year, 1, 1)->startOfYear(); $filterEnd = $filterStart->copy()->addYear();
            $selectedPeriod = 'năm '.$year;
        }

        $leads = LanguageLead::query();
        $students = LanguageStudent::query();
        $classes = LanguageClass::query();
        if (! $canViewAll) {
            $leads->where('consultant_user_id', $user->id);
            $students->whereHas('enrollments.languageClass', fn ($query) => $query->where('teacher_user_id', $user->id));
            $classes->where('teacher_user_id', $user->id);
        }

        $leads->where('created_at', '>=', $filterStart)->where('created_at', '<', $filterEnd);
        $students->where('registered_at', '>=', $filterStart)->where('registered_at', '<', $filterEnd);
        $classes->where(fn ($query) => $query->whereNull('start_date')->orWhere(fn ($dated) => $dated->where('start_date','>=',$filterStart)->where('start_date','<',$filterEnd)));

        $today = now()->startOfDay();
        $tomorrow = $today->copy()->addDay();
        $monthStart = now()->startOfMonth();
        $yearStart = Carbon::create($year, 1, 1)->startOfYear();
        $yearEnd = $yearStart->copy()->addYear();

        $periodCount = function ($query, string $column, Carbon $from, Carbon $to): int {
            return (clone $query)->where($column, '>=', $from)->where($column, '<', $to)->count();
        };

        $consulted = (clone $leads)->where(function ($query) {
            $query->whereNotNull('consultation')->orWhereNot('status', 'new');
        });
        $registrations = clone $students;

        $consultations = [
            'today' => $periodCount($consulted, 'updated_at', $today, $tomorrow),
            'month' => $periodCount($consulted, 'updated_at', $monthStart, $monthStart->copy()->addMonth()),
            'year' => $periodCount($consulted, 'updated_at', $yearStart, $yearEnd),
        ];
        $newLeads = [
            'today' => $periodCount($leads, 'created_at', $today, $tomorrow),
            'month' => $periodCount($leads, 'created_at', $monthStart, $monthStart->copy()->addMonth()),
            'year' => $periodCount($leads, 'created_at', $yearStart, $yearEnd),
        ];
        $registered = [
            'today' => $periodCount($registrations, 'registered_at', $today, $tomorrow),
            'month' => $periodCount($registrations, 'registered_at', $monthStart, $monthStart->copy()->addMonth()),
            'year' => $periodCount($registrations, 'registered_at', $yearStart, $yearEnd),
        ];

        $leadStatusLabels = [
            'new'=>'Mới tiếp nhận', 'contacted'=>'Đã liên hệ', 'consulting'=>'Đang tư vấn',
            'placement_test'=>'Hẹn kiểm tra', 'waiting'=>'Chờ phản hồi', 'registered'=>'Đã đăng ký',
            'not_interested'=>'Không quan tâm', 'follow_up'=>'Chăm sóc lại',
        ];
        $studentStatusLabels = [
            'new'=>'Mới đăng ký', 'placement_test'=>'Chờ kiểm tra', 'waiting_class'=>'Chờ xếp lớp',
            'studying'=>'Đang học', 'paused'=>'Tạm nghỉ', 'reserved'=>'Bảo lưu',
            'completed'=>'Hoàn thành', 'dropped'=>'Thôi học',
        ];
        $leadStatuses = (clone $leads)->selectRaw('status, COUNT(*) total')->groupBy('status')->pluck('total', 'status');
        $studentStatuses = (clone $students)->selectRaw('status, COUNT(*) total')->groupBy('status')->pluck('total', 'status');

        $monthly = collect(range(1, 12))->map(function (int $month) use ($year, $leads, $students) {
            $from = Carbon::create($year, $month)->startOfMonth();
            $to = $from->copy()->addMonth();
            return [
                'month' => $month,
                'leads' => (clone $leads)->where('created_at', '>=', $from)->where('created_at', '<', $to)->count(),
                'students' => (clone $students)->where('registered_at', '>=', $from)->where('registered_at', '<', $to)->count(),
            ];
        });

        $totalLeads = (clone $leads)->count();
        $totalStudents = (clone $students)->count();
        $converted = (clone $leads)->where('status', 'registered')->count();

        $charges=LanguageTuitionCharge::where('created_at','>=',$filterStart)->where('created_at','<',$filterEnd);
        $tuitionPayments=LanguageTuitionPayment::where('paid_at','>=',$filterStart)->where('paid_at','<',$filterEnd);
        $expenses=ExcessPayment::where('status','paid')->where('paid_at','>=',$filterStart)->where('paid_at','<',$filterEnd);
        if (! $canViewAll) {
            $charges->whereHas('student.enrollments.languageClass',fn($query)=>$query->where('teacher_user_id',$user->id));
            $tuitionPayments->whereHas('charge.student.enrollments.languageClass',fn($query)=>$query->where('teacher_user_id',$user->id));
            $expenses->where('personnel_id',$user->personnel_id?:0);
        }
        $financial=[
            'receivable'=>(float)(clone $charges)->sum('payable_amount'),
            'collected'=>(float)(clone $tuitionPayments)->sum('amount'),
            'outstanding'=>(float)(clone $charges)->selectRaw('COALESCE(SUM(GREATEST(payable_amount-paid_amount-credit_amount,0)),0) total')->value('total'),
            'expense'=>(float)(clone $expenses)->sum('payment_amount'),
        ];
        $financial['net']=$financial['collected']-$financial['expense'];

        return view('language.dashboard', compact(
            'year', 'month', 'quarter', 'week', 'fromDate', 'toDate', 'periodType', 'selectedPeriod', 'canViewAll', 'consultations', 'newLeads', 'registered', 'leadStatusLabels', 'studentStatusLabels',
            'leadStatuses', 'studentStatuses', 'monthly', 'totalLeads', 'totalStudents'
        ) + [
            'conversionRate' => $totalLeads > 0 ? round($converted / $totalLeads * 100, 1) : 0,
            'activeClasses' => (clone $classes)->where('status', 'active')->count(),
            'recruitingClasses' => (clone $classes)->whereIn('status', ['recruiting','upcoming'])->count(),
            'programCount' => LanguageProgram::where('active', true)->count(),
            'recentLeads' => (clone $leads)->with(['program','consultant'])->latest()->limit(6)->get(),
            'financial'=>$financial,
        ]);
    }
}
