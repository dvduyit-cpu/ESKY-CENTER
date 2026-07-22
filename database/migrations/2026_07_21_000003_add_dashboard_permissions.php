<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        foreach ([
            ['system_dashboard', 'Tổng quan toàn hệ thống', 'bi-grid-1x2-fill', 1],
            ['kpi_dashboard_all', 'Tổng quan KPI toàn hệ thống', 'bi-speedometer', 2],
            ['language_dashboard_all', 'Tổng quan trung tâm toàn hệ thống', 'bi-speedometer2', 3],
        ] as [$code, $name, $icon, $sort]) {
            DB::table('modules')->updateOrInsert(['code' => $code], ['name' => $name, 'icon' => $icon, 'sort_order' => $sort, 'created_at' => $now, 'updated_at' => $now]);
            $moduleId = DB::table('modules')->where('code', $code)->value('id');
            foreach (DB::table('roles')->get(['id','code']) as $role) {
                $admin = $role->code === 'admin';
                DB::table('role_permissions')->updateOrInsert(['role_id' => $role->id, 'module_id' => $moduleId], [
                    'can_view' => $admin, 'can_create' => false, 'can_update' => false, 'can_delete' => false, 'can_export' => false,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('modules')->whereIn('code', ['system_dashboard','kpi_dashboard_all','language_dashboard_all'])->pluck('id');
        DB::table('role_permissions')->whereIn('module_id', $ids)->delete();
        DB::table('modules')->whereIn('id', $ids)->delete();
    }
};
