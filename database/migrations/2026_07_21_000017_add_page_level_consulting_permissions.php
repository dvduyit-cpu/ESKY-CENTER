<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $sourceId=DB::table('modules')->where('code','language_leads')->value('id');
        foreach ([
            ['code'=>'language_consulting','name'=>'Công việc tư vấn','icon'=>'bi-headset','sort_order'=>18],
            ['code'=>'language_target_submissions','name'=>'Gửi chỉ tiêu','icon'=>'bi-send-fill','sort_order'=>19],
        ] as $module) {
            $moduleId=DB::table('modules')->where('code',$module['code'])->value('id');
            if (! $moduleId) $moduleId=DB::table('modules')->insertGetId($module+['created_at'=>now(),'updated_at'=>now()]);
            if (! $sourceId) continue;
            foreach (DB::table('role_permissions')->where('module_id',$sourceId)->get() as $permission) {
                DB::table('role_permissions')->updateOrInsert(['role_id'=>$permission->role_id,'module_id'=>$moduleId],['can_view'=>$permission->can_view,'can_create'=>$permission->can_create,'can_update'=>$permission->can_update,'can_delete'=>$permission->can_delete,'can_export'=>$permission->can_export,'created_at'=>now(),'updated_at'=>now()]);
            }
            foreach (DB::table('user_permissions')->where('module_id',$sourceId)->get() as $permission) {
                DB::table('user_permissions')->updateOrInsert(['user_id'=>$permission->user_id,'module_id'=>$moduleId],['can_view'=>$permission->can_view,'can_create'=>$permission->can_create,'can_update'=>$permission->can_update,'can_delete'=>$permission->can_delete,'can_export'=>$permission->can_export,'created_at'=>now(),'updated_at'=>now()]);
            }
        }
    }

    public function down(): void
    {
        $ids=DB::table('modules')->whereIn('code',['language_consulting','language_target_submissions'])->pluck('id');
        DB::table('role_permissions')->whereIn('module_id',$ids)->delete();
        DB::table('user_permissions')->whereIn('module_id',$ids)->delete();
        DB::table('modules')->whereIn('id',$ids)->delete();
    }
};
