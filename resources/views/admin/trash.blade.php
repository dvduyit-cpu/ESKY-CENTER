@extends('layouts.app')
@section('title', 'Thùng rác chung')
@section('header', 'Quản trị hệ thống')

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="page-title"><i class="bi bi-trash-fill me-2"></i>Thùng rác chung</h1>
        <div class="page-subtitle">Mọi dữ liệu đã xóa mềm sẽ hiển thị ở đây để admin theo dõi và khôi phục.</div>
    </div>
    <div class="small text-muted">
        Chỉ admin mới xem và khôi phục được dữ liệu trong thùng rác.
    </div>
</div>

<form class="filter-panel row g-3 mb-4">
    <div class="col-md-5">
        <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Tìm theo tên, mã, email, số điện thoại...">
    </div>
    <div class="col-md-4">
        <select class="form-select" name="type">
            <option value="">Mọi loại dữ liệu</option>
            @foreach($types as $type)
                <option value="{{ $type['key'] }}" @selected(request('type') === $type['key'])>{{ $type['label'] }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 d-flex gap-2">
        <button class="btn btn-dark flex-fill">Lọc</button>
        @if(request()->filled('q') || request()->filled('type'))
            <a class="btn btn-outline-secondary" href="{{ route('admin.trash.index') }}">Xóa lọc</a>
        @endif
    </div>
</form>

<div class="card card-soft">
    <div class="table-responsive">
        <table class="table table-modern align-middle">
            <thead>
            <tr>
                <th>Dữ liệu</th>
                <th>Loại</th>
                <th>Phân hệ</th>
                <th>Đã xóa lúc</th>
                <th>Người xóa</th>
                <th></th>
            </tr>
            </thead>
            <tbody>
            @forelse($items as $item)
                @php($logKey = $item['subject_type'].'#'.$item['id'])
                @php($deleteLog = $deleteLogs->get($logKey))
                <tr>
                    <td>
                        <strong>{{ $item['title'] }}</strong>
                        <div class="small text-muted">
                            @if($item['subtitle'])
                                {{ $item['subtitle'] }}
                            @else
                                ID #{{ $item['id'] }}
                            @endif
                        </div>
                    </td>
                    <td><span class="badge-soft badge-danger">{{ $item['type_label'] }}</span></td>
                    <td>{{ $item['module_label'] }}</td>
                    <td>{{ $item['deleted_at']?->format('d/m/Y H:i') ?: '—' }}</td>
                    <td>{{ $deleteLog?->user?->name ?: 'Không rõ' }}</td>
                    <td class="text-end text-nowrap">
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#trashDetailModal"
                            data-trash-title="{{ $item['title'] }}"
                            data-trash-subtitle="{{ $item['subtitle'] ?: 'ID #'.$item['id'] }}"
                            data-trash-type="{{ $item['type_label'] }}"
                            data-trash-module="{{ $item['module_label'] }}"
                            data-trash-deleted-at="{{ $item['deleted_at']?->format('d/m/Y H:i') ?: '—' }}"
                            data-trash-deleted-by="{{ $deleteLog?->user?->name ?: 'Không rõ' }}"
                            data-trash-restore="{{ route('admin.trash.restore', ['type' => $item['type'], 'id' => $item['id']]) }}"
                        >
                            <i class="bi bi-eye me-1"></i>Xem
                        </button>
                        <form class="d-inline" method="POST" action="{{ route('admin.trash.restore', ['type' => $item['type'], 'id' => $item['id']]) }}">
                            @csrf
                            @method('PATCH')
                            <button class="btn btn-sm btn-outline-success" data-confirm="Khôi phục dữ liệu này?">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Khôi phục
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6"><div class="empty-state">Chưa có dữ liệu nào trong thùng rác.</div></td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white border-0">
        {{ $items->links() }}
    </div>
</div>

<div class="modal fade" id="trashDetailModal" tabindex="-1" aria-labelledby="trashDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="trashDetailModalLabel">Xem dữ liệu đã xóa</h5>
                    <div class="small text-muted" data-trash-detail-subtitle></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="small text-muted">Tên dữ liệu</div>
                    <strong data-trash-detail-title></strong>
                </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="small text-muted">Loại dữ liệu</div>
                        <div data-trash-detail-type></div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Phân hệ</div>
                        <div data-trash-detail-module></div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Đã xóa lúc</div>
                        <div data-trash-detail-deleted-at></div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted">Người xóa</div>
                        <div data-trash-detail-deleted-by></div>
                    </div>
                </div>
                <div class="alert alert-light border mt-3 mb-0 small">
                    Đây là bản ghi đã xóa mềm. Bạn có thể xem nhanh thông tin nhận diện rồi khôi phục ngay trong cửa sổ này.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
                <form method="POST" data-trash-detail-form>
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-success" data-confirm="Khôi phục dữ liệu này?">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Khôi phục
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('trashDetailModal');
    if (!modal) return;

    modal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget;
        if (!button) return;

        modal.querySelector('[data-trash-detail-title]').textContent = button.dataset.trashTitle || '';
        modal.querySelector('[data-trash-detail-subtitle]').textContent = button.dataset.trashSubtitle || '';
        modal.querySelector('[data-trash-detail-type]').textContent = button.dataset.trashType || '';
        modal.querySelector('[data-trash-detail-module]').textContent = button.dataset.trashModule || '';
        modal.querySelector('[data-trash-detail-deleted-at]').textContent = button.dataset.trashDeletedAt || '';
        modal.querySelector('[data-trash-detail-deleted-by]').textContent = button.dataset.trashDeletedBy || '';
        modal.querySelector('[data-trash-detail-form]').action = button.dataset.trashRestore || '';
    });
});
</script>
@endpush
