<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('administrative_weekly_periods', function (Blueprint $table) {
            $table->timestamp('starts_at')->nullable()->after('is_active');
            $table->timestamp('ends_at')->nullable()->after('starts_at');
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::table('administrative_weekly_periods', function (Blueprint $table) {
            $table->dropIndex(['starts_at', 'ends_at']);
            $table->dropColumn(['starts_at', 'ends_at']);
        });
    }
};
