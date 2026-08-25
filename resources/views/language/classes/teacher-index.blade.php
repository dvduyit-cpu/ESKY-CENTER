@extends('layouts.app')
@section('title','Lớp giảng dạy')
@section('header','Quản lý giảng dạy')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4"><div><h1 class="page-title">{{request('history')?'Lịch sử lớp đã kết thúc':'Lớp đang giảng dạy'}}</h1><div class="page-subtitle">{{request('history')?'Điểm, đánh giá và học phí vẫn được giữ nguyên để tra cứu.':'Mở sổ điểm để nhập điểm kiểm tra và đánh giá quá trình học theo từng tháng.'}}</div></div><div class="btn-group"><a class="btn {{request('history')?'btn-light':'btn-primary'}}" href="{{route('teacher-classes.index')}}">Đang giảng dạy</a><a class="btn {{request('history')?'btn-primary':'btn-light'}}" href="{{route('teacher-classes.index',['history'=>1])}}">Lịch sử</a></div></div>
<form class="filter-panel row g-3 mb-4">
    @if(request('history'))
        <input type="hidden" name="history" value="1">
    @endif
    <div class="col-lg-10">
        <input class="form-control" name="q" value="{{request('q')}}" placeholder="Tìm mã lớp hoặc tên lớp">
    </div>
    <div class="col-lg-2">
        <button class="btn btn-dark w-100">Lọc</button>
    </div>
</form>
<div class="row g-4">
@forelse($items as $class)
@if($class->completion_requested_at&&$class->status!=='completed')<div class="col-12 mb-n3"><span class="badge-soft badge-warning"><i class="bi bi-hourglass-split me-1"></i>{{$class->code}} đang chờ giáo vụ kiểm tra học phí và đóng lớp</span></div>@endif
<div class="col-md-6 col-xl-4"><div class="card card-soft h-100"><div class="card-body p-4"><div class="d-flex justify-content-between"><div><span class="badge-soft badge-info">{{$class->code}}</span><h4 class="mt-3 mb-1">{{$class->name}}</h4><div class="text-muted">{{$class->program?->name}} · {{$class->level?->name}}</div></div><div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-easel2"></i></div></div><hr><div class="d-flex justify-content-between small mb-2"><span><i class="bi bi-person me-1"></i>{{$class->teacher?->name?:'Chưa phân công'}}</span><span><i class="bi bi-people me-1"></i>{{$class->enrollments_count}} học viên</span></div><div class="small text-muted">Đã học {{$class->completed_sessions}} / {{$class->expected_sessions}} buổi · Kết thúc: {{$class->expected_end_date?->format('d/m/Y')?:'Chưa đặt'}}</div></div><div class="card-footer bg-white border-0 p-4 pt-0"><a class="btn btn-primary w-100" href="{{route('teacher-classes.gradebook',$class)}}"><i class="bi bi-journal-check me-2"></i>{{request('history')?'Xem lại sổ điểm':'Mở sổ điểm'}}</a></div></div></div>
@empty
<div class="col-12"><div class="card card-soft"><div class="empty-state"><i class="bi bi-easel2 fs-1"></i><p class="mt-2">{{request('q')?'Không tìm thấy lớp phù hợp.':'Bạn chưa được phân công lớp học nào.'}}</p></div></div></div>
@endforelse
</div>
@endsection
