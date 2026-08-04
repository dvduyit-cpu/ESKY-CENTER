<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrative_weekly_period_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_id')->constrained('administrative_weekly_periods')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['period_id', 'user_id']);
        });

        $now = now();
        $userIds = DB::table('users')
            ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
            ->where('users.active', true)
            ->whereNull('users.deleted_at')
            ->where(fn ($query) => $query->whereNull('roles.code')->orWhere('roles.code', '!=', 'admin'))
            ->pluck('users.id');

        DB::table('administrative_weekly_periods')->pluck('id')->each(function ($periodId) use ($userIds, $now): void {
            DB::table('administrative_weekly_period_user')->insertOrIgnore($userIds->map(fn ($userId) => [
                'period_id' => $periodId,
                'user_id' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administrative_weekly_period_user');
    }
};
