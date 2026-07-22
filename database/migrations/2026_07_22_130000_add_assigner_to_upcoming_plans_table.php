<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('upcoming_plans', function (Blueprint $table) {
            $table->foreignId('assigned_by_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('upcoming_plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_by_id');
        });
    }
};
