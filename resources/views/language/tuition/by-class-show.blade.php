@extends('layouts.app')

@section('title', 'Học phí lớp '.$languageClass->code)
@section('header', 'Thu học phí theo lớp')

@section('content')
<div class="d-flex flex-wrap gap-3 align-items-start mb-4"><div class="me-auto"><h1 class="page-title">{{ $languageClass->code }} · {{ $languageClass->name }}</h1><div class="page-subtitle">{{ $languageClass->course?->name ?? 'Chưa gắn khóa học' }} · Xem tình hình học phí của từng học viên.</div></div><a class="btn btn-light" href="{{ route('language-tuition.by-class.index') }}"><i class="bi bi-arrow-left me-2"></i>Danh sách lớp</a></div>

<div class="row g-3 mb-4"><div class="col-sm-6 col-xl-3"><div class="card card-soft h-100"><div class="card-body p-3"><div class="small text-muted">Học viên</div><strong class="fs-4">{{ number_format($summary['students']) }}</strong></div></div></div><div class="col-sm-6 col-xl-3"><div class="card card-soft h-100"><div class="card-body p-3"><div class="small text-muted">Chưa lập khoản thu</div><strong class="fs-4 text-secondary">{{ number_format($summary['not_created']) }}</strong></div></div></div><div class="col-sm-6 col-xl-3"><div class="card card-soft h-100"><div class="card-body p-3"><div class="small text-muted">Đã hoàn tất</div><strong class="fs-4 text-success">{{ number_format($summary['settled']) }}</strong></div></div></div><div class="col-sm-6 col-xl-3"><div class="card card-soft h-100"><div class="card-body p-3"><div class="small text-muted">Còn phải thu</div><strong class="fs-4 text-danger">{{ number_format($summary['outstanding']) }}</strong></div></div></div></div>

<div class="card card-soft"><div class="table-responsive"><table class="table table-modern mb-0"><thead><tr><th>Học viên</th><th>Trạng thái học</th><th>Khoản thu</th><th>Phải đóng</th><th>Đã đóng</th><th>Còn lại</th><th>Trạng thái học phí</th><th></th></tr></thead><tbody>
@forelse($rows as $row)
    @php($charge = $row['charge'])
    <tr><td><strong>{{ $row['student']?->name ?? 'Học viên đã xóa' }}</strong><div class="small text-muted">{{ $row['student']?->code }}</div></td><td>{{ $row['enrollment'] ? ($enrollmentLabels[$row['enrollment']->status] ?? $row['enrollment']->status) : 'Đã rời lớp' }}</td><td>{{ $charge?->code ?? 'Chưa lập' }}</td><td>{{ $charge ? number_format($charge->payable_amount).'đ' : '—' }}</td><td class="text-success">{{ $charge ? number_format($charge->paid_amount).'đ' : '—' }}</td><td class="{{ $row['remaining'] > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">{{ $charge ? number_format($row['remaining']).'đ' : '—' }}</td><td>@if(!$charge)<span class="badge-soft badge-secondary">Chưa lập khoản thu</span>@else<span class="badge-soft {{ $row['remaining'] <= 0 ? 'badge-success' : ($charge->status === 'partial' ? 'badge-warning' : 'badge-danger') }}">{{ $chargeLabels[$charge->status] ?? $charge->status }}</span>@endif</td><td class="text-end">@if(!$charge && $row['student'] && auth()->user()->allowed('language_tuition', 'create'))<a class="btn btn-sm btn-primary" href="{{ route('language-tuition.create', ['class' => $languageClass->id, 'student' => $row['student']->id]) }}"><i class="bi bi-plus-circle me-1"></i>Lập khoản thu</a>@elseif($charge && auth()->user()->allowed('language_tuition'))<a class="btn btn-sm btn-outline-primary" href="{{ route('language-tuition.show', $charge) }}"><i class="bi bi-cash-coin me-1"></i>Thu học phí</a>@endif</td></tr>
@empty
    <tr><td colspan="8" class="text-center text-muted py-4">Lớp chưa có học viên hoặc khoản thu.</td></tr>
@endforelse
</tbody></table></div></div>
@endsection
