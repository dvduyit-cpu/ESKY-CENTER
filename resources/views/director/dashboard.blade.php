@extends('layouts.app')
@section('title','Tổng quan điều hành Trung tâm')
@section('header','Tổng quan điều hành Trung tâm')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="page-title">Tổng quan toàn Trung tâm</h1>
        <div class="page-subtitle">Bảng điều hành riêng của Giám đốc, tổng hợp các hoạt động trọng yếu trong {{$period}}.</div>
    </div>
    <span class="badge-soft badge-info"><i class="bi bi-shield-check me-1"></i>Chỉ Giám đốc được xem toàn bộ</span>
</div>

<form class="filter-panel row g-3 mb-4">
    <div class="col-md-3"><label class="form-label">Từ ngày</label><input class="form-control" type="date" name="from_date" value="{{request('from_date')}}"></div>
    <div class="col-md-3"><label class="form-label">Đến ngày</label><input class="form-control" type="date" name="to_date" value="{{request('to_date')}}"></div>
    <div class="col-6 col-md-2"><label class="form-label">Năm</label><input class="form-control" type="number" name="year" value="{{request('year',now()->year)}}" min="2020" max="2100"></div>
    <div class="col-6 col-md-2"><label class="form-label">Tháng</label><select class="form-select" name="month">@foreach(range(1,12) as $month)<option value="{{$month}}" @selected((int)request('month',now()->month)===$month)>Tháng {{$month}}</option>@endforeach</select></div>
    <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100"><i class="bi bi-filter me-1"></i>Xem tổng quan</button></div>
</form>

<div class="system-section-title">
    <span><i class="bi bi-grid-1x2-fill"></i></span>
    <div><h5>Mô-đun tổng toàn Trung tâm</h5><small>Các chỉ số quan trọng nhất trong {{$period}}</small></div>
</div>
<div class="row g-3 mb-4">
@foreach([
    ['Nhân sự hoạt động',$personnelStats['active'],'primary','bi-people-fill','personnels.index'],
    ['Khách hàng mới',$recruitmentStats['leads'],'info','bi-person-plus-fill','language-leads.index'],
    ['Học viên đang học',$trainingStats['studying_students'],'success','bi-mortarboard-fill','language-students.index'],
    ['Lớp đang hoạt động',$trainingStats['active_classes'],'warning','bi-easel2-fill','language-classes.index'],
    ['Học phí đã thu',number_format($financialStats['collected']).'đ','success','bi-wallet2','language-tuition.index'],
    ['Công việc đang làm',$taskStats['open'],'primary','bi-list-check','tasks.index']
] as [$label,$value,$color,$icon,$route])
    <div class="col-sm-6 col-lg-4 col-xxl-2">
        <a class="dashboard-card-link" href="{{route($route)}}">
            <div class="card card-soft stat-card h-100">
                <div class="card-body p-4">
                    <div class="stat-label">{{$label}}</div>
                    <div class="d-flex justify-content-between align-items-center gap-2">
                        <div class="stat-value text-{{$color}}">{{$value}}</div>
                        <div class="stat-icon bg-{{$color}}-subtle text-{{$color}}"><i class="bi {{$icon}}"></i></div>
                    </div>
                </div>
            </div>
        </a>
    </div>
@endforeach
</div>

<div class="system-section-title">
    <span><i class="bi bi-activity"></i></span>
    <div><h5>Giám sát vận hành và sức khỏe hệ thống</h5><small>Cảnh báo kỹ thuật và nghiệp vụ cần Ban Giám đốc chú ý</small></div>
</div>
<div class="row g-4 mb-4">
    <div class="col-xl-5">
        <div class="card card-soft h-100 system-health-card system-health-{{$systemHealth['status']}}">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div><div class="stat-label">Sức khỏe hệ thống</div><div class="stat-value">{{$systemHealth['passed']}}/{{$systemHealth['total']}}</div></div>
                    <span class="badge-soft {{$systemHealth['status']==='healthy'?'badge-success':($systemHealth['status']==='warning'?'badge-warning':'badge-danger')}}"><i class="bi {{$systemHealth['status']==='healthy'?'bi-check-circle-fill':'bi-exclamation-triangle-fill'}} me-1"></i>{{$systemHealth['status']==='healthy'?'Ổn định':($systemHealth['status']==='warning'?'Có cảnh báo':'Cần xử lý')}}</span>
                </div>
                <div class="d-flex flex-wrap gap-3 small mb-3"><span class="text-success"><strong>{{$systemHealth['passed']}}</strong> đạt</span><span class="text-warning"><strong>{{$systemHealth['warnings']}}</strong> cảnh báo</span><span class="text-danger"><strong>{{$systemHealth['errors']}}</strong> lỗi</span></div>
                <div class="health-issue-list">
                    @forelse($systemHealth['issues'] as $issue)
                        <div class="health-issue"><i class="bi {{$issue['severity']==='warning'?'bi-exclamation-circle text-warning':'bi-x-octagon text-danger'}}"></i><span><strong>{{$issue['name']}}</strong><small>{{$issue['detail']}}</small></span></div>
                    @empty
                        <div class="health-issue is-ok"><i class="bi bi-check-circle-fill text-success"></i><span><strong>Không phát hiện lỗi kỹ thuật</strong><small>Các kiểm tra cốt lõi đều đạt.</small></span></div>
                    @endforelse
                </div>
                <div class="small text-muted mt-3">Cập nhật lúc {{$systemHealth['checked_at']->format('H:i d/m/Y')}}</div>
                @if(auth()->user()->isAdmin())<a class="btn btn-outline-primary w-100 mt-3" href="{{route('admin.system-test')}}"><i class="bi bi-clipboard2-pulse me-1"></i>Mở kiểm thử hệ thống</a>@endif
            </div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="card card-soft h-100"><div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-center mb-3"><div><h5 class="mb-1">Cảnh báo vận hành</h5><small class="text-muted">Số liệu hiện tại, không phụ thuộc kỳ lọc phía trên</small></div><i class="bi bi-bell-fill text-primary fs-4"></i></div>
            <div class="row g-3">
                @foreach($operationalAlerts as $alert)
                    <div class="col-sm-6"><a class="operation-alert operation-alert-{{$alert['tone']}}" href="{{route($alert['route'])}}"><span><i class="bi {{$alert['icon']}}"></i></span><div><strong>{{number_format($alert['count'])}}</strong><small>{{$alert['label']}}</small></div><i class="bi bi-arrow-right"></i></a></div>
                @endforeach
            </div>
        </div></div>
    </div>
</div>

<div class="system-section-title">
    <span><i class="bi bi-columns-gap"></i></span>
    <div><h5>Tổng quan từng phân hệ</h5><small>Mỗi mô-đun hiển thị nhanh tình hình và dẫn đến dữ liệu chi tiết</small></div>
</div>
<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-4">
        <div class="card card-soft h-100">
            <div class="card-header bg-white p-4 d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-people-fill"></i></div>
                <div><h5 class="mb-0 fw-bold">Nhân sự Trung tâm</h5><small class="text-muted">Đội ngũ đang hoạt động</small></div>
            </div>
            <div class="card-body p-4">
                @foreach([
                    ['Tổng nhân sự',$personnelStats['active']],
                    ['Lãnh đạo',$personnelStats['leaders']],
                    ['Giáo viên',$personnelStats['teachers']],
                    ['Tài khoản giảng dạy',$personnelStats['teaching_accounts']],
                    ['Nhân viên',$personnelStats['employees']],
                    ['Cộng tác viên',$personnelStats['collaborators']],
                    ['Tài khoản hoạt động',$personnelStats['accounts']]
                ] as [$label,$value])
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom"><span>{{$label}}</span><strong>{{number_format($value)}}</strong></div>
                @endforeach
            </div>
            <div class="card-footer bg-white border-0 p-4 pt-0"><a class="btn btn-outline-primary w-100" href="{{route('personnels.index')}}">Xem nhân sự <i class="bi bi-arrow-right ms-1"></i></a></div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card card-soft h-100">
            <div class="card-header bg-white p-4 d-flex align-items-center gap-3">
                <div class="stat-icon bg-info-subtle text-info"><i class="bi bi-funnel-fill"></i></div>
                <div><h5 class="mb-0 fw-bold">Tư vấn & tuyển sinh</h5><small class="text-muted">Luồng khách hàng trong kỳ</small></div>
            </div>
            <div class="card-body p-4">
                @foreach([
                    ['Khách hàng mới',$recruitmentStats['leads']],
                    ['Đã tư vấn',$recruitmentStats['consulted']],
                    ['Đã đăng ký',$recruitmentStats['registered']]
                ] as [$label,$value])
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom"><span>{{$label}}</span><strong>{{number_format($value)}}</strong></div>
                @endforeach
                <div class="d-flex justify-content-between align-items-center py-2"><span>Tỷ lệ chuyển đổi</span><strong class="text-success">{{$recruitmentStats['conversion_rate']}}%</strong></div>
            </div>
            <div class="card-footer bg-white border-0 p-4 pt-0"><a class="btn btn-outline-info w-100" href="{{route('language-leads.index')}}">Xem tuyển sinh <i class="bi bi-arrow-right ms-1"></i></a></div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card card-soft h-100">
            <div class="card-header bg-white p-4 d-flex align-items-center gap-3">
                <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-mortarboard-fill"></i></div>
                <div><h5 class="mb-0 fw-bold">Đào tạo & học viên</h5><small class="text-muted">Quy mô đào tạo hiện tại</small></div>
            </div>
            <div class="card-body p-4">
                @foreach([
                    ['Tổng học viên',$trainingStats['students']],
                    ['Học viên mới trong kỳ',$trainingStats['new_students']],
                    ['Học viên đang học',$trainingStats['studying_students']],
                    ['Lớp đang hoạt động',$trainingStats['active_classes']],
                    ['Lớp đang/sắp tuyển',$trainingStats['upcoming_classes']],
                    ['Chương trình hoạt động',$trainingStats['programs']]
                ] as [$label,$value])
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom"><span>{{$label}}</span><strong>{{number_format($value)}}</strong></div>
                @endforeach
            </div>
            <div class="card-footer bg-white border-0 p-4 pt-0"><a class="btn btn-outline-success w-100" href="{{route('language-classes.index')}}">Xem đào tạo <i class="bi bi-arrow-right ms-1"></i></a></div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card card-soft h-100">
            <div class="card-header bg-white p-4 d-flex align-items-center gap-3">
                <div class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-cash-stack"></i></div>
                <div><h5 class="mb-0 fw-bold">Học phí & tài chính</h5><small class="text-muted">Thu, chi và công nợ</small></div>
            </div>
            <div class="card-body p-4">
                @foreach([
                    ['Phải thu trong kỳ',$financialStats['receivable'],'text-primary'],
                    ['Đã thu trong kỳ',$financialStats['collected'],'text-success'],
                    ['Công nợ hiện tại',$financialStats['outstanding'],'text-warning'],
                    ['Đã chi trong kỳ',$financialStats['expense'],'text-danger'],
                    ['Thu ròng trong kỳ',$financialStats['net'],$financialStats['net'] >= 0 ? 'text-success' : 'text-danger']
                ] as [$label,$value,$class])
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom gap-3"><span>{{$label}}</span><strong class="{{$class}} text-nowrap">{{number_format($value)}}đ</strong></div>
                @endforeach
            </div>
            <div class="card-footer bg-white border-0 p-4 pt-0"><a class="btn btn-outline-warning w-100" href="{{route('language-tuition.index')}}">Xem học phí <i class="bi bi-arrow-right ms-1"></i></a></div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card card-soft h-100">
            <div class="card-header bg-white p-4 d-flex align-items-center gap-3">
                <div class="stat-icon bg-danger-subtle text-danger"><i class="bi bi-bullseye"></i></div>
                <div><h5 class="mb-0 fw-bold">Chỉ tiêu Trung tâm</h5><small class="text-muted">Kết quả được ghi nhận trong kỳ</small></div>
            </div>
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom"><span>Lượt ghi nhận</span><strong>{{number_format($targetStats['records'])}}</strong></div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom"><span>Số lượng quy đổi</span><strong>{{number_format($targetStats['quantity'],1)}}</strong></div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom"><span>Học viên phát sinh</span><strong>{{number_format($targetStats['students'])}}</strong></div>
                <div class="d-flex justify-content-between align-items-center py-2"><span>Doanh thu ghi nhận</span><strong class="text-success">{{number_format($targetStats['revenue'])}}đ</strong></div>
            </div>
            <div class="card-footer bg-white border-0 p-4 pt-0"><a class="btn btn-outline-danger w-100" href="{{route('language-targets.index',['year'=>$start->year,'month'=>$start->month])}}">Xem chỉ tiêu <i class="bi bi-arrow-right ms-1"></i></a></div>
        </div>
    </div>

    <div class="col-md-6 col-xl-4">
        <div class="card card-soft h-100">
            <div class="card-header bg-white p-4 d-flex align-items-center gap-3">
                <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-list-check"></i></div>
                <div><h5 class="mb-0 fw-bold">Công việc điều hành</h5><small class="text-muted">Tiến độ công việc trong kỳ</small></div>
            </div>
            <div class="card-body p-4">
                @foreach([
                    ['Tổng công việc',$taskStats['total'],''],
                    ['Đang thực hiện',$taskStats['open'],'text-primary'],
                    ['Đã hoàn thành',$taskStats['completed'],'text-success'],
                    ['Đang quá hạn',$taskStats['overdue'],'text-danger'],
                    ['Đã đóng',$taskStats['closed'],'text-muted']
                ] as [$label,$value,$class])
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom"><span>{{$label}}</span><strong class="{{$class}}">{{number_format($value)}}</strong></div>
                @endforeach
            </div>
            <div class="card-footer bg-white border-0 p-4 pt-0"><a class="btn btn-outline-primary w-100" href="{{route('tasks.index')}}">Xem công việc <i class="bi bi-arrow-right ms-1"></i></a></div>
        </div>
    </div>
</div>

<div class="system-section-title"><span><i class="bi bi-people-fill"></i></span><div><h5>Mọi người đang làm gì</h5><small>Tổng hợp khối lượng và tiến độ theo từng thành viên</small></div></div>
<div class="card card-soft mb-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-modern align-middle mb-0">
            <thead><tr><th>Thành viên</th><th class="text-center">Tổng được giao</th><th class="text-center">Đang làm</th><th class="text-center">Hoàn thành</th><th class="text-center">Chưa nhận</th><th class="text-center">Quá hạn</th></tr></thead>
            <tbody>
            @forelse($memberStats as $member)
                <tr>
                    <td><div class="d-flex align-items-center gap-2"><span class="avatar">{{mb_strtoupper(mb_substr($member->user?->name ?? '?',0,1))}}</span><div><strong>{{$member->user?->name ?? 'Tài khoản đã xóa'}}</strong><div class="small text-muted">{{$member->user?->email}}</div></div></div></td>
                    <td class="text-center"><span class="badge-soft badge-info">{{number_format($member->total_tasks)}}</span></td>
                    <td class="text-center fw-bold text-primary">{{number_format($member->doing_tasks)}}</td>
                    <td class="text-center text-success">{{number_format($member->completed_tasks)}}</td>
                    <td class="text-center {{$member->unacknowledged_tasks?'text-warning fw-bold':'text-muted'}}">{{number_format($member->unacknowledged_tasks)}}</td>
                    <td class="text-center {{$member->overdue_tasks?'text-danger fw-bold':'text-muted'}}">{{number_format($member->overdue_tasks)}}</td>
                </tr>
            @empty
                <tr><td colspan="6"><div class="empty-state">Chưa có phân công trong kỳ.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="system-section-title"><span><i class="bi bi-diagram-3-fill"></i></span><div><h5>Ai giao việc cho ai</h5><small>Danh sách công việc và trạng thái của từng người nhận</small></div></div>
<form class="filter-panel row g-3 mb-3">
    @foreach(request()->only(['from_date','to_date','year','month']) as $key=>$value)<input type="hidden" name="{{$key}}" value="{{$value}}">@endforeach
    <div class="col-md-6"><input class="form-control" name="q" value="{{request('q')}}" placeholder="Tìm công việc, người giao hoặc người nhận"></div>
    <div class="col-md-3"><select class="form-select" name="status"><option value="">Tất cả trạng thái</option><option value="doing" @selected(request('status')==='doing')>Đang thực hiện</option><option value="completed" @selected(request('status')==='completed')>Hoàn thành</option><option value="overdue" @selected(request('status')==='overdue')>Quá hạn</option><option value="closed" @selected(request('status')==='closed')>Đã đóng</option></select></div>
    <div class="col-md-3"><button class="btn btn-dark w-100"><i class="bi bi-search me-1"></i>Tìm kiếm</button></div>
</form>
<div class="card card-soft mb-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-modern align-middle mb-0">
            <thead><tr><th>Công việc</th><th>Người giao</th><th>Người nhận và trạng thái</th><th>Hạn hoàn thành</th><th></th></tr></thead>
            <tbody>
            @forelse($tasks as $task)
                <tr>
                    <td><strong>{{$task->title}}</strong>@if($task->closed_at)<div><span class="badge-soft badge-gray">Đã đóng</span></div>@endif</td>
                    <td><strong>{{$task->creator?->name}}</strong><div class="small text-muted">{{$task->created_at->format('H:i d/m/Y')}}</div></td>
                    <td><div class="d-flex flex-column gap-1">@foreach($task->assignees as $assignment)<div><span class="fw-semibold">@if($assignment->is_lead)<i class="bi bi-star-fill text-warning" title="Người chủ trì"></i>@endif {{$assignment->user?->name}}</span><span class="badge-soft ms-1 {{$assignment->completed_at?'badge-success':($assignment->acknowledged_at?'badge-info':'badge-gray')}}">{{$assignment->completed_at?'Hoàn thành':($assignment->acknowledged_at?'Đang thực hiện':'Chưa nhận việc')}}</span></div>@endforeach</div></td>
                    <td><span class="{{$task->due_at->isPast() && $task->assignees->contains(fn($item)=>!$item->completed_at)?'text-danger fw-bold':''}}">{{$task->due_at->format('H:i d/m/Y')}}</span></td>
                    <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="{{route('tasks.show',$task)}}"><i class="bi bi-eye me-1"></i>Xem</a></td>
                </tr>
            @empty
                <tr><td colspan="5"><div class="empty-state">Không có công việc phù hợp.</div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($tasks->hasPages())<div class="card-footer bg-white border-0 p-3">{{$tasks->links()}}</div>@endif
</div>

<div class="system-section-title"><span><i class="bi bi-person-badge-fill"></i></span><div><h5>Ban Giám đốc</h5><small>Giám đốc theo dõi và quản lý tài khoản Phó giám đốc</small></div></div>
<div class="card card-soft">
    <div class="card-body p-4">
        <div class="row g-3">
            @forelse($deputyDirectors as $deputy)
                <div class="col-md-6 col-xl-4">
                    <div class="border rounded-3 p-3 h-100 d-flex align-items-center justify-content-between gap-3">
                        <div><strong>{{$deputy->name}}</strong><div class="small text-muted">{{$deputy->email}}</div><span class="badge-soft {{$deputy->active?'badge-success':'badge-warning'}}">{{$deputy->active?'Đang hoạt động':'Đã khóa'}}</span></div>
                        @if(auth()->user()->allowed('users','update'))<a class="btn btn-sm btn-outline-primary" href="{{route('users.edit',$deputy)}}" title="Quản lý Phó giám đốc"><i class="bi bi-gear"></i></a>@endif
                    </div>
                </div>
            @empty
                <div class="col-12"><div class="empty-state py-4">Chưa có tài khoản Phó giám đốc.</div></div>
            @endforelse
        </div>
        @if(auth()->user()->isDirector() && auth()->user()->allowed('users'))<a class="btn btn-primary mt-4" href="{{route('users.index')}}"><i class="bi bi-person-gear me-1"></i>Quản lý Phó giám đốc</a>@endif
    </div>
</div>
@endsection
