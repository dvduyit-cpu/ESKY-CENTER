@extends('layouts.app')
@section('title', 'Tổng hợp báo cáo tuần')
@section('header', 'Hành chính')
@section('content')
<div class="administration-page">
    <div class="d-flex flex-wrap justify-content-between gap-3 mb-4">
        <div><h1 class="page-title">{{ $period->title ?: 'Tổng hợp báo cáo tuần '.$weekStart->isoWeek() }}</h1><div class="page-subtitle">{{ $weekStart->format('d/m/Y') }} – {{ $weekEnd->format('d/m/Y') }} · hạn gửi {{ $dueDate->format('d/m/Y') }} · mã kỳ #{{ $period->id }}</div></div>
        <div class="d-flex gap-2"><a class="btn btn-outline-secondary" href="{{ route('administration.weekly.index', ['period'=>$period->id]) }}">Danh sách báo cáo</a></div>
    </div>

    <nav class="weekly-summary-nav mb-4" aria-label="Đi đến phần nội dung">
        <a href="#summary-overview"><i class="bi bi-speedometer2"></i><span>Tổng quan</span></a>
        <a href="#summary-duplicates"><i class="bi bi-intersect"></i><span>Ý có thể trùng</span><strong>{{ count($duplicateGroups) }}</strong></a>
        <a href="#summary-people"><i class="bi bi-people"></i><span>Người báo cáo</span><strong>{{ $reports->count() }}</strong></a>
        <a href="#summary-compilation"><i class="bi bi-file-earmark-text"></i><span>Bản tổng hợp</span></a>
        <a href="#summary-official"><i class="bi bi-patch-check"></i><span>Bản chính thức</span></a>
    </nav>

    <form id="summary-overview" class="card card-soft weekly-summary-filter mb-4 summary-anchor" method="GET"><input type="hidden" name="period" value="{{ $period->id }}"><div class="card-body row g-3 align-items-end"><div class="col-md-3"><label class="form-label">Kỳ báo cáo</label><input class="form-control" value="{{ $period->title ?: 'Tuần '.$weekStart->isoWeek() }}" readonly></div><div class="col-md-6"><label class="form-label">Lọc theo nhóm công tác</label><select class="form-select" name="work_area"><option value="">Tất cả nhóm công tác</option>@foreach($workAreas as $areaKey => $areaLabel)<option value="{{ $areaKey }}" @selected($workArea === $areaKey)>{{ $areaLabel }}</option>@endforeach</select></div><div class="col-md-3 d-grid"><button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Lọc báo cáo</button></div></div></form>

    <div class="summary-section-heading summary-section-heading-compact"><div><span>01</span><h4>Tình hình tuần báo cáo</h4></div><p>Các chỉ số gửi báo cáo, chất lượng nội dung và cơ cấu công việc trong tuần.</p></div>
    <div class="row g-3 mb-4 director-report-metrics">
        <div class="col-6 col-xl"><div class="metric-card"><span>Được giao</span><strong>{{ $assignedCount }}</strong><small>nhân sự</small></div></div>
        <div class="col-6 col-xl"><div class="metric-card"><span>Đã gửi</span><strong>{{ $reports->count() }}</strong><small>{{ $completionRate }}% hoàn thành</small></div></div>
        <div class="col-6 col-xl"><div class="metric-card"><span>Chưa gửi</span><strong>{{ $missingUsers->count() }}</strong><small>cần nhắc</small></div></div>
        <div class="col-6 col-xl"><div class="metric-card"><span>Gửi đúng hạn</span><strong>{{ $onTimeCount }}</strong><small>{{ $lateCount }} gửi trễ</small></div></div>
        <div class="col-6 col-xl"><div class="metric-card"><span>Độ rõ ràng TB</span><strong>{{ $averageQuality }}</strong><small>/100 điểm</small></div></div>
    </div>

    <div class="card card-soft mb-4 director-work-area-summary"><div class="card-header bg-white d-flex flex-wrap justify-content-between gap-2"><strong><i class="bi bi-bar-chart-fill me-1"></i>Số liệu công việc dành cho giám đốc</strong><span class="small text-muted">{{ $items->count() }} ý đang hiển thị · {{ count($duplicateGroups) }} nhóm có thể trùng</span></div><div class="card-body"><div class="row g-3">
        @foreach($workAreaStats as $areaStat)
        <div class="col-md-6"><div class="director-area-row"><div><strong>{{ $areaStat['label'] }}</strong><small>{{ $areaStat['people'] }} người có nội dung</small></div><span>{{ $areaStat['items'] }} ý</span></div></div>
        @endforeach
    </div></div></div>

    @if($missingUsers->isNotEmpty())
    <div class="missing-report-compact mb-4"><span class="missing-report-label"><i class="bi bi-person-exclamation me-1"></i>Chưa gửi ({{ $missingUsers->count() }}):</span><div class="missing-user-list">
        @foreach($missingUsers as $user)
            <span class="missing-user-mini" title="{{ $user->email }}">{{ $user->name }}</span>
        @endforeach
    </div></div>
    @else
    <div class="alert alert-success d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill"></i><span>Tất cả nhân sự đã gửi báo cáo tuần này.</span></div>
    @endif
    <section id="summary-duplicates" class="summary-anchor mb-4">
    <div class="summary-section-heading"><div><span>02</span><h4>Gợi ý lọc trùng</h4></div><p>Đọc toàn bộ từng ý, đối chiếu người gửi và mức tương đồng trước khi chọn nội dung đưa vào bản tổng hợp.</p></div>
    @if(count($duplicateGroups))
    <div class="duplicate-review-list">
        @foreach($duplicateGroups as $index => $group)
            @php
                $groupItems = $items->whereIn('id', $group['item_ids']);
            @endphp
            <article class="duplicate-review-card">
                <header><div><span class="duplicate-group-number">Nhóm {{ $index + 1 }}</span><strong>{{ $groupItems->count() }} ý cần đối chiếu</strong></div><span class="duplicate-similarity">Tương đồng {{ $group['similarity'] }}%</span></header>
                <div class="duplicate-review-items">
                    @foreach($groupItems as $item)
                    <div class="duplicate-review-item">
                        <div class="duplicate-item-meta">
                            <span class="duplicate-author-avatar">{{ mb_strtoupper(mb_substr($item->report->user->name, 0, 1)) }}</span>
                            <div><strong>{{ $item->report->user->name }}</strong><small>{{ $types[$item->type] ?? 'Nội dung báo cáo' }}</small></div>
                            <span class="duplicate-area-badge">{{ $workAreas[$item->work_area] ?? 'Công tác khác' }}</span>
                        </div>
                        <div class="duplicate-full-content report-rich-output">{!! $item->content !!}</div>
                        <div class="duplicate-quality">Độ rõ ràng <strong>{{ $item->quality_score }}/100</strong></div>
                    </div>
                    @endforeach
                </div>
            </article>
        @endforeach
    </div>
    @endif
    @if(! count($duplicateGroups))
        <div class="duplicate-empty"><i class="bi bi-check2-circle"></i><div><strong>Chưa phát hiện ý gần trùng</strong><small>Không có nhóm nội dung nào vượt ngưỡng tương đồng trong bộ lọc hiện tại.</small></div></div>
    @endif
    </section>

    <form method="POST" action="{{ route('administration.weekly.compile') }}">
        @csrf
        <input type="hidden" name="period_id" value="{{ $period->id }}">
        <input type="hidden" name="week_start" value="{{ $weekStart->toDateString() }}">
        <div id="summary-people" class="card card-soft mb-4 summary-anchor"><div class="card-header bg-white d-flex flex-wrap justify-content-between gap-2"><strong><span class="summary-section-index">03</span>Báo cáo theo từng nhân sự</strong><small class="text-muted">Bấm vào tên hoặc nút xem để đọc nội dung và chọn ý tổng hợp</small></div><div class="card-body">
            @if($items->isEmpty())
                <div class="empty-state">Không có nội dung thuộc nhóm công tác đang chọn.</div>
            @else
            <div class="employee-report-grid">
                @foreach($reports as $report)
                    @php
                        $filteredReportItems = $workArea ? $report->items->where('work_area', $workArea) : $report->items;
                        $initials = collect(preg_split('/\s+/u', trim($report->user->name)))->filter()->take(-2)->map(fn($word) => mb_substr($word, 0, 1))->implode('');
                    @endphp
                    @continue($filteredReportItems->isEmpty())
                    <article class="employee-report-card">
                        <button class="employee-report-main" type="button" data-bs-toggle="modal" data-bs-target="#employeeReportModal{{ $report->id }}">
                            <span class="employee-report-avatar">{{ mb_strtoupper($initials) }}</span><span><strong>{{ $report->user->name }}</strong><small>Gửi {{ $report->submitted_at?->format('H:i d/m/Y') }}</small></span>
                        </button>
                        <div class="employee-report-score"><strong>{{ $report->quality_score }}</strong><small>/100</small></div>
                        <span class="employee-report-count">{{ $filteredReportItems->count() }} ý</span>
                        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#employeeReportModal{{ $report->id }}"><i class="bi bi-eye me-1"></i>Xem</button>
                    </article>

                    <div class="modal fade" id="employeeReportModal{{ $report->id }}" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered"><div class="modal-content employee-report-modal">
                        <div class="modal-header"><div><h5 class="modal-title">{{ $report->user->name }}</h5><div class="small text-muted">Gửi {{ $report->submitted_at?->format('H:i d/m/Y') }} · độ rõ ràng {{ $report->quality_score }}/100</div></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body">
                            @foreach($types as $type => $typeLabel)
                            <section class="employee-report-section"><h6>{{ $loop->iteration }}. {{ $typeLabel }}</h6>
                                @forelse($filteredReportItems->where('type', $type) as $item)
                                <label class="summary-item {{ $duplicateItemIds->contains($item->id) ? 'is-duplicate' : '' }}">
                                    <input class="form-check-input mt-1" type="checkbox" name="selected_item_ids[]" value="{{ $item->id }}" {{ in_array($item->id, old('selected_item_ids', $compilation?->source_item_ids ?? $items->pluck('id')->all())) ? 'checked' : '' }}>
                                    <span><select class="form-select form-select-sm summary-work-area" data-work-area-select data-url="{{ route('administration.weekly.items.work-area', $item) }}" aria-label="Phân loại công tác">@foreach($workAreas as $areaKey => $areaLabel)<option value="{{ $areaKey }}" @selected($item->work_area === $areaKey)>{{ $areaLabel }}</option>@endforeach</select><span class="report-rich-output">{!! $item->content !!}</span><small class="d-block {{ $item->quality_score >= 60 ? 'text-success' : 'text-danger' }}">Độ rõ ràng {{ $item->quality_score }}/100{{ $duplicateItemIds->contains($item->id) ? ' · Có thể trùng ý' : '' }}</small></span>
                                </label>
                                @empty<p class="text-muted small mb-0">Không có nội dung.</p>@endforelse
                            </section>
                            @endforeach
                        </div><div class="modal-footer"><span class="me-auto small text-muted"><i class="bi bi-check2-square me-1"></i>Tích các ý muốn đưa vào báo cáo tổng hợp.</span><button type="button" class="btn btn-primary" data-bs-dismiss="modal">Xong</button></div>
                    </div></div></div>
                @endforeach
            </div>
            @endif
        </div></div>

        <div id="summary-compilation" class="card card-soft summary-anchor"><div class="card-header bg-white"><strong><span class="summary-section-index">04</span>Nội dung báo cáo tổng hợp</strong></div><div class="card-body">
            <p class="small text-muted">AI sẽ đọc các ý đã chọn, tách đầu việc, sửa lỗi nhẹ và phân vào đúng 4 mục mà không ghi tên người báo cáo. <strong class="text-dark">Nội dung giống hoặc gần trùng chỉ được giữ lại một ý rõ ràng, đầy đủ nhất trong toàn bộ bản tổng hợp.</strong> Admin vẫn có thể chỉnh sửa tự do trước khi lưu.</p>
            <textarea class="form-control" name="content" rows="14" maxlength="50000" placeholder="Nội dung tổng hợp sẽ được tạo từ các ý đã chọn…">{{ old('content', $compilation?->content) }}</textarea>
            @error('selected_item_ids')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
            <div class="d-flex flex-wrap justify-content-end gap-2 mt-3"><button class="btn btn-outline-primary" type="submit" name="regenerate" value="1" onclick="return confirm('Cho AI phân tích lại các ý đang chọn? Nội dung đang chỉnh sửa trong ô sẽ được thay thế.')"><i class="bi bi-stars me-1"></i>AI phân tích và tạo 4 mục</button><button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Lưu bản tổng hợp</button></div>
        </div></div>

        <div id="summary-official" class="card card-soft summary-anchor mt-4"><div class="card-header bg-white"><strong><span class="summary-section-index">05</span>Nội dung báo cáo chính thức</strong></div><div class="card-body">
            <p class="small text-muted">AI kiểm tra lại bản chất từng đầu việc, loại nội dung trùng và phân vào 4 nhóm: <strong>tư vấn – chăm sóc, giáo vụ, giảng dạy và công tác khác</strong>. Mỗi ý trùng chỉ xuất hiện một lần và Admin được chỉnh sửa tự do trước khi lưu.</p>
            <textarea class="form-control official-report-content" name="official_content" rows="16" maxlength="50000" placeholder="1. Công tác tư vấn – chăm sóc&#10;&#10;2. Công tác giáo vụ&#10;&#10;3. Công tác giảng dạy&#10;&#10;4. Công tác khác">{{ old('official_content', $compilation?->official_content) }}</textarea>
            <div class="d-flex flex-wrap justify-content-end gap-2 mt-3"><button class="btn btn-outline-primary" type="submit" name="regenerate_official" value="1" onclick="return confirm('Cho AI kiểm tra và phân loại lại báo cáo chính thức? Nội dung hiện tại trong ô này sẽ được thay thế.')"><i class="bi bi-stars me-1"></i>AI kiểm tra và phân 4 nhóm</button><button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Lưu báo cáo chính thức</button></div>
        </div></div>
    </form>
</div>
@endsection
@push('scripts')
<script>
document.querySelectorAll('[data-work-area-select]').forEach(select => select.addEventListener('change', async () => {
    const previous = select.dataset.savedValue || select.defaultValue;
    select.disabled = true;
    try {
        const response = await fetch(select.dataset.url, {method:'PATCH', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content}, body:JSON.stringify({work_area:select.value})});
        if (!response.ok) throw new Error();
        select.dataset.savedValue = select.value;
        select.classList.add('is-valid');
        setTimeout(() => select.classList.remove('is-valid'), 1200);
    } catch (_) {
        select.value = previous;
        alert('Chưa lưu được nhóm công tác. Vui lòng thử lại.');
    } finally { select.disabled = false; }
}));
document.querySelectorAll('[data-work-area-select]').forEach(select => select.dataset.savedValue = select.value);
</script>
@endpush
