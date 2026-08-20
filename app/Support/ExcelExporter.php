<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelExporter
{
    /**
     * @param iterable<int, array<int|string, mixed>> $rows
     */
    public static function download(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        if (! SpreadsheetSupport::hasZipArchive()) {
            return SpreadsheetSupport::streamCsvDownload($filename, $headers, $rows);
        }

        return response()->streamDownload(function () use ($headers, $rows): void {
            $sheet = (new Spreadsheet())->getActiveSheet();
            $sheet->fromArray($headers, null, 'A1');

            $rowNumber = 2;
            foreach ($rows as $row) {
                $sheet->fromArray(array_values($row), null, 'A'.$rowNumber++);
            }

            $sheet->getStyle('A1:'.$sheet->getHighestColumn().'1')->getFont()->setBold(true);
            foreach (range('A', $sheet->getHighestColumn()) as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            (new Xlsx($sheet->getParent()))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
