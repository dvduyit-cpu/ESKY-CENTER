@extends('layouts.app')
@section('title','Lớp giảng dạy')
@section('header','Quản lý giảng dạy')
@section('content')
@php
    $statusBadgeClasses=['planned'=>'badge-gray','recruiting'=>'badge-warning','upcoming'=>'badge-info','active'=>'badge-success','paused'=>'badge-warning','completed'=>'badge-gray','cancelled'=>'badge-danger'];
@endphp
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><h1 class="page-title">{{request('history')?'Lịch sử lớp đã kết thúc':'Lớp đang giảng dạy'}}</h1><div class="page-subtitle">{{request('history')?'Điểm, đánh giá và học phí vẫn được giữ nguyên để tra cứu.':'Mở sổ điểm để nhập điểm kiểm tra và đánh giá quá trình học theo từng tháng.'}}</div></div><div class="btn-group"><a class="btn {{request('history')?'btn-light':'btn-primary'}}" href="{{route('teacher-classes.index')}}">Đang giảng dạy</a><a class="btn {{request('history')?'btn-primary':'btn-light'}}" href="{{route('teacher-classes.index',['history'=>1])}}">Lịch sử</a></div></div>
@if($overview)
<section class="mb-4">
    <div class="row g-3">
        <div class="col-12 col-md-4"><a class="text-decoration-none text-reset" href="{{route('teacher-classes.index')}}"><div class="card card-soft stat-card h-100"><div class="card-body p-4"><div class="d-flex justify-content-between"><div><div class="stat-label">Tổng lớp đang theo dõi</div><div class="stat-value text-primary">{{number_format($overview['total'])}}</div></div><div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-easel2-fill"></i></div></div></div></div></a></div>
        <div class="col-12 col-md-4"><a class="text-decoration-none text-reset" href="{{route('teacher-classes.index',['completion'=>'requested'])}}"><div class="card card-soft stat-card h-100"><div class="card-body p-4"><div class="d-flex justify-content-between"><div><div class="stat-label">Chờ giáo vụ xử lý</div><div class="stat-value text-warning">{{number_format($overview['requested'])}}</div><small class="text-muted">Đã đề nghị hoàn thành</small></div><div class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-hourglass-split"></i></div></div></div></div></a></div>
        <div class="col-12 col-md-4"><a class="text-decoration-none text-reset" href="{{route('teacher-classes.index',['completion'=>'not_requested'])}}"><div class="card card-soft stat-card h-100"><div class="card-body p-4"><div class="d-flex justify-content-between"><div><div class="stat-label">Chờ giáo viên đề nghị</div><div class="stat-value text-secondary">{{number_format($overview['not_requested'])}}</div><small class="text-muted">Lớp đủ điều kiện, chưa gửi hoàn thành</small></div><div class="stat-icon bg-secondary-subtle text-secondary"><i class="bi bi-send-exclamation"></i></div></div></div></div></a></div>
    </div>
</section>
@endif
<form class="filter-panel row g-3 mb-4">
    @if(request('history'))
        <input type="hidden" name="history" value="1">
    @endif
    <div class="{{ $canFilterByStatus ? 'col-lg-4' : 'col-lg-10' }}">
        <input class="form-control" name="q" value="{{request('q')}}" placeholder="Tìm mã lớp hoặc tên lớp">
    </div>
    @if($canFilterByStatus)
    <div class="col-lg-3">
        <select class="form-select" name="status">
            <option value="">Tất cả trạng thái</option>
            @foreach($classStatuses as $value=>$label)
                <option value="{{$value}}" @selected(request('status')===$value)>{{$label}}</option>
            @endforeach
        </select>
    </div>
    <div class="col-lg-3">
        <select class="form-select" name="completion">
            <option value="">Tất cả tiến độ đề nghị</option>
            <option value="requested" @selected(request('completion')==='requested')>Đã đề nghị hoàn thành</option>
            <option value="not_requested" @selected(request('completion')==='not_requested')>Đủ điều kiện, chưa đề nghị</option>
        </select>
    </div>
    @endif
    <div class="col-lg-2">
        <button class="btn btn-dark w-100">Lọc</button>
    </div>
</form>
<div class="row g-4">
@forelse($items as $class)
@if($class->completion_requested_at&&$class->status!=='completed')<div class="col-12 mb-n3"><span class="badge-soft badge-warning"><i class="bi bi-hourglass-split me-1"></i>{{$class->code}} đang chờ giáo vụ kiểm tra học phí và đóng lớp</span></div>@endif
<div class="col-md-6 col-xl-4"><div class="card card-soft h-100"><div class="card-body p-4"><div class="d-flex justify-content-between"><div><div class="d-flex flex-wrap gap-2"><span class="badge-soft badge-info">{{$class->code}}</span><span class="badge-soft {{$statusBadgeClasses[$class->status]??'badge-gray'}}">{{$classStatuses[$class->status]??$class->status}}</span></div><h4 class="mt-3 mb-1">{{$class->name}}</h4><div class="text-muted">{{$class->program?->name}} · {{$class->level?->name}}</div></div><div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-easel2"></i></div></div><hr><div class="d-flex justify-content-between small mb-2"><span><i class="bi bi-person me-1"></i>{{$class->teacher?->name?:'Chưa phân công'}}</span><span><i class="bi bi-people me-1"></i>{{$class->enrollments_count}} học viên</span></div><div class="small text-muted">Đã học {{$class->completed_sessions}} / {{$class->expected_sessions}} buổi · Kết thúc: {{$class->expected_end_date?->format('d/m/Y')?:'Chưa đặt'}}</div></div><div class="card-footer bg-white border-0 p-4 pt-0"><div class="d-grid gap-2"><a class="btn btn-primary" href="{{route('teacher-classes.gradebook',$class)}}"><i class="bi bi-journal-check me-2"></i>{{request('history')?'Xem lại sổ điểm':'Mở sổ điểm'}}</a>@if($canCloseClasses&&$class->completion_requested_at&&$class->status!=='completed')<form method="POST" action="{{route('teacher-classes.close',$class)}}">@csrf @method('PATCH')<button class="btn btn-success w-100" data-confirm="Xác nhận đã kiểm tra học phí và đóng lớp?"><i class="bi bi-lock-fill me-2"></i>Giáo vụ đóng lớp</button></form>@endif</div></div></div></div>
@empty
<div class="col-12"><div class="card card-soft"><div class="empty-state"><i class="bi bi-easel2 fs-1"></i><p class="mt-2">{{request('q')?'Không tìm thấy lớp phù hợp.':'Bạn chưa được phân công lớp học nào.'}}</p></div></div></div>
@endforelse
</div>
@endsection
