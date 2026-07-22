<?php

namespace App\Support;

use App\Models\KpiPlan;
use App\Models\KpiRecord;
use App\Models\KpiTarget;
use Illuminate\Support\Collection;

class KpiCalculator
{
    public function report(array $filters): array
    {
        $year = (int) ($filters['year'] ?? now()->year);
        $periodType = in_array(($filters['period_type'] ?? 'year'), ['month','quarter','year'], true)
            ? $filters['period_type'] : 'year';
        $periodValue = (int) ($filters['period_value'] ?? 0);

        $actualQuery = KpiRecord::query()
            ->with(['personnel', 'course'])
            ->whereNotNull('personnel_id');
        Period::applyRecordFilter($actualQuery, $year, $periodType, $periodValue);

        if (! empty($filters['personnel_id'])) {
            $actualQuery->where('personnel_id', (int) $filters['personnel_id']);
        }
        if (! empty($filters['course_id'])) {
            $actualQuery->where('course_id', (int) $filters['course_id']);
        }
        if (! empty($filters['personnel_type'])) {
            $actualQuery->whereHas('personnel', fn ($q) => $q->where('type', $filters['personnel_type']));
        }

        $actualGroups = $actualQuery
            ->groupBy('personnel_id', 'course_id', 'conversion_quantity', 'conversion_kpi', 'conversion_mode')
            ->selectRaw('personnel_id, course_id, conversion_quantity, conversion_kpi, conversion_mode, SUM(raw_quantity) raw_quantity, SUM(revenue) revenue, COUNT(*) record_count')
            ->get();

        $actualMap = collect();
        foreach ($actualGroups as $group) {
            $key = $group->personnel_id.'|all';
            $raw = (float) $group->raw_quantity;
            $base = max((float) $group->conversion_quantity, 0.0001);
            $credit = (float) $group->conversion_kpi;
            $converted = $group->conversion_mode === 'full_group'
                ? floor($raw / $base) * $credit
                : ($raw / $base) * $credit;

            $existing = $actualMap->get($key, [
                'personnel' => $group->personnel,
                'course' => null,
                'raw_quantity' => 0.0,
                'actual_quantity' => 0.0,
                'revenue' => 0.0,
                'record_count' => 0,
            ]);
            $existing['raw_quantity'] += $raw;
            $existing['actual_quantity'] += $converted;
            $existing['revenue'] += (float) $group->revenue;
            $existing['record_count'] += (int) $group->record_count;
            $actualMap->put($key, $existing);
        }

        $targetMap = $this->targetsForPeriod($year, $periodType, $periodValue, $filters);
        $keys = $actualMap->keys()->merge($targetMap->keys())->unique();
        $rows = collect();

        foreach ($keys as $key) {
            $actual = $actualMap->get($key, []);
            $target = $targetMap->get($key, []);
            $personnel = $actual['personnel'] ?? $target['personnel'] ?? null;
            $course = $actual['course'] ?? $target['course'] ?? null;
            if (! $personnel) {
                continue;
            }

            $targetQuantity = (float) ($target['target_quantity'] ?? 0);
            $actualQuantity = round((float) ($actual['actual_quantity'] ?? 0), 2);
            $mandatory = (bool) ($target['is_mandatory'] ?? false);
            $hasTarget = array_key_exists('target_quantity', $target);
            $remaining = $mandatory ? max($targetQuantity - $actualQuantity, 0) : 0;
            $excess = ! $hasTarget ? 0 : ($mandatory
                ? max($actualQuantity - $targetQuantity, 0)
                : $actualQuantity);
            $rate = (float) ($target['rate'] ?? 0);
            $payment = round($excess * $rate, 2);
            $completion = $targetQuantity > 0 ? round($actualQuantity / $targetQuantity * 100, 2) : 0;
            $status = ! $hasTarget ? 'no_target' : ($mandatory
                ? ($actualQuantity > $targetQuantity ? 'exceeded' : ($actualQuantity >= $targetQuantity ? 'completed' : 'not_completed'))
                : ($actualQuantity > 0 ? 'payable' : 'no_result'));

            $rows->push([
                'personnel_id' => $personnel->id,
                'personnel_name' => $personnel->name,
                'personnel_type' => $personnel->type,
                'personnel_type_label' => $personnel->typeLabel(),
                'course_id' => null,
                'course_name' => 'Tất cả khóa học',
                'raw_quantity' => round((float) ($actual['raw_quantity'] ?? 0), 2),
                'target_quantity' => round($targetQuantity, 2),
                'actual_quantity' => $actualQuantity,
                'remaining_quantity' => round($remaining, 2),
                'excess_quantity' => round($excess, 2),
                'completion_pct' => $completion,
                'revenue' => round((float) ($actual['revenue'] ?? 0), 2),
                'target_revenue' => round((float) ($target['target_revenue'] ?? 0), 2),
                'is_mandatory' => $mandatory,
                'payment_rate' => $rate,
                'payment_amount' => $payment,
                'status' => $status,
                'record_count' => (int) ($actual['record_count'] ?? 0),
            ]);
        }

        $rows = $rows->sortBy([['personnel_name', 'asc'], ['course_name', 'asc']])->values();
        $totals = [
            'target_quantity' => round($rows->sum('target_quantity'), 2),
            'actual_quantity' => round($rows->sum('actual_quantity'), 2),
            'remaining_quantity' => round($rows->sum('remaining_quantity'), 2),
            'excess_quantity' => round($rows->sum('excess_quantity'), 2),
            'revenue' => round($rows->sum('revenue'), 2),
            'payment_amount' => round($rows->sum('payment_amount'), 2),
            'completed_people' => $rows->whereIn('status', ['completed','exceeded','payable'])->pluck('personnel_id')->unique()->count(),
            'not_completed_people' => $rows->where('status', 'not_completed')->pluck('personnel_id')->unique()->count(),
        ];

        return compact('rows', 'totals');
    }

    private function targetsForPeriod(int $year, string $periodType, int $periodValue, array $filters): Collection
    {
        $plan = KpiPlan::query()->where('year', $year)->first();
        if (! $plan) {
            return collect();
        }

        $query = KpiTarget::query()->with(['personnel','course'])->where('plan_id', $plan->id);
        if (! empty($filters['personnel_id'])) {
            $query->where('personnel_id', (int) $filters['personnel_id']);
        }
        if (! empty($filters['personnel_type'])) {
            $query->whereHas('personnel', fn ($q) => $q->where('type', $filters['personnel_type']));
        }
        $all = $query->get()->groupBy(fn ($t) => $t->personnel_id.'|all');
        $result = collect();

        foreach ($all as $key => $targets) {
            $selected = collect();
            if ($periodType === 'month') {
                $selected = $targets->where('period_type', 'month')->where('month', $periodValue);
            } elseif ($periodType === 'quarter') {
                $monthly = $targets->where('period_type', 'month')->where('quarter', $periodValue);
                $selected = $monthly->isNotEmpty()
                    ? $monthly
                    : $targets->where('period_type', 'quarter')->where('quarter', $periodValue);
            } else {
                foreach (range(1, 4) as $quarter) {
                    $monthly = $targets->where('period_type', 'month')->where('quarter', $quarter);
                    if ($monthly->isNotEmpty()) {
                        $selected = $selected->merge($monthly);
                    } else {
                        $selected = $selected->merge($targets->where('period_type', 'quarter')->where('quarter', $quarter));
                    }
                }
                if ($selected->isEmpty()) {
                    $selected = $targets->where('period_type', 'year');
                }
            }

            if ($selected->isEmpty()) {
                continue;
            }
            $last = $selected->sortByDesc('id')->first();
            $result->put($key, [
                'personnel' => $last->personnel,
                'course' => null,
                'target_quantity' => (float) $selected->sum('target_quantity'),
                'target_revenue' => (float) $selected->sum('target_revenue'),
                'is_mandatory' => $selected->contains(fn ($item) => (bool) $item->is_mandatory),
                'rate' => (float) ($selected->firstWhere('excess_payment_per_kpi', '>', 0)?->excess_payment_per_kpi
                    ?? 0),
            ]);
        }
        return $result;
    }
}
