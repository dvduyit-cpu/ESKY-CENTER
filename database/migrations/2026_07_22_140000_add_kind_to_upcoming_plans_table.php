<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('upcoming_plans', function (Blueprint $table) {
            $table->string('kind', 20)->default('personal')->after('priority')->index();
        });
        DB::table('upcoming_plans')->whereColumn('assigned_by_id', '<>', 'user_id')->update(['kind' => 'task']);
    }

    public function down(): void
    {
        Schema::table('upcoming_plans', function (Blueprint $table) {
            $table->dropIndex(['kind']);
            $table->dropColumn('kind');
        });
    }
};
