@extends('layouts.app')
@section('title','Chuyển lớp học viên')
@section('header','Quản lý lớp học')
@section('content')
@php
    $sourcePayable=(float)($sourceCharge?->payable_amount??max(0,(float)$enrollment->tuition-(float)$enrollment->discount));
    $sourcePaid=(float)($sourceCharge?->paid_amount??0);
    $expectedSessions=max(1,(int)$languageClass->expected_sessions);
    $defaultSessions=min((int)$languageClass->completed_sessions,$expectedSessions);
@endphp
<div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
    <div><h1 class="page-title">Chuyển lớp cho {{$enrollment->student->name}}</h1><div class="page-subtitle">{{$languageClass->code}} – {{$languageClass->name}}</div></div>
    <a class="btn btn-light" href="{{route('language-classes.edit',$languageClass)}}"><i class="bi bi-arrow-left me-2"></i>Quay lại lớp</a>
</div>

<div class="row g-4">
    <div class="col-xl-4">
        <div class="card card-soft h-100"><div class="card-header bg-white p-4"><h5 class="mb-0">Quyết toán lớp cũ</h5></div><div class="card-body p-4">
            <div class="d-flex justify-content-between py-2 border-bottom"><span>Học phí lớp cũ</span><strong>{{number_format($sourcePayable)}}đ</strong></div>
            <div class="d-flex justify-content-between py-2 border-bottom"><span>Tiền học phí đã thu</span><strong class="text-success">{{number_format($sourcePaid)}}đ</strong></div>
            <div class="d-flex justify-content-between py-2 border-bottom"><span>Số tiết của khóa</span><strong>{{$expectedSessions}}</strong></div>
            <div class="alert alert-info mt-3 mb-0"><i class="bi bi-info-circle me-2"></i>Tiền sách không được chuyển. Hệ thống chỉ chuyển phần học phí đã đóng còn lại sau khi trừ giá trị số tiết đã học.</div>
        </div></div>
    </div>
    <div class="col-xl-8">
        <div class="card card-soft"><div class="card-header bg-white p-4"><h5 class="mb-0">Thông tin chuyển lớp</h5></div><div class="card-body p-4">
            <form method="POST" action="{{route('language-classes.enrollments.transfer.store',[$languageClass,$enrollment])}}" data-confirm="Xác nhận chuyển lớp và quyết toán học phí?">@csrf
                <div class="mb-3"><label class="form-label">Lớp mới</label><select class="form-select" name="to_language_class_id" required><option value="">Chọn lớp mới</option>@foreach($targetClasses as $class)<option value="{{$class->id}}" @selected(old('to_language_class_id')==$class->id)>{{$class->code}} – {{$class->name}} · {{number_format((float)($class->default_tuition?:$class->course?->tuition))}}đ · {{$class->studying_count}}/{{$class->max_students}} học viên</option>@endforeach</select>@if($targetClasses->isEmpty())<div class="form-text text-danger">Hiện không có lớp phù hợp còn chỗ trống.</div>@endif</div>
                <div class="row g-3"><div class="col-md-6"><label class="form-label">Ngày chuyển lớp</label><input class="form-control" type="date" name="effective_date" value="{{old('effective_date',now()->format('Y-m-d'))}}" min="{{$enrollment->enrolled_at->format('Y-m-d')}}" required></div><div class="col-md-6"><label class="form-label">Số tiết đã học ở lớp cũ</label><input class="form-control" type="number" name="sessions_used" value="{{old('sessions_used',$defaultSessions)}}" min="0" max="{{$expectedSessions}}" required><div class="form-text">Dùng để tính phần học phí đã sử dụng.</div></div></div>
                <div class="mt-3"><label class="form-label">Ghi chú</label><textarea class="form-control" name="note" rows="3" maxlength="2000" placeholder="Lý do chuyển lớp, thỏa thuận với phụ huynh...">{{old('note')}}</textarea></div>
                <button class="btn btn-primary mt-4" {{$targetClasses->isEmpty()?'disabled':''}}><i class="bi bi-arrow-left-right me-2"></i>Chuyển lớp và quyết toán</button>
            </form>
        </div></div>
    </div>
</div>
@endsection
