<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thu học phí tháng {{ $month->format('m/Y') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; }
        .header { margin-bottom: 18px; }
        .title { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
        .subtitle { color: #6b7280; }
        .meta { margin-top: 8px; font-size: 11px; color: #4b5563; }
        .summary { width: 100%; margin: 18px 0; border-collapse: separate; border-spacing: 0; }
        .summary td { width: 25%; border: 1px solid #dbe4f0; padding: 10px 12px; vertical-align: top; }
        .summary-label { font-size: 11px; color: #6b7280; margin-bottom: 6px; }
        .summary-value { font-size: 16px; font-weight: 700; }
        table.report { width: 100%; border-collapse: collapse; }
        table.report th, table.report td { border: 1px solid #dbe4f0; padding: 8px 10px; vertical-align: top; }
        table.report th { background: #eef3f9; font-size: 11px; text-transform: uppercase; }
        .muted { color: #6b7280; font-size: 11px; }
        .right { text-align: right; }
        .nowrap { white-space: nowrap; }
        .empty { padding: 24px; text-align: center; color: #6b7280; border: 1px solid #dbe4f0; }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">Thu học phí tháng {{ $month->format('m/Y') }}</div>
        <div class="subtitle">Tổng hợp theo ngày thực thu.</div>
        <div class="meta">
            In ngày {{ now()->format('d/m/Y H:i') }}
            @if($filters['q'] !== '')
                · Từ khóa: {{ $filters['q'] }}
            @endif
            @if($filters['receipt_status'] !== '')
                · Trạng thái phiếu: {{ $filters['receipt_status'] === 'confirmed' ? 'Đã xác nhận' : 'Chờ bổ sung' }}
            @endif
        </div>
    </div>

    <table class="summary">
        <tr>
            <td>
                <div class="summary-label">Học phí đã thu</div>
                <div class="summary-value">{{ number_format($tuitionCollected) }}đ</div>
            </td>
            <td>
                <div class="summary-label">Tiền giáo trình</div>
                <div class="summary-value">{{ number_format($bookCollected) }}đ</div>
            </td>
            <td>
                <div class="summary-label">Tổng tiền nhận</div>
                <div class="summary-value">{{ number_format($tuitionCollected + $bookCollected) }}đ</div>
            </td>
            <td>
                <div class="summary-label">Chờ bổ sung phiếu thu</div>
                <div class="summary-value">{{ number_format($pendingCount) }}</div>
            </td>
        </tr>
    </table>

    @if($items->isEmpty())
        <div class="empty">Chưa có khoản học phí nào được thu trong tháng {{ $month->format('m/Y') }}.</div>
    @else
        <table class="report">
            <thead>
                <tr>
                    <th class="nowrap">Ngày thu / phiếu</th>
                    <th>Học viên</th>
                    <th>Khóa học / lớp</th>
                    <th class="nowrap">Học phí</th>
                    <th class="nowrap">Giáo trình</th>
                    <th>Người thu</th>
                    <th class="nowrap">Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $payment)
                    @php($charge = $payment->charge)
                    @php($class = $charge?->languageClass)
                    <tr>
                        <td class="nowrap">
                            <strong>{{ $payment->paid_at?->format('d/m/Y H:i') }}</strong><br>
                            <span class="muted">{{ $payment->receipt_code ?: 'Chưa có số phiếu' }}</span>
                        </td>
                        <td>
                            <strong>{{ $charge?->student?->name }}</strong><br>
                            <span class="muted">{{ $charge?->student?->code }} · {{ $charge?->student?->phone ?: $charge?->student?->guardians?->first()?->phone }}</span>
                        </td>
                        <td>
                            {{ $charge?->course?->name }}<br>
                            <span class="muted">{{ $class?->code ?: 'Chưa gắn lớp' }} · {{ $class?->name }}</span>
                        </td>
                        <td class="right nowrap">{{ number_format((float) $payment->amount) }}đ</td>
                        <td class="right nowrap">{{ number_format((float) $payment->book_amount) }}đ</td>
                        <td>{{ $payment->collector?->name ?: '—' }}</td>
                        <td class="nowrap">{{ $payment->receipt_status === 'confirmed' ? 'Đã xác nhận' : 'Chờ bổ sung' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</body>
</html>
