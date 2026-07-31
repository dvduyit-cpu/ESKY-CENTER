@extends('layouts.app')
@section('title',$item->exists?'Sửa lớp':'Tạo lớp') @section('header','Trung tâm Ngoại ngữ và Tin học')
@section('content')
@php($labels=['planned'=>'Dự kiến mở','recruiting'=>'Đang tuyển sinh','upcoming'=>'Sắp khai giảng','active'=>'Đang hoạt động','paused'=>'Tạm dừng','completed'=>'Đã kết thúc','cancelled'=>'Đã hủy'])
@php($canRegistrar=auth()->user()->isRegistrar())
<div class="d-flex justify-content-between mb-4"><div><h1 class="page-title">{{$item->exists?'Cập nhật':'Tạo'}} lớp học</h1><div class="page-subtitle">Chương trình, cấp độ, số buổi và học phí được lấy tự động từ khóa học.</div></div><a class="btn btn-light" href="{{route('language-classes.index')}}">Quay lại</a></div>
<div class="card card-soft mb-4"><div class="card-body p-4"><form method="POST" action="{{$item->exists?route('language-classes.update',$item):route('language-classes.store')}}">@csrf @if($item->exists)@method('PUT')@endif
<div class="row g-3">
<div class="col-md-3"><label class="form-label">Mã lớp</label><input class="form-control" name="code" value="{{old('code',$item->code)}}" required></div>
<div class="col-md-5"><label class="form-label">Tên lớp</label><input class="form-control" name="name" value="{{old('name',$item->name)}}" required></div>
<div class="col-md-4"><label class="form-label">Khóa học</label><select class="form-select" name="language_course_id" required><option value="">Chọn khóa học</option>@foreach($courses as $course)<option value="{{$course->id}}" @selected(old('language_course_id',$item->language_course_id)==$course->id)>{{$course->name}} · {{$course->program?->name}} · {{$course->level?->name}} · {{number_format($course->tuition)}}đ</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Giáo viên</label><select class="form-select" name="teacher_user_id"><option value="">Chưa phân công</option>@foreach($teachers as $u)<option value="{{$u->id}}" @selected(old('teacher_user_id',$item->teacher_user_id)==$u->id)>{{$u->name}}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Phòng</label><input class="form-control" name="room" value="{{old('room',$item->room)}}"></div>
<div class="col-md-4"><label class="form-label">Trạng thái</label><select class="form-select" name="status">@foreach($labels as $k=>$v)<option value="{{$k}}" @selected(old('status',$item->status?:'recruiting')===$k)>{{$v}}</option>@endforeach</select></div>
<div class="col-md-3"><label class="form-label">Ngày khai giảng</label><input class="form-control" type="date" name="start_date" value="{{old('start_date',$item->start_date?->format('Y-m-d'))}}"></div>
<div class="col-md-3"><label class="form-label">Dự kiến kết thúc</label><input class="form-control" type="date" name="expected_end_date" value="{{old('expected_end_date',$item->expected_end_date?->format('Y-m-d'))}}"></div>
<div class="col-md-2"><label class="form-label">Sĩ số tối đa</label><input class="form-control" type="number" name="max_students" value="{{old('max_students',$item->max_students?:20)}}" required></div>
<div class="col-md-4"><label class="form-label">Lịch học</label><input class="form-control" name="schedule_note" value="{{old('schedule_note',$item->schedule_note)}}"></div>
<div class="col-12"><label class="form-label">Ghi chú</label><textarea class="form-control" name="note">{{old('note',$item->note)}}</textarea></div>
</div><button class="btn btn-primary mt-4"><i class="bi bi-save me-2"></i>Lưu lớp học</button></form></div></div>
@if($item->exists)
<div class="card card-soft"><div class="card-body p-4">
    <h5>Danh sách học viên hiện tại ({{$item->enrollments->count()}}/{{$item->max_students}})</h5>
    @if($canRegistrar)<div class="d-flex flex-wrap justify-content-between align-items-center gap-3"><div class="text-muted">Tìm kiếm và chọn một hoặc nhiều học viên để thêm cùng lúc.</div><button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#classEnrollmentModal"><i class="bi bi-person-plus me-2"></i>Thêm học viên</button></div>@else<div class="alert alert-light border">Chỉ giáo vụ hoặc quản trị viên được xếp và chuyển học viên.</div>@endif
    <hr>
    @forelse($item->enrollments as $e)
        <div class="d-flex justify-content-between align-items-center border-bottom py-2" data-current-enrollment="{{$e->id}}">
            <span><strong>{{$e->student->name}}</strong> · {{$e->student->code}}</span>
            @if($canRegistrar)<div class="d-flex gap-2">
                <a class="btn btn-sm btn-outline-primary" href="{{route('language-classes.enrollments.transfer.create',[$item,$e])}}" title="Chuyển sang lớp khác"><i class="bi bi-arrow-left-right me-1"></i>Chuyển lớp</a>
                <form method="POST" action="{{route('language-classes.enrollments.destroy',[$item,$e])}}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Đưa học viên khỏi lớp" data-confirm="Đưa học viên này khỏi lớp? Khoản học phí chưa thu sẽ được xóa; chứng từ đã thu tiền vẫn được giữ để đối soát."><i class="bi bi-x-circle"></i></button></form>
            </div>@endif
        </div>
    @empty
        <div class="empty-state">Lớp chưa có học viên.</div>
    @endforelse
</div></div>
@if($canRegistrar)@include('language.classes._enroll-modal',['modalId'=>'classEnrollmentModal','enrollmentAction'=>route('language-classes.enrollments.store',$item)])@endif
@endif
@endsection
