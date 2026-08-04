@extends('layouts.app')
@section('title',$canViewAll?'Tổng quan toàn hệ thống':'Tổng quan cá nhân')
@section('header',$canViewAll?'Tổng quan toàn hệ thống':'Tổng quan cá nhân')
@section('content')
@php($leadLabels=['new'=>'Mới tiếp nhận','contacted'=>'Đã liên hệ','consulting'=>'Đang tư vấn','placement_test'=>'Hẹn kiểm tra','waiting'=>'Chờ phản hồi','registered'=>'Đã đăng ký','not_interested'=>'Không quan tâm','follow_up'=>'Chăm sóc lại'])
@php($studentLabels=['new'=>'Mới đăng ký','placement_test'=>'Chờ kiểm tra','waiting_class'=>'Chờ xếp lớp','studying'=>'Đang học','paused'=>'Tạm nghỉ','reserved'=>'Bảo lưu','completed'=>'Hoàn thành','dropped'=>'Thôi học'])
@php($classLabels=['planned'=>'Dự kiến mở','recruiting'=>'Đang tuyển sinh','upcoming'=>'Sắp khai giảng','active'=>'Đang hoạt động','paused'=>'Tạm dừng','completed'=>'Đã kết thúc','cancelled'=>'Đã hủy'])
@php($tuitionLabels=['unpaid'=>'Chưa thu','partial'=>'Thu một phần','pending_receipt'=>'Chờ bổ sung phiếu thu','paid'=>'Đã thu đủ','transferred'=>'Đã quyết toán chuyển lớp'])
<div class="d-flex flex-wrap justify-content-between gap-3 mb-4"><div><h1 class="page-title">{{$canViewAll?'Tổng quan toàn hệ thống':'Tổng quan cá nhân'}}</h1><div class="page-subtitle">Toàn bộ vận hành E-SKY CENTER trong {{$period}}.</div></div>@if(auth()->user()->allowed('system_dashboard','export'))<a class="btn btn-outline-success" href="{{route('dashboard.export',request()->query())}}"><i class="bi bi-file-earmark-excel"></i>Xuất Excel</a>@endif</div>

@if($weeklyReportCard)
<div class="weekly-report-prompt mb-4">
    <div class="weekly-report-prompt-icon"><i class="bi bi-file-earmark-text"></i></div>
    <div class="flex-grow-1">
        <div class="small text-uppercase fw-semibold opacity-75">Báo cáo tuần {{ $weeklyReportCard['week_start']->format('d/m') }} – {{ $weeklyReportCard['week_end']->format('d/m/Y') }}</div>
        @if($weeklyReportCard['mode'] === 'management')
            <h5 class="mb-1">Theo dõi báo cáo tuần của toàn bộ nhân sự</h5>
            <div class="small">Trạng thái: <strong>{{ $weeklyReportCard['is_active'] ? 'Đang hoạt động' : 'Đã tắt' }}</strong> · Đã gửi: <strong>{{ $weeklyReportCard['submitted_count'] }}</strong> · Chưa gửi: <strong>{{ $weeklyReportCard['missing_count'] }}</strong></div>
        @else
            <h5 class="mb-1">{{ $weeklyReportCard['report']?->status === 'submitted' ? 'Báo cáo tuần đã được gửi' : 'Admin đã mở kỳ báo cáo tuần' }}</h5>
            <div class="small">Thẻ hiển thị trong thời gian admin bật hoạt động; dữ liệu đã lưu vẫn được giữ lại khi admin tắt.</div>
        @endif
    </div>
    @if($weeklyReportCard['mode'] === 'management')
        <div class="d-flex flex-wrap gap-2">
            @if(!auth()->user()->isAdmin() && $weeklyReportCard['is_assigned'])<a class="btn btn-light text-primary" href="{{ route('administration.weekly.index', ['week'=>$weeklyReportCard['week_start']->toDateString(),'open'=>1]) }}">{{ $weeklyReportCard['report']?->status === 'submitted' ? 'Xem báo cáo của tôi' : 'Báo cáo của tôi' }}</a>@endif
            <a class="btn btn-light text-primary" href="{{ route('administration.weekly.index') }}">Xem các tuần <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
    @else
        <a class="btn btn-light text-primary" href="{{ route('administration.weekly.index', ['week'=>$weeklyReportCard['week_start']->toDateString(),'open'=>1]) }}">{{ $weeklyReportCard['report'] ? 'Xem báo cáo' : 'Báo cáo ngay' }} <i class="bi bi-arrow-right ms-1"></i></a>
    @endif
</div>
@endif

<form class="filter-panel row g-3 mb-4" data-system-period-filter>
<div class="col-lg-2"><label class="form-label">Kiểu thời gian</label><select class="form-select" name="period_type" data-period-mode><option value="range" @selected($periodType==='range')>Khoảng ngày</option><option value="week" @selected($periodType==='week')>Theo tuần</option><option value="month" @selected($periodType==='month')>Theo tháng</option><option value="quarter" @selected($periodType==='quarter')>Theo quý</option><option value="year" @selected($periodType==='year')>Theo năm</option></select></div>
<div class="col-lg-2"><label class="form-label">Năm</label><input class="form-control" type="number" name="year" value="{{$year}}"></div>
<div class="col-lg-2" data-period-field="week"><label class="form-label">Tuần</label><input class="form-control" type="number" name="week" min="1" max="53" value="{{request('week',now()->isoWeek())}}"></div>
<div class="col-lg-2" data-period-field="month"><label class="form-label">Tháng</label><select class="form-select" name="month">@for($m=1;$m<=12;$m++)<option value="{{$m}}" @selected(request('month',now()->month)==$m)>Tháng {{$m}}</option>@endfor</select></div>
<div class="col-lg-2" data-period-field="quarter"><label class="form-label">Quý</label><select class="form-select" name="quarter">@for($q=1;$q<=4;$q++)<option value="{{$q}}" @selected(request('quarter',now()->quarter)==$q)>Quý {{$q}}</option>@endfor</select></div>
<div class="col-lg-2" data-period-field="range"><label class="form-label">Từ ngày</label><input class="form-control" type="date" name="from_date" value="{{request('from_date',now()->startOfMonth()->format('Y-m-d'))}}"></div>
<div class="col-lg-2" data-period-field="range"><label class="form-label">Đến ngày</label><input class="form-control" type="date" name="to_date" value="{{request('to_date',now()->format('Y-m-d'))}}"></div>
<div class="col-lg-2 d-flex align-items-end"><button class="btn btn-primary w-100"><i class="bi bi-filter"></i>Xem tổng quan</button></div>
</form>

<div class="system-section-title"><span><i class="bi bi-list-check"></i></span><div><h5>{{$canViewAll?'Công việc toàn hệ thống':'Công việc của tôi'}}</h5><small>Thống kê công việc được giao trong {{$period}}</small></div>@if($canViewAll)<button class="btn btn-sm btn-outline-primary ms-auto" type="button" data-bs-toggle="modal" data-bs-target="#taskRecipientStatsModal"><i class="bi bi-people me-1"></i>Xem theo thành viên</button>@elseif(auth()->user()->allowed('work_tasks'))<a class="btn btn-sm btn-outline-primary ms-auto" href="{{route('tasks.index')}}">Mở công việc</a>@endif</div>
<div class="row g-3 mb-4">
@if($canViewAll)
@foreach([
    ['Tổng công việc đã giao',$workTaskStats['total'],'primary','bi-send-check',''],
    ['Lượt phân công',$workTaskStats['assignments'],'info','bi-people',''],
    ['Đã nhận việc',$workTaskStats['acknowledged'],'success','bi-check-square',''],
    ['Đã hoàn thành',$workTaskStats['completed'],'success','bi-check2-circle','completed'],
    ['Đang quá hạn',$workTaskStats['overdue'],'danger','bi-exclamation-triangle','overdue'],
] as [$label,$value,$color,$icon,$status])
<div class="col-6 col-xl">@if(auth()->user()->allowed('work_tasks'))<a class="dashboard-card-link" href="{{route('tasks.index',array_filter(['status'=>$status]))}}">@endif<div class="card card-soft stat-card h-100"><div class="card-body p-4"><div class="stat-label">{{$label}}</div><div class="d-flex justify-content-between align-items-center"><div class="stat-value text-{{$color}}">{{number_format($value)}}</div><div class="stat-icon bg-{{$color}}-subtle text-{{$color}}"><i class="bi {{$icon}}"></i></div></div></div></div>@if(auth()->user()->allowed('work_tasks'))</a>@endif</div>
@endforeach
@else
@foreach([
    ['Công việc liên quan',$workTaskStats['total'],'primary','bi-send-check',''],
    ['Chưa xác nhận',$workTaskStats['awaiting_acknowledgement'],'warning','bi-exclamation-circle','unread'],
    ['Đã nhận việc',$workTaskStats['in_progress'],'info','bi-check-square','acknowledged'],
    ['Đã hoàn thành',$workTaskStats['completed'],'success','bi-check2-circle','personal_completed'],
    ['Đang quá hạn',$workTaskStats['overdue'],'danger','bi-exclamation-triangle','overdue'],
] as [$label,$value,$color,$icon,$status])
<div class="col-6 col-xl">@if(auth()->user()->allowed('work_tasks'))<a class="dashboard-card-link" href="{{route('tasks.index',array_filter(['status'=>$status]))}}">@endif<div class="card card-soft stat-card h-100"><div class="card-body p-4"><div class="stat-label">{{$label}}</div><div class="d-flex justify-content-between align-items-center"><div class="stat-value text-{{$color}}">{{number_format($value)}}</div><div class="stat-icon bg-{{$color}}-subtle text-{{$color}}"><i class="bi {{$icon}}"></i></div></div></div></div>@if(auth()->user()->allowed('work_tasks'))</a>@endif</div>
@endforeach
@endif
</div>

@if($canViewAll)
<div class="modal fade" id="taskRecipientStatsModal" tabindex="-1" aria-labelledby="taskRecipientStatsTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0">
            <div class="modal-header">
                <div><h5 class="modal-title" id="taskRecipientStatsTitle">Công việc theo thành viên</h5><small class="text-muted">Số công việc mỗi thành viên được giao trong {{$period}}.</small></div>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0">
                        <thead><tr><th>Thành viên</th><th class="text-center">Được giao</th><th class="text-center">Đã nhận việc</th><th class="text-center">Hoàn thành</th><th class="text-center">Chưa nhận</th></tr></thead>
                        <tbody>
                        @forelse($taskRecipientStats as $member)
                            <tr><td><strong>{{$member->user?->name ?? 'Tài khoản đã xóa'}}</strong><div class="small text-muted">{{$member->user?->email}}</div></td><td class="text-center"><span class="badge-soft badge-info">{{number_format($member->total_tasks)}}</span></td><td class="text-center">{{number_format($member->acknowledged_tasks)}}</td><td class="text-center text-success fw-bold">{{number_format($member->completed_tasks)}}</td><td class="text-center {{$member->unacknowledged_tasks?'text-danger fw-bold':'text-muted'}}">{{number_format($member->unacknowledged_tasks)}}</td></tr>
                        @empty
                            <tr><td colspan="5"><div class="empty-state py-5">Chưa có công việc được giao trong kỳ này.</div></td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<div class="system-section-title"><span><i class="bi bi-cash-stack"></i></span><div><h5>Tài chính trung tâm</h5><small>Học phí thu, công nợ và khoản chi</small></div></div>
<div class="row g-3 mb-4">@foreach([['Phải thu',$financial['receivable'],'primary','bi-receipt','language-tuition.index','language_tuition'],['Đã thu',$financial['collected'],'success','bi-wallet2','language-tuition.index','language_tuition'],['Còn phải thu',$financial['outstanding'],'warning','bi-exclamation-circle','language-tuition.index','language_tuition'],['Đã chi',$financial['expense'],'danger','bi-cash-coin','payments.index','payments'],['Thu ròng',$financial['net'],$financial['net']>=0?'success':'danger','bi-graph-up-arrow','language-tuition.index','language_tuition']] as [$label,$value,$color,$icon,$route,$permission])<div class="col-sm-6 col-xl">@if(auth()->user()->allowed($permission))<a class="dashboard-card-link" href="{{route($route)}}">@endif<div class="card card-soft stat-card h-100"><div class="card-body p-4"><div class="d-flex justify-content-between gap-2"><div><div class="stat-label">{{$label}}</div><div class="fs-4 fw-bold text-{{$color}}">{{number_format($value)}}đ</div></div><div class="stat-icon bg-{{$color}}-subtle text-{{$color}}"><i class="bi {{$icon}}"></i></div></div></div></div>@if(auth()->user()->allowed($permission))</a>@endif</div>@endforeach</div>

<div class="system-section-title"><span><i class="bi bi-funnel-fill"></i></span><div><h5>Tư vấn và tuyển sinh</h5><small>Luồng chuyển đổi trong {{$period}}</small></div><a class="btn btn-sm btn-outline-primary ms-auto" href="{{route('language-dashboard.index',request()->query())}}">Xem trung tâm</a></div>
<div class="row g-3 mb-4">@foreach([['Khách hàng mới',$leads,'info','bi-person-plus','language-leads.index','language_leads'],['Đã tư vấn',$consulted,'primary','bi-headset','language-consulting.index','language_consulting'],['Đã đăng ký',$registeredLeads,'success','bi-person-check','language-students.index','language_students'],['Tỷ lệ chuyển đổi',$conversionRate.'%','warning','bi-graph-up','language-leads.index','language_leads']] as [$label,$value,$color,$icon,$route,$permission])<div class="col-sm-6 col-xl-3">@if(auth()->user()->allowed($permission))<a class="dashboard-card-link" href="{{route($route)}}">@endif<div class="card card-soft stat-card h-100"><div class="card-body p-4"><div class="d-flex justify-content-between"><div><div class="stat-label">{{$label}}</div><div class="stat-value">{{$value}}</div></div><div class="stat-icon bg-{{$color}}-subtle text-{{$color}}"><i class="bi {{$icon}}"></i></div></div></div></div>@if(auth()->user()->allowed($permission))</a>@endif</div>@endforeach</div>

<div class="system-section-title"><span><i class="bi bi-mortarboard-fill"></i></span><div><h5>Học viên và lớp học</h5><small>Quy mô đào tạo hiện tại</small></div></div>
<div class="row g-3 mb-4">@foreach([['Học viên mới',$students,'success','bi-mortarboard','language-students.index','language_students'],['Lớp đang học',$activeClasses,'primary','bi-easel2','language-classes.index','language_classes'],['Lớp đang/sắp tuyển',$upcomingClasses,'warning','bi-calendar-plus','language-classes.index','language_classes'],['Nhân sự hoạt động',$activePersonnel,'info','bi-people','personnels.index','personnel'],['Account hoạt động',$activeUsers,'primary','bi-person-check','users.index','users']] as [$label,$value,$color,$icon,$route,$permission])<div class="col-sm-6 col-xl">@if(auth()->user()->allowed($permission))<a class="dashboard-card-link" href="{{route($route)}}">@endif<div class="card card-soft stat-card h-100"><div class="card-body p-4"><div class="stat-label">{{$label}}</div><div class="d-flex justify-content-between align-items-center"><div class="stat-value">{{number_format($value)}}</div><div class="stat-icon bg-{{$color}}-subtle text-{{$color}}"><i class="bi {{$icon}}"></i></div></div></div></div>@if(auth()->user()->allowed($permission))</a>@endif</div>@endforeach</div>

<div class="system-section-title"><span><i class="bi bi-bullseye"></i></span><div><h5>Chỉ tiêu & dữ liệu năm {{$year}}</h5><small>KPI được theo dõi riêng theo năm</small></div><a class="btn btn-sm btn-outline-primary ms-auto" href="{{route('kpi-dashboard.index',['year'=>$year])}}">Xem KPI</a></div>
<div class="row g-3 mb-4">@foreach([['Chỉ tiêu',$kpiTotals['target_quantity'],'primary'],['Thực hiện',$kpiTotals['actual_quantity'],'success'],['Còn lại',$kpiTotals['remaining_quantity'],'warning'],['Vượt chỉ tiêu',$kpiTotals['excess_quantity'],'danger']] as [$label,$value,$color])<div class="col-sm-6 col-xl-3"><a class="dashboard-card-link" href="{{route('kpi-dashboard.index',['year'=>$year])}}"><div class="card card-soft"><div class="card-body p-4"><div class="stat-label">{{$label}}</div><div class="stat-value text-{{$color}}">{{number_format($value,1)}}</div></div></div></a></div>@endforeach</div>

<div class="system-section-title"><span><i class="bi bi-ui-checks-grid"></i></span><div><h5>Trạng thái tổng hợp</h5><small>Nắm nhanh tình trạng từng nhóm nghiệp vụ</small></div></div>
<div class="row g-4 mb-4">
@foreach([['Trạng thái tư vấn',$leadLabels,$leadStatuses,'bi-headset','violet','language-leads.index','language_leads'],['Trạng thái học viên',$studentLabels,$studentStatuses,'bi-mortarboard','green','language-students.index','language_students'],['Trạng thái lớp học',$classLabels,$classStatuses,'bi-easel2','orange','language-classes.index','language_classes'],['Trạng thái học phí',$tuitionLabels,$tuitionStatuses,'bi-receipt','rose','language-tuition.index','language_tuition']] as [$title,$labels,$values,$icon,$tone,$route,$permission])
<div class="col-md-6 col-xl-3">@if(auth()->user()->allowed($permission))<a class="dashboard-card-link" href="{{route($route)}}">@endif<div class="card card-soft h-100 status-summary-card status-card-{{$tone}}"><div class="card-header p-4"><h6 class="mb-0 fw-bold"><span class="status-summary-icon"><i class="bi {{$icon}}"></i></span>{{$title}}</h6></div><div class="card-body p-3">@foreach($labels as $key=>$label)@php($count=(int)($values[$key]??0))<div class="status-summary-row status-tone-{{($loop->index%8)+1}}"><span>{{$label}}</span><strong class="{{$count===0?'is-empty':''}}">{{$count}}</strong></div>@endforeach</div></div>@if(auth()->user()->allowed($permission))</a>@endif</div>
@endforeach
</div>

<div class="row g-4">@if($canViewAll)<div class="col-xl-7">@if(auth()->user()->allowed('logs'))<a class="dashboard-card-link" href="{{route('logs.index')}}">@endif<div class="card card-soft"><div class="card-header bg-white p-4"><h5 class="mb-0 fw-bold">Hoạt động gần đây</h5></div><div class="table-responsive"><table class="table table-modern"><thead><tr><th>Người thực hiện</th><th>Nội dung</th><th>Thời gian</th></tr></thead><tbody>@forelse($recentActivities as $log)<tr><td><strong>{{$log->user?->name?:'Hệ thống'}}</strong></td><td>{{$log->description}}</td><td>{{$log->created_at?->format('d/m/Y H:i')}}</td></tr>@empty<tr><td colspan="3"><div class="empty-state">Không có hoạt động trong kỳ.</div></td></tr>@endforelse</tbody></table></div></div>@if(auth()->user()->allowed('logs'))</a>@endif</div>@endif<div class="{{$canViewAll?'col-xl-5':'col-12'}}">@if(auth()->user()->allowed('imports'))<a class="dashboard-card-link" href="{{route('imports.index')}}">@endif<div class="card card-soft h-100"><div class="card-header bg-white p-4"><h5 class="mb-0 fw-bold">Dữ liệu nhập gần đây</h5></div><div class="card-body">@forelse($recentImports as $batch)<div class="d-flex gap-3 border-bottom py-3"><div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-file-earmark-spreadsheet"></i></div><div><strong>{{$batch->original_name}}</strong><div class="small text-muted">{{$batch->user?->name}} · {{$batch->created_at?->format('d/m/Y H:i')}}</div></div></div>@empty<div class="empty-state">Không có dữ liệu nhập trong kỳ.</div>@endforelse</div></div>@if(auth()->user()->allowed('imports'))</a>@endif</div></div>
@endsection
@push('scripts')<script>document.addEventListener('DOMContentLoaded',()=>{const form=document.querySelector('[data-system-period-filter]'),mode=form?.querySelector('[data-period-mode]');if(form&&mode){const update=()=>form.querySelectorAll('[data-period-field]').forEach(field=>field.classList.toggle('d-none',field.dataset.periodField!==mode.value));mode.addEventListener('change',update);update()}});</script>@endpush
