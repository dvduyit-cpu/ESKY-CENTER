@extends('layouts.app')
@section('title','Học viên')
@section('header','Quản lý học viên')
@section('content')
@php($labels=['new'=>'Mới đăng ký','placement_test'=>'Chờ kiểm tra','waiting_class'=>'Chờ xếp lớp','studying'=>'Đang học','paused'=>'Tạm nghỉ','reserved'=>'Bảo lưu','completed'=>'Hoàn thành','dropped'=>'Thôi học'])

<div class="d-flex flex-wrap justify-content-between gap-3 mb-4 student-page-header">
    <div>
        <h1 class="page-title">Quản lý học viên</h1>
        <div class="page-subtitle">Theo dõi hồ sơ, khóa học, lớp hiện tại và tiến độ học tập.</div>
    </div>
    <div class="d-flex flex-wrap gap-2 student-header-actions">
        @if(auth()->user()->allowed('language_tuition'))
            <a class="btn btn-outline-success" href="{{route('language-tuition.index')}}"><i class="bi bi-cash-coin me-2"></i>Thu học phí</a>
        @endif
        <a class="btn btn-primary" href="{{route('language-students.create')}}"><i class="bi bi-plus-circle me-2"></i>Thêm học viên</a>
    </div>
</div>

<form class="filter-panel row g-3 mb-4 student-filter">
    <div class="col-xl-5 col-md-6"><label class="form-label">Tìm kiếm</label><div class="input-group"><span class="input-group-text bg-white"><i class="bi bi-search"></i></span><input class="form-control" name="q" value="{{request('q')}}" placeholder="Mã, họ tên hoặc số điện thoại"></div></div>
    <div class="col-xl-3 col-md-6"><label class="form-label">Khóa học</label><select class="form-select" name="course"><option value="">Tất cả khóa học</option>@foreach($courses as $course)<option value="{{$course->id}}" @selected(request('course')==$course->id)>{{$course->name}}</option>@endforeach</select></div>
    <div class="col-xl-2 col-md-6"><label class="form-label">Trạng thái</label><select class="form-select" name="status"><option value="">Tất cả trạng thái</option>@foreach($labels as $key=>$label)<option value="{{$key}}" @selected(request('status')===$key)>{{$label}}</option>@endforeach</select></div>
    <div class="col-xl-2 col-md-6 d-flex align-items-end"><button class="btn btn-dark w-100"><i class="bi bi-funnel me-2"></i>Lọc dữ liệu</button></div>
</form>

<div class="card card-soft student-list-card">
    <div class="table-responsive d-none d-lg-block">
        <table class="table table-modern align-middle mb-0">
            <thead><tr><th>Học viên</th><th>Khóa học / miễn giảm</th><th>Lớp hiện tại</th><th>Ngày nhập học</th><th>Phụ huynh</th><th>Học phí</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr></thead>
            <tbody>
            @forelse($items as $item)
                @php($current=$item->enrollments->where('status','studying')->sortByDesc('enrolled_at')->first() ?: $item->enrollments->sortByDesc('enrolled_at')->first())
                <tr>
                    <td><div class="student-identity"><span class="student-avatar">{{mb_strtoupper(mb_substr($item->name,0,1))}}</span><div><strong>{{$item->name}}</strong><div class="small text-muted">{{$item->code}} · {{$item->phone?:'Chưa có SĐT'}}</div></div></div></td>
                    <td><strong>{{$item->course?->name?:'Chưa chọn khóa học'}}</strong><div class="small text-muted">{{$item->discountPolicy?->name?:'Không miễn giảm'}}</div></td>
                    <td><strong>{{$current?->languageClass?->code?:'Chưa xếp lớp'}}</strong><div class="small text-muted">{{trim(($current?->languageClass?->program?->name ?? '').' '.($current?->languageClass?->level?->name ?? '')) ?: '—'}}</div></td>
                    <td class="text-nowrap">{{$item->official_enrollment_date?->format('d/m/Y')?:'—'}}</td>
                    <td>@forelse($item->guardians->take(2) as $guardian)<div class="guardian-line">{{$guardian->name}}<small>{{$guardian->phone}}</small></div>@empty<span class="text-muted">Chưa cập nhật</span>@endforelse</td>
                    @php($tuitionDue=max(0,(float)$item->tuition_charges_sum_payable_amount-(float)$item->tuition_charges_sum_paid_amount))
                    <td>@if((float)$item->tuition_charges_sum_payable_amount<=0)<span class="badge-soft badge-gray">Chưa lập</span>@elseif($tuitionDue<=0)<span class="badge-soft badge-success">Đã đóng đủ</span><div class="small text-success mt-1">{{number_format($item->tuition_charges_sum_paid_amount)}}đ</div>@else<span class="badge-soft badge-warning">Còn {{number_format($tuitionDue)}}đ</span><div class="small text-muted mt-1">Đã đóng {{number_format($item->tuition_charges_sum_paid_amount)}}đ</div>@endif</td>
                    <td><span class="badge-soft badge-info">{{$labels[$item->status]??$item->status}}</span></td>
                    <td class="text-end text-nowrap student-actions">
                        <a class="btn btn-sm btn-outline-dark" href="{{route('language-students.show',$item)}}" title="Xem hồ sơ và điểm" aria-label="Xem hồ sơ và điểm"><i class="bi bi-eye"></i></a>
                        @if(auth()->user()->allowed('language_tuition','create'))<a class="btn btn-sm btn-outline-success" href="{{route('language-tuition.create',['student'=>$item->id,'course'=>$item->language_course_id,'class'=>$current?->language_class_id])}}" title="Lập phiếu thu" aria-label="Lập phiếu thu"><i class="bi bi-receipt"></i></a>@endif
                        <a class="btn btn-sm btn-outline-primary" href="{{route('language-students.edit',$item)}}" title="Sửa học viên" aria-label="Sửa học viên"><i class="bi bi-pencil"></i></a>
                        <form class="d-inline" method="POST" action="{{route('language-students.destroy',$item)}}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Xóa học viên" aria-label="Xóa học viên" data-confirm="Xóa học viên này?"><i class="bi bi-trash"></i></button></form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8"><div class="empty-state">Chưa có học viên phù hợp với bộ lọc.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="student-mobile-list d-lg-none">
        @forelse($items as $item)
            @php($current=$item->enrollments->where('status','studying')->sortByDesc('enrolled_at')->first() ?: $item->enrollments->sortByDesc('enrolled_at')->first())
            <article class="student-mobile-card">
                <div class="student-mobile-head"><div class="student-identity"><span class="student-avatar">{{mb_strtoupper(mb_substr($item->name,0,1))}}</span><div><strong>{{$item->name}}</strong><div class="small text-muted">{{$item->code}} · {{$item->phone?:'Chưa có SĐT'}}</div></div></div><span class="badge-soft badge-info">{{$labels[$item->status]??$item->status}}</span></div>
                @php($tuitionDue=max(0,(float)$item->tuition_charges_sum_payable_amount-(float)$item->tuition_charges_sum_paid_amount))
                <div class="student-mobile-grid"><div><span>Khóa học</span><strong>{{$item->course?->name?:'Chưa chọn'}}</strong></div><div><span>Lớp hiện tại</span><strong>{{$current?->languageClass?->code?:'Chưa xếp lớp'}}</strong></div><div><span>Ngày nhập học</span><strong>{{$item->official_enrollment_date?->format('d/m/Y')?:'—'}}</strong></div><div><span>Học phí</span><strong>{{(float)$item->tuition_charges_sum_payable_amount<=0?'Chưa lập':($tuitionDue<=0?'Đã đóng đủ':'Còn '.number_format($tuitionDue).'đ')}}</strong></div></div>
                <div class="student-mobile-actions">
                    <a class="btn btn-outline-dark" href="{{route('language-students.show',$item)}}" title="Xem hồ sơ và điểm"><i class="bi bi-eye"></i></a>
                    @if(auth()->user()->allowed('language_tuition','create'))<a class="btn btn-outline-success" href="{{route('language-tuition.create',['student'=>$item->id,'course'=>$item->language_course_id,'class'=>$current?->language_class_id])}}" title="Lập phiếu thu"><i class="bi bi-receipt"></i></a>@endif
                    <a class="btn btn-outline-primary" href="{{route('language-students.edit',$item)}}" title="Sửa học viên"><i class="bi bi-pencil"></i></a>
                    <form method="POST" action="{{route('language-students.destroy',$item)}}">@csrf @method('DELETE')<button class="btn btn-outline-danger" title="Xóa học viên" data-confirm="Xóa học viên này?"><i class="bi bi-trash"></i></button></form>
                </div>
            </article>
        @empty
            <div class="empty-state">Chưa có học viên phù hợp với bộ lọc.</div>
        @endforelse
    </div>
    <div class="card-footer bg-white border-0">{{$items->links()}}</div>
</div>
@endsection
