@extends('layouts.app')

@section('title', 'QR & học phí')
@section('header', 'QR & học phí')

@section('content')
<div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
    <div>
        <h1 class="page-title">QR & học phí</h1>
        <div class="page-subtitle">Trang riêng cho các công cụ tạo mã QR, gồm QR từ link và QR học phí từ Excel.</div>
    </div>
    <a class="btn btn-light" href="{{ route('tools.index') }}"><i class="bi bi-arrow-left me-2"></i>Về Tool tiện ích</a>
</div>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card card-soft h-100">
            <div class="card-header">
                <h5 class="mb-0">Tạo mã QR từ link</h5>
            </div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <label class="form-label">Link cần tạo QR</label>
                    <input class="form-control" data-link-qr-input placeholder="https://example.com/...">
                </div>
                <div class="mb-3">
                    <label class="form-label">Kích thước</label>
                    <select class="form-select" data-link-qr-size>
                        <option value="240x240">240 x 240</option>
                        <option value="320x320" selected>320 x 320</option>
                        <option value="480x480">480 x 480</option>
                    </select>
                </div>
                <div class="border rounded-3 bg-light p-3 text-center">
                    <img class="img-fluid rounded bg-white border p-2 d-none" alt="QR từ link" data-link-qr-image style="max-height: 280px;">
                    <div class="small text-muted" data-link-qr-placeholder>Nhập link để tạo mã QR.</div>
                </div>
                <div class="form-actions">
                    <button class="btn btn-outline-primary" type="button" data-link-qr-generate>
                        <i class="bi bi-qr-code me-2"></i>Tạo QR
                    </button>
                    <a class="btn btn-outline-success d-none" href="#" target="_blank" data-link-qr-open>
                        <i class="bi bi-box-arrow-up-right me-2"></i>Mở ảnh QR
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card card-soft h-100">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h5 class="mb-0">Tạo mã QR học phí từ Excel</h5>
                <a class="btn btn-sm btn-outline-success" href="{{ route('tools.tuition.template') }}">
                    <i class="bi bi-download me-1"></i>Tải file mẫu
                </a>
            </div>
            <div class="card-body p-4">
                <form method="POST" enctype="multipart/form-data" action="{{ route('tools.tuition.preview') }}">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">File Excel</label>
                            <input class="form-control" type="file" name="file" accept=".xlsx,.xls,.csv" required>
                            <div class="form-text">Cột bắt buộc: `HỌ TÊN`, `SỐ TIỀN`. Có thể thêm `MÃ LỚP`, `NỘI DUNG`, `GHI CHÚ`.</div>
                        </div>
                        @if($bank['enabled'])
                            <div class="col-12">
                                <div class="alert alert-light border mb-0">
                                    <strong>Tài khoản nhận học phí:</strong> {{ $bank['name'] }} - {{ $bank['account_number'] }} - {{ $bank['account_name'] }}
                                </div>
                            </div>
                        @else
                            <div class="col-12">
                                <div class="alert alert-warning mb-0">
                                    Chưa cấu hình tài khoản ngân hàng nhận học phí, cần cấu hình trước khi tạo QR hàng loạt.
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="form-actions">
                        <button class="btn btn-primary" @disabled(! $bank['enabled'])>
                            <i class="bi bi-file-earmark-spreadsheet me-2"></i>Tạo danh sách QR
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const linkInput = document.querySelector('[data-link-qr-input]');
    const sizeInput = document.querySelector('[data-link-qr-size]');
    const image = document.querySelector('[data-link-qr-image]');
    const placeholder = document.querySelector('[data-link-qr-placeholder]');
    const generateButton = document.querySelector('[data-link-qr-generate]');
    const openLink = document.querySelector('[data-link-qr-open]');

    if (!linkInput || !sizeInput || !image || !placeholder || !generateButton || !openLink) {
        return;
    }

    const renderQr = function () {
        const link = linkInput.value.trim();
        if (!link) {
            image.classList.add('d-none');
            image.removeAttribute('src');
            placeholder.classList.remove('d-none');
            openLink.classList.add('d-none');
            openLink.removeAttribute('href');
            return;
        }

        const qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?' + new URLSearchParams({
            size: sizeInput.value,
            data: link,
        }).toString();

        image.src = qrUrl;
        image.classList.remove('d-none');
        placeholder.classList.add('d-none');
        openLink.href = qrUrl;
        openLink.classList.remove('d-none');
    };

    generateButton.addEventListener('click', renderQr);
    sizeInput.addEventListener('change', renderQr);
    linkInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            renderQr();
        }
    });
});
</script>
@endpush
