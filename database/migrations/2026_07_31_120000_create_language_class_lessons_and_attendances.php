<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('language_class_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_class_id')->constrained('language_classes')->cascadeOnDelete();
            $table->date('lesson_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->text('content')->nullable();
            $table->text('evaluation')->nullable();
            $table->string('teacher_signature')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('teacher_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('attendance_marked_at')->nullable();
            $table->foreignId('attendance_marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['language_class_id', 'lesson_date', 'start_time'], 'language_class_lesson_slot_uq');
            $table->index(['language_class_id', 'lesson_date']);
        });

        Schema::create('language_class_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_class_lesson_id')->constrained('language_class_lessons')->cascadeOnDelete();
            $table->foreignId('language_enrollment_id')->constrained('language_enrollments')->cascadeOnDelete();
            $table->string('status', 20)->default('present');
            $table->string('note')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['language_class_lesson_id', 'language_enrollment_id'], 'language_attendance_lesson_enrollment_uq');
            $table->index(['language_enrollment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('language_class_attendances');
        Schema::dropIfExists('language_class_lessons');
    }
};
