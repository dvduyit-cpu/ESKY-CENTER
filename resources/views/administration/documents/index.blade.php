@extends('layouts.app')
@section('title', 'Lưu trữ văn thư')
@section('header', 'Hành chính')
@section('content')
<div class="administration-page">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div><h1 class="page-title">Lưu trữ văn thư</h1><div class="page-subtitle">Quản lý thông tin, file scan và file Word của văn bản.</div></div>
        @if($canCreate)<div class="d-flex gap-2"><a class="btn btn-primary" href="{{ route('administration.documents.create') }}"><i class="bi bi-plus-circle me-1"></i>Thêm văn bản</a></div>@endif
    </div>

    <form class="card card-soft mb-4" method="GET"><div class="card-body row g-2">
        <div class="col-lg-7"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Tìm số, ký hiệu, người soạn, người ký, trích yếu…"></div>
        <div class="col-sm-3"><select class="form-select" name="year"><option value="">Tất cả năm</option>@foreach($years as $year)<option value="{{ $year }}" @selected((string)request('year') === (string)$year)>{{ $year }}</option>@endforeach</select></div>
        <div class="col-sm-2 d-grid"><button class="btn btn-primary"><i class="bi bi-search me-1"></i>Tìm</button></div>
    </div></form>

    <div class="card card-soft"><div class="table-responsive"><table class="table table-modern align-middle mb-0">
        <thead><tr><th>Số, ký hiệu VB</th><th>Ngày văn bản</th><th>Người soạn / ký</th><th>Trích yếu</th><th>Nơi nhận / người nhận</th><th>File</th><th></th></tr></thead>
        <tbody>@forelse($documents as $document)
            <tr>
                <td><strong>{{ $document->document_number ?: '—' }}</strong><div class="small text-muted">{{ $document->document_symbol }}</div></td>
                <td class="text-nowrap">{{ $document->document_date->format('d/m/Y') }}</td>
                <td>{{ $document->drafter }}<div class="small text-muted">Ký: {{ $document->signer ?: '—' }}</div></td>
                <td class="document-summary">{{ $document->summary }}@if($document->storage_link)<div class="mt-1"><a href="{{ $document->storage_link }}" target="_blank" rel="noopener"><i class="bi bi-link-45deg"></i> Link lưu trữ</a></div>@endif</td>
                <td>{{ $document->destination ?: '—' }}<div class="small text-muted">{{ $document->receiver ?: '' }}</div></td>
                <td class="text-nowrap"><span class="badge text-bg-light"><i class="bi bi-file-earmark-pdf"></i> {{ $document->attachments->where('kind','scan')->count() }}</span> <span class="badge text-bg-light"><i class="bi bi-file-earmark-word"></i> {{ $document->attachments->where('kind','word')->count() }}</span></td>
                <td class="text-nowrap text-end">@if($canUpdate)<a class="btn btn-sm btn-outline-primary" href="{{ route('administration.documents.edit', $document) }}" title="Xem và sửa"><i class="bi bi-pencil"></i></a>@endif @if($canDelete)<form class="d-inline" method="POST" action="{{ route('administration.documents.destroy', $document) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" data-confirm="Xóa hồ sơ và toàn bộ file đính kèm?" title="Xóa"><i class="bi bi-trash"></i></button></form>@endif</td>
            </tr>
        @empty<tr><td colspan="7"><div class="empty-state py-5">Chưa có hồ sơ văn bản phù hợp.</div></td></tr>@endforelse</tbody>
    </table></div><div class="card-footer bg-white">{{ $documents->links() }}</div></div>
</div>
@endsection
