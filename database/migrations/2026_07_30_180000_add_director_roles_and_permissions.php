<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $leaderRole = DB::table('roles')->where('code', 'leader')->first();

        DB::table('roles')->updateOrInsert(
            ['code' => 'director'],
            [
                'name' => 'Giám đốc',
                'description' => 'Giám sát toàn bộ hoạt động trung tâm và quản lý Phó giám đốc.',
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        DB::table('roles')->updateOrInsert(
            ['code' => 'deputy_director'],
            [
                'name' => 'Phó giám đốc',
                'description' => 'Điều hành công việc trung tâm theo phân quyền của Giám đốc.',
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $directorRoleId = (int) DB::table('roles')->where('code', 'director')->value('id');
        $deputyRoleId = (int) DB::table('roles')->where('code', 'deputy_director')->value('id');
        $leaderPermissions = $leaderRole
            ? DB::table('role_permissions')->where('role_id', $leaderRole->id)->get()->keyBy('module_id')
            : collect();

        foreach (DB::table('modules')->get(['id', 'code']) as $module) {
            $legacy = $leaderPermissions->get($module->id);
            $deputy = [
                'can_view' => (bool) ($legacy?->can_view ?? false),
                'can_create' => (bool) ($legacy?->can_create ?? false),
                'can_update' => (bool) ($legacy?->can_update ?? false),
                'can_delete' => (bool) ($legacy?->can_delete ?? false),
                'can_export' => (bool) ($legacy?->can_export ?? false),
            ];

            if (in_array($module->code, ['system_dashboard', 'kpi_dashboard_all', 'language_dashboard_all'], true)) {
                $deputy = [
                    'can_view' => true,
                    'can_create' => false,
                    'can_update' => false,
                    'can_delete' => false,
                    'can_export' => true,
                ];
            }
            if ($module->code === 'work_tasks') {
                $deputy = [
                    'can_view' => true,
                    'can_create' => true,
                    'can_update' => true,
                    'can_delete' => true,
                    'can_export' => false,
                ];
            }
            if (in_array($module->code, ['users', 'roles', 'logs', 'software_settings'], true)) {
                $deputy = [
                    'can_view' => false,
                    'can_create' => false,
                    'can_update' => false,
                    'can_delete' => false,
                    'can_export' => false,
                ];
            }

            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $deputyRoleId, 'module_id' => $module->id],
                $deputy + ['created_at' => $now, 'updated_at' => $now]
            );

            $director = $deputy;
            if (! in_array($module->code, ['roles', 'software_settings'], true)) {
                $director['can_view'] = true;
                $director['can_export'] = true;
            }
            if ($module->code === 'work_tasks') {
                $director = [
                    'can_view' => true,
                    'can_create' => true,
                    'can_update' => true,
                    'can_delete' => true,
                    'can_export' => false,
                ];
            }
            if ($module->code === 'users') {
                $director = [
                    'can_view' => true,
                    'can_create' => true,
                    'can_update' => true,
                    'can_delete' => false,
                    'can_export' => false,
                ];
            }
            if ($module->code === 'personnel') {
                $director['can_view'] = true;
                $director['can_update'] = true;
            }
            if ($module->code === 'logs') {
                $director = [
                    'can_view' => true,
                    'can_create' => false,
                    'can_update' => false,
                    'can_delete' => false,
                    'can_export' => true,
                ];
            }
            if (in_array($module->code, ['roles', 'software_settings'], true)) {
                $director = [
                    'can_view' => false,
                    'can_create' => false,
                    'can_update' => false,
                    'can_delete' => false,
                    'can_export' => false,
                ];
            }

            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $directorRoleId, 'module_id' => $module->id],
                $director + ['created_at' => $now, 'updated_at' => $now]
            );
        }

        if ($leaderRole) {
            $leaders = DB::table('users')
                ->leftJoin('personnels', 'personnels.id', '=', 'users.personnel_id')
                ->where('users.role_id', $leaderRole->id)
                ->get(['users.id', 'personnels.position']);

            foreach ($leaders as $leader) {
                $position = Str::lower(Str::ascii((string) $leader->position));
                $roleId = Str::contains($position, ['pho giam doc', 'deputy'])
                    ? $deputyRoleId
                    : $directorRoleId;
                DB::table('users')->where('id', $leader->id)->update([
                    'role_id' => $roleId,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $leaderRoleId = DB::table('roles')->where('code', 'leader')->value('id');
        $roleIds = DB::table('roles')->whereIn('code', ['director', 'deputy_director'])->pluck('id');

        if ($leaderRoleId) {
            DB::table('users')->whereIn('role_id', $roleIds)->update([
                'role_id' => $leaderRoleId,
                'updated_at' => now(),
            ]);
        }

        DB::table('role_permissions')->whereIn('role_id', $roleIds)->delete();
        DB::table('roles')->whereIn('id', $roleIds)->delete();
    }
};
