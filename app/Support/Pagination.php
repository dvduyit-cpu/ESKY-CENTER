<?php

namespace App\Support;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;

class Pagination
{
    public static function perPage(): int
    {
        $default = (int) SystemSetting::valueOf('default_per_page', 10);
        if (Auth::check()) {
            $personal = (int) UserPreferences::value(Auth::user(), 'default_per_page', 0);
            if (in_array($personal, [10,20,30,50,100], true)) $default = $personal;
        }
        $value = request()->integer('per_page', $default);

        return in_array($value, [10, 20, 30, 40, 50, 100], true) ? $value : 10;
    }
}
