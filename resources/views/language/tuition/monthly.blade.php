@extends('layouts.app')
@section('title', 'Thu học phí theo tháng')
@section('header', 'Quản lý học viên')

@section('content')
@php($defaultScopeDate = now()->format('Y-m-d'))
@php($defaultTargetPaidDate = now()->format('Y-m-d'))
@php($isAdmin = auth()->user()?->isAdmin())

<div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
    <div>
        <h1 class="page-title">Thu học phí tháng {{ $month->format('m/Y') }}</h1>
        <div class="page-subtitle">Tổng hợp theo ngày thực thu, kể cả khi lớp bắt đầu và kết thúc ở hai tháng khác nhau.</div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-light" href="{{ route('language-tuition.monthly', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}"><i class="bi bi-chevron-left me-1"></i>Tháng trước</a>
        <a class="btn btn-light" href="{{ route('language-tuition.monthly', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}">Tháng sau<i class="bi bi-chevron-right ms-1"></i></a>
        <a class="btn btn-outline-danger" href="{{ route('language-tuition.monthly.pdf', request()->query() ?: ['month' => $month->format('Y-m')]) }}" data-no-loading><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
        <a class="btn btn-outline-primary" href="{{ route('language-tuition.index') }}"><i class="bi bi-list-ul me-1"></i>Tất cả khoản thu</a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3"><div class="card card-soft h-100"><div class="card-body p-4"><div class="stat-label">Học phí đã thu</div><div class="stat-value text-success">{{ number_format($tuitionCollected) }}đ</div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card card-soft h-100"><div class="card-body p-4"><div class="stat-label">Tiền giáo trình</div><div class="stat-value text-primary">{{ number_format($bookCollected) }}đ</div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card card-soft h-100"><div class="card-body p-4"><div class="stat-label">Tổng tiền nhận</div><div class="stat-value">{{ number_format($tuitionCollected + $bookCollected) }}đ</div></div></div></div>
    <div class="col-md-6 col-xl-3"><div class="card card-soft h-100"><div class="card-body p-4"><div class="stat-label">Chờ bổ sung phiếu thu</div><div class="stat-value {{ $pendingCount > 0 ? 'text-warning' : 'text-muted' }}">{{ $pendingCount }}</div></div></div></div>
</div>

<form class="filter-panel row g-3 mb-4">
    <div class="col-lg-6"><label class="form-label">Tìm kiếm</label><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Học viên, SĐT, phụ huynh, mã lớp hoặc số phiếu"></div>
    <div class="col-lg-2 col-md-4"><label class="form-label">Tháng thu</label><input class="form-control" type="month" name="month" value="{{ $month->format('Y-m') }}"></div>
    <div class="col-lg-2 col-md-4"><label class="form-label">Trạng thái phiếu</label><select class="form-select" name="receipt_status"><option value="">Tất cả</option><option value="confirmed" @selected(request('receipt_status') === 'confirmed')>Đã xác nhận</option><option value="pending" @selected(request('receipt_status') === 'pending')>Chờ bổ sung</option><option value="cancelled" @selected(request('receipt_status') === 'cancelled')>Đã hủy</option></select></div>
    <div class="col-lg-2 col-md-4 d-flex align-items-end"><button class="btn btn-dark w-100"><i class="bi bi-search me-1"></i>Xem dữ liệu</button></div>
</form>

<div class="alert alert-info border-0 shadow-sm"><i class="bi bi-info-circle-fill me-2"></i>Số liệu tháng được tính theo ngày nhận tiền. Các phiếu đã hủy vẫn được giữ trong lịch sử để admin kiểm tra, nhưng không còn cộng vào tổng tiền đã thu.</div>

@if($isAdmin)
<form id="bulk-monthly-paid-date" method="POST" action="{{ route('language-tuition.monthly.shift-paid-date') }}" data-bulk-form="monthly-paid-date" class="mb-3 d-flex flex-wrap align-items-center gap-2">
    @csrf
    <label class="me-2"><input class="form-check-input me-1" type="checkbox" data-bulk-all="monthly-paid-date"> Chọn tất cả trang này</label>
    <button class="btn btn-sm btn-outline-danger" type="button" data-bulk-submit disabled data-bs-toggle="modal" data-bs-target="#monthlyPaidDateModal">
        <i class="bi bi-calendar2-week me-1"></i>Sửa ngày thu (<span data-bulk-count>0</span>)
    </button>
    <span class="small text-muted">Lọc theo tháng, chọn các phiếu cần đổi ngày, rồi mở modal để cập nhật hàng loạt.</span>
</form>

<div class="modal fade" id="monthlyPaidDateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Cập nhật ngày thu hàng loạt</h5>
                    <div class="small text-muted">Chỉ áp dụng cho các phiếu bạn đã chọn trong danh sách hiện tại.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning small mb-3">
                    Hệ thống sẽ giữ nguyên <strong>giờ thu</strong>, chỉ đổi <strong>phần ngày</strong>, đồng thời cập nhật lại dữ liệu ở trang <strong>Thu học phí theo tháng</strong>.
                </div>
                <div class="small text-muted mb-3">Đang chọn <strong data-monthly-paid-date-selected>0</strong> phiếu.</div>
                <div class="mb-3">
                    <label class="form-label">Ngày nhập tự động</label>
                    <input class="form-control" type="date" name="updated_scope_date" value="{{ $defaultScopeDate }}" form="bulk-monthly-paid-date">
                    <div class="form-text">Chỉ cập nhật các phiếu đã được import/cập nhật vào ngày này. Để trống nếu muốn sửa toàn bộ phiếu đã chọn.</div>
                </div>
                <div>
                    <label class="form-label">Ngày cần sửa</label>
                    <input class="form-control" type="date" name="target_paid_date" value="{{ $defaultTargetPaidDate }}" required form="bulk-monthly-paid-date">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" type="button" data-bs-dismiss="modal">Hủy</button>
                <button class="btn btn-danger" type="submit" form="bulk-monthly-paid-date" data-confirm="Xác nhận cập nhật ngày thu cho các phiếu đã chọn?">
                    <i class="bi bi-arrow-repeat me-1"></i>Cập nhật all
                </button>
            </div>
        </div>
    </div>
</div>
@endif

<div class="card card-soft">
    <div class="table-responsive"><table class="table table-modern align-middle mb-0">
        <thead><tr>@if($isAdmin)<th data-selection-column class="text-center" style="width:42px"></th>@endif<th>Ngày thu / phiếu</th><th>Học viên</th><th>Khóa học / lớp</th><th>Thời gian lớp</th><th>Học phí</th><th>Giáo trình</th><th>Người thu</th><th>Trạng thái</th><th></th></tr></thead>
        <tbody>
        @forelse($items as $payment)
            @php($charge = $payment->charge)
            @php($class = $charge?->languageClass)
            <tr>
                @if($isAdmin)
                <td data-selection-column class="text-center">
                        <input class="form-check-input" type="checkbox" name="ids[]" value="{{ $payment->id }}" form="bulk-monthly-paid-date" data-bulk-item="monthly-paid-date">
                </td>
                @endif
                <td class="text-nowrap"><strong>{{ $payment->paid_at?->format('d/m/Y H:i') }}</strong><div class="small text-muted">{{ $payment->receipt_code ?: 'Chưa có số phiếu' }}</div></td>
                <td><strong>{{ $charge?->student?->name }}</strong><div class="small text-muted">{{ $charge?->student?->code }} · {{ $charge?->student?->phone ?: $charge?->student?->guardians?->first()?->phone }}</div></td>
                <td>{{ $charge?->course?->name }}<div class="small text-muted">{{ $class?->code ?: 'Chưa gắn lớp' }} · {{ $class?->name }}</div></td>
                <td class="text-nowrap">{{ $class?->start_date?->format('d/m/Y') ?: '—' }} – {{ $class?->expected_end_date?->format('d/m/Y') ?: 'Chưa xác định' }}</td>
                <td class="fw-bold text-success text-nowrap">{{ number_format($payment->amount) }}đ</td>
                <td class="text-nowrap">{{ number_format($payment->book_amount) }}đ</td>
                <td>{{ $payment->collector?->name ?: '—' }}</td>
                <td><span class="badge-soft {{ $payment->receipt_status === 'confirmed' ? 'badge-success' : ($payment->receipt_status === 'cancelled' ? 'badge-danger' : 'badge-warning') }}">{{ $payment->receipt_status === 'confirmed' ? 'Đã xác nhận' : ($payment->receipt_status === 'cancelled' ? 'Đã hủy' : 'Chờ bổ sung') }}</span></td>
                <td><a class="btn btn-sm btn-outline-primary" href="{{ route('language-tuition.show', $charge) }}" title="Xem khoản thu"><i class="bi bi-eye"></i></a></td>
            </tr>
        @empty
            <tr><td colspan="{{ $isAdmin ? 10 : 9 }}"><div class="empty-state">Chưa có khoản học phí nào được thu trong tháng {{ $month->format('m/Y') }}.</div></td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="card-footer bg-white border-0">{{ $items->links() }}</div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('monthlyPaidDateModal');
    if (!modal) return;

    const selectedLabel = modal.querySelector('[data-monthly-paid-date-selected]');
    const updateCount = () => {
        const count = document.querySelectorAll('[data-bulk-item="monthly-paid-date"]:checked').length;
        if (selectedLabel) selectedLabel.textContent = String(count);
    };

    modal.addEventListener('show.bs.modal', updateCount);
    document.querySelectorAll('[data-bulk-item="monthly-paid-date"]').forEach(box => box.addEventListener('change', updateCount));
    document.querySelector('[data-bulk-all="monthly-paid-date"]')?.addEventListener('change', () => setTimeout(updateCount, 0));
    updateCount();
});
</script>
@endpush
