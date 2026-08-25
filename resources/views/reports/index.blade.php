@extends('layouts.app')
@section('title', 'Báo cáo chỉ tiêu')
@section('header', 'Báo cáo chỉ tiêu')

@section('content')
@php
    $canFilterPersonnel = auth()->user()->isLeader();
    $statusCounts = [
        'exceeded' => $rows->where('status', 'exceeded')->count(),
        'completed' => $rows->where('status', 'completed')->count(),
        'payable' => $rows->where('status', 'payable')->count(),
        'not_completed' => $rows->where('status', 'not_completed')->count(),
        'no_target' => $rows->where('status', 'no_target')->count(),
    ];
    $summaryCards = [
        ['Tổng chỉ tiêu', number_format($totals['target_quantity'], 2), 'primary', 'bi-bullseye'],
        ['Đã thực hiện', number_format($totals['actual_quantity'], 2), 'success', 'bi-check2-circle'],
        ['Còn lại', number_format($totals['remaining_quantity'], 2), 'warning', 'bi-hourglass-split'],
        ['Vượt chỉ tiêu', number_format($totals['excess_quantity'], 2), 'danger', 'bi-graph-up-arrow'],
        ['Doanh thu', number_format($totals['revenue'], 2), 'info', 'bi-cash-stack'],
        ['Tiền vượt dự kiến', number_format($totals['payment_amount'], 2), 'dark', 'bi-wallet2'],
    ];
    $statusLabelMap = [
        'exceeded' => 'Vượt chỉ tiêu',
        'completed' => 'Đã đạt',
        'payable' => 'Được thanh toán',
        'not_completed' => 'Chưa đạt',
        'no_target' => 'Chưa giao',
    ];
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="page-title">Báo cáo chỉ tiêu</h1>
        <div class="page-subtitle">Trang tổng hợp toàn bộ chỉ tiêu, KPI tuyển sinh và KPI giờ dạy theo cùng bộ lọc kỳ để theo dõi một chỗ.</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-primary" href="{{ route('reports.export', request()->query()) }}">
            <i class="bi bi-download me-1"></i>Xuất Excel
        </a>
        @if(auth()->user()->allowed('reports'))
            <a class="btn btn-outline-primary" href="{{ route('reports.recruitment-kpi', ['year' => $filters['year'], 'period_type' => $filters['period_type'], 'period_value' => $filters['period_value']]) }}">
                <i class="bi bi-person-plus me-1"></i>KPI tuyển sinh
            </a>
            <a class="btn btn-outline-primary" href="{{ route('reports.teaching-load-kpi', ['year' => $filters['year']]) }}">
                <i class="bi bi-clock-history me-1"></i>KPI giờ dạy
            </a>
        @endif
        @if(auth()->user()->allowed('teaching_load_management'))
            <a class="btn btn-outline-primary" href="{{ route('teaching-load-management.index', ['year' => $filters['year']]) }}">
                <i class="bi bi-kanban me-1"></i>Tổng hợp giờ dạy
            </a>
        @endif
        @if(\Illuminate\Support\Facades\Route::has('kpi-dashboard.index'))
            <a class="btn btn-outline-secondary" href="{{ route('kpi-dashboard.index', ['year' => $filters['year']]) }}">
                <i class="bi bi-speedometer me-1"></i>Tổng quan KPI
            </a>
        @endif
        @if(auth()->user()->allowed('kpis'))
            <a class="btn btn-outline-secondary" href="{{ route('kpis.index', ['year' => $filters['year']]) }}">
                <i class="bi bi-bullseye me-1"></i>Kế hoạch chỉ tiêu
            </a>
        @endif
        @if(auth()->user()->allowed('imports'))
            <a class="btn btn-outline-secondary" href="{{ route('imports.index') }}">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i>Nhập Excel
            </a>
        @endif
    </div>
</div>

<div class="row g-3 mb-4">
    @foreach($summaryCards as [$label, $value, $tone, $icon])
        <div class="col-md-4 col-xl-2">
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
    <div class="col-xl-9">
        <div class="card card-soft h-100">
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
                    @if($canFilterPersonnel)
                        <div class="col-md-3">
                            <label class="form-label small">Nhân sự</label>
                            <select class="form-select" name="personnel_id">
                                <option value="0">Tất cả nhân sự</option>
                                @foreach($personnels as $personnel)
                                    <option value="{{ $personnel->id }}" @selected($filters['personnel_id'] === (int) $personnel->id)>{{ $personnel->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-md-3">
                        <label class="form-label small">Khóa học</label>
                        <select class="form-select" name="course_id">
                            <option value="0">Tất cả khóa học</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->id }}" @selected($filters['course_id'] === (int) $course->id)>{{ $course->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Loại nhân sự</label>
                        <select class="form-select" name="personnel_type">
                            <option value="">Tất cả</option>
                            <option value="employee" @selected($filters['personnel_type'] === 'employee')>Nhân viên</option>
                            <option value="teacher" @selected($filters['personnel_type'] === 'teacher')>Giáo viên</option>
                            <option value="collaborator" @selected($filters['personnel_type'] === 'collaborator')>Cộng tác viên</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Trạng thái</label>
                        <select class="form-select" name="status">
                            <option value="">Tất cả</option>
                            @foreach($statusLabelMap as $status => $label)
                                <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-dark w-100"><i class="bi bi-filter me-1"></i>Lọc</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-xl-3">
        <div class="card card-soft h-100">
            <div class="card-header bg-white border-0 p-4">
                <h5 class="mb-1">Trạng thái KPI</h5>
                <small class="text-muted">Kỳ đang xem: {{ $periodLabel }}</small>
            </div>
            <div class="card-body pt-0">
                @foreach([
                    ['Vượt chỉ tiêu', $statusCounts['exceeded'], 'danger', 'exceeded'],
                    ['Đã đạt', $statusCounts['completed'], 'success', 'completed'],
                    ['Được thanh toán', $statusCounts['payable'], 'info', 'payable'],
                    ['Chưa đạt', $statusCounts['not_completed'], 'warning', 'not_completed'],
                    ['Chưa giao', $statusCounts['no_target'], 'secondary', 'no_target'],
                ] as [$label, $count, $tone, $status])
                    <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
                        <a class="text-decoration-none text-body" href="{{ route('reports.index', array_filter(array_merge(request()->query(), ['status' => $status]))) }}">{{ $label }}</a>
                        <strong class="text-{{ $tone }}">{{ number_format($count) }}</strong>
                    </div>
                @endforeach
                @if(request()->filled('status'))
                    <div class="pt-3">
                        <a class="btn btn-outline-secondary w-100" href="{{ route('reports.index', request()->except('status')) }}">
                            <i class="bi bi-x-circle me-1"></i>Bỏ lọc trạng thái
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card card-soft">
            <div class="card-header bg-white border-0 p-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-1">Tổng hợp KPI tuyển sinh</h5>
                    <small class="text-muted">Dùng cùng kỳ lọc hiện tại: {{ $periodLabel }}.</small>
                </div>
                @if(auth()->user()->allowed('reports'))
                    <a class="btn btn-outline-primary" href="{{ route('reports.recruitment-kpi', ['year' => $filters['year'], 'period_type' => $filters['period_type'], 'period_value' => $filters['period_value']]) }}">
                        <i class="bi bi-arrow-up-right-circle me-1"></i>Mở trang KPI tuyển sinh
                    </a>
                @endif
            </div>
            <div class="card-body pt-0">
                <div class="row g-3 mb-4">
                    @foreach([
                        ['Lead mới', number_format($recruitmentOverview['totals']['lead_count']), 'primary', 'bi-person-plus-fill'],
                        ['Đã tư vấn', number_format($recruitmentOverview['totals']['consulted_count']), 'info', 'bi-headset'],
                        ['Đã đăng ký', number_format($recruitmentOverview['totals']['registered_count']), 'success', 'bi-check2-circle'],
                        ['Chưa phân công', number_format($recruitmentOverview['totals']['unassigned_count']), 'warning', 'bi-person-x'],
                        ['Tỷ lệ chuyển đổi', number_format($recruitmentOverview['totals']['conversion_rate'], 1).'%', 'danger', 'bi-graph-up-arrow'],
                    ] as [$label, $value, $tone, $icon])
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

                <div class="row g-4">
                    <div class="col-xl-8">
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
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recruitmentOverview['rows'] as $row)
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
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7"><div class="empty-state">Chưa có dữ liệu tuyển sinh trong kỳ này.</div></td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-xl-4">
                        <div class="card card-soft h-100">
                            <div class="card-header bg-white border-0 p-4">
                                <h6 class="mb-1">Trạng thái lead</h6>
                                <small class="text-muted">Phân bổ nhanh trong kỳ hiện tại.</small>
                            </div>
                            <div class="card-body pt-0">
                                @foreach([
                                    ['Mới tiếp nhận', $recruitmentOverview['status_counts']['new'] ?? 0, 'secondary'],
                                    ['Đã liên hệ', $recruitmentOverview['status_counts']['contacted'] ?? 0, 'info'],
                                    ['Đang tư vấn', $recruitmentOverview['status_counts']['consulting'] ?? 0, 'primary'],
                                    ['Hẹn kiểm tra', $recruitmentOverview['status_counts']['placement_test'] ?? 0, 'warning'],
                                    ['Chờ phản hồi', $recruitmentOverview['status_counts']['waiting'] ?? 0, 'warning'],
                                    ['Đã đăng ký', $recruitmentOverview['status_counts']['registered'] ?? 0, 'success'],
                                    ['Không quan tâm', $recruitmentOverview['status_counts']['not_interested'] ?? 0, 'danger'],
                                    ['Chăm sóc lại', $recruitmentOverview['status_counts']['follow_up'] ?? 0, 'info'],
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
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card card-soft">
            <div class="card-header bg-white border-0 p-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h5 class="mb-1">Tổng hợp KPI giờ dạy</h5>
                    <small class="text-muted">{{ $teachingLoadOverview['period_caption'] }} trong năm {{ $filters['year'] }}.</small>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if(auth()->user()->allowed('reports'))
                        <a class="btn btn-outline-primary" href="{{ route('reports.teaching-load-kpi', ['year' => $filters['year'], 'report_month' => $filters['period_type'] === 'month' ? $filters['period_value'] : null]) }}">
                            <i class="bi bi-arrow-up-right-circle me-1"></i>Mở trang KPI giờ dạy
                        </a>
                    @endif
                    @if(auth()->user()->allowed('teaching_load_management'))
                        <a class="btn btn-outline-secondary" href="{{ route('teaching-load-management.index', ['year' => $filters['year'], 'report_month' => $filters['period_type'] === 'month' ? $filters['period_value'] : null]) }}">
                            <i class="bi bi-kanban me-1"></i>Tổng hợp giờ dạy
                        </a>
                    @endif
                </div>
            </div>
            <div class="card-body pt-0">
                @if(! $teachingLoadOverview['plan'])
                    <div class="alert alert-warning border-0 shadow-sm mb-0">Năm {{ $filters['year'] }} chưa có kế hoạch KPI để tổng hợp giờ dạy.</div>
                @else
                    <div class="row g-3 mb-4">
                        @foreach([
                            ['Giáo viên', number_format($teachingLoadOverview['totals']['teacher_count']), 'secondary', 'bi-people-fill'],
                            ['Tiết được giao', number_format($teachingLoadOverview['totals']['assigned_teaching_load'], 2), 'primary', 'bi-journal-check'],
                            ['Đã báo cáo', number_format($teachingLoadOverview['totals']['reported_teaching_load'], 2), 'success', 'bi-check2-circle'],
                            ['Còn lại', number_format($teachingLoadOverview['totals']['remaining_teaching_load'], 2), 'warning', 'bi-hourglass-split'],
                            ['Vượt', number_format($teachingLoadOverview['totals']['exceeded_teaching_load'], 2), 'danger', 'bi-graph-up-arrow'],
                            [$teachingLoadOverview['period_caption'], number_format($teachingLoadOverview['totals']['period_teaching_load'], 2), 'info', 'bi-clock-history'],
                        ] as [$label, $value, $tone, $icon])
                            <div class="col-md-4 col-xl-2">
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

                    <div class="table-responsive">
                        <table class="table table-modern align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Giáo viên</th>
                                    <th>Tiết giao</th>
                                    <th>Đã báo cáo</th>
                                    <th>Còn lại</th>
                                    <th>Vượt</th>
                                    <th>{{ $teachingLoadOverview['period_caption'] }}</th>
                                    <th>Tháng đã báo cáo</th>
                                    <th>Cập nhật cuối</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($teachingLoadOverview['rows'] as $row)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $row['personnel']?->name ?? 'Không xác định' }}</div>
                                            <div class="small text-muted">{{ $row['personnel']?->typeLabel() ?? 'Nhân sự' }}</div>
                                            <div class="progress mt-2" style="height: 7px;">
                                                <div class="progress-bar bg-primary" style="width: {{ $row['progress'] }}%"></div>
                                            </div>
                                            <div class="small text-muted mt-1">Tiến độ {{ number_format($row['progress'], 1) }}%</div>
                                        </td>
                                        <td>{{ number_format($row['assigned_teaching_load'], 2) }}</td>
                                        <td class="fw-bold text-success">{{ number_format($row['reported_teaching_load'], 2) }}</td>
                                        <td>{{ number_format($row['remaining_teaching_load'], 2) }}</td>
                                        <td class="fw-bold text-danger">{{ number_format($row['exceeded_teaching_load'], 2) }}</td>
                                        <td class="fw-bold text-info">{{ number_format($row['period_teaching_load'], 2) }}</td>
                                        <td>
                                            @if(collect($row['months_reported'])->isNotEmpty())
                                                {{ collect($row['months_reported'])->map(fn ($month) => 'T'.$month)->implode(', ') }}
                                            @else
                                                <span class="text-muted">Chưa có</span>
                                            @endif
                                        </td>
                                        <td>{{ $row['latest_report']?->updated_at?->format('d/m/Y H:i') ?: 'Chưa cập nhật' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8"><div class="empty-state">Chưa có dữ liệu giờ dạy phù hợp với bộ lọc hiện tại.</div></td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card card-soft">
    <div class="card-header bg-white border-0 p-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <h5 class="mb-1">Chi tiết KPI</h5>
            <small class="text-muted">Tổng hợp {{ number_format($rows->count()) }} dòng KPI trong kỳ {{ $periodLabel }}.</small>
        </div>
        <div class="small text-muted">Số người đạt/vượt: <strong>{{ number_format($totals['completed_people']) }}</strong> | Chưa đạt: <strong>{{ number_format($totals['not_completed_people']) }}</strong></div>
    </div>
    <div class="table-responsive">
        <table class="table table-modern align-middle mb-0">
            <thead>
                <tr>
                    <th>Nhân sự</th>
                    <th>Khóa học</th>
                    <th>Bắt buộc</th>
                    <th>Chỉ tiêu</th>
                    <th>Thực hiện</th>
                    <th>Còn lại</th>
                    <th>Vượt</th>
                    <th>Tiến độ</th>
                    <th>Doanh thu</th>
                    <th>Trả/KPI</th>
                    <th>Tiền vượt</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    @php
                        $statusTone = match ($row['status']) {
                            'exceeded' => 'danger',
                            'completed' => 'success',
                            'payable' => 'info',
                            'not_completed' => 'warning',
                            default => 'gray',
                        };
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold">{{ $row['personnel_name'] }}</div>
                            <div class="small text-muted">{{ $row['personnel_type_label'] }}</div>
                        </td>
                        <td>{{ $row['course_name'] ?: '-' }}</td>
                        <td>{{ $row['is_mandatory'] ? 'Có' : 'Không' }}</td>
                        <td>{{ number_format($row['target_quantity'], 2) }}</td>
                        <td class="fw-bold text-success">{{ number_format($row['actual_quantity'], 2) }}</td>
                        <td>{{ number_format($row['remaining_quantity'], 2) }}</td>
                        <td class="fw-bold text-danger">{{ number_format($row['excess_quantity'], 2) }}</td>
                        <td style="min-width: 150px;">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>{{ number_format($row['completion_pct'], 1) }}%</span>
                            </div>
                            <div class="progress" style="height: 7px;">
                                <div class="progress-bar bg-primary" style="width: {{ min(100, $row['completion_pct']) }}%"></div>
                            </div>
                        </td>
                        <td>{{ number_format($row['revenue'], 2) }}</td>
                        <td>{{ number_format($row['payment_rate'], 2) }}</td>
                        <td>{{ number_format($row['payment_amount'], 2) }}</td>
                        <td><span class="badge-soft badge-{{ $statusTone }}">{{ $statusLabelMap[$row['status']] ?? 'Chưa có dữ liệu' }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12">
                            <div class="empty-state">
                                <i class="bi bi-inbox fs-1"></i>
                                <p class="mt-2 mb-0">Chưa có dữ liệu KPI phù hợp với bộ lọc hiện tại.</p>
                            </div>
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
            const selected = Number(selectedValue) === value ? 'selected' : '';
            return `<option value="${value}" ${selected}>${prefix}${value}</option>`;
        }).join('');
    };

    periodType.addEventListener('change', () => {
        periodValue.dataset.changed = '1';
        fillOptions();
    });

    fillOptions();
});
</script>
@endpush
