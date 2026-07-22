@extends('layouts.app')
@section('title','Tổng dữ liệu chỉ tiêu đã nhập')
@section('header','Dữ liệu chỉ tiêu đã nhập')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div><h1 class="page-title">Tổng dữ liệu chỉ tiêu đã nhập</h1><div class="page-subtitle">Danh sách chi tiết tất cả kết quả đã ghi nhận vào hệ thống.</div></div>
    <div class="d-flex gap-2"><a class="btn btn-light" href="{{ route('imports.index') }}"><i class="bi bi-arrow-left me-2"></i>Các lần nhập</a>@if(auth()->user()->allowed('imports','create'))<a class="btn btn-primary" href="{{ route('imports.records.create') }}"><i class="bi bi-plus-circle me-2"></i>Nhập thủ công</a>@endif</div>
</div>
<form class="filter-panel row g-3 mb-4">
    <div class="col-xl-3"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Học viên, lớp hoặc số phiếu thu..."></div>
    <div class="col-6 col-xl-2"><input class="form-control" type="number" name="year" min="2020" max="2100" value="{{ request('year') }}" placeholder="Năm"></div>
    <div class="col-6 col-xl-1"><select class="form-select" name="month"><option value="">Tháng</option>@for($month=1;$month<=12;$month++)<option value="{{ $month }}" @selected((string)request('month')===(string)$month)>T{{ $month }}</option>@endfor</select></div>
    <div class="col-xl-2"><select class="form-select" name="personnel_id"><option value="">Mọi nhân sự</option>@foreach($personnels as $personnel)<option value="{{ $personnel->id }}" @selected((string)request('personnel_id')===(string)$personnel->id)>{{ $personnel->name }}</option>@endforeach</select></div>
    <div class="col-xl-2"><select class="form-select" name="course_id"><option value="">Mọi khóa học</option>@foreach($courses as $course)<option value="{{ $course->id }}" @selected((string)request('course_id')===(string)$course->id)>{{ $course->name }}</option>@endforeach</select></div>
    <div class="col-xl-2 d-flex gap-2"><button class="btn btn-dark flex-grow-1"><i class="bi bi-search me-1"></i>Lọc</button><a class="btn btn-light" href="{{ route('imports.records') }}" title="Xóa bộ lọc"><i class="bi bi-x-lg"></i></a></div>
</form>
@if(auth()->user()->allowed('imports','delete'))
<form id="bulk-kpi-records" method="POST" action="{{ route('imports.records.bulk-destroy') }}" data-bulk-form="kpi-records" data-bulk-confirm="Xóa các dòng dữ liệu KPI đã chọn?" class="mb-3 d-flex flex-wrap align-items-center gap-2">
    @csrf @method('DELETE')
    <label class="me-2"><input class="form-check-input me-1" type="checkbox" data-bulk-all="kpi-records"> Chọn tất cả trang này</label>
    <select class="form-select form-select-sm w-auto" name="delete_type"><option value="soft">Xóa mềm</option>@if(auth()->user()->isAdmin())<option value="force">Xóa vĩnh viễn</option>@endif</select>
    <button class="btn btn-sm btn-outline-danger" data-bulk-submit disabled><i class="bi bi-trash me-1"></i>Xóa đã chọn (<span data-bulk-count>0</span>)</button>
</form>
@endif
<div class="card card-soft"><div class="table-responsive"><table class="table table-modern align-middle text-nowrap">
    <thead><tr><th>STT</th><th>HỌ TÊN HỌC VIÊN</th><th>NHÂN SỰ GHI NHẬN</th><th>CỘNG TÁC VIÊN</th><th>KHÓA HỌC</th><th>LỚP ĐĂNG KÝ</th><th class="text-end">THỰC THU</th><th>SỐ PHIẾU THU</th><th>NGÀY GHI NHẬN</th><th class="text-end">SỐ LƯỢNG</th><th>GHI CHÚ</th><th class="text-end">THAO TÁC</th></tr></thead>
    <tbody>@forelse($records as $record)<tr>
        <td>@if(auth()->user()->allowed('imports','delete'))<input class="form-check-input me-2" type="checkbox" name="ids[]" value="{{ $record->id }}" form="bulk-kpi-records" data-bulk-item="kpi-records">@endif{{ $records->firstItem() + $loop->index }}</td>
        <td class="fw-semibold">{{ $record->student_name }}</td>
        <td>{{ $record->personnel?->name ?: '—' }}</td>
        <td>{{ $record->collaborator?->name ?: '—' }}</td>
        <td>{{ $record->course?->name ?: '—' }}</td>
        <td>{{ $record->class_name ?: '—' }}</td>
        <td class="text-end fw-semibold">{{ number_format($record->revenue) }}đ</td>
        <td>{{ $record->receipt_no ?: '—' }}</td>
        <td>{{ $record->record_date?->format('d/m/Y') }}</td>
        <td class="text-end">{{ number_format($record->raw_quantity,2) }}</td>
        <td class="text-wrap" style="min-width:220px">{{ $record->note ?: '—' }}</td>
        <td class="text-end">@if(auth()->user()->allowed('imports','update'))<a class="btn btn-sm btn-outline-primary" href="{{ route('imports.records.edit',$record) }}"><i class="bi bi-pencil"></i></a>@endif @if(auth()->user()->allowed('imports','delete'))<form class="d-inline" method="POST" action="{{ route('imports.records.destroy',$record) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" data-confirm="Xóa dòng dữ liệu KPI này?"><i class="bi bi-trash"></i></button></form>@endif</td>
    </tr>@empty<tr><td colspan="12"><div class="empty-state">Chưa có dữ liệu chỉ tiêu phù hợp.</div></td></tr>@endforelse</tbody>
</table></div>@if($records->hasPages())<div class="card-footer bg-white border-0 p-3">{{ $records->links() }}</div>@endif</div>
@endsection
