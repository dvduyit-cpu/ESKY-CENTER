<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('language_programs', function (Blueprint $table) {
            $table->id(); $table->string('code', 30)->unique(); $table->string('name');
            $table->string('audience')->nullable(); $table->text('description')->nullable();
            $table->boolean('active')->default(true); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('language_levels', function (Blueprint $table) {
            $table->id(); $table->foreignId('language_program_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30)->unique(); $table->string('name'); $table->unsignedSmallInteger('expected_sessions')->default(0);
            $table->decimal('expected_hours', 8, 2)->default(0); $table->decimal('default_tuition', 14, 2)->default(0);
            $table->decimal('passing_score', 5, 2)->nullable(); $table->text('description')->nullable(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('language_leads', function (Blueprint $table) {
            $table->id(); $table->string('code', 30)->unique(); $table->string('name'); $table->date('date_of_birth')->nullable();
            $table->string('phone', 30); $table->string('email')->nullable(); $table->string('zalo')->nullable();
            $table->string('source')->nullable(); $table->foreignId('language_program_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('consultant_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('appointment_at')->nullable(); $table->string('status', 30)->default('new');
            $table->text('consultation')->nullable(); $table->text('note')->nullable(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('language_students', function (Blueprint $table) {
            $table->id(); $table->string('code', 30)->unique(); $table->string('name'); $table->string('gender', 20)->nullable();
            $table->date('date_of_birth')->nullable(); $table->string('school')->nullable(); $table->string('school_class')->nullable();
            $table->string('phone', 30)->nullable(); $table->string('email')->nullable(); $table->string('address')->nullable();
            $table->date('registered_at'); $table->string('source')->nullable(); $table->string('status', 30)->default('new');
            $table->text('note')->nullable(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('language_guardians', function (Blueprint $table) {
            $table->id(); $table->foreignId('language_student_id')->constrained()->cascadeOnDelete(); $table->string('name');
            $table->string('relationship', 50)->nullable(); $table->string('phone', 30); $table->string('email')->nullable();
            $table->string('zalo')->nullable(); $table->boolean('is_primary')->default(false); $table->timestamps();
        });
        Schema::create('language_classes', function (Blueprint $table) {
            $table->id(); $table->string('code', 30)->unique(); $table->string('name');
            $table->foreignId('language_program_id')->constrained()->restrictOnDelete();
            $table->foreignId('language_level_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('teacher_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('room')->nullable(); $table->date('start_date')->nullable(); $table->date('expected_end_date')->nullable();
            $table->unsignedSmallInteger('expected_sessions')->default(0); $table->unsignedSmallInteger('max_students')->default(20);
            $table->decimal('default_tuition', 14, 2)->default(0); $table->string('status', 30)->default('recruiting');
            $table->text('schedule_note')->nullable(); $table->text('note')->nullable(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('language_enrollments', function (Blueprint $table) {
            $table->id(); $table->foreignId('language_class_id')->constrained()->cascadeOnDelete();
            $table->foreignId('language_student_id')->constrained()->cascadeOnDelete(); $table->date('enrolled_at');
            $table->decimal('tuition', 14, 2)->default(0); $table->decimal('discount', 14, 2)->default(0);
            $table->string('status', 30)->default('studying'); $table->text('note')->nullable(); $table->timestamps();
            $table->unique(['language_class_id', 'language_student_id'], 'lang_enrollment_class_student_uq');
        });

        $now = now();
        foreach ([
            ['language_leads', 'Học viên tiềm năng', 'bi-person-plus', 20],
            ['language_students', 'Học viên', 'bi-mortarboard', 21],
            ['language_programs', 'Chương trình & cấp độ', 'bi-journal-richtext', 22],
            ['language_classes', 'Lớp học', 'bi-easel2', 23],
        ] as [$code, $name, $icon, $sort]) {
            DB::table('modules')->updateOrInsert(['code' => $code], ['name' => $name, 'icon' => $icon, 'sort_order' => $sort, 'updated_at' => $now, 'created_at' => $now]);
            $moduleId = DB::table('modules')->where('code', $code)->value('id');
            foreach (DB::table('roles')->get(['id', 'code']) as $role) {
                $admin = $role->code === 'admin';
                DB::table('role_permissions')->updateOrInsert(['role_id' => $role->id, 'module_id' => $moduleId], [
                    'can_view' => $admin, 'can_create' => $admin, 'can_update' => $admin, 'can_delete' => $admin, 'can_export' => $admin,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('modules')->whereIn('code', ['language_leads','language_students','language_programs','language_classes'])->pluck('id');
        DB::table('role_permissions')->whereIn('module_id', $ids)->delete();
        DB::table('modules')->whereIn('id', $ids)->delete();
        Schema::dropIfExists('language_enrollments'); Schema::dropIfExists('language_classes'); Schema::dropIfExists('language_guardians');
        Schema::dropIfExists('language_students'); Schema::dropIfExists('language_leads'); Schema::dropIfExists('language_levels'); Schema::dropIfExists('language_programs');
    }
};
