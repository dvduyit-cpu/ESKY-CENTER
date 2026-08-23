@extends('layouts.app')
@section('title','Báo cáo chỉ tiêu')
@section('header','Báo cáo chỉ tiêu')
@section('content')
@php
    $statusCounts = [
        'not_completed' => $rows->where('status', 'not_completed')->count(),
        'completed' => $rows->where('status', 'completed')->count(),
        'exceeded' => $rows->where('status', 'exceeded')->count(),
        'payable' => $rows->where('status', 'payable')->count(),
        'no_target' => $rows->where('status', 'no_target')->count(),
    ];
    $summaryCards = [
        ['Chỉ tiêu', number_format($totals['target_quantity'], 2), 'primary', 'bi-bullseye'],
        ['Thực hiện', number_format($totals['actual_quantity'], 2), 'success', 'bi-check2-circle'],
        ['Còn lại', number_format($totals['remaining_quantity'], 2), 'warning', 'bi-hourglass-split'],
        ['Vượt', number_format($totals['excess_quantity'], 2), 'danger', 'bi-graph-up-arrow'],
        ['Doanh thu', number_format($totals['revenue']) . 'đ', 'info', 'bi-cash-stack'],
        ['Tiền vượt', number_format($totals['payment_amount']) . 'đ', 'danger', 'bi-wallet2'],
    ];
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="page-title">Báo cáo {{ $periodLabel }}</h1>
        <div class="page-subtitle">Tổng hợp kết quả chỉ tiêu, đối chiếu tiến độ và mở nhanh sang các khu vực KPI liên quan.</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if(auth()->user()->allowed('reports','export'))
            <a class="btn btn-success" href="{{ route('reports.export', request()->query()) }}">
                <i class="bi bi-file-earmark-excel me-1"></i>Xuất Excel kỳ này
            </a>
        @endif
        <a class="btn btn-outline-primary" href="{{ route('kpi-dashboard.index', ['year' => $filters['year']]) }}">
            <i class="bi bi-speedometer2 me-1"></i>Tổng quan KPI
        </a>
        @if(auth()->user()->allowed('kpis'))
            <a class="btn btn-outline-secondary" href="{{ route('kpis.index') }}">
                <i class="bi bi-calendar3 me-1"></i>Kế hoạch chỉ tiêu
            </a>
        @endif
        @if(auth()->user()->allowed('imports'))
            <a class="btn btn-outline-secondary" href="{{ route('imports.index') }}">
                <i class="bi bi-cloud-upload me-1"></i>Nhập dữ liệu
            </a>
        @endif
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card card-soft h-100">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                    <div>
                        <div class="small text-uppercase fw-semibold text-muted mb-2">Toàn cảnh báo cáo</div>
                        <h4 class="mb-1">{{ $periodLabel }}</h4>
                        <div class="small text-muted">Dữ liệu đang hiển thị theo bộ lọc hiện tại, dùng để đối chiếu với kế hoạch và kết quả đã nhập.</div>
                    </div>
                    <div class="rounded-3 bg-light px-3 py-2">
                        <div class="small text-muted">Số dòng kết quả</div>
                        <div class="fw-semibold">{{ number_format($rows->count()) }}</div>
                        <div class="small text-muted">Người đạt/vượt: {{ number_format($totals['completed_people']) }}</div>
                    </div>
                </div>
                <div class="row g-3">
                    @foreach($summaryCards as [$label, $value, $color, $icon])
                        <div class="col-sm-6 col-xl-4">
                            <div class="card card-soft stat-card h-100">
                                <div class="card-body p-4">
                                    <div class="stat-label">{{ $label }}</div>
                                    <div class="d-flex justify-content-between align-items-center gap-2">
                                        <div class="stat-value text-{{ $color }}">{{ $value }}</div>
                                        <div class="stat-icon bg-{{ $color }}-subtle text-{{ $color }}"><i class="bi {{ $icon }}"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card card-soft h-100">
            <div class="card-header bg-white border-0 p-4 pb-2">
                <h5 class="mb-1">Trạng thái nổi bật</h5>
                <small class="text-muted">Nhìn nhanh tình trạng hoàn thành của các dòng chỉ tiêu.</small>
            </div>
            <div class="card-body p-4 pt-2">
                @foreach([
                    ['Đã đạt', $statusCounts['completed'], 'success'],
                    ['Vượt chỉ tiêu', $statusCounts['exceeded'], 'danger'],
                    ['Được thanh toán', $statusCounts['payable'], 'info'],
                    ['Chưa đạt', $statusCounts['not_completed'], 'warning'],
                    ['Chưa giao', $statusCounts['no_target'], 'secondary'],
                ] as [$label, $value, $tone])
                    <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                        <span>{{ $label }}</span>
                        <strong class="text-{{ $tone }}">{{ number_format($value) }}</strong>
                    </div>
                @endforeach
                <div class="rounded-3 bg-light p-3 mt-3">
                    <div class="small text-muted mb-1">Người chưa đạt</div>
                    <div class="fs-4 fw-bold text-warning">{{ number_format($totals['not_completed_people']) }}</div>
                    <div class="small text-muted mt-2">Dùng để ưu tiên rà lại nhân sự và khóa học cần hỗ trợ thêm.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card card-soft mb-4">
    <div class="card-header bg-white border-0 p-4 pb-2">
        <h5 class="mb-1">Bộ lọc báo cáo</h5>
        <small class="text-muted">Lọc theo kỳ, nhân sự, khóa học và trạng thái để ra đúng tập dữ liệu cần xem.</small>
    </div>
    <div class="card-body pt-2">
        <form class="filter-panel row g-3" data-report-filter-form>
            <div class="col-md-2">
                <label class="form-label small">Năm</label>
                <input class="form-control" type="number" name="year" value="{{ $filters['year'] }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Loại kỳ</label>
                <select class="form-select" name="period_type" data-period-type>
                    <option value="year" @selected($filters['period_type']==='year')>Theo năm</option>
                    <option value="quarter" @selected($filters['period_type']==='quarter')>Theo quý</option>
                    <option value="month" @selected($filters['period_type']==='month')>Theo tháng</option>
                </select>
            </div>
            <div class="col-md-2" data-period-value-wrap>
                <label class="form-label small" data-period-value-label>Tháng/Quý</label>
                <select class="form-select" name="period_value" data-period-value>
                    <option value="0">Cả năm</option>
                    @for($v = 1; $v <= 12; $v++)
                        <option value="{{ $v }}" @selected($filters['period_value'] === $v)>{{ $v }}</option>
                    @endfor
                </select>
            </div>
            @if(auth()->user()->isLeader())
                <div class="col-md-3">
                    <label class="form-label small">Nhân sự</label>
                    <select class="form-select" name="personnel_id">
                        <option value="">Tất cả nhân sự</option>
                        @foreach($personnels as $p)
                            <option value="{{ $p->id }}" @selected($filters['personnel_id'] === $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Nhóm nhân sự</label>
                    <select class="form-select" name="personnel_type">
                        <option value="">Tất cả nhóm</option>
                        <option value="leader" @selected($filters['personnel_type'] === 'leader')>Lãnh đạo</option>
                        <option value="admin" @selected($filters['personnel_type'] === 'admin')>Admin</option>
                        <option value="employee" @selected($filters['personnel_type'] === 'employee')>Nhân viên</option>
                        <option value="teacher" @selected($filters['personnel_type'] === 'teacher')>Giáo viên</option>
                    </select>
                </div>
            @endif
            <div class="col-md-3">
                <label class="form-label small">Khóa học</label>
                <select class="form-select" name="course_id">
                    <option value="">Tất cả khóa học</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->id }}" @selected($filters['course_id'] === $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Trạng thái</label>
                <select class="form-select" name="status">
                    <option value="">Tất cả</option>
                    <option value="not_completed" @selected(request('status') === 'not_completed')>Chưa đạt</option>
                    <option value="completed" @selected(request('status') === 'completed')>Đã đạt</option>
                    <option value="exceeded" @selected(request('status') === 'exceeded')>Vượt chỉ tiêu</option>
                    <option value="payable" @selected(request('status') === 'payable')>Được thanh toán</option>
                    <option value="no_target" @selected(request('status') === 'no_target')>Chưa giao</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button class="btn btn-dark w-100"><i class="bi bi-filter me-2"></i>Lọc báo cáo</button>
            </div>
        </form>
    </div>
</div>

<div class="card card-soft">
    <div class="card-header bg-white border-0 p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h5 class="mb-1">Chi tiết báo cáo</h5>
            <small class="text-muted">Bảng kết quả theo từng nhân sự và khóa học sau khi áp dụng bộ lọc.</small>
        </div>
        <div class="small text-muted">Tổng {{ number_format($rows->count()) }} dòng</div>
    </div>
    <div class="table-responsive">
        <table class="table table-modern align-middle">
            <thead>
                <tr>
                    <th>Nhân sự</th>
                    <th>Khóa học</th>
                    <th>Quy đổi gốc</th>
                    <th>Chỉ tiêu</th>
                    <th>Thực hiện</th>
                    <th>Còn lại</th>
                    <th>Vượt</th>
                    <th>Doanh thu</th>
                    <th>Tiền vượt</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>
                            <strong>{{ $row['personnel_name'] }}</strong>
                            <div class="small text-muted">{{ $row['personnel_type_label'] }}</div>
                        </td>
                        <td>{{ $row['course_name'] }}</td>
                        <td>{{ number_format($row['raw_quantity'], 2) }}</td>
                        <td>{{ number_format($row['target_quantity'], 2) }}</td>
                        <td class="fw-bold text-success">{{ number_format($row['actual_quantity'], 2) }}</td>
                        <td>{{ number_format($row['remaining_quantity'], 2) }}</td>
                        <td class="fw-bold text-danger">{{ number_format($row['excess_quantity'], 2) }}</td>
                        <td>{{ number_format($row['revenue']) }}đ</td>
                        <td>{{ number_format($row['payment_amount']) }}đ</td>
                        <td>
                            @if($row['status'] === 'exceeded')
                                <span class="badge-soft badge-danger">Vượt chỉ tiêu</span>
                            @elseif($row['status'] === 'completed')
                                <span class="badge-soft badge-success">Đã đạt</span>
                            @elseif($row['status'] === 'not_completed')
                                <span class="badge-soft badge-warning">Chưa đạt</span>
                            @elseif($row['status'] === 'payable')
                                <span class="badge-soft badge-info">Được thanh toán</span>
                            @else
                                <span class="badge-soft badge-gray">Chưa giao</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10">
                            <div class="empty-state">Không có dữ liệu phù hợp bộ lọc.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-report-filter-form]');
    const periodType = form?.querySelector('[data-period-type]');
    const periodValue = form?.querySelector('[data-period-value]');
    const periodValueWrap = form?.querySelector('[data-period-value-wrap]');
    const periodValueLabel = form?.querySelector('[data-period-value-label]');
    if (!form || !periodType || !periodValue || !periodValueWrap || !periodValueLabel) {
        return;
    }

    const currentValue = @json($filters['period_value']);

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
            const selected = Number(currentValue) === value ? ' selected' : '';
            return `<option value="${value}"${selected}>${prefix}${value}</option>`;
        }).join('');
        if (!periodValue.value) {
            periodValue.value = '1';
        }
    };

    periodType.addEventListener('change', () => {
        const previous = periodValue.value;
        fillOptions();
        if (periodType.value !== 'year' && previous && periodValue.querySelector(`option[value="${previous}"]`)) {
            periodValue.value = previous;
        }
    });

    fillOptions();
});
</script>
@endpush
