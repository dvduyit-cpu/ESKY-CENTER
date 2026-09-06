<?php

namespace App\Http\Controllers;

use App\Support\ExcelExporter;
use App\Support\SpreadsheetSupport;
use App\Support\TextNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ToolController extends Controller
{
    public function index(): View
    {
        return view('tools.index');
    }

    public function shippingIndex(): View
    {
        return view('tools.shipping-index');
    }

    public function tuitionIndex(): View
    {
        return view('tools.tuition-index', [
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
            'backRoute' => route('tools.shipping.index'),
            'backLabel' => 'Quay lại nhóm In ấn & vận chuyển',
        ]);
    }

    public function tuitionQrTemplate(): StreamedResponse
    {
        return ExcelExporter::download(
            'mau-danh-sach-qr-hoc-phi.xlsx',
            ['HỌ TÊN', 'MÃ LỚP', 'SỐ TIỀN', 'GHI CHÚ'],
            [[
                'Nguyễn Văn A',
                'SKY-A1-01',
                1500000,
                'Lời nhắn ngân hàng chỉ gồm họ tên và mã lớp. Ảnh QR cũng hiển thị hai thông tin này.',
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

        foreach (['HO TEN', 'MA LOP', 'SO TIEN'] as $required) {
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
            $note = trim((string) ($row[$headers['GHI CHU'] ?? -1] ?? ''));
            $amount = $this->number($row[$headers['SO TIEN']] ?? 0);

            if ($name === '' && $classCode === '' && $amount <= 0) {
                continue;
            }

            if ($name === '') {
                $errors[] = "Dòng {$rowNumber}: thiếu HỌ TÊN.";
                continue;
            }

            if ($classCode === '') {
                $errors[] = "Dòng {$rowNumber}: thiếu MÃ LỚP.";
                continue;
            }

            if ($amount <= 0) {
                $errors[] = "Dòng {$rowNumber}: SỐ TIỀN phải lớn hơn 0.";
                continue;
            }

            $content = $this->tuitionQrContent($name, $classCode);

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

        $request->session()->put('tools.tuition_qr_preview', [
            'source_name' => $file->getClientOriginalName(),
            'items' => $items,
        ]);

        return view('tools.tuition-qr-preview', [
            'bank' => $bank,
            'items' => $items,
            'errors' => $errors,
            'sourceName' => $file->getClientOriginalName(),
            'backRoute' => route('tools.tuition.index'),
            'backLabel' => 'Quay lại nhóm QR & học phí',
        ]);
    }

    public function downloadPreviewTuitionQr(Request $request, int $index): StreamedResponse|RedirectResponse
    {
        $item = $this->previewTuitionQrItem($request, $index);
        $binary = $this->downloadTuitionQrImage($item);

        return response()->streamDownload(
            fn () => print($binary),
            $this->tuitionQrFilename($item, $index),
            ['Content-Type' => 'image/png']
        );
    }

    public function previewTuitionQrImage(Request $request, int $index)
    {
        $item = $this->previewTuitionQrItem($request, $index);

        return response($this->downloadTuitionQrImage($item), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function downloadAllPreviewTuitionQrs(Request $request): BinaryFileResponse|RedirectResponse
    {
        if (! SpreadsheetSupport::hasZipArchive()) {
            throw ValidationException::withMessages([
                'file' => SpreadsheetSupport::missingZipExportMessage(),
            ]);
        }

        $preview = $this->previewTuitionQrPreview($request);
        $archivePath = tempnam(sys_get_temp_dir(), 'tuition-qr-');

        if ($archivePath === false) {
            throw ValidationException::withMessages([
                'file' => 'Không thể tạo file tạm để đóng gói danh sách QR.',
            ]);
        }

        $zip = new \ZipArchive();
        if ($zip->open($archivePath, \ZipArchive::OVERWRITE) !== true) {
            @unlink($archivePath);

            throw ValidationException::withMessages([
                'file' => 'Không thể khởi tạo file zip để tải tất cả QR.',
            ]);
        }

        foreach ($preview['items'] as $index => $item) {
            $zip->addFromString($this->tuitionQrFilename($item, $index), $this->downloadTuitionQrImage($item));
        }

        $zip->close();

        $archiveName = 'danh-sach-qr-hoc-phi-'.Str::slug(
            pathinfo((string) $preview['source_name'], PATHINFO_FILENAME) ?: 'xem-truoc'
        ).'.zip';

        return response()
            ->download($archivePath, $archiveName, ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    private function tuitionQrImageUrl(array $bank, float $amount, string $content): string
    {
        return 'https://img.vietqr.io/image/'.$bank['bin'].'-'.$bank['account_number'].'-compact2.png?'.http_build_query(
            [
                'amount' => (int) round($amount),
                'addInfo' => $content,
                'accountName' => $bank['account_name'],
            ],
            '',
            '&',
            PHP_QUERY_RFC3986
        );
    }

    private function tuitionQrContent(string $name, string $classCode): string
    {
        return sprintf('%s | Mã lớp: %s', $name, $classCode);
    }

    private function previewTuitionQrPreview(Request $request): array
    {
        $preview = $request->session()->get('tools.tuition_qr_preview');

        if (! is_array($preview) || ! isset($preview['items']) || ! is_array($preview['items']) || $preview['items'] === []) {
            throw ValidationException::withMessages([
                'file' => 'Phiên xem trước QR đã hết hạn. Vui lòng tải lại file Excel để tạo danh sách mới.',
            ]);
        }

        return $preview;
    }

    private function previewTuitionQrItem(Request $request, int $index): array
    {
        $preview = $this->previewTuitionQrPreview($request);

        if (! array_key_exists($index, $preview['items'])) {
            throw ValidationException::withMessages([
                'file' => 'Không tìm thấy QR cần tải trong phiên xem trước hiện tại.',
            ]);
        }

        return $preview['items'][$index];
    }

    private function downloadTuitionQrImage(array $item): string
    {
        $response = Http::timeout(20)->retry(2, 250)->get((string) ($item['qr_url'] ?? ''));

        if (! $response->successful() || $response->body() === '') {
            $name = trim((string) ($item['name'] ?? 'QR'));

            throw ValidationException::withMessages([
                'file' => 'Không thể tải ảnh QR cho '.$name.'. Vui lòng thử lại sau.',
            ]);
        }

        return $this->addTuitionQrDetails($response->body(), $item);
    }

    private function addTuitionQrDetails(string $qrImage, array $item): string
    {
        $fontPath = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Bold.ttf');

        if (! function_exists('imagecreatefromstring') || ! function_exists('imagettftext') || ! is_file($fontPath)) {
            throw ValidationException::withMessages([
                'file' => 'Máy chủ chưa có đủ thư viện để thêm thông tin vào ảnh QR.',
            ]);
        }

        $source = @imagecreatefromstring($qrImage);
        if ($source === false) {
            throw ValidationException::withMessages([
                'file' => 'Ảnh QR nhận được không đúng định dạng PNG.',
            ]);
        }

        $sourceWidth = imagesx($source);
        $sourceHeight = imagesy($source);
        $this->removeEmbeddedTuitionQrAmount($source, $sourceWidth, $sourceHeight);
        $padding = 18;
        $fontSize = max(14, min(20, (int) floor($sourceWidth / 26)));
        $maxTextWidth = $sourceWidth - ($padding * 2);
        $details = [
            'Học viên: '.trim((string) ($item['name'] ?? '')),
            'Mã lớp: '.trim((string) ($item['class_code'] ?? '')),
            'Số tiền: '.number_format((float) ($item['amount'] ?? 0), 0, ',', '.').'đ',
        ];
        $lines = [];
        foreach ($details as $detail) {
            foreach ($this->wrapTuitionQrText($detail, $fontPath, $fontSize, $maxTextWidth) as $line) {
                $lines[] = $line;
            }
        }

        $lineHeight = $fontSize + 10;
        $footerHeight = ($padding * 2) + (count($lines) * $lineHeight) + 1;
        $canvas = imagecreatetruecolor($sourceWidth, $sourceHeight + $footerHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        $textColor = imagecolorallocate($canvas, 17, 24, 39);
        $borderColor = imagecolorallocate($canvas, 203, 213, 225);
        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $source, 0, 0, 0, 0, $sourceWidth, $sourceHeight);
        imageline($canvas, $padding, $sourceHeight, $sourceWidth - $padding, $sourceHeight, $borderColor);

        $baseline = $sourceHeight + $padding + $fontSize;
        foreach ($lines as $line) {
            imagettftext($canvas, $fontSize, 0, $padding, $baseline, $textColor, $fontPath, $line);
            $baseline += $lineHeight;
        }

        ob_start();
        imagepng($canvas);
        $image = (string) ob_get_clean();
        imagedestroy($canvas);
        imagedestroy($source);

        return $image;
    }

    private function removeEmbeddedTuitionQrAmount($source, int $width, int $height): void
    {
        $amountHeight = max(38, (int) ceil($height * 0.07));
        $white = imagecolorallocate($source, 255, 255, 255);
        imagefilledrectangle($source, 0, $height - $amountHeight, $width, $height, $white);
    }

    private function wrapTuitionQrText(string $text, string $fontPath, int $fontSize, int $maxWidth): array
    {
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $lines = [];
        $line = '';

        foreach ($words as $word) {
            $candidate = $line === '' ? $word : $line.' '.$word;
            $bounds = imagettfbbox($fontSize, 0, $fontPath, $candidate);
            $width = $bounds === false ? 0 : $bounds[2] - $bounds[0];

            if ($line !== '' && $width > $maxWidth) {
                $lines[] = $line;
                $line = $word;
                continue;
            }

            $line = $candidate;
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines;
    }

    private function tuitionQrFilename(array $item, int $index): string
    {
        $name = Str::slug((string) ($item['name'] ?? 'hoc-vien')) ?: 'hoc-vien';
        $classCode = Str::slug((string) ($item['class_code'] ?? ''));
        $parts = ['qr-hoc-phi', str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)];

        if (! empty($item['row_number'])) {
            $parts[] = 'dong-'.$item['row_number'];
        }

        if ($classCode !== '') {
            $parts[] = $classCode;
        }

        $parts[] = $name;

        return implode('-', $parts).'.png';
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
