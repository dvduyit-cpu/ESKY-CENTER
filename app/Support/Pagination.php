<?php

namespace App\Support;

use App\Models\SystemSetting;

class Pagination
{
    public static function perPage(): int
    {
        $default = (int) SystemSetting::valueOf('default_per_page', 10);
        $value = request()->integer('per_page', $default);

        return in_array($value, [10, 20, 30, 40, 50, 100], true) ? $value : 10;
    }
}
