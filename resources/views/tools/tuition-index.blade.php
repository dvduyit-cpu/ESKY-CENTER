@extends('layouts.app')

@section('title', 'QR & học phí')
@section('header', 'QR & học phí')

@section('content')
<div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
    <div>
        <h1 class="page-title">QR & học phí</h1>
        <div class="page-subtitle">Tạo QR từ link, QR học phí từng học viên hoặc hàng loạt từ Excel.</div>
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
                <h5 class="mb-0">Tạo mã QR học phí</h5>
                <a class="btn btn-sm btn-outline-success" href="{{ route('tools.tuition.template') }}">
                    <i class="bi bi-download me-1"></i>Tải file mẫu
                </a>
            </div>
            <div class="card-body p-4">
                <form method="POST" enctype="multipart/form-data" action="{{ route('tools.tuition.preview') }}" data-tuition-qr-form autocomplete="off">
                    @csrf
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="tuitionEntryMode">Cách nhập học viên</label>
                            <select class="form-select" name="entry_mode" id="tuitionEntryMode" data-tuition-entry-mode>
                                <option value="single" @selected(old('entry_mode', 'single') === 'single')>Nhập từng học viên</option>
                                <option value="excel" @selected(old('entry_mode') === 'excel')>Nhập danh sách từ Excel</option>
                            </select>
                        </div>
                        <div class="col-12" data-single-student>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label" for="qrStudentName">Họ tên học viên</label>
                                    <input class="form-control" id="qrStudentName" name="student_name" value="{{ old('student_name') }}" maxlength="150" required data-single-input>
                                    @error('student_name')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="qrClassCode">Mã lớp</label>
                                    <input class="form-control" id="qrClassCode" name="class_code" value="{{ old('class_code') }}" maxlength="50" required data-single-input>
                                    @error('class_code')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="qrAmount">Số tiền (VNĐ)</label>
                                    <input class="form-control" type="number" id="qrAmount" name="amount" value="{{ old('amount') }}" min="1" max="999999999" step="1" placeholder="Ví dụ: 1500000" required data-single-input>
                                    @error('amount')<div class="text-danger small">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                        <div class="col-12 d-none" data-excel-students>
                            <label class="form-label">File Excel</label>
                            <input class="form-control" type="file" name="file" accept=".xlsx,.xls,.csv" data-excel-input disabled>
                            @error('file')<div class="text-danger small">{{ $message }}</div>@enderror
                            <div class="form-text">Cột bắt buộc: `HỌ TÊN`, `MÃ LỚP`, `SỐ TIỀN`. Lời nhắn ngân hàng chỉ gồm họ tên và mã lớp; có thể thêm `GHI CHÚ`.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label d-block">Tài khoản nhận học phí</label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="recipient_account" value="configured" id="recipientConfigured" data-tuition-recipient-choice @checked(old('recipient_account', $bank['enabled'] ? 'configured' : 'custom') === 'configured')>
                                    <label class="form-check-label" for="recipientConfigured">Dùng tài khoản có sẵn</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="recipient_account" value="custom" id="recipientCustom" data-tuition-recipient-choice @checked(old('recipient_account', $bank['enabled'] ? 'configured' : 'custom') === 'custom')>
                                    <label class="form-check-label" for="recipientCustom">Tài khoản khác</label>
                                </div>
                            </div>
                            @error('recipient_account')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 d-none" data-custom-recipient>
                            <div class="border rounded-3 p-3 bg-light">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">Ngân hàng</label>
                                        <select class="form-select" name="custom_bank_option" data-custom-bank-select data-selected-bank="{{ old('custom_bank_option') }}">
                                            <option value="">Chọn ngân hàng</option>
                                            <option value="970436|Vietcombank">Vietcombank</option>
                                            <option value="970415|VietinBank">VietinBank</option>
                                            <option value="970418|BIDV">BIDV</option>
                                            <option value="970405|Agribank">Agribank</option>
                                            <option value="970407|Techcombank">Techcombank</option>
                                            <option value="970422|MB Bank">MB Bank</option>
                                            <option value="970416|ACB">ACB</option>
                                            <option value="970432|VPBank">VPBank</option>
                                            <option value="970423|TPBank">TPBank</option>
                                            <option value="970403|Sacombank">Sacombank</option>
                                            <option value="970437|HDBank">HDBank</option>
                                            <option value="970441|VIB">VIB</option>
                                            <option value="970448|OCB">OCB</option>
                                            <option value="970443|SHB">SHB</option>
                                            <option value="970440|SeABank">SeABank</option>
                                            <option value="970426|MSB">MSB</option>
                                            <option value="970431|Eximbank">Eximbank</option>
                                            <option value="970425|ABBank">ABBank</option>
                                            <option value="970449|LPBank">LPBank</option>
                                            <option value="970428|Nam A Bank">Nam A Bank</option>
                                            <option value="970412|PVcomBank">PVcomBank</option>
                                            <option value="970409|Bac A Bank">Bac A Bank</option>
                                            <option value="970419|NCB">NCB</option>
                                            <option value="970430|PGBank">PGBank</option>
                                            <option value="970429|SCB">SCB</option>
                                            <option value="other">Ngân hàng khác</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6" data-custom-bank-detail>
                                        <label class="form-label">Mã BIN ngân hàng</label>
                                        <input class="form-control @error('custom_bank_bin') is-invalid @enderror" name="custom_bank_bin" value="{{ old('custom_bank_bin') }}" inputmode="numeric" placeholder="Ví dụ: 970436" data-custom-recipient-input>
                                        <div class="form-text">Mã 6 chữ số của ngân hàng trên VietQR.</div>
                                        @error('custom_bank_bin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Số tài khoản</label>
                                        <input class="form-control @error('custom_account_number') is-invalid @enderror" name="custom_account_number" value="{{ old('custom_account_number') }}" inputmode="numeric" placeholder="Nhập số tài khoản nhận" data-custom-recipient-input>
                                        @error('custom_account_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tên chủ tài khoản</label>
                                        <input class="form-control @error('custom_account_name') is-invalid @enderror" name="custom_account_name" value="{{ old('custom_account_name') }}" placeholder="Nhập tên chủ tài khoản" data-custom-recipient-input>
                                        @error('custom_account_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if($bank['enabled'])
                            <div class="col-12" data-configured-recipient>
                                <div class="alert alert-light border mb-0">
                                    <strong>Tài khoản nhận học phí:</strong> {{ $bank['name'] }} - {{ $bank['account_number'] }} - {{ $bank['account_name'] }}
                                </div>
                            </div>
                        @else
                            <div class="col-12">
                                <div class="alert alert-warning mb-0">
                                    Chưa cấu hình tài khoản ngân hàng nhận học phí. Hãy nhập tài khoản nhận bên trên.
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="form-actions">
                        <button class="btn btn-primary">
                            <i class="bi bi-qr-code me-2"></i>Tạo QR học phí
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
    const tuitionForm = document.querySelector('[data-tuition-qr-form]');
    const entryMode = document.querySelector('[data-tuition-entry-mode]');
    const toggleEntryMode = function () {
        const single = entryMode.value === 'single';
        document.querySelector('[data-single-student]').classList.toggle('d-none', !single);
        document.querySelector('[data-excel-students]').classList.toggle('d-none', single);
        document.querySelectorAll('[data-single-input]').forEach(function (input) {
            input.disabled = !single;
            input.required = single;
        });
        const fileInput = document.querySelector('[data-excel-input]');
        fileInput.disabled = single;
        fileInput.required = !single;
    };
    entryMode.addEventListener('change', toggleEntryMode);
    toggleEntryMode();
    const recipientChoices = document.querySelectorAll('[data-tuition-recipient-choice]');
    const configuredRecipient = document.querySelector('[data-configured-recipient]');
    const customRecipient = document.querySelector('[data-custom-recipient]');
    const customRecipientInputs = document.querySelectorAll('[data-custom-recipient-input]');
    const customBankSelect = document.querySelector('[data-custom-bank-select]');
    const customBankDetails = document.querySelectorAll('[data-custom-bank-detail]');
    const customBankBin = document.querySelector('[name="custom_bank_bin"]');

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

    const toggleRecipient = function () {
        const custom = document.querySelector('[data-tuition-recipient-choice]:checked')?.value === 'custom';
        configuredRecipient?.classList.toggle('d-none', custom);
        customRecipient?.classList.toggle('d-none', !custom);
        customRecipientInputs.forEach(function (input) {
            input.required = custom;
            input.disabled = !custom;
        });
    };

    recipientChoices.forEach(function (choice) {
        choice.addEventListener('change', toggleRecipient);
    });

    const fillSelectedBank = function () {
        const [bin, name] = (customBankSelect?.value || '').split('|');
        const selectedPreset = Boolean(bin && name);

        customBankDetails.forEach(function (detail) {
            detail.classList.toggle('d-none', selectedPreset);
        });

        if (selectedPreset) {
            customBankBin.value = bin;
        }
    };

    if (customBankSelect?.dataset.selectedBank) {
        customBankSelect.value = customBankSelect.dataset.selectedBank;
    }
    customBankSelect?.addEventListener('change', fillSelectedBank);
    toggleRecipient();
    fillSelectedBank();

});
</script>
@endpush
