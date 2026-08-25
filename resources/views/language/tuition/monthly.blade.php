@extends('layouts.app')
@section('title', 'Thu học phí theo tháng')
@section('header', 'Quản lý học viên')

@section('content')
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

<div class="card card-soft">
    <div class="table-responsive"><table class="table table-modern align-middle mb-0">
        <thead><tr><th>Ngày thu / phiếu</th><th>Học viên</th><th>Khóa học / lớp</th><th>Thời gian lớp</th><th>Học phí</th><th>Giáo trình</th><th>Người thu</th><th>Trạng thái</th><th></th></tr></thead>
        <tbody>
        @forelse($items as $payment)
            @php($charge = $payment->charge)
            @php($class = $charge?->languageClass)
            <tr>
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
            <tr><td colspan="9"><div class="empty-state">Chưa có khoản học phí nào được thu trong tháng {{ $month->format('m/Y') }}.</div></td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="card-footer bg-white border-0">{{ $items->links() }}</div>
</div>
@endsection
