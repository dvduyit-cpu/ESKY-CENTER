@extends('layouts.app')
@section('title',$target->exists?'Sửa chỉ tiêu':'Giao chỉ tiêu')
@section('header','Kế hoạch chỉ tiêu')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="page-title">{{ $target->exists ? 'Cập nhật chỉ tiêu' : 'Giao chỉ tiêu mới' }}</h1>
        <div class="page-subtitle">Chỉ tiêu áp dụng cho tổng kết quả của tất cả khóa học. Phần tiết dạy chỉ dùng cho dòng giao theo năm của giáo viên.</div>
    </div>
    <a href="{{ route('kpis.show', $plan) }}" class="btn btn-light">Quay lại</a>
</div>

<div class="card card-soft">
    <div class="card-body p-4">
        <form method="POST" action="{{ $target->exists ? route('kpis.targets.update', [$plan, $target]) : route('kpis.targets.store', $plan) }}">
            @csrf
            @if($target->exists)
                @method('PUT')
            @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nhân sự</label>
                    <select class="form-select" name="personnel_id" required>
                        <option value="">Chọn nhân sự</option>
                        @foreach($personnels as $personnel)
                            <option value="{{ $personnel->id }}" @selected((string) old('personnel_id', $target->personnel_id) === (string) $personnel->id)>
                                {{ $personnel->name }} - {{ $personnel->typeLabel() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Phạm vi khóa học</label>
                    <input class="form-control" value="Tất cả khóa học" disabled>
                    <div class="form-text">Kết quả mọi khóa học được quy đổi và cộng vào chỉ tiêu tổng.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Loại kỳ</label>
                    <select class="form-select" name="period_type" data-period-type required>
                        <option value="month" @selected(old('period_type', $target->period_type ?: 'month') === 'month')>Theo tháng</option>
                        <option value="quarter" @selected(old('period_type', $target->period_type) === 'quarter')>Theo quý</option>
                        <option value="year" @selected(old('period_type', $target->period_type) === 'year')>Theo năm</option>
                    </select>
                </div>

                <div class="col-md-4" data-period-quarter>
                    <label class="form-label">Quý</label>
                    <select class="form-select" name="quarter">
                        <option value="">Chọn quý</option>
                        @for($quarter = 1; $quarter <= 4; $quarter++)
                            <option value="{{ $quarter }}" @selected((string) old('quarter', $target->quarter) === (string) $quarter)>Quý {{ $quarter }}</option>
                        @endfor
                    </select>
                </div>

                <div class="col-md-4" data-period-month>
                    <label class="form-label">Tháng</label>
                    <select class="form-select" name="month">
                        <option value="">Chọn tháng</option>
                        @for($month = 1; $month <= 12; $month++)
                            <option value="{{ $month }}" @selected((string) old('month', $target->month) === (string) $month)>Tháng {{ $month }}</option>
                        @endfor
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Chỉ tiêu KPI tổng</label>
                    <input class="form-control" type="number" step="0.01" min="0" name="target_quantity" value="{{ old('target_quantity', $target->target_quantity) }}" required>
                </div>

                <div class="col-md-4" data-period-year>
                    <label class="form-label">Số tiết dạy được giao</label>
                    <input class="form-control" type="number" step="0.01" min="0" name="assigned_teaching_load" value="{{ old('assigned_teaching_load', $target->assigned_teaching_load) }}">
                    <div class="form-text">Áp dụng cho giáo viên và dùng để trừ dần theo báo cáo tháng.</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Doanh thu mục tiêu</label>
                    <input class="form-control" type="number" min="0" name="target_revenue" value="{{ old('target_revenue', $target->target_revenue) }}">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Mức thanh toán/KPI vượt</label>
                    <input class="form-control" type="number" min="0" name="excess_payment_per_kpi" value="{{ old('excess_payment_per_kpi', $target->excess_payment_per_kpi) }}">
                </div>

                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_mandatory" value="1" id="mandatory" @checked(old('is_mandatory', $target->exists ? $target->is_mandatory : true))>
                        <label class="form-check-label fw-semibold" for="mandatory">Chỉ tiêu bắt buộc: kết quả được trừ vào chỉ tiêu trước, phần dư mới tính thanh toán</label>
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">Ghi chú</label>
                    <textarea class="form-control" name="note" rows="3">{{ old('note', $target->note) }}</textarea>
                </div>
            </div>

            <div class="mt-4">
                <button class="btn btn-primary"><i class="bi bi-save me-2"></i>Lưu chỉ tiêu</button>
            </div>
        </form>
    </div>
</div>
@endsection
