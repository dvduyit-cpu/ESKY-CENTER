<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SpreadsheetSupport
{
    public static function hasZipArchive(): bool
    {
        return extension_loaded('zip') && class_exists(\ZipArchive::class);
    }

    public static function canReadUpload(UploadedFile $file): bool
    {
        return ! self::uploadRequiresZip($file) || self::hasZipArchive();
    }

    public static function uploadRequiresZip(UploadedFile $file): bool
    {
        return in_array(self::uploadedExtension($file), ['xlsx', 'xlsm', 'xltx', 'xltm'], true);
    }

    public static function uploadedExtension(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();

        if ($extension === '') {
            $extension = $file->extension();
        }

        return strtolower((string) $extension);
    }

    public static function missingZipImportMessage(string $extension = 'xlsx'): string
    {
        $extension = ltrim(strtolower($extension), '.');

        return "Máy chủ chưa bật PHP ZipArchive nên chưa thể đọc file .{$extension}. "
            .'Bạn có thể nhập file .xls hoặc .csv, hoặc liên hệ quản trị viên để bật extension=zip.';
    }

    public static function missingZipExportMessage(): string
    {
        return 'Máy chủ chưa bật PHP ZipArchive nên chưa thể tạo file Excel .xlsx. '
            .'Hệ thống sẽ xuất file .csv thay thế hoặc bạn có thể liên hệ quản trị viên để bật extension=zip.';
    }

    public static function replaceExtension(string $filename, string $extension): string
    {
        $extension = ltrim($extension, '.');
        $baseName = pathinfo($filename, PATHINFO_FILENAME);

        return $baseName.'.'.$extension;
    }

    /**
     * @param iterable<int, array<int|string, mixed>> $rows
     */
    public static function streamCsvDownload(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $output = fopen('php://output', 'wb');

            if ($output === false) {
                throw new \RuntimeException('Không thể tạo dữ liệu CSV.');
            }

            fwrite($output, "\xEF\xBB\xBF");
            if ($headers !== []) {
                fputcsv($output, $headers);
            }

            foreach ($rows as $row) {
                fputcsv($output, array_map(
                    static fn (mixed $value): string => self::csvValue($value),
                    array_values($row)
                ));
            }

            fclose($output);
        }, self::replaceExtension($filename, 'csv'), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private static function csvValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }
}
