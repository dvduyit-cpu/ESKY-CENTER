@extends('layouts.app')

@section('title', 'Thu học phí theo lớp')
@section('header', 'Thu học phí theo lớp')

@section('content')
<div class="d-flex flex-wrap gap-3 align-items-start mb-4">
    <div class="me-auto"><h1 class="page-title">Thu học phí theo lớp</h1><div class="page-subtitle">Mở từng lớp để theo dõi học viên đã đóng, chưa đóng và lập khoản thu nhanh.</div></div>
    @if(auth()->user()->allowed('language_tuition_overview'))<a class="btn btn-outline-primary" href="{{ route('language-tuition.overview') }}"><i class="bi bi-pie-chart me-2"></i>Tổng quan học phí</a>@endif
</div>

<form class="card card-soft mb-4" method="GET"><div class="card-body p-3 d-flex flex-wrap gap-2 align-items-end"><div><label class="form-label small mb-1">Trạng thái lớp</label><select class="form-select" name="status"><option value="">Tất cả lớp</option>@foreach($statusLabels as $key => $label)<option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>@endforeach</select></div><button class="btn btn-primary"><i class="bi bi-funnel me-2"></i>Lọc</button><a class="btn btn-light" href="{{ route('language-tuition.by-class.index') }}">Xóa lọc</a></div></form>

<div class="card card-soft"><div class="table-responsive"><table class="table table-modern mb-0"><thead><tr><th>Lớp học</th><th>Trạng thái</th><th>Học viên</th><th>Khoản thu</th><th>Đã quyết toán</th><th>Còn phải thu</th><th></th></tr></thead><tbody>
@forelse($classes as $class)
    <tr><td><strong>{{ $class->code }}</strong><div class="small text-muted">{{ $class->name }} · {{ $class->course?->name ?? 'Chưa gắn khóa học' }}</div></td><td><span class="badge-soft badge-secondary">{{ $statusLabels[$class->status] ?? $class->status }}</span></td><td>{{ number_format($class->enrollments_count) }}</td><td>{{ number_format($class->tuition_charges_count) }}</td><td class="text-success fw-semibold">{{ number_format($class->settled_tuition_charges_count) }}</td><td class="{{ $class->outstanding_tuition_charges_count > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">{{ number_format($class->outstanding_tuition_charges_count) }}</td><td><a class="btn btn-sm btn-primary" href="{{ route('language-tuition.by-class.show', $class) }}"><i class="bi bi-eye me-1"></i>Mở lớp</a></td></tr>
@empty
    <tr><td colspan="7" class="text-center text-muted py-4">Chưa có lớp có học viên hoặc khoản thu.</td></tr>
@endforelse
</tbody></table></div>@if($classes->hasPages())<div class="card-body border-top">{{ $classes->links() }}</div>@endif</div>
@endsection
