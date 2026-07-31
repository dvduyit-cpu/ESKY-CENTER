<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('language_students', function (Blueprint $table) {
            $table->date('registered_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('language_students')
            ->whereNull('registered_at')
            ->update(['registered_at' => DB::raw('DATE(created_at)')]);

        Schema::table('language_students', function (Blueprint $table) {
            $table->date('registered_at')->nullable(false)->change();
        });
    }
};
