<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
    $faviconPath = $systemLogo ?: 'logo-20260722101948.png';
@endphp
    <link rel="icon" href="{{ asset($faviconPath) }}?v={{ is_file(public_path($faviconPath)) ? filemtime(public_path($faviconPath)) : 1 }}">
    <link rel="apple-touch-icon" href="{{ asset($faviconPath) }}?v={{ is_file(public_path($faviconPath)) ? filemtime(public_path($faviconPath)) : 1 }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', $systemName) · {{ $systemName }}</title>
    <link href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/dashboard-links.css') }}?v={{ filemtime(public_path('css/dashboard-links.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/student-history.css') }}?v={{ filemtime(public_path('css/student-history.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/teacher-gradebook.css') }}?v={{ filemtime(public_path('css/teacher-gradebook.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/student-tuition.css') }}?v={{ filemtime(public_path('css/student-tuition.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/pagination.css') }}?v={{ filemtime(public_path('css/pagination.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/responsive-actions.css') }}?v={{ filemtime(public_path('css/responsive-actions.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/work-tasks.css') }}?v={{ filemtime(public_path('css/work-tasks.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/list-selection.css') }}?v={{ filemtime(public_path('css/list-selection.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/theme.css') }}?v={{ filemtime(public_path('css/theme.css')) }}" rel="stylesheet">
</head>
<body data-theme="{{ $systemTheme ?? 'blue' }}" data-loading-style="{{ $systemLoadingStyle ?? 'center' }}" data-sidebar-mode="{{ $personalSidebarMode ?? 'remember' }}" data-visual-effect="{{ $personalVisualEffect ?? $systemVisualEffect ?? 'standard' }}">
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-mark @if($systemLogo) has-logo @endif">@if($systemLogo)<img src="{{ asset($systemLogo) }}" alt="Logo">@else<i class="bi bi-graph-up-arrow"></i>@endif</div>
            <div><strong>{{ $systemName }}</strong><small>HỆ THỐNG QUẢN LÝ</small></div>
        </div>
        <div class="sidebar-label">Tổng quan</div>
        <nav class="sidebar-nav nav flex-column">
            <a class="nav-link {{ request()->routeIs('welcome') ? 'active' : '' }}" href="{{ route('welcome') }}"><i class="bi bi-house-door-fill"></i> Trang chủ</a>
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><i class="bi bi-grid-1x2-fill"></i> {{ auth()->user()->isAdmin() || auth()->user()->allowed('system_dashboard') ? 'Tổng quan toàn hệ thống' : 'Tổng quan cá nhân' }}</a>
        </nav>
        @php
            $me = auth()->user();
        @endphp
        <div class="sidebar-label">Công việc</div>
        <nav class="sidebar-nav nav flex-column">
            <a class="nav-link {{ request()->routeIs('plans.*') ? 'active' : '' }}" href="{{ route('plans.index') }}"><i class="bi bi-calendar2-week-fill"></i> Kế hoạch & lịch cá nhân</a>
            @if($me->allowed('work_tasks'))<a class="nav-link {{ request()->routeIs('tasks.*') ? 'active' : '' }}" href="{{ route('tasks.index') }}"><i class="bi bi-list-check"></i> Giao & theo dõi công việc</a>@endif
        </nav>
        @if($me->allowed('language_consulting') || $me->allowed('language_target_submissions') || $me->allowed('language_leads') || $me->allowed('language_collaborators'))
        <div class="sidebar-label">Tư vấn & tuyển sinh</div>
        <nav class="sidebar-nav nav flex-column">
            @if($me->allowed('language_consulting'))<a class="nav-link {{ request()->routeIs('language-consulting.*') ? 'active' : '' }}" href="{{ route('language-consulting.index') }}"><i class="bi bi-headset"></i> Công việc tư vấn</a>@endif
            @if($me->allowed('language_target_submissions'))<a class="nav-link {{ request()->routeIs('language-target-submissions.*') ? 'active' : '' }}" href="{{ route('language-target-submissions.index') }}"><i class="bi bi-send-fill"></i> Gửi chỉ tiêu</a>@endif
            @if($me->allowed('language_leads'))<a class="nav-link {{ request()->routeIs('language-leads.*') ? 'active' : '' }}" href="{{ route('language-leads.index') }}"><i class="bi bi-person-plus-fill"></i> Học viên tiềm năng</a>@endif
            @if($me->allowed('language_collaborators'))<a class="nav-link {{ request()->routeIs('language-collaborators.*') ? 'active' : '' }}" href="{{ route('language-collaborators.index') }}"><i class="bi bi-people-fill"></i> Cộng tác viên</a>@endif
        </nav>
        @endif
        @if($me->allowed('language_students') || $me->allowed('language_tuition'))
        <div class="sidebar-label">Học viên</div>
        <nav class="sidebar-nav nav flex-column">
            @if($me->allowed('language_students'))<a class="nav-link {{ request()->routeIs('language-students.*') ? 'active' : '' }}" href="{{ route('language-students.index') }}"><i class="bi bi-mortarboard-fill"></i> Học viên</a>@endif
            @if($me->allowed('language_tuition'))<a class="nav-link {{ request()->routeIs('language-tuition.*') ? 'active' : '' }}" href="{{ route('language-tuition.index') }}"><i class="bi bi-cash-coin"></i> Thu học phí</a>@endif
        </nav>
        @endif
        @if($me->allowed('language_classes') || $me->allowed('language_programs') || $me->allowed('language_courses'))
        <div class="sidebar-label">Đào tạo</div>
        <nav class="sidebar-nav nav flex-column">
            @if($me->allowed('teacher_classes'))<a class="nav-link {{ request()->routeIs('teacher-classes.*') ? 'active' : '' }}" href="{{ route('teacher-classes.index') }}"><i class="bi bi-journal-check"></i> Lớp giảng dạy & điểm</a>@endif
            @if($me->allowed('language_classes'))<a class="nav-link {{ request()->routeIs('language-classes.*') ? 'active' : '' }}" href="{{ route('language-classes.index') }}"><i class="bi bi-easel2-fill"></i> Quản lý lớp học</a>@endif
            @if($me->allowed('language_programs'))<a class="nav-link {{ request()->routeIs('language-programs.*') ? 'active' : '' }}" href="{{ route('language-programs.index') }}"><i class="bi bi-journal-richtext"></i> Chương trình & cấp độ</a>@endif
            @if($me->allowed('language_courses'))<a class="nav-link {{ request()->routeIs('language-center-courses.*') ? 'active' : '' }}" href="{{ route('language-center-courses.index') }}"><i class="bi bi-book-fill"></i> Khóa học trung tâm</a>@endif
        </nav>
        @endif
        @if($me->isAdmin() || $me->allowed('language_discounts') || $me->allowed('language_targets') || $me->allowed('language_dashboard_all'))
        <div class="sidebar-label">Điều hành trung tâm</div>
        <nav class="sidebar-nav nav flex-column">
            <a class="nav-link {{ request()->routeIs('language-dashboard.*') ? 'active' : '' }}" href="{{ route('language-dashboard.index') }}"><i class="bi bi-speedometer2"></i> Tổng quan trung tâm</a>
            @if($me->allowed('language_targets'))<a class="nav-link {{ request()->routeIs('language-targets.*') ? 'active' : '' }}" href="{{ route('language-targets.index') }}"><i class="bi bi-clipboard-data"></i> Chỉ tiêu trung tâm</a>@endif
            @if($me->allowed('language_discounts'))<a class="nav-link {{ request()->routeIs('language-discounts.*') ? 'active' : '' }}" href="{{ route('language-discounts.index') }}"><i class="bi bi-percent"></i> Chế độ miễn giảm</a>@endif
        </nav>
        @endif
        @if($me->allowed('kpis') || $me->allowed('courses') || $me->allowed('imports') || $me->allowed('reports') || $me->allowed('payments'))
        <div class="sidebar-label">KPI & báo cáo</div>
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
            @if($me->allowed('users'))<a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><i class="bi bi-person-circle"></i> Tài khoản</a>@endif
            @if($me->allowed('roles'))<a class="nav-link {{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}"><i class="bi bi-shield-check"></i> Vai trò & quyền</a>@endif
            @if($me->allowed('logs'))<a class="nav-link {{ request()->routeIs('logs.*') ? 'active' : '' }}" href="{{ route('logs.index') }}"><i class="bi bi-clock-history"></i> Nhật ký hệ thống</a>@endif
            @if($me->allowed('software_settings'))<a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.edit','general') }}"><i class="bi bi-sliders"></i> Cấu hình phần mềm</a>@endif
            @if($me->isAdmin())<a class="nav-link {{ request()->routeIs('admin.system-test*') ? 'active' : '' }}" href="{{ route('admin.system-test') }}"><i class="bi bi-clipboard2-pulse"></i> Kiểm thử hệ thống</a>@endif
        </nav>
        @endif
        <div class="sidebar-label">Trợ giúp</div>
        <nav class="sidebar-nav nav flex-column pb-4 sidebar-help">
            <a class="nav-link {{ request()->routeIs('guide') ? 'active' : '' }}" href="{{ route('guide') }}"><i class="bi bi-question-circle-fill"></i> Hướng dẫn sử dụng</a>
        </nav>
    </aside>
    <div class="main-wrap">
        @php
            $routeName = request()->route()?->getName() ?? '';
            $isLanguageCenter = str_starts_with($routeName, 'language-') || str_starts_with($routeName, 'teacher-classes');
            $languageSection = match (true) {
                str_starts_with($routeName, 'language-leads'),
                str_starts_with($routeName, 'language-consulting'),
                str_starts_with($routeName, 'language-target-submissions'),
                str_starts_with($routeName, 'language-collaborators') => 'Tư vấn & tuyển sinh',
                str_starts_with($routeName, 'language-students'),
                str_starts_with($routeName, 'language-tuition') => 'Học viên',
                str_starts_with($routeName, 'teacher-classes'),
                str_starts_with($routeName, 'language-classes'),
                str_starts_with($routeName, 'language-programs'),
                str_starts_with($routeName, 'language-center-courses') => 'Đào tạo',
                default => 'Điều hành trung tâm',
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
                str_starts_with($routeName, 'teacher-classes') => 'Lớp giảng dạy & điểm',
                default => '',
            };
            $pageAction = match (true) {
                $routeName === 'welcome' => 'Trang chủ',
                $routeName === 'plans.index' => 'Lịch cá nhân',
                $routeName === 'tasks.index' => 'Lịch sử & bộ lọc',
                $routeName === 'guide' => 'Nội dung hướng dẫn',
                str_ends_with($routeName, '.export') => 'Xuất dữ liệu',
                str_ends_with($routeName, '.template') => 'Tải tệp mẫu',
                str_contains($routeName, 'receipt') => 'Phiếu thu',
                str_contains($routeName, 'dashboard') => 'Tổng hợp',
                str_ends_with($routeName, '.create') => 'Thêm mới',
                str_ends_with($routeName, '.edit') => 'Chỉnh sửa',
                str_ends_with($routeName, '.show') => 'Chi tiết',
                str_contains($routeName, 'permissions') => 'Phân quyền',
                str_contains($routeName, 'import') => 'Nhập dữ liệu',
                str_starts_with($routeName, 'profile.') => 'Cập nhật',
                str_starts_with($routeName, 'personal-settings.') => 'Thiết lập',
                str_starts_with($routeName, 'settings.') => 'Thiết lập',
                default => 'Danh sách',
            };
            $generalSection = match (true) {
                $routeName === 'welcome' => 'Tổng quan',
                str_starts_with($routeName, 'plans.'), str_starts_with($routeName, 'tasks.') => 'Công việc',
                $routeName === 'guide' => 'Trợ giúp',
                $routeName === 'dashboard' => 'Tổng quan',
                str_starts_with($routeName, 'kpi-dashboard'), str_starts_with($routeName, 'kpis'), str_starts_with($routeName, 'courses'), str_starts_with($routeName, 'imports'), str_starts_with($routeName, 'reports'), str_starts_with($routeName, 'payments') => 'KPI & báo cáo',
                str_starts_with($routeName, 'personnels'), str_starts_with($routeName, 'users'), str_starts_with($routeName, 'roles'), str_starts_with($routeName, 'logs'), str_starts_with($routeName, 'settings') => 'Quản trị hệ thống',
                str_starts_with($routeName, 'profile'), str_starts_with($routeName, 'personal-settings') => 'Tài khoản cá nhân',
                default => 'Hệ thống',
            };
            $generalPage = match (true) {
                $routeName === 'welcome' => 'Trang chủ',
                str_starts_with($routeName, 'plans.') => 'Kế hoạch & lịch',
                str_starts_with($routeName, 'tasks.') => 'Giao task',
                $routeName === 'guide' => 'Hướng dẫn sử dụng',
                $routeName === 'dashboard' => 'Tổng quan hệ thống',
                str_starts_with($routeName, 'kpi-dashboard') => 'Tổng quan chỉ tiêu',
                str_starts_with($routeName, 'kpis') => 'Kế hoạch chỉ tiêu',
                str_starts_with($routeName, 'courses') => 'Khóa học & quy đổi',
                str_starts_with($routeName, 'imports.records') => 'Dữ liệu đã nhập',
                str_starts_with($routeName, 'imports') => 'Nhập kết quả Excel',
                str_starts_with($routeName, 'reports') => 'Báo cáo chỉ tiêu',
                str_starts_with($routeName, 'payments') => 'Thanh toán vượt chỉ tiêu',
                str_starts_with($routeName, 'personnels') => 'Nhân sự & cộng tác viên',
                str_starts_with($routeName, 'users') => 'Tài khoản',
                str_starts_with($routeName, 'roles') => 'Vai trò & quyền',
                str_starts_with($routeName, 'logs') => 'Nhật ký hệ thống',
                str_starts_with($routeName, 'settings') => 'Cấu hình phần mềm',
                $routeName === 'profile.password' => 'Đổi mật khẩu',
                str_starts_with($routeName, 'profile') => 'Hồ sơ cá nhân',
                str_starts_with($routeName, 'personal-settings') => 'Cài đặt cá nhân',
                default => 'Trang hiện tại',
            };
        @endphp
        <header class="topbar">
            <div class="d-flex align-items-center gap-3"><button class="btn topbar-menu" data-sidebar-toggle title="Ẩn/hiện menu" aria-label="Ẩn hoặc hiện menu"><i class="bi bi-list fs-5"></i></button><div><div class="topbar-title">@yield('header', $systemName)</div><div class="topbar-path">@if($isLanguageCenter)<i class="bi bi-house-door me-1"></i> {{ $languageSection }} <i class="bi bi-chevron-right mx-1"></i> {{ $languagePage }} <i class="bi bi-chevron-right mx-1 d-none d-md-inline"></i> <span class="d-none d-md-inline">{{ $pageAction }}</span>@else<i class="bi bi-house-door me-1"></i> {{ $generalSection }} <i class="bi bi-chevron-right mx-1"></i> {{ $generalPage }} <i class="bi bi-chevron-right mx-1 d-none d-md-inline"></i> <span class="d-none d-md-inline">{{ $pageAction }}</span>@endif</div></div></div>
            <div class="dropdown topbar-reminders" data-realtime-notifications data-user-id="{{auth()->id()}}" data-reverb-key="{{config('broadcasting.connections.reverb.key')}}" data-reverb-host="{{config('broadcasting.connections.reverb.client.host')}}" data-reverb-port="{{config('broadcasting.connections.reverb.client.port')}}" data-reverb-scheme="{{config('broadcasting.connections.reverb.client.scheme')}}">
    <button class="btn border-0 reminder-button" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Thông báo" aria-label="Thông báo">
        <i class="bi bi-bell fs-5"></i>
        <span class="reminder-count {{ $planReminders->isEmpty() ? 'd-none' : '' }}" data-notification-count>{{ $planReminders->count() }}</span>
    </button>
    <div class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3 p-0 reminder-menu">
        <div class="d-flex align-items-center justify-content-between px-3 py-3 border-bottom"><strong>Thông báo</strong><a class="small text-decoration-none" href="{{ route('personal-settings.edit') }}">Cài đặt</a></div>
        <div data-notification-items>
            @forelse($planReminders as $reminder)
            <a class="reminder-item text-decoration-none text-dark" href="{{ route('plans.index',['month'=>$reminder->scheduled_for->format('Y-m')]) }}"><i class="bi {{ $reminder->scheduled_for->isPast() ? 'bi-exclamation-circle-fill text-danger' : 'bi-bell-fill' }}"></i><span><strong>{{ $reminder->title }}</strong><small>{{ $reminder->scheduled_for->format('H:i d/m/Y') }}</small></span></a>
            @empty<div class="empty-state py-4"><i class="bi bi-bell-slash"></i><div class="small mt-2">Không có việc cần nhắc.</div></div>@endforelse
        </div>
        <div class="px-3 py-2 border-top small text-muted"><i class="bi bi-broadcast-pin me-1"></i>Cập nhật tức thời</div>
    </div>
</div>
<div class="dropdown">
                <button class="btn border-0 d-flex align-items-center gap-2" data-bs-toggle="dropdown"><span class="avatar">{{ mb_strtoupper(mb_substr($me->name,0,1)) }}</span><span class="d-none d-md-block text-start"><strong class="d-block small">{{ $me->name }}</strong><small class="text-muted">{{ $me->role?->name }}</small></span><i class="bi bi-chevron-down small"></i></button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i> Hồ sơ cá nhân</a></li>
                    <li><a class="dropdown-item" href="{{ route('personal-settings.edit') }}"><i class="bi bi-sliders me-2"></i> Cài đặt cá nhân</a></li>
                    <li><a class="dropdown-item" href="{{ route('profile.password') }}"><i class="bi bi-key me-2"></i> Đổi mật khẩu</a></li>
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
        <footer class="app-footer">{!! $systemCopyright !!}</footer>
    </div>
</div>
<div class="modal fade app-confirm-modal" id="appConfirmModal" tabindex="-1" aria-labelledby="appConfirmTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-body text-center p-4">
                <span class="app-confirm-icon"><i class="bi bi-exclamation-triangle-fill"></i></span>
                <h5 id="appConfirmTitle" class="mt-3 mb-2">Xác nhận thao tác</h5>
                <p class="text-muted mb-0" data-confirm-message>Bạn có chắc chắn muốn tiếp tục?</p>
            </div>
            <div class="modal-footer border-0 justify-content-center gap-2 pt-0 pb-4">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Hủy</button>
                <button type="button" class="btn btn-danger px-4" data-confirm-accept><i class="bi bi-check2-circle me-1"></i>Tiếp tục</button>
            </div>
        </div>
    </div>
</div>

<div class="page-loading-overlay" data-page-loading aria-hidden="true" aria-live="polite"><div class="page-loading-card"><span class="page-loading-spinner" aria-hidden="true"></span><span>Đang tải dữ liệu...</span></div></div>
<script src="{{ asset('vendor/bootstrap/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/app.js') }}"></script>
<script src="{{ asset('js/list-selection.js') }}?v={{ filemtime(public_path('js/list-selection.js')) }}"></script>
<script src="{{ asset('js/page-loading.js') }}?v={{ filemtime(public_path('js/page-loading.js')) }}"></script>
<script src="{{ asset('js/sidebar-scroll.js') }}"></script>
<script src="{{ asset('js/sidebar-mobile.js') }}"></script>
<script src="{{ asset('js/realtime.js') }}"></script>
<script src="{{ asset('js/auto-dismiss-alerts.js') }}"></script>
<script src="{{ asset('js/permissions.js') }}"></script>
<script src="{{ asset('js/searchable-select.js') }}?v={{ filemtime(public_path('js/searchable-select.js')) }}"></script>
<script src="{{ asset('js/table-serial-numbers.js') }}"></script>
<script src="{{ asset('js/icon-buttons.js') }}?v={{ filemtime(public_path('js/icon-buttons.js')) }}"></script>
@stack('scripts')
</body>
</html>
