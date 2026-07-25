<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\KpiRecord;
use App\Models\Personnel;
use App\Support\ActivityLogger;
use App\Support\KpiCalculator;
use App\Support\Period;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly KpiCalculator $calculator) {}

    public function index(Request $request): View
    {
        [$filters, $report] = $this->build($request);
        $rows = $report['rows'];
        if ($request->filled('status')) $rows = $rows->where('status', $request->string('status')->toString())->values();
        $totals = $this->totals($rows);
        $rows = $rows->values();

        return view('reports.index', [
            'filters' => $filters,
            'rows' => $rows,
            'totals' => $totals,
            'personnels' => Personnel::where('active', true)->where('type','!=','collaborator')->orderBy('name')->get(),
            'courses' => Course::where('active', true)->orderBy('name')->get(),
            'periodLabel' => Period::label($filters['period_type'], $filters['period_value'], $filters['year']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        [$filters, $report] = $this->build($request);
        $rows = $report['rows'];
        if ($request->filled('status')) $rows = $rows->where('status', $request->string('status')->toString())->values();
        $totals = $this->totals($rows);
        $periodLabel = Period::label($filters['period_type'], $filters['period_value'], $filters['year']);

        $spreadsheet = new Spreadsheet();
        $summary = $spreadsheet->getActiveSheet();
        $summary->setTitle('TONG HOP');
        $summary->mergeCells('A1:H1');
        $summary->setCellValue('A1', 'BÁO CÁO CHỈ TIÊU - '.mb_strtoupper($periodLabel));
        $summary->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setARGB('FFFFFFFF');
        $summary->getStyle('A1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');
        $summary->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $summary->fromArray([
            ['Nội dung','Giá trị'],
            ['Tổng chỉ tiêu',$totals['target_quantity']],
            ['Tổng thực hiện',$totals['actual_quantity']],
            ['Còn lại',$totals['remaining_quantity']],
            ['Vượt chỉ tiêu',$totals['excess_quantity']],
            ['Tổng doanh thu',$totals['revenue']],
            ['Tiền vượt dự kiến',$totals['payment_amount']],
            ['Số người đạt/vượt',$totals['completed_people']],
            ['Số người chưa đạt',$totals['not_completed_people']],
        ], null, 'A3');
        $summary->getStyle('A3:B3')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $summary->getStyle('A3:B3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F766E');
        $summary->getStyle('B4:B7')->getNumberFormat()->setFormatCode('#,##0.00');
        $summary->getStyle('B8:B9')->getNumberFormat()->setFormatCode('#,##0');
        $summary->getColumnDimension('A')->setWidth(30); $summary->getColumnDimension('B')->setWidth(20);

        $details = $spreadsheet->createSheet();
        $details->setTitle('CHI TIET KPI');
        $detailHeaders = ['STT','NHÂN SỰ','NHÓM','KHÓA HỌC','BẮT BUỘC','CHỈ TIÊU','THỰC HIỆN','CÒN LẠI','VƯỢT','TỶ LỆ %','DOANH THU','MỨC TRẢ/KPI','TIỀN VƯỢT','TRẠNG THÁI'];
        $details->fromArray($detailHeaders, null, 'A1');
        $rowNo = 2;
        foreach ($rows as $index => $row) {
            $details->fromArray([
                $index + 1, $row['personnel_name'], $row['personnel_type_label'], $row['course_name'],
                $row['is_mandatory'] ? 'Có' : 'Không', $row['target_quantity'], $row['actual_quantity'],
                $row['remaining_quantity'], $row['excess_quantity'], $row['completion_pct'], $row['revenue'],
                $row['payment_rate'], $row['payment_amount'], $this->statusLabel($row['status']),
            ], null, 'A'.$rowNo++);
        }
        $details->getStyle('A1:N1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $details->getStyle('A1:N1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');
        $details->getStyle('F2:M'.max(2,$rowNo-1))->getNumberFormat()->setFormatCode('#,##0.00');
        foreach (range('A','N') as $column) $details->getColumnDimension($column)->setAutoSize(true);
        $details->freezePane('A2'); $details->setAutoFilter('A1:N'.max(1,$rowNo-1));

        $recordsSheet = $spreadsheet->createSheet();
        $recordsSheet->setTitle('PHAT SINH GOC');
        $recordsSheet->fromArray(['STT','NGÀY','NHÂN SỰ','CỘNG TÁC VIÊN','KHÓA HỌC','HỌC VIÊN','LỚP','SỐ LƯỢNG','THỰC THU','PHIẾU THU','GHI CHÚ'], null, 'A1');
        $recordsQuery = $this->recordQuery($filters);
        if ($request->filled('status')) {
            $pairs = $rows->map(fn ($row) => [$row['personnel_id'], $row['course_id']]);
            if ($pairs->isEmpty()) {
                $recordsQuery->whereRaw('1 = 0');
            } else {
                $recordsQuery->where(function ($query) use ($pairs): void {
                    foreach ($pairs as [$personnelId, $courseId]) {
                        $query->orWhere(function ($pair) use ($personnelId, $courseId): void {
                            $pair->where('personnel_id', $personnelId);
                            if ($courseId) $pair->where('course_id', $courseId);
                        });
                    }
                });
            }
        }
        $records = $recordsQuery->with(['personnel','collaborator','course'])->orderBy('record_date')->get();
        $recordRow = 2;
        foreach ($records as $index => $record) {
            $recordsSheet->fromArray([
                $index+1, $record->record_date?->format('d/m/Y'), $record->personnel?->name,
                $record->collaborator?->name, $record->course?->name, $record->student_name,
                $record->class_name, (float) $record->raw_quantity, (float) $record->revenue,
                $record->receipt_no, $record->note,
            ], null, 'A'.$recordRow++);
        }
        $recordsSheet->getStyle('A1:K1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $recordsSheet->getStyle('A1:K1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F766E');
        $recordsSheet->getStyle('H2:I'.max(2,$recordRow-1))->getNumberFormat()->setFormatCode('#,##0.00');
        foreach (range('A','K') as $column) $recordsSheet->getColumnDimension($column)->setAutoSize(true);
        $recordsSheet->freezePane('A2'); $recordsSheet->setAutoFilter('A1:K'.max(1,$recordRow-1));

        $file = match ($filters['period_type']) {
            'month' => sprintf('bao-cao-chi-tieu-thang-%02d-%d.xlsx', $filters['period_value'], $filters['year']),
            'quarter' => sprintf('bao-cao-chi-tieu-quy-%02d-%d.xlsx', $filters['period_value'], $filters['year']),
            default => sprintf('bao-cao-chi-tieu-nam-%d.xlsx', $filters['year']),
        };
        ActivityLogger::log('reports', 'export', 'Xuất báo cáo '.$periodLabel);
        return response()->streamDownload(fn () => (new Xlsx($spreadsheet))->save('php://output'), $file, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function build(Request $request): array
    {
        $filters = [
            'year' => $request->integer('year', now()->year),
            'period_type' => $request->string('period_type', 'year')->toString(),
            'period_value' => $request->integer('period_value', 0),
            'personnel_id' => $request->integer('personnel_id', 0),
            'course_id' => $request->integer('course_id', 0),
            'personnel_type' => $request->string('personnel_type')->toString(),
        ];
        if (! in_array($filters['period_type'], ['month','quarter','year'], true)) $filters['period_type'] = 'year';
        if ($filters['period_type'] === 'year') {
            $filters['period_value'] = 0;
        } elseif ($filters['period_type'] === 'month' && ($filters['period_value'] < 1 || $filters['period_value'] > 12)) {
            $filters['period_value'] = now()->month;
        } elseif ($filters['period_type'] === 'quarter' && ($filters['period_value'] < 1 || $filters['period_value'] > 4)) {
            $filters['period_value'] = (int) ceil(now()->month / 3);
        }
        $user = $request->user();
        if (! $user->isLeader()) $filters['personnel_id'] = $user->personnel_id ?: -1;
        return [$filters, $this->calculator->report($filters)];
    }

    private function recordQuery(array $filters)
    {
        $query = KpiRecord::query();
        Period::applyRecordFilter($query, $filters['year'], $filters['period_type'], $filters['period_value']);
        if ($filters['personnel_id']) $query->where('personnel_id', $filters['personnel_id']);
        if ($filters['course_id']) $query->where('course_id', $filters['course_id']);
        if ($filters['personnel_type']) $query->whereHas('personnel', fn ($q) => $q->where('type', $filters['personnel_type']));
        return $query;
    }

    private function totals($rows): array
    {
        return [
            'target_quantity' => round($rows->sum('target_quantity'),2),
            'actual_quantity' => round($rows->sum('actual_quantity'),2),
            'remaining_quantity' => round($rows->sum('remaining_quantity'),2),
            'excess_quantity' => round($rows->sum('excess_quantity'),2),
            'revenue' => round($rows->sum('revenue'),2),
            'payment_amount' => round($rows->sum('payment_amount'),2),
            'completed_people' => $rows->whereIn('status',['completed','exceeded','payable'])->pluck('personnel_id')->unique()->count(),
            'not_completed_people' => $rows->where('status','not_completed')->pluck('personnel_id')->unique()->count(),
        ];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'not_completed' => 'Chưa đạt', 'completed' => 'Đã đạt', 'exceeded' => 'Vượt chỉ tiêu',
            'payable' => 'Được thanh toán', 'no_target' => 'Chưa giao chỉ tiêu', default => 'Chưa có dữ liệu',
        };
    }
}
