@extends('layouts.app')
@section('title','Học viên') @section('header','Quản lý học viên')
@section('content')
@php($labels=['new'=>'Mới đăng ký','placement_test'=>'Chờ kiểm tra','waiting_class'=>'Chờ xếp lớp','studying'=>'Đang học','paused'=>'Tạm nghỉ','reserved'=>'Bảo lưu','completed'=>'Hoàn thành','dropped'=>'Thôi học'])
<div class="d-flex justify-content-between mb-4">
    <div><h1 class="page-title">Học viên</h1><div class="page-subtitle">Hồ sơ, khóa học trung tâm, xếp lớp và tiến độ học tập.</div></div>
    <div class="d-flex gap-2">@if(auth()->user()->allowed('language_tuition'))<a class="btn btn-outline-success" href="{{route('language-tuition.index')}}"><i class="bi bi-cash-coin me-2"></i>Thu học phí</a>@endif<a class="btn btn-primary" href="{{route('language-students.create')}}"><i class="bi bi-plus-circle me-2"></i>Thêm học viên</a></div>
</div>
<form class="filter-panel row g-3 mb-4"><div class="col-md-5"><input class="form-control" name="q" value="{{request('q')}}" placeholder="Tìm mã, tên, điện thoại"></div><div class="col-md-3"><select class="form-select" name="course"><option value="">Mọi khóa học</option>@foreach($courses as $course)<option value="{{$course->id}}" @selected(request('course')==$course->id)>{{$course->name}}</option>@endforeach</select></div><div class="col-md-2"><select class="form-select" name="status"><option value="">Mọi trạng thái</option>@foreach($labels as $key=>$label)<option value="{{$key}}" @selected(request('status')===$key)>{{$label}}</option>@endforeach</select></div><div class="col-md-2"><button class="btn btn-dark w-100">Lọc</button></div></form>
<div class="card card-soft"><div class="table-responsive"><table class="table table-modern"><thead><tr><th>Học viên</th><th>Khóa học / miễn giảm</th><th>Lớp hiện tại</th><th>Ngày nhập học</th><th>Phụ huynh</th><th>Trạng thái</th><th></th></tr></thead><tbody>
@forelse($items as $item)
@php($current=$item->enrollments->where('status','studying')->sortByDesc('enrolled_at')->first() ?: $item->enrollments->sortByDesc('enrolled_at')->first())
<tr><td><strong>{{$item->name}}</strong><div class="small text-muted">{{$item->code}} · {{$item->phone?:'—'}}</div></td><td>{{$item->course?->name?:'Chưa chọn'}}<div class="small text-muted">{{$item->discountPolicy?->name?:'Không miễn giảm'}}</div></td><td>{{$current?->languageClass?->code?:'Chưa xếp lớp'}}<div class="small text-muted">{{$current?->languageClass?->program?->name}} {{$current?->languageClass?->level?->name}}</div></td><td>{{$item->official_enrollment_date?->format('d/m/Y')?:'—'}}</td><td>@foreach($item->guardians->take(2) as $guardian)<div>{{$guardian->name}} <small class="text-muted">· {{$guardian->phone}}</small></div>@endforeach</td><td><span class="badge-soft badge-info">{{$labels[$item->status]??$item->status}}</span></td><td class="text-end text-nowrap">
    @if(auth()->user()->allowed('language_tuition','create'))<a class="btn btn-sm btn-outline-success" href="{{route('language-tuition.create',['student'=>$item->id,'course'=>$item->language_course_id,'class'=>$current?->language_class_id])}}" title="Lập phiếu thu" aria-label="Lập phiếu thu"><i class="bi bi-receipt"></i></a>@endif
    <a class="btn btn-sm btn-outline-primary" href="{{route('language-students.edit',$item)}}" title="Sửa học viên" aria-label="Sửa học viên"><i class="bi bi-pencil"></i></a>
    <form class="d-inline" method="POST" action="{{route('language-students.destroy',$item)}}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Xóa học viên" aria-label="Xóa học viên" data-confirm="Xóa học viên này?"><i class="bi bi-trash"></i></button></form>
</td></tr>
@empty<tr><td colspan="7"><div class="empty-state">Chưa có học viên.</div></td></tr>@endforelse
</tbody></table></div><div class="card-footer bg-white border-0">{{$items->links()}}</div></div>
@endsection
