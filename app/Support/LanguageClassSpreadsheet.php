<?php

namespace App\Support;

use App\Models\LanguageClass;
use App\Models\LanguageCourse;
use App\Models\LanguageDiscountPolicy;
use App\Models\LanguageEnrollment;
use App\Models\LanguageStudent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LanguageClassSpreadsheet
{
    private const CLASS_HEADERS = [
        'MA LOP',
        'TEN LOP',
        'MA KHOA HOC',
        'HOC PHI LOP',
        'MA MIEN GIAM',
        'GIAO VIEN',
        'PHONG',
        'NGAY KHAI GIANG',
        'DU KIEN KET THUC',
        'GIO BAT DAU MAC DINH',
        'GIO KET THUC MAC DINH',
        'SI SO TOI DA',
        'TRANG THAI',
        'LICH HOC',
        'GHI CHU',
    ];

    private const ENROLLMENT_HEADERS = [
        'MA HOC VIEN',
        'NGAY VAO LOP',
        'GHI CHU',
    ];

    private const MAX_IMPORT_ROWS = 5000;

    public function __construct(private readonly LanguageEnrollmentManager $enrollmentManager) {}

    public function classTemplate(): StreamedResponse
    {
        return ExcelExporter::download('mau-lop-hoc.xlsx', self::CLASS_HEADERS, [[
            'LA-TA-0826-01',
            'Tieng Anh giao tiep 08/2026',
            'KH-001',
            '2500000',
            '10',
            'teacher@example.com',
            'Phong 201',
            '25/08/2026',
            '25/11/2026',
            '18:00',
            '19:30',
            '20',
            'upcoming',
            'Thu 2 - Thu 4 - Thu 6',
            'Lop toi',
        ]]);
    }

    public function enrollmentTemplate(LanguageClass $languageClass): StreamedResponse
    {
        return ExcelExporter::download(
            'mau-them-hoc-vien-vao-lop-'.$languageClass->code.'.xlsx',
            self::ENROLLMENT_HEADERS,
            [[
                'HV-2026-00001',
                $languageClass->start_date?->format('d/m/Y') ?? now()->format('d/m/Y'),
                'Nhap vao lop '.$languageClass->code,
            ]]
        );
    }

    /**
     * @return array{total:int,created:int,updated:int,failed:int,errors:array<int,string>}
     */
    public function importClasses(UploadedFile $file): array
    {
        [$headers, $dataRows] = $this->readRows($file, 'LOP HOC');

        foreach (['MA LOP', 'TEN LOP', 'MA KHOA HOC'] as $required) {
            if (! array_key_exists($required, $headers)) {
                throw new \RuntimeException('Thieu cot bat buoc '.$required.'.');
            }
        }

        $result = [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($dataRows as $dataRow) {
            $rowNumber = $dataRow['number'];
            $row = $dataRow['values'];
            $result['total']++;
            $code = $this->nullableUpper($this->cell($row, $headers, 'MA LOP'));
            $name = trim((string) $this->cell($row, $headers, 'TEN LOP'));
            $context = $name !== '' ? " ({$name})" : '';

            try {
                $outcome = DB::transaction(function () use ($row, $headers, $code): string {
                    $course = $this->findCourse(
                        trim((string) $this->cell($row, $headers, 'MA KHOA HOC'))
                    );
                    $discount = $this->findDiscountPolicy(
                        trim((string) $this->cell($row, $headers, 'MA MIEN GIAM'))
                    );
                    $teacher = $this->findTeacher(
                        trim((string) $this->cell($row, $headers, 'GIAO VIEN'))
                    );

                    $tuitionValue = $this->cell($row, $headers, 'HOC PHI LOP');
                    $defaultTuition = $tuitionValue === null || trim((string) $tuitionValue) === ''
                        ? (float) $course->tuition
                        : $this->decimalValue($tuitionValue, 'hoc phi lop');

                    $payload = [
                        'code' => $code,
                        'name' => trim((string) $this->cell($row, $headers, 'TEN LOP')),
                        'language_course_id' => $course->id,
                        'language_program_id' => $course->language_program_id,
                        'language_level_id' => $course->language_level_id,
                        'default_tuition' => $defaultTuition,
                        'language_discount_policy_id' => $discount?->id,
                        'teacher_user_id' => $teacher?->id,
                        'room' => $this->nullableString($this->cell($row, $headers, 'PHONG')),
                        'start_date' => $this->parseDate($this->cell($row, $headers, 'NGAY KHAI GIANG'), 'ngay khai giang')?->toDateString(),
                        'expected_end_date' => $this->parseDate($this->cell($row, $headers, 'DU KIEN KET THUC'), 'du kien ket thuc')?->toDateString(),
                        'default_start_time' => $this->parseTime($this->cell($row, $headers, 'GIO BAT DAU MAC DINH'), 'gio bat dau mac dinh') ?? '18:00',
                        'default_end_time' => $this->parseTime($this->cell($row, $headers, 'GIO KET THUC MAC DINH'), 'gio ket thuc mac dinh') ?? '19:30',
                        'max_students' => $this->integerValue($this->cell($row, $headers, 'SI SO TOI DA'), 'si so toi da') ?? 20,
                        'status' => $this->parseClassStatus($this->cell($row, $headers, 'TRANG THAI')),
                        'schedule_note' => $this->nullableString($this->cell($row, $headers, 'LICH HOC')),
                        'note' => $this->nullableString($this->cell($row, $headers, 'GHI CHU')),
                        'expected_sessions' => (int) $course->sessions,
                    ];

                    if ($payload['expected_end_date'] !== null && $payload['start_date'] !== null
                        && $payload['expected_end_date'] < $payload['start_date']) {
                        throw new \RuntimeException('Du kien ket thuc phai bang hoac sau ngay khai giang.');
                    }

                    if ($payload['default_end_time'] <= $payload['default_start_time']) {
                        throw new \RuntimeException('Gio ket thuc phai sau gio bat dau mac dinh.');
                    }

                    $existing = LanguageClass::query()->where('code', $code)->first();
                    $validator = Validator::make($payload, [
                        'code' => ['required', 'max:30', Rule::unique('language_classes', 'code')->ignore($existing)],
                        'name' => ['required', 'max:255'],
                        'language_course_id' => ['required', 'exists:language_courses,id'],
                        'language_discount_policy_id' => ['nullable', 'exists:language_discount_policies,id'],
                        'teacher_user_id' => ['nullable', 'exists:users,id'],
                        'room' => ['nullable', 'max:255'],
                        'start_date' => ['nullable', 'date'],
                        'expected_end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
                        'default_start_time' => ['required', 'date_format:H:i'],
                        'default_end_time' => ['required', 'date_format:H:i', 'after:default_start_time'],
                        'max_students' => ['required', 'integer', 'min:1'],
                        'default_tuition' => ['required', 'numeric', 'min:0'],
                        'status' => ['required', Rule::in(['planned', 'recruiting', 'upcoming', 'active', 'paused', 'completed', 'cancelled'])],
                        'schedule_note' => ['nullable', 'string'],
                        'note' => ['nullable', 'string'],
                        'language_program_id' => ['required', 'exists:language_programs,id'],
                        'language_level_id' => ['nullable', 'exists:language_levels,id'],
                        'expected_sessions' => ['required', 'integer', 'min:0'],
                    ]);
                    if ($validator->fails()) {
                        throw new \RuntimeException($validator->errors()->first());
                    }

                    if ($existing) {
                        $existing->update($validator->validated());

                        return 'updated';
                    }

                    LanguageClass::create($validator->validated());

                    return 'created';
                });

                $result[$outcome]++;
            } catch (\Throwable $exception) {
                $result['failed']++;
                $message = $exception instanceof QueryException
                    ? 'Khong the luu lop hoc do du lieu trung hoac khong hop le.'
                    : $exception->getMessage();
                if (count($result['errors']) < 100) {
                    $result['errors'][] = "Dong {$rowNumber}{$context}: {$message}";
                }
            }
        }

        if ($result['failed'] > count($result['errors'])) {
            $remaining = $result['failed'] - count($result['errors']);
            $result['errors'][] = "Con {$remaining} dong loi khac chua hien thi.";
        }

        return $result;
    }

    /**
     * @return array{total:int,created:int,updated:int,failed:int,errors:array<int,string>}
     */
    public function importEnrollments(
        LanguageClass $languageClass,
        UploadedFile $file,
        string $defaultEnrolledAt,
        int $userId
    ): array {
        [$headers, $dataRows] = $this->readRows($file, 'HOC VIEN VAO LOP');

        if (! array_key_exists('MA HOC VIEN', $headers)) {
            throw new \RuntimeException('Thieu cot bat buoc MA HOC VIEN.');
        }

        $result = [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($dataRows as $dataRow) {
            $rowNumber = $dataRow['number'];
            $row = $dataRow['values'];
            $result['total']++;
            $code = $this->nullableUpper($this->cell($row, $headers, 'MA HOC VIEN'));

            try {
                $outcome = DB::transaction(function () use ($languageClass, $row, $headers, $defaultEnrolledAt, $userId, $code): string {
                    if ($code === null) {
                        throw new \RuntimeException('Thieu ma hoc vien.');
                    }

                    $student = LanguageStudent::query()
                        ->where('code', $code)
                        ->whereIn('status', ['new', 'waiting_class', 'studying', 'dropped'])
                        ->first();
                    if (! $student) {
                        throw new \RuntimeException("Khong tim thay hoc vien co ma {$code}.");
                    }

                    $enrolledAt = $this->parseDate($this->cell($row, $headers, 'NGAY VAO LOP'), 'ngay vao lop')
                        ?->toDateString() ?? $defaultEnrolledAt;

                    $existing = LanguageEnrollment::query()
                        ->where('language_class_id', $languageClass->id)
                        ->where('language_student_id', $student->id)
                        ->first();

                    $this->enrollmentManager->enroll($languageClass, $student, $enrolledAt, $userId);

                    return $existing ? 'updated' : 'created';
                });

                $result[$outcome]++;
            } catch (\Throwable $exception) {
                $result['failed']++;
                $message = $exception instanceof QueryException
                    ? 'Khong the xep hoc vien vao lop do du lieu trung hoac khong hop le.'
                    : $exception->getMessage();
                if (count($result['errors']) < 100) {
                    $result['errors'][] = "Dong {$rowNumber} ({$code}): {$message}";
                }
            }
        }

        if ($result['failed'] > count($result['errors'])) {
            $remaining = $result['failed'] - count($result['errors']);
            $result['errors'][] = "Con {$remaining} dong loi khac chua hien thi.";
        }

        return $result;
    }

    /**
     * @return array{0:array<string, int>,1:array<int, array{number:int,values:array<int,mixed>}>}
     */
    private function readRows(UploadedFile $file, ?string $sheetName = null): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $sheetName !== null
            ? ($spreadsheet->getSheetByName($sheetName) ?? $spreadsheet->getActiveSheet())
            : $spreadsheet->getActiveSheet();

        $highestDataRow = $sheet->getHighestDataRow();
        $highestDataColumn = $sheet->getHighestDataColumn();
        $rows = $sheet->rangeToArray(
            "A1:{$highestDataColumn}{$highestDataRow}",
            null,
            true,
            true,
            false
        );
        $spreadsheet->disconnectWorksheets();

        if (count($rows) < 2) {
            throw new \RuntimeException('File khong co du lieu de nhap.');
        }

        $headers = [];
        foreach ($rows[0] as $index => $header) {
            $normalized = TextNormalizer::header((string) $header);
            if ($normalized !== '') {
                $headers[$normalized] = $index;
            }
        }

        $dataRows = [];
        foreach (array_slice($rows, 1) as $offset => $row) {
            if (! $this->rowHasData($row, $headers)) {
                continue;
            }

            $dataRows[] = ['number' => $offset + 2, 'values' => $row];
            if (count($dataRows) > self::MAX_IMPORT_ROWS) {
                throw new \RuntimeException('Moi lan chi duoc nhap toi da 5.000 dong.');
            }
        }

        if ($dataRows === []) {
            throw new \RuntimeException('File khong co dong du lieu nao de nhap.');
        }

        return [$headers, $dataRows];
    }

    /**
     * @param array<int, mixed> $row
     * @param array<string, int> $headers
     */
    private function cell(array $row, array $headers, string $header): mixed
    {
        return array_key_exists($header, $headers) ? ($row[$headers[$header]] ?? null) : null;
    }

    /**
     * @param array<int, mixed> $row
     * @param array<string, int> $headers
     */
    private function rowHasData(array $row, array $headers): bool
    {
        foreach ($headers as $index) {
            $value = $row[$index] ?? null;
            if ($value !== null && trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function findCourse(string $code): LanguageCourse
    {
        if ($code === '') {
            throw new \RuntimeException('Thieu ma khoa hoc.');
        }

        $course = LanguageCourse::query()->where('code', $code)->first();
        if (! $course) {
            throw new \RuntimeException("Khong tim thay khoa hoc co ma {$code}.");
        }
        if (! $course->language_program_id || ! $course->language_level_id) {
            throw new \RuntimeException("Khoa hoc {$code} chua duoc lien ket day du voi chuong trinh va cap do.");
        }

        return $course;
    }

    private function findTeacher(string $value): ?User
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $query = User::query()->instructors()->where('active', true);
        if (str_contains($value, '@')) {
            $teacher = $query->where('email', $value)->first();
            if (! $teacher) {
                throw new \RuntimeException("Khong tim thay giao vien co email {$value}.");
            }

            return $teacher;
        }

        $normalized = TextNormalizer::name($value);
        $matches = $query->get()->filter(
            fn (User $user) => TextNormalizer::name($user->name) === $normalized
        )->values();

        if ($matches->count() === 1) {
            return $matches->first();
        }
        if ($matches->count() > 1) {
            throw new \RuntimeException("Co nhieu giao vien trung ten {$value}. Hay dung email de phan biet.");
        }

        throw new \RuntimeException("Khong tim thay giao vien {$value}.");
    }

    private function findDiscountPolicy(string $code): ?LanguageDiscountPolicy
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        $policy = LanguageDiscountPolicy::query()->where('code', $code)->first();
        if ($policy) {
            return $policy;
        }

        $normalizedNumeric = str_replace([' ', '%', ','], ['', '', '.'], $code);
        if (is_numeric($normalizedNumeric)) {
            $percentage = round((float) $normalizedNumeric, 2);
            $matches = LanguageDiscountPolicy::query()
                ->where('percentage', $percentage)
                ->orderByDesc('active')
                ->orderBy('id')
                ->get();

            if ($matches->count() === 1) {
                return $matches->first();
            }

            if ($matches->count() > 1) {
                return $matches->firstWhere('active', true) ?? $matches->first();
            }
        }

        throw new \RuntimeException("Khong tim thay chinh sach mien giam co ma {$code}.");
    }

    private function parseClassStatus(mixed $value): string
    {
        $normalized = TextNormalizer::header((string) $value);

        return match ($normalized) {
            '', 'RECRUITING', 'DANG TUYEN SINH' => 'recruiting',
            'PLANNED', 'DU KIEN', 'DU KIEN MO' => 'planned',
            'UPCOMING', 'SAP KHAI GIANG', 'SAP MO' => 'upcoming',
            'ACTIVE', 'DANG HOAT DONG', 'DANG HOC' => 'active',
            'PAUSED', 'TAM DUNG' => 'paused',
            'COMPLETED', 'DA KET THUC' => 'completed',
            'CANCELLED', 'DA HUY' => 'cancelled',
            default => throw new \RuntimeException('Trang thai lop hoc khong hop le.'),
        };
    }

    private function parseDate(mixed $value, string $label): ?Carbon
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance(\DateTime::createFromInterface($value))->startOfDay();
        }

        if (is_int($value) || is_float($value)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->startOfDay();
        }

        $text = trim((string) $value);
        $text = preg_replace('/(?<=\d)[,.]00(?=\/|$)/', '', $text) ?: $text;
        $text = str_replace(['.', '-'], '/', $text);

        foreach (['d/m/Y', 'j/n/Y', 'd/m/y', 'j/n/y', 'Y/m/d', 'Y/n/j'] as $format) {
            try {
                return Carbon::createFromFormat($format, $text)->startOfDay();
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($text)->startOfDay();
        } catch (\Throwable) {
        }

        throw new \RuntimeException(ucfirst($label).' phai co dinh dang ngay/thang/nam.');
    }

    private function parseTime(mixed $value, string $label): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance(\DateTime::createFromInterface($value))->format('H:i');
        }

        if (is_int($value) || is_float($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->format('H:i');
            } catch (\Throwable) {
                throw new \RuntimeException(ucfirst($label).' khong dung dinh dang.');
            }
        }

        $text = trim((string) $value);
        if (preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $text)) {
            return substr($text, 0, 5);
        }

        throw new \RuntimeException(ucfirst($label).' phai co dinh dang HH:MM.');
    }

    private function decimalValue(mixed $value, string $label): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $text = trim((string) $value);
        $normalized = str_replace([' ', ','], ['', '.'], $text);
        if (! is_numeric($normalized)) {
            throw new \RuntimeException(ucfirst($label).' khong hop le.');
        }

        return (float) $normalized;
    }

    private function integerValue(mixed $value, string $label): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }
        if (is_float($value)) {
            return (int) round($value);
        }

        $text = preg_replace('/\D+/', '', (string) $value) ?? '';
        if ($text === '') {
            throw new \RuntimeException(ucfirst($label).' khong hop le.');
        }

        return (int) $text;
    }

    private function nullableString(mixed $value): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    private function nullableUpper(mixed $value): ?string
    {
        $text = strtoupper(trim((string) $value));

        return $text === '' ? null : $text;
    }
}
