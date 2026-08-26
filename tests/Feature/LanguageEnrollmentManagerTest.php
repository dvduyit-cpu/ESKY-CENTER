<?php

namespace Tests\Feature;

use App\Models\LanguageClass;
use App\Models\LanguageClassTransfer;
use App\Models\LanguageCourse;
use App\Models\LanguageEnrollment;
use App\Models\LanguageLevel;
use App\Models\LanguageProgram;
use App\Models\LanguageStudent;
use App\Models\LanguageTuitionCharge;
use App\Support\LanguageEnrollmentManager;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LanguageEnrollmentManagerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_unenroll_keeps_target_charge_when_transfer_history_points_to_it(): void
    {
        [$student, $course] = $this->createStudentAndCourse('001');
        $sourceClass = $this->createClass($course, '001A');
        $targetClass = $this->createClass($course, '001B');

        $sourceEnrollment = LanguageEnrollment::create([
            'language_class_id' => $sourceClass->id,
            'language_student_id' => $student->id,
            'enrolled_at' => '2026-08-01',
            'tuition' => 1200000,
            'discount' => 0,
            'status' => 'dropped',
            'ended_at' => '2026-08-10',
            'exit_reason' => 'Chuyen lop',
        ]);

        $targetEnrollment = LanguageEnrollment::create([
            'language_class_id' => $targetClass->id,
            'language_student_id' => $student->id,
            'enrolled_at' => '2026-08-10',
            'tuition' => 1200000,
            'discount' => 0,
            'status' => 'studying',
        ]);

        $targetCharge = LanguageTuitionCharge::create([
            'code' => 'HP-TARGET-001',
            'language_student_id' => $student->id,
            'language_course_id' => $course->id,
            'language_class_id' => $targetClass->id,
            'original_amount' => 1200000,
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'payable_amount' => 1200000,
            'paid_amount' => 0,
            'credit_amount' => 0,
            'due_date' => '2026-08-10',
            'status' => 'unpaid',
        ]);

        LanguageClassTransfer::create([
            'language_student_id' => $student->id,
            'from_language_class_id' => $sourceClass->id,
            'to_language_class_id' => $targetClass->id,
            'from_enrollment_id' => $sourceEnrollment->id,
            'to_enrollment_id' => $targetEnrollment->id,
            'from_tuition_charge_id' => null,
            'to_tuition_charge_id' => $targetCharge->id,
            'effective_date' => '2026-08-10',
            'sessions_used' => 0,
            'source_payable_amount' => 0,
            'source_paid_amount' => 0,
            'source_credit_amount' => 0,
            'used_amount' => 0,
            'transferred_amount' => 0,
            'applied_amount' => 0,
            'surplus_amount' => 0,
        ]);

        $deleted = app(LanguageEnrollmentManager::class)->unenroll($targetClass, $targetEnrollment);

        $this->assertFalse($deleted);
        $this->assertDatabaseHas('language_tuition_charges', ['id' => $targetCharge->id]);
        $this->assertDatabaseHas('language_enrollments', [
            'id' => $targetEnrollment->id,
            'status' => 'dropped',
            'exit_reason' => 'Giáo vụ đưa khỏi lớp',
        ]);
    }

    public function test_unenroll_still_deletes_unpaid_charge_without_transfer_history(): void
    {
        [$student, $course] = $this->createStudentAndCourse('002');
        $class = $this->createClass($course, '002A');

        $enrollment = LanguageEnrollment::create([
            'language_class_id' => $class->id,
            'language_student_id' => $student->id,
            'enrolled_at' => '2026-08-05',
            'tuition' => 950000,
            'discount' => 0,
            'status' => 'studying',
        ]);

        $charge = LanguageTuitionCharge::create([
            'code' => 'HP-DELETE-002',
            'language_student_id' => $student->id,
            'language_course_id' => $course->id,
            'language_class_id' => $class->id,
            'original_amount' => 950000,
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'payable_amount' => 950000,
            'paid_amount' => 0,
            'credit_amount' => 0,
            'due_date' => '2026-08-05',
            'status' => 'unpaid',
        ]);

        $deleted = app(LanguageEnrollmentManager::class)->unenroll($class, $enrollment);

        $this->assertTrue($deleted);
        $this->assertDatabaseMissing('language_tuition_charges', ['id' => $charge->id]);
        $this->assertDatabaseHas('language_enrollments', [
            'id' => $enrollment->id,
            'status' => 'dropped',
            'exit_reason' => 'Giáo vụ đưa khỏi lớp',
        ]);
    }

    public function test_enroll_rejects_reusing_a_class_with_dropped_history(): void
    {
        [$student, $course] = $this->createStudentAndCourse('003');
        $class = $this->createClass($course, '003A');

        $enrollment = LanguageEnrollment::create([
            'language_class_id' => $class->id,
            'language_student_id' => $student->id,
            'enrolled_at' => '2026-08-01',
            'tuition' => 1200000,
            'discount' => 0,
            'status' => 'dropped',
            'ended_at' => '2026-08-10',
            'exit_reason' => 'Gi lich su cu',
        ]);

        try {
            app(LanguageEnrollmentManager::class)->enroll($class, $student, '2026-08-20');
            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('class', $exception->errors());
        }

        $this->assertDatabaseHas('language_enrollments', [
            'id' => $enrollment->id,
            'status' => 'dropped',
            'ended_at' => '2026-08-10',
        ]);
    }

    public function test_enroll_reopens_existing_active_enrollment_and_sets_student_back_to_studying(): void
    {
        [$student, $course] = $this->createStudentAndCourse('004');
        $student->update([
            'status' => 'paused',
            'official_enrollment_date' => '2026-08-01',
        ]);

        $class = $this->createClass($course, '004A');
        $enrollment = LanguageEnrollment::create([
            'language_class_id' => $class->id,
            'language_student_id' => $student->id,
            'enrolled_at' => '2026-08-01',
            'tuition' => 1200000,
            'discount' => 0,
            'status' => 'paused',
            'ended_at' => '2026-08-12',
            'exit_reason' => 'Tam nghi',
        ]);

        LanguageTuitionCharge::create([
            'code' => 'HP-REOPEN-004',
            'language_student_id' => $student->id,
            'language_course_id' => $course->id,
            'language_class_id' => $class->id,
            'original_amount' => 1200000,
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'payable_amount' => 1200000,
            'paid_amount' => 0,
            'credit_amount' => 0,
            'due_date' => '2026-08-01',
            'status' => 'unpaid',
        ]);

        app(LanguageEnrollmentManager::class)->enroll($class, $student->fresh(), '2026-08-20');

        $this->assertDatabaseHas('language_enrollments', [
            'id' => $enrollment->id,
            'status' => 'studying',
            'ended_at' => null,
            'exit_reason' => null,
        ]);
        $this->assertDatabaseHas('language_students', [
            'id' => $student->id,
            'status' => 'studying',
        ]);
    }

    private function createStudentAndCourse(string $suffix): array
    {
        $program = LanguageProgram::create([
            'code' => 'PRG-'.$suffix,
            'name' => 'Program '.$suffix,
            'active' => true,
        ]);

        $level = LanguageLevel::create([
            'language_program_id' => $program->id,
            'code' => 'LVL-'.$suffix,
            'name' => 'Level '.$suffix,
            'expected_sessions' => 12,
            'default_tuition' => 1200000,
        ]);

        $course = LanguageCourse::create([
            'code' => 'CRS-'.$suffix,
            'name' => 'Course '.$suffix,
            'language_program_id' => $program->id,
            'language_level_id' => $level->id,
            'tuition' => 1200000,
            'sessions' => 12,
            'active' => true,
        ]);

        $student = LanguageStudent::create([
            'code' => 'HV-'.$suffix,
            'name' => 'Hoc vien '.$suffix,
            'registered_at' => '2026-08-01',
            'status' => 'studying',
            'language_course_id' => $course->id,
        ]);

        return [$student, $course];
    }

    private function createClass(LanguageCourse $course, string $suffix): LanguageClass
    {
        return LanguageClass::create([
            'code' => 'CLS-'.$suffix,
            'name' => 'Class '.$suffix,
            'language_program_id' => $course->language_program_id,
            'language_level_id' => $course->language_level_id,
            'language_course_id' => $course->id,
            'start_date' => '2026-08-01',
            'expected_end_date' => '2026-09-30',
            'default_start_time' => '18:00:00',
            'default_end_time' => '19:30:00',
            'expected_sessions' => 12,
            'max_students' => 20,
            'default_tuition' => $course->tuition,
            'status' => 'active',
        ]);
    }
}
