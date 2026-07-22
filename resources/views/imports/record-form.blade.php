@extends('layouts.app')
@section('title',$record->exists?'Sửa dữ liệu KPI':'Nhập dữ liệu KPI')
@section('header','Dữ liệu KPI')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="page-title">{{ $record->exists?'Cập nhật dữ liệu KPI':'Nhập dữ liệu KPI thủ công' }}</h1><div class="page-subtitle">Dữ liệu nhập tay được cộng dồn cùng dữ liệu nhập từ Excel.</div></div><a class="btn btn-light" href="{{ route('imports.records') }}">Quay lại</a></div>
<div class="card card-soft"><div class="card-body p-4"><form method="POST" action="{{ $record->exists?route('imports.records.update',$record):route('imports.records.store') }}">@csrf @if($record->exists)@method('PUT')@endif
<div class="row g-3">
    <div class="col-md-6"><label class="form-label">Họ tên học viên</label><input class="form-control" name="student_name" value="{{ old('student_name',$record->student_name) }}" required></div>
    <div class="col-md-6"><label class="form-label">Nhân sự ghi nhận</label><select class="form-select" name="personnel_id" required><option value="">Chọn nhân sự</option>@foreach($personnels as $personnel)<option value="{{ $personnel->id }}" @selected((string)old('personnel_id',$record->personnel_id)===(string)$personnel->id)>{{ $personnel->name }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Cộng tác viên</label><select class="form-select" name="collaborator_id"><option value="">Không có</option>@foreach($collaborators as $collaborator)<option value="{{ $collaborator->id }}" @selected((string)old('collaborator_id',$record->collaborator_id)===(string)$collaborator->id)>{{ $collaborator->name }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Khóa học</label><select class="form-select" name="course_id" required><option value="">Chọn khóa học</option>@foreach($courses as $course)<option value="{{ $course->id }}" @selected((string)old('course_id',$record->course_id)===(string)$course->id)>{{ $course->name }} — {{ $course->conversionText() }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label">Lớp đăng ký</label><input class="form-control" name="class_name" value="{{ old('class_name',$record->class_name) }}"></div>
    <div class="col-md-4"><label class="form-label">Thực thu</label><input class="form-control" type="number" min="0" step="0.01" name="revenue" value="{{ old('revenue',$record->revenue?:0) }}"></div>
    <div class="col-md-4"><label class="form-label">Số phiếu thu</label><input class="form-control" name="receipt_no" value="{{ old('receipt_no',$record->receipt_no) }}"></div>
    <div class="col-md-4"><label class="form-label">Ngày ghi nhận</label><input class="form-control" type="date" name="record_date" value="{{ old('record_date',$record->record_date?->format('Y-m-d')?:now()->format('Y-m-d')) }}" required></div>
    <div class="col-md-4"><label class="form-label">Số lượng</label><input class="form-control" type="number" min="0" step="0.01" name="raw_quantity" value="{{ old('raw_quantity',$record->raw_quantity?:1) }}" required></div>
    <div class="col-12"><label class="form-label">Ghi chú</label><textarea class="form-control" name="note" rows="3">{{ old('note',$record->note) }}</textarea></div>
</div><div class="mt-4"><button class="btn btn-primary"><i class="bi bi-save me-2"></i>{{ $record->exists?'Lưu thay đổi':'Thêm dữ liệu' }}</button></div>
</form></div></div>
@endsection
