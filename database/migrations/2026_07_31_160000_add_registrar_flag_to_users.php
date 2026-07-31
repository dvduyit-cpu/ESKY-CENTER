<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_registrar')->default(false)->after('active')->index();
        });

        $moduleId = DB::table('modules')->where('code', 'language_classes')->value('id');
        $query = DB::table('users as user')
            ->leftJoin('roles as role', 'role.id', '=', 'user.role_id')
            ->where(fn ($builder) => $builder->where('role.code', 'admin'));
        if ($moduleId) {
            $query->leftJoin('user_permissions as user_permission', function ($join) use ($moduleId) {
                $join->on('user_permission.user_id', '=', 'user.id')->where('user_permission.module_id', $moduleId);
            })->leftJoin('role_permissions as role_permission', function ($join) use ($moduleId) {
                $join->on('role_permission.role_id', '=', 'user.role_id')->where('role_permission.module_id', $moduleId);
            })->orWhere(function ($builder) {
                $builder->where('role.code', '!=', 'teacher')
                    ->whereRaw('COALESCE(user_permission.can_update, role_permission.can_update, 0) = 1');
            });
        }

        $query->select('user.id')->orderBy('user.id')->pluck('user.id')->chunk(500)->each(function ($ids) {
            DB::table('users')->whereIn('id', $ids)->update(['is_registrar' => true]);
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_registrar'));
    }
};
