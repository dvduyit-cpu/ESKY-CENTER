<?php

namespace Tests\Feature;

use App\Models\LanguageClass;
use App\Models\LanguageCourse;
use App\Models\LanguageLevel;
use App\Models\LanguageProgram;
use App\Models\LanguageStudent;
use App\Models\LanguageTuitionCharge;
use App\Models\LanguageTuitionPayment;
use App\Support\LanguageTuitionSpreadsheet;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class LanguageTuitionSpreadsheetImportTest extends TestCase
{
    use DatabaseTransactions;

    public function test_import_confirms_new_payment_when_receipt_code_is_present(): void
    {
        [$student, $class, $charge] = $this->createChargeFixture('NEW01');

        $spreadsheet = app(LanguageTuitionSpreadsheet::class);
        $result = $spreadsheet->import($this->makeUpload([
            ['1', $class->code, $charge->code, $student->name, '15/08/2012', 'PT-IMPORT-001', '05/08/2026 09:30', '1200000', '50000', 'Tien mat', '', '', 'Import moi'],
        ]));

        $payment = LanguageTuitionPayment::query()->where('receipt_code', 'PT-IMPORT-001')->firstOrFail();
        $charge->refresh();

        $this->assertSame(1, $result['created']);
        $this->assertSame(0, $result['failed']);
        $this->assertSame('confirmed', $payment->receipt_status);
        $this->assertNotNull($payment->confirmed_at);
        $this->assertSame('paid', $charge->status);
        $this->assertSame(1200000.0, (float) $charge->paid_amount);
        $this->assertDatabaseHas('language_monthly_target_records', [
            'language_tuition_payment_id' => $payment->id,
            'record_year' => 2026,
            'record_month' => 8,
            'language_student_id' => $student->id,
        ]);
    }

    public function test_import_confirms_existing_pending_payment_when_receipt_code_is_added(): void
    {
        [$student, $class, $charge] = $this->createChargeFixture('UPD01');

        $pendingPayment = $charge->payments()->create([
            'receipt_code' => null,
            'receipt_status' => 'pending',
            'confirmed_at' => null,
            'amount' => 1200000,
            'book_amount' => 0,
            'paid_at' => '2026-08-06 10:00:00',
            'payment_method' => 'cash',
            'collected_by' => null,
        ]);
        $charge->update([
            'status' => 'pending_receipt',
            'paid_amount' => 0,
        ]);

        $spreadsheet = app(LanguageTuitionSpreadsheet::class);
        $result = $spreadsheet->import($this->makeUpload([
            ['1', $class->code, $charge->code, $student->name, '15/08/2012', 'PT-IMPORT-002', '06/08/2026 10:00', '', '', 'Tien mat', '', '', 'Bo sung phieu'],
        ]));

        $pendingPayment->refresh();
        $charge->refresh();

        $this->assertSame(1, $result['updated']);
        $this->assertSame('confirmed', $pendingPayment->receipt_status);
        $this->assertNotNull($pendingPayment->confirmed_at);
        $this->assertSame('PT-IMPORT-002', $pendingPayment->receipt_code);
        $this->assertSame('paid', $charge->status);
        $this->assertSame(1200000.0, (float) $charge->paid_amount);
        $this->assertDatabaseHas('language_monthly_target_records', [
            'language_tuition_payment_id' => $pendingPayment->id,
            'record_year' => 2026,
            'record_month' => 8,
            'language_student_id' => $student->id,
        ]);
    }

    private function createChargeFixture(string $suffix): array
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

        $class = LanguageClass::create([
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

        $student = LanguageStudent::create([
            'code' => 'HV-'.$suffix,
            'name' => 'Hoc vien '.$suffix,
            'date_of_birth' => '2012-08-15',
            'registered_at' => '2026-08-01',
            'status' => 'studying',
            'language_course_id' => $course->id,
        ]);

        $charge = LanguageTuitionCharge::create([
            'code' => 'HP-'.$suffix,
            'language_student_id' => $student->id,
            'language_course_id' => $course->id,
            'language_class_id' => $class->id,
            'original_amount' => 1200000,
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'payable_amount' => 1200000,
            'paid_amount' => 0,
            'credit_amount' => 0,
            'status' => 'unpaid',
        ]);

        return [$student, $class, $charge];
    }

    /**
     * @param array<int, array<int, string>> $rows
     */
    private function makeUpload(array $rows): UploadedFile
    {
        $header = 'STT,MA LOP,MA KHOAN THU,HO TEN,NGAY SINH,SO PHIEU THU,NGAY THU,SO TIEN HOC PHI,TIEN SACH,HINH THUC,THU NUA LOP,TY LE THU (%),GHI CHU';
        $body = array_map(static fn (array $row) => implode(',', $row), $rows);

        return UploadedFile::fake()->createWithContent('tuition-import.csv', $header."\n".implode("\n", $body));
    }
}
