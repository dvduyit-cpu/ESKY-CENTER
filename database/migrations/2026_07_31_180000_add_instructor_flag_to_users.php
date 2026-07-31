<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_instructor')->default(false)->after('is_registrar')->index();
        });

        DB::table('users')
            ->whereIn('role_id', DB::table('roles')->where('code', 'teacher')->select('id'))
            ->update(['is_instructor' => true]);
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_instructor'));
    }
};
