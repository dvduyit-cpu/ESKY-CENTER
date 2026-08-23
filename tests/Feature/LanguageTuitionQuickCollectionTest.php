<?php

namespace Tests\Feature;

use App\Models\LanguageCourse;
use App\Models\LanguageLevel;
use App\Models\LanguageProgram;
use App\Models\LanguageStudent;
use App\Models\LanguageTuitionCharge;
use App\Models\LanguageTuitionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LanguageTuitionQuickCollectionTest extends TestCase
{
    use DatabaseTransactions;

    public function test_can_quick_collect_tuition_and_redirect_to_print(): void
    {
        $this->withoutMiddleware();

        $user = User::create([
            'name' => 'Quick Cashier',
            'email' => 'quick-cashier@example.test',
            'password' => 'secret123',
            'active' => true,
            'must_change_password' => false,
        ]);

        [$student, $course] = $this->createStudentAndCourse('QC01');

        $response = $this->actingAs($user)->post(route('language-tuition.store'), [
            'entry_mode' => 'quick',
            'language_student_id' => $student->id,
            'language_course_id' => $course->id,
            'original_amount' => 1850000,
            'collected_amount' => 1850000,
            'receipt_code' => 'PT-QUICK-001',
            'paid_at' => '2026-08-23 09:30:00',
            'payment_method' => 'cash',
            'reference' => 'MECOS-01',
            'note' => 'Thu nhanh khoa hoc tu chon',
            'submit_action' => 'print',
        ]);

        $payment = LanguageTuitionPayment::query()->where('receipt_code', 'PT-QUICK-001')->firstOrFail();
        $charge = LanguageTuitionCharge::query()->findOrFail($payment->language_tuition_charge_id);

        $response->assertRedirect(route('language-tuition.receipt.print', $payment));

        $this->assertNull($charge->language_class_id);
        $this->assertSame($student->id, $charge->language_student_id);
        $this->assertSame($course->id, $charge->language_course_id);
        $this->assertSame('paid', $charge->status);

        $this->assertDatabaseHas('language_tuition_charges', [
            'id' => $charge->id,
            'language_student_id' => $student->id,
            'language_course_id' => $course->id,
            'language_class_id' => null,
        ]);

        $this->assertDatabaseHas('language_tuition_payments', [
            'id' => $payment->id,
            'language_tuition_charge_id' => $charge->id,
            'receipt_code' => 'PT-QUICK-001',
            'payment_method' => 'cash',
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
            'default_tuition' => 1850000,
        ]);

        $course = LanguageCourse::create([
            'code' => 'CRS-'.$suffix,
            'name' => 'Course '.$suffix,
            'language_program_id' => $program->id,
            'language_level_id' => $level->id,
            'tuition' => 1850000,
            'sessions' => 12,
            'active' => true,
        ]);

        $student = LanguageStudent::create([
            'code' => 'HV-'.$suffix,
            'name' => 'Hoc vien '.$suffix,
            'registered_at' => '2026-08-01',
            'status' => 'studying',
            'phone' => '0912345678',
            'email' => 'student-'.$suffix.'@example.test',
            'language_course_id' => $course->id,
        ]);

        return [$student, $course];
    }
}
