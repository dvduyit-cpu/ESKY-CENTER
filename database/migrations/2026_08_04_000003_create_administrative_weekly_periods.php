<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrative_weekly_periods', function (Blueprint $table) {
            $table->id();
            $table->date('week_start')->unique();
            $table->date('week_end');
            $table->date('due_date');
            $table->string('title', 180)->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
        });

        $now = now();
        DB::table('administrative_weekly_reports')
            ->select(['week_start', 'week_end', 'due_date'])
            ->distinct()
            ->orderBy('week_start')
            ->get()
            ->each(function ($report) use ($now): void {
                DB::table('administrative_weekly_periods')->insertOrIgnore([
                    'week_start' => $report->week_start,
                    'week_end' => $report->week_end,
                    'due_date' => $report->due_date,
                    'is_active' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('administrative_weekly_periods');
    }
};
