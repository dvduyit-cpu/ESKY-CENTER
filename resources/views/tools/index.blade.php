@extends('layouts.app')

@section('title', 'Tool tiện ích')
@section('header', 'Tool tiện ích')

@section('content')
<div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
    <div>
        <h1 class="page-title">Tool tiện ích</h1>
        <div class="page-subtitle">Chia theo từng nhóm để mở nhanh đúng trang chức năng cần dùng.</div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-6">
        <a class="text-decoration-none text-reset" href="{{ route('tools.shipping.index') }}">
            <div class="card card-soft h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <span class="badge-soft badge-info">Nhóm 1</span>
                            <h4 class="mt-3 mb-2">In ấn & vận chuyển</h4>
                            <p class="text-muted mb-0">Tạo và in phiếu gửi đơn A5, tách riêng để thao tác nhanh khi cần in vận chuyển.</p>
                        </div>
                        <span class="stat-icon bg-info-subtle text-info"><i class="bi bi-box-seam"></i></span>
                    </div>
                    <div class="small text-muted">Gồm: in gửi đơn vận chuyển A5.</div>
                    <div class="mt-3 fw-semibold text-primary">Mở trang nhóm <i class="bi bi-arrow-right-short"></i></div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-lg-6">
        <a class="text-decoration-none text-reset" href="{{ route('tools.tuition.index') }}">
            <div class="card card-soft h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                        <div>
                            <span class="badge-soft badge-success">Nhóm 2</span>
                            <h4 class="mt-3 mb-2">QR & học phí</h4>
                            <p class="text-muted mb-0">Gộp các tiện ích tạo mã QR, gồm QR từ link và QR học phí hàng loạt từ file Excel.</p>
                        </div>
                        <span class="stat-icon bg-success-subtle text-success"><i class="bi bi-qr-code"></i></span>
                    </div>
                    <div class="small text-muted">Gồm: QR từ link, QR học phí từ Excel.</div>
                    <div class="mt-3 fw-semibold text-primary">Mở trang nhóm <i class="bi bi-arrow-right-short"></i></div>
                </div>
            </div>
        </a>
    </div>
</div>
@endsection
