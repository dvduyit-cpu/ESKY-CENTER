<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
<title>Đăng nhập · {{ $systemName }}</title>
<link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
<link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
<link href="{{ asset('css/login.css') }}?v={{ filemtime(public_path('css/login.css')) }}" rel="stylesheet">
</head>
<body data-theme="{{ $systemTheme }}" data-loading-style="{{ $systemLoadingStyle }}">
<main class="login-shell"><section class="login-panel" aria-labelledby="login-title">
<div class="login-intro">
<div class="login-brand"><span class="login-logo @if($systemLogo) has-logo @endif">@if($systemLogo)<img src="{{ asset($systemLogo) }}" alt="Logo">@else<i class="bi bi-mortarboard-fill"></i>@endif</span><span><strong>{{ $systemName }}</strong><small>Hệ thống quản lý trung tâm</small></span></div>
<div class="intro-content"><span class="intro-badge"><i class="bi bi-stars"></i> Quản lý tập trung, vận hành hiệu quả</span><h1>Mọi hoạt động của trung tâm trong một hệ thống.</h1><p>Quản lý tuyển sinh, học viên, lớp học và chỉ tiêu trên một nền tảng thống nhất.</p><div class="intro-modules"><span><i class="bi bi-person-plus-fill"></i>Tuyển sinh</span><span><i class="bi bi-mortarboard-fill"></i>Đào tạo</span><span><i class="bi bi-cash-coin"></i>Học phí</span><span><i class="bi bi-bullseye"></i>KPI & Báo cáo</span></div>
<div class="intro-features"><div><i class="bi bi-people-fill"></i><span><strong>Quản lý học viên</strong><small>Thông tin rõ ràng, dễ tra cứu</small></span></div><div><i class="bi bi-graph-up-arrow"></i><span><strong>Theo dõi chỉ tiêu</strong><small>Số liệu cập nhật và trực quan</small></span></div><div><i class="bi bi-shield-check"></i><span><strong>Phân quyền an toàn</strong><small>Kiểm soát quyền theo vai trò</small></span></div></div></div>
<p class="intro-footer">{!! $systemCopyright !!}</p></div>
<div class="login-form-panel"><div class="login-form-wrap">
<div class="mobile-brand"><span class="login-logo @if($systemLogo) has-logo @endif">@if($systemLogo)<img src="{{ asset($systemLogo) }}" alt="Logo">@else<i class="bi bi-mortarboard-fill"></i>@endif</span><span><strong>{{ $systemName }}</strong><small>Hệ thống quản lý trung tâm</small></span></div>
<header class="form-heading"><span>Chào mừng trở lại</span><h2 id="login-title">Đăng nhập hệ thống</h2><p>Nhập thông tin tài khoản được quản trị viên cấp.</p></header>
@if($errors->any())<div class="login-alert" role="alert" data-auto-dismiss="5000"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $errors->first() }}</span></div>@endif
<form method="POST" action="{{ route('login.submit') }}" class="login-form">@csrf
<div class="form-field"><label for="email">Địa chỉ email</label><div class="field-control @error('email') is-invalid @enderror"><i class="bi bi-envelope"></i><input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="name@example.com" autocomplete="username" required autofocus></div></div>
<div class="form-field"><label for="password">Mật khẩu</label><div class="field-control"><i class="bi bi-lock"></i><input id="password" type="password" name="password" placeholder="Nhập mật khẩu" autocomplete="current-password" required><button type="button" class="password-toggle" data-password-toggle aria-label="Hiện mật khẩu"><i class="bi bi-eye"></i></button></div></div>
<label class="remember-option" for="remember"><input id="remember" type="checkbox" name="remember" value="1" @checked(old('remember'))><span>Ghi nhớ đăng nhập trên thiết bị này</span></label>
<button class="login-submit" type="submit"><span>Đăng nhập</span><i class="bi bi-arrow-right"></i></button></form>
<p class="login-help"><i class="bi bi-info-circle"></i> Liên hệ quản trị viên nếu bạn không thể đăng nhập.</p><p class="mobile-copyright">{!! $systemCopyright !!}</p></div></div>
</section></main>
<div class="page-loading-overlay" data-page-loading aria-hidden="true" aria-live="polite"><div class="page-loading-card"><span class="page-loading-spinner" aria-hidden="true"></span><span>Đang tải dữ liệu...</span></div></div>
<script src="{{ asset('js/page-loading.js') }}?v={{ filemtime(public_path('js/page-loading.js')) }}"></script>
<script src="{{ asset('js/auto-dismiss-alerts.js') }}"></script><script>document.querySelector('[data-password-toggle]')?.addEventListener('click',function(){const i=document.getElementById('password'),v=i.type==='text';i.type=v?'password':'text';this.setAttribute('aria-label',v?'Hiện mật khẩu':'Ẩn mật khẩu');this.querySelector('i').className=v?'bi bi-eye':'bi bi-eye-slash'});</script>
</body></html>