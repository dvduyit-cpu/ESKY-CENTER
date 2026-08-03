<?php

namespace App\Support;

use App\Models\LanguageClass;
use App\Models\LanguageCourse;
use App\Models\LanguageDiscountPolicy;
use App\Models\LanguageStudent;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LanguageStudentSpreadsheet
{
    private const MAX_IMPORT_ROWS = 5000;

    private const HEADER_ALIASES = [
        'HO TEN PHU HUYNH' => [
            'TEN PHU HUYNH',
            'HO TEN NGUOI GIAM HO',
            'TEN NGUOI GIAM HO',
        ],
        'SDT PHU HUYNH' => [
            'SO DIEN THOAI PHU HUYNH',
            'DIEN THOAI PHU HUYNH',
            'SDT NGUOI GIAM HO',
            'SO DT PHU HUYNH',
            'SO DT NGUOI GIAM HO',
            'SO DIEN THOAI NGUOI GIAM HO',
            'DIEN THOAI NGUOI GIAM HO',
        ],
        'EMAIL PHU HUYNH' => [
            'EMAIL NGUOI GIAM HO',
        ],
        'QUAN HE' => [
            'MOI QUAN HE',
            'QUAN HE PHU HUYNH',
            'QUAN HE NGUOI GIAM HO',
        ],
    ];

    /**
     * @var array<string, array<int, array{student:LanguageStudent,phones:array<string, true>}>>
     */
    private array $studentIdentityIndex = [];

    public function __construct(private readonly LanguageEnrollmentManager $enrollmentManager) {}

    private const HEADERS = [
        'STT',
        'MÃ HỌC VIÊN',
        'HỌ TÊN',
        'GIỚI TÍNH',
        'NGÀY SINH',
        'TRƯỜNG',
        'LỚP TẠI TRƯỜNG',
        'ĐIỆN THOẠI',
        'EMAIL',
        'ĐỊA CHỈ',
        'NGÀY ĐĂNG KÝ',
        'NGÀY NHẬP HỌC',
        'NGUỒN',
        'MÃ KHÓA HỌC',
        'MÃ LỚP TRUNG TÂM',
        'MÃ MIỄN GIẢM',
        'TRẠNG THÁI',
        'GHI CHÚ',
        'HỌ TÊN PHỤ HUYNH',
        'QUAN HỆ',
        'SĐT PHỤ HUYNH',
        'EMAIL PHỤ HUYNH',
    ];

    /**
     * @return array{total:int,success:int,created:int,updated:int,skipped:int,failed:int,errors:array<int,string>,warnings:array<int,string>}
     */
    public function import(
        UploadedFile $file,
        bool $overwriteExisting = false,
        ?callable $progress = null
    ): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getSheetByName('HOC VIEN') ?? $spreadsheet->getActiveSheet();
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
            throw new \RuntimeException('File không có dữ liệu học viên.');
        }

        $headers = [];
        foreach ($rows[0] as $index => $header) {
            $normalized = TextNormalizer::header((string) $header);
            if ($normalized !== '') {
                $headers[$normalized] = $index;
            }
        }

        foreach (['HO TEN'] as $required) {
            if (! array_key_exists($required, $headers)) {
                throw new \RuntimeException('Thiếu cột bắt buộc '.$required.'. Vui lòng dùng file mẫu mới nhất.');
            }
        }

        $dataRows = [];

        foreach (array_slice($rows, 1) as $offset => $row) {
            if (! $this->rowHasData($row, $headers)) {
                continue;
            }

            $dataRows[] = ['number' => $offset + 2, 'values' => $row];
            if (count($dataRows) > self::MAX_IMPORT_ROWS) {
                throw new \RuntimeException('Mỗi lần chỉ được nhập tối đa 5.000 dòng học viên.');
            }
        }

        $result = [
            'total' => 0,
            'success' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
            'warnings' => [],
        ];
        if ($dataRows === []) {
            throw new \RuntimeException('File không có dòng học viên nào để nhập.');
        }

        $this->buildStudentIdentityIndex();
        $rowTotal = count($dataRows);
        if ($progress !== null) {
            $progress([
                'type' => 'start',
                'total' => $rowTotal,
            ]);
        }

        foreach ($dataRows as $dataRow) {
            $rowNumber = $dataRow['number'];
            $row = $dataRow['values'];
            $result['total']++;
            $name = trim((string) $this->cell($row, $headers, 'HO TEN'));
            $context = $name !== '' ? " ({$name})" : '';
            $status = 'failed';
            $rowMessage = '';

            try {
                $outcome = DB::transaction(
                    fn () => $this->importRow($row, $headers, $overwriteExisting)
                );
                $status = $outcome;
                $result[$outcome]++;
                if ($outcome === 'skipped') {
                    $rowMessage = "Dòng {$rowNumber}{$context}: Hồ sơ đã tồn tại nên không được ghi đè.";
                    if (count($result['warnings']) < 100) {
                        $result['warnings'][] = $rowMessage;
                    }
                } else {
                    $result['success']++;
                    $rowMessage = $outcome === 'updated'
                        ? "Dòng {$rowNumber}{$context}: Đã ghi đè hồ sơ trùng."
                        : "Dòng {$rowNumber}{$context}: Đã thêm học viên.";
                }
            } catch (\Throwable $exception) {
                $result['failed']++;
                $message = $exception instanceof QueryException
                    ? 'Không thể lưu do dữ liệu bị trùng hoặc không hợp lệ.'
                    : $exception->getMessage();
                $rowMessage = "Dòng {$rowNumber}{$context}: {$message}";
                if (count($result['errors']) < 100) {
                    $result['errors'][] = $rowMessage;
                }
            }

            if ($progress !== null) {
                $progress([
                    'type' => 'row',
                    'processed' => $result['total'],
                    'total' => $rowTotal,
                    'row' => $rowNumber,
                    'name' => $name,
                    'status' => $status,
                    'message' => $rowMessage,
                    'created' => $result['created'],
                    'updated' => $result['updated'],
                    'skipped' => $result['skipped'],
                    'failed' => $result['failed'],
                ]);
            }
        }

        if ($result['failed'] > count($result['errors'])) {
            $remaining = $result['failed'] - count($result['errors']);
            $result['errors'][] = "Còn {$remaining} dòng lỗi khác chưa hiển thị.";
        }

        if ($result['skipped'] > count($result['warnings'])) {
            $remaining = $result['skipped'] - count($result['warnings']);
            $result['warnings'][] = "Còn {$remaining} hồ sơ trùng khác đã được bỏ qua.";
        }

        return $result;
    }

    public function template(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setTitle('Mẫu nhập học viên')
            ->setSubject('Nhập danh sách học viên từ Excel');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('HOC VIEN');
        $sheet->fromArray(self::HEADERS, null, 'A1');
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:V1');
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getStyle('A1:V1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A8A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('B2:B5001')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $sheet->getStyle('H2:H5001')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $sheet->getStyle('U2:U5001')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $sheet->getStyle('E2:E5001')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $sheet->getStyle('K2:L5001')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $sheet->getStyle('A1:V5001')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        foreach ([
            'A' => 7, 'B' => 18, 'C' => 28, 'D' => 14, 'E' => 15, 'F' => 25, 'G' => 18,
            'H' => 18, 'I' => 27, 'J' => 32, 'K' => 17, 'L' => 17, 'M' => 20, 'N' => 18,
            'O' => 22, 'P' => 18, 'Q' => 20, 'R' => 32, 'S' => 28, 'T' => 18, 'U' => 20, 'V' => 28,
        ] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $this->addListValidation($sheet, 'D2:D5001', ['Nam', 'Nữ', 'Khác']);
        $this->addListValidation($sheet, 'Q2:Q5001', [
            'Mới đăng ký', 'Chờ kiểm tra', 'Chờ xếp lớp', 'Đang học',
            'Tạm nghỉ', 'Bảo lưu', 'Hoàn thành', 'Thôi học',
        ]);
        $this->addListValidation($sheet, 'T2:T5001', ['Cha', 'Mẹ', 'Người giám hộ']);

        $guide = $spreadsheet->createSheet();
        $guide->setTitle('HUONG DAN');
        $guide->fromArray([
            ['HƯỚNG DẪN NHẬP HỌC VIÊN'],
            ['1. Nhập dữ liệu tại sheet HOC VIEN, không đổi tên hoặc xóa hàng tiêu đề.'],
            ['2. Cột bắt buộc: HỌ TÊN. Ngày đăng ký có thể để trống.'],
            ['3. Ngày nhập theo định dạng dd/mm/yyyy, ví dụ 25/07/2026.'],
            ['4. MÃ HỌC VIÊN có thể để trống để hệ thống tự sinh. Mã có nhập phải chưa tồn tại.'],
            ['5. Mã khóa học, mã lớp và mã miễn giảm phải có trong sheet DANH MUC.'],
            ['6. Nếu nhập mã lớp mà bỏ trống mã khóa học, hệ thống tự lấy khóa học của lớp.'],
            ['7. Số điện thoại nên định dạng Text để giữ số 0 ở đầu.'],
            ['8. Mỗi dòng chỉ hỗ trợ một phụ huynh/người giám hộ chính.'],
            ['9. Các dòng lỗi sẽ không được lưu; các dòng hợp lệ vẫn được nhập bình thường.'],
            [],
            ['Ví dụ dữ liệu (chỉ để tham khảo, không cần sao chép hàng tiêu đề):'],
            ['MÃ HỌC VIÊN', 'HỌ TÊN', 'GIỚI TÍNH', 'NGÀY SINH', 'ĐIỆN THOẠI', 'NGÀY ĐĂNG KÝ', 'TRẠNG THÁI'],
            ['', 'Nguyễn Văn A', 'Nam', '15/08/2012', '0912345678', '25/07/2026', 'Mới đăng ký'],
        ], null, 'A1');
        $guide->mergeCells('A1:G1');
        $guide->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 15, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A8A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $guide->getStyle('A2:A10')->getAlignment()->setWrapText(true);
        $guide->getStyle('A13:G13')->getFont()->setBold(true);
        $guide->getColumnDimension('A')->setWidth(78);
        foreach (range('B', 'G') as $column) {
            $guide->getColumnDimension($column)->setWidth(20);
        }

        $catalog = $spreadsheet->createSheet();
        $catalog->setTitle('DANH MUC');
        $catalog->fromArray([
            ['MÃ KHÓA HỌC', 'TÊN KHÓA HỌC', '', 'MÃ LỚP', 'TÊN LỚP', 'KHÓA HỌC', '', 'MÃ MIỄN GIẢM', 'TÊN CHÍNH SÁCH'],
        ], null, 'A1');

        $courses = LanguageCourse::query()->where('active', true)->orderBy('code')->get(['code', 'name']);
        foreach ($courses->values() as $index => $course) {
            $catalog->fromArray([$course->code, $course->name], null, 'A'.($index + 2));
        }

        $classes = LanguageClass::query()
            ->whereIn('status', ['recruiting', 'upcoming', 'active'])
            ->with('course:id,code')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'language_course_id']);
        foreach ($classes->values() as $index => $class) {
            $catalog->fromArray([$class->code, $class->name, $class->course?->code], null, 'D'.($index + 2));
        }

        $discounts = LanguageDiscountPolicy::query()->where('active', true)->orderBy('code')->get(['code', 'name']);
        foreach ($discounts->values() as $index => $discount) {
            $catalog->fromArray([$discount->code, $discount->name], null, 'H'.($index + 2));
        }

        $catalog->getStyle('A1:I1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF0F766E']],
        ]);
        foreach (['A' => 20, 'B' => 32, 'C' => 4, 'D' => 20, 'E' => 32, 'F' => 20, 'G' => 4, 'H' => 20, 'I' => 32] as $column => $width) {
            $catalog->getColumnDimension($column)->setWidth($width);
        }
        $catalog->freezePane('A2');

        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(
            function () use ($spreadsheet): void {
                (new Xlsx($spreadsheet))->save('php://output');
                $spreadsheet->disconnectWorksheets();
            },
            'mau-nhap-hoc-vien.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /**
     * @param array<int, mixed> $row
     * @param array<string, int> $headers
     */
    private function importRow(array $row, array $headers, bool $overwriteExisting): string
    {
        $course = $this->findByCode(
            LanguageCourse::class,
            trim((string) $this->cell($row, $headers, 'MA KHOA HOC')),
            'khóa học'
        );
        $class = $this->findByCode(
            LanguageClass::class,
            trim((string) $this->cell($row, $headers, 'MA LOP TRUNG TAM')),
            'lớp trung tâm'
        );
        $discount = $this->findByCode(
            LanguageDiscountPolicy::class,
            trim((string) $this->cell($row, $headers, 'MA MIEN GIAM')),
            'chính sách miễn giảm'
        );

        if ($class?->language_course_id && $course && (int) $class->language_course_id !== (int) $course->id) {
            throw new \RuntimeException('Mã lớp không thuộc mã khóa học đã nhập.');
        }
        if (! $course && $class?->language_course_id) {
            $course = LanguageCourse::find($class->language_course_id);
        }

        $registeredAt = $this->parseDate($this->cell($row, $headers, 'NGAY DANG KY'), 'ngày đăng ký');
        $studentData = [
            'code' => $this->nullableUpper($this->cell($row, $headers, 'MA HOC VIEN')),
            'name' => trim((string) $this->cell($row, $headers, 'HO TEN')),
            'gender' => $this->gender($this->cell($row, $headers, 'GIOI TINH')),
            'date_of_birth' => $this->parseDate($this->cell($row, $headers, 'NGAY SINH'), 'ngày sinh')?->toDateString(),
            'school' => $this->nullableString($this->cell($row, $headers, 'TRUONG')),
            'school_class' => $this->nullableString($this->cell($row, $headers, 'LOP TAI TRUONG')),
            'phone' => $this->phone($this->cell($row, $headers, 'DIEN THOAI')),
            'email' => $this->nullableString($this->cell($row, $headers, 'EMAIL')),
            'address' => $this->nullableString($this->cell($row, $headers, 'DIA CHI')),
            'registered_at' => $registeredAt?->toDateString(),
            'official_enrollment_date' => $this->parseDate($this->cell($row, $headers, 'NGAY NHAP HOC'), 'ngày nhập học')?->toDateString(),
            'source' => $this->nullableString($this->cell($row, $headers, 'NGUON')),
            'language_course_id' => $course?->id,
            'language_discount_policy_id' => $discount?->id,
            'status' => $this->status($this->cell($row, $headers, 'TRANG THAI')),
            'note' => $this->nullableString($this->cell($row, $headers, 'GHI CHU')),
        ];

        $guardianPhone = $this->phone($this->cell($row, $headers, 'SDT PHU HUYNH'));
        $student = $this->findMatchingStudent(
            $studentData['name'],
            [$studentData['phone'], $guardianPhone]
        );

        if ($student && ! $overwriteExisting) {
            return 'skipped';
        }

        $outcome = $student ? 'updated' : 'created';
        if ($student) {
            $studentData = $this->mergeStudentData($student, $studentData, $row, $headers);
        }

        $validator = Validator::make($studentData, [
            'code' => [
                'nullable',
                'max:30',
                $student
                    ? Rule::unique('language_students', 'code')->ignore($student)
                    : Rule::unique('language_students', 'code'),
            ],
            'name' => ['required', 'max:255'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['nullable', 'date'],
            'school' => ['nullable', 'max:255'],
            'school_class' => ['nullable', 'max:100'],
            'phone' => ['nullable', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'max:255'],
            'registered_at' => ['nullable', 'date'],
            'official_enrollment_date' => [
                'nullable',
                'date',
                Rule::when($studentData['registered_at'] !== null, ['after_or_equal:registered_at']),
            ],
            'source' => ['nullable', 'max:255'],
            'language_course_id' => ['nullable', 'exists:language_courses,id'],
            'language_discount_policy_id' => ['nullable', 'exists:language_discount_policies,id'],
            'status' => ['required', Rule::in(array_keys($this->statusLabels()))],
            'note' => ['nullable', 'string'],
        ], [
            'code.unique' => 'Mã học viên đã tồn tại.',
            'name.required' => 'Thiếu họ tên học viên.',
            'email.email' => 'Email học viên không đúng định dạng.',
            'official_enrollment_date.after_or_equal' => 'Ngày nhập học phải bằng hoặc sau ngày đăng ký.',
        ]);
        if ($validator->fails()) {
            throw new \RuntimeException($validator->errors()->first());
        }

        if ($student) {
            $student->update($validator->validated());
        } else {
            $student = LanguageStudent::create($validator->validated());
        }
        $this->mergeGuardian($student, $row, $headers);

        if ($class) {
            $this->enrollmentManager->enroll(
                $class,
                $student,
                $student->official_enrollment_date ?? $student->registered_at ?? now(),
                auth()->id(),
                in_array($student->status, ['paused', 'reserved', 'completed', 'dropped'], true)
                    ? $student->status
                    : 'studying'
            );
        }

        $this->indexStudent($student, [$guardianPhone]);

        return $outcome;
    }

    /**
     * @param array<int, mixed> $row
     * @param array<string, int> $headers
     */
    private function mergeGuardian(LanguageStudent $student, array $row, array $headers): void
    {
        $name = trim((string) $this->cell($row, $headers, 'HO TEN PHU HUYNH'));
        $phone = $this->phone($this->cell($row, $headers, 'SDT PHU HUYNH'));
        $email = $this->nullableString($this->cell($row, $headers, 'EMAIL PHU HUYNH'));
        if ($name === '' && $phone === null && $email === null) {
            return;
        }

        $relationship = $this->relationship($this->cell($row, $headers, 'QUAN HE'));
        if ($name === '') {
            $name = $this->guardianDefaultName($relationship);
        }

        $data = [
            'name' => $name,
            'relationship' => $relationship,
            'phone' => $phone ?? '',
            'email' => $email,
            'is_primary' => true,
        ];
        $validator = Validator::make($data, [
            'name' => ['required', 'max:255'],
            'relationship' => ['nullable', Rule::in(['father', 'mother', 'guardian'])],
            'phone' => ['max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_primary' => ['boolean'],
        ], [
            'email.email' => 'Email phụ huynh không đúng định dạng.',
        ]);
        if ($validator->fails()) {
            throw new \RuntimeException($validator->errors()->first());
        }

        $guardian = null;
        $phone = TextNormalizer::phone($data['phone']);
        if ($phone !== null) {
            $guardian = $student->guardians()->get()->first(
                fn ($item) => TextNormalizer::phone($item->phone) === $phone
            );
        }

        if ($guardian) {
            $guardian->update($this->mergeNonBlank(
                $guardian->only(array_keys($data)),
                $validator->validated()
            ));
        } else {
            $student->guardians()->create($validator->validated());
        }
    }

    /**
     * @param array<string, mixed> $incoming
     * @param array<int, mixed> $row
     * @param array<string, int> $headers
     * @return array<string, mixed>
     */
    private function mergeStudentData(
        LanguageStudent $student,
        array $incoming,
        array $row,
        array $headers
    ): array {
        $incoming['code'] = $student->code;
        if (trim((string) $this->cell($row, $headers, 'TRANG THAI')) === '') {
            $incoming['status'] = null;
        }

        return $this->mergeNonBlank(
            $student->only(array_keys($incoming)),
            $incoming
        );
    }

    /**
     * @param array<string, mixed> $current
     * @param array<string, mixed> $incoming
     * @return array<string, mixed>
     */
    private function mergeNonBlank(array $current, array $incoming): array
    {
        foreach ($incoming as $key => $value) {
            if ($value !== null && $value !== '') {
                $current[$key] = $value;
            }
        }

        return $current;
    }

    private function buildStudentIdentityIndex(): void
    {
        $this->studentIdentityIndex = [];

        LanguageStudent::query()
            ->with('guardians:id,language_student_id,phone')
            ->get()
            ->each(fn (LanguageStudent $student) => $this->indexStudent($student));
    }

    /**
     * @param array<int, mixed> $extraPhones
     */
    private function indexStudent(LanguageStudent $student, array $extraPhones = []): void
    {
        $name = TextNormalizer::name($student->name);
        if ($name === '') {
            return;
        }

        $phones = $this->studentIdentityIndex[$name][$student->id]['phones'] ?? [];
        foreach ([$student->phone, ...$extraPhones] as $phone) {
            $normalized = TextNormalizer::phone($phone);
            if ($normalized !== null) {
                $phones[$normalized] = true;
            }
        }
        if ($student->relationLoaded('guardians')) {
            foreach ($student->guardians as $guardian) {
                $normalized = TextNormalizer::phone($guardian->phone);
                if ($normalized !== null) {
                    $phones[$normalized] = true;
                }
            }
        }

        $this->studentIdentityIndex[$name][$student->id] = [
            'student' => $student,
            'phones' => $phones,
        ];
    }

    /**
     * @param array<int, mixed> $phones
     */
    private function findMatchingStudent(string $name, array $phones): ?LanguageStudent
    {
        $name = TextNormalizer::name($name);
        $phones = array_values(array_filter(array_map(
            fn ($phone) => TextNormalizer::phone($phone),
            $phones
        )));
        if ($name === '' || $phones === []) {
            return null;
        }

        $matches = [];
        foreach ($this->studentIdentityIndex[$name] ?? [] as $identity) {
            if (array_intersect($phones, array_keys($identity['phones'])) !== []) {
                $matches[$identity['student']->id] = $identity['student'];
            }
        }

        if (count($matches) > 1) {
            throw new \RuntimeException(
                'Có nhiều hồ sơ học viên trùng họ tên và số điện thoại. Vui lòng gộp thủ công trước khi nhập lại.'
            );
        }

        return $matches ? reset($matches)->fresh() : null;
    }


    private function findByCode(string $model, string $code, string $label): ?object
    {
        if ($code === '') {
            return null;
        }

        $item = $model::query()->where('code', $code)->first();
        if (! $item) {
            throw new \RuntimeException("Không tìm thấy {$label} có mã {$code}.");
        }

        return $item;
    }

    private function parseDate(mixed $value, string $label): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance(\DateTime::createFromInterface($value))->startOfDay();
        }

        if (trim((string) $value) === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($value))->startOfDay();
            } catch (\Throwable) {
                throw new \RuntimeException(ucfirst($label).' không đúng định dạng.');
            }
        }

        $text = trim((string) $value);

        if (preg_match('/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{2}|\d{4})$/', $text, $matches)) {
            $day = (int) $matches[1];
            $month = (int) $matches[2];
            $year = (int) $matches[3];
            if (strlen($matches[3]) === 2) {
                $year += $year >= 70 ? 1900 : 2000;
            }

            if (checkdate($month, $day, $year)) {
                return Carbon::create($year, $month, $day)->startOfDay();
            }
        }

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $text, $matches)) {
            $year = (int) $matches[1];
            $month = (int) $matches[2];
            $day = (int) $matches[3];

            if (checkdate($month, $day, $year)) {
                return Carbon::create($year, $month, $day)->startOfDay();
            }
        }

        throw new \RuntimeException(ucfirst($label).' phải có định dạng ngày/tháng/năm, ví dụ 5/7/2026 hoặc 05/07/2026.');
    }

    private function gender(mixed $value): ?string
    {
        $normalized = TextNormalizer::header((string) $value);
        if ($normalized === '') {
            return null;
        }

        return match ($normalized) {
            'NAM', 'MALE' => 'male',
            'NU', 'FEMALE' => 'female',
            'KHAC', 'OTHER' => 'other',
            default => throw new \RuntimeException('Giới tính chỉ nhận Nam, Nữ hoặc Khác.'),
        };
    }

    private function status(mixed $value): string
    {
        $normalized = TextNormalizer::header((string) $value);
        if ($normalized === '') {
            return 'new';
        }

        foreach ($this->statusLabels() as $status => $labels) {
            if (in_array($normalized, $labels, true)) {
                return $status;
            }
        }

        throw new \RuntimeException('Trạng thái học viên không hợp lệ.');
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function statusLabels(): array
    {
        return [
            'new' => ['NEW', 'MOI', 'MOI DANG KY'],
            'placement_test' => ['PLACEMENT TEST', 'CHO KIEM TRA', 'CHO KIEM TRA DAU VAO'],
            'waiting_class' => ['WAITING CLASS', 'CHO XEP LOP'],
            'studying' => ['STUDYING', 'DANG HOC'],
            'paused' => ['PAUSED', 'TAM NGHI'],
            'reserved' => ['RESERVED', 'BAO LUU'],
            'completed' => ['COMPLETED', 'HOAN THANH'],
            'dropped' => ['DROPPED', 'THOI HOC'],
        ];
    }

    private function relationship(mixed $value): string
    {
        return match (TextNormalizer::header((string) $value)) {
            '', 'NGUOI GIAM HO', 'GUARDIAN' => 'guardian',
            'CHA', 'BO', 'FATHER' => 'father',
            'ME', 'MOTHER' => 'mother',
            default => throw new \RuntimeException('Quan hệ phụ huynh chỉ nhận Cha, Mẹ hoặc Người giám hộ.'),
        };
    }

    private function guardianDefaultName(string $relationship): string
    {
        return match ($relationship) {
            'father' => 'Cha',
            'mother' => 'Mẹ',
            default => 'Người giám hộ',
        };
    }

    private function nullableUpper(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_strtoupper($value);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function phone(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            $phone = number_format((float) $value, 0, '', '');

            return strlen($phone) === 9 ? '0'.$phone : $phone;
        }

        return trim((string) $value);
    }

    /**
     * @param array<int, mixed> $row
     * @param array<string, int> $headers
     */
    private function rowHasData(array $row, array $headers): bool
    {
        foreach ($headers as $header => $index) {
            if ($header !== 'STT' && trim((string) ($row[$index] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int, mixed> $row
     * @param array<string, int> $headers
     */
    private function cell(array $row, array $headers, string $header): mixed
    {
        foreach ([$header, ...(self::HEADER_ALIASES[$header] ?? [])] as $candidate) {
            if (array_key_exists($candidate, $headers)) {
                return $row[$headers[$candidate]] ?? null;
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $values
     */
    private function addListValidation(object $sheet, string $range, array $values): void
    {
        $validation = new DataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setErrorTitle('Giá trị không hợp lệ');
        $validation->setError('Vui lòng chọn một giá trị trong danh sách.');
        $validation->setFormula1('"'.implode(',', $values).'"');
        $sheet->setDataValidation($range, $validation);
    }
}
