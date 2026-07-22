@extends('layouts.app')
@section('title',$item->exists?'Sửa khóa học':'Thêm khóa học')
@section('header','Trung tâm ngoại ngữ')
@section('content')
<div class="d-flex justify-content-between mb-4"><div><h1 class="page-title">{{$item->exists?'Cập nhật':'Thêm'}} khóa học</h1><div class="page-subtitle">Khóa học bắt buộc thuộc một chương trình và một cấp độ.</div></div><a class="btn btn-light" href="{{route('language-center-courses.index')}}">Quay lại</a></div>
<div class="card card-soft form-card"><div class="card-header"><h5>Thông tin khóa học</h5></div><div class="card-body p-4">
<form method="POST" action="{{$item->exists?route('language-center-courses.update',$item):route('language-center-courses.store')}}">@csrf @if($item->exists)@method('PUT')@endif
<div class="row g-3">
<div class="col-md-6"><label class="form-label">Chương trình</label><select class="form-select" name="language_program_id" required><option value="">Chọn chương trình</option>@foreach($programs as $program)<option value="{{$program->id}}" @selected(old('language_program_id',$item->language_program_id)==$program->id)>{{$program->name}}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Cấp độ</label><select class="form-select" name="language_level_id" required><option value="">Chọn cấp độ thuộc chương trình</option>@foreach($programs as $program)@foreach($program->levels as $level)<option value="{{$level->id}}" @selected(old('language_level_id',$item->language_level_id)==$level->id)>{{$program->name}} – {{$level->name}}</option>@endforeach @endforeach</select></div>
<div class="col-md-6"><label class="form-label">Tên khóa học</label><input class="form-control" name="name" value="{{old('name',$item->name)}}" required></div>
<div class="col-md-6"><label class="form-label">Giáo trình</label><input class="form-control" name="textbook" value="{{old('textbook',$item->textbook)}}"></div>
<div class="col-md-4"><label class="form-label">Học phí</label><input class="form-control" type="number" name="tuition" value="{{old('tuition',$item->tuition?:0)}}" required></div>
<div class="col-md-4"><label class="form-label">Thời lượng (giờ)</label><input class="form-control" type="number" step="0.5" name="duration_hours" value="{{old('duration_hours',$item->duration_hours?:0)}}" required></div>
<div class="col-md-4"><label class="form-label">Số buổi</label><input class="form-control" type="number" name="sessions" value="{{old('sessions',$item->sessions?:0)}}" required></div>
<div class="col-12"><label class="form-label">Mô tả</label><textarea class="form-control" name="description">{{old('description',$item->description)}}</textarea></div>
<div class="col-12"><label><input type="checkbox" name="active" value="1" @checked(old('active',$item->exists?$item->active:true))> Đang hoạt động</label></div>
</div><div class="form-actions"><a class="btn btn-light" href="{{route('language-center-courses.index')}}">Hủy</a><button class="btn btn-primary">Lưu khóa học</button></div></form>
</div></div>
@endsection
