<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = fn (string $table): array => collect(Schema::getIndexes($table))->pluck('name')->all();

        if (in_array('administrative_weekly_periods_week_start_unique', $indexes('administrative_weekly_periods'), true)) {
            Schema::table('administrative_weekly_periods', fn (Blueprint $table) => $table->dropUnique('administrative_weekly_periods_week_start_unique'));
        }
        if (! in_array('administrative_weekly_periods_week_start_index', $indexes('administrative_weekly_periods'), true)) {
            Schema::table('administrative_weekly_periods', fn (Blueprint $table) => $table->index('week_start'));
        }

        if (! Schema::hasColumn('administrative_weekly_reports', 'period_id')) {
            Schema::table('administrative_weekly_reports', fn (Blueprint $table) => $table->foreignId('period_id')->nullable()->after('id')->constrained('administrative_weekly_periods')->cascadeOnDelete());
        }
        if (! Schema::hasColumn('administrative_weekly_compilations', 'period_id')) {
            Schema::table('administrative_weekly_compilations', fn (Blueprint $table) => $table->foreignId('period_id')->nullable()->after('id')->constrained('administrative_weekly_periods')->cascadeOnDelete());
        }

        DB::statement('UPDATE administrative_weekly_reports r INNER JOIN administrative_weekly_periods p ON p.week_start = r.week_start SET r.period_id = p.id WHERE r.period_id IS NULL');
        DB::statement('UPDATE administrative_weekly_compilations c INNER JOIN administrative_weekly_periods p ON p.week_start = c.week_start SET c.period_id = p.id WHERE c.period_id IS NULL');

        if (! in_array('administrative_weekly_reports_user_lookup', $indexes('administrative_weekly_reports'), true)) {
            Schema::table('administrative_weekly_reports', fn (Blueprint $table) => $table->index('user_id', 'administrative_weekly_reports_user_lookup'));
        }
        if (in_array('administrative_weekly_reports_user_id_week_start_unique', $indexes('administrative_weekly_reports'), true)) {
            Schema::table('administrative_weekly_reports', fn (Blueprint $table) => $table->dropUnique('administrative_weekly_reports_user_id_week_start_unique'));
        }
        if (! in_array('administrative_weekly_reports_user_id_period_id_unique', $indexes('administrative_weekly_reports'), true)) {
            Schema::table('administrative_weekly_reports', fn (Blueprint $table) => $table->unique(['user_id', 'period_id']));
        }
        if (in_array('administrative_weekly_compilations_week_start_unique', $indexes('administrative_weekly_compilations'), true)) {
            Schema::table('administrative_weekly_compilations', fn (Blueprint $table) => $table->dropUnique('administrative_weekly_compilations_week_start_unique'));
        }
        if (! in_array('administrative_weekly_compilations_period_id_unique', $indexes('administrative_weekly_compilations'), true)) {
            Schema::table('administrative_weekly_compilations', fn (Blueprint $table) => $table->unique('period_id'));
        }
    }

    public function down(): void
    {
        Schema::table('administrative_weekly_reports', function (Blueprint $table): void {
            $table->dropUnique('administrative_weekly_reports_user_id_period_id_unique');
            $table->dropConstrainedForeignId('period_id');
            $table->unique(['user_id', 'week_start']);
        });
        Schema::table('administrative_weekly_compilations', function (Blueprint $table): void {
            $table->dropUnique('administrative_weekly_compilations_period_id_unique');
            $table->dropConstrainedForeignId('period_id');
            $table->unique('week_start');
        });
        Schema::table('administrative_weekly_periods', function (Blueprint $table): void {
            $table->dropIndex('administrative_weekly_periods_week_start_index');
            $table->unique('week_start');
        });
    }
};
