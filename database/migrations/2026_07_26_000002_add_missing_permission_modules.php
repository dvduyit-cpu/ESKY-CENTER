<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $modules = [
            ['code'=>'teacher_classes','name'=>'Lớp giảng dạy & điểm','icon'=>'bi-journal-check','sort_order'=>31],
            ['code'=>'software_settings','name'=>'Cấu hình phần mềm','icon'=>'bi-sliders','sort_order'=>96],
        ];

        foreach ($modules as $module) {
            DB::table('modules')->updateOrInsert(
                ['code'=>$module['code']],
                ['name'=>$module['name'],'icon'=>$module['icon'],'sort_order'=>$module['sort_order'],'created_at'=>$now,'updated_at'=>$now]
            );
        }

        $teacherModuleId = DB::table('modules')->where('code','teacher_classes')->value('id');
        $settingsModuleId = DB::table('modules')->where('code','software_settings')->value('id');
        $classesModuleId = DB::table('modules')->where('code','language_classes')->value('id');

        foreach (DB::table('roles')->get(['id','code']) as $role) {
            $admin = $role->code === 'admin';
            $teacher = $role->code === 'teacher';
            $classPermission = $classesModuleId
                ? DB::table('role_permissions')->where(['role_id'=>$role->id,'module_id'=>$classesModuleId])->first()
                : null;

            DB::table('role_permissions')->updateOrInsert(
                ['role_id'=>$role->id,'module_id'=>$teacherModuleId],
                [
                    'can_view'=>$admin || $teacher || (bool) ($classPermission?->can_view),
                    'can_create'=>false,
                    'can_update'=>$admin || $teacher || (bool) ($classPermission?->can_update),
                    'can_delete'=>false,
                    'can_export'=>false,
                    'created_at'=>$now,'updated_at'=>$now,
                ]
            );

            DB::table('role_permissions')->updateOrInsert(
                ['role_id'=>$role->id,'module_id'=>$settingsModuleId],
                [
                    'can_view'=>$admin,'can_create'=>false,'can_update'=>$admin,'can_delete'=>false,'can_export'=>false,
                    'created_at'=>$now,'updated_at'=>$now,
                ]
            );
        }
    }

    public function down(): void
    {
        $ids = DB::table('modules')->whereIn('code',['teacher_classes','software_settings'])->pluck('id');
        DB::table('user_permissions')->whereIn('module_id',$ids)->delete();
        DB::table('role_permissions')->whereIn('module_id',$ids)->delete();
        DB::table('modules')->whereIn('id',$ids)->delete();
    }
};
