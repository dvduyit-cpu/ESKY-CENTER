<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();
        foreach ([
            ['code'=>'teacher_classes','name'=>'Lớp giảng dạy & điểm','icon'=>'bi-journal-check','sort_order'=>23],
            ['code'=>'language_courses','name'=>'Khóa học trung tâm','icon'=>'bi-book-fill','sort_order'=>26],
        ] as $module) {
            DB::table('modules')->updateOrInsert(
                ['code'=>$module['code']],
                ['name'=>$module['name'],'icon'=>$module['icon'],'sort_order'=>$module['sort_order'],'updated_at'=>$now,'created_at'=>$now]
            );
        }

        $modules = DB::table('modules')->get(['id','code']);
        $roles = DB::table('roles')->get(['id','code']);
        $managementRoles = ['admin','leader','director','deputy_director'];

        foreach ($roles as $role) {
            foreach ($modules as $module) {
                if (DB::table('role_permissions')->where(['role_id'=>$role->id,'module_id'=>$module->id])->exists()) continue;
                $values = ['can_view'=>false,'can_create'=>false,'can_update'=>false,'can_delete'=>false,'can_export'=>false];
                if ($module->code === 'teacher_classes' && (in_array($role->code,$managementRoles,true)||$role->code === 'teacher')) {
                    $values['can_view'] = true;
                    $values['can_update'] = true;
                }
                if ($module->code === 'language_courses' && in_array($role->code,$managementRoles,true)) {
                    $values = ['can_view'=>true,'can_create'=>true,'can_update'=>true,'can_delete'=>true,'can_export'=>true];
                }
                DB::table('role_permissions')->insert($values+['role_id'=>$role->id,'module_id'=>$module->id,'created_at'=>$now,'updated_at'=>$now]);
            }
        }

        $teacherModuleId = $modules->firstWhere('code','teacher_classes')?->id;
        if ($teacherModuleId) {
            DB::table('role_permissions')->where('module_id',$teacherModuleId)->update(['can_create'=>false,'can_delete'=>false,'can_export'=>false,'updated_at'=>$now]);
            DB::table('user_permissions')->where('module_id',$teacherModuleId)->update(['can_create'=>false,'can_delete'=>false,'can_export'=>false,'updated_at'=>$now]);
        }
    }

    public function down(): void
    {
        // Giữ lại module và quyền đã có để không làm mất cấu hình của người dùng.
    }
};
