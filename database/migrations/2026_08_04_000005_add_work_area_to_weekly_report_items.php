<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('administrative_weekly_report_items', function (Blueprint $table) {
            $table->string('work_area', 40)->default('other')->after('type');
            $table->index(['work_area', 'report_id']);
        });
    }

    public function down(): void
    {
        Schema::table('administrative_weekly_report_items', function (Blueprint $table) {
            $table->dropIndex(['work_area', 'report_id']);
            $table->dropColumn('work_area');
        });
    }
};
