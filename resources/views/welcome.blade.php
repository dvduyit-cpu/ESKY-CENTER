@extends('layouts.app')
@section('title','Chào mừng')
@section('header','Chào mừng')
@section('content')
<div class="welcome-hero welcome-hero-simple">
    <div>
        <span class="welcome-kicker"><i class="bi bi-sun-fill"></i> {{ now()->hour < 12 ? 'Chào buổi sáng' : (now()->hour < 18 ? 'Chào buổi chiều' : 'Chào buổi tối') }}</span>
        <h1>Xin chào, {{ auth()->user()->name }}!</h1>
        <p>Chúc bạn một ngày làm việc hiệu quả cùng {{ $systemName }}.</p>
    </div>
    <div class="welcome-date"><strong>{{ now()->format('d') }}</strong><span>Tháng {{ now()->format('m, Y') }}</span></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card card-soft welcome-stat"><span class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-calendar2-day"></i></span><div><strong>{{ $todayCount }}</strong><small>Kế hoạch hôm nay</small></div></div></div>
    <div class="col-md-4"><div class="card card-soft welcome-stat"><span class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-clock"></i></span><div><strong>{{ $upcomingCount }}</strong><small>Sắp tới trong 7 ngày</small></div></div></div>
    <div class="col-md-4"><div class="card card-soft welcome-stat"><span class="stat-icon bg-danger-subtle text-danger"><i class="bi bi-exclamation-circle"></i></span><div><strong>{{ $overdueCount }}</strong><small>Kế hoạch quá hạn</small></div></div></div>
</div>

<div class="row g-4">
    <div class="col-xl-8">
        <section class="card card-soft h-100">
            <div class="card-body p-4">
                <div class="section-heading"><span><i class="bi bi-lightning-charge-fill"></i></span><div><h5>Truy cập nhanh</h5><small>Các chức năng thường xuyên sử dụng</small></div></div>
                <div class="welcome-shortcuts">
                    <a href="{{ route('plans.index') }}"><i class="bi bi-calendar2-week-fill"></i><span><strong>Kế hoạch</strong><small>Lịch và nhắc việc cá nhân</small></span></a>
                    <a href="{{ route('dashboard') }}"><i class="bi bi-grid-1x2-fill"></i><span><strong>Tổng quan</strong><small>Số liệu toàn hệ thống</small></span></a>
                    @if(auth()->user()->allowed('language_students'))<a href="{{ route('language-students.index') }}"><i class="bi bi-mortarboard-fill"></i><span><strong>Học viên</strong><small>Quản lý hồ sơ học viên</small></span></a>@endif
                    @if(auth()->user()->allowed('language_tuition'))<a href="{{ route('language-tuition.index') }}"><i class="bi bi-cash-coin"></i><span><strong>Học phí</strong><small>Khoản thu và phiếu thu</small></span></a>@endif
                    <a href="{{ route('guide') }}"><i class="bi bi-question-circle-fill"></i><span><strong>Hướng dẫn</strong><small>Cách sử dụng phần mềm</small></span></a>
                </div>
            </div>
        </section>
    </div>
    <div class="col-xl-4">
        <section class="card card-soft h-100">
            <div class="card-body p-4">
                <div class="section-heading mb-3"><span><i class="bi bi-bell-fill"></i></span><div><h5>Kế hoạch gần nhất</h5><small>Những việc cần chuẩn bị</small></div></div>
                <div class="upcoming-list">
                    @forelse($nextPlans as $plan)
                    <a class="upcoming-item priority-{{ $plan->priority }} text-decoration-none text-dark" href="{{ route('plans.index',['month'=>$plan->scheduled_for->format('Y-m')]) }}">
                        <div class="upcoming-time"><strong>{{ $plan->scheduled_for->format('d/m') }}</strong><span>{{ $plan->scheduled_for->format('H:i') }}</span></div>
                        <div class="upcoming-copy"><strong>{{ $plan->title }}</strong><small>{{ $plan->scheduled_for->diffForHumans() }}</small></div><i class="bi bi-chevron-right text-muted"></i>
                    </a>
                    @empty<div class="empty-plan"><i class="bi bi-calendar2-check"></i><p>Chưa có kế hoạch sắp tới.</p></div>@endforelse
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
