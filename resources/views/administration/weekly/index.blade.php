@extends('layouts.app')
@section('title', 'Báo cáo tuần')
@section('header', 'Hành chính')
@section('content')
<div class="administration-page" data-weekly-report data-review-url="{{ route('administration.weekly.check') }}">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div><h1 class="page-title">Báo cáo tuần</h1><div class="page-subtitle">Mỗi tuần là một thẻ riêng; báo cáo đã gửi được lưu đầy đủ tại trang quản lý.</div></div>
        @if($canManage)<div class="d-flex flex-wrap gap-2">@if(!$showEditor)<button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createWeeklyPeriodModal"><i class="bi bi-plus-circle me-1"></i>Tạo tuần</button>@endif<a class="btn btn-outline-primary" href="{{ route('administration.weekly.summary', ['week'=>$weekStart->toDateString()]) }}"><i class="bi bi-collection me-1"></i>Tổng hợp tuần</a></div>@endif
    </div>

    @if($canManage && !$showEditor)
    <form class="card card-soft weekly-period-filter mb-4" method="GET" data-period-filter><div class="card-body row g-3 align-items-end">
        <div class="col-lg-3"><label class="form-label">Lọc danh sách theo</label><select class="form-select" name="filter_type" data-period-filter-type><option value="all" @selected($filterType==='all')>Tất cả thời gian</option><option value="date" @selected($filterType==='date')>Theo ngày</option><option value="month" @selected($filterType==='month')>Theo tháng</option><option value="year" @selected($filterType==='year')>Theo năm</option></select></div>
        <div class="col-lg-5"><div data-filter-field="date"><label class="form-label">Chọn ngày thuộc tuần</label><input class="form-control" type="date" name="filter_date" value="{{ $filterDate }}"></div><div data-filter-field="month"><label class="form-label">Chọn tháng</label><input class="form-control" type="month" name="filter_month" value="{{ $filterMonth }}"></div><div data-filter-field="year"><label class="form-label">Chọn năm</label><input class="form-control" type="number" min="2020" max="2100" name="filter_year" value="{{ $filterYear }}"></div><div data-filter-field="all" class="filter-all-label"><i class="bi bi-calendar3 me-1"></i>Hiển thị toàn bộ tuần đã tạo</div></div>
        <div class="col-lg-4 d-flex gap-2"><button class="btn btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i>Lọc</button><a class="btn btn-outline-secondary" href="{{ route('administration.weekly.index') }}">Đặt lại</a></div>
    </div></form>

    @if($showMissingDetail)
        @php
            $incompleteCards = $weekCards->filter(fn ($card) => $card->week_start->lte(today()) && $card->missingUsers->isNotEmpty());
        @endphp
        <section id="weekly-missing-detail" class="card card-soft weekly-missing-detail mb-4">
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div><strong><i class="bi bi-person-exclamation text-warning me-1"></i>Chi tiết những người chưa gửi báo cáo</strong><div class="small text-muted mt-1">Chỉ tính các tuần đã bắt đầu; bấm “Mở tổng hợp tuần” để xem và xử lý chi tiết.</div></div>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('administration.weekly.index', ['filter_type'=>'all']) }}">Đóng chi tiết</a>
            </div>
            <div class="card-body">
                @forelse($incompleteCards as $card)
                    <article class="weekly-missing-period">
                        <div class="weekly-missing-period-head">
                            <div><strong>{{ $card->title ?: 'Báo cáo tuần '.$card->week_start->isoWeek() }}</strong><small>{{ $card->week_start->format('d/m/Y') }} – {{ $card->week_end->format('d/m/Y') }}</small></div>
                            <span>{{ $card->missingUsers->count() }} người chưa gửi</span>
                        </div>
                        <div class="weekly-missing-people">
                            @foreach($card->missingUsers as $missingUser)
                                <span title="{{ $missingUser->email }}"><i class="bi bi-person"></i>{{ $missingUser->name }}</span>
                            @endforeach
                        </div>
                        <a class="btn btn-sm btn-outline-primary" href="{{ route('administration.weekly.summary', ['week'=>$card->week_start->toDateString()]) }}#summary-overview">Mở tổng hợp tuần <i class="bi bi-arrow-right ms-1"></i></a>
                    </article>
                @empty
                    <div class="empty-state py-4"><i class="bi bi-check-circle text-success me-1"></i>Tất cả các tuần đã bắt đầu đều hoàn tất báo cáo.</div>
                @endforelse
            </div>
        </section>
    @endif

    <div class="modal fade" id="createWeeklyPeriodModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered"><div class="modal-content"><form method="POST" action="{{ route('administration.weekly.periods.store') }}" data-assignee-form>@csrf
        <div class="modal-header"><div><h5 class="modal-title"><i class="bi bi-calendar-plus me-1"></i>Tạo kỳ báo cáo tuần</h5><div class="small text-muted">Chọn thời gian và những tài khoản phải gửi báo cáo về admin.</div></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body"><div class="row g-3">
            <div class="col-md-4"><label class="form-label">Chọn một ngày trong tuần</label><input class="form-control" type="date" name="week_start" required value="{{ old('week_start', now()->startOfWeek()->toDateString()) }}">@error('week_start')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-md-4"><label class="form-label">Tên kỳ báo cáo</label><input class="form-control" name="title" maxlength="180" value="{{ old('title') }}" placeholder="Ví dụ: Tuần 32"></div>
            <div class="col-md-4"><label class="form-label">Trạng thái</label><select class="form-select" name="is_active"><option value="0" @selected(!old('is_active'))>Tạm tắt</option><option value="1" @selected(old('is_active'))>Hoạt động</option></select></div>
            <div class="col-md-6"><label class="form-label">Tự bật lúc</label><input class="form-control" type="datetime-local" name="starts_at" value="{{ old('starts_at') }}"></div><div class="col-md-6"><label class="form-label">Tự tắt lúc</label><input class="form-control" type="datetime-local" name="ends_at" value="{{ old('ends_at') }}">@error('ends_at')<div class="text-danger small">{{ $message }}</div>@enderror</div>
            <div class="col-12"><div class="assignee-picker" data-assignee-group><div class="assignee-picker-head"><div><strong>Người phải báo cáo</strong><small>Admin không nằm trong danh sách báo cáo.</small></div><label class="form-check"><input class="form-check-input" type="checkbox" data-select-all-assignees><span class="form-check-label">Chọn tất cả</span></label></div><div class="assignee-picker-list">@foreach($reportableUsers as $person)<label class="assignee-option"><input class="form-check-input" type="checkbox" name="assigned_user_ids[]" value="{{ $person->id }}" @checked(in_array($person->id, old('assigned_user_ids', $reportableUsers->pluck('id')->all())))><span><strong>{{ $person->name }}</strong><small>{{ $person->email }}</small></span></label>@endforeach</div></div>@error('assigned_user_ids')<div class="text-danger small mt-1">{{ $message }}</div>@enderror</div>
        </div></div><div class="modal-footer"><span class="me-auto small text-muted">Có thể chọn tất cả hoặc tích từng người.</span><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle me-1"></i>Tạo tuần</button></div>
    </form></div></div></div>
    @endif

    @if(!$showEditor)
        @if(!$canManage && !$submissionWindowOpen)
            <div class="weekly-window-closed"><i class="bi bi-calendar2-check"></i><h4>Chưa có kỳ báo cáo đang hoạt động</h4><p>Khi admin bật một kỳ báo cáo, thẻ sẽ xuất hiện tại đây và trên trang Tổng quan. Khi admin tắt, thẻ sẽ ẩn nhưng nội dung đã lưu vẫn được giữ đầy đủ.</p></div>
        @else
            <div class="weekly-report-list">
                <div class="weekly-list-heading"><h3><i class="bi bi-clipboard2-fill"></i> Danh sách báo cáo tuần</h3><button class="btn btn-outline-dark" type="button" onclick="location.reload()" title="Làm mới"><i class="bi bi-arrow-clockwise"></i></button></div>
                @php
                    $lastPeriodGroup = null;
                @endphp
                @forelse($weekCards as $card)
                    @php
                        $periodGroup = 'Tháng '.$card->week_start->format('m/Y');
                        $personalReport = $card->report ?? null;
                        $submitted = $canManage ? (int)$card->submitted_count : ($personalReport?->status === 'submitted' ? 1 : 0);
                    @endphp
                    @if($periodGroup !== $lastPeriodGroup)
                        <div class="weekly-month-divider"><span>{{ $periodGroup }}</span></div>
                        @php
                            $lastPeriodGroup = $periodGroup;
                        @endphp
                    @endif
                    <article class="weekly-report-row {{ $submitted ? 'is-submitted' : 'is-pending' }}">
                        <div class="weekly-row-name"><span class="weekly-row-calendar"><i class="bi bi-calendar3"></i></span><div><strong>{{ $card->title ?: 'Báo cáo tuần '.$card->week_start->isoWeek() }}</strong><small>Tuần {{ $card->week_start->isoWeek() }}/{{ $card->week_start->isoWeekYear() }}</small></div></div>
                        <div class="weekly-row-time"><i class="bi bi-play-fill text-success"></i><div><small>Bắt đầu</small><strong>{{ $card->week_start->format('d/m/Y') }} - Thứ Hai</strong></div></div>
                        <div class="weekly-row-time"><i class="bi bi-stop-fill text-danger"></i><div><small>Kết thúc</small><strong>{{ $card->week_end->format('d/m/Y') }} - Chủ nhật</strong></div></div>
                        <div class="weekly-row-status"><span class="activity-dot {{ $card->effective_active ? 'is-active' : '' }}" title="{{ $card->effective_active ? 'Đang hoạt động' : 'Đã tắt hoặc chưa đến giờ' }}"></span><small>{{ $canManage ? $submitted.'/'.(int)$card->assigned_count.' đã gửi · '.(int)$card->draft_count.' nháp' : ($submitted ? 'Đã gửi' : ($personalReport ? 'Bản nháp' : 'Chưa báo cáo')) }}</small></div>
                        <div class="weekly-row-actions">
                            @if($canManage)
                                <button type="button" data-bs-toggle="modal" data-bs-target="#weeklyReportModal{{ $card->id }}" title="Xem nhanh báo cáo"><i class="bi bi-eye"></i></button>
                                @if($canSubmitReport && $card->assigned_to_current_user)<a href="{{ route('administration.weekly.index', ['week'=>$card->week_start->toDateString(),'open'=>1]) }}" title="{{ $personalReport ? 'Xem hoặc chỉnh sửa báo cáo của tôi' : 'Nhập báo cáo của tôi' }}"><i class="bi bi-pencil-square"></i></a>@endif
                                <form method="POST" action="{{ route('administration.weekly.periods.toggle', $card) }}">@csrf @method('PATCH')<input type="hidden" name="is_active" value="{{ $card->effective_active ? 0 : 1 }}"><button type="submit" title="{{ $card->effective_active ? 'Tắt thủ công' : 'Bật thủ công' }}"><i class="bi {{ $card->effective_active ? 'bi-toggle-on text-success' : 'bi-toggle-off' }}"></i></button></form>
                                @if((int)$card->report_count === 0)
                                    <form method="POST" action="{{ route('administration.weekly.periods.destroy', $card) }}" onsubmit="return confirm('Xóa kỳ báo cáo chưa có dữ liệu này?')">@csrf @method('DELETE')<button type="submit" class="text-danger" title="Xóa kỳ báo cáo"><i class="bi bi-trash"></i></button></form>
                                @elseif(auth()->user()->isAdmin())
                                    <button type="button" class="text-danger" data-bs-toggle="modal" data-bs-target="#forceDeleteWeeklyPeriod{{ $card->id }}" title="Xóa toàn bộ tuần và dữ liệu"><i class="bi bi-trash"></i></button>
                                @else
                                    <button type="button" class="text-muted" disabled title="Tuần đã có dữ liệu; chỉ Admin được xóa toàn bộ"><i class="bi bi-lock-fill"></i></button>
                                @endif
                            @else
                                @if($personalReport)
                                    <button type="button" data-bs-toggle="modal" data-bs-target="#weeklyReportModal{{ $card->id }}" title="Xem nhanh báo cáo"><i class="bi bi-eye"></i></button>
                                    <a href="{{ route('administration.weekly.index', ['week'=>$card->week_start->toDateString(),'open'=>1]) }}" title="Chỉnh sửa báo cáo"><i class="bi bi-pencil-square"></i></a>
                                    <form method="POST" action="{{ route('administration.weekly.destroy', $personalReport) }}" onsubmit="return confirm('Xóa báo cáo tuần này? Nội dung đã nhập sẽ không thể khôi phục.')">@csrf @method('DELETE')<button type="submit" class="text-danger" title="Xóa báo cáo"><i class="bi bi-trash"></i></button></form>
                                @else<a class="weekly-enter-action" href="{{ route('administration.weekly.index', ['week'=>$card->week_start->toDateString(),'open'=>1]) }}" title="Nhập báo cáo"><i class="bi bi-pencil-square"></i><span>Nhập báo cáo</span></a>@endif
                            @endif
                        </div>
                    </article>
                    @if($canManage || $personalReport)
                    <div class="modal fade" id="weeklyReportModal{{ $card->id }}" tabindex="-1" aria-labelledby="weeklyReportModalLabel{{ $card->id }}" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered"><div class="modal-content weekly-preview-modal">
                            <div class="modal-header"><div><h5 class="modal-title" id="weeklyReportModalLabel{{ $card->id }}">{{ $card->title ?: 'Báo cáo tuần '.$card->week_start->isoWeek() }}</h5><div class="small text-muted">Từ {{ $card->week_start->format('d/m/Y') }} đến {{ $card->week_end->format('d/m/Y') }}</div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button></div>
                            <div class="modal-body">
                                @if($canManage)
                                    <div class="row g-3"><div class="col-sm-3"><div class="weekly-preview-stat"><span>Được giao</span><strong>{{ (int)$card->assigned_count }}</strong></div></div><div class="col-sm-3"><div class="weekly-preview-stat"><span>Đã gửi</span><strong>{{ (int)$card->submitted_count }}</strong></div></div><div class="col-sm-3"><div class="weekly-preview-stat"><span>Bản nháp</span><strong>{{ (int)$card->draft_count }}</strong></div></div><div class="col-sm-3"><div class="weekly-preview-stat"><span>Trạng thái</span><strong class="fs-6 {{ $card->effective_active ? 'text-success' : 'text-secondary' }}">{{ $card->effective_active ? 'Hoạt động' : 'Tạm tắt' }}</strong></div></div></div>
                                    @if($card->starts_at || $card->ends_at)<div class="small text-muted mt-3"><i class="bi bi-clock me-1"></i>Lịch tự động: {{ $card->starts_at?->format('H:i d/m/Y') ?? 'ngay lập tức' }} – {{ $card->ends_at?->format('H:i d/m/Y') ?? 'không giới hạn' }}</div>@endif
                                    <form id="periodEdit{{ $card->id }}" class="row g-3 mt-2" method="POST" action="{{ route('administration.weekly.periods.update', $card) }}">@csrf @method('PUT')<div class="col-12"><label class="form-label">Tên kỳ báo cáo</label><input class="form-control" name="title" maxlength="180" value="{{ $card->title }}"></div><div class="col-md-6"><label class="form-label">Tự bật lúc</label><input class="form-control" type="datetime-local" name="starts_at" value="{{ $card->starts_at?->format('Y-m-d\TH:i') }}"></div><div class="col-md-6"><label class="form-label">Tự tắt lúc</label><input class="form-control" type="datetime-local" name="ends_at" value="{{ $card->ends_at?->format('Y-m-d\TH:i') }}"></div><div class="col-12"><div class="assignee-picker is-compact" data-assignee-group><div class="assignee-picker-head"><div><strong>Người phải báo cáo ({{ $card->assigned_count }})</strong></div><label class="form-check"><input class="form-check-input" type="checkbox" data-select-all-assignees><span class="form-check-label">Chọn tất cả</span></label></div><div class="assignee-picker-list">@foreach($reportableUsers as $person)<label class="assignee-option"><input class="form-check-input" type="checkbox" name="assigned_user_ids[]" value="{{ $person->id }}" @checked($card->assignedUsers->contains('id', $person->id))><span><strong>{{ $person->name }}</strong><small>{{ $person->email }}</small></span></label>@endforeach</div></div></div></form>
                                    @if($card->compilation)<div class="weekly-compilation-preview"><div class="small fw-semibold text-uppercase text-muted mb-2">Nội dung tổng hợp</div><div>{{ $card->compilation->content }}</div></div>@else<p class="text-muted mb-0 mt-3">Tuần này chưa có bản tổng hợp. Mở trang tổng hợp để chọn các ý, xem người chưa gửi và tạo nội dung theo 4 mục.</p>@endif
                                @else
                                    @foreach($types as $type => $label)
                                        <section class="weekly-preview-section"><h6>{{ $loop->iteration }}. {{ $label }}</h6>@forelse($personalReport->items->where('type', $type) as $item)<div class="report-rich-output">{!! $item->content !!}</div>@empty<p class="text-muted mb-0">Chưa có nội dung.</p>@endforelse</section>
                                    @endforeach
                                @endif
                            </div>
                            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Đóng</button>@if($canManage)<button class="btn btn-outline-primary" type="submit" form="periodEdit{{ $card->id }}"><i class="bi bi-save me-1"></i>Lưu lịch</button>@endif<a class="btn btn-primary" href="{{ $canManage ? route('administration.weekly.summary', ['week'=>$card->week_start->toDateString()]) : route('administration.weekly.index', ['week'=>$card->week_start->toDateString(),'open'=>1]) }}">{{ $canManage ? 'Mở trang tổng hợp' : 'Chỉnh sửa báo cáo' }}</a></div>
                        </div></div>
                    </div>
                    @endif
                    @if($canManage && auth()->user()->isAdmin() && (int)$card->report_count > 0)
                    <div class="modal fade" id="forceDeleteWeeklyPeriod{{ $card->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered"><div class="modal-content border-danger">
                            <form method="POST" action="{{ route('administration.weekly.periods.destroy', $card) }}" data-force-delete-weekly-form>
                                @csrf @method('DELETE')
                                <input type="hidden" name="delete_with_data" value="1">
                                <div class="modal-header"><div><h5 class="modal-title text-danger"><i class="bi bi-exclamation-octagon-fill me-1"></i>Xóa toàn bộ {{ $card->title ?: 'tuần '.$card->week_start->isoWeek() }}</h5><div class="small text-muted">Thao tác chỉ dành cho Admin và không thể khôi phục.</div></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                <div class="modal-body">
                                    <div class="alert alert-danger"><strong>Sẽ xóa vĩnh viễn:</strong><ul class="mb-0 mt-2"><li>{{ (int)$card->submitted_count }} báo cáo đã gửi</li><li>{{ (int)$card->draft_count }} bản nháp</li><li>Nội dung chi tiết, bản tổng hợp và báo cáo chính thức của tuần</li></ul></div>
                                    @php
                                        $deleteConfirmation = 'XOA TUAN '.$card->week_start->isoWeek();
                                    @endphp
                                    <label class="form-label">Nhập <strong>{{ $deleteConfirmation }}</strong> để xác nhận</label>
                                    <input class="form-control" name="confirmation" required autocomplete="off" data-delete-confirmation="{{ $deleteConfirmation }}" placeholder="{{ $deleteConfirmation }}">
                                    <div class="form-text">Phải nhập đúng câu trên thì nút xóa mới được bật.</div>
                                </div>
                                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Hủy</button><button class="btn btn-danger" type="submit" disabled data-force-delete-submit><i class="bi bi-trash me-1"></i>Xóa toàn bộ tuần</button></div>
                            </form>
                        </div></div>
                    </div>
                    @endif
                @empty<div class="empty-state py-5">Chưa có báo cáo tuần nào được lưu.</div>@endforelse
            </div>
        @endif
    @else
        <div class="d-flex justify-content-between align-items-center gap-3 mb-3"><a class="btn btn-outline-secondary" href="{{ route('administration.weekly.index') }}"><i class="bi bi-arrow-left me-1"></i>Về danh sách tuần</a><div class="small text-muted">Account được nhập báo cáo trong thời gian admin bật hoạt động.</div></div>

        @if($report)<div class="alert {{ $report->status === 'submitted' ? 'alert-success' : 'alert-warning' }} d-flex flex-wrap justify-content-between gap-2"><span><strong>{{ $report->status === 'submitted' ? 'Đã gửi về admin' : 'Bản nháp đã lưu' }}</strong>{{ $report->submitted_at ? ' lúc '.$report->submitted_at->format('H:i d/m/Y') : '' }}</span><span>Độ rõ ràng: <strong>{{ $report->quality_score ?? 0 }}/100</strong></span></div>@endif

        <form class="weekly-entry-form" method="POST" action="{{ route('administration.weekly.save') }}" data-report-form>
            @csrf<input type="hidden" name="week_start" value="{{ $weekStart->toDateString() }}">
            <div class="card card-soft mb-4"><div class="card-body d-flex flex-wrap justify-content-between gap-3">
                <div><div class="small text-muted">Kỳ báo cáo</div><strong>{{ $selectedPeriod?->title ?: 'Tuần '.$weekStart->isoWeek().'/'.$weekStart->isoWeekYear() }}</strong><div class="small text-muted">Từ {{ $weekStart->format('d/m/Y') }} đến {{ $weekEnd->format('d/m/Y') }}</div></div>
                <div><div class="small text-muted">Người báo cáo</div><strong>{{ auth()->user()->name }}</strong><div class="small text-muted">Hạn báo cáo: {{ $dueDate->format('d/m/Y') }}</div></div>
            </div></div>
            @error('items')<div class="alert alert-danger">{{ $message }}</div>@enderror
            <div class="weekly-entry-sections">
                    @foreach($types as $type => $label)
                        @php
                            $savedItems = $report?->items?->where('type', $type)->values() ?? collect();
                            $values = old('items.'.$type, $savedItems->pluck('content')->all());
                            if (!is_array($values) || count($values) === 0) $values = [''];
                        @endphp
                        <section class="weekly-input-section" data-report-section data-type="{{ $type }}" data-placeholder="{{ $typePlaceholders[$type] ?? 'Nhập nội dung…' }}">
                            <div class="weekly-input-heading"><div><span>{{ $loop->iteration }}. {{ $label }}@if($type === 'results') <em>*</em>@endif</span><small>{{ $typeHelp[$type] ?? '' }}</small></div><button type="button" data-add-report-item><i class="bi bi-plus-circle"></i> Thêm ý</button></div>
                            <div data-report-items>
                                @foreach($values as $itemIndex => $value)
                                <div class="weekly-input-item" data-report-item>
                                    <div class="report-rich-editor">
                                        <div class="report-editor-toolbar">
                                            <button type="button" data-editor-command="bold" title="In đậm"><i class="bi bi-type-bold"></i></button><button type="button" data-editor-command="italic" title="In nghiêng"><i class="bi bi-type-italic"></i></button><button type="button" data-editor-command="underline" title="Gạch chân"><i class="bi bi-type-underline"></i></button><span></span><button type="button" data-editor-command="insertUnorderedList" title="Danh sách gạch đầu dòng"><i class="bi bi-list-ul"></i></button><button type="button" data-editor-command="insertOrderedList" title="Danh sách đánh số"><i class="bi bi-list-ol"></i></button><span></span><button type="button" data-editor-link title="Chèn liên kết"><i class="bi bi-link-45deg"></i></button>
                                            <button class="report-item-remove" type="button" data-remove-report-item title="Xóa ý"><i class="bi bi-trash"></i></button>
                                        </div>
                                        <div class="report-editor-content" contenteditable="true" data-rich-editor data-placeholder="{{ $typePlaceholders[$type] ?? 'Nhập nội dung…' }}">{!! $value !!}</div>
                                        <textarea class="d-none" name="items[{{ $type }}][]" maxlength="12000" data-report-content>{{ $value }}</textarea>
                                    </div>
                                    <div class="report-review" data-report-review aria-live="polite"><span class="text-muted small">Nhập nội dung để kiểm tra độ cụ thể.</span></div>
                                </div>
                                @endforeach
                            </div>
                        </section>
                    @endforeach
            </div>
            <div class="weekly-form-actions"><span><i class="bi bi-shield-check me-1"></i>Dữ liệu sau khi gửi được lưu đầy đủ cho admin.</span><div class="d-flex gap-2"><button class="btn btn-outline-secondary" type="submit" name="action" value="draft"><i class="bi bi-floppy me-1"></i>Lưu nháp</button><button class="btn btn-primary" type="submit" name="action" value="submit"><i class="bi bi-send me-1"></i>Lưu và gửi báo cáo</button></div></div>
        </form>
    @endif
</div>
@endsection
@push('scripts')
<script>
(() => {
    const page = document.querySelector('[data-weekly-report]'); if (!page) return;
    const token = document.querySelector('meta[name="csrf-token"]')?.content;
    const escape = value => String(value).replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
    const renderReview = (box, data) => { const messages=[...(data.issues||[]),...(data.suggestions||[])]; box.innerHTML=`<div class="review-result review-${data.passed?'success':'danger'}"><strong>${data.score}/100 · ${data.passed?'Đủ rõ ràng':'Cần bổ sung'}</strong>${messages.length?`<ul>${messages.map(message=>`<li>${escape(message)}</li>`).join('')}</ul>`:''}</div>`; };
    const sync = editor => { const input=editor.closest('[data-report-item]').querySelector('[data-report-content]'); input.value=editor.innerHTML.trim(); return input.value; };
    const check = async editor => { const box=editor.closest('[data-report-item]').querySelector('[data-report-review]'),content=sync(editor); if(!editor.innerText.trim()){box.innerHTML='<span class="text-muted small">Nhập nội dung để kiểm tra độ cụ thể.</span>';return;} box.innerHTML='<span class="text-muted small">Đang kiểm tra…</span>'; try{const response=await fetch(page.dataset.reviewUrl,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':token},body:JSON.stringify({content})});if(response.ok)renderReview(box,await response.json());}catch(_){box.innerHTML='<span class="text-muted small">Chưa thể kiểm tra tự động; bạn vẫn có thể lưu nháp.</span>';}};
    const bind = editor => { let timer; editor.addEventListener('input',()=>{sync(editor);clearTimeout(timer);timer=setTimeout(()=>check(editor),550);}); if(editor.innerText.trim())check(editor); };
    page.querySelectorAll('[data-rich-editor]').forEach(bind);
    page.querySelector('[data-report-form]')?.addEventListener('submit',()=>page.querySelectorAll('[data-rich-editor]').forEach(sync));
    page.addEventListener('mousedown',event=>{if(event.target.closest('.report-editor-toolbar button'))event.preventDefault();});
    page.addEventListener('click',event=>{
        const command=event.target.closest('[data-editor-command]'); if(command){event.preventDefault();const editor=command.closest('[data-report-item]').querySelector('[data-rich-editor]');editor.focus();document.execCommand(command.dataset.editorCommand,false,null);sync(editor);editor.dispatchEvent(new Event('input'));return;}
        const link=event.target.closest('[data-editor-link]'); if(link){event.preventDefault();const editor=link.closest('[data-report-item]').querySelector('[data-rich-editor]'),url=prompt('Nhập liên kết (https://…):','https://');if(url&&/^https?:\/\//i.test(url)){editor.focus();document.execCommand('createLink',false,url);sync(editor);editor.dispatchEvent(new Event('input'));}return;}
        const add=event.target.closest('[data-add-report-item]'); if(add){const section=add.closest('[data-report-section]'),type=section.dataset.type,placeholder=escape(section.dataset.placeholder||'Nhập nội dung…'),wrapper=document.createElement('div');wrapper.className='weekly-input-item';wrapper.dataset.reportItem='';wrapper.innerHTML=`<div class="report-rich-editor"><div class="report-editor-toolbar"><button type="button" data-editor-command="bold" title="In đậm"><i class="bi bi-type-bold"></i></button><button type="button" data-editor-command="italic" title="In nghiêng"><i class="bi bi-type-italic"></i></button><button type="button" data-editor-command="underline" title="Gạch chân"><i class="bi bi-type-underline"></i></button><span></span><button type="button" data-editor-command="insertUnorderedList" title="Danh sách gạch đầu dòng"><i class="bi bi-list-ul"></i></button><button type="button" data-editor-command="insertOrderedList" title="Danh sách đánh số"><i class="bi bi-list-ol"></i></button><span></span><button type="button" data-editor-link title="Chèn liên kết"><i class="bi bi-link-45deg"></i></button><button class="report-item-remove" type="button" data-remove-report-item title="Xóa ý"><i class="bi bi-trash"></i></button></div><div class="report-editor-content" contenteditable="true" data-rich-editor data-placeholder="${placeholder}"></div><textarea class="d-none" name="items[${type}][]" maxlength="12000" data-report-content></textarea></div><div class="report-review" data-report-review><span class="text-muted small">Nhập nội dung để kiểm tra độ cụ thể.</span></div>`;section.querySelector('[data-report-items]').appendChild(wrapper);const editor=wrapper.querySelector('[data-rich-editor]');bind(editor);editor.focus();}
        const remove=event.target.closest('[data-remove-report-item]'); if(remove){const container=remove.closest('[data-report-items]');if(container.querySelectorAll('[data-report-item]').length>1)remove.closest('[data-report-item]').remove();else{const editor=remove.closest('[data-report-item]').querySelector('[data-rich-editor]');editor.innerHTML='';editor.dispatchEvent(new Event('input'));}}
    });
})();
(() => {
    const filterType=document.querySelector('[data-period-filter-type]');
    const syncFilter=()=>document.querySelectorAll('[data-filter-field]').forEach(field=>field.hidden=field.dataset.filterField!==(filterType?.value||'all'));
    filterType?.addEventListener('change',syncFilter);syncFilter();
    document.querySelectorAll('[data-assignee-group]').forEach(group=>{
        const all=group.querySelector('[data-select-all-assignees]'),items=[...group.querySelectorAll('input[name="assigned_user_ids[]"]')];
        const sync=()=>{const checked=items.filter(item=>item.checked).length;all.checked=items.length>0&&checked===items.length;all.indeterminate=checked>0&&checked<items.length;};
        all?.addEventListener('change',()=>{items.forEach(item=>item.checked=all.checked);sync();});items.forEach(item=>item.addEventListener('change',sync));sync();
    });
    if (@json($errors->has('assigned_user_ids') || $errors->has('week_start') || $errors->has('ends_at'))) {
        const modal=document.getElementById('createWeeklyPeriodModal');if(modal) bootstrap.Modal.getOrCreateInstance(modal).show();
    }
})();
document.querySelectorAll('[data-force-delete-weekly-form]').forEach(form => {
    const input = form.querySelector('[data-delete-confirmation]');
    const submit = form.querySelector('[data-force-delete-submit]');
    const sync = () => submit.disabled = input.value.trim().toUpperCase() !== input.dataset.deleteConfirmation;
    input.addEventListener('input', sync);
    form.addEventListener('submit', event => {
        sync();
        if (submit.disabled || !confirm('Xóa vĩnh viễn toàn bộ tuần và tất cả báo cáo liên quan?')) event.preventDefault();
    });
    sync();
});
</script>
@endpush
