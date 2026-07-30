<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('work_task_comments', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('user_id');
            $table->string('reply_to_user_name')->nullable()->after('parent_id');
            $table->string('reply_excerpt', 500)->nullable()->after('reply_to_user_name');
            $table->index('parent_id');
            $table->foreign('parent_id')
                ->references('id')
                ->on('work_task_comments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('work_task_comments', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['parent_id']);
            $table->dropColumn(['parent_id', 'reply_to_user_name', 'reply_excerpt']);
        });
    }
};
