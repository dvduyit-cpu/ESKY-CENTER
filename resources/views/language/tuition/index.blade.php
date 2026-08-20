@extends('layouts.app')

@section('title', 'Thu học phí')
@section('header', 'Quản lý học viên')

@section('content')
@php($labels = ['unpaid' => 'Chưa đóng', 'partial' => 'Đóng một phần', 'pending_receipt' => 'Chờ bổ sung phiếu thu', 'paid' => 'Đã đóng đủ', 'transferred' => 'Đã quyết toán chuyển lớp'])

<div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
    <div>
        <h1 class="page-title">Thu học phí</h1>
        <div class="page-subtitle">Danh sách khoản phải thu của từng học viên và lớp học. Chọn nhiều dòng để xuất nhanh hoặc áp dụng lại mức miễn giảm cao nhất.</div>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary" href="{{ route('language-tuition.monthly') }}">
            <i class="bi bi-calendar2-check me-2"></i>Theo tháng
        </a>
        <a class="btn btn-outline-success" href="{{ route('language-tuition.export', request()->query()) }}">
            <i class="bi bi-file-earmark-excel me-2"></i>Excel
        </a>
        <a class="btn btn-primary" href="{{ route('language-tuition.create') }}">
            <i class="bi bi-plus-circle me-2"></i>Lập khoản thu
        </a>
    </div>
</div>

<form class="filter-panel row g-3 mb-4">
    <div class="col-lg-3">
        <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Mã lớp, khoản thu hoặc học viên">
    </div>
    <div class="col-lg-3">
        <select class="form-select" name="class">
            <option value="">Mọi mã lớp</option>
            @foreach($classes as $class)
                <option value="{{ $class->id }}" @selected((int) request('class') === $class->id)>{{ $class->code }} – {{ $class->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-lg-2">
        <select class="form-select" name="status">
            <option value="">Mọi trạng thái</option>
            @foreach($labels as $key => $label)
                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-lg-1">
        <input class="form-control" type="number" name="year" value="{{ $filterYear }}" min="2020" max="2100" aria-label="Năm">
    </div>
    <div class="col-lg-1">
        <select class="form-select" name="quarter">
            <option value="">Quý</option>
            @for($q = 1; $q <= 4; $q++)
                <option value="{{ $q }}" @selected(request('quarter') == $q)>Q{{ $q }}</option>
            @endfor
        </select>
    </div>
    <div class="col-lg-1">
        <select class="form-select" name="month">
            <option value="">Tháng</option>
            @for($m = 1; $m <= 12; $m++)
                <option value="{{ $m }}" @selected(request('month') == $m)>T{{ $m }}</option>
            @endfor
        </select>
    </div>
    <div class="col-lg-1">
        <button class="btn btn-dark w-100" title="Lọc">
            <i class="bi bi-filter"></i>
        </button>
    </div>
</form>

<form id="bulk-tuition" method="POST" action="{{ route('language-tuition.discount.bulk-highest') }}" data-bulk-form="tuition" data-bulk-confirm="Áp dụng mức miễn giảm cao nhất cho các khoản thu đã chọn?" class="mb-3 d-flex flex-wrap align-items-center gap-2">
    @csrf
    @method('PATCH')
    <label class="me-2">
        <input class="form-check-input me-1" type="checkbox" data-bulk-all="tuition"> Chọn tất cả trang này
    </label>
    @if(auth()->user()->allowed('language_tuition', 'update'))
        <button class="btn btn-sm btn-outline-primary" data-bulk-submit disabled>
            <i class="bi bi-percent me-1"></i>Áp dụng mức cao nhất (<span data-bulk-count>0</span>)
        </button>
    @endif
</form>

<div class="card card-soft">
    <div class="table-responsive">
        <table class="table table-modern align-middle">
            <thead>
                <tr>
                    <th data-selection-column class="text-center" style="width:42px"></th>
                    <th>Mã khoản thu</th>
                    <th>Học viên</th>
                    <th>Khóa học / lớp</th>
                    <th>Giảm</th>
                    <th>Phải thu</th>
                    <th>Đã thu</th>
                    <th>Chuyển sang</th>
                    <th>Còn lại</th>
                    <th>Trạng thái</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    @php($remaining = $item->remainingAmount())
                    <tr>
                        <td data-selection-column class="text-center">
                            <input class="form-check-input" type="checkbox" name="ids[]" value="{{ $item->id }}" form="bulk-tuition" data-bulk-item="tuition">
                        </td>
                        <td>
                            <strong>{{ $item->code }}</strong>
                            <div class="small text-muted">{{ $item->created_at?->format('d/m/Y') }}</div>
                        </td>
                        <td>
                            <strong>{{ $item->student->name }}</strong>
                            <div class="small text-muted">{{ $item->student->code }}</div>
                        </td>
                        <td>
                            {{ $item->course->name }}
                            <div class="small text-muted">Lớp: {{ $item->languageClass?->code ?: 'Chưa xếp' }}</div>
                        </td>
                        <td>
                            <strong>{{ \App\Support\ValueFormatter::percentage($item->discount_percentage) }}%</strong>
                            <div class="small text-muted">{{ $item->discount?->name ?: 'Không miễn giảm' }}</div>
                        </td>
                        <td>{{ number_format($item->payable_amount) }}đ</td>
                        <td class="fw-semibold text-success">{{ number_format($item->paid_amount) }}đ</td>
                        <td class="fw-semibold text-primary">{{ number_format($item->credit_amount) }}đ</td>
                        <td class="fw-semibold {{ $remaining > 0 ? 'text-danger' : 'text-muted' }}">{{ number_format($remaining) }}đ</td>
                        <td>
                            <span class="badge-soft {{ in_array($item->status, ['paid', 'transferred']) ? 'badge-success' : (in_array($item->status, ['partial', 'pending_receipt']) ? 'badge-warning' : 'badge-danger') }}">
                                {{ $labels[$item->status] ?? $item->status }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-primary text-nowrap" href="{{ route('language-tuition.show', $item) }}">
                                <i class="bi bi-eye me-1"></i>Chi tiết
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11">
                            <div class="empty-state">Không có khoản thu trong kỳ đã chọn.</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white border-0">{{ $items->links() }}</div>
</div>
@endsection
