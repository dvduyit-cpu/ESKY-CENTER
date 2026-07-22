@extends('layouts.app')
@section('title','Giao task')
@section('header','Giao task')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4"><div><h1 class="page-title">Giao task cho tài khoản</h1><div class="page-subtitle">Giao việc, nhắc hạn và theo dõi lịch sử thực hiện.</div></div><a class="btn btn-outline-primary" href="{{ route('plans.index') }}"><i class="bi bi-calendar2-week me-2"></i>Xem lịch</a></div>

<div class="row g-3 mb-4">
@foreach([['Tổng task',$taskStats['total'],'bi-list-task','primary'],['Đang thực hiện',$taskStats['pending'],'bi-hourglass-split','info'],['Quá hạn',$taskStats['overdue'],'bi-exclamation-triangle','danger'],['Hoàn thành',$taskStats['completed'],'bi-check-circle','success']] as [$label,$value,$icon,$color])
<div class="col-6 col-xl-3"><div class="card card-soft task-stat"><span class="bg-{{$color}}-subtle text-{{$color}}"><i class="bi {{$icon}}"></i></span><div><strong>{{$value}}</strong><small>{{$label}}</small></div></div></div>
@endforeach
</div>

<section class="card card-soft mb-4"><div class="card-body p-4">
<div class="section-heading"><span><i class="bi bi-person-check-fill"></i></span><div><h5>Tạo task mới</h5><small>Người nhận sẽ thấy task trên lịch và chuông thông báo</small></div></div>
<form method="POST" action="{{ route('tasks.store') }}" class="task-create-form">@csrf
<div><label class="form-label">Giao cho</label><select class="form-select" name="assignee_id" required><option value="all">Tất cả tài khoản đang hoạt động</option>@foreach($users as $account)<option value="{{$account->id}}" @selected(old('assignee_id')==$account->id)>{{$account->name}} — {{$account->email}}</option>@endforeach</select></div>
<div><label class="form-label">Tên task</label><input class="form-control" name="title" value="{{old('title')}}" maxlength="180" required placeholder="Nhập công việc cần giao..."></div>
<div><label class="form-label">Thời hạn</label><input class="form-control" type="datetime-local" name="scheduled_for" value="{{old('scheduled_for',now()->addDay()->format('Y-m-d\TH:i'))}}" required></div>
<div><label class="form-label">Ưu tiên</label><select class="form-select" name="priority"><option value="normal">Bình thường</option><option value="high">Quan trọng</option><option value="low">Thấp</option></select></div>
<div><label class="form-label">Nhắc trước</label><select class="form-select" name="reminder_days">@foreach([0,1,2,3,7,14,30] as $days)<option value="{{$days}}" @selected($days==1)>{{$days===0?'Đúng ngày':$days.' ngày'}}</option>@endforeach</select></div>
<div class="task-note-field"><label class="form-label">Nội dung task</label><textarea class="form-control" name="note" rows="3" maxlength="2000" placeholder="Yêu cầu, tài liệu và kết quả cần hoàn thành...">{{old('note')}}</textarea></div>
<div class="task-submit"><button class="btn btn-primary" type="submit"><i class="bi bi-send-fill me-2"></i>Giao task</button></div>
</form>
</div></section>

<div class="task-history-toolbar"><div><h5>Lịch sử giao task</h5><small>Có thể ẩn hoặc hiện bộ lọc và danh sách</small></div><button class="btn btn-outline-primary task-history-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#taskHistorySection" aria-expanded="true" aria-controls="taskHistorySection" data-task-history-toggle><i class="bi bi-chevron-up"></i><span class="ms-2">Ẩn lịch sử</span></button></div>
<div class="collapse show task-history-collapse" id="taskHistorySection">
<section class="filter-panel mb-4"><form method="GET" action="{{route('tasks.index')}}" class="task-filter">
<div><label class="form-label">Tìm kiếm</label><input class="form-control" name="q" value="{{request('q')}}" placeholder="Tên hoặc nội dung task"></div>
<div><label class="form-label">Người nhận</label><select class="form-select" name="user_id"><option value="">Tất cả</option>@foreach($users as $account)<option value="{{$account->id}}" @selected(request('user_id')==$account->id)>{{$account->name}}</option>@endforeach</select></div>
<div><label class="form-label">Trạng thái</label><select class="form-select" name="status"><option value="">Tất cả</option><option value="pending" @selected(request('status')==='pending')>Đang thực hiện</option><option value="overdue" @selected(request('status')==='overdue')>Quá hạn</option><option value="completed" @selected(request('status')==='completed')>Hoàn thành</option></select></div>
<div><label class="form-label">Ưu tiên</label><select class="form-select" name="priority"><option value="">Tất cả</option><option value="high" @selected(request('priority')==='high')>Quan trọng</option><option value="normal" @selected(request('priority')==='normal')>Bình thường</option><option value="low" @selected(request('priority')==='low')>Thấp</option></select></div>
<div><label class="form-label">Từ ngày</label><input class="form-control" type="date" name="from" value="{{request('from')}}"></div>
<div><label class="form-label">Đến ngày</label><input class="form-control" type="date" name="to" value="{{request('to')}}"></div>
<div class="task-filter-actions"><button class="btn btn-primary" type="submit"><i class="bi bi-funnel me-1"></i>Lọc</button><a class="btn btn-light" href="{{route('tasks.index')}}">Đặt lại</a></div>
</form></section>

<section class="card card-soft"><div class="card-header bg-white border-0 px-4 pt-4"><h5 class="mb-1">Lịch sử giao task</h5><small class="text-muted">{{$tasks->total()}} kết quả phù hợp</small></div>
<div class="table-responsive"><table class="table table-modern"><thead><tr><th>#</th><th>Task</th><th>Người nhận</th><th>Thời hạn</th><th>Ưu tiên</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr></thead><tbody>
@forelse($tasks as $task)<tr><td>{{$tasks->firstItem()+$loop->index}}</td><td><strong>{{$task->title}}</strong>@if($task->note)<div class="small text-muted task-note-preview">{{$task->note}}</div>@endif</td><td><span class="d-block fw-semibold">{{$task->user?->name}}</span><small class="text-muted">{{$task->user?->email}}</small></td><td>{{$task->scheduled_for->format('H:i d/m/Y')}}<small class="d-block text-muted">{{$task->scheduled_for->diffForHumans()}}</small></td><td><span class="badge-soft {{$task->priority==='high'?'badge-danger':($task->priority==='low'?'badge-gray':'badge-info')}}">{{['high'=>'Quan trọng','normal'=>'Bình thường','low'=>'Thấp'][$task->priority]}}</span></td><td><span class="badge-soft {{$task->completed_at?'badge-success':($task->scheduled_for->isPast()?'badge-danger':'badge-warning')}}">{{$task->completed_at?'Hoàn thành':($task->scheduled_for->isPast()?'Quá hạn':'Đang thực hiện')}}</span>@if($task->completed_at)<small class="d-block text-muted mt-1">{{$task->completed_at->format('H:i d/m/Y')}}</small>@endif</td><td class="text-end"><div class="d-inline-flex gap-1"><form method="POST" action="{{route('plans.toggle',$task)}}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success" title="{{$task->completed_at?'Mở lại':'Hoàn thành'}}" aria-label="{{$task->completed_at?'Mở lại':'Hoàn thành'}}"><i class="bi {{$task->completed_at?'bi-arrow-counterclockwise':'bi-check2'}}"></i></button></form><form method="POST" action="{{route('plans.destroy',$task)}}" data-confirm="Xóa task này?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Xóa" aria-label="Xóa"><i class="bi bi-trash"></i></button></form></div></td></tr>
@empty<tr><td colspan="7"><div class="empty-state"><i class="bi bi-inbox fs-1"></i><p class="mt-2 mb-0">Chưa có lịch sử giao task phù hợp.</p></div></td></tr>@endforelse
</tbody></table></div><div class="card-footer bg-white border-0">{{$tasks->links()}}</div></section>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const section = document.getElementById('taskHistorySection');
    const button = document.querySelector('[data-task-history-toggle]');
    if (!section || !button) return;

    const updateButton = (visible) => {
        button.setAttribute('aria-expanded', visible ? 'true' : 'false');
        button.querySelector('i').className = visible ? 'bi bi-chevron-up' : 'bi bi-chevron-down';
        button.querySelector('span').textContent = visible ? 'Ẩn lịch sử' : 'Hiện lịch sử';
    };

    const shouldShow = localStorage.getItem('taskHistoryVisible') !== 'false';
    if (!shouldShow) section.classList.remove('show');
    updateButton(shouldShow);

    section.addEventListener('shown.bs.collapse', () => {
        localStorage.setItem('taskHistoryVisible', 'true');
        updateButton(true);
    });
    section.addEventListener('hidden.bs.collapse', () => {
        localStorage.setItem('taskHistoryVisible', 'false');
        updateButton(false);
    });
});
</script>
@endpush

@endsection