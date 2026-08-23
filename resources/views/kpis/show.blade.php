@extends('layouts.app')
@section('title','Chỉ tiêu năm '.$plan->year) @section('header','Kế hoạch chỉ tiêu')
@section('content')
@php
    $planStatusLabels = ['draft' => 'Bản nháp', 'active' => 'Đang áp dụng', 'closed' => 'Đã chốt'];
    $planStatusClasses = ['draft' => 'badge-soft badge-warning', 'active' => 'badge-soft badge-success', 'closed' => 'badge-soft badge-gray'];
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="page-title">{{ $plan->name }}</h1>
        <div class="page-subtitle">Tách riêng bảng chỉ tiêu KPI và bảng giờ dạy để theo dõi trực quan hơn.</div>
    </div>
    <div class="d-flex gap-2">
        @if(auth()->user()->allowed('kpis','create'))
            <a href="{{ route('kpis.import',$plan) }}" class="btn btn-outline-success"><i class="bi bi-file-earmark-excel me-2"></i>Nhập Excel</a>
            <a href="{{ route('kpis.targets.create',$plan) }}" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>Giao chỉ tiêu</a>
        @endif
    </div>
</div>

@if(auth()->user()->allowed('kpis','delete'))
    <form id="bulk-targets" method="POST" action="{{ route('kpis.targets.bulk-destroy',$plan) }}" data-bulk-form="targets" data-bulk-confirm="Xóa các chỉ tiêu đã chọn?" class="mb-3 d-flex flex-wrap align-items-center gap-2">
        @csrf
        @method('DELETE')
        <label class="me-2"><input class="form-check-input me-1" type="checkbox" data-bulk-all="targets"> Chọn tất cả trang này</label>
        <select class="form-select form-select-sm w-auto" name="delete_type">
            <option value="soft">Xóa mềm</option>
            @if(auth()->user()->isAdmin())
                <option value="force">Xóa vĩnh viễn</option>
            @endif
        </select>
        <button class="btn btn-sm btn-outline-danger" data-bulk-submit disabled><i class="bi bi-trash me-1"></i>Xóa đã chọn (<span data-bulk-count>0</span>)</button>
    </form>
@endif

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card card-soft"><div class="card-body"><div class="stat-label">Năm kế hoạch</div><div class="stat-value">{{ $plan->year }}</div></div></div></div>
    <div class="col-md-3"><div class="card card-soft"><div class="card-body"><div class="stat-label">Kỳ thanh toán</div><div class="fs-4 fw-bold">{{ ['month'=>'Theo tháng','quarter'=>'Theo quý','year'=>'Theo năm'][$plan->settlement_scope] }}</div></div></div></div>
    <div class="col-md-3"><div class="card card-soft"><div class="card-body"><div class="stat-label">Trạng thái</div><div class="fs-4 fw-bold">{{ $planStatusLabels[$plan->status] }}</div></div></div></div>
    <div class="col-md-3"><div class="card card-soft"><div class="card-body"><div class="stat-label">Tổng dòng giao</div><div class="stat-value">{{ $targets->total() }}</div></div></div></div>
</div>

<form class="filter-panel row g-3 mb-4">
    <div class="col-md-2">
        <select class="form-select" name="period_type">
            <option value="">Mọi loại kỳ</option>
            <option value="month" @selected(request('period_type')==='month')>Tháng</option>
            <option value="quarter" @selected(request('period_type')==='quarter')>Quý</option>
            <option value="year" @selected(request('period_type')==='year')>Năm</option>
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-select" name="quarter">
            <option value="">Mọi quý</option>
            @for($q=1;$q<=4;$q++)
                <option value="{{ $q }}" @selected((string) request('quarter') === (string) $q)>Quý {{ $q }}</option>
            @endfor
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-select" name="month">
            <option value="">Mọi tháng</option>
            @for($m=1;$m<=12;$m++)
                <option value="{{ $m }}" @selected((string) request('month') === (string) $m)>Tháng {{ $m }}</option>
            @endfor
        </select>
    </div>
    <div class="col-md-3">
        <select class="form-select" name="personnel_id">
            <option value="">Tất cả nhân sự</option>
            @foreach($personnels as $p)
                <option value="{{ $p->id }}" @selected((string) request('personnel_id') === (string) $p->id)>{{ $p->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-select" name="course_id">
            <option value="">Tất cả khóa học</option>
            @foreach($courses as $c)
                <option value="{{ $c->id }}" @selected((string) request('course_id') === (string) $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-1">
        <button class="btn btn-dark w-100"><i class="bi bi-search"></i></button>
    </div>
</form>

<div class="card card-soft mb-4">
    <div class="card-header bg-white border-0 p-4">
        <h5 class="mb-1">Bảng Chỉ Tiêu KPI</h5>
        <small class="text-muted">Hiển thị riêng kết quả KPI theo từng dòng giao chỉ tiêu.</small>
    </div>
    <div class="table-responsive">
        <table class="table table-modern">
            <thead>
                <tr>
                    <th>Nhân sự</th>
                    <th>Khóa học</th>
                    <th>Chỉ tiêu</th>
                    <th>Thực hiện</th>
                    <th>Còn lại</th>
                    <th>Vượt</th>
                    <th>Tiến độ</th>
                    <th class="text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($targets as $t)
                    @php
                        $actualQuantity = (float) ($t->actual_quantity ?? 0);
                        $targetQuantity = (float) $t->target_quantity;
                        $remainingQuantity = $t->is_mandatory ? max($targetQuantity - $actualQuantity, 0) : 0;
                        $excessQuantity = $t->is_mandatory ? max($actualQuantity - $targetQuantity, 0) : $actualQuantity;
                        $completionPct = $targetQuantity > 0 ? round($actualQuantity / $targetQuantity * 100, 1) : 0;
                        $completionWidth = min($completionPct, 100);
                        $statusLabel = ! $t->is_mandatory
                            ? ($actualQuantity > 0 ? 'Được thanh toán' : 'Chưa có kết quả')
                            : ($actualQuantity > $targetQuantity ? 'Vượt chỉ tiêu' : ($actualQuantity >= $targetQuantity ? 'Đã đạt' : 'Chưa đạt'));
                        $statusClass = ! $t->is_mandatory
                            ? ($actualQuantity > 0 ? 'badge-soft badge-info' : 'badge-soft badge-gray')
                            : ($actualQuantity > $targetQuantity ? 'badge-soft badge-danger' : ($actualQuantity >= $targetQuantity ? 'badge-soft badge-success' : 'badge-soft badge-warning'));
                    @endphp
                    <tr>
                        <td>
                            <strong>{{ $t->personnel?->name }}</strong>
                            <div class="small text-muted">{{ $t->personnel?->typeLabel() }}</div>
                            <div class="small text-muted mt-1">
                                @if($t->period_type === 'month')
                                    Tháng {{ $t->month }}/{{ $plan->year }}
                                @elseif($t->period_type === 'quarter')
                                    Quý {{ $t->quarter }}/{{ $plan->year }}
                                @else
                                    Năm {{ $plan->year }}
                                @endif
                            </div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $t->course?->name ?: 'Tất cả khóa học' }}</div>
                            @if(!$t->course_id)
                                <div class="small text-muted">Áp dụng cho toàn bộ khóa học</div>
                            @endif
                        </td>
                        <td>{{ number_format($targetQuantity,2) }}</td>
                        <td class="fw-bold text-success">{{ number_format($actualQuantity,2) }}</td>
                        <td>{{ number_format($remainingQuantity,2) }}</td>
                        <td class="fw-bold text-danger">{{ number_format($excessQuantity,2) }}</td>
                        <td style="min-width:150px">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>{{ number_format($completionPct,1) }}%</span>
                                <span class="{{ $statusClass }}">{{ $statusLabel }}</span>
                            </div>
                                <div class="progress" style="height:8px;">
                                    <div class="progress-bar" style="width:{{ $completionWidth }}%"></div>
                                </div>
                            <div class="small text-muted mt-2">{{ $t->is_mandatory ? 'Chỉ tiêu bắt buộc' : 'Không bắt buộc' }}</div>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#targetViewModal{{ $t->id }}" title="Xem chi tiết">
                                <i class="bi bi-eye"></i>
                            </button>
                            @if(auth()->user()->allowed('kpis','update'))
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('kpis.targets.edit',[$plan,$t]) }}"><i class="bi bi-pencil"></i></a>
                            @endif
                            @if(auth()->user()->allowed('kpis','delete'))
                                <form class="d-inline" method="POST" action="{{ route('kpis.targets.destroy',[$plan,$t]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" data-confirm="Xóa chỉ tiêu này?"><i class="bi bi-trash"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="empty-state">Chưa có chỉ tiêu trong kế hoạch.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white border-0 p-3">{{ $targets->links() }}</div>
</div>

@if($teachingLoadSummary->isNotEmpty())
    <div class="card card-soft">
        <div class="card-header bg-white border-0 p-4">
            <h5 class="mb-1">Bảng Giờ Dạy</h5>
            <small class="text-muted">Hiển thị riêng phần chỉ tiêu và báo cáo giờ dạy của giáo viên.</small>
        </div>
        <div class="table-responsive">
            <table class="table table-modern">
                <thead>
                    <tr>
                        <th>Giáo viên</th>
                        <th>Dạy chỉ tiêu</th>
                        <th>Dạy thực hiện</th>
                        <th>Dạy còn lại</th>
                        <th>Dạy vượt</th>
                        <th>Tiến độ</th>
                        <th>Tháng đã báo cáo</th>
                        <th>Cập nhật cuối</th>
                        <th class="text-end">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($teachingLoadSummary as $row)
                        @php
                            $teachingCompletionPct = $row['assigned_teaching_load'] > 0
                                ? round($row['reported_teaching_load'] / $row['assigned_teaching_load'] * 100, 1)
                                : 0;
                            $teachingCompletionWidth = min($teachingCompletionPct, 100);
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $row['personnel']?->name }}</strong>
                                <div class="small text-muted">{{ $row['personnel']?->typeLabel() }}</div>
                                <div class="small text-muted mt-1">Năm {{ $plan->year }} · {{ $row['report_count'] }} tháng đã gửi</div>
                            </td>
                            <td>{{ number_format($row['assigned_teaching_load'],2) }}</td>
                            <td class="fw-bold text-primary">{{ number_format($row['reported_teaching_load'],2) }}</td>
                            <td>{{ number_format($row['remaining_teaching_load'],2) }}</td>
                            <td class="fw-bold text-danger">{{ number_format($row['exceeded_teaching_load'],2) }}</td>
                            <td style="min-width:150px">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span>{{ number_format($teachingCompletionPct,1) }}%</span>
                                </div>
                                <div class="progress" style="height:8px;">
                                    <div class="progress-bar bg-primary" style="width:{{ $teachingCompletionWidth }}%"></div>
                                </div>
                            </td>
                            <td>
                                @if($row['reported_months']->isNotEmpty())
                                    {{ $row['reported_months']->map(fn ($month) => 'T'.$month)->implode(', ') }}
                                @else
                                    <span class="text-muted">Chưa có</span>
                                @endif
                            </td>
                            <td>{{ $row['latest_report']?->updated_at?->format('d/m/Y H:i') ?: 'Chưa cập nhật' }}</td>
                            <td class="text-end">
                                @if($row['target'])
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#targetViewModal{{ $row['target']->id }}" title="Xem chi tiết">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@foreach($targets as $t)
    @php
        $actualQuantity = (float) ($t->actual_quantity ?? 0);
        $targetQuantity = (float) $t->target_quantity;
        $remainingQuantity = $t->is_mandatory ? max($targetQuantity - $actualQuantity, 0) : 0;
        $excessQuantity = $t->is_mandatory ? max($actualQuantity - $targetQuantity, 0) : $actualQuantity;
        $completionPct = $targetQuantity > 0 ? round($actualQuantity / $targetQuantity * 100, 1) : 0;
        $completionWidth = min($completionPct, 100);

        $teachingTarget = $t->period_type === 'year' ? (float) ($t->assigned_teaching_load ?? 0) : 0;
        $teachingActual = $t->period_type === 'year' ? (float) ($t->reported_teaching_load ?? 0) : 0;
        $teachingRemaining = $t->period_type === 'year' ? max($teachingTarget - $teachingActual, 0) : 0;
        $teachingExcess = $t->period_type === 'year' ? max($teachingActual - $teachingTarget, 0) : 0;
        $teachingCompletionPct = $t->period_type === 'year' && $teachingTarget > 0
            ? round($teachingActual / $teachingTarget * 100, 1)
            : 0;
        $teachingCompletionWidth = min($teachingCompletionPct, 100);

        $statusLabel = ! $t->is_mandatory
            ? ($actualQuantity > 0 ? 'Được thanh toán' : 'Chưa có kết quả')
            : ($actualQuantity > $targetQuantity ? 'Vượt chỉ tiêu' : ($actualQuantity >= $targetQuantity ? 'Đã đạt' : 'Chưa đạt'));
        $statusClass = ! $t->is_mandatory
            ? ($actualQuantity > 0 ? 'badge-soft badge-info' : 'badge-soft badge-gray')
            : ($actualQuantity > $targetQuantity ? 'badge-soft badge-danger' : ($actualQuantity >= $targetQuantity ? 'badge-soft badge-success' : 'badge-soft badge-warning'));
    @endphp
    <div class="modal fade" id="targetViewModal{{ $t->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title mb-1">Thông tin chỉ tiêu</h5>
                        <div class="small text-muted">{{ $t->personnel?->name }} · {{ $t->course?->name ?: 'Tất cả khóa học' }}</div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-4">
                        <div class="col-12">
                            <div class="card card-soft">
                                <div class="card-header bg-white border-0 p-4 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-1">Kết quả chỉ tiêu KPI</h5>
                                        <small class="text-muted">Nhóm chỉ số thực hiện của riêng dòng chỉ tiêu này.</small>
                                    </div>
                                    <span class="{{ $statusClass }}">{{ $statusLabel }}</span>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-sm-6 col-xl-3">
                                            <div class="rounded-3 bg-light h-100 p-3">
                                                <div class="small text-muted">Chỉ tiêu</div>
                                                <div class="fs-4 fw-bold">{{ number_format($targetQuantity,2) }}</div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-xl-3">
                                            <div class="rounded-3 bg-light h-100 p-3">
                                                <div class="small text-muted">Thực hiện</div>
                                                <div class="fs-4 fw-bold text-success">{{ number_format($actualQuantity,2) }}</div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-xl-3">
                                            <div class="rounded-3 bg-light h-100 p-3">
                                                <div class="small text-muted">Còn lại</div>
                                                <div class="fs-4 fw-bold">{{ number_format($remainingQuantity,2) }}</div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-xl-3">
                                            <div class="rounded-3 bg-light h-100 p-3">
                                                <div class="small text-muted">Vượt</div>
                                                <div class="fs-4 fw-bold text-danger">{{ number_format($excessQuantity,2) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="rounded-3 bg-light p-3 mt-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <div class="small text-muted">Tiến độ KPI</div>
                                                <div class="fw-semibold">{{ $t->is_mandatory ? 'Chỉ tiêu bắt buộc' : 'Chỉ tiêu không bắt buộc' }}</div>
                                            </div>
                                            <div class="fs-5 fw-bold">{{ number_format($completionPct,1) }}%</div>
                                        </div>
                                        <div class="progress" style="height:8px;">
                                            <div class="progress-bar" style="width:{{ $completionWidth }}%"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="card card-soft">
                                <div class="card-header bg-white border-0 p-4">
                                    <h5 class="mb-1">Kết quả giờ dạy</h5>
                                    <small class="text-muted">Nhóm chỉ số giờ dạy áp dụng cho chỉ tiêu năm.</small>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-sm-6 col-xl-3">
                                            <div class="rounded-3 bg-light h-100 p-3">
                                                <div class="small text-muted">Dạy chỉ tiêu</div>
                                                <div class="fs-5 fw-bold">{{ $t->period_type === 'year' ? number_format($teachingTarget,2) : '—' }}</div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-xl-3">
                                            <div class="rounded-3 bg-light h-100 p-3">
                                                <div class="small text-muted">Dạy thực hiện</div>
                                                <div class="fs-5 fw-bold text-primary">{{ $t->period_type === 'year' ? number_format($teachingActual,2) : '—' }}</div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-xl-3">
                                            <div class="rounded-3 bg-light h-100 p-3">
                                                <div class="small text-muted">Dạy còn lại</div>
                                                <div class="fs-5 fw-bold">{{ $t->period_type === 'year' ? number_format($teachingRemaining,2) : '—' }}</div>
                                            </div>
                                        </div>
                                        <div class="col-sm-6 col-xl-3">
                                            <div class="rounded-3 bg-light h-100 p-3">
                                                <div class="small text-muted">Dạy vượt</div>
                                                <div class="fs-5 fw-bold text-danger">{{ $t->period_type === 'year' ? number_format($teachingExcess,2) : '—' }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="rounded-3 bg-light p-3 mt-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <div class="small text-muted">Tiến độ giờ dạy</div>
                                                <div class="fw-semibold">{{ $t->period_type === 'year' ? 'Theo dõi theo năm' : 'Không áp dụng cho kỳ này' }}</div>
                                            </div>
                                            <div class="fs-5 fw-bold">{{ $t->period_type === 'year' ? number_format($teachingCompletionPct,1).'%' : '—' }}</div>
                                        </div>
                                        @if($t->period_type === 'year')
                                            <div class="progress" style="height:8px;">
                                                <div class="progress-bar bg-primary" style="width:{{ $teachingCompletionWidth }}%"></div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card card-soft mt-4">
                        <div class="card-header bg-white border-0 p-4">
                            <h5 class="mb-1">Thông tin chỉ tiêu</h5>
                            <small class="text-muted">Nhóm thông tin theo từng phần để theo dõi nhanh hơn.</small>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-lg-4">
                                    <div class="rounded-3 bg-light h-100 p-3">
                                        <div class="text-uppercase small fw-semibold text-muted mb-3">Phạm vi áp dụng</div>
                                        <div class="mb-3">
                                            <div class="small text-muted">Nhân sự</div>
                                            <div class="fw-semibold">{{ $t->personnel?->name }}</div>
                                            <div class="small text-muted">{{ $t->personnel?->typeLabel() }}</div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="small text-muted">Khóa học</div>
                                            <div class="fw-semibold">{{ $t->course?->name ?: 'Tất cả khóa học' }}</div>
                                        </div>
                                        <div>
                                            <div class="small text-muted">Kỳ áp dụng</div>
                                            <div class="fw-semibold">
                                                @if($t->period_type === 'month')
                                                    Tháng {{ $t->month }}/{{ $plan->year }}
                                                @elseif($t->period_type === 'quarter')
                                                    Quý {{ $t->quarter }}/{{ $plan->year }}
                                                @else
                                                    Năm {{ $plan->year }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="rounded-3 bg-light h-100 p-3">
                                        <div class="text-uppercase small fw-semibold text-muted mb-3">Thiết lập thanh toán</div>
                                        <div class="mb-3">
                                            <div class="small text-muted">Doanh thu mục tiêu</div>
                                            <div class="fw-semibold">{{ number_format($t->target_revenue) }}đ</div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="small text-muted">Trả/KPI vượt</div>
                                            <div class="fw-semibold">{{ number_format($t->excess_payment_per_kpi ?: $t->course?->default_excess_rate) }}đ</div>
                                        </div>
                                        <div>
                                            <div class="small text-muted">Trạng thái kế hoạch</div>
                                            <div class="mt-1"><span class="{{ $planStatusClasses[$plan->status] }}">{{ $planStatusLabels[$plan->status] }}</span></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="rounded-3 bg-light h-100 p-3">
                                        <div class="text-uppercase small fw-semibold text-muted mb-3">Ghi chú</div>
                                        <div class="small text-muted mb-2">Mô tả thêm cho dòng chỉ tiêu</div>
                                        <div class="fw-semibold">{{ $t->note ?: 'Chưa có ghi chú.' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    @if(auth()->user()->allowed('kpis','update'))
                        <a class="btn btn-outline-primary" href="{{ route('kpis.targets.edit',[$plan,$t]) }}">Chỉnh sửa</a>
                    @endif
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection
