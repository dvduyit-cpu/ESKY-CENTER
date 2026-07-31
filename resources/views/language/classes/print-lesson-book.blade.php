<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sổ lớp {{$languageClass->code}}</title>
    <style>
        @page{size:A4 landscape;margin:15mm}
        *{box-sizing:border-box}
        html,body{margin:0;padding:0;background:#eef2f7;color:#000;font-family:"Times New Roman",Times,serif;font-size:13px}
        .print-toolbar{position:sticky;top:0;z-index:10;display:flex;justify-content:space-between;align-items:center;gap:12px;padding:10px 16px;background:#242424;color:#fff;box-shadow:0 3px 12px rgba(15,23,42,.2)}
        .print-toolbar a,.print-toolbar button{border:1px solid rgba(255,255,255,.7);border-radius:6px;background:#fff;color:#111;padding:8px 14px;font:600 13px Arial,sans-serif;text-decoration:none;cursor:pointer}
        .print-toolbar-actions{display:flex;gap:8px}
        .print-sheet{width:267mm;min-height:180mm;margin:10px auto;padding:10mm;background:#fff;outline:1px solid #d4d4d4}
        .official-header{display:grid;grid-template-columns:1fr 1fr;align-items:start;gap:14mm;margin-bottom:4mm;text-align:center;line-height:1.35}
        .official-header strong{display:block;text-transform:uppercase}
        .official-header .agency-name{font-weight:400}
        .official-header .agency-unit{font-weight:700}
        .official-header .nation-name{font-weight:700}
        .official-header .national-motto{display:inline-block;margin-top:.8mm;padding-bottom:.8mm;border-bottom:1px solid #000;font-weight:700}
        .document-title{margin:0 0 3mm;text-align:center}
        .document-title h1{margin:0;font-size:18px;text-transform:uppercase}
        .document-title h2{margin:1mm 0 0;font-size:14px;font-weight:700}
        .document-reference{margin-top:1mm;font-size:13px;font-weight:400}
        .class-info{display:grid;grid-template-columns:1.2fr 1.5fr 1fr 1fr;gap:2mm 5mm;margin-bottom:3mm;padding:2.5mm 3mm;border:1px solid #111827}
        .class-info span{font-weight:700}
        .section-title{margin:0 0 2mm;text-align:center;font-size:14px;text-transform:uppercase}
        table{width:100%;border-collapse:collapse;table-layout:fixed}
        th,td{border:1px solid #111827;padding:.8mm;vertical-align:middle}
        th{background:#f1f1f1;color:#000;font-weight:700;text-align:center}
        .attendance-table{font-size:13px}
        .attendance-table .col-index{width:8mm}
        .attendance-table .col-student{width:40mm;text-align:left}
        .attendance-table .col-phone{width:27mm}
        .attendance-table tbody td{height:5.2mm}
        .attendance-table .session-column{padding:.4mm .15mm;text-align:center}
        .session-head{display:flex;min-height:9mm;flex-direction:column;align-items:center;justify-content:center;gap:.3mm}
        .session-head strong{font-size:13px}
        .session-head small{font-size:11px;font-weight:400;line-height:1.15;white-space:nowrap}
        .attendance-mark{display:inline-grid;width:4.5mm;height:4.5mm;place-items:center;border:1px solid #334155;border-radius:.8mm;font-size:13px;font-weight:700;line-height:1}
        .attendance-mark.present,.attendance-mark.late,.attendance-mark.absent,.attendance-mark.excused{border-color:#000;color:#000}
        .student-name{font-size:13px;font-weight:700}
        .student-code{display:inline;margin-left:1mm;color:#475569;font-size:11px}
        .legend{display:flex;justify-content:space-between;gap:6mm;margin-top:2mm;font-size:13px}
        .signatures{display:grid;grid-template-columns:1fr 1fr 1fr;gap:12mm;margin-top:5mm;text-align:center}
        .signatures strong{display:block;margin-bottom:1mm;text-transform:uppercase}
        .signatures em{display:block;margin-bottom:8mm;font-size:12px;font-weight:400}
        .signature-date{display:block;margin-bottom:1.5mm;font-style:italic;font-weight:400}
        .lesson-book-page{break-before:page;page-break-before:always}
        .lesson-book-table{font-size:13px}
        .lesson-book-table thead{display:table-header-group}
        .lesson-book-table tr{break-inside:avoid;page-break-inside:avoid}
        .lesson-book-table th:nth-child(1){width:8mm}
        .lesson-book-table th:nth-child(2){width:22mm}
        .lesson-book-table th:nth-child(3){width:23mm}
        .lesson-book-table th:nth-child(4){width:76mm}
        .lesson-book-table th:nth-child(5){width:55mm}
        .lesson-book-table th:nth-child(6){width:36mm}
        .lesson-book-table th:nth-child(7){width:auto}
        .lesson-book-table th{padding:1.8mm 1.2mm}
        .lesson-book-table td{height:12mm;padding:2mm 1.5mm;white-space:pre-line;overflow-wrap:anywhere}
        .lesson-book-table .blank-row td{height:14mm}
        .print-note{margin-top:2mm;color:#475569;font-size:11px}
        @media print{
            html,body{background:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact}
            .print-toolbar{display:none!important}
            .print-sheet{width:auto;min-height:auto;margin:0;padding:10mm;outline:0}
        }
    </style>
</head>
<body>
<div class="print-toolbar">
    <strong>Sổ lớp {{$languageClass->code}} · A4 ngang</strong>
    <div class="print-toolbar-actions"><a href="{{route('teacher-classes.gradebook',$languageClass)}}">Quay lại</a><button type="button" onclick="window.print()">In sổ lớp</button></div>
</div>

<main class="print-sheet attendance-page">
    <header class="official-header">
        <div><strong class="agency-name">PHÂN HIỆU TRƯỜNG ĐẠI HỌC BÌNH DƯƠNG TẠI CÀ MAU</strong><strong class="agency-unit">TRUNG TÂM NGOẠI NGỮ VÀ TIN HỌC</strong></div>
        <div><strong class="nation-name">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</strong><span class="national-motto">Độc lập - Tự do - Hạnh phúc</span></div>
    </header>
    <div class="document-title"><h1>Sổ theo dõi lớp học</h1><h2>Danh sách và điểm danh học viên</h2><div class="document-reference">Năm học: ........................ · Khóa: {{$languageClass->start_date?->format('Y')?:'................'}}</div></div>
    <div class="class-info">
        <div><span>Mã lớp:</span> {{$languageClass->code}}</div>
        <div><span>Tên lớp:</span> {{$languageClass->name}}</div>
        <div><span>Số buổi:</span> {{$sessionCount}}</div>
        <div><span>Phòng:</span> {{$languageClass->room?:'................'}}</div>
        <div><span>Khóa học:</span> {{$languageClass->course?->name?:$languageClass->program?->name}}</div>
        <div><span>Giáo viên:</span> {{$languageClass->teacher?->name?:'Chưa phân công'}}</div>
        <div><span>Khai giảng:</span> {{$languageClass->start_date?->format('d/m/Y')?:'................'}}</div>
        <div><span>Lịch học:</span> {{$languageClass->schedule_note?:'................'}}</div>
    </div>
    <h3 class="section-title">Bảng điểm danh</h3>
    <table class="attendance-table">
        <thead><tr><th class="col-index">STT</th><th class="col-student">Họ và tên học viên</th><th class="col-phone">Số điện thoại</th>@foreach($sessionLessons as $slot)<th class="session-column" data-session-number="{{$slot['number']}}"><div class="session-head"><strong>B{{$slot['number']}}</strong><small>{{$slot['lesson']?->lesson_date?->format('d/m')?:'..../....'}}</small></div></th>@endforeach</tr></thead>
        <tbody>
        @forelse($languageClass->enrollments as $index=>$enrollment)
            @php
                $student=$enrollment->student;
                $primaryGuardian=$student?->guardians?->firstWhere('is_primary',true) ?: $student?->guardians?->first();
                $phone=$student?->phone ?: $primaryGuardian?->phone;
            @endphp
            <tr>
                <td class="text-center" style="text-align:center">{{$index+1}}</td>
                <td><span class="student-name">{{$student?->name}}</span><span class="student-code">{{$student?->code}}</span></td>
                <td style="text-align:center">{{$phone?:'—'}}</td>
                @foreach($sessionLessons as $slot)
                    @php($attendance=$slot['lesson']?->attendances?->firstWhere('language_enrollment_id',$enrollment->id))
                    @php($mark=match($attendance?->status){'present'=>'✓','late'=>'M','excused'=>'P','absent'=>'V',default=>''})
                    <td class="session-column"><span class="attendance-mark {{$attendance?->status}}">{{$mark}}</span></td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="{{$sessionCount+3}}" style="height:18mm;text-align:center">Lớp chưa có học viên.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="legend"><span><strong>Ký hiệu:</strong> ✓ Có mặt · M Đi muộn · P Vắng có phép · V Vắng</span><span>Tổng số học viên: <strong>{{$languageClass->enrollments->count()}}</strong></span></div>
    <div class="signatures"><div><strong>Giáo viên phụ trách</strong><em>(Ký và ghi rõ họ tên)</em></div><div><strong>Giáo vụ</strong><em>(Ký và ghi rõ họ tên)</em></div><div><span class="signature-date">Cà Mau, ngày ..... tháng ..... năm ........</span><strong>Xác nhận của trung tâm</strong><em>(Ký, ghi rõ họ tên và đóng dấu)</em></div></div>
</main>

<section class="print-sheet lesson-book-page">
    <header class="official-header">
        <div><strong class="agency-name">PHÂN HIỆU TRƯỜNG ĐẠI HỌC BÌNH DƯƠNG TẠI CÀ MAU</strong><strong class="agency-unit">TRUNG TÂM NGOẠI NGỮ VÀ TIN HỌC</strong></div>
        <div><strong class="nation-name">CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</strong><span class="national-motto">Độc lập - Tự do - Hạnh phúc</span></div>
    </header>
    <div class="document-title"><h1>Sổ đầu bài</h1><h2>Theo dõi nội dung giảng dạy từng buổi</h2><div class="document-reference">Lớp: <strong>{{$languageClass->code}} – {{$languageClass->name}}</strong> · Giáo viên: {{$languageClass->teacher?->name?:'................'}} · Số buổi: {{$sessionCount}}</div></div>
    <table class="lesson-book-table">
        <thead><tr><th>STT</th><th>Ngày</th><th>Giờ học</th><th>Nội dung giảng dạy</th><th>Đánh giá</th><th>Chữ ký giáo viên</th><th>Ghi chú</th></tr></thead>
        <tbody>
        @foreach($sessionLessons as $slot)
            @php($lesson=$slot['lesson'])
            <tr class="{{$lesson?'':'blank-row'}}">
                <td style="text-align:center">{{$slot['number']}}</td>
                <td style="text-align:center">{{$lesson?->lesson_date?->format('d/m/Y')}}</td>
                <td style="text-align:center">@if($lesson){{substr((string)$lesson->start_time,0,5)}}–{{substr((string)$lesson->end_time,0,5)}}@endif</td>
                <td>{{$lesson?->content}}</td>
                <td>{{$lesson?->evaluation}}</td>
                <td aria-label="Chữ ký giáo viên để ký tay"></td>
                <td>{{$lesson?->note}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="print-note">Sổ được in từ hệ thống ngày {{now()->format('d/m/Y H:i')}}. Các dòng trống được dùng để ghi bổ sung trực tiếp.</div>
</section>
</body>
</html>
