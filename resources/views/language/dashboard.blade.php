@extends('layouts.app')
@section('title',$canViewAll?'Tổng quan trung tâm':'Tổng quan trung tâm cá nhân') @section('header','Trung tâm ngoại ngữ')
@section('content')
@php
    $maxMonthly = max(1, $monthly->max(fn($row) => max($row['leads'], $row['students'])));
    $maxLeadStatus = max(1, (int) $leadStatuses->max());
    $maxStudentStatus = max(1, (int) $studentStatuses->max());
    $leadColors = ['new'=>'info','contacted'=>'primary','consulting'=>'warning','placement_test'=>'primary','waiting'=>'gray','registered'=>'success','not_interested'=>'danger','follow_up'=>'warning'];
@endphp
<div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
    <div><h1 class="page-title">{{$canViewAll?'Tổng quan trung tâm':'Tổng quan trung tâm cá nhân'}}</h1><div class="page-subtitle">{{$canViewAll?'Tình hình toàn trung tâm':'Dữ liệu tư vấn và lớp học được giao cho tài khoản này'}} trong {{$selectedPeriod}}.</div></div>
    <form class="d-flex flex-wrap gap-2 align-items-center"><input class="form-control" type="number" name="year" value="{{$year}}" min="2020" max="2100" style="width:110px" aria-label="Năm"><select class="form-select" name="quarter" style="width:130px"><option value="">Mọi quý</option>@for($q=1;$q<=4;$q++)<option value="{{$q}}" @selected($quarter===$q)>Quý {{$q}}</option>@endfor</select><select class="form-select" name="month" style="width:140px"><option value="">Mọi tháng</option>@for($m=1;$m<=12;$m++)<option value="{{$m}}" @selected($month===$m)>Tháng {{$m}}</option>@endfor</select><button class="btn btn-primary"><i class="bi bi-filter me-1"></i>Lọc</button></form>
</div>

<form class="filter-panel row g-3 mb-4" data-overview-period-filter>
    <div class="col-lg-2 col-md-4"><label class="form-label">Kiểu thời gian</label><select class="form-select" name="period_type" data-period-mode><option value="range" @selected($periodType==='range')>Khoảng ngày</option><option value="week" @selected($periodType==='week')>Theo tuần</option><option value="month" @selected($periodType==='month')>Theo tháng</option><option value="quarter" @selected($periodType==='quarter')>Theo quý</option><option value="year" @selected($periodType==='year')>Theo năm</option></select></div>
    <div class="col-lg-2 col-md-4"><label class="form-label">Năm</label><input class="form-control" type="number" name="year" value="{{$year}}" min="2020" max="2100"></div>
    <div class="col-lg-2 col-md-4" data-period-field="week"><label class="form-label">Tuần</label><input class="form-control" type="number" name="week" value="{{$week?:now()->isoWeek()}}" min="1" max="53"></div>
    <div class="col-lg-2 col-md-4" data-period-field="month"><label class="form-label">Tháng</label><select class="form-select" name="month">@for($m=1;$m<=12;$m++)<option value="{{$m}}" @selected(($month?:now()->month)===$m)>Tháng {{$m}}</option>@endfor</select></div>
    <div class="col-lg-2 col-md-4" data-period-field="quarter"><label class="form-label">Quý</label><select class="form-select" name="quarter">@for($q=1;$q<=4;$q++)<option value="{{$q}}" @selected(($quarter?:now()->quarter)===$q)>Quý {{$q}}</option>@endfor</select></div>
    <div class="col-lg-2 col-md-4" data-period-field="range"><label class="form-label">Từ ngày</label><input class="form-control" type="date" name="from_date" value="{{request('from_date',$fromDate?->format('Y-m-d')?:now()->startOfMonth()->format('Y-m-d'))}}"></div>
    <div class="col-lg-2 col-md-4" data-period-field="range"><label class="form-label">Đến ngày</label><input class="form-control" type="date" name="to_date" value="{{request('to_date',$toDate?->format('Y-m-d')?:now()->format('Y-m-d'))}}"></div>
    <div class="col-lg-2 col-md-4 d-flex align-items-end"><button class="btn btn-primary w-100"><i class="bi bi-filter"></i>Xem tổng quan</button></div>
</form>

<div class="system-section-title"><span><i class="bi bi-cash-stack"></i></span><div><h5>Học phí thu – chi</h5><small>Số liệu trong {{$selectedPeriod}}</small></div><div class="ms-auto d-flex gap-2">@if(auth()->user()->allowed('language_dashboard_all','export'))<a class="btn btn-sm btn-outline-success" href="{{route('language-dashboard.export',request()->query())}}"><i class="bi bi-file-earmark-excel"></i>Xuất tổng quan</a>@endif @if(auth()->user()->allowed('language_tuition'))<a class="btn btn-sm btn-outline-primary" href="{{route('language-tuition.index')}}">Chi tiết học phí</a>@endif</div></div>
<div class="row g-3 mb-4">
    @foreach([
        ['Phải thu',$financial['receivable'],'bi-receipt','primary'],
        ['Đã thu',$financial['collected'],'bi-wallet2','success'],
        ['Còn phải thu',$financial['outstanding'],'bi-exclamation-circle','warning'],
        ['Đã chi',$financial['expense'],'bi-cash-coin','danger'],
        ['Thu ròng',$financial['net'],'bi-graph-up-arrow',$financial['net']>=0?'success':'danger'],
    ] as [$label,$amount,$icon,$color])
    <div class="col-sm-6 col-xl">@if(auth()->user()->allowed('language_tuition'))<a class="dashboard-card-link" href="{{route('language-tuition.index')}}">@endif<div class="card card-soft stat-card h-100"><div class="card-body p-4"><div class="d-flex justify-content-between gap-2"><div><div class="stat-label">{{$label}}</div><div class="fs-4 fw-bold text-{{$color}}">{{number_format($amount)}}đ</div></div><div class="stat-icon bg-{{$color}}-subtle text-{{$color}}"><i class="bi {{$icon}}"></i></div></div></div></div>@if(auth()->user()->allowed('language_tuition'))</a>@endif</div>
    @endforeach
</div>

<div class="system-section-title"><span><i class="bi bi-headset"></i></span><div><h5>Tư vấn và tuyển sinh</h5><small>Tổng quan từng nhóm hoạt động</small></div></div>
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">@if(auth()->user()->allowed('language_leads'))<a class="dashboard-card-link" href="{{route('language-leads.index')}}">@endif<div class="card card-soft stat-card h-100"><div class="card-body p-4"><div class="d-flex justify-content-between"><div><div class="stat-label">Tổng khách hàng</div><div class="stat-value">{{number_format($totalLeads)}}</div></div><div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-person-lines-fill"></i></div></div><div class="metric-note">+{{$newLeads['month']}} khách trong tháng</div></div></div>@if(auth()->user()->allowed('language_leads'))</a>@endif</div>
    <div class="col-sm-6 col-xl-3">@if(auth()->user()->allowed('language_students'))<a class="dashboard-card-link" href="{{route('language-students.index')}}">@endif<div class="card card-soft stat-card h-100"><div class="card-body p-4"><div class="d-flex justify-content-between"><div><div class="stat-label">Tổng học viên</div><div class="stat-value">{{number_format($totalStudents)}}</div></div><div class="stat-icon bg-success-subtle text-success"><i class="bi bi-mortarboard-fill"></i></div></div><div class="metric-note">+{{$registered['month']}} đăng ký trong tháng</div></div></div>@if(auth()->user()->allowed('language_students'))</a>@endif</div>
    <div class="col-sm-6 col-xl-3">@if(auth()->user()->allowed('language_classes'))<a class="dashboard-card-link" href="{{route('language-classes.index')}}">@endif<div class="card card-soft stat-card h-100"><div class="card-body p-4"><div class="d-flex justify-content-between"><div><div class="stat-label">Lớp đang hoạt động</div><div class="stat-value">{{number_format($activeClasses)}}</div></div><div class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-easel2-fill"></i></div></div><div class="metric-note">{{$recruitingClasses}} lớp đang/sắp tuyển sinh</div></div></div>@if(auth()->user()->allowed('language_classes'))</a>@endif</div>
    <div class="col-sm-6 col-xl-3">@if(auth()->user()->allowed('language_programs'))<a class="dashboard-card-link" href="{{route('language-programs.index')}}">@endif<div class="card card-soft stat-card h-100"><div class="card-body p-4"><div class="d-flex justify-content-between"><div><div class="stat-label">Tỷ lệ chuyển đổi</div><div class="stat-value">{{$conversionRate}}%</div></div><div class="stat-icon bg-danger-subtle text-danger"><i class="bi bi-graph-up-arrow"></i></div></div><div class="metric-note">{{$programCount}} chương trình hoạt động</div></div></div>@if(auth()->user()->allowed('language_programs'))</a>@endif</div>
</div>

<div class="row g-3 mb-4">
    @foreach([
        ['Tư vấn', $consultations, 'bi-headset', 'primary'],
        ['Khách hàng mới', $newLeads, 'bi-person-plus-fill', 'info'],
        ['Lượt đăng ký', $registered, 'bi-person-check-fill', 'success'],
    ] as [$title,$values,$icon,$color])
    @php($metricRoute=$title==='Tư vấn'?'language-consulting.index':($title==='Khách hàng mới'?'language-leads.index':'language-students.index'))
    @php($metricPermission=$title==='Tư vấn'?'language_consulting':($title==='Khách hàng mới'?'language_leads':'language_students'))
    <div class="col-xl-4">@if(auth()->user()->allowed($metricPermission))<a class="dashboard-card-link" href="{{route($metricRoute)}}">@endif<div class="card card-soft h-100"><div class="card-body p-4"><div class="d-flex align-items-center gap-3 mb-4"><div class="stat-icon bg-{{$color}}-subtle text-{{$color}}"><i class="bi {{$icon}}"></i></div><div><h5 class="mb-0 fw-bold">{{$title}}</h5><small class="text-muted">Theo thời gian hiện tại</small></div></div><div class="period-metrics"><div><span>Hôm nay</span><strong>{{$values['today']}}</strong></div><div><span>Tháng này</span><strong>{{$values['month']}}</strong></div><div><span>Năm {{$year}}</span><strong>{{$values['year']}}</strong></div></div></div></div>@if(auth()->user()->allowed($metricPermission))</a>@endif</div>
    @endforeach
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-7"><div class="card card-soft h-100"><div class="card-header bg-white border-0 p-4 pb-2"><h5 class="mb-1 fw-bold">Tuyển sinh theo tháng</h5><small class="text-muted">So sánh khách hàng mới và học viên đăng ký trong năm {{$year}}.</small></div><div class="card-body p-4"><div class="monthly-chart">@foreach($monthly as $row)<div class="monthly-column"><div class="bar-area"><span class="bar leads" style="height:{{round($row['leads']/$maxMonthly*100)}}%" title="{{$row['leads']}} khách hàng"></span><span class="bar students" style="height:{{round($row['students']/$maxMonthly*100)}}%" title="{{$row['students']}} đăng ký"></span></div><small>T{{$row['month']}}</small></div>@endforeach</div><div class="chart-legend"><span><i class="legend-dot leads"></i>Khách hàng mới</span><span><i class="legend-dot students"></i>Học viên đăng ký</span></div></div></div></div>
    <div class="col-xl-5"><div class="card card-soft h-100"><div class="card-header bg-white border-0 p-4 pb-2"><h5 class="mb-1 fw-bold">Tình trạng tư vấn</h5><small class="text-muted">Phân bố toàn bộ khách hàng hiện tại.</small></div><div class="card-body p-4">@foreach($leadStatusLabels as $key=>$label)@php($count=(int)($leadStatuses[$key]??0))<div class="status-row"><div class="d-flex justify-content-between"><span>{{$label}}</span><strong>{{$count}}</strong></div><div class="status-track"><span class="{{$leadColors[$key]??'info'}}" style="width:{{round($count/$maxLeadStatus*100)}}%"></span></div></div>@endforeach</div></div></div>
</div>

<div class="row g-4">
    <div class="col-xl-7"><div class="card card-soft"><div class="card-header bg-white border-0 p-4 d-flex justify-content-between align-items-center"><div><h5 class="mb-1 fw-bold">Khách hàng gần đây</h5><small class="text-muted">Các lượt tiếp nhận mới nhất.</small></div>@if(auth()->user()->allowed('language_leads'))<a class="btn btn-sm btn-outline-primary" href="{{route('language-leads.index')}}">Xem tất cả</a>@endif</div><div class="table-responsive"><table class="table table-modern"><thead><tr><th>Khách hàng</th><th>Chương trình</th><th>Tư vấn viên</th><th>Ngày tiếp nhận</th></tr></thead><tbody>@forelse($recentLeads as $lead)<tr><td><strong>{{$lead->name}}</strong><div class="small text-muted">{{$lead->phone}}</div></td><td>{{$lead->program?->name?:'Chưa xác định'}}</td><td>{{$lead->consultant?->name?:'Chưa phân công'}}</td><td>{{$lead->created_at?->format('d/m/Y H:i')}}</td></tr>@empty<tr><td colspan="4"><div class="empty-state">Chưa có khách hàng.</div></td></tr>@endforelse</tbody></table></div></div></div>
    <div class="col-xl-5"><div class="card card-soft h-100"><div class="card-header bg-white border-0 p-4 pb-2"><h5 class="mb-1 fw-bold">Tình trạng học viên</h5><small class="text-muted">Số lượng theo trạng thái học tập.</small></div><div class="card-body p-4">@foreach($studentStatusLabels as $key=>$label)@php($count=(int)($studentStatuses[$key]??0))<div class="status-row"><div class="d-flex justify-content-between"><span>{{$label}}</span><strong>{{$count}}</strong></div><div class="status-track"><span class="info" style="width:{{round($count/$maxStudentStatus*100)}}%"></span></div></div>@endforeach</div></div></div>
</div>
@endsection
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const filter=document.querySelector('[data-overview-period-filter]');
    const legacyFilter=document.querySelector('.content > .d-flex:first-child form');
    if(legacyFilter)legacyFilter.classList.add('d-none');
    if(!filter)return;
    const mode=filter.querySelector('[data-period-mode]');
    const update=()=>filter.querySelectorAll('[data-period-field]').forEach(field=>field.classList.toggle('d-none',field.dataset.periodField!==mode.value));
    mode.addEventListener('change',update);update();
});
</script>
@endpush
