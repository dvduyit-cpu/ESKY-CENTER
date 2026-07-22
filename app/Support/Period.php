<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

class Period
{
    public static function quarterOfMonth(int $month): int
    {
        return (int) ceil($month / 3);
    }

    public static function applyRecordFilter(Builder $query, int $year, string $type, int $value = 0): Builder
    {
        $query->where('record_year', $year);
        if ($type === 'month' && $value > 0) {
            $query->where('record_month', $value);
        } elseif ($type === 'quarter' && $value > 0) {
            $query->where('record_quarter', $value);
        }
        return $query;
    }

    public static function label(string $type, int $value, int $year): string
    {
        return match ($type) {
            'month' => 'Tháng '.str_pad((string) $value, 2, '0', STR_PAD_LEFT).'/'.$year,
            'quarter' => 'Quý '.$value.'/'.$year,
            default => 'Năm '.$year,
        };
    }
}
