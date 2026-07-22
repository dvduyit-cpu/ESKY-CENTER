<?php

namespace App\Support;

class Pagination
{
    public static function perPage(): int
    {
        $value = request()->integer('per_page', 10);

        return in_array($value, [10, 20, 30, 40, 50, 100], true) ? $value : 10;
    }
}
