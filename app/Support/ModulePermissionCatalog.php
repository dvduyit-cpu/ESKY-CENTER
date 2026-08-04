<?php

namespace App\Support;

use Illuminate\Support\Collection;

class ModulePermissionCatalog
{
    public const ACTIONS = ['view', 'create', 'update', 'delete', 'export'];

    private const CAPABILITIES = [
        'system_dashboard' => ['view', 'export'],
        'kpi_dashboard_all' => ['view'],
        'language_dashboard_all' => ['view', 'export'],
        'work_tasks' => ['view', 'create', 'update', 'delete'],
        'administration' => ['view', 'create', 'update', 'delete', 'export'],
        'personnel' => ['view', 'create', 'update', 'delete'],
        'language_consulting' => ['view'],
        'language_target_submissions' => ['view', 'create'],
        'users' => ['view', 'create', 'update', 'delete'],
        'language_leads' => ['view', 'create', 'update', 'delete', 'export'],
        'language_students' => ['view', 'create', 'update', 'delete'],
        'language_programs' => ['view', 'create', 'update', 'delete'],
        'language_classes' => ['view', 'create', 'update', 'delete'],
        'language_collaborators' => ['view', 'create', 'update', 'delete', 'export'],
        'language_courses' => ['view', 'create', 'update', 'delete', 'export'],
        'language_discounts' => ['view', 'create', 'update', 'delete', 'export'],
        'language_tuition' => ['view', 'create', 'update', 'export'],
        'language_targets' => ['view', 'export'],
        'roles' => ['view', 'create', 'update', 'delete'],
        'teacher_classes' => ['view', 'update'],
        'kpis' => ['view', 'create', 'update', 'delete'],
        'courses' => ['view', 'create', 'update', 'delete'],
        'imports' => ['view', 'create', 'update', 'delete'],
        'reports' => ['view', 'export'],
        'payments' => ['view', 'create', 'update'],
        'logs' => ['view'],
        'software_settings' => ['view', 'update'],
    ];

    private const GROUPS = [
        'Tổng quan' => ['system_dashboard', 'kpi_dashboard_all', 'language_dashboard_all'],
        'Công việc & hệ thống' => ['work_tasks', 'administration', 'personnel', 'users', 'roles', 'logs', 'software_settings'],
        'Tuyển sinh' => ['language_consulting', 'language_target_submissions', 'language_leads', 'language_collaborators'],
        'Học viên & điều hành trung tâm' => ['language_students', 'language_tuition', 'language_discounts', 'language_targets'],
        'Đào tạo' => ['teacher_classes', 'language_classes', 'language_programs', 'language_courses'],
        'KPI & báo cáo' => ['kpis', 'courses', 'imports', 'reports', 'payments'],
    ];

    public static function actionsFor(string $moduleCode): array
    {
        return self::CAPABILITIES[$moduleCode] ?? self::ACTIONS;
    }

    public static function supports(string $moduleCode, string $action): bool
    {
        return in_array($action, self::actionsFor($moduleCode), true);
    }

    public static function grouped(Collection $modules): array
    {
        $byCode = $modules->keyBy('code');
        $groups = [];
        $used = collect();

        foreach (self::GROUPS as $name => $codes) {
            $rows = collect($codes)->map(fn (string $code) => $byCode->get($code))->filter()->values();
            if ($rows->isEmpty()) continue;
            $groups[] = ['name' => $name, 'modules' => $rows];
            $used = $used->merge($rows->pluck('code'));
        }

        $remaining = $modules->reject(fn ($module) => $used->contains($module->code))->values();
        if ($remaining->isNotEmpty()) $groups[] = ['name' => 'Khác', 'modules' => $remaining];

        return $groups;
    }
}
