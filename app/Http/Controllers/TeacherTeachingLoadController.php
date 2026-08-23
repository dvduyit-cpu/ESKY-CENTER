<?php

namespace App\Http\Controllers;

use App\Models\KpiPlan;
use App\Models\KpiTarget;
use App\Models\KpiTeachingReport;
use App\Support\ActivityLogger;
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
