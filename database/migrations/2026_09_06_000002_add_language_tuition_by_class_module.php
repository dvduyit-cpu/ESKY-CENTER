<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('modules')->updateOrInsert(
            ['code' => 'language_tuition_by_class'],
            [
                'name' => 'Thu học phí theo lớp',
                'icon' => 'bi-people-fill',
                'sort_order' => 27,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $moduleId = DB::table('modules')->where('code', 'language_tuition_by_class')->value('id');
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
        $moduleId = DB::table('modules')->where('code', 'language_tuition_by_class')->value('id');
        if (! $moduleId) {
            return;
        }

        DB::table('role_permissions')->where('module_id', $moduleId)->delete();
        DB::table('modules')->where('id', $moduleId)->delete();
    }
};
