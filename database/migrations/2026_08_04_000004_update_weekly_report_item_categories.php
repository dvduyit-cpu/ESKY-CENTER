<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('administrative_weekly_report_items')
            ->where('type', 'issues')
            ->update(['type' => 'other_work']);
    }

    public function down(): void
    {
        DB::table('administrative_weekly_report_items')
            ->where('type', 'other_work')
            ->update(['type' => 'issues']);
    }
};
