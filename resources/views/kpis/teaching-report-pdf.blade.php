<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Lịch dạy trung tâm tháng {{ $reportMonth }}/{{ $year }}</title>
    <style>
        @page { margin: 20mm 15mm 18mm 20mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #000; line-height: 1.35; }
        .header { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .header td { width: 50%; vertical-align: top; text-align: center; }
        .agency { font-weight: 700; line-height: 1.45; }
        .national { font-weight: 700; text-transform: uppercase; }
        .motto { font-weight: 700; margin-top: 3px; }
        .motto-rule { width: 115px; height: 1px; margin: 4px auto 0; background: #000; }
        .issued-at { margin-top: 22px; font-style: italic; text-align: right; }
        .document-title { margin: 16px 0 14px; text-align: center; font-size: 15px; font-weight: 700; line-height: 1.5; }
        .teacher-info { margin-bottom: 11px; line-height: 1.8; }
        .teacher-info .label { display: inline-block; min-width: 94px; }
        table.schedule { width: 100%; border-collapse: collapse; }
        table.schedule th, table.schedule td { border: 1px solid #000; padding: 6px 5px; vertical-align: middle; }
        table.schedule th { font-weight: 700; text-align: center; }
        .center { text-align: center; }
        .right { text-align: right; }
        .total-label { font-weight: 700; text-align: center; }
        .total-value { font-weight: 700; text-align: right; }
        .empty { height: 150px; }
        .signatures { width: 100%; border-collapse: collapse; margin-top: 28px; }
        .signatures td { width: 50%; text-align: center; vertical-align: top; font-weight: 700; }
        .signature-space { height: 68px; }
        .acting { display: block; margin-bottom: 2px; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td>
                <div class="agency">PHÂN HIỆU TRƯỜNG ĐẠI HỌC<br>BÌNH DƯƠNG TẠI CÀ MAU</div>
                <div class="agency" style="margin-top:4px">TRUNG TÂM NGOẠI NGỮ<br>VÀ TIN HỌC</div>
            </td>
            <td>
                <div class="national">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</div>
                <div class="motto">Độc lập – Tự do – Hạnh phúc</div>
                <div class="motto-rule"></div>
                <div class="issued-at">Cà Mau, ngày {{ now()->format('d') }} tháng {{ now()->format('m') }} năm {{ now()->format('Y') }}</div>
            </td>
        </tr>
    </table>

    <div class="document-title">LỊCH DẠY TRUNG TÂM<br>THÁNG {{ str_pad((string) $reportMonth, 2, '0', STR_PAD_LEFT) }} NĂM {{ $year }}</div>

    <div class="teacher-info">
        <div><span class="label">Tên giáo viên:</span><strong>{{ $personnel->name }}</strong></div>
        <div><span class="label">Bộ phận:</span>Trung tâm Ngoại ngữ và Tin học</div>
    </div>

    <table class="schedule">
        <thead>
            <tr>
                <th style="width:15%">Ngày</th>
                <th style="width:29%">Lớp/Mã lớp</th>
                <th style="width:20%">Khung giờ</th>
                <th style="width:14%">Số tiết</th>
                <th style="width:22%">Ghi chú</th>
            </tr>
        </thead>
        <tbody>
            @forelse($detailRows as $row)
                <tr>
                    <td class="center">{{ $row['date'] !== '' ? \Illuminate\Support\Carbon::parse($row['date'])->format('d/m/Y') : '' }}</td>
                    <td>{{ $row['class_name'] }}</td>
                    <td class="center">{{ $row['time_slot'] }}</td>
                    <td class="right">{{ $row['lesson_count'] !== '' ? number_format((float) $row['lesson_count'], 2, ',', '.') : '' }}</td>
                    <td>{{ $row['note'] }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty"></td></tr>
            @endforelse
            <tr>
                <td colspan="3" class="total-label">TỔNG CỘNG</td>
                <td class="total-value">{{ number_format($reportedTeachingLoad, 2, ',', '.') }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td>LẬP BẢNG<div class="signature-space"></div></td>
            <td><span class="acting">KT. GIÁM ĐỐC</span>P. GIÁM ĐỐC<div class="signature-space"></div></td>
        </tr>
    </table>
</body>
</html>
