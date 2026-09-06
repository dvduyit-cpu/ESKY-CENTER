@extends('layouts.app')

@section('title', 'Tổng quan học phí')
@section('header', 'Tổng quan học phí')

@section('content')
@php
    $payable = (float) $summary->payable_amount;
    $paid = (float) $summary->paid_amount;
    $credit = (float) $summary->credit_amount;
    $remaining = (float) $summary->remaining_amount;
    $collectionRate = $payable > 0 ? min(100, round((($paid + $credit) / $payable) * 100, 1)) : 0;
    $statusLabels = ['unpaid' => 'Chưa đóng', 'partial' => 'Đóng một phần', 'pending_receipt' => 'Chờ bổ sung phiếu thu', 'paid' => 'Đã đóng đủ', 'transferred' => 'Đã quyết toán chuyển lớp'];
    $maxCollection = max(1, $monthlyCollections->max(fn ($month) => $month['tuition'] + $month['books']));
@endphp

<div class="d-flex flex-wrap gap-3 align-items-start mb-4">
    <div class="me-auto">
        <h1 class="page-title">Tổng quan học phí</h1>
        <div class="page-subtitle">Theo dõi tổng phải thu, thực thu, công nợ và các phiếu thu mới nhất.</div>
    </div>
    @if(auth()->user()->allowed('language_tuition'))
        <a class="btn btn-outline-primary" href="{{ route('language-tuition.index') }}"><i class="bi bi-list-ul me-2"></i>Chi tiết khoản thu</a>
    @endif
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><div class="card card-soft h-100"><div class="card-body p-4"><div class="small text-muted">Tổng phải thu</div><div class="fs-4 fw-bold mt-1">{{ number_format($payable) }}đ</div><div class="small text-muted mt-2">{{ number_format($summary->charge_count) }} khoản thu</div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card card-soft h-100 border-success-subtle"><div class="card-body p-4"><div class="small text-muted">Đã thu</div><div class="fs-4 fw-bold text-success mt-1">{{ number_format($paid) }}đ</div><div class="small text-muted mt-2">Tỷ lệ hoàn thành {{ number_format($collectionRate, 1) }}%</div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card card-soft h-100 border-primary-subtle"><div class="card-body p-4"><div class="small text-muted">Học phí chuyển sang</div><div class="fs-4 fw-bold text-primary mt-1">{{ number_format($credit) }}đ</div><div class="small text-muted mt-2">Đã quyết toán khi chuyển lớp</div></div></div></div>
    <div class="col-sm-6 col-xl-3"><div class="card card-soft h-100 border-danger-subtle"><div class="card-body p-4"><div class="small text-muted">Còn phải thu</div><div class="fs-4 fw-bold text-danger mt-1">{{ number_format($remaining) }}đ</div><div class="small text-muted mt-2">Quá hạn: {{ number_format($overdue->total) }} khoản · {{ number_format($overdue->amount) }}đ</div></div></div></div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card card-soft h-100"><div class="card-header bg-white p-4"><h5 class="mb-1">Thu học phí 6 tháng gần đây</h5><div class="small text-muted">Chỉ tính phiếu đã xác nhận, gồm học phí và giáo trình.</div></div><div class="card-body p-4">
            @foreach($monthlyCollections as $month)
                @php($total = $month['tuition'] + $month['books'])
                <div class="mb-3"><div class="d-flex justify-content-between small mb-1"><span>{{ $month['label'] }}</span><strong>{{ number_format($total) }}đ</strong></div><div class="progress" style="height:10px"><div class="progress-bar bg-success" style="width: {{ round(($total / $maxCollection) * 100, 1) }}%"></div></div></div>
            @endforeach
        </div></div>
    </div>
    <div class="col-lg-5">
        <div class="card card-soft h-100"><div class="card-header bg-white p-4"><h5 class="mb-1">Trạng thái khoản thu</h5><div class="small text-muted">Theo số khoản đang quản lý.</div></div><div class="list-group list-group-flush">
            @foreach($statusLabels as $status => $label)
                <div class="list-group-item px-4 py-3 d-flex justify-content-between gap-3"><span>{{ $label }}</span><strong>{{ number_format($statusCounts[$status] ?? 0) }}</strong></div>
            @endforeach
        </div></div>
    </div>
</div>

<div class="card card-soft"><div class="card-header bg-white p-4"><h5 class="mb-1">Phiếu thu đã xác nhận gần đây</h5><div class="small text-muted">Cập nhật theo thời điểm xác nhận phiếu thu.</div></div><div class="table-responsive"><table class="table table-modern mb-0"><thead><tr><th>Thời gian</th><th>Học viên</th><th>Lớp</th><th>Số tiền</th><th>Người thu</th>@if(auth()->user()->allowed('language_tuition'))<th></th>@endif</tr></thead><tbody>
    @forelse($recentPayments as $payment)
        <tr><td>{{ $payment->paid_at?->format('d/m/Y H:i') }}</td><td><strong>{{ $payment->charge?->student?->name ?? '—' }}</strong><div class="small text-muted">{{ $payment->charge?->code }}</div></td><td>{{ $payment->charge?->languageClass?->code ?? 'Chưa gắn lớp' }}</td><td class="fw-bold text-success">{{ number_format((float) $payment->amount + (float) $payment->book_amount) }}đ</td><td>{{ $payment->collector?->name ?? '—' }}</td>@if(auth()->user()->allowed('language_tuition'))<td><a class="btn btn-sm btn-outline-primary" href="{{ route('language-tuition.show', $payment->charge) }}"><i class="bi bi-eye"></i></a></td>@endif</tr>
    @empty
        <tr><td class="text-center text-muted py-4" colspan="{{ auth()->user()->allowed('language_tuition') ? 6 : 5 }}">Chưa có phiếu thu đã xác nhận.</td></tr>
    @endforelse
</tbody></table></div></div>
@endsection
