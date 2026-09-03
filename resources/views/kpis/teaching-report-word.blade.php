<!DOCTYPE html>
<html lang="vi" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word">
<head>
    <meta charset="UTF-8">
    <title>Lịch dạy trung tâm tháng {{ $reportMonth }}/{{ $year }}</title>
    <!--[if gte mso 9]>
    <xml>
        <w:WordDocument><w:View>Print</w:View><w:Zoom>100</w:Zoom></w:WordDocument>
    </xml>
    <![endif]-->
    <style>
        @page Section1 { size: 21cm 29.7cm; margin: 2cm 1.5cm 1.8cm 2cm; }
        div.Section1 { page: Section1; }
        body, table, td, th, div, p { font-family: "Times New Roman", serif; font-size: 13pt; color: #000; }
        p { margin: 0; }
        .header, .schedule, .signatures { width: 100%; border-collapse: collapse; }
        .header { margin-bottom: 12pt; }
        .header td { width: 50%; vertical-align: top; text-align: center; }
        .agency, .national, .motto, .document-title, .schedule th, .total-label, .total-value, .signatures td { font-weight: bold; }
        .agency { line-height: 1.3; }
        .national { text-transform: uppercase; line-height: 1.3; }
        .motto { margin-top: 3pt; }
        .motto-rule { border-top: 1pt solid #000; width: 118pt; margin: 4pt auto 0; }
        .issued-at { margin-top: 20pt; font-style: italic; text-align: right; }
        .document-title { margin: 18pt 0 14pt; text-align: center; font-size: 15pt; line-height: 1.35; }
        .teacher-info { margin-bottom: 10pt; line-height: 1.55; }
        .teacher-info .label { display: inline-block; width: 98pt; }
        .schedule th, .schedule td { border: 1pt solid #000; padding: 5pt 4pt; vertical-align: middle; }
        .schedule th { text-align: center; }
        .center { text-align: center; }
        .right { text-align: right; }
        .total-label { text-align: center; }
        .total-value { text-align: right; }
        .empty { height: 120pt; }
        .signatures { margin-top: 28pt; }
        .signatures td { width: 50%; text-align: center; vertical-align: top; }
        .signature-space { height: 70pt; }
        .acting { display: block; margin-bottom: 2pt; }
    </style>
</head>
<body>
<div class="Section1">
    <table class="header">
        <tr>
            <td>
                <div class="agency">PHÂN HIỆU TRƯỜNG ĐẠI HỌC<br>BÌNH DƯƠNG TẠI CÀ MAU</div>
                <div class="agency" style="margin-top:4pt">TRUNG TÂM NGOẠI NGỮ<br>VÀ TIN HỌC</div>
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
</div>
</body>
</html>
