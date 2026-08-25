<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Danh sách QR học phí</title>
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #111827; background: #eef2f7; }
        .toolbar { position: sticky; top: 0; z-index: 10; display: flex; justify-content: space-between; gap: 12px; padding: 14px 18px; background: #111827; }
        .toolbar-actions { display: flex; flex-wrap: wrap; gap: 12px; }
        .toolbar a, .toolbar button { border: 0; border-radius: 10px; background: #fff; color: #111827; padding: 10px 16px; font: 600 14px Arial, Helvetica, sans-serif; cursor: pointer; text-decoration: none; }
        .page { max-width: 1100px; margin: 18px auto; padding: 24px; }
        .intro { margin-bottom: 18px; }
        .intro h1 { margin: 0 0 6px; font-size: 28px; }
        .intro p { margin: 0; color: #6b7280; }
        .alert { margin-bottom: 18px; padding: 14px 16px; border-radius: 14px; background: #fff7ed; border: 1px solid #fdba74; color: #9a3412; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 16px; }
        .card { break-inside: avoid; border: 1px solid #dbe2ea; border-radius: 18px; padding: 16px; background: #fff; box-shadow: 0 10px 28px rgba(15, 23, 42, .08); }
        .card img { display: block; width: 100%; max-width: 220px; margin: 0 auto 12px; border: 1px solid #e5e7eb; border-radius: 12px; padding: 8px; background: #fff; }
        .card h2 { margin: 0 0 8px; font-size: 18px; line-height: 1.35; }
        .meta { margin: 6px 0; font-size: 13px; color: #4b5563; }
        .meta strong { color: #111827; }
        .content-box { margin-top: 12px; padding: 10px 12px; border-radius: 12px; background: #f8fafc; border: 1px dashed #cbd5e1; }
        .content-box span { display: block; font-size: 11px; text-transform: uppercase; color: #64748b; letter-spacing: .06em; margin-bottom: 4px; }
        .content-box strong { font-size: 14px; line-height: 1.45; }
        .card-actions { margin-top: 14px; }
        .card-actions a { display: inline-flex; align-items: center; gap: 8px; border-radius: 10px; background: #111827; color: #fff; padding: 10px 14px; font: 600 13px Arial, Helvetica, sans-serif; text-decoration: none; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .page { margin: 0; max-width: none; padding: 0; }
            .card { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ $backRoute ?? route('tools.tuition.index') }}">{{ $backLabel ?? 'Quay lại nhóm QR & học phí' }}</a>
        <div class="toolbar-actions">
            <a href="{{ route('tools.tuition.download-all') }}">Tải tất cả ảnh QR</a>
            <button type="button" onclick="window.print()">In danh sách QR</button>
        </div>
    </div>

    <main class="page">
        <section class="intro">
            <h1>Danh sách QR học phí</h1>
            <p>File nguồn: <strong>{{ $sourceName }}</strong> · Tài khoản nhận: {{ $bank['name'] }} - {{ $bank['account_number'] }} - {{ $bank['account_name'] }}</p>
        </section>

        @if($errors !== [])
            <div class="alert">
                <strong>Có {{ count($errors) }} dòng chưa tạo được QR:</strong>
                <ul style="margin:8px 0 0 18px; padding:0;">
                    @foreach($errors as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="grid">
            @foreach($items as $item)
                <article class="card">
                    <img src="{{ $item['qr_url'] }}" alt="QR học phí {{ $item['name'] }}">
                    <h2>{{ $item['name'] }}</h2>
                    <div class="meta"><strong>Mã lớp:</strong> {{ $item['class_code'] !== '' ? $item['class_code'] : 'Chưa có' }}</div>
                    <div class="meta"><strong>Số tiền:</strong> {{ number_format($item['amount']) }}đ</div>
                    @if($item['note'] !== '')
                        <div class="meta"><strong>Ghi chú:</strong> {{ $item['note'] }}</div>
                    @endif
                    <div class="content-box">
                        <span>Nội dung chuyển khoản</span>
                        <strong>{{ $item['content'] }}</strong>
                    </div>
                    <div class="card-actions">
                        <a href="{{ route('tools.tuition.download', ['index' => $loop->index]) }}">Tải ảnh QR</a>
                    </div>
                </article>
            @endforeach
        </section>
    </main>
</body>
</html>
