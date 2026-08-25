<?php

namespace App\Http\Controllers;

use App\Models\KpiPlan;
use App\Models\KpiTarget;
use App\Models\KpiTeachingReport;
use App\Models\Personnel;
use App\Support\ActivityLogger;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TeacherTeachingLoadController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->canTeach() || $user->isAdmin(), 403);

        $personnel = $user->personnel;
        $year = (int) $request->input('year', now()->year);

        $availableYears = collect();
        if ($personnel) {
            $availableYears = KpiPlan::query()
                ->whereHas('targets', fn ($query) => $query
                    ->where('personnel_id', $personnel->id)
                    ->where('period_type', 'year')
                    ->where('assigned_teaching_load', '>', 0)
                )
                ->orderByDesc('year')
                ->pluck('year');
        }
        if ($availableYears->isEmpty()) {
            $availableYears = collect([$year]);
        } elseif (! $availableYears->contains($year)) {
            $availableYears = $availableYears->prepend($year)->unique()->sortDesc()->values();
        }

        $plan = KpiPlan::query()->where('year', $year)->first();
        $target = null;
        $reports = collect();

        if ($plan && $personnel) {
            $target = KpiTarget::query()
                ->where('plan_id', $plan->id)
                ->where('personnel_id', $personnel->id)
                ->where('period_type', 'year')
                ->first();

            $reports = KpiTeachingReport::query()
                ->where('plan_id', $plan->id)
                ->where('personnel_id', $personnel->id)
                ->orderBy('report_month')
                ->get()
                ->keyBy('report_month');
        }

        $assignedTeachingLoad = round((float) ($target?->assigned_teaching_load ?? 0), 2);
        $reportedTeachingLoad = round((float) $reports->sum('reported_teaching_load'), 2);
        $remainingTeachingLoad = round(max($assignedTeachingLoad - $reportedTeachingLoad, 0), 2);
        $exceededTeachingLoad = round(max($reportedTeachingLoad - $assignedTeachingLoad, 0), 2);
        $monthlyRows = collect(range(1, 12))->map(fn (int $month) => [
            'month' => $month,
            'report' => $reports->get($month),
            'detail_rows' => $this->detailRowsForMonth($reports->get($month)),
        ]);

        return view('kpis.teaching-reports', compact(
            'availableYears',
            'assignedTeachingLoad',
            'exceededTeachingLoad',
            'monthlyRows',
            'personnel',
            'plan',
            'remainingTeachingLoad',
            'reportedTeachingLoad',
            'target',
            'year',
        ));
    }

    public function managementIndex(Request $request): View
    {
        $user = $request->user();
        abort_unless($user?->allowed('teaching_load_management'), 403);

        $year = (int) $request->input('year', now()->year);
        $selectedMonth = $request->filled('report_month')
            ? max(1, min(12, (int) $request->input('report_month')))
            : null;
        $selectedPersonnelId = $request->filled('personnel_id')
            ? (int) $request->input('personnel_id')
            : null;

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
                    ->whereHas('plan', fn ($planQuery) => $planQuery->where('year', $year))
                    ->where('assigned_teaching_load', '>', 0)
                )
                ->orWhereHas('teachingReports', fn ($reports) => $reports->where('report_year', $year))
            )
            ->orderBy('name')
            ->get();

        $plan = KpiPlan::query()->where('year', $year)->first();
        $summaryRows = collect();
        $summaryTotals = [
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
                ->when($selectedPersonnelId, fn ($query) => $query->where('personnel_id', $selectedPersonnelId))
                ->get()
                ->keyBy('personnel_id');

            $reportsByPersonnel = KpiTeachingReport::query()
                ->with(['personnel', 'reporter'])
                ->where('plan_id', $plan->id)
                ->where('report_year', $year)
                ->when($selectedPersonnelId, fn ($query) => $query->where('personnel_id', $selectedPersonnelId))
                ->orderBy('report_month')
                ->get()
                ->groupBy('personnel_id');

            $personnelIds = $targets->keys()
                ->merge($reportsByPersonnel->keys())
                ->unique()
                ->values();

            $personnelMap = Personnel::withTrashed()
                ->whereIn('id', $personnelIds)
                ->get()
                ->keyBy('id');

            $summaryRows = $personnelIds->map(function ($personnelId) use ($personnelMap, $reportsByPersonnel, $selectedMonth, $targets) {
                $target = $targets->get($personnelId);
                $reports = $reportsByPersonnel->get($personnelId, collect())->sortBy('report_month')->values();
                $periodReport = $selectedMonth ? $reports->firstWhere('report_month', $selectedMonth) : null;
                $periodTotal = $selectedMonth
                    ? round((float) ($periodReport?->reported_teaching_load ?? 0), 2)
                    : round((float) $reports->sum('reported_teaching_load'), 2);
                $assigned = round((float) ($target?->assigned_teaching_load ?? 0), 2);
                $reported = round((float) $reports->sum('reported_teaching_load'), 2);

                return [
                    'personnel' => $personnelMap->get($personnelId),
                    'target' => $target,
                    'assigned_teaching_load' => $assigned,
                    'reported_teaching_load' => $reported,
                    'remaining_teaching_load' => round(max($assigned - $reported, 0), 2),
                    'exceeded_teaching_load' => round(max($reported - $assigned, 0), 2),
                    'period_teaching_load' => $periodTotal,
                    'report_count' => $reports->count(),
                    'months_reported' => $reports->pluck('report_month')->values(),
                    'latest_report' => $selectedMonth ? $periodReport : $reports->sortByDesc('updated_at')->first(),
                    'period_report' => $periodReport,
                    'period_detail_rows' => $selectedMonth ? $this->detailRowsForMonth($periodReport) : [],
                    'monthly_breakdown' => collect(range(1, 12))->map(function (int $month) use ($reports) {
                        $report = $reports->firstWhere('report_month', $month);

                        return [
                            'month' => $month,
                            'total' => round((float) ($report?->reported_teaching_load ?? 0), 2),
                            'updated_at' => $report?->updated_at,
                            'reporter_name' => $report?->reporter?->name,
                            'detail_rows' => $this->detailRowsForMonth($report),
                        ];
                    })->all(),
                ];
            })
                ->sortBy(fn (array $row) => mb_strtolower((string) ($row['personnel']?->name ?? '')))
                ->values();

            $summaryTotals = [
                'teacher_count' => $summaryRows->count(),
                'assigned_teaching_load' => round((float) $summaryRows->sum('assigned_teaching_load'), 2),
                'reported_teaching_load' => round((float) $summaryRows->sum('reported_teaching_load'), 2),
                'remaining_teaching_load' => round((float) $summaryRows->sum('remaining_teaching_load'), 2),
                'exceeded_teaching_load' => round((float) $summaryRows->sum('exceeded_teaching_load'), 2),
                'period_teaching_load' => round((float) $summaryRows->sum('period_teaching_load'), 2),
            ];
        }

        return view('kpis.teaching-load-management', [
            'availableYears' => $availableYears,
            'personnels' => $personnels,
            'plan' => $plan,
            'selectedMonth' => $selectedMonth,
            'selectedPersonnelId' => $selectedPersonnelId,
            'summaryRows' => $summaryRows,
            'summaryTotals' => $summaryTotals,
            'year' => $year,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->canTeach() || $user->isAdmin(), 403);

        $personnel = $user->personnel;
        if (! $personnel) {
            throw ValidationException::withMessages([
                'personnel' => 'Tài khoản này chưa liên kết nhân sự nên chưa thể báo cáo tiết dạy.',
            ]);
        }

        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'report_month' => ['required', 'integer', 'min:1', 'max:12'],
            'rows' => ['nullable', 'array'],
            'rows.*.date' => ['nullable', 'date'],
            'rows.*.class_name' => ['nullable', 'string', 'max:255'],
            'rows.*.time_slot' => ['nullable', 'string', 'max:100'],
            'rows.*.lesson_count' => ['nullable', 'numeric', 'min:0'],
            'rows.*.note' => ['nullable', 'string', 'max:1000'],
        ]);

        $plan = KpiPlan::query()->where('year', $data['year'])->first();
        if (! $plan) {
            throw ValidationException::withMessages([
                'year' => 'Năm này chưa có kế hoạch chỉ tiêu để gắn báo cáo tiết dạy.',
            ]);
        }

        $target = KpiTarget::query()
            ->where('plan_id', $plan->id)
            ->where('personnel_id', $personnel->id)
            ->where('period_type', 'year')
            ->first();

        if (! $target || (float) $target->assigned_teaching_load <= 0) {
            throw ValidationException::withMessages([
                'reported_teaching_load' => 'Bạn chưa được giao số tiết dạy cho năm này.',
            ]);
        }

        $detailRows = collect($data['rows'] ?? [])
            ->map(fn (array $row): array => [
                'date' => trim((string) ($row['date'] ?? '')),
                'class_name' => trim((string) ($row['class_name'] ?? '')),
                'time_slot' => trim((string) ($row['time_slot'] ?? '')),
                'lesson_count' => $row['lesson_count'] ?? null,
                'note' => trim((string) ($row['note'] ?? '')),
            ])
            ->filter(fn (array $row): bool => $row['date'] !== ''
                || $row['class_name'] !== ''
                || $row['time_slot'] !== ''
                || $row['note'] !== ''
                || $row['lesson_count'] !== null
            )
            ->values();

        $rowErrors = [];
        $normalizedRows = $detailRows->map(function (array $row, int $index) use ($data, &$rowErrors): array {
            $line = $index + 1;

            if ($row['date'] === '') {
                $rowErrors["rows.$index.date"] = 'Dòng '.$line.' chưa nhập ngày.';
            }
            if ($row['class_name'] === '') {
                $rowErrors["rows.$index.class_name"] = 'Dòng '.$line.' chưa nhập lớp/mã lớp.';
            }
            if ($row['time_slot'] === '') {
                $rowErrors["rows.$index.time_slot"] = 'Dòng '.$line.' chưa nhập khung giờ.';
            }
            if ($row['lesson_count'] === null || $row['lesson_count'] === '') {
                $rowErrors["rows.$index.lesson_count"] = 'Dòng '.$line.' cần nhập số tiết từ 0 trở lên.';
            }

            if ($row['date'] !== '') {
                try {
                    $date = Carbon::parse($row['date']);
                    if ((int) $date->year !== (int) $data['year'] || (int) $date->month !== (int) $data['report_month']) {
                        $rowErrors["rows.$index.date"] = 'Dòng '.$line.' phải thuộc tháng '.$data['report_month'].'/'.$data['year'].'.';
                    }
                    $row['date'] = $date->format('Y-m-d');
                } catch (\Throwable) {
                    $rowErrors["rows.$index.date"] = 'Dòng '.$line.' có ngày không hợp lệ.';
                }
            }

            $row['lesson_count'] = $row['lesson_count'] === null || $row['lesson_count'] === ''
                ? null
                : round((float) $row['lesson_count'], 2);

            return $row;
        });

        if ($rowErrors !== []) {
            throw ValidationException::withMessages($rowErrors);
        }

        $reportedTeachingLoad = round((float) $normalizedRows->sum('lesson_count'), 2);
        $summaryNote = $normalizedRows->pluck('note')->filter()->implode(' | ');

        $report = KpiTeachingReport::query()->updateOrCreate(
            [
                'plan_id' => $plan->id,
                'personnel_id' => $personnel->id,
                'report_year' => (int) $data['year'],
                'report_month' => (int) $data['report_month'],
            ],
            [
                'kpi_target_id' => $target->id,
                'reported_by' => $user->id,
                'reported_teaching_load' => $reportedTeachingLoad,
                'note' => $summaryNote !== '' ? mb_substr($summaryNote, 0, 2000) : null,
                'report_rows' => $normalizedRows->all(),
            ]
        );

        ActivityLogger::log(
            'kpis',
            'save_teaching_report',
            'Cập nhật báo cáo tiết dạy tháng '.$data['report_month'].'/'.$data['year'].' cho '.$personnel->name,
            $report
        );

        return redirect()
            ->route('teacher-classes.teaching-load.index', ['year' => $data['year']])
            ->with('success', 'Đã lưu báo cáo tiết dạy tháng '.$data['report_month'].'/'.$data['year'].'.');
    }

    public function pdf(Request $request)
    {
        $user = $request->user();
        abort_unless($user->canTeach() || $user->isAdmin(), 403);

        $personnel = $user->personnel;
        if (! $personnel) {
            throw ValidationException::withMessages([
                'personnel' => 'TÃ i khoáº£n nÃ y chÆ°a liÃªn káº¿t nhÃ¢n sá»± nÃªn chÆ°a thá»ƒ táº£i bÃ¡o cÃ¡o tiáº¿t dáº¡y.',
            ]);
        }

        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'report_month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $plan = KpiPlan::query()->where('year', $data['year'])->first();
        if (! $plan) {
            throw ValidationException::withMessages([
                'year' => 'NÄƒm nÃ y chÆ°a cÃ³ káº¿ hoáº¡ch chá»‰ tiÃªu Ä‘á»ƒ gáº¯n bÃ¡o cÃ¡o tiáº¿t dáº¡y.',
            ]);
        }

        $target = KpiTarget::query()
            ->where('plan_id', $plan->id)
            ->where('personnel_id', $personnel->id)
            ->where('period_type', 'year')
            ->first();

        $report = KpiTeachingReport::query()
            ->where('plan_id', $plan->id)
            ->where('personnel_id', $personnel->id)
            ->where('report_year', (int) $data['year'])
            ->where('report_month', (int) $data['report_month'])
            ->first();

        $detailRows = collect($this->detailRowsForMonth($report));
        $reportedTeachingLoad = round((float) $detailRows->sum(fn (array $row) => (float) ($row['lesson_count'] ?? 0)), 2);
        if ($reportedTeachingLoad <= 0 && $report) {
            $reportedTeachingLoad = round((float) $report->reported_teaching_load, 2);
        }

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isFontSubsettingEnabled', true);
        $options->set('isRemoteEnabled', false);
        $options->setChroot(public_path());

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('kpis.teaching-report-pdf', [
            'personnel' => $personnel,
            'plan' => $plan,
            'target' => $target,
            'report' => $report,
            'detailRows' => $detailRows,
            'reportedTeachingLoad' => $reportedTeachingLoad,
            'year' => (int) $data['year'],
            'reportMonth' => (int) $data['report_month'],
        ])->render(), 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $monthLabel = str_pad((string) $data['report_month'], 2, '0', STR_PAD_LEFT);

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="bao-cao-tiet-day-'.$data['year'].'-'.$monthLabel.'.pdf"',
        ]);
    }

    private function detailRowsForMonth(?KpiTeachingReport $report): array
    {
        $rows = collect($report?->report_rows ?? [])
            ->map(fn (array $row): array => [
                'date' => (string) ($row['date'] ?? ''),
                'class_name' => (string) ($row['class_name'] ?? ''),
                'time_slot' => (string) ($row['time_slot'] ?? ''),
                'lesson_count' => isset($row['lesson_count']) && $row['lesson_count'] !== null
                    ? rtrim(rtrim(number_format((float) $row['lesson_count'], 2, '.', ''), '0'), '.')
                    : '',
                'note' => (string) ($row['note'] ?? ''),
            ])
            ->filter(fn (array $row): bool => $row['date'] !== ''
                || $row['class_name'] !== ''
                || $row['time_slot'] !== ''
                || $row['note'] !== ''
                || $row['lesson_count'] !== ''
            )
            ->values();

        if ($rows->isEmpty() && $report && (float) $report->reported_teaching_load > 0) {
            $rows = collect([[
                'date' => '',
                'class_name' => '',
                'time_slot' => '',
                'lesson_count' => rtrim(rtrim(number_format((float) $report->reported_teaching_load, 2, '.', ''), '0'), '.'),
                'note' => (string) ($report->note ?? ''),
            ]]);
        }

        return $rows->all();
    }
}
