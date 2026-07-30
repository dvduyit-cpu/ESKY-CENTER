<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('work_task_attachments', function (Blueprint $table) {
            $table->foreignId('work_task_comment_id')
                ->nullable()
                ->after('work_task_id')
                ->constrained()
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('work_task_attachments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('work_task_comment_id');
        });
    }
};
