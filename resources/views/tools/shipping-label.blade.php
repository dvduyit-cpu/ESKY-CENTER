<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Gửi đơn vận chuyển {{ $label['order_code'] }}</title>
    <style>
        @page { size: A5 portrait; margin: 8mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #111827; background: #eef2f7; }
        .toolbar { position: sticky; top: 0; z-index: 10; display: flex; justify-content: space-between; gap: 12px; padding: 14px 18px; background: #111827; }
        .toolbar a, .toolbar button { border: 0; border-radius: 10px; background: #fff; color: #111827; padding: 10px 16px; font: 600 14px Arial, Helvetica, sans-serif; cursor: pointer; text-decoration: none; }
        .page { max-width: 148mm; min-height: 210mm; margin: 18px auto; padding: 10mm; background: #fff; box-shadow: 0 14px 36px rgba(15, 23, 42, .15); }
        .header { display: flex; justify-content: space-between; gap: 12px; border-bottom: 2px solid #111827; padding-bottom: 12px; }
        .header h1 { margin: 0; font-size: 24px; letter-spacing: .04em; }
        .muted { color: #6b7280; font-size: 12px; }
        .order-code { font-size: 20px; font-weight: 700; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 16px; }
        .card { border: 1px solid #d1d5db; border-radius: 14px; padding: 14px; background: #fff; }
        .card h2 { margin: 0 0 10px; font-size: 14px; text-transform: uppercase; letter-spacing: .08em; color: #1f2937; }
        .row + .row { margin-top: 8px; }
        .label { display: block; margin-bottom: 3px; font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: .06em; }
        .value { font-size: 14px; font-weight: 600; line-height: 1.45; white-space: pre-line; }
        .summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 14px; }
        .summary .card { text-align: center; }
        .summary strong { display: block; font-size: 20px; margin-top: 6px; }
        .note { margin-top: 14px; border: 1px dashed #9ca3af; border-radius: 12px; padding: 12px 14px; background: #f9fafb; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none !important; }
            .page { margin: 0; min-height: auto; padding: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('tools.index') }}">Quay lại Tool</a>
        <button type="button" onclick="window.print()">In A5</button>
    </div>

    <main class="page">
        <section class="header">
            <div>
                <h1>PHIẾU GỬI ĐƠN</h1>
                <div class="muted">Mẫu in A5 cho gửi hàng / bàn giao đơn vận chuyển</div>
            </div>
            <div style="text-align:right">
                <div class="muted">Mã đơn</div>
                <div class="order-code">{{ $label['order_code'] }}</div>
                <div class="muted">{{ $label['carrier_name'] ?: 'Chưa ghi đơn vị vận chuyển' }}</div>
            </div>
        </section>

        <section class="grid">
            <div class="card">
                <h2>Người gửi</h2>
                <div class="row">
                    <span class="label">Họ tên</span>
                    <span class="value">{{ $label['sender_name'] }}</span>
                </div>
                <div class="row">
                    <span class="label">Số điện thoại</span>
                    <span class="value">{{ $label['sender_phone'] ?: '—' }}</span>
                </div>
                <div class="row">
                    <span class="label">Địa chỉ</span>
                    <span class="value">{{ $label['sender_address'] }}</span>
                </div>
            </div>

            <div class="card">
                <h2>Người nhận</h2>
                <div class="row">
                    <span class="label">Họ tên</span>
                    <span class="value">{{ $label['recipient_name'] }}</span>
                </div>
                <div class="row">
                    <span class="label">Số điện thoại</span>
                    <span class="value">{{ $label['recipient_phone'] }}</span>
                </div>
                <div class="row">
                    <span class="label">Địa chỉ</span>
                    <span class="value">{{ $label['recipient_address'] }}</span>
                </div>
            </div>
        </section>

        <section class="summary">
            <div class="card">
                <span class="label">Đơn vị vận chuyển</span>
                <strong>{{ $label['carrier_name'] ?: '—' }}</strong>
            </div>
            <div class="card">
                <span class="label">COD</span>
                <strong>{{ number_format((float) ($label['cod_amount'] ?? 0)) }}đ</strong>
            </div>
            <div class="card">
                <span class="label">Mã đơn</span>
                <strong>{{ $label['order_code'] }}</strong>
            </div>
        </section>

        <section class="note">
            <span class="label">Ghi chú kiện hàng</span>
            <div class="value">{{ $label['package_note'] ?: 'Không có ghi chú.' }}</div>
        </section>
    </main>

    @if($autoPrint)
        <script>
        window.addEventListener('load', function () {
            window.setTimeout(function () { window.print(); }, 350);
        });
        </script>
    @endif
</body>
</html>
