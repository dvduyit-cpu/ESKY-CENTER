<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\KpiPlan;
use App\Models\KpiRecord;
use App\Models\KpiTarget;
use App\Models\KpiTeachingReport;
use App\Models\LanguageLead;
use App\Models\Personnel;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\KpiCalculator;
use App\Support\Period;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly KpiCalculator $calculator) {}

    public function index(Request $request): View
    {
        [$filters, $report] = $this->build($request);
        $rows = $report['rows'];
        if ($request->filled('status')) {
            $rows = $rows->where('status', $request->string('status')->toString())->values();
        }
        $totals = $this->totals($rows);
        $rows = $rows->values();
        $recruitmentOverview = $this->recruitmentOverview($request, $filters);
        $teachingLoadOverview = $this->teachingLoadOverview($request, $filters);

        return view('reports.index', [
            'filters' => $filters,
            'rows' => $rows,
            'totals' => $totals,
            'personnels' => Personnel::where('active', true)->where('type', '!=', 'collaborator')->orderBy('name')->get(),
            'courses' => Course::where('active', true)->orderBy('name')->get(),
            'periodLabel' => Period::label($filters['period_type'], $filters['period_value'], $filters['year']),
            'recruitmentOverview' => $recruitmentOverview,
            'teachingLoadOverview' => $teachingLoadOverview,
        ]);
    }

    public function recruitmentKpi(Request $request): View
    {
        $filters = $this->periodFilters($request) + [
            'consultant_user_id' => $request->integer('consultant_user_id', 0),
        ];

        $user = $request->user()->load('personnel');
        $canViewAll = $user->isLeader();
        if (! $canViewAll) {
            $filters['consultant_user_id'] = $user->id;
        }

        [$start, $end] = $this->dateRangeForFilters($filters);
        $leadQuery = LanguageLead::query()
            ->with(['consultant:id,name'])
            ->whereBetween('created_at', [$start, $end]);

        if ($filters['consultant_user_id'] > 0) {
            $leadQuery->where('consultant_user_id', $filters['consultant_user_id']);
        }

        $leads = $leadQuery->get();
        $rows = $this->recruitmentRows($leads)->values();
        $totals = [
            'lead_count' => $leads->count(),
            'consulted_count' => $leads->filter(fn ($lead) => $this->leadWasConsulted($lead))->count(),
            'registered_count' => $leads->filter(fn ($lead) => $this->leadWasRegistered($lead))->count(),
            'unassigned_count' => $leads->whereNull('consultant_user_id')->count(),
            'conversion_rate' => $leads->count() > 0
                ? round($leads->filter(fn ($lead) => $this->leadWasRegistered($lead))->count() / $leads->count() * 100, 1)
                : 0.0,
        ];

        $statusCounts = $leads->groupBy('status')->map->count();
        $consultants = User::query()
            ->where('active', true)
            ->where(function ($query) {
                $query->where('is_registrar', true)
                    ->orWhereHas('personnel', fn ($personnel) => $personnel->where('is_consultant', true));
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('reports.recruitment-kpi', [
            'filters' => $filters,
            'periodLabel' => Period::label($filters['period_type'], $filters['period_value'], $filters['year']),
            'consultants' => $consultants,
            'rows' => $rows,
            'totals' => $totals,
            'statusCounts' => $statusCounts,
            'canViewAll' => $canViewAll,
        ]);
    }

    public function teachingLoadKpi(Request $request): View
    {
        $year = $request->integer('year', now()->year);
        $selectedMonth = $request->filled('report_month')
            ? max(1, min(12, $request->integer('report_month')))
            : null;
        $selectedPersonnelId = $request->integer('personnel_id', 0);

        $user = $request->user()->load('personnel');
        $canViewAll = $user->isLeader();
        if (! $canViewAll) {
            $selectedPersonnelId = $user->personnel_id ?: -1;
        }

        $availableYears = KpiPlan::query()
            ->whereHas('targets', fn ($query) => $query
                ->where('period_type', 'year')
                ->where('assigned_teaching_load', '>', 0)
            )
            ->orderByDesc('year')
            ->pluck('year');

        if ($availableYears->isEmpty()) {
            $availableYears = collect([$year]);
        } elseif (! $availableYears->contains($year)) {
            $availableYears = $availableYears->prepend($year)->unique()->sortDesc()->values();
        }

        $personnels = Personnel::query()
            ->where('type', '!=', 'collaborator')
            ->where(fn ($query) => $query
                ->where('active', true)
                ->orWhereHas('targets', fn ($targets) => $targets
                    ->where('assigned_teaching_load', '>', 0)
                    ->whereHas('plan', fn ($planQuery) => $planQuery->where('year', $year))
                )
                ->orWhereHas('teachingReports', fn ($reports) => $reports->where('report_year', $year))
            )
            ->orderBy('name')
            ->get();

        $plan = KpiPlan::query()->where('year', $year)->first();
        $rows = collect();
        $totals = [
            'teacher_count' => 0,
            'assigned_teaching_load' => 0.0,
            'reported_teaching_load' => 0.0,
            'remaining_teaching_load' => 0.0,
            'exceeded_teaching_load' => 0.0,
            'period_teaching_load' => 0.0,
        ];

        if ($plan) {
            $targets = KpiTarget::query()
                ->with('personnel')
                ->where('plan_id', $plan->id)
                ->where('period_type', 'year')
                ->where('assigned_teaching_load', '>', 0)
                ->when($selectedPersonnelId > 0, fn ($query) => $query->where('personnel_id', $selectedPersonnelId))
                ->get()
                ->keyBy('personnel_id');

            $reportsByPersonnel = KpiTeachingReport::query()
                ->with(['personnel', 'reporter'])
                ->where('plan_id', $plan->id)
                ->where('report_year', $year)
                ->when($selectedPersonnelId > 0, fn ($query) => $query->where('personnel_id', $selectedPersonnelId))
                ->orderBy('report_month')
                ->get()
                ->groupBy('personnel_id');

            $personnelIds = $targets->keys()->merge($reportsByPersonnel->keys())->unique()->values();
            $personnelMap = Personnel::withTrashed()
                ->whereIn('id', $personnelIds)
                ->get()
                ->keyBy('id');

            $rows = $personnelIds->map(function ($personnelId) use ($personnelMap, $reportsByPersonnel, $selectedMonth, $targets) {
                $reports = $reportsByPersonnel->get($personnelId, collect())->sortBy('report_month')->values();
                $target = $targets->get($personnelId);
                $periodReport = $selectedMonth ? $reports->firstWhere('report_month', $selectedMonth) : null;
                $assigned = round((float) ($target?->assigned_teaching_load ?? 0), 2);
                $reported = round((float) $reports->sum('reported_teaching_load'), 2);
                $periodTotal = $selectedMonth
                    ? round((float) ($periodReport?->reported_teaching_load ?? 0), 2)
                    : $reported;

                return [
                    'personnel' => $personnelMap->get($personnelId),
                    'assigned_teaching_load' => $assigned,
                    'reported_teaching_load' => $reported,
                    'remaining_teaching_load' => round(max($assigned - $reported, 0), 2),
                    'exceeded_teaching_load' => round(max($reported - $assigned, 0), 2),
                    'period_teaching_load' => $periodTotal,
                    'report_count' => $reports->count(),
                    'months_reported' => $reports->pluck('report_month')->values(),
                    'latest_report' => $selectedMonth ? $periodReport : $reports->sortByDesc('updated_at')->first(),
                    'progress' => $assigned > 0 ? round(min(($reported / $assigned) * 100, 100), 1) : 0.0,
                ];
            })
                ->sortBy(fn (array $row) => mb_strtolower((string) ($row['personnel']?->name ?? '')))
                ->values();

            $totals = [
                'teacher_count' => $rows->count(),
                'assigned_teaching_load' => round((float) $rows->sum('assigned_teaching_load'), 2),
                'reported_teaching_load' => round((float) $rows->sum('reported_teaching_load'), 2),
                'remaining_teaching_load' => round((float) $rows->sum('remaining_teaching_load'), 2),
                'exceeded_teaching_load' => round((float) $rows->sum('exceeded_teaching_load'), 2),
                'period_teaching_load' => round((float) $rows->sum('period_teaching_load'), 2),
            ];
        }

        return view('reports.teaching-load-kpi', [
            'availableYears' => $availableYears,
            'filters' => [
                'year' => $year,
                'report_month' => $selectedMonth,
                'personnel_id' => $selectedPersonnelId > 0 ? $selectedPersonnelId : 0,
            ],
            'personnels' => $personnels,
            'plan' => $plan,
            'rows' => $rows,
            'totals' => $totals,
            'canViewAll' => $canViewAll,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$filters, $report] = $this->build($request);
        $rows = $report['rows'];
        if ($request->filled('status')) {
            $rows = $rows->where('status', $request->string('status')->toString())->values();
        }
        $totals = $this->totals($rows);
        $periodLabel = Period::label($filters['period_type'], $filters['period_value'], $filters['year']);

        $spreadsheet = new Spreadsheet();
        $summary = $spreadsheet->getActiveSheet();
        $summary->setTitle('TONG HOP');
        $summary->mergeCells('A1:B1');
        $summary->setCellValue('A1', 'BAO CAO CHI TIEU - '.mb_strtoupper($periodLabel));
        $summary->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setARGB('FFFFFFFF');
        $summary->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');
        $summary->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $summary->fromArray([
            ['Noi dung', 'Gia tri'],
            ['Tong chi tieu', $totals['target_quantity']],
            ['Tong thuc hien', $totals['actual_quantity']],
            ['Con lai', $totals['remaining_quantity']],
            ['Vuot chi tieu', $totals['excess_quantity']],
            ['Tong doanh thu', $totals['revenue']],
            ['Tien vuot du kien', $totals['payment_amount']],
            ['So nguoi dat/vuot', $totals['completed_people']],
            ['So nguoi chua dat', $totals['not_completed_people']],
        ], null, 'A3');
        $summary->getStyle('A3:B3')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $summary->getStyle('A3:B3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F766E');
        $summary->getStyle('B4:B7')->getNumberFormat()->setFormatCode('#,##0.00');
        $summary->getStyle('B8:B9')->getNumberFormat()->setFormatCode('#,##0');
        $summary->getColumnDimension('A')->setWidth(30);
        $summary->getColumnDimension('B')->setWidth(20);

        $details = $spreadsheet->createSheet();
        $details->setTitle('CHI TIET KPI');
        $detailHeaders = ['STT', 'NHAN SU', 'NHOM', 'KHOA HOC', 'BAT BUOC', 'CHI TIEU', 'THUC HIEN', 'CON LAI', 'VUOT', 'TY LE %', 'DOANH THU', 'MUC TRA/KPI', 'TIEN VUOT', 'TRANG THAI'];
        $details->fromArray($detailHeaders, null, 'A1');
        $rowNo = 2;
        foreach ($rows as $index => $row) {
            $details->fromArray([
                $index + 1,
                $row['personnel_name'],
                $row['personnel_type_label'],
                $row['course_name'],
                $row['is_mandatory'] ? 'Co' : 'Khong',
                $row['target_quantity'],
                $row['actual_quantity'],
                $row['remaining_quantity'],
                $row['excess_quantity'],
                $row['completion_pct'],
                $row['revenue'],
                $row['payment_rate'],
                $row['payment_amount'],
                $this->statusLabel($row['status']),
            ], null, 'A'.$rowNo++);
        }
        $details->getStyle('A1:N1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $details->getStyle('A1:N1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');
        $details->getStyle('F2:M'.max(2, $rowNo - 1))->getNumberFormat()->setFormatCode('#,##0.00');
        foreach (range('A', 'N') as $column) {
            $details->getColumnDimension($column)->setAutoSize(true);
        }
        $details->freezePane('A2');
        $details->setAutoFilter('A1:N'.max(1, $rowNo - 1));

        $recordsSheet = $spreadsheet->createSheet();
        $recordsSheet->setTitle('PHAT SINH GOC');
        $recordsSheet->fromArray(['STT', 'NGAY', 'NHAN SU', 'CONG TAC VIEN', 'KHOA HOC', 'HOC VIEN', 'LOP', 'SO LUONG', 'THUC THU', 'PHIEU THU', 'GHI CHU'], null, 'A1');
        $recordsQuery = $this->recordQuery($filters);
        if ($request->filled('status')) {
            $pairs = $rows->map(fn ($row) => [$row['personnel_id'], $row['course_id']]);
            if ($pairs->isEmpty()) {
                $recordsQuery->whereRaw('1 = 0');
            } else {
                $recordsQuery->where(function ($query) use ($pairs): void {
                    foreach ($pairs as [$personnelId, $courseId]) {
                        $query->orWhere(function ($pair) use ($personnelId, $courseId): void {
                            $pair->where('personnel_id', $personnelId);
                            if ($courseId) {
                                $pair->where('course_id', $courseId);
                            }
                        });
                    }
                });
            }
        }
        $records = $recordsQuery->with(['personnel', 'collaborator', 'course'])->orderBy('record_date')->get();
        $recordRow = 2;
        foreach ($records as $index => $record) {
            $recordsSheet->fromArray([
                $index + 1,
                $record->record_date?->format('d/m/Y'),
                $record->personnel?->name,
                $record->collaborator?->name,
                $record->course?->name,
                $record->student_name,
                $record->class_name,
                (float) $record->raw_quantity,
                (float) $record->revenue,
                $record->receipt_no,
                $record->note,
            ], null, 'A'.$recordRow++);
        }
        $recordsSheet->getStyle('A1:K1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $recordsSheet->getStyle('A1:K1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F766E');
        $recordsSheet->getStyle('H2:I'.max(2, $recordRow - 1))->getNumberFormat()->setFormatCode('#,##0.00');
        foreach (range('A', 'K') as $column) {
            $recordsSheet->getColumnDimension($column)->setAutoSize(true);
        }
        $recordsSheet->freezePane('A2');
        $recordsSheet->setAutoFilter('A1:K'.max(1, $recordRow - 1));

        $file = match ($filters['period_type']) {
            'month' => sprintf('bao-cao-chi-tieu-thang-%02d-%d.xlsx', $filters['period_value'], $filters['year']),
            'quarter' => sprintf('bao-cao-chi-tieu-quy-%02d-%d.xlsx', $filters['period_value'], $filters['year']),
            default => sprintf('bao-cao-chi-tieu-nam-%d.xlsx', $filters['year']),
        };

        ActivityLogger::log('reports', 'export', 'Xuất báo cáo '.$periodLabel);

        return response()->streamDownload(fn () => (new Xlsx($spreadsheet))->save('php://output'), $file, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function build(Request $request): array
    {
        $filters = $this->periodFilters($request) + [
            'personnel_id' => $request->integer('personnel_id', 0),
            'course_id' => $request->integer('course_id', 0),
            'personnel_type' => $request->string('personnel_type')->toString(),
        ];

        $user = $request->user();
        if (! $user->isLeader()) {
            $filters['personnel_id'] = $user->personnel_id ?: -1;
        }

        return [$filters, $this->calculator->report($filters)];
    }

    private function periodFilters(Request $request): array
    {
        $filters = [
            'year' => $request->integer('year', now()->year),
            'period_type' => $request->string('period_type', 'year')->toString(),
            'period_value' => $request->integer('period_value', 0),
        ];

        if (! in_array($filters['period_type'], ['month', 'quarter', 'year'], true)) {
            $filters['period_type'] = 'year';
        }

        if ($filters['period_type'] === 'year') {
            $filters['period_value'] = 0;
        } elseif ($filters['period_type'] === 'month' && ($filters['period_value'] < 1 || $filters['period_value'] > 12)) {
            $filters['period_value'] = now()->month;
        } elseif ($filters['period_type'] === 'quarter' && ($filters['period_value'] < 1 || $filters['period_value'] > 4)) {
            $filters['period_value'] = (int) ceil(now()->month / 3);
        }

        return $filters;
    }

    private function dateRangeForFilters(array $filters): array
    {
        $year = (int) $filters['year'];
        $periodType = (string) $filters['period_type'];
        $periodValue = (int) $filters['period_value'];

        return match ($periodType) {
            'month' => [
                now()->setDate($year, $periodValue, 1)->startOfMonth(),
                now()->setDate($year, $periodValue, 1)->endOfMonth(),
            ],
            'quarter' => [
                now()->setDate($year, (($periodValue - 1) * 3) + 1, 1)->startOfMonth(),
                now()->setDate($year, (($periodValue - 1) * 3) + 1, 1)->addMonths(2)->endOfMonth(),
            ],
            default => [
                now()->setDate($year, 1, 1)->startOfYear(),
                now()->setDate($year, 12, 1)->endOfYear(),
            ],
        };
    }

    private function recruitmentRows(Collection $leads): Collection
    {
        return $leads
            ->groupBy(fn ($lead) => $lead->consultant_user_id ?: 'unassigned')
            ->map(function (Collection $group) {
                $first = $group->first();
                $leadCount = $group->count();
                $consultedCount = $group->filter(fn ($lead) => $this->leadWasConsulted($lead))->count();
                $registeredCount = $group->filter(fn ($lead) => $this->leadWasRegistered($lead))->count();

                return [
                    'consultant_name' => $first?->consultant?->name ?: 'Chưa phân công',
                    'lead_count' => $leadCount,
                    'consulted_count' => $consultedCount,
                    'registered_count' => $registeredCount,
                    'waiting_count' => $group->whereIn('status', ['new', 'contacted', 'consulting', 'placement_test', 'waiting', 'follow_up'])->count(),
                    'not_interested_count' => $group->where('status', 'not_interested')->count(),
                    'conversion_rate' => $leadCount > 0 ? round($registeredCount / $leadCount * 100, 1) : 0.0,
                    'last_received_at' => $group->max('created_at'),
                ];
            })
            ->sortByDesc('registered_count')
            ->values();
    }

    private function leadWasConsulted($lead): bool
    {
        return filled($lead->consultation ?? null)
            || ! in_array((string) $lead->status, ['new'], true)
            || ! is_null($lead->last_consulted_at);
    }

    private function leadWasRegistered($lead): bool
    {
        return (string) $lead->status === 'registered' || ! is_null($lead->converted_student_id);
    }

    private function recordQuery(array $filters)
    {
        $query = KpiRecord::query();
        Period::applyRecordFilter($query, $filters['year'], $filters['period_type'], $filters['period_value']);
        if ($filters['personnel_id']) {
            $query->where('personnel_id', $filters['personnel_id']);
        }
        if ($filters['course_id']) {
            $query->where('course_id', $filters['course_id']);
        }
        if ($filters['personnel_type']) {
            $query->whereHas('personnel', fn ($q) => $q->where('type', $filters['personnel_type']));
        }

        return $query;
    }

    private function recruitmentOverview(Request $request, array $filters): array
    {
        $user = $request->user()->loadMissing('personnel');
        $consultantUserId = 0;
        if (! $user->isLeader()) {
            $consultantUserId = $user->id;
        }

        [$start, $end] = $this->dateRangeForFilters($filters);
        $leadQuery = LanguageLead::query()
            ->with(['consultant:id,name'])
            ->whereBetween('created_at', [$start, $end]);

        if ($consultantUserId > 0) {
            $leadQuery->where('consultant_user_id', $consultantUserId);
        }

        $leads = $leadQuery->get();
        $rows = $this->recruitmentRows($leads)->values();

        return [
            'totals' => [
                'lead_count' => $leads->count(),
                'consulted_count' => $leads->filter(fn ($lead) => $this->leadWasConsulted($lead))->count(),
                'registered_count' => $leads->filter(fn ($lead) => $this->leadWasRegistered($lead))->count(),
                'unassigned_count' => $leads->whereNull('consultant_user_id')->count(),
                'conversion_rate' => $leads->count() > 0
                    ? round($leads->filter(fn ($lead) => $this->leadWasRegistered($lead))->count() / $leads->count() * 100, 1)
                    : 0.0,
            ],
            'status_counts' => $leads->groupBy('status')->map->count(),
            'rows' => $rows->take(6)->values(),
        ];
    }

    private function teachingLoadOverview(Request $request, array $filters): array
    {
        $year = (int) $filters['year'];
        $selectedMonths = $this->monthsForFilters($filters);
        $selectedPersonnelId = (int) ($filters['personnel_id'] ?? 0);
        $user = $request->user()->loadMissing('personnel');

        if (! $user->isLeader()) {
            $selectedPersonnelId = $user->personnel_id ?: -1;
        }

        $plan = KpiPlan::query()->where('year', $year)->first();
        $rows = collect();
        $totals = [
            'teacher_count' => 0,
            'assigned_teaching_load' => 0.0,
            'reported_teaching_load' => 0.0,
            'remaining_teaching_load' => 0.0,
            'exceeded_teaching_load' => 0.0,
            'period_teaching_load' => 0.0,
        ];

        if (! $plan) {
            return [
                'plan' => null,
                'rows' => $rows,
                'totals' => $totals,
                'period_caption' => $this->teachingLoadPeriodCaption($filters),
            ];
        }

        $targets = KpiTarget::query()
            ->with('personnel')
            ->where('plan_id', $plan->id)
            ->where('period_type', 'year')
            ->where('assigned_teaching_load', '>', 0)
            ->when($selectedPersonnelId !== 0, fn ($query) => $query->where('personnel_id', $selectedPersonnelId))
            ->get()
            ->keyBy('personnel_id');

        $reportsByPersonnel = KpiTeachingReport::query()
            ->with(['personnel', 'reporter'])
            ->where('plan_id', $plan->id)
            ->where('report_year', $year)
            ->when($selectedPersonnelId !== 0, fn ($query) => $query->where('personnel_id', $selectedPersonnelId))
            ->orderBy('report_month')
            ->get()
            ->groupBy('personnel_id');

        $personnelIds = $targets->keys()->merge($reportsByPersonnel->keys())->unique()->values();
        $personnelMap = Personnel::withTrashed()
            ->whereIn('id', $personnelIds)
            ->get()
            ->keyBy('id');

        $rows = $personnelIds->map(function ($personnelId) use ($personnelMap, $reportsByPersonnel, $selectedMonths, $targets) {
            $reports = $reportsByPersonnel->get($personnelId, collect())->sortBy('report_month')->values();
            $target = $targets->get($personnelId);
            $assigned = round((float) ($target?->assigned_teaching_load ?? 0), 2);
            $reported = round((float) $reports->sum('reported_teaching_load'), 2);
            $periodReports = empty($selectedMonths)
                ? $reports
                : $reports->whereIn('report_month', $selectedMonths)->values();
            $periodTotal = round((float) $periodReports->sum('reported_teaching_load'), 2);

            return [
                'personnel' => $personnelMap->get($personnelId),
                'assigned_teaching_load' => $assigned,
                'reported_teaching_load' => $reported,
                'remaining_teaching_load' => round(max($assigned - $reported, 0), 2),
                'exceeded_teaching_load' => round(max($reported - $assigned, 0), 2),
                'period_teaching_load' => $periodTotal,
                'months_reported' => $reports->pluck('report_month')->values(),
                'latest_report' => $periodReports->sortByDesc('updated_at')->first() ?: $reports->sortByDesc('updated_at')->first(),
                'progress' => $assigned > 0 ? round(min(($reported / $assigned) * 100, 100), 1) : 0.0,
            ];
        })
            ->sortByDesc('period_teaching_load')
            ->values();

        $totals = [
            'teacher_count' => $rows->count(),
            'assigned_teaching_load' => round((float) $rows->sum('assigned_teaching_load'), 2),
            'reported_teaching_load' => round((float) $rows->sum('reported_teaching_load'), 2),
            'remaining_teaching_load' => round((float) $rows->sum('remaining_teaching_load'), 2),
            'exceeded_teaching_load' => round((float) $rows->sum('exceeded_teaching_load'), 2),
            'period_teaching_load' => round((float) $rows->sum('period_teaching_load'), 2),
        ];

        return [
            'plan' => $plan,
            'rows' => $rows->take(6)->values(),
            'totals' => $totals,
            'period_caption' => $this->teachingLoadPeriodCaption($filters),
        ];
    }

    private function monthsForFilters(array $filters): array
    {
        return match ((string) $filters['period_type']) {
            'month' => [(int) $filters['period_value']],
            'quarter' => range((((int) $filters['period_value'] - 1) * 3) + 1, ((int) $filters['period_value']) * 3),
            default => [],
        };
    }

    private function teachingLoadPeriodCaption(array $filters): string
    {
        return match ((string) $filters['period_type']) {
            'month' => 'Tiết tháng '.(int) $filters['period_value'],
            'quarter' => 'Tiết quý '.(int) $filters['period_value'],
            default => 'Tiết cả năm',
        };
    }

    private function totals(Collection $rows): array
    {
        return [
            'target_quantity' => round($rows->sum('target_quantity'), 2),
            'actual_quantity' => round($rows->sum('actual_quantity'), 2),
            'remaining_quantity' => round($rows->sum('remaining_quantity'), 2),
            'excess_quantity' => round($rows->sum('excess_quantity'), 2),
            'revenue' => round($rows->sum('revenue'), 2),
            'payment_amount' => round($rows->sum('payment_amount'), 2),
            'completed_people' => $rows->whereIn('status', ['completed', 'exceeded', 'payable'])->pluck('personnel_id')->unique()->count(),
            'not_completed_people' => $rows->where('status', 'not_completed')->pluck('personnel_id')->unique()->count(),
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'not_completed' => 'Chưa đạt',
            'completed' => 'Đã đạt',
            'exceeded' => 'Vượt chỉ tiêu',
            'payable' => 'Được thanh toán',
            'no_target' => 'Chưa giao chỉ tiêu',
            default => 'Chưa có dữ liệu',
        };
    }
}
