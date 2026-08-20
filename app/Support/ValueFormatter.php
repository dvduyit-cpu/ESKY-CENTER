<?php

namespace App\Support;

class ValueFormatter
{
    public static function percentage(float|int|string|null $value, int $maxDecimals = 2): string
    {
        $number = (float) ($value ?? 0);
        $formatted = number_format($number, $maxDecimals, '.', '');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        return $formatted === '-0' ? '0' : $formatted;
    }
}
