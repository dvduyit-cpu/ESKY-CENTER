@extends('layouts.app')

@section('title','Học viên giới thiệu · '.$item->name)
@section('header','Trung tâm ngoại ngữ')

@section('content')
@php($statusLabels=['new'=>'Mới tiếp nhận','contacted'=>'Đã liên hệ','consulting'=>'Đang tư vấn','placement_test'=>'Hẹn kiểm tra','waiting'=>'Chờ phản hồi','registered'=>'Đã đăng ký','not_interested'=>'Không quan tâm','follow_up'=>'Chăm sóc lại'])

<div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
    <div>
        <a class="small text-decoration-none" href="{{route('language-collaborators.index')}}"><i class="bi bi-arrow-left me-1"></i>Danh sách cộng tác viên</a>
        <h1 class="page-title mt-2">Học viên do {{$item->name}} giới thiệu</h1>
        <div class="page-subtitle">{{$item->code}} · {{$item->phone?:'Chưa có số điện thoại'}} · Tổng hợp nguồn học viên theo tháng, năm.</div>
    </div>
    <div class="d-flex align-items-start gap-2">
        @if(auth()->user()->allowed('language_collaborators','export'))
            <a class="btn btn-outline-success" href="{{route('language-collaborators.referrals.export',['languageCollaborator'=>$item]+$filters)}}"><i class="bi bi-file-earmark-excel me-2"></i>Xuất Excel</a>
        @endif
        <a class="btn btn-outline-primary" href="{{route('language-collaborators.edit',$item)}}"><i class="bi bi-pencil me-2"></i>Sửa CTV</a>
    </div>
</div>

<form class="filter-panel row g-3 align-items-end mb-4">
    <div class="col-lg-3 col-md-6"><label class="form-label">Tìm học viên</label><input class="form-control" name="q" value="{{$filters['q']}}" placeholder="Mã, họ tên hoặc số điện thoại"></div>
    <div class="col-lg-2 col-md-6"><label class="form-label">Trạng thái</label><select class="form-select" name="status"><option value="">Tất cả trạng thái</option>@foreach($statusLabels as $value=>$label)<option value="{{$value}}" @selected($filters['status']===$value)>{{$label}}</option>@endforeach</select></div>
    <div class="col-lg-2 col-md-6"><label class="form-label">Khóa học</label><select class="form-select" name="course"><option value="">Tất cả khóa học</option>@foreach($courses as $course)<option value="{{$course->id}}" @selected($filters['course']===$course->id)>{{$course->name}}</option>@endforeach</select></div>
    <div class="col-lg-2 col-md-4"><label class="form-label">Tháng</label><select class="form-select" name="month"><option value="">Cả năm</option>@for($month=1;$month<=12;$month++)<option value="{{$month}}" @selected($filters['month']===$month)>Tháng {{$month}}</option>@endfor</select></div>
    <div class="col-lg-2 col-md-4"><label class="form-label">Năm</label><select class="form-select" name="year">@foreach($years as $year)<option value="{{$year}}" @selected($filters['year']===$year)>{{$year}}</option>@endforeach</select></div>
    <div class="col-lg-1 col-md-4 d-flex gap-2"><button class="btn btn-primary flex-grow-1" title="Lọc"><i class="bi bi-funnel"></i></button><a class="btn btn-light" href="{{route('language-collaborators.show',$item)}}" title="Xóa bộ lọc"><i class="bi bi-arrow-counterclockwise"></i></a></div>
</form>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card card-soft h-100"><div class="card-body p-4"><div class="stat-label">Học viên được giới thiệu</div><div class="stat-value">{{number_format((int)$summary->referral_count)}}</div><div class="small text-muted">{{ $filters['month'] ? 'Tháng '.$filters['month'].'/'.$filters['year'] : 'Năm '.$filters['year'] }}</div></div></div></div>
    <div class="col-md-4"><div class="card card-soft h-100"><div class="card-body p-4"><div class="stat-label">Đã đăng ký</div><div class="stat-value text-success">{{number_format((int)$summary->registered_count)}}</div><div class="small text-muted">Khách hàng có trạng thái đã đăng ký</div></div></div></div>
    <div class="col-md-4"><div class="card card-soft h-100"><div class="card-body p-4"><div class="stat-label">Đã thành học viên chính thức</div><div class="stat-value text-primary">{{number_format((int)$summary->converted_count)}}</div><div class="small text-muted">Đã tạo hồ sơ học viên trong hệ thống</div></div></div></div>
</div>

@if(!$filters['month'])
<div class="card card-soft mb-4">
    <div class="card-header bg-white p-4 pb-0"><h5 class="mb-1">Tổng hợp theo tháng · {{$filters['year']}}</h5><div class="small text-muted">Số liệu theo ngày tiếp nhận học viên do CTV giới thiệu.</div></div>
    <div class="card-body p-4"><div class="table-responsive"><table class="table table-modern mb-0"><thead><tr><th>Tháng</th><th>Giới thiệu</th><th>Đã đăng ký</th><th>Đã thành học viên</th></tr></thead><tbody>@for($month=1;$month<=12;$month++)@php($monthData=$monthlySummary->get($month))<tr><td><strong>Tháng {{$month}}</strong></td><td>{{number_format((int)($monthData?->referral_count??0))}}</td><td>{{number_format((int)($monthData?->registered_count??0))}}</td><td>{{number_format((int)($monthData?->converted_count??0))}}</td></tr>@endfor</tbody></table></div></div>
</div>
@endif

<div class="card card-soft">
    <div class="card-header bg-white p-4 pb-0"><h5 class="mb-1">Danh sách học viên giới thiệu</h5><div class="small text-muted">{{number_format((int)$summary->referral_count)}} hồ sơ phù hợp bộ lọc.</div></div>
    <div class="card-body p-0"><div class="table-responsive"><table class="table table-modern mb-0"><thead><tr><th>Học viên</th><th>Ngày tiếp nhận</th><th>Liên hệ</th><th>Khóa học</th><th>Tư vấn viên</th><th>Trạng thái</th><th>Hồ sơ học viên</th></tr></thead><tbody>
    @forelse($items as $lead)
        <tr>
            <td><strong>{{$lead->name}}</strong><div class="small text-muted">{{$lead->code}}</div></td>
            <td>{{$lead->received_at?->format('d/m/Y')?:'—'}}</td>
            <td>{{$lead->phone}}<div class="small text-muted">{{$lead->email}}</div></td>
            <td>{{$lead->course?->name?:'—'}}</td>
            <td>{{$lead->consultant?->name?:'Chưa phân công'}}</td>
            <td><span class="badge-soft {{$lead->status==='registered'?'badge-success':'badge-info'}}">{{$statusLabels[$lead->status]??$lead->status}}</span></td>
            <td>@if($lead->convertedStudent)<strong>{{$lead->convertedStudent->name}}</strong><div class="small text-muted">{{$lead->convertedStudent->code}}</div>@else<span class="text-muted">Chưa chuyển đổi</span>@endif</td>
        </tr>
    @empty
        <tr><td colspan="7"><div class="empty-state">Chưa có học viên do CTV này giới thiệu trong kỳ đã chọn.</div></td></tr>
    @endforelse
    </tbody></table></div></div>
    <div class="card-footer bg-white border-0 p-3">{{$items->links()}}</div>
</div>
@endsection
