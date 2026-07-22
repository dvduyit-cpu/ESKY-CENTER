@extends('layouts.app')
@section('title','Nhập kết quả Excel') @section('header','Nhập kết quả chỉ tiêu')
@section('content')
@if(session('import_errors'))
<div class="alert alert-danger border-0 shadow-sm">
    <div class="d-flex justify-content-between align-items-center"><strong><i class="bi bi-exclamation-triangle-fill me-2"></i>Chi tiết {{ count(session('import_errors')) }} dòng nhập lỗi</strong><span class="small">Các dòng này chưa được lưu</span></div>
    <div class="mt-3 overflow-auto" style="max-height:320px"><ol class="mb-0">@foreach(session('import_errors') as $error)<li class="mb-1">{{ $error }}</li>@endforeach</ol></div>
</div>
@endif
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><h1 class="page-title">Các lần nhập kết quả</h1><div class="page-subtitle">Nhập theo tháng hoặc quý; hệ thống tự cộng dồn theo thành viên.</div></div><div class="d-flex gap-2"><a class="btn btn-outline-primary" href="{{ route('imports.records') }}"><i class="bi bi-table me-2"></i>Tổng dữ liệu đã nhập</a><a class="btn btn-outline-success" href="{{ route('imports.template') }}"><i class="bi bi-download me-2"></i>Tải file mẫu</a>@if(auth()->user()->allowed('imports','create'))<a class="btn btn-primary" href="{{ route('imports.create') }}"><i class="bi bi-cloud-upload me-2"></i>Nhập dữ liệu</a>@endif</div></div>
<form class="filter-panel row g-3 mb-4"><div class="col-md-4"><input class="form-control" type="number" name="year" value="{{ request('year') }}" placeholder="Năm"></div><div class="col-md-4"><select class="form-select" name="period_type"><option value="">Mọi loại kỳ</option><option value="month" @selected(request('period_type')==='month')>Theo tháng</option><option value="quarter" @selected(request('period_type')==='quarter')>Theo quý</option></select></div><div class="col-md-2"><button class="btn btn-dark w-100">Lọc</button></div></form>
<div class="card card-soft"><div class="table-responsive"><table class="table table-modern"><thead><tr><th>File</th><th>Kỳ dữ liệu</th><th>Kết quả</th><th>Doanh thu</th><th>Người nhập</th><th>Thời gian</th><th>Trạng thái</th></tr></thead><tbody>@forelse($batches as $b)<tr><td><strong>{{ $b->original_name }}</strong><div class="small text-muted">{{ substr($b->file_hash,0,12) }}...</div></td><td>@if($b->period_type==='month')Tháng {{ $b->month }}/{{ $b->year }}@else Quý {{ $b->quarter }}/{{ $b->year }}@endif</td><td><span class="text-success fw-bold">{{ $b->success_rows }}</span> thành công / <span class="text-danger">{{ $b->error_rows }}</span> lỗi<div class="small text-muted">Tổng {{ $b->total_rows }} dòng</div></td><td>{{ number_format($b->total_revenue) }}đ</td><td>{{ $b->user?->name }}</td><td>{{ $b->created_at?->format('d/m/Y H:i') }}</td><td>@if($b->status==='completed')<span class="badge-soft badge-success">Hoàn tất</span>@elseif($b->status==='failed')<span class="badge-soft badge-danger">Thất bại</span>@else<span class="badge-soft badge-warning">Đang xử lý</span>@endif</td></tr>@empty<tr><td colspan="7"><div class="empty-state">Chưa có lần nhập dữ liệu.</div></td></tr>@endforelse</tbody></table></div>@if($batches->hasPages())<div class="card-footer bg-white border-0 p-3">{{ $batches->links() }}</div>@endif</div>
@php($batchesWithErrors = $batches->filter(fn($batch) => !empty($batch->error_details)))
@if($batchesWithErrors->isNotEmpty())
<div class="card card-soft mt-4"><div class="card-header bg-white border-0 p-4"><h5 class="mb-0"><i class="bi bi-bug me-2 text-danger"></i>Chi tiết lỗi các lần nhập trên trang</h5></div><div class="card-body pt-0">
@foreach($batchesWithErrors as $batch)
<details class="border rounded-3 p-3 mb-2"><summary class="fw-semibold">{{ $batch->original_name }} — {{ count($batch->error_details) }} lỗi — {{ $batch->created_at?->format('d/m/Y H:i') }}</summary><ol class="mt-3 mb-0">@foreach($batch->error_details as $error)<li class="mb-1">{{ $error }}</li>@endforeach</ol></details>
@endforeach
</div></div>
@endif
@endsection
