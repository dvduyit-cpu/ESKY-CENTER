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
            <button type="button" data-qr-download-all>Tải tất cả ảnh QR</button>
            <button type="button" onclick="window.print()">In danh sách QR</button>
        </div>
    </div>

    <main class="page">
        <section class="intro">
            <h1>Danh sách QR học phí</h1>
            <p>Nguồn: <strong>{{ $sourceName }}</strong> · Tài khoản nhận: {{ $bank['name'] }} - {{ $bank['account_number'] }} - {{ $bank['account_name'] }}</p>
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

        <div class="alert" data-qr-batch-status role="status" aria-live="polite" hidden></div>

        <section class="grid">
            @foreach($items as $item)
                <article class="card" data-name="{{ $item['name'] }}" data-class-code="{{ $item['class_code'] }}" data-amount="{{ number_format($item['amount'], 0, ',', '.') }}đ">
                    <img src="{{ route('tools.tuition.image', ['index' => $loop->index]) }}" alt="QR học phí {{ $item['name'] }}" data-qr-image data-fallback-src="{{ $item['qr_url'] }}">
                    <div class="alert" data-qr-error hidden>Không tải được ảnh QR. Vui lòng tải lại trang hoặc thử lại sau.</div>
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
                        <a href="{{ route('tools.tuition.download', ['index' => $loop->index]) }}" data-qr-download data-filename="qr-hoc-phi-{{ str_pad((string) ($loop->index + 1), 2, '0', STR_PAD_LEFT) }}-{{ \Illuminate\Support\Str::slug($item['name']) ?: 'hoc-vien' }}.png">Tải ảnh QR</a>
                    </div>
                </article>
            @endforeach
        </section>
    </main>
<script>
    async function addQrDetails(blob, card) {
        const sourceUrl = URL.createObjectURL(blob);
        try {
            const picture = new Image();
            picture.src = sourceUrl;
            await picture.decode();
            const canvas = document.createElement('canvas');
            const width = Math.max(400, picture.naturalWidth);
            const height = Math.round(picture.naturalHeight * width / picture.naturalWidth);
            const padding = 18;
            const fontSize = Math.max(14, Math.min(20, Math.floor(width / 26)));
            const lineHeight = fontSize + 10;
            const context = canvas.getContext('2d');
            context.font = 'bold ' + fontSize + 'px Arial, sans-serif';
            const lines = [];
            [
                'Họ và tên: ' + card.dataset.name,
                'Mã lớp: ' + (card.dataset.classCode || 'Chưa có'),
                'Số tiền: ' + card.dataset.amount,
            ].forEach(function (text) {
                let line = '';
                for (const character of text) {
                    if (line && context.measureText(line + character).width > width - padding * 2) {
                        lines.push(line);
                        line = '';
                    }
                    line += character;
                }
                if (line) lines.push(line);
            });
            canvas.width = width;
            canvas.height = height + padding * 2 + lines.length * lineHeight;
            context.fillStyle = '#fff';
            context.fillRect(0, 0, canvas.width, canvas.height);
            context.drawImage(picture, 0, 0, width, height);
            context.font = 'bold ' + fontSize + 'px Arial, sans-serif';
            context.fillStyle = '#111827';
            lines.forEach(function (line, index) {
                context.fillText(line, padding, height + padding + fontSize + index * lineHeight);
            });
            const result = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
            if (!result) throw new Error('PNG conversion failed');
            return result;
        } finally {
            URL.revokeObjectURL(sourceUrl);
        }
    }

    async function fetchQrBlob(link) {
        const card = link.closest('.card');
        const image = card.querySelector('[data-qr-image]');
        // The browser may reach VietQR even when the application's server cannot.
        const urls = image.dataset.fallbackAttempted
            ? [image.dataset.fallbackSrc, link.href]
            : [link.href, image.dataset.fallbackSrc];
        let blob;
        for (const url of urls) {
            const controller = new AbortController();
            // Works over LAN HTTP too; allow the server's VietQR retry to finish.
            const timeout = setTimeout(function () { controller.abort(); }, 40000);
            try {
                const response = await fetch(url, {
                    signal: controller.signal,
                    headers: { Accept: 'image/png, image/svg+xml, application/json' },
                });
                if (!response.ok) throw new Error('Download failed');
                let candidate = await response.blob();
                const signature = new Uint8Array(await candidate.slice(0, 8).arrayBuffer());
                if (signature.join(',') !== '137,80,78,71,13,10,26,10') {
                    const source = await candidate.text();
                    const svg = new DOMParser().parseFromString(source, 'image/svg+xml');
                    if (svg.documentElement.localName !== 'svg' || svg.querySelector('parsererror')) {
                        throw new Error('Invalid image');
                    }
                    const svgUrl = URL.createObjectURL(new Blob([source], { type: 'image/svg+xml' }));
                    try {
                        const picture = new Image();
                        picture.src = svgUrl;
                        await picture.decode();
                        const canvas = document.createElement('canvas');
                        canvas.width = picture.naturalWidth;
                        canvas.height = picture.naturalHeight;
                        canvas.getContext('2d').drawImage(picture, 0, 0);
                        candidate = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
                        if (!candidate) throw new Error('PNG conversion failed');
                    } finally {
                        URL.revokeObjectURL(svgUrl);
                    }
                }
                const hasDetails = url === link.href && response.headers.get('X-Tuition-Qr-Details') === '1';
                blob = hasDetails ? new Blob([candidate], { type: 'image/png' }) : await addQrDetails(candidate, card);
                break;
            } catch (exception) {
                // Try the other source before reporting the failure.
            } finally {
                clearTimeout(timeout);
            }
        }
        if (!blob) throw new Error('No image available');
        return blob;
    }

    function saveQrFile(blob, filename) {
        const objectUrl = URL.createObjectURL(blob);
        const download = document.createElement('a');
        download.href = objectUrl;
        download.download = filename;
        document.body.appendChild(download);
        download.click();
        download.remove();
        setTimeout(function () { URL.revokeObjectURL(objectUrl); }, 60000);
    }

    // Store PNGs without compression: PNG data is already compressed. ZIP headers
    // are written here so bulk downloads work without PHP ZIP or a CDN library.
    async function createQrZip(files) {
        if (files.length > 65535) throw new Error('Too many ZIP entries');
        const parts = [];
        const directory = [];
        let offset = 0;
        for (const file of files) {
            const name = new TextEncoder().encode(file.name);
            const bytes = new Uint8Array(await file.blob.arrayBuffer());
            let crc = 0xffffffff;
            for (const byte of bytes) {
                crc ^= byte;
                for (let bit = 0; bit < 8; bit++) {
                    crc = (crc >>> 1) ^ ((crc & 1) ? 0xedb88320 : 0);
                }
            }
            crc = (crc ^ 0xffffffff) >>> 0;
            if (offset + 30 + name.length + bytes.length > 0xffffffff) throw new Error('ZIP too large');
            const local = new Uint8Array(30 + name.length);
            const header = new DataView(local.buffer);
            header.setUint32(0, 0x04034b50, true);
            header.setUint16(4, 20, true);
            header.setUint16(6, 0x0800, true);
            header.setUint16(12, 33, true); // 1980-01-01
            header.setUint32(14, crc, true);
            header.setUint32(18, bytes.length, true);
            header.setUint32(22, bytes.length, true);
            header.setUint16(26, name.length, true);
            local.set(name, 30);
            parts.push(local, bytes);
            const central = new Uint8Array(46 + name.length);
            const entry = new DataView(central.buffer);
            entry.setUint32(0, 0x02014b50, true);
            entry.setUint16(4, 20, true);
            entry.setUint16(6, 20, true);
            entry.setUint16(8, 0x0800, true);
            entry.setUint16(14, 33, true);
            entry.setUint32(16, crc, true);
            entry.setUint32(20, bytes.length, true);
            entry.setUint32(24, bytes.length, true);
            entry.setUint16(28, name.length, true);
            entry.setUint32(42, offset, true);
            central.set(name, 46);
            directory.push(central);
            offset += local.length + bytes.length;
        }
        const end = new Uint8Array(22);
        const footer = new DataView(end.buffer);
        footer.setUint32(0, 0x06054b50, true);
        footer.setUint16(8, files.length, true);
        footer.setUint16(10, files.length, true);
        footer.setUint32(12, directory.reduce((size, entry) => size + entry.length, 0), true);
        footer.setUint32(16, offset, true);
        return new Blob([...parts, ...directory, end], { type: 'application/zip' });
    }

    document.querySelectorAll('[data-qr-download-all]').forEach(function (button) {
        button.addEventListener('click', async function () {
            if (button.disabled) return;
            const status = document.querySelector('[data-qr-batch-status]');
            const links = Array.from(document.querySelectorAll('[data-qr-download]'));
            const label = button.textContent;
            button.disabled = true;
            status.hidden = false;
            try {
                const files = [];
                const failed = [];
                for (const [index, link] of links.entries()) {
                    button.textContent = 'Đang tải ' + (index + 1) + '/' + links.length + '…';
                    status.textContent = button.textContent;
                    try {
                        files.push({ name: link.dataset.filename, blob: await fetchQrBlob(link) });
                    } catch (exception) {
                        failed.push(link.closest('.card').dataset.name);
                    }
                }
                if (!files.length) throw new Error('No images');
                saveQrFile(await createQrZip(files), 'danh-sach-qr-hoc-phi.zip');
                status.textContent = 'Đã tải ' + files.length + '/' + links.length + ' ảnh QR trong file ZIP.';
                if (failed.length) status.textContent += ' Chưa tải được: ' + failed.join(', ') + '. Vui lòng thử tải riêng các QR này.';
            } catch (exception) {
                status.textContent = 'Không tải được file ZIP. Vui lòng kiểm tra kết nối và thử lại hoặc tải từng ảnh QR.';
            } finally {
                button.disabled = false;
                button.textContent = label;
            }
        });
    });

    document.querySelectorAll('[data-qr-download]').forEach(function (link) {
        link.addEventListener('click', async function (event) {
            event.preventDefault();
            if (link.dataset.busy) return;

            const card = link.closest('.card');
            const error = card.querySelector('[data-qr-error]');
            const label = link.textContent;
            link.dataset.busy = '1';
            link.textContent = 'Đang tải…';
            error.hidden = true;

            try {
                const blob = await fetchQrBlob(link);
                saveQrFile(blob, link.dataset.filename);
            } catch (exception) {
                error.textContent = 'Không tải được ảnh QR. Vui lòng kiểm tra kết nối và thử lại.';
                error.hidden = false;
            } finally {
                delete link.dataset.busy;
                link.textContent = label;
            }
        });
    });
    document.querySelectorAll('[data-qr-image]').forEach(function (image) {
        const handleError = function () {
            if (!image.dataset.fallbackAttempted) {
                image.dataset.fallbackAttempted = '1';
                image.src = image.dataset.fallbackSrc;
                return;
            }
            image.style.display = 'none';
            image.nextElementSibling.hidden = false;
        };
        image.addEventListener('error', handleError);
        if (image.complete && image.naturalWidth === 0) {
            handleError();
        }
    });
</script>
</body>
</html>
