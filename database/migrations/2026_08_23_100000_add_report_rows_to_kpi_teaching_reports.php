<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_teaching_reports', function (Blueprint $table) {
            $table->json('report_rows')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_teaching_reports', function (Blueprint $table) {
            $table->dropColumn('report_rows');
        });
    }
};
