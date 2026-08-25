<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Báo cáo tiết dạy tháng {{ $reportMonth }}/{{ $year }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        .header { margin-bottom: 18px; }
        .title { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
        .subtitle, .meta { color: #6b7280; }
        .meta { font-size: 11px; margin-top: 8px; }
        .summary { width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 18px; }
        .summary td { width: 25%; border: 1px solid #dbe4f0; padding: 10px 12px; vertical-align: top; }
        .summary-label { font-size: 11px; color: #6b7280; margin-bottom: 6px; }
        .summary-value { font-size: 16px; font-weight: 700; }
        table.report { width: 100%; border-collapse: collapse; }
        table.report th, table.report td { border: 1px solid #dbe4f0; padding: 8px 10px; vertical-align: top; }
        table.report th { background: #eef3f9; font-size: 11px; text-transform: uppercase; }
        .center { text-align: center; }
        .right { text-align: right; }
        .muted { color: #6b7280; font-size: 11px; }
        .empty { border: 1px solid #dbe4f0; padding: 24px; text-align: center; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Báo cáo tiết dạy tháng {{ $reportMonth }}/{{ $year }}</div>
        <div class="subtitle">Chi tiết từng buổi dạy theo tháng.</div>
        <div class="meta">
            Giáo viên: {{ $personnel->name }}
            @if($plan)
                · Kế hoạch năm {{ $plan->year }}
            @endif
            · In ngày {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <table class="summary">
        <tr>
            <td>
                <div class="summary-label">Tiết dạy được giao</div>
                <div class="summary-value">{{ number_format((float) ($target?->assigned_teaching_load ?? 0), 2) }}</div>
            </td>
            <td>
                <div class="summary-label">Đã báo cáo tháng này</div>
                <div class="summary-value">{{ number_format($reportedTeachingLoad, 2) }}</div>
            </td>
            <td>
                <div class="summary-label">Cập nhật cuối</div>
                <div class="summary-value" style="font-size:13px">
                    {{ $report?->updated_at?->format('d/m/Y H:i') ?: 'Chưa báo cáo' }}
                </div>
            </td>
            <td>
                <div class="summary-label">Ghi chú tổng</div>
                <div class="summary-value" style="font-size:13px">
                    {{ $report?->note ?: 'Không có' }}
                </div>
            </td>
        </tr>
    </table>

    @if($detailRows->isEmpty())
        <div class="empty">Tháng {{ $reportMonth }}/{{ $year }} chưa có dòng báo cáo tiết dạy nào.</div>
    @else
        <table class="report">
            <thead>
                <tr>
                    <th style="width:56px">STT</th>
                    <th style="width:110px">Ngày</th>
                    <th>Lớp / Mã lớp</th>
                    <th style="width:130px">Khung giờ</th>
                    <th style="width:90px">Số tiết</th>
                    <th>Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                @foreach($detailRows as $row)
                    <tr>
                        <td class="center">{{ $loop->iteration }}</td>
                        <td>{{ $row['date'] !== '' ? \Illuminate\Support\Carbon::parse($row['date'])->format('d/m/Y') : '—' }}</td>
                        <td>{{ $row['class_name'] !== '' ? $row['class_name'] : '—' }}</td>
                        <td>{{ $row['time_slot'] !== '' ? $row['time_slot'] : '—' }}</td>
                        <td class="right">{{ $row['lesson_count'] !== '' ? number_format((float) $row['lesson_count'], 2) : '0.00' }}</td>
                        <td>{{ $row['note'] !== '' ? $row['note'] : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
