<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('modules')->updateOrInsert(
            ['code' => 'teaching_load_management'],
            [
                'name' => 'Tổng hợp giờ dạy',
                'icon' => 'bi-kanban',
                'sort_order' => 32,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $moduleId = DB::table('modules')->where('code', 'teaching_load_management')->value('id');
        foreach (DB::table('roles')->get(['id', 'code']) as $role) {
            $isAdmin = $role->code === 'admin';

            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $role->id, 'module_id' => $moduleId],
                [
                    'can_view' => $isAdmin,
                    'can_create' => false,
                    'can_update' => false,
                    'can_delete' => false,
                    'can_export' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        $moduleId = DB::table('modules')->where('code', 'teaching_load_management')->value('id');
        if (! $moduleId) {
            return;
        }

        DB::table('role_permissions')->where('module_id', $moduleId)->delete();
        DB::table('modules')->where('id', $moduleId)->delete();
    }
};
