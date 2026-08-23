<?php

namespace App\Http\Controllers;

use App\Support\ExcelExporter;
use App\Support\SpreadsheetSupport;
use App\Support\TextNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ToolController extends Controller
{
    public function index(): View
    {
        return view('tools.index', [
            'bank' => LanguageTuitionController::bankSettings(),
        ]);
    }

    public function printShippingLabel(Request $request): View
    {
        $data = $request->validate([
            'order_code' => ['required', 'string', 'max:50'],
            'carrier_name' => ['nullable', 'string', 'max:100'],
            'sender_name' => ['required', 'string', 'max:150'],
            'sender_phone' => ['nullable', 'string', 'max:30'],
            'sender_address' => ['required', 'string', 'max:500'],
            'recipient_name' => ['required', 'string', 'max:150'],
            'recipient_phone' => ['required', 'string', 'max:30'],
            'recipient_address' => ['required', 'string', 'max:500'],
            'cod_amount' => ['nullable', 'numeric', 'min:0'],
            'package_note' => ['nullable', 'string', 'max:1000'],
        ]);

        return view('tools.shipping-label', [
            'label' => $data,
            'autoPrint' => ! $request->boolean('preview'),
        ]);
    }

    public function tuitionQrTemplate(): StreamedResponse
    {
        return ExcelExporter::download(
            'mau-danh-sach-qr-hoc-phi.xlsx',
            ['HỌ TÊN', 'MÃ LỚP', 'SỐ TIỀN', 'NỘI DUNG', 'GHI CHÚ'],
            [[
                'Nguyễn Văn A',
                'SKY-A1-01',
                1500000,
                '',
                'Nếu để trống nội dung, hệ thống sẽ ghép Họ tên + Mã lớp.',
            ]]
        );
    }

    public function previewTuitionQrs(Request $request): View|RedirectResponse
    {
        $bank = LanguageTuitionController::bankSettings();
        if (! $bank['enabled']) {
            throw ValidationException::withMessages([
                'file' => 'Chưa cấu hình tài khoản ngân hàng nhận học phí nên chưa thể tạo QR hàng loạt.',
            ]);
        }

        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:20480'],
        ]);

        $file = $request->file('file');
        if (! SpreadsheetSupport::canReadUpload($file)) {
            return back()->withErrors([
                'file' => SpreadsheetSupport::missingZipImportMessage(
                    SpreadsheetSupport::uploadedExtension($file)
                ),
            ]);
        }

        $rows = IOFactory::load($file->getRealPath())->getActiveSheet()->toArray(null, true, true, false);
        if (count($rows) < 2) {
            throw ValidationException::withMessages([
                'file' => 'File Excel chưa có dữ liệu để tạo QR.',
            ]);
        }

        $headers = [];
        foreach ($rows[0] as $index => $header) {
            $headers[TextNormalizer::header((string) $header)] = $index;
        }

        foreach (['HO TEN', 'SO TIEN'] as $required) {
            if (! array_key_exists($required, $headers)) {
                throw ValidationException::withMessages([
                    'file' => 'Thiếu cột bắt buộc '.$required.'.',
                ]);
            }
        }

        $items = [];
        $errors = [];

        foreach (array_slice($rows, 1) as $offset => $row) {
            $rowNumber = $offset + 2;
            $name = trim((string) ($row[$headers['HO TEN']] ?? ''));
            $classCode = trim((string) ($row[$headers['MA LOP'] ?? -1] ?? ''));
            $customContent = trim((string) ($row[$headers['NOI DUNG'] ?? -1] ?? ''));
            $note = trim((string) ($row[$headers['GHI CHU'] ?? -1] ?? ''));
            $amount = $this->number($row[$headers['SO TIEN']] ?? 0);

            if ($name === '' && $classCode === '' && $customContent === '' && $amount <= 0) {
                continue;
            }

            if ($name === '') {
                $errors[] = "Dòng {$rowNumber}: thiếu HỌ TÊN.";
                continue;
            }

            if ($amount <= 0) {
                $errors[] = "Dòng {$rowNumber}: SỐ TIỀN phải lớn hơn 0.";
                continue;
            }

            $content = $customContent !== ''
                ? $customContent
                : trim($name.' '.($classCode !== '' ? $classCode : ''));

            $items[] = [
                'row_number' => $rowNumber,
                'name' => $name,
                'class_code' => $classCode,
                'amount' => $amount,
                'content' => $content,
                'note' => $note,
                'qr_url' => $this->tuitionQrImageUrl($bank, $amount, $content),
            ];
        }

        if ($items === []) {
            throw ValidationException::withMessages([
                'file' => $errors[0] ?? 'Không có dòng hợp lệ để tạo QR học phí.',
            ]);
        }

        return view('tools.tuition-qr-preview', [
            'bank' => $bank,
            'items' => $items,
            'errors' => $errors,
            'sourceName' => $file->getClientOriginalName(),
        ]);
    }

    private function tuitionQrImageUrl(array $bank, float $amount, string $content): string
    {
        return 'https://img.vietqr.io/image/'.$bank['bin'].'-'.$bank['account_number'].'-compact2.png?'.http_build_query([
            'amount' => (int) round($amount),
            'addInfo' => $content,
            'accountName' => $bank['account_name'],
        ]);
    }

    private function number(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $clean = preg_replace('/[^0-9,.-]/', '', trim((string) $value));
        if ($clean === '' || $clean === '-') {
            return 0;
        }

        $lastComma = strrpos($clean, ',');
        $lastDot = strrpos($clean, '.');
        if ($lastComma !== false && $lastDot !== false) {
            $decimalPos = max($lastComma, $lastDot);
            $decimalDigits = strlen($clean) - $decimalPos - 1;
            $decimalSeparator = $clean[$decimalPos];
            $thousandSeparator = $decimalSeparator === ',' ? '.' : ',';
            $clean = str_replace($thousandSeparator, '', $clean);
            $clean = $decimalDigits <= 2 ? str_replace(',', '.', $clean) : str_replace([',', '.'], '', $clean);
        } elseif (preg_match('/^-?\d+[,.]\d{1,2}$/', $clean)) {
            $clean = str_replace(',', '.', $clean);
        } else {
            $clean = str_replace([',', '.'], '', $clean);
        }

        return (float) $clean;
    }
}
