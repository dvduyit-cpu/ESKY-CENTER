@extends('layouts.app')
@section('title','Kế hoạch')
@section('header','Kế hoạch & lịch')
@section('content')
@php
    $weekdays = ['Thứ 2','Thứ 3','Thứ 4','Thứ 5','Thứ 6','Thứ 7','Chủ nhật'];
    $priorityLabels = ['low'=>'Thấp','normal'=>'Bình thường','high'=>'Quan trọng'];
@endphp
<div class="welcome-hero">
    <div>
        <span class="welcome-kicker"><i class="bi bi-sun-fill"></i> {{ now()->hour < 12 ? 'Chào buổi sáng' : (now()->hour < 18 ? 'Chào buổi chiều' : 'Chào buổi tối') }}</span>
        <h1>Xin chào, {{ auth()->user()->name }}!</h1>
        <p>Chúc bạn một ngày làm việc hiệu quả. Hãy theo dõi lịch và những kế hoạch sắp tới ngay tại đây.</p>
    </div>
    <div class="welcome-date"><strong>{{ now()->format('d') }}</strong><span>Tháng {{ now()->format('m, Y') }}</span></div>
</div>

<div class="welcome-grid">
    <section class="card card-soft calendar-card">
        <div class="calendar-header">
            <div><h5>Lịch tháng {{ $current->format('m/Y') }}</h5><small>Chọn kế hoạch trong ngày để xem nội dung</small></div>
            <div class="calendar-nav">
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

    <aside class="welcome-side">
        <section class="card card-soft plan-form-card">
            <div class="card-body">
                <div class="section-heading"><span><i class="bi bi-journal-plus"></i></span><div><h5>Thêm kế hoạch</h5><small>Hệ thống sẽ nhắc trước thời hạn</small></div></div>
                <form method="POST" action="{{ route('plans.store') }}" class="plan-form">@csrf
<div><label class="form-label">Tên kế hoạch</label><input class="form-control" name="title" value="{{ old('title') }}" placeholder="Ví dụ: Họp giáo viên..." maxlength="180" required></div>
                    <div><label class="form-label">Ngày giờ thực hiện</label><input class="form-control" type="datetime-local" name="scheduled_for" value="{{ old('scheduled_for',now()->addDay()->format('Y-m-d\TH:i')) }}" required></div>
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label">Ưu tiên</label><select class="form-select" name="priority"><option value="normal">Bình thường</option><option value="high">Quan trọng</option><option value="low">Thấp</option></select></div>
                        <div class="col-6"><label class="form-label">Nhắc trước</label><select class="form-select" name="reminder_days">@foreach([0,1,2,3,7,14,30] as $days)<option value="{{ $days }}" @selected($days==1)>{{ $days==0?'Đúng ngày':$days.' ngày' }}</option>@endforeach</select></div>
                    </div>
                    <div><label class="form-label">Lặp lại lịch hẹn</label><select class="form-select" name="repeat_weeks">
                        <option value="1" @selected(old('repeat_weeks','1')==='1')>Không lặp</option>
                        <option value="4" @selected(old('repeat_weeks')==='4')>Hàng tuần — 4 tuần</option>
                        <option value="8" @selected(old('repeat_weeks')==='8')>Hàng tuần — 8 tuần</option>
                        <option value="12" @selected(old('repeat_weeks')==='12')>Hàng tuần — 12 tuần</option>
                        <option value="24" @selected(old('repeat_weeks')==='24')>Hàng tuần — 24 tuần</option>
                        <option value="52" @selected(old('repeat_weeks')==='52')>Hàng tuần — 52 tuần</option>
                    </select><small class="form-text text-muted">Mỗi tuần có lịch và thông báo riêng.</small></div>
                    <div><label class="form-label">Ghi chú</label><textarea class="form-control" name="note" rows="3" maxlength="2000" placeholder="Nội dung cần chuẩn bị...">{{ old('note') }}</textarea></div>
                    <button class="btn btn-primary w-100" type="submit"><i class="bi bi-bell-fill me-2"></i>Lưu và nhắc việc</button>
                </form>
            </div>
        </section>

        <section class="card card-soft upcoming-card">
            <div class="card-body">
                <div class="section-heading mb-3"><span><i class="bi bi-clock-history"></i></span><div><h5>Sắp tới</h5><small>{{ $upcoming->count() }} kế hoạch gần nhất</small></div></div>
                <div class="upcoming-list">
                    @forelse($upcoming as $plan)
                        <div class="upcoming-item priority-{{ $plan->priority }}">
                            <div class="upcoming-time"><strong>{{ $plan->scheduled_for->format('d/m') }}</strong><span>{{ $plan->scheduled_for->format('H:i') }}</span></div>
                            <div class="upcoming-copy"><strong>{{ $plan->title }}</strong><small>{{ $plan->scheduled_for->diffForHumans() }}</small></div>
                            <form method="POST" action="{{ route('plans.toggle',$plan) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success" title="Hoàn thành" aria-label="Hoàn thành"><i class="bi bi-check2"></i></button></form>
                        </div>
                    @empty
                        <div class="empty-plan"><i class="bi bi-calendar2-check"></i><p>Chưa có kế hoạch sắp tới.</p></div>
                    @endforelse
                </div>
            </div>
        </section>
</aside>
</div>

@foreach($plansByDate->flatten() as $plan)
<div class="modal fade" id="planDetail{{ $plan->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-0 rounded-4 shadow">
        <div class="modal-header"><div><h5 class="modal-title">{{ $plan->title }}</h5><small class="text-muted">{{ $plan->scheduled_for->format('H:i, d/m/Y') }}</small></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">@if($plan->assignedBy && $plan->assigned_by_id !== $plan->user_id)<div class="small text-muted mb-3"><i class="bi bi-person-check me-1"></i>Task được giao bởi <strong>{{ $plan->assignedBy->name }}</strong></div>@endif<span class="badge-soft {{ $plan->priority==='high'?'badge-danger':($plan->priority==='low'?'badge-gray':'badge-info') }}">{{ $priorityLabels[$plan->priority] }}</span><p class="mt-3 mb-0 text-secondary">{!! nl2br(e($plan->note ?: 'Không có ghi chú.')) !!}</p></div>
        <div class="modal-footer">
            <form method="POST" action="{{ route('plans.toggle',$plan) }}">@csrf @method('PATCH')<button class="btn btn-outline-success"><i class="bi bi-check2-circle me-1"></i>{{ $plan->completed_at?'Mở lại':'Hoàn thành' }}</button></form>
            <form method="POST" action="{{ route('plans.destroy',$plan) }}" data-confirm="Xóa kế hoạch này?">@csrf @method('DELETE')<button class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>Xóa</button></form>
        </div>
    </div></div>
</div>
@endforeach
@endsection
