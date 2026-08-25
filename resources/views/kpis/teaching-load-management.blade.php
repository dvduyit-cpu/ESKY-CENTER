@extends('layouts.app')
@section('title', 'Tổng hợp giờ dạy năm '.$year)
@section('header', 'Tổng hợp giờ dạy')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="page-title">Tổng hợp giờ dạy</h1>
        <div class="page-subtitle">Danh sách ngoài cùng chỉ hiển thị theo giáo viên. Bấm xem để mở modal, trong modal sẽ nhóm theo tháng và mở tiếp để xem chi tiết từng tháng.</div>
    </div>
    <form class="row g-2 align-items-center">
        <div class="col-sm-auto">
            <input class="form-control" list="teaching-load-years" type="number" name="year" value="{{ $year }}" min="2020" max="2100" style="width: 140px;">
            <datalist id="teaching-load-years">
                @foreach($availableYears as $availableYear)
                    <option value="{{ $availableYear }}"></option>
                @endforeach
            </datalist>
        </div>
        <div class="col-sm-auto">
            <select class="form-select" name="report_month">
                <option value="">Cả năm</option>
                @for($month = 1; $month <= 12; $month++)
                    <option value="{{ $month }}" @selected($selectedMonth === $month)>Tháng {{ $month }}</option>
                @endfor
            </select>
        </div>
        <div class="col-sm-auto">
            <select class="form-select" name="personnel_id">
                <option value="">Tất cả giáo viên</option>
                @foreach($personnels as $personnelOption)
                    <option value="{{ $personnelOption->id }}" @selected($selectedPersonnelId === (int) $personnelOption->id)>{{ $personnelOption->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-auto">
            <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Lọc</button>
        </div>
    </form>
</div>

@if(! $plan)
    <div class="alert alert-warning border-0 shadow-sm">Năm {{ $year }} chưa có kế hoạch KPI nên chưa thể tổng hợp giờ dạy.</div>
@else
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card card-soft h-100"><div class="card-body"><div class="stat-label">Giáo viên</div><div class="stat-value">{{ number_format($summaryTotals['teacher_count']) }}</div></div></div>
        </div>
        <div class="col-md-2">
            <div class="card card-soft h-100"><div class="card-body"><div class="stat-label">Tiết được giao</div><div class="stat-value text-primary">{{ number_format($summaryTotals['assigned_teaching_load'], 2) }}</div></div></div>
        </div>
        <div class="col-md-2">
            <div class="card card-soft h-100"><div class="card-body"><div class="stat-label">Đã báo cáo</div><div class="stat-value text-success">{{ number_format($summaryTotals['reported_teaching_load'], 2) }}</div></div></div>
        </div>
        <div class="col-md-2">
            <div class="card card-soft h-100"><div class="card-body"><div class="stat-label">Còn lại</div><div class="stat-value text-warning">{{ number_format($summaryTotals['remaining_teaching_load'], 2) }}</div></div></div>
        </div>
        <div class="col-md-2">
            <div class="card card-soft h-100"><div class="card-body"><div class="stat-label">Vượt</div><div class="stat-value text-danger">{{ number_format($summaryTotals['exceeded_teaching_load'], 2) }}</div></div></div>
        </div>
        <div class="col-md-2">
            <div class="card card-soft h-100"><div class="card-body"><div class="stat-label">{{ $selectedMonth ? 'Tiết tháng đang xem' : 'Tổng tiết năm đang xem' }}</div><div class="stat-value text-info">{{ number_format($summaryTotals['period_teaching_load'], 2) }}</div></div></div>
        </div>
    </div>

    <div class="card card-soft">
        <div class="card-header bg-white border-0 p-4">
            <h5 class="mb-1">Bảng tổng hợp theo giáo viên</h5>
            <small class="text-muted">
                @if($selectedMonth)
                    Đang lọc theo tháng {{ $selectedMonth }}/{{ $year }}. Bấm xem ở từng giáo viên để mở modal theo tháng.
                @else
                    Đang xem tổng hợp năm {{ $year }}. Bấm xem ở từng giáo viên để mở modal nhóm theo 12 tháng.
                @endif
            </small>
        </div>
        <div class="table-responsive">
            <table class="table table-modern align-middle mb-0">
                <thead>
                    <tr>
                        <th>Giáo viên</th>
                        <th>Tiết giao năm</th>
                        <th>Đã báo cáo năm</th>
                        <th>Còn lại</th>
                        <th>Vượt</th>
                        <th>{{ $selectedMonth ? 'Tiết tháng' : 'Tổng kỳ xem' }}</th>
                        <th>Tháng đã báo cáo</th>
                        <th>Cập nhật cuối</th>
                        <th class="text-end">Xem</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summaryRows as $row)
                        @php
                            $progress = $row['assigned_teaching_load'] > 0
                                ? min(round($row['reported_teaching_load'] / $row['assigned_teaching_load'] * 100, 1), 100)
                                : 0;
                            $modalId = 'teaching-load-personnel-'.$loop->index;
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $row['personnel']?->name ?? 'Không xác định' }}</div>
                                <div class="small text-muted">{{ $row['personnel']?->typeLabel() ?? 'Nhân sự' }}</div>
                                <div class="progress mt-2" style="height: 7px;">
                                    <div class="progress-bar bg-primary" style="width: {{ $progress }}%"></div>
                                </div>
                                <div class="small text-muted mt-1">Tiến độ {{ number_format($progress, 1) }}%</div>
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
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#{{ $modalId }}">
                                    <i class="bi bi-eye me-1"></i>Xem
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9"><div class="empty-state">Chưa có dữ liệu giờ dạy phù hợp với bộ lọc hiện tại.</div></td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @foreach($summaryRows as $row)
        @php
            $modalId = 'teaching-load-personnel-'.$loop->index;
            $monthRows = collect($row['monthly_breakdown']);
            if ($selectedMonth) {
                $monthRows = $monthRows->where('month', $selectedMonth)->values();
            }
        @endphp
        <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header border-0 pb-0">
                        <div>
                            <h5 class="modal-title mb-1">{{ $row['personnel']?->name ?? 'Không xác định' }}</h5>
                            <div class="small text-muted">
                                @if($selectedMonth)
                                    Tổng hợp giờ dạy tháng {{ $selectedMonth }}/{{ $year }}
                                @else
                                    Tổng hợp giờ dạy theo tháng trong năm {{ $year }}
                                @endif
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="row g-3 mb-4">
                            <div class="col-md-3"><div class="card card-soft h-100"><div class="card-body"><div class="stat-label">Tiết giao năm</div><div class="stat-value text-primary">{{ number_format($row['assigned_teaching_load'], 2) }}</div></div></div></div>
                            <div class="col-md-3"><div class="card card-soft h-100"><div class="card-body"><div class="stat-label">Đã báo cáo năm</div><div class="stat-value text-success">{{ number_format($row['reported_teaching_load'], 2) }}</div></div></div></div>
                            <div class="col-md-3"><div class="card card-soft h-100"><div class="card-body"><div class="stat-label">{{ $selectedMonth ? 'Tiết tháng đang xem' : 'Tổng kỳ xem' }}</div><div class="stat-value text-info">{{ number_format($row['period_teaching_load'], 2) }}</div></div></div></div>
                            <div class="col-md-3"><div class="card card-soft h-100"><div class="card-body"><div class="stat-label">Cập nhật cuối</div><div class="fw-semibold mt-2">{{ $row['latest_report']?->updated_at?->format('d/m/Y H:i') ?: 'Chưa cập nhật' }}</div></div></div></div>
                        </div>

                        <div class="d-grid gap-3">
                            @forelse($monthRows as $monthRow)
                                @php
                                    $monthDetailModalId = 'teaching-load-month-detail-'.$loop->parent->index.'-'.$monthRow['month'];
                                @endphp
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                                        <div>
                                            <div class="fw-semibold">Tháng {{ $monthRow['month'] }}</div>
                                            <div class="small text-muted">{{ $monthRow['reporter_name'] ?: 'Chưa có người lưu' }}</div>
                                        </div>
                                        <div class="d-flex flex-wrap align-items-center gap-2">
                                            <span class="badge-soft {{ $monthRow['total'] > 0 ? 'badge-success' : 'badge-gray' }}">
                                                {{ $monthRow['total'] > 0 ? 'Đã báo cáo' : 'Còn trống' }}
                                            </span>
                                            <span class="badge-soft badge-info">{{ number_format($monthRow['total'], 2) }} tiết</span>
                                            <button class="btn btn-sm btn-outline-primary" type="button" data-modal-switch data-modal-target="#{{ $monthDetailModalId }}">
                                                <i class="bi bi-eye me-1"></i>Xem chi tiết
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="empty-state">Không có dữ liệu tháng phù hợp với bộ lọc hiện tại.</div>
                            @endforelse
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
                    </div>
                </div>
            </div>
        </div>

        @foreach($monthRows as $monthRow)
            @php
                $monthDetailModalId = 'teaching-load-month-detail-'.$loop->parent->index.'-'.$monthRow['month'];
            @endphp
            <div class="modal fade" id="{{ $monthDetailModalId }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
                    <div class="modal-content border-0 shadow-lg">
                        <div class="modal-header border-0 pb-0">
                            <div>
                                <h5 class="modal-title mb-1">{{ $row['personnel']?->name ?? 'Không xác định' }}</h5>
                                <div class="small text-muted">Chi tiết giờ dạy tháng {{ $monthRow['month'] }}/{{ $year }}</div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                        </div>
                        <div class="modal-body pt-3">
                            <div class="row g-3 mb-4">
                                <div class="col-md-4"><div class="card card-soft h-100"><div class="card-body"><div class="stat-label">Tiết tháng {{ $monthRow['month'] }}</div><div class="stat-value text-info">{{ number_format($monthRow['total'], 2) }}</div></div></div></div>
                                <div class="col-md-4"><div class="card card-soft h-100"><div class="card-body"><div class="stat-label">Người lưu</div><div class="fw-semibold mt-2">{{ $monthRow['reporter_name'] ?: 'Chưa có người lưu' }}</div></div></div></div>
                                <div class="col-md-4"><div class="card card-soft h-100"><div class="card-body"><div class="stat-label">Cập nhật cuối</div><div class="fw-semibold mt-2">{{ $monthRow['updated_at']?->format('d/m/Y H:i') ?: 'Chưa cập nhật' }}</div></div></div></div>
                            </div>

                            <div class="card card-soft">
                                <div class="card-header bg-white border-0 p-4">
                                    <h6 class="mb-1">Chi tiết buổi dạy trong tháng</h6>
                                    <small class="text-muted">Nguồn dữ liệu lấy từ báo cáo tiết dạy giáo viên đã lưu theo tháng.</small>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-modern mb-0">
                                        <thead>
                                            <tr>
                                                <th>Ngày</th>
                                                <th>Lớp/Mã lớp</th>
                                                <th>Khung giờ</th>
                                                <th>Số tiết</th>
                                                <th>Ghi chú</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($monthRow['detail_rows'] as $detailRow)
                                                <tr>
                                                    <td>{{ $detailRow['date'] ?: '-' }}</td>
                                                    <td>{{ $detailRow['class_name'] ?: '-' }}</td>
                                                    <td>{{ $detailRow['time_slot'] ?: '-' }}</td>
                                                    <td>{{ $detailRow['lesson_count'] !== '' ? $detailRow['lesson_count'] : '-' }}</td>
                                                    <td>{{ $detailRow['note'] ?: '-' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5"><div class="empty-state">Tháng này chưa có chi tiết giờ dạy.</div></td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 pt-0">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
@endforeach
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-modal-switch]').forEach((button) => {
        button.addEventListener('click', () => {
            const targetSelector = button.getAttribute('data-modal-target');
            const sourceModalElement = button.closest('.modal');
            const targetModalElement = targetSelector ? document.querySelector(targetSelector) : null;
            if (!sourceModalElement || !targetModalElement || typeof bootstrap === 'undefined') {
                return;
            }

            const sourceModal = bootstrap.Modal.getOrCreateInstance(sourceModalElement);
            const targetModal = bootstrap.Modal.getOrCreateInstance(targetModalElement);
            targetModalElement.dataset.returnModalTarget = '#'+sourceModalElement.id;

            const openTargetModal = () => {
                sourceModalElement.removeEventListener('hidden.bs.modal', openTargetModal);
                targetModal.show();
            };

            sourceModalElement.addEventListener('hidden.bs.modal', openTargetModal);
            sourceModal.hide();
        });
    });

    document.querySelectorAll('.modal').forEach((modalElement) => {
        modalElement.addEventListener('hidden.bs.modal', () => {
            const returnTarget = modalElement.dataset.returnModalTarget;
            if (!returnTarget || typeof bootstrap === 'undefined') {
                return;
            }

            modalElement.dataset.returnModalTarget = '';
            const parentModalElement = document.querySelector(returnTarget);
            if (!parentModalElement) {
                return;
            }

            bootstrap.Modal.getOrCreateInstance(parentModalElement).show();
        });
    });
});
</script>
@endpush
