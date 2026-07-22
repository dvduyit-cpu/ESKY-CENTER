<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('work_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->dateTime('due_at');
            $table->string('priority', 20)->default('normal');
            $table->timestamps();
            $table->index(['created_by_id', 'due_at']);
        });

        Schema::create('work_task_assignees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_lead')->default(false);
            $table->dateTime('acknowledged_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['work_task_id', 'user_id']);
        });

        Schema::create('work_task_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('work_task_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 40);
            $table->text('description');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_task_activities');
        Schema::dropIfExists('work_task_comments');
        Schema::dropIfExists('work_task_assignees');
        Schema::dropIfExists('work_tasks');
    }
};
