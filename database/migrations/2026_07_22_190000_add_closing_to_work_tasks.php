<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_tasks', function (Blueprint $table) {
            $table->dateTime('closed_at')->nullable()->after('due_at')->index();
            $table->foreignId('closed_by_id')->nullable()->after('created_by_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('work_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('closed_by_id');
            $table->dropColumn('closed_at');
        });
    }
};
