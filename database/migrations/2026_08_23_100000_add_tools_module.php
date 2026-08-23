<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('modules')->updateOrInsert(
            ['code' => 'tools'],
            [
                'name' => 'Tool tiện ích',
                'icon' => 'bi-tools',
                'sort_order' => 38,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $moduleId = DB::table('modules')->where('code', 'tools')->value('id');
        foreach (DB::table('roles')->get(['id', 'code']) as $role) {
            $admin = $role->code === 'admin';
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $role->id, 'module_id' => $moduleId],
                [
                    'can_view' => $admin,
                    'can_create' => $admin,
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
        $moduleId = DB::table('modules')->where('code', 'tools')->value('id');
        if (! $moduleId) {
            return;
        }

        DB::table('role_permissions')->where('module_id', $moduleId)->delete();
        DB::table('modules')->where('id', $moduleId)->delete();
    }
};
