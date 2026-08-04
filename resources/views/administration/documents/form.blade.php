@extends('layouts.app')
@section('title', $document->exists ? 'Cập nhật văn bản' : 'Thêm văn bản')
@section('header', 'Hành chính')
@section('content')
@php $editing = $document->exists; @endphp
<div class="administration-page">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div><h1 class="page-title">{{ $editing ? 'Cập nhật hồ sơ văn bản' : 'Thêm hồ sơ văn bản' }}</h1><div class="page-subtitle">File tải lên được lưu riêng tư và chỉ tải xuống qua tài khoản có quyền.</div></div>
        <a class="btn btn-outline-secondary" href="{{ route('administration.documents.index') }}"><i class="bi bi-arrow-left me-1"></i>Danh sách văn thư</a>
    </div>

    <form method="POST" enctype="multipart/form-data" action="{{ $editing ? route('administration.documents.update', $document) : route('administration.documents.store') }}">
        @csrf @if($editing) @method('PUT') @endif
        <div class="card card-soft mb-4"><div class="card-header bg-white"><strong>Thông tin văn bản</strong></div><div class="card-body"><div class="row g-3">
            <div class="col-md-3"><label class="form-label">Số văn bản</label><input class="form-control" name="document_number" maxlength="100" value="{{ old('document_number', $document->document_number) }}"></div>
            <div class="col-md-5"><label class="form-label">Ký hiệu văn bản <span class="text-danger">*</span></label><input class="form-control" name="document_symbol" maxlength="150" required value="{{ old('document_symbol', $document->document_symbol) }}">@error('document_symbol')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-md-4"><label class="form-label">Ngày văn bản <span class="text-danger">*</span></label><input class="form-control" type="date" name="document_date" required value="{{ old('document_date', $document->document_date?->toDateString() ?? now()->toDateString()) }}">@error('document_date')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">Người soạn <span class="text-danger">*</span></label><input class="form-control" name="drafter" maxlength="150" required value="{{ old('drafter', $document->drafter) }}">@error('drafter')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">Người ký</label><input class="form-control" name="signer" maxlength="150" value="{{ old('signer', $document->signer) }}"></div>
            <div class="col-12"><label class="form-label">Trích yếu <span class="text-danger">*</span></label><textarea class="form-control" name="summary" rows="3" maxlength="5000" required>{{ old('summary', $document->summary) }}</textarea>@error('summary')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">Nơi nhận</label><textarea class="form-control" name="destination" rows="2" maxlength="3000">{{ old('destination', $document->destination) }}</textarea></div>
            <div class="col-md-6"><label class="form-label">Người nhận</label><input class="form-control" name="receiver" maxlength="200" value="{{ old('receiver', $document->receiver) }}"></div>
            <div class="col-12"><label class="form-label">Link lưu trữ</label><input class="form-control" type="url" name="storage_link" maxlength="2000" placeholder="https://…" value="{{ old('storage_link', $document->storage_link) }}">@error('storage_link')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-12"><label class="form-label">Ghi chú</label><textarea class="form-control" name="notes" rows="3" maxlength="5000">{{ old('notes', $document->notes) }}</textarea></div>
        </div></div></div>

        <div class="card card-soft mb-4"><div class="card-header bg-white"><strong>File đính kèm</strong></div><div class="card-body"><div class="row g-3">
            <div class="col-md-6"><label class="form-label">File scan (PDF, JPG, PNG)</label><input class="form-control" type="file" name="scan_files[]" accept=".pdf,.jpg,.jpeg,.png" multiple><div class="form-text">Tối đa 5 file/lần, mỗi file 20 MB.</div>@error('scan_files.*')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-md-6"><label class="form-label">File văn bản (DOC, DOCX, ODT)</label><input class="form-control" type="file" name="word_files[]" accept=".doc,.docx,.odt" multiple><div class="form-text">Tối đa 5 file/lần, mỗi file 20 MB.</div>@error('word_files.*')<div class="text-danger small">{{ $message }}</div>@enderror</div>
        </div>
        @if($editing && $document->attachments->isNotEmpty())
        <div class="attachment-list mt-4">
            @foreach($document->attachments->groupBy('kind') as $kind => $files)
                <div class="small fw-semibold text-uppercase text-muted mb-2">{{ $kind === 'scan' ? 'File scan' : 'File văn bản' }}</div>
                @foreach($files as $file)<div class="attachment-row"><a href="{{ route('administration.documents.attachments.download', [$document, $file]) }}"><i class="bi {{ $kind === 'scan' ? 'bi-file-earmark-pdf' : 'bi-file-earmark-word' }} me-1"></i>{{ $file->original_name }}</a><span class="small text-muted">{{ number_format($file->size / 1024, 0, ',', '.') }} KB</span><form method="POST" action="{{ route('administration.documents.attachments.destroy', [$document, $file]) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" data-confirm="Xóa file này?"><i class="bi bi-trash"></i></button></form></div>@endforeach
            @endforeach
        </div>
        @endif
        </div></div>

        <div class="d-flex justify-content-end gap-2"><a class="btn btn-outline-secondary" href="{{ route('administration.documents.index') }}">Hủy</a><button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>{{ $editing ? 'Lưu thay đổi' : 'Tạo hồ sơ' }}</button></div>
    </form>
</div>
@endsection
