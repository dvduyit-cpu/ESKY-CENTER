<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::table('modules')->updateOrInsert(
            ['code' => 'work_tasks'],
            ['name' => 'Công việc', 'icon' => 'bi-list-check', 'sort_order' => 4, 'created_at' => $now, 'updated_at' => $now]
        );
        $moduleId = DB::table('modules')->where('code', 'work_tasks')->value('id');

        foreach (DB::table('roles')->get(['id', 'code']) as $role) {
            $admin = $role->code === 'admin';
            $leader = $role->code === 'leader';
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $role->id, 'module_id' => $moduleId],
                [
                    'can_view' => true,
                    'can_create' => $admin || $leader,
                    'can_update' => true,
                    'can_delete' => $admin || $leader,
                    'can_export' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        $moduleId = DB::table('modules')->where('code', 'work_tasks')->value('id');
        if ($moduleId) {
            DB::table('user_permissions')->where('module_id', $moduleId)->delete();
            DB::table('role_permissions')->where('module_id', $moduleId)->delete();
            DB::table('modules')->where('id', $moduleId)->delete();
        }
    }
};
