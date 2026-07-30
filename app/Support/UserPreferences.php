<?php

namespace App\Support;

use App\Models\User;

class UserPreferences
{
    public const LANDING_PAGES = [
        'welcome' => ['Trang chào mừng', 'welcome', null],
        'dashboard' => ['Tổng quan', 'dashboard', null],
        'plans' => ['Kế hoạch & lịch cá nhân', 'plans.index', null],
        'tasks' => ['Công việc', 'tasks.index', 'work_tasks'],
        'kpi-dashboard' => ['Tổng quan KPI', 'kpi-dashboard.index', 'kpis'],
        'language-dashboard' => ['Tổng quan trung tâm', 'language-dashboard.index', 'language_leads|language_students|language_programs|language_classes'],
        'teacher-classes' => ['Lớp giảng dạy & điểm', 'teacher-classes.index', 'teacher_classes'],
    ];

    public static function value(User $user, string $key, mixed $default = null): mixed
    {
        $preference = $user->preferences->firstWhere('key', $key);

        return $preference && $preference->value !== null ? $preference->value : $default;
    }

    public static function landingPages(User $user): array
    {
        return array_filter(self::LANDING_PAGES, function (array $option) use ($user): bool {
            if (! $option[2]) return true;
            foreach (explode('|', $option[2]) as $module) {
                if ($user->allowed($module)) return true;
            }
            return false;
        });
    }

    public static function landingRoute(User $user): string
    {
        if ($user->isDirector()) return 'director.dashboard';

        $selected = (string) self::value($user, 'landing_page', 'welcome');
        $available = self::landingPages($user);

        return $available[$selected][1] ?? 'welcome';
    }
}
