@extends('layouts.app')
@section('title','Kế hoạch')
@section('header','Kế hoạch & lịch')
@section('content')
@php
    $weekdays = ['Thứ 2','Thứ 3','Thứ 4','Thứ 5','Thứ 6','Thứ 7','Chủ nhật'];
    $priorityLabels = ['low'=>'Thấp','normal'=>'Bình thường','high'=>'Quan trọng'];
@endphp
<section class="mb-4"><div class="row g-3">
    @foreach([['Tổng kế hoạch',$planStats['total'],'primary','bi-calendar3',''],['Sắp tới',$planStats['upcoming'],'info','bi-clock','upcoming'],['Đã hoàn thành',$planStats['completed'],'success','bi-check2-circle','completed'],['Quá hạn',$planStats['overdue'],'danger','bi-exclamation-triangle','overdue']] as [$label,$value,$color,$icon,$status])
    <div class="col-6 col-xl-3"><a class="text-decoration-none text-reset" href="{{route('plans.index',array_filter(['status'=>$status]))}}#plan-history"><div class="card card-soft stat-card h-100"><div class="card-body p-4"><div class="d-flex justify-content-between"><div><div class="stat-label">{{$label}}</div><div class="stat-value text-{{$color}}">{{number_format($value)}}</div></div><div class="stat-icon bg-{{$color}}-subtle text-{{$color}}"><i class="bi {{$icon}}"></i></div></div></div></div></a></div>
    @endforeach
</div></section>
<div class="welcome-grid plan-calendar-layout">
    <section class="card card-soft calendar-card">
        <div class="calendar-header">
            <div><h5>Lịch tháng {{ $current->format('m/Y') }}</h5><small>Chọn kế hoạch trong ngày để xem nội dung</small></div>
            <div class="calendar-nav">
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createPlanModal"><i class="bi bi-plus-lg me-1"></i>Thêm kế hoạch</button>
                <a class="btn btn-sm btn-light" href="{{ route('plans.index',['month'=>$current->copy()->subMonth()->format('Y-m')]) }}" title="Tháng trước" aria-label="Tháng trước"><i class="bi bi-chevron-left"></i></a>
                <a class="btn btn-sm btn-outline-primary" href="{{ route('plans.index') }}">Hôm nay</a>
                <a class="btn btn-sm btn-light" href="{{ route('plans.index',['month'=>$current->copy()->addMonth()->format('Y-m')]) }}" title="Tháng sau" aria-label="Tháng sau"><i class="bi bi-chevron-right"></i></a>
            </div>
        </div>
        <div class="calendar-scroll">
            <div class="calendar-grid calendar-weekdays">
                @foreach($weekdays as $weekday)<div>{{ $weekday }}</div>@endforeach
            </div>
            <div class="calendar-grid calendar-days">
                @foreach($calendarDays as $day)
                    @php $dayPlans = $plansByDate->get($day->format('Y-m-d'), collect()); @endphp
                    <div class="calendar-day {{ !$day->isSameMonth($current) ? 'outside' : '' }} {{ $day->isToday() ? 'today' : '' }}">
                        <span class="day-number">{{ $day->day }}</span>
                        <div class="day-plans">
                            @foreach($dayPlans->take(3) as $plan)
                                <button type="button" class="calendar-plan priority-{{ $plan->priority }} {{ $plan->completed_at ? 'completed' : '' }}" data-bs-toggle="modal" data-bs-target="#planDetail{{ $plan->id }}" title="{{ $plan->title }}">
                                    <span>{{ $plan->scheduled_for->format('H:i') }}</span> {{ $plan->title }}
                                </button>
                            @endforeach
                            @if($dayPlans->count()>3)<small>+{{ $dayPlans->count()-3 }} kế hoạch khác</small>@endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    <aside class="today-agenda card card-soft">
        <div class="today-agenda-head"><div><span>{{now()->translatedFormat('l')}}</span><strong>{{now()->format('d/m/Y')}}</strong></div><div class="current-time"><i class="bi bi-clock"></i><strong data-current-time>{{now()->format('H:i')}}</strong></div></div>
        <div class="today-agenda-title"><div><h5>Lịch hôm nay</h5><small>{{$todayPlans->count()}} công việc</small></div><button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createPlanModal" title="Thêm kế hoạch" aria-label="Thêm kế hoạch"><i class="bi bi-plus-lg"></i></button></div>
        <div class="today-timeline">@forelse($todayPlans as $plan)<button class="today-plan {{$plan->completed_at?'is-completed':($plan->scheduled_for->isPast()?'is-past':'')}}" data-bs-toggle="modal" data-bs-target="#planDetail{{$plan->id}}"><span class="today-plan-time">{{$plan->scheduled_for->format('H:i')}}</span><span class="today-plan-dot"></span><span class="today-plan-copy"><strong>{{$plan->title}}</strong><small>{{$plan->completed_at?'Đã hoàn thành':($plan->scheduled_for->isPast()?'Đã quá giờ':$priorityLabels[$plan->priority])}}</small></span></button>@empty<div class="today-empty"><i class="bi bi-calendar2-check"></i><strong>Hôm nay chưa có lịch</strong><small>Thêm kế hoạch để bắt đầu.</small></div>@endforelse</div>
        <div class="today-agenda-foot"><span><i class="bi bi-check-circle text-success"></i> {{$todayPlans->whereNotNull('completed_at')->count()}} hoàn thành</span><span><i class="bi bi-hourglass-split text-warning"></i> {{$todayPlans->whereNull('completed_at')->count()}} còn lại</span></div>
    </aside>
</div>

<div class="modal fade" id="createPlanModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content border-0"><div class="modal-header"><div><h5 class="modal-title">Thêm kế hoạch</h5><small class="text-muted">Hệ thống sẽ nhắc trước thời hạn</small></div><button class="btn-close" data-bs-dismiss="modal"></button></div><form method="POST" action="{{route('plans.store')}}">@csrf<div class="modal-body"><div class="row g-3"><div class="col-md-7"><label class="form-label">Tên kế hoạch</label><input class="form-control" name="title" value="{{old('title')}}" maxlength="180" required></div><div class="col-md-5"><label class="form-label">Ngày giờ thực hiện</label><input class="form-control" type="datetime-local" name="scheduled_for" value="{{old('scheduled_for',now()->addDay()->format('Y-m-d\TH:i'))}}" required></div><div class="col-md-3"><label class="form-label">Ưu tiên</label><select class="form-select" name="priority"><option value="normal">Bình thường</option><option value="high">Quan trọng</option><option value="low">Thấp</option></select></div><div class="col-md-3"><label class="form-label">Nhắc trước</label><select class="form-select" name="reminder_days">@foreach([0,1,2,3,7,14,30] as $days)<option value="{{$days}}" @selected((int)old('reminder_days',1)===$days)>{{$days===0?'Đúng ngày':$days.' ngày'}}</option>@endforeach</select></div><div class="col-md-3"><label class="form-label">Lặp lại</label><select class="form-select" name="repeat_type" data-repeat-type><option value="none">Không lặp</option><option value="weekly">Hàng tuần</option><option value="monthly">Hàng tháng</option></select></div><div class="col-md-3"><label class="form-label">Số kỳ</label><input class="form-control" type="number" name="repeat_count" value="{{old('repeat_count',12)}}" min="1" max="60" data-repeat-count></div><div class="col-12"><label class="form-label">Ghi chú</label><textarea class="form-control" name="note" rows="3" maxlength="2000">{{old('note')}}</textarea></div></div></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button><button class="btn btn-primary"><i class="bi bi-floppy me-1"></i>Lưu kế hoạch</button></div></form></div></div></div>

<section id="plan-history" class="mt-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3"><div><h5 class="mb-1">Danh sách kế hoạch</h5><small class="text-muted">Hiển thị {{ $historyPlans->firstItem() ?? 0 }}–{{ $historyPlans->lastItem() ?? 0 }} trong {{ $historyPlans->total() }} công việc phù hợp</small></div></div>
    <form method="GET" action="{{route('plans.index')}}#plan-history" class="filter-panel row g-3 align-items-end mb-3">
        <input type="hidden" name="month" value="{{$current->format('Y-m')}}">
        <div class="col-md-6 col-xl-3"><label class="form-label">Tìm kiếm</label><input class="form-control" name="q" value="{{request('q')}}" placeholder="Tên hoặc ghi chú"></div>
        <div class="col-md-6 col-xl-2"><label class="form-label">Trạng thái</label><select class="form-select" name="status"><option value="">Tất cả</option><option value="upcoming" @selected(request('status')==='upcoming')>Sắp tới</option><option value="completed" @selected(request('status')==='completed')>Đã hoàn thành</option><option value="overdue" @selected(request('status')==='overdue')>Quá hạn</option></select></div>
        <div class="col-md-4 col-xl-2"><label class="form-label">Ưu tiên</label><select class="form-select" name="priority"><option value="">Tất cả</option>@foreach($priorityLabels as $value=>$label)<option value="{{$value}}" @selected(request('priority')===$value)>{{$label}}</option>@endforeach</select></div>
        <div class="col-md-4 col-xl-2"><label class="form-label">Từ ngày</label><input class="form-control" type="date" name="from" value="{{request('from')}}"></div>
        <div class="col-md-4 col-xl-2"><label class="form-label">Đến ngày</label><input class="form-control" type="date" name="to" value="{{request('to')}}"></div>
        <div class="col-xl-1 d-flex gap-2"><button class="btn btn-primary" title="Lọc" aria-label="Lọc"><i class="bi bi-funnel"></i></button><a class="btn btn-light" href="{{route('plans.index',['month'=>$current->format('Y-m')])}}#plan-history" title="Đặt lại" aria-label="Đặt lại"><i class="bi bi-arrow-counterclockwise"></i></a></div>
    </form>
    <div class="card card-soft overflow-hidden"><div class="table-responsive"><table class="table table-modern align-middle mb-0"><thead><tr><th>Công việc</th><th>Thời gian</th><th>Ưu tiên</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr></thead><tbody>
        @forelse($historyPlans as $plan)<tr><td><strong>{{$plan->title}}</strong>@if($plan->note)<small class="d-block text-muted text-truncate" style="max-width:420px">{{$plan->note}}</small>@endif</td><td>{{$plan->scheduled_for->format('H:i d/m/Y')}}</td><td><span class="badge-soft {{$plan->priority==='high'?'badge-danger':($plan->priority==='low'?'badge-gray':'badge-info')}}">{{$priorityLabels[$plan->priority]}}</span></td><td><span class="badge-soft {{$plan->completed_at?'badge-success':($plan->scheduled_for->isPast()?'badge-danger':'badge-warning')}}">{{$plan->completed_at?'Đã hoàn thành':($plan->scheduled_for->isPast()?'Quá hạn':'Sắp tới')}}</span></td><td class="text-end"><div class="d-inline-flex gap-1"><button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#planDetail{{$plan->id}}" title="Xem" aria-label="Xem"><i class="bi bi-eye"></i></button><button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#planEdit{{$plan->id}}" title="Sửa" aria-label="Sửa"><i class="bi bi-pencil"></i></button><form method="POST" action="{{route('plans.destroy',$plan)}}" data-confirm="Xóa kế hoạch này?"><button class="btn btn-sm btn-outline-danger" title="Xóa" aria-label="Xóa">@csrf @method('DELETE')<i class="bi bi-trash"></i></button></form></div></td></tr>@empty<tr><td colspan="5" class="empty-state">Không có kế hoạch phù hợp với bộ lọc.</td></tr>@endforelse
    </tbody></table></div></div>
    <div class="mt-3">{{$historyPlans->links()}}</div>
</section>

@foreach($modalPlans as $plan)
<div class="modal fade" id="planDetail{{ $plan->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 rounded-4 shadow">
        <div class="modal-header"><div><h5 class="modal-title">{{ $plan->title }}</h5><small class="text-muted">{{ $plan->scheduled_for->format('H:i, d/m/Y') }}</small></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">@if($plan->assignedBy && $plan->assigned_by_id !== $plan->user_id)<div class="small text-muted mb-3"><i class="bi bi-person-check me-1"></i>Task được giao bởi <strong>{{ $plan->assignedBy->name }}</strong></div>@endif<span class="badge-soft {{ $plan->priority==='high'?'badge-danger':($plan->priority==='low'?'badge-gray':'badge-info') }}">{{ $priorityLabels[$plan->priority] }}</span><p class="mt-3 mb-0 text-secondary">{!! nl2br(e($plan->note ?: 'Không có ghi chú.')) !!}</p></div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-primary" data-switch-modal="#planEdit{{ $plan->id }}"><i class="bi bi-pencil me-1"></i>Chỉnh sửa</button>
            <form method="POST" action="{{ route('plans.toggle',$plan) }}">@csrf @method('PATCH')<button class="btn btn-outline-success"><i class="bi bi-check2-circle me-1"></i>{{ $plan->completed_at?'Mở lại':'Hoàn thành' }}</button></form>
            <form method="POST" action="{{ route('plans.destroy',$plan) }}" data-confirm="Xóa kế hoạch này?">@csrf @method('DELETE')<button class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>Xóa</button></form>
        </div>
    </div></div>
</div>
<div class="modal fade" id="planEdit{{ $plan->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content border-0 rounded-4 shadow">
        <form method="POST" action="{{ route('plans.update',$plan) }}">@csrf @method('PUT')
            <div class="modal-header"><h5 class="modal-title">Chỉnh sửa kế hoạch</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><div class="row g-3">
                <div class="col-md-7"><label class="form-label">Tên kế hoạch</label><input class="form-control" name="title" value="{{ $plan->title }}" maxlength="180" required></div>
                <div class="col-md-5"><label class="form-label">Ngày giờ thực hiện</label><input class="form-control" type="datetime-local" name="scheduled_for" value="{{ $plan->scheduled_for->format('Y-m-d\TH:i') }}" required></div>
                <div class="col-md-3"><label class="form-label">Ưu tiên</label><select class="form-select" name="priority"><option value="normal" @selected($plan->priority==='normal')>Bình thường</option><option value="high" @selected($plan->priority==='high')>Quan trọng</option><option value="low" @selected($plan->priority==='low')>Thấp</option></select></div>
                <div class="col-md-3"><label class="form-label">Nhắc trước</label><select class="form-select" name="reminder_days">@foreach([0,1,2,3,7,14,30] as $days)<option value="{{ $days }}" @selected($plan->reminder_days===$days)>{{ $days===0?'Đúng ngày':$days.' ngày' }}</option>@endforeach</select></div>
                <div class="col-md-3"><label class="form-label">Lặp lại</label><select class="form-select" name="repeat_type" data-repeat-type><option value="none">Không lặp</option><option value="weekly">Hàng tuần</option><option value="monthly">Hàng tháng</option></select></div>
                <div class="col-md-3"><label class="form-label">Số kỳ</label><input class="form-control" type="number" name="repeat_count" value="12" min="1" max="60" data-repeat-count></div>
                <div class="col-12"><label class="form-label">Ghi chú</label><textarea class="form-control" name="note" rows="3" maxlength="2000">{{ $plan->note }}</textarea><small class="text-muted">Nếu chọn lặp lại, kỳ hiện tại được sửa và các kỳ tiếp theo sẽ được tạo mới.</small></div>
            </div></div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Hủy</button><button class="btn btn-primary"><i class="bi bi-save me-1"></i>Lưu thay đổi</button></div>
        </form>
    </div></div>
</div>
@endforeach
@endsection
@push('scripts')<script>document.addEventListener('DOMContentLoaded',()=>{document.querySelectorAll('[data-repeat-type]').forEach(type=>{const count=type.closest('.row').querySelector('[data-repeat-count]'),sync=()=>{count.disabled=type.value==='none';if(type.value==='monthly'&&(!count.value||count.value==='1'))count.value=12};type.addEventListener('change',sync);sync()});document.querySelectorAll('[data-switch-modal]').forEach(button=>button.addEventListener('click',()=>{const current=button.closest('.modal'),target=document.querySelector(button.dataset.switchModal);if(!current||!target)return;current.addEventListener('hidden.bs.modal',()=>bootstrap.Modal.getOrCreateInstance(target).show(),{once:true});bootstrap.Modal.getOrCreateInstance(current).hide()}));document.querySelectorAll('.modal').forEach(modal=>modal.addEventListener('show.bs.modal',event=>{document.querySelectorAll('.modal.show').forEach(open=>{if(open!==event.target)bootstrap.Modal.getOrCreateInstance(open).hide()})}));const clock=document.querySelector('[data-current-time]');if(clock)setInterval(()=>clock.textContent=new Date().toLocaleTimeString('vi-VN',{hour:'2-digit',minute:'2-digit'}),30000);@if($errors->any())bootstrap.Modal.getOrCreateInstance(document.getElementById('createPlanModal')).show();@endif});</script>@endpush
