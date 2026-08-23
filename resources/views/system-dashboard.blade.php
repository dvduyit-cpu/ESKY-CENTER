@extends('layouts.app')
@section('title', $canViewAll ? 'Tổng quan toàn hệ thống' : 'Tổng quan cá nhân')
@section('header', $canViewAll ? 'Tổng quan toàn hệ thống' : 'Tổng quan cá nhân')
@section('content')
@php
    $leadLabels = [
        'new' => 'Mới tiếp nhận',
        'contacted' => 'Đã liên hệ',
        'consulting' => 'Đang tư vấn',
        'placement_test' => 'Hẹn kiểm tra',
        'waiting' => 'Chờ phản hồi',
        'registered' => 'Đã đăng ký',
        'not_interested' => 'Không quan tâm',
        'follow_up' => 'Chăm sóc lại',
    ];
    $studentLabels = [
        'new' => 'Mới đăng ký',
        'placement_test' => 'Chờ kiểm tra',
        'waiting_class' => 'Chờ xếp lớp',
        'studying' => 'Đang học',
        'paused' => 'Tạm nghỉ',
        'reserved' => 'Bảo lưu',
        'completed' => 'Hoàn thành',
        'dropped' => 'Thôi học',
    ];
    $classLabels = [
        'planned' => 'Dự kiến mở',
        'recruiting' => 'Đang tuyển sinh',
        'upcoming' => 'Sắp khai giảng',
        'active' => 'Đang hoạt động',
        'paused' => 'Tạm dừng',
        'completed' => 'Đã kết thúc',
        'cancelled' => 'Đã hủy',
    ];
    $tuitionLabels = [
        'unpaid' => 'Chưa thu',
        'partial' => 'Thu một phần',
        'pending_receipt' => 'Chờ bổ sung phiếu thu',
        'paid' => 'Đã thu đủ',
        'transferred' => 'Đã quyết toán chuyển lớp',
    ];
    $classRoute = auth()->user()->allowed('teacher_classes')
        ? route('teacher-classes.index')
        : (auth()->user()->allowed('language_classes') ? route('language-classes.index') : null);
    $teachingLoadRoute = auth()->user()->allowed('teacher_classes')
        ? route('teacher-classes.teaching-load.index')
        : null;
    $teachingProgressWidth = min(max((float) ($teachingSummary['progress'] ?? 0), 0), 100);
    $headlineCards = $canViewAll
        ? [
            ['Nhân sự hoạt động', number_format($activePersonnel), 'info', 'bi-people-fill', route('personnels.index'), auth()->user()->allowed('personnel')],
            ['Tài khoản hoạt động', number_format($activeUsers), 'primary', 'bi-person-check-fill', route('users.index'), auth()->user()->allowed('users')],
            ['Học viên mới', number_format($students), 'success', 'bi-mortarboard-fill', route('language-students.index'), auth()->user()->allowed('language_students')],
            ['Lớp đang học', number_format($activeClasses), 'warning', 'bi-easel2-fill', route('language-classes.index'), auth()->user()->allowed('language_classes')],
            ['Khách hàng mới', number_format($leads), 'info', 'bi-person-plus-fill', route('language-leads.index'), auth()->user()->allowed('language_leads')],
            ['Thu ròng', number_format($financial['net']) . 'đ', $financial['net'] >= 0 ? 'success' : 'danger', 'bi-graph-up-arrow', route('language-tuition.index'), auth()->user()->allowed('language_tuition')],
        ]
        : [
            ['Công việc đang làm', number_format($workTaskStats['in_progress']), 'primary', 'bi-list-check', route('tasks.index', ['status' => 'acknowledged']), auth()->user()->allowed('work_tasks')],
            ['Chưa xác nhận', number_format($workTaskStats['awaiting_acknowledgement']), 'warning', 'bi-exclamation-circle', route('tasks.index', ['status' => 'unread']), auth()->user()->allowed('work_tasks')],
            ['Học viên của tôi', number_format($students), 'success', 'bi-mortarboard-fill', route('language-students.index'), auth()->user()->allowed('language_students')],
            ['Lớp tôi phụ trách', number_format($activeClasses), 'info', 'bi-easel2-fill', $classRoute, (bool) $classRoute],
            ['Tiết đã báo cáo', number_format($teachingSummary['reported'], 1), 'primary', 'bi-journal-check', $teachingLoadRoute, (bool) $teachingLoadRoute],
            ['KPI đã thực hiện', number_format($kpiTotals['actual_quantity'], 1), 'danger', 'bi-bullseye', route('kpi-dashboard.index', ['year' => $year]), true],
        ];

    $quickLinks = collect($canViewAll
        ? [
            [route('tasks.index'), 'bi-list-check', 'Công việc', 'Theo dõi giao việc và tiến độ', auth()->user()->allowed('work_tasks')],
            [route('language-tuition.index'), 'bi-cash-stack', 'Học phí', 'Quản lý thu tiền và công nợ', auth()->user()->allowed('language_tuition')],
            [route('language-students.index'), 'bi-mortarboard', 'Học viên', 'Tra cứu và cập nhật hồ sơ', auth()->user()->allowed('language_students')],
            [route('language-classes.index'), 'bi-easel2', 'Lớp học', 'Theo dõi lớp đang học và sắp mở', auth()->user()->allowed('language_classes')],
            [route('language-leads.index'), 'bi-headset', 'Tư vấn', 'Quản lý đầu vào tuyển sinh', auth()->user()->allowed('language_leads')],
            [route('imports.index'), 'bi-file-earmark-spreadsheet', 'Nhập dữ liệu', 'Tải mẫu và xem lịch sử import', auth()->user()->allowed('imports')],
            [route('kpi-dashboard.index', ['year' => $year]), 'bi-bullseye', 'KPI', 'Theo dõi chỉ tiêu và kết quả', true],
            [route('logs.index'), 'bi-clock-history', 'Nhật ký', 'Xem hoạt động gần đây của hệ thống', auth()->user()->allowed('logs')],
        ]
        : [
            [route('profile.edit'), 'bi-person-circle', 'Hồ sơ cá nhân', 'Xem và cập nhật thông tin cá nhân', true],
            [route('personal-settings.edit'), 'bi-sliders', 'Cài đặt cá nhân', 'Đổi giao diện và tùy chỉnh làm việc', true],
            [route('tasks.index'), 'bi-list-check', 'Công việc của tôi', 'Các việc đang làm và quá hạn', auth()->user()->allowed('work_tasks')],
            [$classRoute, 'bi-easel2', 'Lớp tôi phụ trách', 'Danh sách lớp đang dạy hoặc theo dõi', (bool) $classRoute],
            [$teachingLoadRoute, 'bi-clock-history', 'Báo cáo tiết dạy', 'Nhập và theo dõi giờ dạy của tôi', (bool) $teachingLoadRoute],
            [route('kpi-dashboard.index', ['year' => $year]), 'bi-bullseye', 'KPI cá nhân', 'Theo dõi tiến độ chỉ tiêu của tôi', true],
        ]
    )->filter(fn (array $item): bool => !empty($item[4]) && !empty($item[0]))->values();

    $moduleCards = [
        [
            'title' => $canViewAll ? 'Công việc toàn hệ thống' : 'Công việc của tôi',
            'subtitle' => 'Tiến độ xử lý trong kỳ đang chọn.',
            'icon' => 'bi-list-check',
            'tone' => 'primary',
            'route' => auth()->user()->allowed('work_tasks') ? route('tasks.index') : null,
            'items' => $canViewAll
                ? [
                    ['Tổng công việc đã giao', number_format($workTaskStats['total']), 'text-primary'],
                    ['Lượt phân công', number_format($workTaskStats['assignments']), 'text-info'],
                    ['Đã nhận việc', number_format($workTaskStats['acknowledged']), 'text-success'],
                    ['Đã hoàn thành', number_format($workTaskStats['completed']), 'text-success'],
                    ['Đang quá hạn', number_format($workTaskStats['overdue']), 'text-danger'],
                ]
                : [
                    ['Công việc liên quan', number_format($workTaskStats['total']), 'text-primary'],
                    ['Chưa xác nhận', number_format($workTaskStats['awaiting_acknowledgement']), 'text-warning'],
                    ['Đang thực hiện', number_format($workTaskStats['in_progress']), 'text-info'],
                    ['Đã hoàn thành', number_format($workTaskStats['completed']), 'text-success'],
                    ['Đang quá hạn', number_format($workTaskStats['overdue']), 'text-danger'],
                ],
        ],
        [
            'title' => $canViewAll ? 'Tài chính trung tâm' : 'Học phí liên quan tới tôi',
            'subtitle' => $canViewAll ? 'Thu, chi và công nợ học phí.' : 'Các khoản thu và công nợ trong phạm vi tôi phụ trách.',
            'icon' => 'bi-cash-stack',
            'tone' => 'warning',
            'route' => auth()->user()->allowed('language_tuition') ? route('language-tuition.index') : null,
            'items' => [
                ['Phải thu', number_format($financial['receivable']) . 'đ', 'text-primary'],
                ['Đã thu', number_format($financial['collected']) . 'đ', 'text-success'],
                ['Còn phải thu', number_format($financial['outstanding']) . 'đ', 'text-warning'],
                ['Đã chi', number_format($financial['expense']) . 'đ', 'text-danger'],
                ['Thu ròng', number_format($financial['net']) . 'đ', $financial['net'] >= 0 ? 'text-success' : 'text-danger'],
            ],
        ],
        [
            'title' => $canViewAll ? 'Tư vấn và tuyển sinh' : 'Khách hàng tôi phụ trách',
            'subtitle' => $canViewAll ? 'Luồng chuyển đổi khách hàng.' : 'Tình trạng khách hàng và tỷ lệ chuyển đổi của cá nhân tôi.',
            'icon' => 'bi-funnel-fill',
            'tone' => 'info',
            'route' => auth()->user()->allowed('language_leads') ? route('language-leads.index') : null,
            'items' => [
                ['Khách hàng mới', number_format($leads), 'text-info'],
                ['Đã tư vấn', number_format($consulted), 'text-primary'],
                ['Đã đăng ký', number_format($registeredLeads), 'text-success'],
                ['Tỷ lệ chuyển đổi', $conversionRate . '%', 'text-warning'],
            ],
        ],
        [
            'title' => $canViewAll ? 'Đào tạo và lớp học' : 'Lớp học và học viên của tôi',
            'subtitle' => $canViewAll ? 'Quy mô học viên và lớp hiện tại.' : 'Các lớp, học viên và tiến độ đào tạo trong phạm vi tôi phụ trách.',
            'icon' => 'bi-mortarboard-fill',
            'tone' => 'success',
            'route' => $classRoute,
            'items' => $canViewAll
                ? [
                    ['Học viên mới', number_format($students), 'text-success'],
                    ['Lớp đang học', number_format($activeClasses), 'text-primary'],
                    ['Đang/sắp tuyển', number_format($upcomingClasses), 'text-warning'],
                    ['Nhân sự hoạt động', number_format($activePersonnel), 'text-info'],
                ]
                : [
                    ['Học viên của tôi', number_format($students), 'text-success'],
                    ['Lớp tôi phụ trách', number_format($activeClasses), 'text-primary'],
                    ['Lớp đang/sắp tuyển', number_format($upcomingClasses), 'text-warning'],
                    ['Tiết đã báo cáo', number_format($teachingSummary['reported'], 1), 'text-danger'],
                ],
        ],
    ];
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h1 class="page-title">{{ $canViewAll ? 'Tổng quan toàn hệ thống' : 'Tổng quan cá nhân' }}</h1>
        <div class="page-subtitle">{{ $canViewAll ? 'Bảng điều hành trung tâm, gom đủ các số liệu quan trọng trong '.$period.' để theo dõi và thao tác nhanh hơn.' : 'Bảng theo dõi riêng cho cá nhân tôi, chỉ hiển thị các số liệu và tác vụ liên quan trực tiếp trong '.$period.'.' }}</div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        @if(auth()->user()->allowed('system_dashboard', 'export'))
            <a class="btn btn-outline-success" href="{{ route('dashboard.export', request()->query()) }}">
                <i class="bi bi-file-earmark-excel me-1"></i>Xuất Excel
            </a>
        @endif
        @if(auth()->user()->allowed('work_tasks'))
            <a class="btn btn-primary" href="{{ route('tasks.index') }}">
                <i class="bi bi-arrow-up-right-circle me-1"></i>Mở công việc
            </a>
        @endif
    </div>
</div>

@if($weeklyReportCard)
    <div class="weekly-report-prompt mb-4">
        <div class="weekly-report-prompt-icon"><i class="bi bi-file-earmark-text"></i></div>
        <div class="flex-grow-1">
            <div class="small text-uppercase fw-semibold opacity-75">{{ $weeklyReportCard['title'] }} · {{ $weeklyReportCard['week_start']->format('d/m') }} - {{ $weeklyReportCard['week_end']->format('d/m/Y') }}</div>
            @if($weeklyReportCard['mode'] === 'management')
                <h5 class="mb-1">Theo dõi báo cáo tuần của toàn bộ nhân sự</h5>
                <div class="small">Trạng thái: <strong>{{ $weeklyReportCard['is_active'] ? 'Đang hoạt động' : 'Đã tắt' }}</strong> · Đã gửi: <strong>{{ $weeklyReportCard['submitted_count'] }}</strong> · Chưa gửi: <strong>{{ $weeklyReportCard['missing_count'] }}</strong></div>
            @else
                <h5 class="mb-1">{{ $weeklyReportCard['report']?->status === 'submitted' ? 'Báo cáo tuần đã được gửi' : 'Admin đã mở kỳ báo cáo tuần' }}</h5>
                <div class="small">Dữ liệu đã lưu vẫn được giữ lại, bạn có thể mở lại để cập nhật khi cần.</div>
            @endif
        </div>
        @if($weeklyReportCard['mode'] === 'management')
            <div class="d-flex flex-wrap gap-2">
                @if(!auth()->user()->isAdmin() && $weeklyReportCard['is_assigned'])
                    <a class="btn btn-light text-primary" href="{{ route('administration.weekly.index', ['period' => $weeklyReportCard['period_id'], 'open' => 1]) }}">
                        {{ $weeklyReportCard['report']?->status === 'submitted' ? 'Xem báo cáo của tôi' : 'Báo cáo của tôi' }}
                    </a>
                @endif
                <a class="btn btn-light text-primary" href="{{ route('administration.weekly.index') }}">
                    Xem các tuần <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        @else
            <a class="btn btn-light text-primary" href="{{ route('administration.weekly.index', ['period' => $weeklyReportCard['period_id'], 'open' => 1]) }}">
                {{ $weeklyReportCard['report'] ? 'Xem báo cáo' : 'Báo cáo ngay' }} <i class="bi bi-arrow-right ms-1"></i>
            </a>
        @endif
    </div>
@endif

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card card-soft h-100">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
                    <div>
                        <div class="small text-uppercase fw-semibold text-muted mb-2">Tổng quan nhanh</div>
                        <h4 class="mb-1">Điểm chạm chính trong {{ $period }}</h4>
                        <div class="small text-muted">{{ $canViewAll ? 'Bạn đang xem dữ liệu ở cấp toàn hệ thống.' : 'Bạn đang xem dữ liệu theo phạm vi cá nhân được phân quyền.' }}</div>
                    </div>
                    <div class="rounded-3 bg-light px-3 py-2">
                        <div class="small text-muted">Người dùng hiện tại</div>
                        <div class="fw-semibold">{{ $currentUser->name }}</div>
                        <div class="small text-muted">{{ $currentUser->personnel?->name ?: 'Chưa liên kết nhân sự' }}</div>
                    </div>
                </div>
                <div class="row g-3">
                    @foreach($headlineCards as [$label, $value, $color, $icon, $route, $permission])
                        <div class="col-sm-6 col-xl-4">
                            @if($permission && $route)
                                <a class="dashboard-card-link" href="{{ $route }}">
                            @endif
                            <div class="card card-soft stat-card h-100">
                                <div class="card-body p-4">
                                    <div class="stat-label">{{ $label }}</div>
                                    <div class="d-flex justify-content-between align-items-center gap-2">
                                        <div class="stat-value text-{{ $color }}">{{ $value }}</div>
                                        <div class="stat-icon bg-{{ $color }}-subtle text-{{ $color }}"><i class="bi {{ $icon }}"></i></div>
                                    </div>
                                </div>
                            </div>
                            @if($permission && $route)
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card card-soft h-100">
            <div class="card-header bg-white border-0 p-4 pb-2">
                <h5 class="mb-1">Lối tắt thao tác</h5>
                <small class="text-muted">Mở nhanh các khu vực được dùng nhiều nhất.</small>
            </div>
            <div class="card-body p-4 pt-2">
                <div class="row g-3">
                    @forelse($quickLinks as [$route, $icon, $title, $note])
                        <div class="col-12">
                            <a class="dashboard-card-link" href="{{ $route }}">
                                <div class="card card-soft h-100">
                                    <div class="card-body p-3 d-flex align-items-center gap-3">
                                        <div class="stat-icon bg-primary-subtle text-primary"><i class="bi {{ $icon }}"></i></div>
                                        <div>
                                            <div class="fw-semibold">{{ $title }}</div>
                                            <div class="small text-muted">{{ $note }}</div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="empty-state py-4">Chưa có lối tắt phù hợp với quyền hiện tại.</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card card-soft mb-4">
    <div class="card-header bg-white border-0 p-4 pb-2">
        <h5 class="mb-1">Bộ lọc thời gian</h5>
        <small class="text-muted">Chọn kiểu thời gian để làm mới toàn bộ số liệu trên trang chủ.</small>
    </div>
    <div class="card-body pt-2">
        <form class="filter-panel row g-3" data-system-period-filter>
            <div class="col-lg-2">
                <label class="form-label">Kiểu thời gian</label>
                <select class="form-select" name="period_type" data-period-mode>
                    <option value="range" @selected($periodType === 'range')>Khoảng ngày</option>
                    <option value="week" @selected($periodType === 'week')>Theo tuần</option>
                    <option value="month" @selected($periodType === 'month')>Theo tháng</option>
                    <option value="quarter" @selected($periodType === 'quarter')>Theo quý</option>
                    <option value="year" @selected($periodType === 'year')>Theo năm</option>
                </select>
            </div>
            <div class="col-lg-2">
                <label class="form-label">Năm</label>
                <input class="form-control" type="number" name="year" value="{{ $year }}">
            </div>
            <div class="col-lg-2" data-period-field="week">
                <label class="form-label">Tuần</label>
                <input class="form-control" type="number" name="week" min="1" max="53" value="{{ request('week', now()->isoWeek()) }}">
            </div>
            <div class="col-lg-2" data-period-field="month">
                <label class="form-label">Tháng</label>
                <select class="form-select" name="month">
                    @for($m = 1; $m <= 12; $m++)
                        <option value="{{ $m }}" @selected(request('month', now()->month) == $m)>Tháng {{ $m }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-lg-2" data-period-field="quarter">
                <label class="form-label">Quý</label>
                <select class="form-select" name="quarter">
                    @for($q = 1; $q <= 4; $q++)
                        <option value="{{ $q }}" @selected(request('quarter', now()->quarter) == $q)>Quý {{ $q }}</option>
                    @endfor
                </select>
            </div>
            <div class="col-lg-2" data-period-field="range">
                <label class="form-label">Từ ngày</label>
                <input class="form-control" type="date" name="from_date" value="{{ request('from_date', now()->startOfMonth()->format('Y-m-d')) }}">
            </div>
            <div class="col-lg-2" data-period-field="range">
                <label class="form-label">Đến ngày</label>
                <input class="form-control" type="date" name="to_date" value="{{ request('to_date', now()->format('Y-m-d')) }}">
            </div>
            <div class="col-lg-2 d-flex align-items-end">
                <button class="btn btn-primary w-100"><i class="bi bi-filter me-1"></i>Xem tổng quan</button>
            </div>
        </form>
    </div>
</div>

<div class="system-section-title">
    <span><i class="bi bi-grid-1x2-fill"></i></span>
    <div>
        <h5>Phân hệ chính</h5>
        <small>{{ $canViewAll ? 'Mỗi khối gom một phần dữ liệu trọng tâm để nhìn đủ hơn trên trang chủ.' : 'Mỗi khối chỉ giữ lại phần dữ liệu và đầu việc gắn trực tiếp với cá nhân tôi.' }}</small>
    </div>
</div>
<div class="row g-4 mb-4">
    @foreach($moduleCards as $card)
        <div class="col-md-6 col-xl-3">
            @if($card['route'])
                <a class="dashboard-card-link" href="{{ $card['route'] }}">
            @endif
            <div class="card card-soft h-100">
                <div class="card-header bg-white border-0 p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-{{ $card['tone'] }}-subtle text-{{ $card['tone'] }}"><i class="bi {{ $card['icon'] }}"></i></div>
                        <div>
                            <h5 class="mb-0 fw-bold">{{ $card['title'] }}</h5>
                            <small class="text-muted">{{ $card['subtitle'] }}</small>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4 pt-0">
                    @foreach($card['items'] as [$label, $value, $class])
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom gap-3">
                            <span>{{ $label }}</span>
                            <strong class="{{ $class }}">{{ $value }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
            @if($card['route'])
                </a>
            @endif
        </div>
    @endforeach
</div>

<div class="system-section-title">
    <span><i class="bi bi-list-check"></i></span>
    <div>
        <h5>{{ $canViewAll ? 'Công việc toàn hệ thống' : 'Công việc của tôi' }}</h5>
        <small>Thống kê công việc được giao trong {{ $period }}</small>
    </div>
    @if($canViewAll)
        <button class="btn btn-sm btn-outline-primary ms-auto" type="button" data-bs-toggle="modal" data-bs-target="#taskRecipientStatsModal">
            <i class="bi bi-people me-1"></i>Xem theo thành viên
        </button>
    @elseif(auth()->user()->allowed('work_tasks'))
        <a class="btn btn-sm btn-outline-primary ms-auto" href="{{ route('tasks.index') }}">Mở công việc</a>
    @endif
</div>
<div class="row g-3 mb-4">
    @if($canViewAll)
        @foreach([
            ['Tổng công việc đã giao', $workTaskStats['total'], 'primary', 'bi-send-check', ''],
            ['Lượt phân công', $workTaskStats['assignments'], 'info', 'bi-people', ''],
            ['Đã nhận việc', $workTaskStats['acknowledged'], 'success', 'bi-check-square', ''],
            ['Đã hoàn thành', $workTaskStats['completed'], 'success', 'bi-check2-circle', 'completed'],
            ['Đang quá hạn', $workTaskStats['overdue'], 'danger', 'bi-exclamation-triangle', 'overdue'],
        ] as [$label, $value, $color, $icon, $status])
            <div class="col-6 col-xl">
                @if(auth()->user()->allowed('work_tasks'))
                    <a class="dashboard-card-link" href="{{ route('tasks.index', array_filter(['status' => $status])) }}">
                @endif
                <div class="card card-soft stat-card h-100">
                    <div class="card-body p-4">
                        <div class="stat-label">{{ $label }}</div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="stat-value text-{{ $color }}">{{ number_format($value) }}</div>
                            <div class="stat-icon bg-{{ $color }}-subtle text-{{ $color }}"><i class="bi {{ $icon }}"></i></div>
                        </div>
                    </div>
                </div>
                @if(auth()->user()->allowed('work_tasks'))
                    </a>
                @endif
            </div>
        @endforeach
    @else
        @foreach([
            ['Công việc liên quan', $workTaskStats['total'], 'primary', 'bi-send-check', ''],
            ['Chưa xác nhận', $workTaskStats['awaiting_acknowledgement'], 'warning', 'bi-exclamation-circle', 'unread'],
            ['Đã nhận việc', $workTaskStats['in_progress'], 'info', 'bi-check-square', 'acknowledged'],
            ['Đã hoàn thành', $workTaskStats['completed'], 'success', 'bi-check2-circle', 'personal_completed'],
            ['Đang quá hạn', $workTaskStats['overdue'], 'danger', 'bi-exclamation-triangle', 'overdue'],
        ] as [$label, $value, $color, $icon, $status])
            <div class="col-6 col-xl">
                @if(auth()->user()->allowed('work_tasks'))
                    <a class="dashboard-card-link" href="{{ route('tasks.index', array_filter(['status' => $status])) }}">
                @endif
                <div class="card card-soft stat-card h-100">
                    <div class="card-body p-4">
                        <div class="stat-label">{{ $label }}</div>
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="stat-value text-{{ $color }}">{{ number_format($value) }}</div>
                            <div class="stat-icon bg-{{ $color }}-subtle text-{{ $color }}"><i class="bi {{ $icon }}"></i></div>
                        </div>
                    </div>
                </div>
                @if(auth()->user()->allowed('work_tasks'))
                    </a>
                @endif
            </div>
        @endforeach
    @endif
</div>

@if($canViewAll)
    <div class="modal fade" id="taskRecipientStatsModal" tabindex="-1" aria-labelledby="taskRecipientStatsTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="taskRecipientStatsTitle">Công việc theo thành viên</h5>
                        <small class="text-muted">Số công việc mỗi thành viên được giao trong {{ $period }}.</small>
                    </div>
                    <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-modern align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Thành viên</th>
                                    <th class="text-center">Được giao</th>
                                    <th class="text-center">Đã nhận việc</th>
                                    <th class="text-center">Hoàn thành</th>
                                    <th class="text-center">Chưa nhận</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($taskRecipientStats as $member)
                                    <tr>
                                        <td>
                                            <strong>{{ $member->user?->name ?? 'Tài khoản đã xóa' }}</strong>
                                            <div class="small text-muted">{{ $member->user?->email }}</div>
                                        </td>
                                        <td class="text-center"><span class="badge-soft badge-info">{{ number_format($member->total_tasks) }}</span></td>
                                        <td class="text-center">{{ number_format($member->acknowledged_tasks) }}</td>
                                        <td class="text-center text-success fw-bold">{{ number_format($member->completed_tasks) }}</td>
                                        <td class="text-center {{ $member->unacknowledged_tasks ? 'text-danger fw-bold' : 'text-muted' }}">{{ number_format($member->unacknowledged_tasks) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5"><div class="empty-state py-5">Chưa có công việc được giao trong kỳ này.</div></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

<div class="system-section-title">
    <span><i class="bi bi-bullseye"></i></span>
    <div>
        <h5>{{ $canViewAll ? 'Chỉ tiêu năm '.$year : 'Chỉ tiêu cá nhân năm '.$year }}</h5>
        <small>{{ $canViewAll ? 'KPI được theo dõi riêng theo năm để đối chiếu nhanh ngay trên trang chủ.' : 'Tiến độ KPI của riêng tôi trong năm, dùng để theo dõi kết quả và phần còn thiếu.' }}</small>
    </div>
    <a class="btn btn-sm btn-outline-primary ms-auto" href="{{ route('kpi-dashboard.index', ['year' => $year]) }}">Xem KPI</a>
</div>
<div class="row g-3 mb-4">
    @foreach([
        ['Chỉ tiêu', $kpiTotals['target_quantity'], 'primary'],
        ['Thực hiện', $kpiTotals['actual_quantity'], 'success'],
        ['Còn lại', $kpiTotals['remaining_quantity'], 'warning'],
        ['Vượt chỉ tiêu', $kpiTotals['excess_quantity'], 'danger'],
    ] as [$label, $value, $color])
        <div class="col-sm-6 col-xl-3">
            <a class="dashboard-card-link" href="{{ route('kpi-dashboard.index', ['year' => $year]) }}">
                <div class="card card-soft">
                    <div class="card-body p-4">
                        <div class="stat-label">{{ $label }}</div>
                        <div class="stat-value text-{{ $color }}">{{ number_format($value, 1) }}</div>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>

@if(! $canViewAll && $teachingLoadRoute)
    <div class="system-section-title">
        <span><i class="bi bi-journal-richtext"></i></span>
        <div>
            <h5>Giảng dạy năm {{ $year }}</h5>
            <small>Theo dõi tổng số tiết được giao, đã báo cáo, còn lại và phần vượt của riêng tôi.</small>
        </div>
        <a class="btn btn-sm btn-outline-primary ms-auto" href="{{ $teachingLoadRoute }}">Mở báo cáo tiết dạy</a>
    </div>
    <div class="row g-3 mb-4">
        @foreach([
            ['Tiết được giao', $teachingSummary['assigned'], 'primary'],
            ['Đã báo cáo', $teachingSummary['reported'], 'success'],
            ['Còn lại', $teachingSummary['remaining'], 'warning'],
            ['Vượt tiết', $teachingSummary['exceeded'], 'danger'],
        ] as [$label, $value, $color])
            <div class="col-sm-6 col-xl-3">
                <a class="dashboard-card-link" href="{{ $teachingLoadRoute }}">
                    <div class="card card-soft">
                        <div class="card-body p-4">
                            <div class="stat-label">{{ $label }}</div>
                            <div class="stat-value text-{{ $color }}">{{ number_format($value, 1) }}</div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
    <div class="card card-soft mb-4">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <div class="small text-muted">Tiến độ giảng dạy cá nhân</div>
                <div class="fw-semibold">
                    @if($teachingSummary['has_target'])
                        Đã báo cáo {{ number_format($teachingSummary['reported_months']) }} tháng trong năm {{ $year }}
                    @else
                        Chưa được giao chỉ tiêu tiết dạy cho năm {{ $year }}
                    @endif
                </div>
                <div class="small text-muted mt-2">Tiến độ hiện tại: {{ number_format($teachingSummary['progress'], 1) }}%</div>
                <div class="progress mt-2" role="progressbar" aria-label="Tiến độ giảng dạy" aria-valuenow="{{ $teachingSummary['progress'] }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar {{ $teachingSummary['progress'] > 100 ? 'bg-danger' : 'bg-primary' }}" style="width: {{ $teachingProgressWidth }}%"></div>
                </div>
            </div>
            <div class="text-md-end">
                <div class="small text-muted">Cập nhật cuối</div>
                <div class="fw-semibold">{{ $teachingSummary['last_updated_at']?->format('d/m/Y H:i') ?: 'Chưa có báo cáo' }}</div>
            </div>
        </div>
    </div>
@endif

<div class="system-section-title">
    <span><i class="bi bi-ui-checks-grid"></i></span>
    <div>
        <h5>Trạng thái tổng hợp</h5>
        <small>{{ $canViewAll ? 'Nắm nhanh tình trạng từng nhóm nghiệp vụ theo bộ lọc hiện tại.' : 'Nắm nhanh trạng thái công việc, khách hàng, lớp học và học phí trong phạm vi cá nhân tôi.' }}</small>
    </div>
</div>
<div class="row g-4 mb-4">
    @foreach([
        ['Trạng thái tư vấn', $leadLabels, $leadStatuses, 'bi-headset', 'violet', 'language-leads.index', 'language_leads'],
        ['Trạng thái học viên', $studentLabels, $studentStatuses, 'bi-mortarboard', 'green', 'language-students.index', 'language_students'],
        ['Trạng thái lớp học', $classLabels, $classStatuses, 'bi-easel2', 'orange', 'language-classes.index', 'language_classes'],
        ['Trạng thái học phí', $tuitionLabels, $tuitionStatuses, 'bi-receipt', 'rose', 'language-tuition.index', 'language_tuition'],
    ] as [$title, $labels, $values, $icon, $tone, $route, $permission])
        <div class="col-md-6 col-xl-3">
            @if(auth()->user()->allowed($permission))
                <a class="dashboard-card-link" href="{{ route($route) }}">
            @endif
            <div class="card card-soft h-100 status-summary-card status-card-{{ $tone }}">
                <div class="card-header p-4">
                    <h6 class="mb-0 fw-bold"><span class="status-summary-icon"><i class="bi {{ $icon }}"></i></span>{{ $title }}</h6>
                </div>
                <div class="card-body p-3">
                    @foreach($labels as $key => $label)
                        @php($count = (int) ($values[$key] ?? 0))
                        <div class="status-summary-row status-tone-{{ ($loop->index % 8) + 1 }}">
                            <span>{{ $label }}</span>
                            <strong class="{{ $count === 0 ? 'is-empty' : '' }}">{{ $count }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>
            @if(auth()->user()->allowed($permission))
                </a>
            @endif
        </div>
    @endforeach
</div>

<div class="row g-4">
    @if($canViewAll)
        <div class="col-xl-7">
            @if(auth()->user()->allowed('logs'))
                <a class="dashboard-card-link" href="{{ route('logs.index') }}">
            @endif
            <div class="card card-soft h-100">
                <div class="card-header bg-white p-4">
                    <h5 class="mb-0 fw-bold">Hoạt động gần đây</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-modern">
                        <thead>
                            <tr>
                                <th>Người thực hiện</th>
                                <th>Nội dung</th>
                                <th>Thời gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentActivities as $log)
                                <tr>
                                    <td><strong>{{ $log->user?->name ?: 'Hệ thống' }}</strong></td>
                                    <td>{{ $log->description }}</td>
                                    <td>{{ $log->created_at?->format('d/m/Y H:i') }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3"><div class="empty-state">Không có hoạt động trong kỳ.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if(auth()->user()->allowed('logs'))
                </a>
            @endif
        </div>
    @endif
    <div class="{{ $canViewAll ? 'col-xl-5' : 'col-12' }}">
        @if(auth()->user()->allowed('imports'))
            <a class="dashboard-card-link" href="{{ route('imports.index') }}">
        @endif
        <div class="card card-soft h-100">
            <div class="card-header bg-white p-4">
                <h5 class="mb-0 fw-bold">Dữ liệu nhập gần đây</h5>
            </div>
            <div class="card-body">
                @forelse($recentImports as $batch)
                    <div class="d-flex gap-3 border-bottom py-3">
                        <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-file-earmark-spreadsheet"></i></div>
                        <div>
                            <strong>{{ $batch->original_name }}</strong>
                            <div class="small text-muted">{{ $batch->user?->name }} · {{ $batch->created_at?->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">Không có dữ liệu nhập trong kỳ.</div>
                @endforelse
            </div>
        </div>
        @if(auth()->user()->allowed('imports'))
            </a>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-system-period-filter]');
    const mode = form?.querySelector('[data-period-mode]');
    if (!form || !mode) {
        return;
    }

    const update = () => {
        form.querySelectorAll('[data-period-field]').forEach(field => {
            field.classList.toggle('d-none', field.dataset.periodField !== mode.value);
        });
    };

    mode.addEventListener('change', update);
    update();
});
</script>
@endpush
