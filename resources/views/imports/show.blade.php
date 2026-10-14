@extends('layouts.app')
@section('title','Chi tiết lần nhập kết quả')
@section('header','Chi tiết lần nhập kết quả')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><h1 class="page-title">{{ $batch->original_name }}</h1><div class="page-subtitle">@if($batch->period_type==='month')Tháng {{ $batch->month }}/{{ $batch->year }}@else Quý {{ $batch->quarter }}/{{ $batch->year }}@endif · {{ $batch->user?->name }} · {{ $batch->created_at?->format('d/m/Y H:i') }}</div></div>
    <div class="d-flex flex-wrap gap-2"><a class="btn btn-light" href="{{ route('imports.index') }}">Quay lại các lần nhập</a><a class="btn btn-outline-success" href="{{ route('imports.download',$batch) }}">Tải file gốc</a><a class="btn btn-primary" href="{{ route('imports.records',['batch_id'=>$batch->id]) }}">Xem dữ liệu đang sử dụng ({{ $currentCount }})</a></div>
</div>
<div class="row g-3 mb-4">
    @foreach(['Tổng dòng đã xử lý'=>$batch->total_rows,'Dòng nhập thành công'=>$batch->success_rows,'Dòng lỗi, chưa lưu'=>$batch->error_rows,'Dòng hiện đang sử dụng'=>$currentCount] as $label=>$count)
    <div class="col-sm-6 col-xl-3"><div class="card card-soft"><div class="card-body"><div class="text-muted">{{ $label }}</div><strong class="fs-3">{{ $count }}</strong></div></div></div>
    @endforeach
</div>
<div class="alert alert-info">Bảng bên dưới hiển thị nội dung file gốc của lần nhập này, bao gồm cả dòng lỗi. Khi nhập lại, dòng trùng lấy kết quả mới; dữ liệu hiện đang sử dụng có thể khác file gốc hoặc đã chuyển sang lần nhập mới hơn. Số dòng thành công tính cả dòng thêm mới và dòng ghi đè.</div>
@if($batch->error_details)
<details class="alert alert-danger"><summary>Chi tiết {{ count($batch->error_details) }} lỗi nhập</summary><ol class="mt-3 mb-0">@foreach($batch->error_details as $error)<li>{{ $error }}</li>@endforeach</ol></details>
@endif
@if($fileError)<div class="alert alert-warning">{{ $fileError }}</div>@else
<div class="card card-soft"><div class="card-header bg-white p-3"><strong>Danh sách trong file đã tải lên</strong><span class="text-muted ms-2">{{ $rows->total() }} dòng có dữ liệu</span></div><div class="table-responsive"><table class="table table-modern align-middle text-nowrap">
<thead><tr><th>Dòng Excel</th>@foreach($headers as $header)<th>{{ $header ?: 'Cột '.$loop->iteration }}</th>@endforeach</tr></thead>
<tbody>@forelse($rows as $row)<tr><td>{{ $row['number'] }}</td>@foreach($row['values'] as $value)<td>{{ $value ?? '—' }}</td>@endforeach</tr>@empty<tr><td colspan="{{ count($headers)+1 }}"><div class="empty-state">File không có dữ liệu.</div></td></tr>@endforelse</tbody>
</table></div><div class="card-footer bg-white p-3">{{ $rows->links() }}</div></div>
@endif
@endsection
