<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class CenterCode
{
    public static function next(string $table, string $prefix, int $digits = 5): string
    {
        $id = (int) DB::table($table)->max('id') + 1;

        return $prefix.'-'.now()->format('Y').'-'.str_pad((string) $id, $digits, '0', STR_PAD_LEFT);
    }
}
