<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $configuredLogo = $systemLogo && is_file(public_path($systemLogo)) ? $systemLogo : null;
        $faviconPath = $configuredLogo ?: 'uploads/branding/logo-20260722101948.png';
    @endphp
    <link rel="icon" href="{{ asset($faviconPath) }}?v={{ is_file(public_path($faviconPath)) ? filemtime(public_path($faviconPath)) : 1 }}">
    <link rel="apple-touch-icon" href="{{ asset($faviconPath) }}?v={{ is_file(public_path($faviconPath)) ? filemtime(public_path($faviconPath)) : 1 }}">
    <title>Đăng nhập · {{ $systemName }}</title>
    <link href="{{ asset('assets/vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/login.css') }}?v={{ filemtime(public_path('css/login.css')) }}" rel="stylesheet">
    <style>.field-control.email-field{grid-template-columns:45px minmax(80px,1fr) auto}.email-domain{padding:0 14px 0 8px;color:#64748b;font-size:14px;font-weight:700;white-space:nowrap}.email-field input{padding-right:4px}</style>
</head>
<body data-theme="{{ $systemTheme }}" data-loading-style="{{ $systemLoadingStyle }}">
<main class="login-shell">
    <section class="login-panel" aria-labelledby="login-title">
        <div class="login-intro">
            <div class="login-brand">
                <span class="login-logo has-logo"><img src="{{ asset($faviconPath) }}" alt="Logo {{ $systemName }}"></span>
                <span><strong>{{ $systemName }}</strong><small>Hệ thống quản lý trung tâm</small></span>
            </div>
            <div class="intro-content">
                <span class="intro-badge"><i class="bi bi-stars"></i> Quản lý tập trung, vận hành hiệu quả</span>
                <h1>Mọi hoạt động của trung tâm trong một hệ thống.</h1>
                <p>Quản lý tuyển sinh, học viên, lớp học, học phí và chỉ tiêu trên một nền tảng thống nhất.</p>
                <div class="intro-modules"><span><i class="bi bi-person-plus-fill"></i>Tuyển sinh</span><span><i class="bi bi-mortarboard-fill"></i>Đào tạo</span><span><i class="bi bi-cash-coin"></i>Học phí</span><span><i class="bi bi-bullseye"></i>KPI & Báo cáo</span></div>
                <div class="intro-features">
                    <div><i class="bi bi-people-fill"></i><span><strong>Quản lý học viên</strong><small>Thông tin rõ ràng, dễ tra cứu</small></span></div>
                    <div><i class="bi bi-graph-up-arrow"></i><span><strong>Theo dõi chỉ tiêu</strong><small>Số liệu cập nhật và trực quan</small></span></div>
                    <div><i class="bi bi-shield-check"></i><span><strong>Phân quyền an toàn</strong><small>Kiểm soát quyền theo vai trò</small></span></div>
                </div>
            </div>
            <p class="intro-footer">{!! $systemCopyright !!}</p>
        </div>

        <div class="login-form-panel">
            <div class="login-form-wrap">
                <header class="form-heading"><span>Chào mừng trở lại</span><h2 id="login-title">Đăng nhập hệ thống</h2><p>Nhập thông tin tài khoản được quản trị viên cấp.</p></header>
                @if($errors->any())<div class="login-alert" role="alert" data-auto-dismiss="5000"><i class="bi bi-exclamation-circle-fill"></i><span>{{ $errors->first() }}</span></div>@endif
                @if(session('warning'))<div class="login-alert" role="alert" data-auto-dismiss="5000"><i class="bi bi-exclamation-triangle-fill"></i><span>{{session('warning')}}</span></div>@endif
                <form method="POST" action="{{ route('login.submit') }}" class="login-form">@csrf
                    <div class="form-field"><label for="email">Địa chỉ email</label><div class="field-control email-field @error('email') is-invalid @enderror"><i class="bi bi-envelope"></i><input id="email" type="text" name="email" value="{{ old('email') }}" placeholder="tên tài khoản" autocomplete="username" inputmode="email" autocapitalize="none" spellcheck="false" required><span class="email-domain">{{ '@'.config('auth.login_email_domain') }}</span></div></div>
                    <div class="form-field"><label for="password">Mật khẩu</label><div class="field-control password-field"><i class="bi bi-lock"></i><input id="password" type="password" name="password" placeholder="Nhập mật khẩu" autocomplete="current-password" required><button type="button" class="password-toggle" data-password-toggle aria-controls="password" aria-pressed="false" aria-label="Hiện mật khẩu" title="Hiện mật khẩu"><i class="bi bi-eye" aria-hidden="true"></i></button></div></div>
                    <label class="remember-option" for="remember"><input id="remember" type="checkbox" name="remember" value="1" @checked(old('remember'))><span>Ghi nhớ đăng nhập trên thiết bị này</span></label>
                    <button class="login-submit" type="submit"><span>Đăng nhập</span><i class="bi bi-arrow-right"></i></button>
                </form>
                <p class="login-help"><i class="bi bi-info-circle"></i> Liên hệ quản trị viên nếu bạn không thể đăng nhập.</p>
                <p class="mobile-copyright">{!! $systemCopyright !!}</p>
            </div>
        </div>
    </section>
</main>
<div class="page-loading-overlay" data-page-loading aria-hidden="true" aria-live="polite"><div class="page-loading-card"><span class="page-loading-spinner" aria-hidden="true"></span><span>Đang tải dữ liệu...</span></div></div>
<script src="{{ asset('js/page-loading.js') }}?v={{ filemtime(public_path('js/page-loading.js')) }}"></script>
<script src="{{ asset('js/auto-dismiss-alerts.js') }}"></script>
<script>document.querySelector('[data-password-toggle]')?.addEventListener('click',function(){const input=document.getElementById(this.getAttribute('aria-controls')),visible=input.type==='text';input.type=visible?'password':'text';const label=visible?'Hiện mật khẩu':'Ẩn mật khẩu';this.setAttribute('aria-label',label);this.setAttribute('title',label);this.setAttribute('aria-pressed',visible?'false':'true');this.querySelector('i').className=visible?'bi bi-eye':'bi bi-eye-slash';input.focus({preventScroll:true})});</script>
</body>
</html>
