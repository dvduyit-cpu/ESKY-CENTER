<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('language_classes', function (Blueprint $table) {
            $table->time('default_start_time')->default('18:00:00')->after('expected_end_date');
            $table->time('default_end_time')->default('19:30:00')->after('default_start_time');
        });
    }

    public function down(): void
    {
        Schema::table('language_classes', function (Blueprint $table) {
            $table->dropColumn(['default_start_time', 'default_end_time']);
        });
    }
};
