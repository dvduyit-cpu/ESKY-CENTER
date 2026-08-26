<?php

namespace App\Support;

use App\Models\LanguageMonthlyTargetRecord;
use App\Models\LanguageStudent;
use App\Models\LanguageTuitionCharge;
use App\Models\LanguageTuitionPayment;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LanguageTuitionSpreadsheet
{
    private const MAX_IMPORT_ROWS = 5000;

    private const HEADERS = [
        'STT',
        'MÃ LỚP',
        'MÃ KHOẢN THU',
        'HỌ TÊN',
        'NGÀY SINH',
        'SỐ PHIẾU THU',
        'NGÀY THU',
        'SỐ TIỀN HỌC PHÍ',
        'TIỀN SÁCH',
        'HÌNH THỨC',
        'THU NỬA LỚP',
        'TỶ LỆ THU (%)',
        'GHI CHÚ',
    ];

    /**
     * @var array<string, array<int, LanguageStudent>>
     */
    private array $studentIdentityIndex = [];

    /**
     * @var array<string, array<int, LanguageStudent>>
     */
    private array $studentExactIdentityIndex = [];

    /**
     * @return array{total:int,success:int,created:int,updated:int,skipped:int,failed:int,errors:array<int,string>,warnings:array<int,string>,preview:array<int,array<string,mixed>>}
     */
    public function import(
        UploadedFile $file,
        ?int $userId = null,
        ?callable $progress = null,
        bool $validateOnly = false
    ): array {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getSheetByName('HOC PHI') ?? $spreadsheet->getActiveSheet();
        $highestDataRow = $sheet->getHighestDataRow();
        $highestDataColumn = $sheet->getHighestDataColumn();
        $rows = $sheet->rangeToArray("A1:{$highestDataColumn}{$highestDataRow}", null, true, true, false);
        $spreadsheet->disconnectWorksheets();

        if (count($rows) < 2) {
            throw new \RuntimeException('File không có dữ liệu thu học phí.');
        }

        $headers = [];
        foreach ($rows[0] as $index => $header) {
            $normalized = TextNormalizer::header((string) $header);
            if ($normalized !== '') {
                $headers[$normalized] = $index;
            }
        }

        foreach (['HO TEN', 'NGAY SINH'] as $required) {
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
                throw new \RuntimeException('Mỗi lần chỉ được nhập tối đa 5.000 dòng học phí.');
            }
        }

        if ($dataRows === []) {
            throw new \RuntimeException('File không có dòng học phí nào để nhập.');
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
            'preview' => [],
        ];

        $this->buildStudentIdentityIndex();
        $rowTotal = count($dataRows);

        if ($progress !== null) {
            $progress([
                'type' => 'start',
                'total' => $rowTotal,
            ]);
        }

        if ($validateOnly) {
            DB::beginTransaction();
        }

        try {
            foreach ($dataRows as $dataRow) {
                $rowNumber = $dataRow['number'];
                $row = $dataRow['values'];
                $result['total']++;
                $name = trim((string) $this->cell($row, $headers, 'HO TEN'));
                $context = $name !== '' ? " ({$name})" : '';
                $status = 'failed';
                $rowMessage = '';

                try {
                    if ($validateOnly && count($result['preview']) < 20) {
                        $preview = $this->previewRow($row, $headers);
                        if ($preview !== null) {
                            $result['preview'][] = $preview;
                        }
                    }

                    $outcome = DB::transaction(
                        fn () => $this->importRow($row, $headers, $userId)
                    );
                    $status = $outcome;
                    $result[$outcome]++;
                    if ($outcome !== 'skipped') {
                        $result['success']++;
                    }

                    $rowMessage = match ($outcome) {
                        'created' => "Dòng {$rowNumber}{$context}: Đã ghi nhận phiếu thu mới.",
                        'updated' => "Dòng {$rowNumber}{$context}: Đã cập nhật phiếu thu chờ hoặc bổ sung số phiếu.",
                        'skipped' => "Dòng {$rowNumber}{$context}: Không có dữ liệu cần cập nhật nên đã bỏ qua.",
                        default => "Dòng {$rowNumber}{$context}: Đã xử lý thành công.",
                    };
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
        } finally {
            if ($validateOnly && DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }

        if ($result['failed'] > count($result['errors'])) {
            $remaining = $result['failed'] - count($result['errors']);
            $result['errors'][] = "Còn {$remaining} dòng lỗi khác chưa hiển thị.";
        }

        return $result;
    }

    public function template(): StreamedResponse
    {
        if (! SpreadsheetSupport::hasZipArchive()) {
            return SpreadsheetSupport::streamCsvDownload(
                'mau-cap-nhat-thu-hoc-phi.csv',
                self::HEADERS,
                [[
                    1,
                    'IELTS-S-01',
                    '',
                    'Nguyễn Văn A',
                    '15/08/2012',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                ]]
            );
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setTitle('Mẫu cập nhật thu học phí')
            ->setSubject('Cập nhật phiếu thu và trạng thái đã đóng tiền');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('HOC PHI');
        $sheet->fromArray(self::HEADERS, null, 'A1');
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:M1');
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getStyle('A1:M1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF166534']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('B2:C5001')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $sheet->getStyle('E2:E5001')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $sheet->getStyle('F2:F5001')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $sheet->getStyle('G2:G5001')->getNumberFormat()->setFormatCode('dd/mm/yyyy hh:mm');
        $sheet->getStyle('H2:I5001')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        $sheet->getStyle('A1:M5001')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        foreach ([
            'A' => 7, 'B' => 18, 'C' => 18, 'D' => 28, 'E' => 15, 'F' => 18, 'G' => 20,
            'H' => 18, 'I' => 14, 'J' => 18, 'K' => 14, 'L' => 14, 'M' => 34,
        ] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $this->addListValidation($sheet, 'J2:J5001', ['Tiền mặt', 'Chuyển khoản', 'Thẻ', 'Khác']);
        $this->addListValidation($sheet, 'K2:K5001', ['Có', 'Không']);

        $guide = $spreadsheet->createSheet();
        $guide->setTitle('HUONG DAN');
        $guide->fromArray([
            ['HƯỚNG DẪN CẬP NHẬT THU HỌC PHÍ'],
            ['1. Hệ thống đối chiếu bắt buộc theo HỌ TÊN + NGÀY SINH. Nếu một học viên còn nhiều khoản thu mở, hãy điền thêm MÃ LỚP hoặc MÃ KHOẢN THU.'],
            ['2. SỐ PHIẾU THU có thể để trống nếu mới ghi nhận tiền và bổ sung phiếu sau; khi có số phiếu thì hệ thống tự xác nhận phiếu đã đóng tiền.'],
            ['3. SỐ TIỀN HỌC PHÍ để trống thì hệ thống tự lấy số tiền còn phải thu của khoản đó.'],
            ['4. Nếu học viên chỉ học nửa lớp, điền THU NỬA LỚP = Có hoặc TỶ LỆ THU (%) = 50 để hệ thống tính lại học phí còn phải thu.'],
            ['5. NGÀY THU nhận các dạng dd/mm/yyyy hoặc dd/mm/yyyy hh:mm.'],
            ['6. Chỉ những dòng có nhập dữ liệu cần cập nhật mới được xử lý.'],
        ], null, 'A1');
        $guide->mergeCells('A1:H1');
        $guide->getStyle('A1:H1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 15, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF166534']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $guide->getColumnDimension('A')->setWidth(82);

        $spreadsheet->setActiveSheetIndex(0);

        return response()->streamDownload(
            function () use ($spreadsheet): void {
                (new Xlsx($spreadsheet))->save('php://output');
                $spreadsheet->disconnectWorksheets();
            },
            'mau-cap-nhat-thu-hoc-phi.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    public function outstandingSheet(iterable $charges, string $filename = 'danh-sach-hoc-phi-con-no.xlsx'): StreamedResponse
    {
        $headers = array_merge(self::HEADERS, [
            'CÔNG NỢ CÒN LẠI',
            'LẦN THU CHỜ',
            'GỢI Ý CẬP NHẬT',
        ]);

        $rows = [];
        $index = 1;

        foreach ($charges as $charge) {
            $charge->loadMissing(['student', 'languageClass', 'payments']);
            $pendingPayments = $charge->payments->where('receipt_status', 'pending')->values();

            if ($pendingPayments->isNotEmpty()) {
                foreach ($pendingPayments as $payment) {
                    $rows[] = [
                        $index++,
                        $charge->languageClass?->code ?: '',
                        $charge->code,
                        $charge->student?->name ?: '',
                        $charge->student?->date_of_birth?->format('d/m/Y') ?: '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        number_format($charge->remainingAmount(), 0, ',', '.'),
                        number_format((float) $payment->amount + (float) $payment->book_amount, 0, ',', '.').'đ ngày '.$payment->paid_at?->format('d/m/Y H:i'),
                        'Điền số phiếu thu rồi import lại để bổ sung phiếu đang chờ.',
                    ];
                }
                continue;
            }

            if ($charge->remainingAmount() <= 0) {
                continue;
            }

            $rows[] = [
                $index++,
                $charge->languageClass?->code ?: '',
                $charge->code,
                $charge->student?->name ?: '',
                $charge->student?->date_of_birth?->format('d/m/Y') ?: '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                number_format($charge->remainingAmount(), 0, ',', '.'),
                '',
                'Điền số tiền/phiếu/ngày thu rồi import lại để ghi nhận thu tiền còn nợ.',
            ];
        }

        if (! SpreadsheetSupport::hasZipArchive()) {
            return SpreadsheetSupport::streamCsvDownload(
                SpreadsheetSupport::replaceExtension($filename, 'csv'),
                $headers,
                $rows
            );
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setTitle('Danh sách học phí còn nợ')
            ->setSubject('Xuất danh sách học phí còn nợ để cập nhật lại phiếu thu');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('CON NO');
        $sheet->fromArray($headers, null, 'A1');
        if ($rows !== []) {
            $sheet->fromArray($rows, null, 'A2');
        }
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:P1');
        $sheet->getRowDimension(1)->setRowHeight(30);
        $sheet->getStyle('A1:P1')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF92400E']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('B2:C5001')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $sheet->getStyle('E2:E5001')->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $sheet->getStyle('F2:F5001')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $sheet->getStyle('G2:G5001')->getNumberFormat()->setFormatCode('dd/mm/yyyy hh:mm');
        $sheet->getStyle('H2:I5001')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1);
        $sheet->getStyle('A1:P5001')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

        foreach ([
            'A' => 7, 'B' => 18, 'C' => 18, 'D' => 28, 'E' => 15, 'F' => 18, 'G' => 20,
            'H' => 18, 'I' => 14, 'J' => 18, 'K' => 14, 'L' => 14, 'M' => 34, 'N' => 16,
            'O' => 24, 'P' => 42,
        ] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        $this->addListValidation($sheet, 'J2:J5001', ['Tiền mặt', 'Chuyển khoản', 'Thẻ', 'Khác']);
        $this->addListValidation($sheet, 'K2:K5001', ['Có', 'Không']);

        return response()->streamDownload(
            function () use ($spreadsheet): void {
                (new Xlsx($spreadsheet))->save('php://output');
                $spreadsheet->disconnectWorksheets();
            },
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /**
     * @param array<int, mixed> $row
     * @param array<string, int> $headers
     */
    private function importRow(array $row, array $headers, ?int $userId): string
    {
        $context = $this->rowContext($row, $headers);
        if (! $context['has_manual_change']) {
            return 'skipped';
        }

        $charge = $context['charge'];
        $pendingPayments = $charge->payments()
            ->where('receipt_status', 'pending')
            ->orderBy('paid_at')
            ->get();

        if ($pendingPayments->count() === 1 && ! $context['has_tuition'] && ! $context['has_book']) {
            $payment = $pendingPayments->first();
            $this->ensureUniqueReceiptCode($context['receipt_code'], $payment->id);
            $isConfirmed = $context['receipt_code'] !== null;
            $payment->update([
                'receipt_code' => $context['receipt_code'],
                'receipt_status' => $isConfirmed ? 'confirmed' : 'pending',
                'confirmed_at' => $isConfirmed ? now() : null,
                'paid_at' => $context['paid_at'] ?? $payment->paid_at,
                'payment_method' => $context['has_method'] ? $context['payment_method'] : $payment->payment_method,
                'note' => $context['note'] ?: $payment->note,
            ]);

            $this->refreshCharge($charge->fresh(), $payment->fresh());

            return 'updated';
        }

        if (
            ! $context['has_tuition']
            && ! $context['has_book']
            && $context['receipt_code'] !== null
            && ! $context['has_paid_at']
            && ! $context['has_method']
            && $context['ratio'] === null
            && $context['note'] === null
        ) {
            throw new \RuntimeException('Khoản này chưa có lần thu chờ. Hãy nhập thêm ngày thu hoặc số tiền học phí để tạo lần thu mới.');
        }

        $charge = $charge->fresh(['payments', 'lead', 'languageClass']);
        $remaining = $charge->remainingAmount();
        $tuitionAmount = $context['tuition_amount'] ?? $remaining;

        if ($tuitionAmount <= 0) {
            throw new \RuntimeException('Khoản thu này không còn tiền học phí để ghi nhận thêm.');
        }
        if ($tuitionAmount > $remaining + 0.001) {
            throw new \RuntimeException('Số tiền học phí vượt quá công nợ còn lại '.number_format($remaining).'đ.');
        }

        $this->ensureUniqueReceiptCode($context['receipt_code']);
        $isConfirmed = $context['receipt_code'] !== null;

        $payment = $charge->payments()->create([
            'receipt_code' => $context['receipt_code'],
            'receipt_status' => $isConfirmed ? 'confirmed' : 'pending',
            'confirmed_at' => $isConfirmed ? now() : null,
            'amount' => $tuitionAmount,
            'book_amount' => $context['book_amount'],
            'paid_at' => $context['paid_at'] ?? now(),
            'payment_method' => $context['payment_method'],
            'reference' => null,
            'note' => $context['note'],
            'collected_by' => $userId,
        ]);

        $this->refreshCharge($charge->fresh(), $payment->fresh());

        return 'created';
    }

    /**
     * @param array<int, mixed> $row
     * @param array<string, int> $headers
     * @return array<string, mixed>|null
     */
    private function previewRow(array $row, array $headers): ?array
    {
        $context = $this->rowContext($row, $headers);
        if (! $context['has_manual_change']) {
            return null;
        }

        /** @var LanguageTuitionCharge $charge */
        $charge = $context['charge'];
        $simulated = $this->simulateChargeTotals($charge, $context['ratio']);
        $pendingPayments = $charge->payments()
            ->where('receipt_status', 'pending')
            ->orderBy('paid_at')
            ->get();

        if ($pendingPayments->count() === 1 && ! $context['has_tuition'] && ! $context['has_book']) {
            $pendingPayment = $pendingPayments->first();

            return [
                'student_name' => $context['student']->name,
                'date_of_birth' => $context['date_of_birth']->format('d/m/Y'),
                'class_code' => $charge->languageClass?->code ?: 'Chưa xếp lớp',
                'charge_code' => $charge->code,
                'action' => 'Cập nhật phiếu chờ',
                'receipt_code' => $context['receipt_code'] ?: 'Bổ sung sau',
                'paid_at' => ($context['paid_at'] ?? $pendingPayment->paid_at)?->format('d/m/Y H:i'),
                'tuition_amount' => (float) $pendingPayment->amount,
                'book_amount' => (float) $pendingPayment->book_amount,
                'payment_method' => $context['payment_method'],
                'ratio_label' => $context['ratio'] === null ? 'Giữ nguyên' : $this->formatRatioLabel($context['ratio']),
                'remaining' => max(0, $simulated['payable_amount'] - $simulated['settled_amount']),
            ];
        }

        if (
            ! $context['has_tuition']
            && ! $context['has_book']
            && $context['receipt_code'] !== null
            && ! $context['has_paid_at']
            && ! $context['has_method']
            && $context['ratio'] === null
            && $context['note'] === null
        ) {
            throw new \RuntimeException('Khoản này chưa có lần thu chờ. Hãy nhập thêm ngày thu hoặc số tiền học phí để tạo lần thu mới.');
        }

        $remaining = max(0, $simulated['payable_amount'] - $simulated['settled_amount']);
        $tuitionAmount = $context['tuition_amount'] ?? $remaining;

        if ($tuitionAmount <= 0) {
            throw new \RuntimeException('Khoản thu này không còn tiền học phí để ghi nhận thêm.');
        }
        if ($tuitionAmount > $remaining + 0.001) {
            throw new \RuntimeException('Số tiền học phí vượt quá công nợ còn lại '.number_format($remaining).'đ.');
        }

        return [
            'student_name' => $context['student']->name,
            'date_of_birth' => $context['date_of_birth']->format('d/m/Y'),
            'class_code' => $charge->languageClass?->code ?: 'Chưa xếp lớp',
            'charge_code' => $charge->code,
            'action' => 'Tạo lần thu mới',
            'receipt_code' => $context['receipt_code'] ?: 'Bổ sung sau',
            'paid_at' => ($context['paid_at'] ?? now())->format('d/m/Y H:i'),
            'tuition_amount' => $tuitionAmount,
            'book_amount' => $context['book_amount'],
            'payment_method' => $context['payment_method'],
            'ratio_label' => $context['ratio'] === null ? 'Giữ nguyên' : $this->formatRatioLabel($context['ratio']),
            'remaining' => $remaining,
        ];
    }

    /**
     * @param array<int, mixed> $row
     * @param array<string, int> $headers
     * @return array<string, mixed>
     */
    private function rowContext(array $row, array $headers): array
    {
        $name = trim((string) $this->cell($row, $headers, 'HO TEN'));
        if ($name === '') {
            throw new \RuntimeException('Thiếu họ tên học viên.');
        }

        $dateOfBirth = $this->parseDate($this->cell($row, $headers, 'NGAY SINH'), 'ngày sinh');
        if (! $dateOfBirth) {
            throw new \RuntimeException('Thiếu ngày sinh học viên.');
        }

        $student = $this->findStudent($name, $dateOfBirth);
        $charge = $this->findCharge(
            $student,
            $this->nullableUpper($this->cell($row, $headers, 'MA KHOAN THU')),
            $this->nullableUpper($this->cell($row, $headers, 'MA LOP'))
        );

        $rawReceiptCode = $this->nullableString($this->cell($row, $headers, 'SO PHIEU THU'));
        $receiptLooksLikeTransferMarker = $this->receiptCodeLooksLikeTransferMarker($rawReceiptCode);
        if ($receiptLooksLikeTransferMarker) {
            $rawReceiptCode = null;
        }
        $rawPaidAtValue = $this->cell($row, $headers, 'NGAY THU');
        $rawTuitionValue = $this->cell($row, $headers, 'SO TIEN HOC PHI');
        $rawBookValue = $this->cell($row, $headers, 'TIEN SACH');
        $rawMethodValue = $this->cell($row, $headers, 'HINH THUC');
        $rawNote = $this->nullableString($this->cell($row, $headers, 'GHI CHU'));
        $hasPaidAt = $this->hasCellInput($rawPaidAtValue);
        $hasTuition = $this->hasCellInput($rawTuitionValue);
        $hasBook = $this->hasCellInput($rawBookValue);
        $hasMethod = $this->hasCellInput($rawMethodValue);

        $charge->loadMissing(['languageClass', 'lead', 'payments']);
        $ratio = $this->resolveCollectionRatio($row, $headers);
        $shouldInferTransferMethod = ! $hasMethod
            && $receiptLooksLikeTransferMarker
            && ($hasPaidAt || $hasTuition || $hasBook || $ratio !== null || $rawNote !== null);
        $hasMethod = $hasMethod || $shouldInferTransferMethod;
        $hasManualChange = $rawReceiptCode !== null || $hasPaidAt || $hasTuition || $hasBook || $this->hasCellInput($rawMethodValue) || $ratio !== null || $rawNote !== null;

        if ($ratio !== null) {
            $this->applyCollectionRatio($charge, $ratio);
            $charge = $charge->fresh(['languageClass', 'lead', 'payments']);
        }

        return [
            'student' => $student,
            'date_of_birth' => $dateOfBirth,
            'charge' => $charge,
            'ratio' => $ratio,
            'receipt_code' => $rawReceiptCode,
            'paid_at' => $hasPaidAt ? $this->parseDateTime($rawPaidAtValue, 'ngày thu') : null,
            'tuition_amount' => $hasTuition ? $this->parseMoney($rawTuitionValue, 'số tiền học phí') : null,
            'book_amount' => $hasBook ? ($this->parseMoney($rawBookValue, 'tiền sách') ?? 0.0) : 0.0,
            'payment_method' => $this->resolvePaymentMethod($rawMethodValue, $shouldInferTransferMethod),
            'note' => $rawNote,
            'has_paid_at' => $hasPaidAt,
            'has_tuition' => $hasTuition,
            'has_book' => $hasBook,
            'has_method' => $hasMethod,
            'has_manual_change' => $hasManualChange,
        ];
    }

    /**
     * @return array{payable_amount:float,settled_amount:float}
     */
    private function simulateChargeTotals(LanguageTuitionCharge $charge, ?float $ratio): array
    {
        $charge->loadMissing(['payments', 'languageClass']);
        $paidAmount = (float) $charge->payments()->where('receipt_status', 'confirmed')->sum('amount');
        $settledAmount = $paidAmount + (float) $charge->credit_amount;

        if ($ratio === null) {
            return [
                'payable_amount' => (float) $charge->payable_amount,
                'settled_amount' => $settledAmount,
            ];
        }

        $baseOriginal = $charge->languageClass
            ? (float) $charge->languageClass->default_tuition
            : (float) $charge->original_amount;
        $originalAmount = round($baseOriginal * $ratio, 2);
        $discountAmount = round($originalAmount * (float) $charge->discount_percentage / 100, 2);
        $payableAmount = max(0, round($originalAmount - $discountAmount, 2));

        if ($payableAmount + 0.001 < $settledAmount) {
            throw new \RuntimeException('Không thể giảm học phí vì số đã thu/chuyển sang đang lớn hơn mức học phí mới.');
        }

        return [
            'payable_amount' => $payableAmount,
            'settled_amount' => $settledAmount,
        ];
    }

    private function formatRatioLabel(float $ratio): string
    {
        $ratioLabel = rtrim(rtrim(number_format($ratio * 100, 2, '.', ''), '0'), '.');

        return $ratioLabel.'% học phí';
    }

    private function buildStudentIdentityIndex(): void
    {
        $this->studentIdentityIndex = [];
        $this->studentExactIdentityIndex = [];

        LanguageStudent::query()
            ->whereNotNull('date_of_birth')
            ->get(['id', 'name', 'date_of_birth'])
            ->each(function (LanguageStudent $student): void {
                $date = $student->date_of_birth?->format('Y-m-d');
                $normalizedName = TextNormalizer::name($student->name);
                $exactName = TextNormalizer::exactName($student->name);
                if (! $date) {
                    return;
                }

                if ($normalizedName !== '') {
                    $this->studentIdentityIndex[$normalizedName.'|'.$date][$student->id] = $student;
                }
                if ($exactName !== '') {
                    $this->studentExactIdentityIndex[$exactName.'|'.$date][$student->id] = $student;
                }
            });
    }

    private function findStudent(string $name, Carbon $dateOfBirth): LanguageStudent
    {
        $dateKey = $dateOfBirth->format('Y-m-d');
        $exactMatches = $this->studentExactIdentityIndex[TextNormalizer::exactName($name).'|'.$dateKey] ?? [];
        if (count($exactMatches) > 1) {
            throw new \RuntimeException('Có nhiều học viên trùng họ tên và ngày sinh. Vui lòng kiểm tra lại dữ liệu học viên trước khi import.');
        }
        if (count($exactMatches) === 1) {
            return reset($exactMatches)->fresh();
        }

        $matches = $this->studentIdentityIndex[TextNormalizer::name($name).'|'.$dateKey] ?? [];
        if (count($matches) > 1) {
            throw new \RuntimeException('Có nhiều học viên trùng họ tên và ngày sinh. Vui lòng kiểm tra lại dữ liệu học viên trước khi import.');
        }
        if (count($matches) === 1) {
            return reset($matches)->fresh();
        }

        throw new \RuntimeException('Không tìm thấy học viên khớp họ tên và ngày sinh.');
    }

    private function findCharge(LanguageStudent $student, ?string $chargeCode, ?string $classCode): LanguageTuitionCharge
    {
        $query = LanguageTuitionCharge::query()
            ->with(['payments', 'languageClass'])
            ->where('language_student_id', $student->id)
            ->orderByDesc('created_at');

        if ($chargeCode) {
            $charge = (clone $query)->where('code', $chargeCode)->first();
            if (! $charge) {
                throw new \RuntimeException("Không tìm thấy khoản thu {$chargeCode} của học viên này.");
            }

            return $charge;
        }

        if ($classCode) {
            $charge = (clone $query)
                ->whereHas('languageClass', fn ($builder) => $builder->where('code', $classCode))
                ->first();
            if (! $charge) {
                throw new \RuntimeException("Không tìm thấy khoản thu theo mã lớp {$classCode} của học viên này.");
            }

            return $charge;
        }

        $openCharges = (clone $query)->get()->filter(
            fn (LanguageTuitionCharge $charge) => $charge->remainingAmount() > 0
                || $charge->payments->contains(fn (LanguageTuitionPayment $payment) => $payment->receipt_status === 'pending')
        )->values();

        if ($openCharges->count() > 1) {
            throw new \RuntimeException('Học viên đang có nhiều khoản thu mở. Hãy điền thêm mã lớp hoặc mã khoản thu để hệ thống xử lý đúng khoản.');
        }
        if ($openCharges->count() === 1) {
            return $openCharges->first();
        }

        throw new \RuntimeException('Không còn khoản thu mở nào để cập nhật cho học viên này.');
    }

    private function resolveCollectionRatio(array $row, array $headers): ?float
    {
        $halfClass = TextNormalizer::header((string) $this->cell($row, $headers, 'THU NUA LOP'));
        $ratioValue = $this->nullableString($this->cell($row, $headers, 'TY LE THU'));

        if ($halfClass === '' && $ratioValue === null) {
            return null;
        }

        $ratio = null;
        if ($ratioValue !== null) {
            $numeric = str_replace(['%', ' '], '', str_replace(',', '.', $ratioValue));
            if (! is_numeric($numeric)) {
                throw new \RuntimeException('Tỷ lệ thu phải là số phần trăm, ví dụ 50 hoặc 100.');
            }
            $ratio = round(((float) $numeric) / 100, 4);
        }

        if ($halfClass !== '') {
            $halfRatio = match ($halfClass) {
                'CO', 'YES', 'Y', '1' => 0.5,
                'KHONG', 'NO', 'N', '0' => 1.0,
                default => throw new \RuntimeException('Cột Thu nửa lớp chỉ nhận Có hoặc Không.'),
            };

            if ($ratio !== null && abs($ratio - $halfRatio) > 0.0001) {
                throw new \RuntimeException('Thu nửa lớp và Tỷ lệ thu (%) đang mâu thuẫn nhau.');
            }

            $ratio = $halfRatio;
        }

        if ($ratio === null || $ratio <= 0 || $ratio > 1) {
            throw new \RuntimeException('Tỷ lệ thu phải lớn hơn 0% và không vượt quá 100%.');
        }

        return $ratio;
    }

    private function applyCollectionRatio(LanguageTuitionCharge $charge, float $ratio): void
    {
        $charge->loadMissing(['payments', 'languageClass']);
        $baseOriginal = $charge->languageClass
            ? (float) $charge->languageClass->default_tuition
            : (float) $charge->original_amount;
        $originalAmount = round($baseOriginal * $ratio, 2);
        $discountPercentage = (float) $charge->discount_percentage;
        $discountAmount = round($originalAmount * $discountPercentage / 100, 2);
        $payableAmount = max(0, round($originalAmount - $discountAmount, 2));
        $paidAmount = (float) $charge->payments()->where('receipt_status', 'confirmed')->sum('amount');
        $settledAmount = $paidAmount + (float) $charge->credit_amount;

        if ($payableAmount + 0.001 < $settledAmount) {
            throw new \RuntimeException('Không thể giảm học phí vì số đã thu/chuyển sang đang lớn hơn mức học phí mới.');
        }

        $hasPendingReceipt = $charge->payments()->where('receipt_status', 'pending')->exists();
        $status = $hasPendingReceipt
            ? 'pending_receipt'
            : ($settledAmount >= $payableAmount ? 'paid' : ($settledAmount > 0 ? 'partial' : 'unpaid'));

        $ratioNote = 'Điều chỉnh tỷ lệ thu từ import Excel: '.$this->formatRatioLabel($ratio).'.';
        $note = trim((string) $charge->note);
        if (! str_contains($note, $ratioNote)) {
            $note = trim($note === '' ? $ratioNote : $note."\n".$ratioNote);
        }

        $charge->update([
            'original_amount' => $originalAmount,
            'discount_amount' => $discountAmount,
            'payable_amount' => $payableAmount,
            'paid_amount' => $paidAmount,
            'status' => $status,
            'note' => $note,
        ]);
    }

    private function ensureUniqueReceiptCode(?string $receiptCode, ?int $ignorePaymentId = null): void
    {
        if ($receiptCode === null || $receiptCode === '') {
            return;
        }

        $exists = LanguageTuitionPayment::query()
            ->where('receipt_code', $receiptCode)
            ->when($ignorePaymentId, fn ($query) => $query->whereKeyNot($ignorePaymentId))
            ->exists();

        if ($exists) {
            throw new \RuntimeException('Số phiếu thu đã tồn tại: '.$receiptCode.'.');
        }
    }

    private function receiptCodeLooksLikeTransferMarker(?string $receiptCode): bool
    {
        if ($receiptCode === null || $receiptCode === '') {
            return false;
        }

        return in_array(TextNormalizer::header($receiptCode), [
            'CK',
            'CHUYEN KHOAN',
            'CK NAM A',
            'CK NAMA',
            'NAM A',
            'NAMA',
            'NAM A BANK',
            'NAMABANK',
        ], true);
    }

    private function resolvePaymentMethod(mixed $value, bool $inferTransfer = false): string
    {
        if ($this->hasCellInput($value)) {
            return $this->paymentMethod($value);
        }

        return $inferTransfer ? 'transfer' : 'cash';
    }

    private function refreshCharge(LanguageTuitionCharge $charge, LanguageTuitionPayment $payment): void
    {
        $activePayments = $charge->payments()->where('receipt_status', '!=', 'cancelled');
        $confirmedPayments = $charge->payments()->where('receipt_status', 'confirmed');
        $paidAmount = (float) (clone $confirmedPayments)->sum('amount');
        $hasPendingReceipt = (clone $activePayments)->where('receipt_status', 'pending')->exists();
        $settledAmount = $paidAmount + (float) $charge->credit_amount;
        $status = $hasPendingReceipt
            ? 'pending_receipt'
            : ($settledAmount >= (float) $charge->payable_amount ? 'paid' : ($settledAmount > 0 ? 'partial' : 'unpaid'));

        $charge->update([
            'paid_amount' => $paidAmount,
            'status' => $status,
        ]);

        if ($payment->receipt_status !== 'confirmed') {
            LanguageMonthlyTargetRecord::where('language_tuition_payment_id', $payment->id)->delete();
            return;
        }

        $charge->loadMissing('lead');
        LanguageMonthlyTargetRecord::updateOrCreate(
            ['language_tuition_payment_id' => $payment->id],
            [
                'record_year' => $payment->paid_at->year,
                'record_month' => $payment->paid_at->month,
                'language_student_id' => $charge->language_student_id,
                'language_lead_id' => $charge->language_lead_id,
                'language_collaborator_id' => $charge->lead?->language_collaborator_id,
                'language_course_id' => $charge->language_course_id,
                'quantity' => 1,
                'revenue' => (float) $payment->amount + (float) $payment->book_amount,
                'note' => 'Thu học phí '.$charge->code,
            ]
        );
    }

    private function paymentMethod(mixed $value): string
    {
        $normalized = TextNormalizer::header((string) $value);

        if (
            $normalized === 'CK'
            || str_starts_with($normalized, 'CK ')
            || str_contains($normalized, 'CHUYEN KHOAN')
            || in_array($normalized, ['NAM A', 'NAMA', 'NAM A BANK', 'NAMABANK'], true)
        ) {
            return 'transfer';
        }

        return match ($normalized) {
            '', 'TIEN MAT', 'CASH' => 'cash',
            'CHUYEN KHOAN', 'TRANSFER' => 'transfer',
            'THE', 'CARD' => 'card',
            'KHAC', 'OTHER' => 'other',
            default => throw new \RuntimeException('Hình thức chỉ nhận Tiền mặt, Chuyển khoản, Thẻ hoặc Khác.'),
        };
    }

    private function parseMoney(mixed $value, string $label): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return round((float) $value, 2);
        }

        $normalized = str_replace(['đ', 'Đ', 'd', 'D', '₫', ' '], '', (string) $value);
        $normalized = preg_replace('/[^0-9,.\-]/', '', $normalized) ?? '';
        $lastDot = strrpos($normalized, '.');
        $lastComma = strrpos($normalized, ',');

        if ($lastDot !== false && $lastComma !== false) {
            if ($lastDot > $lastComma) {
                $normalized = str_replace(',', '', $normalized);
            } else {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            }
        } elseif (substr_count($normalized, '.') > 1) {
            $normalized = str_replace('.', '', $normalized);
        } elseif (substr_count($normalized, ',') > 1) {
            $normalized = str_replace(',', '', $normalized);
        } elseif (str_contains($normalized, ',')) {
            $normalized = str_replace(',', '.', $normalized);
        }

        if ($normalized === '' || ! is_numeric($normalized)) {
            throw new \RuntimeException(ucfirst($label).' không đúng định dạng số.');
        }

        return round((float) $normalized, 2);
    }

    private function parseDate(mixed $value, string $label): ?Carbon
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance(\DateTime::createFromInterface($value))->startOfDay();
        }

        if ($value === null || trim((string) $value) === '') {
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
        $text = preg_replace('/\s+\d{1,2}:\d{1,2}(:\d{1,2})?$/', '', $text) ?: $text;
        $normalized = str_replace(['.', '-'], '/', $text);

        foreach (['d/m/Y', 'j/n/Y', 'd/m/y', 'j/n/y', 'Y/m/d', 'Y/n/j'] as $format) {
            try {
                return Carbon::createFromFormat($format, $normalized)->startOfDay();
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($text)->startOfDay();
        } catch (\Throwable) {
        }

        throw new \RuntimeException(ucfirst($label).' phải có định dạng ngày/tháng/năm.');
    }

    private function parseDateTime(mixed $value, string $label): ?Carbon
    {
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance(\DateTime::createFromInterface($value));
        }

        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($value));
            } catch (\Throwable) {
                throw new \RuntimeException(ucfirst($label).' không đúng định dạng.');
            }
        }

        $text = trim((string) $value);
        $normalized = str_replace(['.', '-'], '/', $text);
        foreach ([
            'd/m/Y H:i:s', 'd/m/Y H:i', 'j/n/Y H:i:s', 'j/n/Y H:i',
            'd/m/Y', 'j/n/Y', 'Y/m/d H:i:s', 'Y/m/d H:i', 'Y/m/d',
        ] as $format) {
            try {
                return Carbon::createFromFormat($format, $normalized);
            } catch (\Throwable) {
            }
        }

        try {
            return Carbon::parse($text);
        } catch (\Throwable) {
        }

        throw new \RuntimeException(ucfirst($label).' phải có định dạng ngày hợp lệ.');
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
        return array_key_exists($header, $headers)
            ? ($row[$headers[$header]] ?? null)
            : null;
    }

    private function hasCellInput(mixed $value): bool
    {
        if ($value instanceof \DateTimeInterface) {
            return true;
        }

        if ($value === null) {
            return false;
        }

        return trim((string) $value) !== '';
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
