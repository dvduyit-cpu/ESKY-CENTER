<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_targets', function (Blueprint $table) {
            $table->decimal('assigned_teaching_load', 10, 2)->default(0)->after('target_quantity');
        });

        Schema::create('kpi_teaching_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('kpi_plans')->cascadeOnDelete();
            $table->foreignId('kpi_target_id')->nullable()->constrained('kpi_targets')->nullOnDelete();
            $table->foreignId('personnel_id')->constrained('personnels')->cascadeOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('report_year');
            $table->unsignedTinyInteger('report_month');
            $table->decimal('reported_teaching_load', 10, 2)->default(0);
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(
                ['plan_id', 'personnel_id', 'report_year', 'report_month'],
                'kpi_teaching_reports_month_unique'
            );
            $table->index(['report_year', 'report_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_teaching_reports');

        Schema::table('kpi_targets', function (Blueprint $table) {
            $table->dropColumn('assigned_teaching_load');
        });
    }
};
