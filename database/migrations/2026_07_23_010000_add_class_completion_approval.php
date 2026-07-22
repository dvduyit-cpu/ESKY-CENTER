<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('language_classes', function (Blueprint $table) {
            $table->timestamp('completion_requested_at')->nullable()->after('completed_sessions');
            $table->foreignId('completion_requested_by')->nullable()->after('completion_requested_at')->constrained('users')->nullOnDelete();
            $table->text('completion_note')->nullable()->after('completion_requested_by');
            $table->timestamp('completed_at')->nullable()->after('completion_note');
            $table->foreignId('completed_by')->nullable()->after('completed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('language_classes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('completed_by');
            $table->dropConstrainedForeignId('completion_requested_by');
            $table->dropColumn(['completion_requested_at','completion_note','completed_at']);
        });
    }
};
