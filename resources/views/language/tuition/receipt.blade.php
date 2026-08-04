<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Phiếu thu{{ $payment->receipt_code ? ' '.$payment->receipt_code : '' }}</title>
    <style>
        @if(!empty($receiptFonts['regular']))
        @font-face{font-family:"ESKY Vietnamese";src:url("{!! $receiptFonts['regular'] !!}") format("truetype");font-style:normal;font-weight:400}
        @endif
        @if(!empty($receiptFonts['bold']))
        @font-face{font-family:"ESKY Vietnamese";src:url("{!! $receiptFonts['bold'] !!}") format("truetype");font-style:normal;font-weight:700}
        @endif
        @page{size:A5 landscape;margin:6mm}
        *{box-sizing:border-box}
        html,body{margin:0;padding:0;color:#111;font-family:"ESKY Vietnamese","DejaVu Sans",sans-serif;font-size:8pt;line-height:1.3;font-weight:400}
        body.screen{background:#eee;padding:16px}
        .receipt{position:relative;width:100%;max-width:198mm;min-height:132mm;margin:0 auto;background:#fff;overflow:hidden}
        body.screen .receipt{padding:6mm;box-shadow:0 12px 36px rgba(15,39,76,.18);border-radius:6px}
        .top-accent{height:2px;background:#111;margin-bottom:4px}
        .header{display:table;width:100%;table-layout:fixed;border-bottom:1px solid #555;padding:0 2px 5px}
        .brand-logo,.brand-info,.receipt-meta{display:table-cell;vertical-align:middle}
        .brand-logo{width:18mm}
        .brand-logo img{display:block;max-width:15mm;max-height:15mm;object-fit:contain;filter:grayscale(100%)}
        .logo-default{display:table-cell;width:14mm;height:14mm;text-align:center;vertical-align:middle;border:1.5px solid #111;border-radius:50%;color:#111;font-size:13pt;font-weight:700}
        .brand-info{padding-left:2px}
        .brand-info h2{margin:0;color:#111;font-size:10pt;line-height:1.15;text-transform:uppercase;letter-spacing:.01em}
        .brand-info p{margin:1px 0 0;color:#333;font-size:6.3pt}
        .brand-info .contact{font-weight:700;color:#111}
        .receipt-meta{width:49mm;text-align:right}
        .meta-label{display:block;color:#555;font-size:6pt;text-transform:uppercase;letter-spacing:.07em}
        .receipt-number{display:block;margin:2px 0;color:#111;font-size:10pt;font-weight:700}
        .document-heading{text-align:center;padding:5px 0 4px}
        .document-heading h1{margin:0;color:#111;font-size:16pt;line-height:1;letter-spacing:.09em}
        .document-heading p{margin:3px 0 0;color:#444;font-size:6.5pt}
        .info-card{border:1px solid #888;border-radius:4px;background:#fff;padding:4px 6px}
        .info-table{width:100%;border-collapse:collapse;table-layout:fixed}
        .info-table td{padding:1.7px 3px;vertical-align:top}
        .info-table .label{width:22mm;color:#444;font-size:6.5pt}
        .info-table .value{font-weight:700;color:#111;border-bottom:1px dotted #777}
        .info-table .gap{width:6mm;border:0}
        .amount-panel{display:table;width:100%;margin:5px 0;border:1.5px solid #111;border-radius:4px;background:#fff;overflow:hidden}
        .amount-words,.amount-number{display:table-cell;vertical-align:middle;padding:5px 7px}
        .amount-words{width:64%;color:#111}
        .amount-words span{display:block;margin-bottom:1px;color:#333;font-size:6pt;font-weight:700;text-transform:uppercase;letter-spacing:.06em}
        .amount-words strong{font-size:7.5pt;font-style:italic}
        .amount-number{width:36%;text-align:right;border-left:1px solid #777;background:#fff;color:#111}
        .amount-number span{display:block;font-size:6pt;text-transform:uppercase;letter-spacing:.06em}
        .amount-number strong{display:block;font-size:13pt;line-height:1.15;white-space:nowrap}
        .detail{width:100%;border-collapse:collapse;table-layout:fixed}
        .detail th{padding:3.5px 4px;background:#eee;color:#111;border:1px solid #555;font-size:6.5pt;text-align:left}
        .detail td{padding:3.5px 4px;border:1px solid #777;vertical-align:top}
        .detail .money{text-align:right;white-space:nowrap}
        .detail .description{width:35%}
        .detail .original{width:13%}
        .detail .discount{width:18%}
        .detail .payable{width:15%}
        .detail .current{width:19%}
        .detail .total-row td{background:#f1f1f1;font-weight:700;color:#111}
        .detail .subtext{display:block;margin-top:1px;color:#555;font-size:5.8pt;font-weight:400}
        .note{margin-top:4px;padding:3px 5px;border-left:3px solid #555;background:#f5f5f5;color:#222;font-size:6.5pt}
        .signatures{display:table;width:100%;table-layout:fixed;margin-top:6px;text-align:center;page-break-inside:avoid}
        .signature{display:table-cell;width:33.333%;padding:0 8px;vertical-align:top}
        .signature strong{display:block;color:#111;font-size:7.2pt}
        .signature small{display:block;color:#444;font-size:5.8pt;font-style:italic}
        .signature-space{height:40px}
        .signature-name{font-weight:700}
        .footer{margin-top:5px;padding-top:3px;border-top:1px solid #aaa;color:#555;font-size:5.8pt;text-align:center}
        .print-actions{position:fixed;right:16px;top:16px;z-index:10;display:flex;gap:8px}
        .print-actions a,.print-actions button{padding:9px 14px;border:0;border-radius:7px;color:#fff;background:#1557a6;text-decoration:none;font:700 12px Arial;cursor:pointer;box-shadow:0 4px 12px rgba(21,87,166,.2)}
        .print-actions a{background:#168052}
        @media print{body.screen{background:#fff;padding:0}.print-actions{display:none}body.screen .receipt{padding:0;box-shadow:none;border-radius:0}body{print-color-adjust:exact;-webkit-print-color-adjust:exact}}
    </style>
</head>
@php
    $methods=['cash'=>'Tiền mặt','transfer'=>'Chuyển khoản','card'=>'Thẻ','other'=>'Khác'];
    $classLabel=$charge->languageClass?->code?:'Chưa xếp lớp';
    $contactPhone=$student->phone?:$primaryGuardian?->phone?:'—';
@endphp
<body class="{{ $pdfMode ? 'pdf' : 'screen' }}">
@if(!$pdfMode)
    <div class="print-actions"><a data-no-loading download href="{{route('language-tuition.receipt.pdf',$payment)}}">Tải PDF</a><button type="button" onclick="window.print()">In A5 ngang</button></div>
@endif
<main class="receipt">
    <div class="top-accent"></div>
    <header class="header">
        <div class="brand-logo">@if($logoData)<img src="{{$logoData}}" alt="Logo">@else<span class="logo-default">E</span>@endif</div>
        <div class="brand-info"><h2>Trung tâm Ngoại ngữ và Tin học E-SKY</h2><p class="contact">02903 683 888 · 0916 727 808</p><p>Số 3, Đường Lê Thị Riêng, Phường Tân Thành, Tỉnh Cà Mau</p></div>
        <div class="receipt-meta"><span class="meta-label">Số phiếu</span><strong class="receipt-number">{{$payment->receipt_code?:'........................'}}</strong></div>
    </header>

    <section class="document-heading"><h1>PHIẾU THU</h1><p>Lập lúc {{$payment->paid_at?->format('H:i')}} · Ngày {{$payment->paid_at?->format('d/m/Y')}} · Lần thu thứ {{number_format($paymentSequence)}}</p></section>

    <section class="info-card">
        <table class="info-table">
            <tr><td class="label">Người nộp tiền</td><td class="value">{{$student->name}}</td><td class="gap"></td><td class="label">Mã học viên</td><td class="value">{{$student->code}}</td></tr>
            <tr><td class="label">Điện thoại</td><td class="value">{{$contactPhone}}</td><td class="gap"></td><td class="label">Lớp / khóa học</td><td class="value">{{$classLabel}} · {{$charge->course?->name}}</td></tr>
            <tr><td class="label">Địa chỉ</td><td class="value">{{$student->address?:'—'}}</td><td class="gap"></td><td class="label">Thanh toán</td><td class="value">{{$methods[$payment->payment_method]??$payment->payment_method}}{{filled($payment->reference)?' · '.$payment->reference:''}}</td></tr>
        </table>
    </section>

    <table class="detail">
        <thead><tr><th class="description">Nội dung thu</th><th class="original money">Học phí lớp</th><th class="discount money">Miễn giảm áp dụng</th><th class="payable money">Phải thu</th><th class="current money">Thu lần này</th></tr></thead>
        <tbody>
            <tr><td><strong>Học phí {{$charge->course?->name}}</strong><span class="subtext">Mã khoản thu {{$charge->code}} · Lớp {{$classLabel}}</span></td><td class="money">{{number_format($charge->original_amount,0,',','.')}} đ</td><td class="money">{{$charge->discount_percentage}}%<span class="subtext">{{$charge->discount?->name?:'Không miễn giảm'}} · -{{number_format($charge->discount_amount,0,',','.')}} đ</span></td><td class="money">{{number_format($charge->payable_amount,0,',','.')}} đ</td><td class="money"><strong>{{number_format($payment->amount,0,',','.')}} đ</strong></td></tr>
            @if((float)$payment->book_amount>0)<tr><td><strong>Giáo trình / sách</strong><span class="subtext">Thu kèm trong phiếu này</span></td><td class="money">—</td><td class="money">—</td><td class="money">{{number_format($payment->book_amount,0,',','.')}} đ</td><td class="money"><strong>{{number_format($payment->book_amount,0,',','.')}} đ</strong></td></tr>@endif
            <tr class="total-row"><td colspan="4">TỔNG CỘNG THANH TOÁN</td><td class="money">{{number_format($totalAmount,0,',','.')}} đ</td></tr>
        </tbody>
    </table>

    <section class="amount-panel"><div class="amount-words"><span>Số tiền bằng chữ</span><strong>{{$amountInWords}}.</strong></div><div class="amount-number"><span>Tổng số tiền</span><strong>{{number_format($totalAmount,0,',','.')}} đ</strong></div></section>

    <div class="note"><strong>Ghi chú:</strong> {{$payment->note?:$charge->note?:'Không có ghi chú.'}}</div>

    <section class="signatures"><div class="signature"><strong>Người nộp tiền</strong><small>(Ký và ghi rõ họ tên)</small><div class="signature-space"></div><span class="signature-name">........................</span></div><div class="signature"><strong>Người lập phiếu</strong><small>(Ký và ghi rõ họ tên)</small><div class="signature-space"></div><span class="signature-name">{{$payment->collector?->name?:'........................'}}</span></div><div class="signature"><strong>Thủ quỹ</strong><small>(Ký và ghi rõ họ tên)</small><div class="signature-space"></div><span class="signature-name">........................</span></div></section>

    <footer class="footer">Phiếu thu được lập từ hệ thống {{$softwareName}} · Mã đối soát: {{$charge->code}}-{{$payment->id}}</footer>
</main>
@if(!$pdfMode&&$autoPrint)<script>window.addEventListener('load',()=>window.setTimeout(()=>window.print(),450));</script>@endif
</body>
</html>
