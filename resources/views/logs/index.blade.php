@extends('layouts.app')
@section('title','Nhật ký hệ thống')
@section('header','Nhật ký hệ thống')
@section('content')
@php($moduleLabels=['users'=>'Tài khoản','roles'=>'Vai trò & quyền','personnel'=>'Nhân sự','courses'=>'Khóa học','kpis'=>'Chỉ tiêu','imports'=>'Nhập dữ liệu','reports'=>'Báo cáo','payments'=>'Thanh toán','settings'=>'Cấu hình','profile'=>'Hồ sơ','language_students'=>'Học viên','language_leads'=>'Học viên tiềm năng'])
@php($actionLabels=['create'=>'Tạo mới','update'=>'Cập nhật','delete'=>'Xóa','force_delete'=>'Xóa vĩnh viễn','restore'=>'Khôi phục','toggle'=>'Đổi trạng thái','permissions'=>'Phân quyền','reset_password'=>'Đặt lại mật khẩu','export'=>'Xuất dữ liệu','login_success'=>'Đăng nhập','login_failed'=>'Đăng nhập thất bại','logout'=>'Đăng xuất'])

<div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
    <div><h1 class="page-title">Nhật ký hệ thống</h1><div class="page-subtitle">Theo dõi chi tiết thao tác quản trị, đăng nhập và những dữ liệu đã thay đổi.</div></div>
    <div class="log-period-badge"><i class="bi bi-calendar3"></i><div><small>Kỳ đang xem</small><strong>{{$month?'Tháng '.$month.' / ':''}}Năm {{$year}}</strong></div></div>
</div>

<div class="row g-3 mb-4">
    @foreach([['Thao tác',$activityCount,'primary','bi-activity'],['Đăng nhập',$loginCount,'info','bi-box-arrow-in-right'],['Thành công',$loginSuccess,'success','bi-check-circle'],['Thất bại',$loginFailed,'danger','bi-shield-exclamation']] as [$label,$value,$color,$icon])
        <div class="col-6 col-xl-3"><div class="card card-soft log-stat h-100"><div class="card-body"><span class="log-stat-icon bg-{{$color}}-subtle text-{{$color}}"><i class="bi {{$icon}}"></i></span><div><small>{{$label}}</small><strong>{{number_format($value)}}</strong></div></div></div></div>
    @endforeach
</div>

<form class="filter-panel row g-3 mb-4" method="GET">
    <input type="hidden" name="tab" value="{{$activeTab}}" data-log-tab-input>
    <div class="col-xl-3 col-md-6"><label class="form-label">Tìm kiếm</label><div class="input-group"><span class="input-group-text bg-white"><i class="bi bi-search"></i></span><input class="form-control" name="q" value="{{request('q')}}" placeholder="Mô tả, tài khoản, IP..."></div></div>
    <div class="col-xl-2 col-md-3 col-6"><label class="form-label">Năm</label><select class="form-select" name="year">@for($itemYear=now()->year;$itemYear>=2020;$itemYear--)<option value="{{$itemYear}}" @selected($year===$itemYear)>Năm {{$itemYear}}</option>@endfor</select></div>
    <div class="col-xl-2 col-md-3 col-6"><label class="form-label">Tháng</label><select class="form-select" name="month"><option value="">Cả năm</option>@for($itemMonth=1;$itemMonth<=12;$itemMonth++)<option value="{{$itemMonth}}" @selected($month===$itemMonth)>Tháng {{$itemMonth}}</option>@endfor</select></div>
    <div class="col-xl-2 col-md-4"><label class="form-label">Phân hệ</label><select class="form-select" name="module"><option value="">Tất cả phân hệ</option>@foreach($modules as $module)<option value="{{$module}}" @selected(request('module')===$module)>{{$moduleLabels[$module]??str_replace('_',' ',$module)}}</option>@endforeach</select></div>
    <div class="col-xl-2 col-md-4"><label class="form-label">Hành động</label><select class="form-select" name="action"><option value="">Tất cả hành động</option>@foreach($actions as $action)<option value="{{$action}}" @selected(request('action')===$action)>{{$actionLabels[$action]??str_replace('_',' ',$action)}}</option>@endforeach</select></div>
    <div class="col-xl-1 col-md-4"><label class="form-label">Số dòng</label><select class="form-select" name="per_page">@foreach([15,30,50] as $size)<option value="{{$size}}" @selected($perPage===$size)>{{$size}}</option>@endforeach</select></div>
    <div class="col-12 d-flex flex-wrap justify-content-end gap-2"><a class="btn btn-light" href="{{route('logs.index')}}"><i class="bi bi-arrow-counterclockwise me-1"></i>Đặt lại</a><button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Áp dụng bộ lọc</button></div>
</form>

<ul class="nav nav-pills log-tabs mb-3" role="tablist">
    <li class="nav-item"><button class="nav-link {{$activeTab==='activity'?'active':''}}" data-bs-toggle="pill" data-bs-target="#activity" data-log-tab="activity"><i class="bi bi-activity me-2"></i>Thao tác quản trị <span>{{$activityCount}}</span></button></li>
    <li class="nav-item"><button class="nav-link {{$activeTab==='login'?'active':''}}" data-bs-toggle="pill" data-bs-target="#login" data-log-tab="login"><i class="bi bi-shield-lock me-2"></i>Đăng nhập <span>{{$loginCount}}</span></button></li>
</ul>

<div class="tab-content">
    <div class="tab-pane fade {{$activeTab==='activity'?'show active':''}}" id="activity">
        <div class="card card-soft log-card"><div class="table-responsive"><table class="table table-modern align-middle mb-0"><thead><tr><th>Thời gian</th><th>Người thực hiện</th><th>Phân hệ</th><th>Hành động</th><th>Nội dung và chi tiết</th><th>IP</th></tr></thead><tbody>
        @forelse($activities as $log)
            <tr><td class="text-nowrap"><strong>{{$log->created_at?->format('H:i:s')}}</strong><div class="small text-muted">{{$log->created_at?->format('d/m/Y')}}</div></td><td><div class="log-user"><span>{{mb_strtoupper(mb_substr($log->user?->name?:'H',0,1))}}</span><div><strong>{{$log->user?->name?:'Hệ thống'}}</strong><small>{{$log->user?->email?:'Tác vụ tự động'}}</small></div></div></td><td><span class="log-module">{{$moduleLabels[$log->module]??str_replace('_',' ',$log->module)}}</span></td><td><span class="log-action log-action-{{$log->action}}">{{$actionLabels[$log->action]??str_replace('_',' ',$log->action)}}</span></td><td class="log-description"><strong>{{$log->description}}</strong>@if($log->subject_type||$log->old_values||$log->new_values||$log->user_agent)<details><summary>Xem thông tin kỹ thuật</summary><div class="log-detail-grid"><div><span>Đối tượng</span><code>{{$log->subject_type ? class_basename($log->subject_type) : 'Không có'}} @if($log->subject_id)#{{$log->subject_id}}@endif</code></div><div><span>Thiết bị</span><small>{{$log->user_agent?:'Không ghi nhận'}}</small></div></div>@if($log->old_values||$log->new_values)<div class="log-change-grid"><div><span>Dữ liệu trước</span><pre>{{json_encode($log->old_values,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)}}</pre></div><div><span>Dữ liệu sau</span><pre>{{json_encode($log->new_values,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)}}</pre></div></div>@endif</details>@endif</td><td class="text-nowrap"><code>{{$log->ip_address?:'—'}}</code></td></tr>
        @empty<tr><td colspan="6"><div class="empty-state">Không có thao tác trong kỳ đã chọn.</div></td></tr>@endforelse
        </tbody></table></div><div class="card-footer bg-white border-0 p-3">{{$activities->appends(['tab'=>'activity'])->links()}}</div></div>
    </div>

    <div class="tab-pane fade {{$activeTab==='login'?'show active':''}}" id="login">
        <div class="card card-soft log-card"><div class="table-responsive"><table class="table table-modern align-middle mb-0"><thead><tr><th>Thời gian</th><th>Tài khoản</th><th>Sự kiện</th><th>Kết quả</th><th>Địa chỉ IP</th><th>Thiết bị / trình duyệt</th></tr></thead><tbody>
        @forelse($logins as $log)
            <tr><td class="text-nowrap"><strong>{{$log->created_at?->format('H:i:s')}}</strong><div class="small text-muted">{{$log->created_at?->format('d/m/Y')}}</div></td><td><strong>{{$log->user?->name?:'Không xác định'}}</strong><div class="small text-muted">{{$log->email}}</div></td><td>{{$actionLabels[$log->event]??$log->event}}</td><td>@if($log->success)<span class="badge-soft badge-success"><i class="bi bi-check-circle me-1"></i>Thành công</span>@else<span class="badge-soft badge-danger"><i class="bi bi-x-circle me-1"></i>Thất bại</span>@endif</td><td><code>{{$log->ip_address?:'—'}}</code></td><td class="log-device" title="{{$log->user_agent}}">{{$log->user_agent?:'Không ghi nhận thiết bị'}}</td></tr>
        @empty<tr><td colspan="6"><div class="empty-state">Không có lượt đăng nhập trong kỳ đã chọn.</div></td></tr>@endforelse
        </tbody></table></div><div class="card-footer bg-white border-0 p-3">{{$logins->appends(['tab'=>'login'])->links()}}</div></div>
    </div>
</div>
@endsection

@push('scripts')
<script>document.querySelectorAll('[data-log-tab]').forEach(function(button){button.addEventListener('shown.bs.tab',function(){const input=document.querySelector('[data-log-tab-input]');if(input)input.value=button.dataset.logTab;});});</script>
@endpush