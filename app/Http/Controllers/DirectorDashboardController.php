<?php

namespace App\Http\Controllers;

use App\Models\ExcessPayment;
use App\Models\AdministrativeWeeklyPeriod;
use App\Models\AdministrativeWeeklyReport;
use App\Models\LanguageClass;
use App\Models\LanguageLead;
use App\Models\LanguageMonthlyTargetRecord;
use App\Models\LanguageProgram;
use App\Models\LanguageStudent;
use App\Models\LanguageTuitionCharge;
use App\Models\LanguageTuitionPayment;
use App\Models\Personnel;
use App\Models\User;
use App\Models\WorkTask;
use App\Models\WorkTaskAssignee;
use App\Support\SystemHealthMonitor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DirectorDashboardController extends Controller
{
    public function __construct(private readonly SystemHealthMonitor $healthMonitor) {}

    public function index(Request $request): View
    {
        abort_unless(
            $request->user()->isAdmin() || $request->user()->isDirector(),
            403,
            'Trang giám sát này chỉ dành cho Giám đốc.'
        );

        [$start, $end, $period] = $this->period($request);
        $base = WorkTask::query()->whereBetween('due_at', [$start, $end]);

        $taskStats = [
            'total' => (clone $base)->count(),
            'open' => (clone $base)->whereNull('closed_at')
                ->whereHas('assignees', fn ($query) => $query->whereNull('completed_at'))
                ->count(),
            'completed' => (clone $base)->whereDoesntHave(
                'assignees',
                fn ($query) => $query->whereNull('completed_at')
            )->count(),
            'overdue' => (clone $base)->whereNull('closed_at')->where('due_at', '<', now())
                ->whereHas('assignees', fn ($query) => $query->whereNull('completed_at'))
                ->count(),
            'closed' => (clone $base)->whereNotNull('closed_at')->count(),
        ];

        $memberStats = WorkTaskAssignee::query()
            ->join('work_tasks', 'work_tasks.id', '=', 'work_task_assignees.work_task_id')
            ->whereBetween('work_tasks.due_at', [$start, $end])
            ->select('work_task_assignees.user_id')
            ->selectRaw('COUNT(*) AS total_tasks')
            ->selectRaw('SUM(CASE WHEN work_task_assignees.completed_at IS NULL AND work_task_assignees.acknowledged_at IS NOT NULL THEN 1 ELSE 0 END) AS doing_tasks')
            ->selectRaw('SUM(CASE WHEN work_task_assignees.completed_at IS NOT NULL THEN 1 ELSE 0 END) AS completed_tasks')
            ->selectRaw('SUM(CASE WHEN work_task_assignees.acknowledged_at IS NULL THEN 1 ELSE 0 END) AS unacknowledged_tasks')
            ->selectRaw('SUM(CASE WHEN work_task_assignees.completed_at IS NULL AND work_tasks.due_at < ? THEN 1 ELSE 0 END) AS overdue_tasks', [now()])
            ->with('user:id,name,email,role_id')
            ->groupBy('work_task_assignees.user_id')
            ->orderByDesc('doing_tasks')
            ->orderByDesc('overdue_tasks')
            ->get();

        $periodLeads = LanguageLead::query()->whereBetween('created_at', [$start, $end]);
        $periodStudents = LanguageStudent::query()->whereBetween('registered_at', [$start, $end]);
        $convertedLeads = (clone $periodLeads)->where(fn ($query) => $query
            ->where('status', 'registered')
            ->orWhereNotNull('converted_student_id'))
            ->count();
        $leadTotal = (clone $periodLeads)->count();

        $personnelStats = [
            'active' => Personnel::query()->where('active', true)->count(),
            'leaders' => Personnel::query()->where('active', true)->where('type', 'leader')->count(),
            'teachers' => Personnel::query()->where('active', true)->where('type', 'teacher')->count(),
            'teaching_accounts' => User::query()->where('active', true)->instructors()->count(),
            'employees' => Personnel::query()->where('active', true)->where('type', 'employee')->count(),
            'collaborators' => Personnel::query()->where('active', true)->where('type', 'collaborator')->count(),
            'accounts' => User::query()->where('active', true)->count(),
        ];

        $weeklyPeriods = AdministrativeWeeklyPeriod::query()
            ->with('assignedUsers:id')
            ->whereDate('week_start', '<=', today())
            ->orderBy('week_start')
            ->get();
        $weeklyReportsByPeriod = AdministrativeWeeklyReport::query()
            ->whereIn('period_id', $weeklyPeriods->pluck('id'))
            ->where('status', 'submitted')
            ->get(['period_id', 'user_id'])
            ->groupBy('period_id');
        $missingByWeek = $weeklyPeriods->mapWithKeys(function (AdministrativeWeeklyPeriod $weeklyPeriod) use ($weeklyReportsByPeriod) {
            $submittedUserIds = $weeklyReportsByPeriod->get($weeklyPeriod->id, collect())->pluck('user_id');

            return [$weeklyPeriod->id => $weeklyPeriod->assignedUsers->pluck('id')->diff($submittedUserIds)->values()];
        });
        $weeklyReportStats = [
            'missing_submissions' => $missingByWeek->sum(fn ($userIds) => $userIds->count()),
            'missing_people' => $missingByWeek->flatten()->unique()->count(),
            'incomplete_weeks' => $missingByWeek->filter(fn ($userIds) => $userIds->isNotEmpty())->count(),
            'tracked_weeks' => $weeklyPeriods->count(),
        ];

        $recruitmentStats = [
            'leads' => $leadTotal,
            'consulted' => (clone $periodLeads)->where(fn ($query) => $query
                ->whereNotNull('consultation')
                ->orWhere('status', '!=', 'new'))
                ->count(),
            'registered' => $convertedLeads,
            'conversion_rate' => $leadTotal > 0 ? round($convertedLeads / $leadTotal * 100, 1) : 0,
        ];

        $trainingStats = [
            'students' => LanguageStudent::query()->count(),
            'new_students' => (clone $periodStudents)->count(),
            'studying_students' => LanguageStudent::query()->where('status', 'studying')->count(),
            'active_classes' => LanguageClass::query()->where('status', 'active')->count(),
            'upcoming_classes' => LanguageClass::query()->whereIn('status', ['planned', 'recruiting', 'upcoming'])->count(),
            'programs' => LanguageProgram::query()->where('active', true)->count(),
        ];

        $periodCharges = LanguageTuitionCharge::query()->whereBetween('created_at', [$start, $end]);
        $periodReceipts = LanguageTuitionPayment::query()->whereBetween('paid_at', [$start, $end]);
        $periodExpenses = ExcessPayment::query()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end]);
        $financialStats = [
            'receivable' => (float) (clone $periodCharges)->sum('payable_amount'),
            'collected' => (float) (clone $periodReceipts)->sum('amount'),
            'outstanding' => (float) LanguageTuitionCharge::query()
                ->selectRaw('COALESCE(SUM(GREATEST(payable_amount - paid_amount - credit_amount, 0)), 0) AS total')
                ->value('total'),
            'expense' => (float) (clone $periodExpenses)->sum('payment_amount'),
        ];
        $financialStats['net'] = $financialStats['collected'] - $financialStats['expense'];

        $healthChecks = $this->healthMonitor->checks();
        $systemHealth = $this->healthMonitor->summary($healthChecks);
        $operationalAlerts = [
            [
                'label' => 'Lớp chưa phân công giáo viên',
                'count' => LanguageClass::query()->whereIn('status', ['recruiting', 'upcoming', 'active'])->whereNull('teacher_user_id')->count(),
                'icon' => 'bi-person-exclamation', 'tone' => 'warning', 'route' => 'language-classes.index',
            ],
            [
                'label' => 'Lớp chờ giáo vụ đóng',
                'count' => LanguageClass::query()->whereNotNull('completion_requested_at')->whereNotIn('status', ['completed', 'cancelled'])->count(),
                'icon' => 'bi-hourglass-split', 'tone' => 'warning', 'route' => 'language-classes.index',
            ],
            [
                'label' => 'Phiếu thu chờ xác nhận',
                'count' => LanguageTuitionPayment::query()->where('receipt_status', 'pending')->count(),
                'icon' => 'bi-receipt-cutoff', 'tone' => 'danger', 'route' => 'language-tuition.index',
            ],
            [
                'label' => 'Khoản học phí còn nợ',
                'count' => LanguageTuitionCharge::query()->whereRaw('(payable_amount - paid_amount - credit_amount) > 0')->count(),
                'icon' => 'bi-cash-coin', 'tone' => 'danger', 'route' => 'language-tuition.index',
            ],
        ];

        $periodTargetRecords = LanguageMonthlyTargetRecord::query()
            ->whereHas('payment', fn ($query) => $query->whereBetween('paid_at', [$start, $end]));
        $targetStats = [
            'records' => (clone $periodTargetRecords)->count(),
            'quantity' => (float) (clone $periodTargetRecords)->sum('quantity'),
            'revenue' => (float) (clone $periodTargetRecords)->sum('revenue'),
            'students' => (clone $periodTargetRecords)->distinct()->count('language_student_id'),
        ];

        $tasks = clone $base;
        if ($request->filled('q')) {
            $keyword = $request->string('q')->toString();
            $tasks->where(fn ($query) => $query
                ->where('title', 'like', '%'.$keyword.'%')
                ->orWhereHas('creator', fn ($creator) => $creator->where('name', 'like', '%'.$keyword.'%'))
                ->orWhereHas('assignees.user', fn ($user) => $user->where('name', 'like', '%'.$keyword.'%')));
        }
        match ($request->string('status')->toString()) {
            'doing' => $tasks->whereNull('closed_at')
                ->whereHas('assignees', fn ($query) => $query->whereNull('completed_at')),
            'completed' => $tasks->whereDoesntHave('assignees', fn ($query) => $query->whereNull('completed_at')),
            'overdue' => $tasks->whereNull('closed_at')->where('due_at', '<', now())
                ->whereHas('assignees', fn ($query) => $query->whereNull('completed_at')),
            'closed' => $tasks->whereNotNull('closed_at'),
            default => null,
        };

        return view('director.dashboard', [
            'start' => $start,
            'end' => $end,
            'period' => $period,
            'taskStats' => $taskStats,
            'personnelStats' => $personnelStats,
            'weeklyReportStats' => $weeklyReportStats,
            'recruitmentStats' => $recruitmentStats,
            'trainingStats' => $trainingStats,
            'financialStats' => $financialStats,
            'targetStats' => $targetStats,
            'systemHealth' => $systemHealth,
            'healthChecks' => $healthChecks,
            'operationalAlerts' => $operationalAlerts,
            'memberStats' => $memberStats,
            'tasks' => $tasks->with(['creator', 'assignees.user'])->latest('due_at')
                ->paginate(15)
                ->withQueryString(),
            'deputyDirectors' => User::query()
                ->with('personnel')
                ->whereHas('role', fn ($role) => $role->where('code', 'deputy_director'))
                ->orderBy('name')
                ->get(),
        ]);
    }

    private function period(Request $request): array
    {
        if ($request->filled(['from_date', 'to_date'])) {
            $start = Carbon::parse($request->input('from_date'))->startOfDay();
            $last = Carbon::parse($request->input('to_date'))->startOfDay();
            if ($start->gt($last)) [$start, $last] = [$last, $start];

            return [
                $start,
                $last->copy()->endOfDay(),
                'Từ '.$start->format('d/m/Y').' đến '.$last->format('d/m/Y'),
            ];
        }

        $year = max(2020, min(2100, $request->integer('year', now()->year)));
        $month = max(1, min(12, $request->integer('month', now()->month)));
        $start = Carbon::create($year, $month)->startOfMonth();

        return [$start, $start->copy()->endOfMonth(), 'Tháng '.$month.'/'.$year];
    }
}
