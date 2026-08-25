@extends('layouts.app')
@section('title', 'Báo cáo KPI tuyển sinh')
@section('header', 'KPI tuyển sinh')

@section('content')
@php
    $summaryCards = [
        ['Lead mới', number_format($totals['lead_count']), 'primary', 'bi-person-plus-fill'],
        ['Đã tư vấn', number_format($totals['consulted_count']), 'info', 'bi-headset'],
        ['Đã đăng ký', number_format($totals['registered_count']), 'success', 'bi-check2-circle'],
        ['Chưa phân công', number_format($totals['unassigned_count']), 'warning', 'bi-person-x'],
        ['Tỷ lệ chuyển đổi', number_format($totals['conversion_rate'], 1).'%', 'danger', 'bi-graph-up-arrow'],
    ];
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="page-title">Báo cáo KPI tuyển sinh</h1>
        <div class="page-subtitle">Theo dõi số lead mới, tiến độ tư vấn và tỷ lệ chuyển đổi đăng ký theo từng nhân sự tư vấn.</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('reports.index', ['year' => $filters['year'], 'period_type' => $filters['period_type'], 'period_value' => $filters['period_value']]) }}">
            <i class="bi bi-arrow-left me-1"></i>Báo cáo chung
        </a>
        <a class="btn btn-outline-primary" href="{{ route('reports.teaching-load-kpi', ['year' => $filters['year']]) }}">
            <i class="bi bi-clock-history me-1"></i>KPI giờ dạy
        </a>
        @if(auth()->user()->allowed('teaching_load_management'))
            <a class="btn btn-outline-primary" href="{{ route('teaching-load-management.index', ['year' => $filters['year']]) }}">
                <i class="bi bi-kanban me-1"></i>Tổng hợp giờ dạy
            </a>
        @endif
    </div>
</div>

<div class="card card-soft mb-4">
    <div class="card-body">
        <form class="filter-panel row g-3">
            <div class="col-md-2">
                <label class="form-label small">Năm</label>
                <input class="form-control" type="number" name="year" value="{{ $filters['year'] }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Loại kỳ</label>
                <select class="form-select" name="period_type" data-period-type>
                    <option value="year" @selected($filters['period_type'] === 'year')>Theo năm</option>
                    <option value="quarter" @selected($filters['period_type'] === 'quarter')>Theo quý</option>
                    <option value="month" @selected($filters['period_type'] === 'month')>Theo tháng</option>
                </select>
            </div>
            <div class="col-md-2" data-period-value-wrap>
                <label class="form-label small" data-period-value-label>Tháng/Quý</label>
                <select class="form-select" name="period_value" data-period-value></select>
            </div>
            @if($canViewAll)
                <div class="col-md-4">
                    <label class="form-label small">Nhân sự tư vấn</label>
                    <select class="form-select" name="consultant_user_id">
                        <option value="0">Tất cả nhân sự tư vấn</option>
                        @foreach($consultants as $consultant)
                            <option value="{{ $consultant->id }}" @selected($filters['consultant_user_id'] === (int) $consultant->id)>{{ $consultant->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-dark w-100"><i class="bi bi-filter me-1"></i>Lọc</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    @foreach($summaryCards as [$label, $value, $tone, $icon])
        <div class="col-md">
            <div class="card card-soft h-100">
                <div class="card-body">
                    <div class="stat-label">{{ $label }}</div>
                    <div class="d-flex justify-content-between align-items-center gap-2">
                        <div class="stat-value text-{{ $tone }}">{{ $value }}</div>
                        <div class="stat-icon bg-{{ $tone }}-subtle text-{{ $tone }}"><i class="bi {{ $icon }}"></i></div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card card-soft h-100">
            <div class="card-header bg-white border-0 p-4">
                <h5 class="mb-1">Bảng KPI tuyển sinh theo nhân sự</h5>
                <small class="text-muted">Kỳ đang xem: {{ $periodLabel }}</small>
            </div>
            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nhân sự tư vấn</th>
                            <th>Lead mới</th>
                            <th>Đã tư vấn</th>
                            <th>Đã đăng ký</th>
                            <th>Đang theo dõi</th>
                            <th>Không quan tâm</th>
                            <th>Tỷ lệ chuyển đổi</th>
                            <th>Cập nhật lead cuối</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['consultant_name'] }}</td>
                                <td>{{ number_format($row['lead_count']) }}</td>
                                <td class="text-info fw-semibold">{{ number_format($row['consulted_count']) }}</td>
                                <td class="text-success fw-semibold">{{ number_format($row['registered_count']) }}</td>
                                <td>{{ number_format($row['waiting_count']) }}</td>
                                <td>{{ number_format($row['not_interested_count']) }}</td>
                                <td>
                                    <span class="badge-soft {{ $row['conversion_rate'] >= 50 ? 'badge-success' : ($row['conversion_rate'] >= 25 ? 'badge-warning' : 'badge-gray') }}">
                                        {{ number_format($row['conversion_rate'], 1) }}%
                                    </span>
                                </td>
                                <td>{{ $row['last_received_at']?->format('d/m/Y H:i') ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8"><div class="empty-state">Chưa có dữ liệu tuyển sinh trong kỳ này.</div></td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card card-soft h-100">
            <div class="card-header bg-white border-0 p-4">
                <h5 class="mb-1">Trạng thái lead</h5>
                <small class="text-muted">Phân bố trạng thái trong kỳ lọc hiện tại.</small>
            </div>
            <div class="card-body pt-0">
                @foreach([
                    ['Mới tiếp nhận', $statusCounts['new'] ?? 0, 'secondary'],
                    ['Đã liên hệ', $statusCounts['contacted'] ?? 0, 'info'],
                    ['Đang tư vấn', $statusCounts['consulting'] ?? 0, 'primary'],
                    ['Hẹn kiểm tra', $statusCounts['placement_test'] ?? 0, 'warning'],
                    ['Chờ phản hồi', $statusCounts['waiting'] ?? 0, 'warning'],
                    ['Đã đăng ký', $statusCounts['registered'] ?? 0, 'success'],
                    ['Không quan tâm', $statusCounts['not_interested'] ?? 0, 'danger'],
                    ['Chăm sóc lại', $statusCounts['follow_up'] ?? 0, 'info'],
                ] as [$label, $count, $tone])
                    <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                        <span>{{ $label }}</span>
                        <strong class="text-{{ $tone }}">{{ number_format($count) }}</strong>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const periodType = document.querySelector('[data-period-type]');
    const periodValue = document.querySelector('[data-period-value]');
    const periodValueWrap = document.querySelector('[data-period-value-wrap]');
    const periodValueLabel = document.querySelector('[data-period-value-label]');
    if (!periodType || !periodValue || !periodValueWrap || !periodValueLabel) {
        return;
    }

    const selectedValue = @json($filters['period_value']);

    const fillOptions = () => {
        const mode = periodType.value;
        if (mode === 'year') {
            periodValue.innerHTML = '<option value="0">Cả năm</option>';
            periodValue.value = '0';
            periodValueWrap.classList.add('d-none');
            return;
        }

        periodValueWrap.classList.remove('d-none');
        const max = mode === 'quarter' ? 4 : 12;
        const prefix = mode === 'quarter' ? 'Quý ' : 'Tháng ';
        periodValueLabel.textContent = mode === 'quarter' ? 'Quý' : 'Tháng';
        periodValue.innerHTML = Array.from({ length: max }, (_, index) => {
            const value = index + 1;
            const selected = Number(selectedValue) === value ? ' selected' : '';
            return `<option value="${value}"${selected}>${prefix}${value}</option>`;
        }).join('');
    };

    periodType.addEventListener('change', fillOptions);
    fillOptions();
});
</script>
@endpush
