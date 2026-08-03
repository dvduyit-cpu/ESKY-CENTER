<?php

namespace App\Support;

use App\Models\LanguageStudent;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LanguageStudentMergeService
{
    /**
     * @param array<int, int> $sourceIds
     */
    public function merge(LanguageStudent $primary, array $sourceIds): int
    {
        $sourceIds = collect($sourceIds)
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $id === (int) $primary->id)
            ->unique()
            ->values();

        if ($sourceIds->isEmpty()) {
            throw ValidationException::withMessages([
                'duplicate_ids' => 'Vui lòng chọn ít nhất một hồ sơ trùng để gộp.',
            ]);
        }

        return DB::transaction(function () use ($primary, $sourceIds): int {
            $studentIds = [(int) $primary->id, ...$sourceIds->all()];
            $students = LanguageStudent::query()
                ->whereIn('id', $studentIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $lockedPrimary = $students->get((int) $primary->id);
            $sources = $sourceIds->map(fn ($id) => $students->get($id))->filter();

            if (! $lockedPrimary || $sources->count() !== $sourceIds->count()) {
                throw ValidationException::withMessages([
                    'duplicate_ids' => 'Có hồ sơ không còn tồn tại. Vui lòng kiểm tra lại dữ liệu trùng.',
                ]);
            }

            $this->ensureNoClassConflicts($studentIds);

            foreach ($sources as $source) {
                $this->fillMissingProfile($lockedPrimary, $source);
                $this->mergeGuardians($lockedPrimary, $source);

                DB::table('language_leads')
                    ->where('converted_student_id', $source->id)
                    ->update(['converted_student_id' => $lockedPrimary->id]);
                DB::table('language_enrollments')
                    ->where('language_student_id', $source->id)
                    ->update(['language_student_id' => $lockedPrimary->id]);
                DB::table('language_tuition_charges')
                    ->where('language_student_id', $source->id)
                    ->update(['language_student_id' => $lockedPrimary->id]);
                DB::table('language_monthly_target_records')
                    ->where('language_student_id', $source->id)
                    ->update(['language_student_id' => $lockedPrimary->id]);
                DB::table('language_class_transfers')
                    ->where('language_student_id', $source->id)
                    ->update(['language_student_id' => $lockedPrimary->id]);

                $source->delete();
            }

            $this->ensurePrimaryGuardian($lockedPrimary);
            if (in_array($lockedPrimary->status, ['new', 'placement_test', 'waiting_class'], true)
                && $lockedPrimary->enrollments()->where('status', 'studying')->exists()) {
                $lockedPrimary->update(['status' => 'studying']);
            }

            return $sources->count();
        });
    }

    /**
     * @param array<int, int> $studentIds
     */
    private function ensureNoClassConflicts(array $studentIds): void
    {
        $enrollmentConflict = DB::table('language_enrollments')
            ->whereIn('language_student_id', $studentIds)
            ->select('language_class_id')
            ->groupBy('language_class_id')
            ->havingRaw('COUNT(DISTINCT language_student_id) > 1')
            ->exists();
        if ($enrollmentConflict) {
            throw ValidationException::withMessages([
                'duplicate_ids' => 'Không thể tự động gộp vì các hồ sơ đã cùng xuất hiện trong một lớp học.',
            ]);
        }

        $tuitionConflict = DB::table('language_tuition_charges')
            ->whereIn('language_student_id', $studentIds)
            ->whereNotNull('language_class_id')
            ->select('language_class_id')
            ->groupBy('language_class_id')
            ->havingRaw('COUNT(DISTINCT language_student_id) > 1')
            ->exists();
        if ($tuitionConflict) {
            throw ValidationException::withMessages([
                'duplicate_ids' => 'Không thể tự động gộp vì các hồ sơ đã cùng có dữ liệu học phí trong một lớp.',
            ]);
        }
    }

    private function fillMissingProfile(LanguageStudent $primary, LanguageStudent $source): void
    {
        $fields = [
            'gender', 'date_of_birth', 'school', 'school_class', 'phone', 'email', 'address',
            'registered_at', 'official_enrollment_date', 'source', 'language_course_id',
            'language_discount_policy_id', 'note',
        ];
        foreach ($fields as $field) {
            if (blank($primary->getAttribute($field)) && filled($source->getAttribute($field))) {
                $primary->setAttribute($field, $source->getAttribute($field));
            }
        }
        if ($primary->isDirty()) {
            $primary->save();
        }
    }

    private function mergeGuardians(LanguageStudent $primary, LanguageStudent $source): void
    {
        $primaryGuardians = $primary->guardians()->get();

        foreach ($source->guardians()->get() as $guardian) {
            $phone = TextNormalizer::phone($guardian->phone);
            $existing = $phone === null
                ? null
                : $primaryGuardians->first(
                    fn ($item) => TextNormalizer::phone($item->phone) === $phone
                );

            if (! $existing) {
                $guardian->update(['language_student_id' => $primary->id]);
                $primaryGuardians->push($guardian);
                continue;
            }

            foreach (['name', 'relationship', 'phone', 'email', 'zalo'] as $field) {
                if (blank($existing->getAttribute($field)) && filled($guardian->getAttribute($field))) {
                    $existing->setAttribute($field, $guardian->getAttribute($field));
                }
            }
            if (! $primaryGuardians->contains('is_primary', true) && $guardian->is_primary) {
                $existing->is_primary = true;
            }
            if ($existing->isDirty()) {
                $existing->save();
            }
            $guardian->delete();
        }
    }

    private function ensurePrimaryGuardian(LanguageStudent $student): void
    {
        $guardians = $student->guardians()
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get();
        $primaryGuardian = $guardians->first();
        if (! $primaryGuardian) {
            return;
        }

        foreach ($guardians as $guardian) {
            $shouldBePrimary = $guardian->id === $primaryGuardian->id;
            if ($guardian->is_primary !== $shouldBePrimary) {
                $guardian->update(['is_primary' => $shouldBePrimary]);
            }
        }
    }
}
