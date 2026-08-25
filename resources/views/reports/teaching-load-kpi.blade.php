@extends('layouts.app')
@section('title', 'KPI giờ dạy')
@section('header', 'KPI giờ dạy')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="page-title">Báo cáo KPI giờ dạy</h1>
        <div class="page-subtitle">Theo dõi chỉ tiêu giờ dạy, số tiết đã báo cáo và tiến độ hoàn thành theo từng giáo viên.</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('reports.index', ['year' => $filters['year']]) }}">
            <i class="bi bi-arrow-left me-1"></i>Báo cáo chung
        </a>
        <a class="btn btn-outline-primary" href="{{ route('reports.recruitment-kpi', ['year' => $filters['year']]) }}">
            <i class="bi bi-person-plus me-1"></i>KPI tuyển sinh
        </a>
        @if(auth()->user()->allowed('teaching_load_management'))
            <a class="btn btn-outline-primary" href="{{ route('teaching-load-management.index', ['year' => $filters['year']]) }}">
                <i class="bi bi-kanban me-1"></i>Trang quản lý giờ dạy
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
                <label class="form-label small">Tháng</label>
                <select class="form-select" name="report_month">
                    <option value="">Cả năm</option>
                    @for($month = 1; $month <= 12; $month++)
                        <option value="{{ $month }}" @selected($filters['report_month'] === $month)>Tháng {{ $month }}</option>
                    @endfor
                </select>
            </div>
            @if($canViewAll)
                <div class="col-md-5">
                    <label class="form-label small">Giáo viên</label>
                    <select class="form-select" name="personnel_id">
                        <option value="0">Tất cả giáo viên</option>
                        @foreach($personnels as $personnel)
                            <option value="{{ $personnel->id }}" @selected($filters['personnel_id'] === (int) $personnel->id)>{{ $personnel->name }}</option>
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

@if(! $plan)
    <div class="alert alert-warning border-0 shadow-sm">Năm {{ $filters['year'] }} chưa có kế hoạch KPI để tổng hợp giờ dạy.</div>
@else
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card card-soft h-100"><div class="card-body"><div class="stat-label">Giáo viên</div><div class="stat-value">{{ number_format($totals['teacher_count']) }}</div></div></div>
        </div>
        <div class="col-md-2">
            <div class="card card-soft h-100"><div class="card-body"><div class="stat-label">Tiết được giao</div><div class="stat-value text-primary">{{ number_format($totals['assigned_teaching_load'], 2) }}</div></div></div>
        </div>
        <div class="col-md-2">
            <div class="card card-soft h-100"><div class="card-body"><div class="stat-label">Đã báo cáo</div><div class="stat-value text-success">{{ number_format($totals['reported_teaching_load'], 2) }}</div></div></div>
        </div>
        <div class="col-md-2">
            <div class="card card-soft h-100"><div class="card-body"><div class="stat-label">Còn lại</div><div class="stat-value text-warning">{{ number_format($totals['remaining_teaching_load'], 2) }}</div></div></div>
        </div>
        <div class="col-md-2">
            <div class="card card-soft h-100"><div class="card-body"><div class="stat-label">Vượt</div><div class="stat-value text-danger">{{ number_format($totals['exceeded_teaching_load'], 2) }}</div></div></div>
        </div>
        <div class="col-md-2">
            <div class="card card-soft h-100"><div class="card-body"><div class="stat-label">{{ $filters['report_month'] ? 'Tiết tháng xem' : 'Tiết kỳ xem' }}</div><div class="stat-value text-info">{{ number_format($totals['period_teaching_load'], 2) }}</div></div></div>
        </div>
    </div>

    <div class="card card-soft">
        <div class="card-header bg-white border-0 p-4">
            <h5 class="mb-1">Bảng KPI giờ dạy theo giáo viên</h5>
            <small class="text-muted">
                @if($filters['report_month'])
                    Đang xem tháng {{ $filters['report_month'] }}/{{ $filters['year'] }}.
                @else
                    Đang xem tổng hợp năm {{ $filters['year'] }}.
                @endif
            </small>
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
                        <th>{{ $filters['report_month'] ? 'Tiết tháng' : 'Tiết kỳ xem' }}</th>
                        <th>Tháng đã báo cáo</th>
                        <th>Cập nhật cuối</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
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
                            <td colspan="8"><div class="empty-state">Chưa có dữ liệu giờ dạy phù hợp với bộ lọc.</div></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
