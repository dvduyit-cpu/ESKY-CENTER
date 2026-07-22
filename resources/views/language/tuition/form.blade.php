@extends('layouts.app')
@section('title','Lập khoản thu học phí') @section('header','Quản lý học viên')
@section('content')
<div class="d-flex justify-content-between mb-4"><div><h1 class="page-title">Lập khoản thu học phí</h1><div class="page-subtitle">Học viên, khóa học và lớp hiện tại được chọn sẵn khi mở từ danh sách Học viên.</div></div><a class="btn btn-light" href="{{route('language-tuition.index')}}">Quay lại</a></div>
<div class="card card-soft form-card"><div class="card-header"><h5>Thông tin khoản thu</h5></div><div class="card-body p-4"><form method="POST" action="{{route('language-tuition.store')}}">@csrf
<div class="row g-3">
    <div class="col-md-6"><label class="form-label">Học viên</label><select class="form-select" name="language_student_id" required><option value="">Chọn học viên</option>@foreach($students as $student)<option value="{{$student->id}}" @selected(old('language_student_id',$selectedStudent)==$student->id)>{{$student->code}} – {{$student->name}}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Khách hàng nguồn</label><select class="form-select" name="language_lead_id"><option value="">Không liên kết</option>@foreach($leads as $lead)<option value="{{$lead->id}}" @selected(old('language_lead_id',$selectedLead)==$lead->id)>{{$lead->code}} – {{$lead->name}}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Khóa học</label><select class="form-select" name="language_course_id" required><option value="">Chọn khóa học</option>@foreach($courses as $course)<option value="{{$course->id}}" @selected(old('language_course_id',$selectedCourse)==$course->id)>{{$course->name}} – {{number_format($course->tuition)}}đ</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Lớp học</label><select class="form-select" name="language_class_id"><option value="">Chưa xếp lớp</option>@foreach($classes as $class)<option value="{{$class->id}}" @selected(old('language_class_id',$selectedClass)==$class->id)>{{$class->code}} – {{$class->name}}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Chế độ miễn giảm</label><select class="form-select" name="language_discount_policy_id"><option value="">Không miễn giảm</option>@foreach($discounts as $discount)<option value="{{$discount->id}}">{{$discount->name}} – giảm {{$discount->percentage}}% ({{$discount->eligible_subject}})</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">Hạn đóng</label><input class="form-control" type="date" name="due_date" value="{{old('due_date')}}"></div>
    <div class="col-12"><label class="form-label">Ghi chú</label><textarea class="form-control" name="note">{{old('note')}}</textarea></div>
</div>
<div class="form-actions"><a class="btn btn-light" href="{{route('language-tuition.index')}}">Hủy</a><button class="btn btn-primary"><i class="bi bi-receipt me-2"></i>Lập khoản thu</button></div>
</form></div></div>
@endsection
