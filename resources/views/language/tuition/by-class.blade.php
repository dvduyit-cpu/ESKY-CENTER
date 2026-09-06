@extends('layouts.app')

@section('title', 'Thu học phí theo lớp')
@section('header', 'Thu học phí theo lớp')

@section('content')
<div class="d-flex flex-wrap gap-3 align-items-start mb-4">
    <div class="me-auto"><h1 class="page-title">{{ $history ? 'Lịch sử thu học phí theo lớp' : 'Thu học phí theo lớp' }}</h1><div class="page-subtitle">{{ $history ? 'Các lớp giáo vụ đã đóng được giữ lại để tra cứu học phí.' : 'Mở từng lớp để theo dõi học viên đã đóng, chưa đóng và lập khoản thu nhanh.' }}</div></div>
    @if(auth()->user()->allowed('language_tuition_overview'))<a class="btn btn-outline-primary" href="{{ route('language-tuition.overview') }}"><i class="bi bi-pie-chart me-2"></i>Tổng quan học phí</a>@endif
    @if($history)<a class="btn btn-outline-secondary" href="{{ route('language-tuition.by-class.index', request()->except(['history','page'])) }}"><i class="bi bi-arrow-left me-2"></i>Lớp đang thu</a>@else<a class="btn btn-outline-secondary" href="{{ route('language-tuition.by-class.index', array_merge(request()->except(['history','page']), ['history' => 1])) }}"><i class="bi bi-clock-history me-2"></i>Lịch sử lớp đã đóng</a>@endif
</div>

<form class="card card-soft mb-4" method="GET"><div class="card-body p-3 d-flex flex-wrap gap-2 align-items-end"><input type="hidden" name="per_page" value="{{ request('per_page') }}"><div class="flex-grow-1" style="min-width:240px"><label class="form-label small mb-1">Tìm lớp</label><div class="input-group"><span class="input-group-text bg-white"><i class="bi bi-search"></i></span><input class="form-control" name="q" value="{{ $search }}" placeholder="Mã lớp, tên lớp hoặc khóa học..."></div></div><div><label class="form-label small mb-1">Trạng thái lớp</label><select class="form-select" name="status"><option value="">Tất cả lớp</option>@foreach($statusLabels as $key => $label)<option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>@endforeach</select></div><button class="btn btn-primary"><i class="bi bi-funnel me-2"></i>Lọc</button><a class="btn btn-light" href="{{ route('language-tuition.by-class.index') }}">Xóa lọc</a></div></form>

<div class="card card-soft"><div class="table-responsive"><table class="table table-modern mb-0"><thead><tr><th>Lớp học</th><th>Trạng thái</th><th>Học viên</th><th>Khoản thu</th><th>Đã quyết toán</th><th>Còn phải thu</th><th></th></tr></thead><tbody>
@forelse($classes as $class)
    <tr><td><strong>{{ $class->code }}</strong><div class="small text-muted">{{ $class->name }} · {{ $class->course?->name ?? 'Chưa gắn khóa học' }}</div></td><td><span class="badge-soft badge-secondary">{{ $statusLabels[$class->status] ?? $class->status }}</span></td><td>{{ number_format($class->enrollments_count) }}</td><td>{{ number_format($class->tuition_charges_count) }}</td><td class="text-success fw-semibold">{{ number_format($class->settled_tuition_charges_count) }}</td><td class="{{ $class->outstanding_tuition_charges_count > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">{{ number_format($class->outstanding_tuition_charges_count) }}</td><td><a class="btn btn-sm btn-primary" href="{{ route('language-tuition.by-class.show', $class) }}"><i class="bi bi-eye me-1"></i>Mở lớp</a></td></tr>
@empty
    <tr><td colspan="7" class="text-center text-muted py-4">Chưa có lớp có học viên hoặc khoản thu.</td></tr>
@endforelse
</tbody></table></div>@if($classes->hasPages())<div class="card-body border-top">{{ $classes->links() }}</div>@endif</div>
@endsection
