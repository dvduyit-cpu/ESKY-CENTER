@extends('layouts.app')

@section('title', 'In ấn & vận chuyển')
@section('header', 'In ấn & vận chuyển')

@section('content')
<div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
    <div>
        <h1 class="page-title">In ấn & vận chuyển</h1>
        <div class="page-subtitle">Trang riêng cho các công cụ in phiếu và xử lý vận chuyển.</div>
    </div>
    <a class="btn btn-light" href="{{ route('tools.index') }}"><i class="bi bi-arrow-left me-2"></i>Về Tool tiện ích</a>
</div>

<div class="card card-soft">
    <div class="card-header">
        <h5 class="mb-0">In gửi đơn vận chuyển A5</h5>
    </div>
    <div class="card-body p-4">
        <form method="POST" action="{{ route('tools.shipping.print') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Mã đơn</label>
                    <input class="form-control" name="order_code" value="{{ old('order_code') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Đơn vị vận chuyển</label>
                    <input class="form-control" name="carrier_name" value="{{ old('carrier_name') }}" placeholder="Ví dụ: GHTK, J&T, VNPost">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tiền COD</label>
                    <div class="input-group">
                        <input class="form-control" type="number" min="0" step="0.01" name="cod_amount" value="{{ old('cod_amount', 0) }}">
                        <span class="input-group-text">đ</span>
                    </div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Người gửi</label>
                    <input class="form-control" name="sender_name" value="{{ old('sender_name', $systemName ?? 'E-SKY CENTER') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">SĐT người gửi</label>
                    <input class="form-control" name="sender_phone" value="{{ old('sender_phone') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Địa chỉ người gửi</label>
                    <textarea class="form-control" name="sender_address" rows="2" required>{{ old('sender_address') }}</textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Người nhận</label>
                    <input class="form-control" name="recipient_name" value="{{ old('recipient_name') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">SĐT người nhận</label>
                    <input class="form-control" name="recipient_phone" value="{{ old('recipient_phone') }}" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Địa chỉ người nhận</label>
                    <textarea class="form-control" name="recipient_address" rows="3" required>{{ old('recipient_address') }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Ghi chú kiện hàng</label>
                    <textarea class="form-control" name="package_note" rows="2">{{ old('package_note') }}</textarea>
                </div>
            </div>

            <div class="form-actions">
                <button class="btn btn-outline-secondary" type="submit" name="preview" value="1">
                    <i class="bi bi-eye me-2"></i>Xem trước
                </button>
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-printer me-2"></i>In A5
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
