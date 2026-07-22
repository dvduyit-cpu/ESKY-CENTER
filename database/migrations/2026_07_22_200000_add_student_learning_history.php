<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('language_enrollments', function (Blueprint $table) {
            $table->date('ended_at')->nullable()->after('enrolled_at');
            $table->string('exit_reason')->nullable()->after('status');
        });

        Schema::create('language_student_monthly_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_enrollment_id')->constrained('language_enrollments')->cascadeOnDelete();
            $table->date('month');
            $table->unsignedSmallInteger('planned_sessions')->default(0);
            $table->unsignedSmallInteger('attended_sessions')->default(0);
            $table->decimal('participation_score', 5, 2)->nullable();
            $table->decimal('homework_score', 5, 2)->nullable();
            $table->text('assessment')->nullable();
            $table->text('learning_note')->nullable();
            $table->foreignId('teacher_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['language_enrollment_id', 'month'], 'student_progress_enrollment_month_uq');
        });

        Schema::create('language_student_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_enrollment_id')->constrained('language_enrollments')->cascadeOnDelete();
            $table->date('test_date');
            $table->string('test_name');
            $table->string('test_type')->default('regular');
            $table->decimal('score', 6, 2);
            $table->decimal('max_score', 6, 2)->default(10);
            $table->text('note')->nullable();
            $table->foreignId('teacher_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['language_enrollment_id', 'test_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('language_student_scores');
        Schema::dropIfExists('language_student_monthly_progress');
        Schema::table('language_enrollments', fn (Blueprint $table) => $table->dropColumn(['ended_at', 'exit_reason']));
    }
};
