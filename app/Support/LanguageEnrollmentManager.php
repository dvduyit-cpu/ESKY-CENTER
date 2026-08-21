<?php

namespace App\Support;

use App\Models\LanguageClass;
use App\Models\LanguageCourse;
use App\Models\LanguageDiscountPolicy;
use App\Models\LanguageEnrollment;
use App\Models\LanguageClassTransfer;
use App\Models\LanguageLead;
use App\Models\LanguageStudent;
use App\Models\LanguageTuitionCharge;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LanguageEnrollmentManager
{
    public function enroll(
        LanguageClass $class,
        LanguageStudent $student,
        string|Carbon $enrolledAt,
        ?int $createdBy = null,
        string $enrollmentStatus = 'studying'
    ): LanguageEnrollment {
        $date = $enrolledAt instanceof Carbon ? $enrolledAt->copy()->startOfDay() : Carbon::parse($enrolledAt)->startOfDay();

        return DB::transaction(function () use ($class, $student, $date, $createdBy, $enrollmentStatus) {
            $lockedClass = LanguageClass::query()->lockForUpdate()->findOrFail($class->id);
            if (in_array($lockedClass->status, ['completed', 'cancelled'], true)) {
                throw ValidationException::withMessages(['class' => 'Không thể xếp học viên vào lớp đã kết thúc.']);
            }
            if (! $lockedClass->language_course_id) {
                throw ValidationException::withMessages(['class' => 'Lớp chưa được liên kết với khóa học.']);
            }

            $existing = LanguageEnrollment::query()
                ->where('language_class_id', $lockedClass->id)
                ->where('language_student_id', $student->id)
                ->first();
            if ($enrollmentStatus === 'studying' && (! $existing || $existing->status !== 'studying')
                && $lockedClass->enrollments()->where('status', 'studying')->count() >= $lockedClass->max_students) {
                throw ValidationException::withMessages(['class' => 'Lớp đã đủ sĩ số.']);
            }

            $course = LanguageCourse::findOrFail($lockedClass->language_course_id);
            $tuition = (float) $lockedClass->default_tuition;
            $enrollment = LanguageEnrollment::updateOrCreate(
                ['language_class_id' => $lockedClass->id, 'language_student_id' => $student->id],
                [
                    'enrolled_at' => $date->toDateString(),
                    'tuition' => $tuition,
                    'discount' => 0,
                    'status' => $enrollmentStatus,
                    'ended_at' => $enrollmentStatus === 'studying' ? null : now()->toDateString(),
                    'exit_reason' => null,
                ]
            );

            $studentUpdates = [
                'language_course_id' => $course->id,
            ];
            if (in_array($student->status, ['new', 'placement_test', 'waiting_class'], true)) {
                $studentUpdates['status'] = 'studying';
            }
            if (! $student->official_enrollment_date) {
                $studentUpdates['official_enrollment_date'] = $date->toDateString();
            }
            $student->update($studentUpdates);

            $this->ensureTuitionCharge($lockedClass, $student->fresh(), $course, $tuition, $date, $createdBy);

            return $enrollment;
        });
    }

    public function unenroll(LanguageClass $class, LanguageEnrollment $enrollment): bool
    {
        return DB::transaction(function () use ($class, $enrollment) {
            $lockedEnrollment = LanguageEnrollment::query()->lockForUpdate()->findOrFail($enrollment->id);
            abort_unless($lockedEnrollment->language_class_id === $class->id, 404);

            $charge = LanguageTuitionCharge::query()
                ->where('language_student_id', $lockedEnrollment->language_student_id)
                ->where('language_class_id', $class->id)
                ->lockForUpdate()
                ->first();
            $chargeDeleted = false;

            if (
                $charge
                && (float) $charge->paid_amount <= 0
                && (float) $charge->credit_amount <= 0
                && ! $charge->payments()->exists()
                && ! $charge->incomingTransfers()->exists()
            ) {
                $charge->delete();
                $chargeDeleted = true;
            }

            $lockedEnrollment->update([
                'status' => 'dropped',
                'ended_at' => now()->toDateString(),
                'exit_reason' => 'Giáo vụ đưa khỏi lớp',
            ]);

            return $chargeDeleted;
        });
    }

    public function transfer(
        LanguageClass $sourceClass,
        LanguageEnrollment $sourceEnrollment,
        LanguageClass $targetClass,
        string|Carbon $effectiveDate,
        int $sessionsUsed,
        ?int $createdBy = null,
        ?string $note = null
    ): LanguageClassTransfer {
        $date = $effectiveDate instanceof Carbon
            ? $effectiveDate->copy()->startOfDay()
            : Carbon::parse($effectiveDate)->startOfDay();

        return DB::transaction(function () use ($sourceClass, $sourceEnrollment, $targetClass, $date, $sessionsUsed, $createdBy, $note) {
            $lockedSourceClass = LanguageClass::query()->lockForUpdate()->findOrFail($sourceClass->id);
            $lockedTargetClass = LanguageClass::query()->lockForUpdate()->findOrFail($targetClass->id);
            $lockedEnrollment = LanguageEnrollment::query()->lockForUpdate()->findOrFail($sourceEnrollment->id);

            abort_unless($lockedEnrollment->language_class_id === $lockedSourceClass->id, 404);
            if (! in_array($lockedEnrollment->status, ['studying', 'paused', 'reserved'], true)) {
                throw ValidationException::withMessages(['student' => 'Học viên không còn ở trạng thái đang học, tạm nghỉ hoặc bảo lưu trong lớp cũ.']);
            }
            if ($lockedSourceClass->id === $lockedTargetClass->id) {
                throw ValidationException::withMessages(['to_language_class_id' => 'Lớp mới phải khác lớp hiện tại.']);
            }
            if (! in_array($lockedTargetClass->status, ['recruiting', 'upcoming', 'active'], true) || ! $lockedTargetClass->language_course_id) {
                throw ValidationException::withMessages(['to_language_class_id' => 'Chỉ có thể chuyển vào lớp đang tuyển, sắp khai giảng hoặc đang hoạt động.']);
            }
            if ($lockedTargetClass->enrollments()->where('status', 'studying')->count() >= $lockedTargetClass->max_students) {
                throw ValidationException::withMessages(['to_language_class_id' => 'Lớp mới đã đủ sĩ số.']);
            }
            if (LanguageEnrollment::query()->where('language_class_id', $lockedTargetClass->id)->where('language_student_id', $lockedEnrollment->language_student_id)->exists()) {
                throw ValidationException::withMessages(['to_language_class_id' => 'Học viên đã từng có hồ sơ trong lớp mới. Vui lòng chọn lớp khác để không ghi đè lịch sử cũ.']);
            }
            if (LanguageClassTransfer::query()->where('from_enrollment_id', $lockedEnrollment->id)->exists()) {
                throw ValidationException::withMessages(['student' => 'Hồ sơ lớp này đã được chuyển trước đó.']);
            }

            $student = LanguageStudent::query()->lockForUpdate()->findOrFail($lockedEnrollment->language_student_id);
            $sourceCharge = LanguageTuitionCharge::query()
                ->where('language_student_id', $lockedEnrollment->language_student_id)
                ->where('language_class_id', $lockedSourceClass->id)
                ->lockForUpdate()
                ->first();
            if (! $sourceCharge && $lockedSourceClass->language_course_id) {
                $sourceCourse = LanguageCourse::findOrFail($lockedSourceClass->language_course_id);
                $sourceTuition = (float) $lockedSourceClass->default_tuition;
                $sourceCharge = $this->ensureTuitionCharge($lockedSourceClass, $student, $sourceCourse, $sourceTuition, $lockedEnrollment->enrolled_at, $createdBy);
            }
            if ($sourceCharge?->payments()->where('receipt_status', 'pending')->exists()) {
                throw ValidationException::withMessages(['student' => 'Phiếu thu lớp cũ đang chờ xác nhận. Hãy xác nhận số phiếu trước khi chuyển lớp.']);
            }

            $targetCourse = LanguageCourse::findOrFail($lockedTargetClass->language_course_id);
            $targetTuition = (float) $lockedTargetClass->default_tuition;
            $targetEnrollment = LanguageEnrollment::create([
                'language_class_id' => $lockedTargetClass->id,
                'language_student_id' => $student->id,
                'enrolled_at' => $date->toDateString(),
                'tuition' => $targetTuition,
                'discount' => 0,
                'status' => 'studying',
            ]);
            $targetCharge = $this->ensureTuitionCharge($lockedTargetClass, $student, $targetCourse, $targetTuition, $date, $createdBy);

            $sourcePayable = (float) ($sourceCharge?->payable_amount ?? max(0, (float) $lockedEnrollment->tuition - (float) $lockedEnrollment->discount));
            $sourcePaid = (float) ($sourceCharge?->paid_amount ?? 0);
            $sourceCredit = (float) ($sourceCharge?->credit_amount ?? 0);
            $sourceSettled = $sourcePaid + $sourceCredit;
            $expectedSessions = max(1, (int) $lockedSourceClass->expected_sessions);
            $usedSessions = min(max(0, $sessionsUsed), $expectedSessions);
            $usedAmount = round($sourcePayable * $usedSessions / $expectedSessions, 2);
            $transferable = max(0, round($sourceSettled - $usedAmount, 2));
            $targetRemaining = $targetCharge->remainingAmount();
            $applied = min($transferable, $targetRemaining);
            $surplus = max(0, round($transferable - $applied, 2));

            $sourceChargeId = $sourceCharge?->id;
            if ($sourceCharge) {
                if ($sourcePaid <= 0 && $sourceCredit <= 0 && ! $sourceCharge->payments()->exists() && $usedAmount <= 0) {
                    $sourceCharge->delete();
                    $sourceChargeId = null;
                } else {
                    $retainedCredit = max(0, min($sourceCredit, $usedAmount - $sourcePaid));
                    $sourceStatus = $sourceSettled >= $usedAmount
                        ? 'transferred'
                        : ($sourceSettled > 0 ? 'partial' : 'unpaid');
                    $sourceCharge->update([
                        'payable_amount' => $usedAmount,
                        'credit_amount' => $retainedCredit,
                        'status' => $sourceStatus,
                        'note' => trim(($sourceCharge->note ? $sourceCharge->note."\n" : '').'Quyết toán chuyển sang lớp '.$lockedTargetClass->code.' ngày '.$date->format('d/m/Y').'.'),
                    ]);
                }
            }

            $targetCredit = round((float) $targetCharge->credit_amount + $applied, 2);
            $targetSettled = (float) $targetCharge->paid_amount + $targetCredit;
            $targetStatus = $targetSettled >= (float) $targetCharge->payable_amount
                ? 'paid'
                : ($targetSettled > 0 ? 'partial' : 'unpaid');
            $targetCharge->update([
                'credit_amount' => $targetCredit,
                'status' => $targetStatus,
                'note' => trim(($targetCharge->note ? $targetCharge->note."\n" : '').'Nhận '.number_format($applied, 0, ',', '.').'đ học phí chuyển từ lớp '.$lockedSourceClass->code.'.'),
            ]);

            $lockedEnrollment->update([
                'status' => 'dropped',
                'ended_at' => $date->toDateString(),
                'exit_reason' => 'Chuyển sang lớp '.$lockedTargetClass->code,
            ]);
            $student->update(['language_course_id' => $targetCourse->id, 'status' => 'studying']);

            return LanguageClassTransfer::create([
                'language_student_id' => $student->id,
                'from_language_class_id' => $lockedSourceClass->id,
                'to_language_class_id' => $lockedTargetClass->id,
                'from_enrollment_id' => $lockedEnrollment->id,
                'to_enrollment_id' => $targetEnrollment->id,
                'from_tuition_charge_id' => $sourceChargeId,
                'to_tuition_charge_id' => $targetCharge->id,
                'effective_date' => $date->toDateString(),
                'sessions_used' => $usedSessions,
                'source_payable_amount' => $sourcePayable,
                'source_paid_amount' => $sourcePaid,
                'source_credit_amount' => $sourceCredit,
                'used_amount' => $usedAmount,
                'transferred_amount' => $transferable,
                'applied_amount' => $applied,
                'surplus_amount' => $surplus,
                'created_by' => $createdBy,
                'note' => $note,
            ]);
        });
    }

    private function ensureTuitionCharge(
        LanguageClass $class,
        LanguageStudent $student,
        LanguageCourse $course,
        float $tuition,
        Carbon $enrolledAt,
        ?int $createdBy
    ): LanguageTuitionCharge {
        $existing = LanguageTuitionCharge::query()
            ->where('language_student_id', $student->id)
            ->where('language_class_id', $class->id)
            ->lockForUpdate()
            ->first();
        if ($existing) {
            return $existing;
        }

        $studentDiscount = $student->language_discount_policy_id
            ? LanguageDiscountPolicy::find($student->language_discount_policy_id)
            : null;
        $classDiscount = $class->language_discount_policy_id
            ? LanguageDiscountPolicy::find($class->language_discount_policy_id)
            : null;
        $discount = LanguageDiscountResolver::highest($classDiscount, $studentDiscount);
        $percentage = (float) ($discount?->percentage ?? 0);
        $discountAmount = round($tuition * $percentage / 100, 2);
        $leadId = LanguageLead::query()
            ->where('converted_student_id', $student->id)
            ->orderByRaw('language_course_id = ? desc', [$course->id])
            ->latest('id')
            ->value('id');

        $payableAmount = max(0, $tuition - $discountAmount);

        return LanguageTuitionCharge::create([
            'code' => CenterCode::next('language_tuition_charges', 'HP'),
            'language_student_id' => $student->id,
            'language_lead_id' => $leadId,
            'language_course_id' => $course->id,
            'language_class_id' => $class->id,
            'language_discount_policy_id' => $discount?->id,
            'original_amount' => $tuition,
            'discount_percentage' => $percentage,
            'discount_amount' => $discountAmount,
            'payable_amount' => $payableAmount,
            'paid_amount' => 0,
            'credit_amount' => 0,
            'due_date' => $enrolledAt->toDateString(),
            'status' => $payableAmount > 0 ? 'unpaid' : 'paid',
            'note' => 'Tự động tạo khi xếp học viên vào lớp '.$class->code.'.',
            'created_by' => $createdBy,
        ]);
    }
}
