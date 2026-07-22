<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $systemName) · {{ $systemName }}</title>
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/theme.css') }}?v={{ filemtime(public_path('css/theme.css')) }}" rel="stylesheet">
</head>
<body data-theme="{{ $systemTheme ?? 'blue' }}">
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-mark">@if($systemLogo)<img src="{{ asset($systemLogo) }}" alt="Logo">@else<i class="bi bi-graph-up-arrow"></i>@endif</div>
            <div><strong>{{ $systemName }}</strong><small>HỆ THỐNG QUẢN LÝ</small></div>
        </div>
        <div class="sidebar-label">Tổng quan</div>
        <nav class="sidebar-nav nav flex-column">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-grid-1x2-fill"></i> {{ auth()->user()->isAdmin() || auth()->user()->allowed('system_dashboard') ? 'Tổng quan toàn hệ thống' : 'Tổng quan cá nhân' }}</a>
        </nav>
        @php
            $me = auth()->user();
        @endphp
        @if($me->allowed('language_consulting') || $me->allowed('language_target_submissions') || $me->allowed('language_leads') || $me->allowed('language_collaborators'))
        <div class="sidebar-label">Tư vấn</div>
        <nav class="sidebar-nav nav flex-column">
            @if($me->allowed('language_consulting'))<a class="nav-link {{ request()->routeIs('language-consulting.*') ? 'active' : '' }}" href="{{ route('language-consulting.index') }}"><i class="bi bi-headset"></i> Công việc tư vấn</a>@endif
            @if($me->allowed('language_target_submissions'))<a class="nav-link {{ request()->routeIs('language-target-submissions.*') ? 'active' : '' }}" href="{{ route('language-target-submissions.index') }}"><i class="bi bi-send-fill"></i> Gửi chỉ tiêu</a>@endif
            @if($me->allowed('language_leads'))<a class="nav-link {{ request()->routeIs('language-leads.*') ? 'active' : '' }}" href="{{ route('language-leads.index') }}"><i class="bi bi-person-plus-fill"></i> Học viên tiềm năng</a>@endif
            @if($me->allowed('language_collaborators'))<a class="nav-link {{ request()->routeIs('language-collaborators.*') ? 'active' : '' }}" href="{{ route('language-collaborators.index') }}"><i class="bi bi-people-fill"></i> Cộng tác viên</a>@endif
        </nav>
        @endif
        @if($me->allowed('language_students') || $me->allowed('language_classes') || $me->allowed('language_tuition') || $me->allowed('language_targets'))
        <div class="sidebar-label">Quản lý học viên</div>
        <nav class="sidebar-nav nav flex-column">
            @if($me->allowed('language_students'))<a class="nav-link {{ request()->routeIs('language-students.*') ? 'active' : '' }}" href="{{ route('language-students.index') }}"><i class="bi bi-mortarboard-fill"></i> Học viên</a>@endif
            @if($me->allowed('language_classes'))<a class="nav-link {{ request()->routeIs('language-classes.*') ? 'active' : '' }}" href="{{ route('language-classes.index') }}"><i class="bi bi-easel2-fill"></i> Lớp học</a>@endif
            @if($me->allowed('language_tuition'))<a class="nav-link {{ request()->routeIs('language-tuition.*') ? 'active' : '' }}" href="{{ route('language-tuition.index') }}"><i class="bi bi-cash-coin"></i> Thu học phí</a>@endif
            @if($me->allowed('language_targets'))<a class="nav-link {{ request()->routeIs('language-targets.*') ? 'active' : '' }}" href="{{ route('language-targets.index') }}"><i class="bi bi-clipboard-data"></i> Chỉ tiêu trung tâm</a>@endif
        </nav>
        @endif
        @if($me->isAdmin() || $me->allowed('language_programs') || $me->allowed('language_courses') || $me->allowed('language_discounts') || $me->allowed('language_dashboard_all'))
        <div class="sidebar-label">Quản lý trung tâm</div>
        <nav class="sidebar-nav nav flex-column">
            <a class="nav-link {{ request()->routeIs('language-dashboard.*') ? 'active' : '' }}" href="{{ route('language-dashboard.index') }}"><i class="bi bi-speedometer2"></i> Tổng quan trung tâm</a>
            @if($me->allowed('language_programs'))<a class="nav-link {{ request()->routeIs('language-programs.*') ? 'active' : '' }}" href="{{ route('language-programs.index') }}"><i class="bi bi-journal-richtext"></i> Chương trình & cấp độ</a>@endif
            @if($me->allowed('language_courses'))<a class="nav-link {{ request()->routeIs('language-center-courses.*') ? 'active' : '' }}" href="{{ route('language-center-courses.index') }}"><i class="bi bi-book-fill"></i> Khóa học trung tâm</a>@endif
            @if($me->allowed('language_discounts'))<a class="nav-link {{ request()->routeIs('language-discounts.*') ? 'active' : '' }}" href="{{ route('language-discounts.index') }}"><i class="bi bi-percent"></i> Chế độ miễn giảm</a>@endif
        </nav>
        @endif
        @if($me->allowed('kpis') || $me->allowed('courses') || $me->allowed('imports') || $me->allowed('reports'))
        <div class="sidebar-label">Chỉ tiêu & dữ liệu</div>
        <nav class="sidebar-nav nav flex-column">
            <a class="nav-link {{ request()->routeIs('kpi-dashboard.*') ? 'active' : '' }}" href="{{ route('kpi-dashboard.index') }}"><i class="bi bi-speedometer"></i> Tổng quan chỉ tiêu & dữ liệu</a>
            @if($me->allowed('kpis'))<a class="nav-link {{ request()->routeIs('kpis.*') ? 'active' : '' }}" href="{{ route('kpis.index') }}"><i class="bi bi-bullseye"></i> Kế hoạch chỉ tiêu</a>@endif
            @if($me->allowed('courses'))<a class="nav-link {{ request()->routeIs('courses.*') ? 'active' : '' }}" href="{{ route('courses.index') }}"><i class="bi bi-journal-bookmark-fill"></i> Khóa học & quy đổi</a>@endif
            @if($me->allowed('imports'))<a class="nav-link {{ request()->routeIs('imports.index','imports.create','imports.template') ? 'active' : '' }}" href="{{ route('imports.index') }}"><i class="bi bi-file-earmark-spreadsheet"></i> Nhập kết quả Excel</a><a class="nav-link {{ request()->routeIs('imports.records') ? 'active' : '' }}" href="{{ route('imports.records') }}"><i class="bi bi-table"></i> Tổng dữ liệu đã nhập</a>@endif
            @if($me->allowed('reports'))<a class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}" href="{{ route('reports.index') }}"><i class="bi bi-bar-chart-line-fill"></i> Báo cáo</a>@endif
            @if($me->allowed('payments'))<a class="nav-link {{ request()->routeIs('payments.*') ? 'active' : '' }}" href="{{ route('payments.index') }}"><i class="bi bi-cash-coin"></i> Thanh toán vượt</a>@endif
        </nav>
        @endif
        @if($me->allowed('personnel') || $me->allowed('users') || $me->allowed('roles') || $me->allowed('logs'))
        <div class="sidebar-label">Quản trị hệ thống</div>
        <nav class="sidebar-nav nav flex-column pb-4">
            @if($me->allowed('personnel'))<a class="nav-link {{ request()->routeIs('personnels.*') ? 'active' : '' }}" href="{{ route('personnels.index') }}"><i class="bi bi-people-fill"></i> Nhân sự & CTV</a>@endif
            @if($me->allowed('users'))<a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><i class="bi bi-person-lock"></i> Tài khoản</a>@endif
            @if($me->allowed('roles'))<a class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}"><i class="bi bi-shield-check"></i> Vai trò & quyền</a>@endif
            @if($me->allowed('logs'))<a class="nav-link {{ request()->routeIs('logs.*') ? 'active' : '' }}" href="{{ route('logs.index') }}"><i class="bi bi-clock-history"></i> Nhật ký hệ thống</a>@endif
        </nav>
        @endif
    </aside>
    <div class="main-wrap">
        @php
            $routeName = request()->route()?->getName() ?? '';
            $isLanguageCenter = str_starts_with($routeName, 'language-');
            $languageSection = match (true) {
                str_starts_with($routeName, 'language-leads'),
                str_starts_with($routeName, 'language-consulting'),
                str_starts_with($routeName, 'language-target-submissions'),
                str_starts_with($routeName, 'language-collaborators') => 'Tư vấn',
                str_starts_with($routeName, 'language-students'),
                str_starts_with($routeName, 'language-classes'),
                str_starts_with($routeName, 'language-tuition'),
                str_starts_with($routeName, 'language-targets') => 'Quản lý học viên',
                default => 'Quản lý trung tâm',
            };
            $languagePage = match (true) {
                str_starts_with($routeName, 'language-target-submissions') => 'Gửi chỉ tiêu',
                str_starts_with($routeName, 'language-consulting') => 'Công việc tư vấn',
                str_starts_with($routeName, 'language-dashboard') => 'Tổng quan trung tâm',
                str_starts_with($routeName, 'language-collaborators') => 'Cộng tác viên',
                str_starts_with($routeName, 'language-center-courses') => 'Khóa học trung tâm',
                str_starts_with($routeName, 'language-discounts') => 'Chế độ miễn giảm',
                str_starts_with($routeName, 'language-tuition') => 'Thu học phí',
                str_starts_with($routeName, 'language-targets') => 'Chỉ tiêu trung tâm',
                str_starts_with($routeName, 'language-leads') => 'Học viên tiềm năng',
                str_starts_with($routeName, 'language-students') => 'Học viên',
                str_starts_with($routeName, 'language-programs') => 'Chương trình & cấp độ',
                str_starts_with($routeName, 'language-classes') => 'Lớp học',
                default => '',
            };
            $pageAction = match (true) {
                str_starts_with($routeName, 'language-dashboard') => 'Tổng hợp',
                str_ends_with($routeName, '.create') => 'Thêm mới',
                str_ends_with($routeName, '.edit') => 'Chỉnh sửa',
                default => 'Danh sách',
            };
        @endphp
        <header class="topbar">
            <div class="d-flex align-items-center gap-3"><button class="btn topbar-menu d-lg-none" data-sidebar-toggle><i class="bi bi-list fs-5"></i></button><div><div class="topbar-title">@yield('header', $systemName)</div><div class="topbar-path">@if($isLanguageCenter)<i class="bi bi-house-door me-1"></i> {{ $languageSection }} <i class="bi bi-chevron-right mx-1"></i> {{ $languagePage }} <i class="bi bi-chevron-right mx-1 d-none d-md-inline"></i> <span class="d-none d-md-inline">{{ $pageAction }}</span>@else Năm → Quý → Tháng · Cộng dồn tự động @endif</div></div></div>
            <div class="dropdown">
                <button class="btn border-0 d-flex align-items-center gap-2" data-bs-toggle="dropdown"><span class="avatar">{{ mb_strtoupper(mb_substr($me->name,0,1)) }}</span><span class="d-none d-md-block text-start"><strong class="d-block small">{{ $me->name }}</strong><small class="text-muted">{{ $me->role?->name }}</small></span><i class="bi bi-chevron-down small"></i></button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i> Hồ sơ cá nhân</a></li>
                    <li><a class="dropdown-item" href="{{ route('profile.password') }}"><i class="bi bi-key me-2"></i> Đổi mật khẩu</a></li>
                    @if($me->isAdmin())<li><a class="dropdown-item" href="{{ route('settings.theme.edit') }}"><i class="bi bi-palette me-2"></i> Cấu hình phần mềm</a></li>@endif
                    <li><hr class="dropdown-divider"></li>
                    <li><form method="POST" action="{{ route('logout') }}">@csrf<button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i> Đăng xuất</button></form></li>
                </ul>
            </div>
        </header>
        <main class="content">
            @if(session('success'))<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" data-auto-dismiss="5000" role="alert"><i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button></div>@endif
            @if(session('warning'))<div class="alert alert-warning alert-dismissible fade show border-0 shadow-sm" data-auto-dismiss="5000" role="alert"><i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('warning') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button></div>@endif
            @if($errors->any())<div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" data-auto-dismiss="5000" role="alert"><strong><i class="bi bi-x-circle-fill me-2"></i>Vui lòng kiểm tra:</strong><ul class="mb-0 mt-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button></div>@endif
            @yield('content')
        </main>
        <footer class="app-footer">{{ $systemCopyright }}</footer>
    </div>
</div>
<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/app.js') }}"></script>
<script src="{{ asset('js/sidebar-scroll.js') }}"></script>
<script src="{{ asset('js/sidebar-mobile.js') }}"></script>
<script src="{{ asset('js/realtime.js') }}"></script>
<script src="{{ asset('js/auto-dismiss-alerts.js') }}"></script>
<script src="{{ asset('js/permissions.js') }}"></script>
<script src="{{ asset('js/searchable-select.js') }}"></script>
<script src="{{ asset('js/table-serial-numbers.js') }}"></script>
@stack('scripts')
</body>
</html>
