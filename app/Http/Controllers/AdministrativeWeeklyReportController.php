<?php

namespace App\Http\Controllers;

use App\Models\AdministrativeWeeklyCompilation;
use App\Models\AdministrativeWeeklyPeriod;
use App\Models\AdministrativeWeeklyReport;
use App\Models\AdministrativeWeeklyReportItem;
use App\Models\User;
use App\Support\AdministrativeReportReviewer;
use App\Support\AdministrativeReportAiCompiler;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdministrativeWeeklyReportController extends Controller
{
    public const TYPES = [
        'results' => 'Công tác thực hiện',
        'other_work' => 'Công tác khác',
        'proposals' => 'Đề xuất, kiến nghị',
        'next_plan' => 'Kế hoạch trong tuần tới',
    ];

    public const WORK_AREAS = [
        'consulting_care' => 'Công tác tư vấn và chăm sóc người học',
        'academic_affairs' => 'Công tác giáo vụ',
        'teaching' => 'Công tác giảng dạy',
        'other' => 'Công tác khác',
    ];

    public const TYPE_HELP = [
        'results' => 'Căn cứ theo nhiệm vụ được giao trong phân công công việc.',
        'other_work' => 'Các công việc phát sinh hoặc hỗ trợ ngoài nhiệm vụ được giao.',
        'proposals' => 'Nêu rõ nội dung cần hỗ trợ, người/bộ phận xử lý và thời hạn mong muốn.',
        'next_plan' => 'Nêu công việc dự kiến, kết quả cần đạt và thời gian hoàn thành.',
    ];

    public const TYPE_PLACEHOLDERS = [
        'results' => 'Liệt kê từng nhiệm vụ được giao, kết quả/số liệu đã thực hiện và thời gian hoàn thành…',
        'other_work' => 'Nhập các công việc phát sinh, công việc phối hợp hoặc hỗ trợ trong tuần…',
        'proposals' => 'Nhập đề xuất, kiến nghị hoặc nội dung cần admin/bộ phận liên quan hỗ trợ…',
        'next_plan' => 'Nhập kế hoạch công việc tuần tới, mục tiêu và thời hạn dự kiến…',
    ];

    public function __construct(
        private readonly AdministrativeReportReviewer $reviewer,
        private readonly AdministrativeReportAiCompiler $aiCompiler,
    )
    {
    }

    public function index(Request $request): View
    {
        $canManage = $request->user()->isLeader();
        $canSubmitReport = ! $request->user()->isAdmin();
        $periods = AdministrativeWeeklyPeriod::query()
            ->with('assignedUsers:id,name,email')
            ->when(! $canManage, fn ($query) => $query->activeNow()
                ->whereHas('assignedUsers', fn ($users) => $users->whereKey($request->user()->id)))
            ->when($canManage, fn ($query) => $this->applyPeriodFilter($query, $request))
            ->latest('week_start')
            ->get();
        $requestedWeek = $request->filled('week') ? $this->weekStart($request->query('week')) : null;
        $selectedPeriod = $requestedWeek
            ? $periods->first(fn ($period) => $period->week_start->isSameDay($requestedWeek))
            : $periods->first();
        $weekStart = $selectedPeriod?->week_start?->copy() ?? now()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $submissionWindowOpen = $canManage ? (bool) $selectedPeriod : (bool) $selectedPeriod?->isCurrentlyActive();
        $report = AdministrativeWeeklyReport::query()
            ->with('items')
            ->where('user_id', $request->user()->id)
            ->whereDate('week_start', $weekStart)
            ->first();
        $weekKeys = $periods->pluck('week_start')->map->toDateString();
        $compilations = $canManage ? AdministrativeWeeklyCompilation::query()
            ->whereIn('week_start', $weekKeys)
            ->get()
            ->keyBy(fn ($row) => $row->week_start->toDateString()) : collect();
        $personalReports = AdministrativeWeeklyReport::query()
            ->with('items')
            ->where('user_id', $request->user()->id)
            ->whereIn('week_start', $weekKeys)
            ->get()
            ->keyBy(fn ($row) => $row->week_start->toDateString());
        $weekCards = $periods->each(function ($period) use ($personalReports, $compilations, $canManage, $request): void {
            $key = $period->week_start->toDateString();
            $periodReports = AdministrativeWeeklyReport::query()->whereDate('week_start', $period->week_start)
                ->whereIn('user_id', $period->assignedUsers->pluck('id'))
                ->get(['user_id', 'status']);
            $submittedUserIds = $periodReports->where('status', 'submitted')->pluck('user_id');
            $period->setAttribute('report_count', $periodReports->count());
            $period->setAttribute('submitted_count', $submittedUserIds->count());
            $period->setAttribute('draft_count', $periodReports->where('status', 'draft')->count());
            $period->setAttribute('assigned_count', $period->assignedUsers->count());
            $period->setAttribute('assigned_to_current_user', $period->assignedUsers->contains('id', $request->user()->id));
            $period->setAttribute('effective_active', $period->isCurrentlyActive());
            if ($canManage) {
                $period->setRelation('compilation', $compilations->get($key));
                $period->setRelation('missingUsers', $period->assignedUsers->whereNotIn('id', $submittedUserIds)->sortBy('name')->values());
            }
            $period->setRelation('report', $personalReports->get($key));
        });

        return view('administration.weekly.index', [
            'weekStart' => $weekStart,
            'weekEnd' => $selectedPeriod?->week_end?->copy() ?? $weekStart->copy()->endOfWeek(Carbon::SUNDAY),
            'dueDate' => $selectedPeriod?->due_date?->copy() ?? $weekStart->copy()->addDays(2),
            'report' => $report,
            'weekCards' => $weekCards,
            'selectedPeriod' => $selectedPeriod,
            'types' => self::TYPES,
            'workAreas' => self::WORK_AREAS,
            'typeHelp' => self::TYPE_HELP,
            'typePlaceholders' => self::TYPE_PLACEHOLDERS,
            'canManage' => $canManage,
            'canSubmitReport' => $canSubmitReport,
            'submissionWindowOpen' => $submissionWindowOpen,
            'showEditor' => $canSubmitReport && $submissionWindowOpen && (bool) $selectedPeriod?->assignedUsers->contains('id', $request->user()->id) && $request->boolean('open'),
            'reportableUsers' => User::query()->where('active', true)
                ->whereDoesntHave('role', fn ($role) => $role->where('code', 'admin'))
                ->orderBy('name')->get(['id', 'name', 'email']),
            'filterType' => $request->query('filter_type', 'all'),
            'filterDate' => $request->query('filter_date'),
            'filterMonth' => $request->query('filter_month'),
            'filterYear' => $request->query('filter_year', now()->year),
            'showMissingDetail' => $canManage && $request->boolean('show_missing'),
        ]);
    }

    public function storePeriod(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isLeader(), 403);
        $data = $request->validate([
            'week_start' => ['required', 'date'],
            'title' => ['nullable', 'string', 'max:180'],
            'is_active' => ['nullable', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'assigned_user_ids' => ['required', 'array', 'min:1'],
            'assigned_user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);
        $assigneeIds = $this->validAssigneeIds($data['assigned_user_ids']);
        if ($assigneeIds === []) throw ValidationException::withMessages(['assigned_user_ids' => 'Hãy chọn ít nhất một tài khoản đang hoạt động, không bao gồm admin.']);
        $weekStart = Carbon::parse($data['week_start'])->startOfWeek(Carbon::MONDAY)->startOfDay();
        $isActive = $request->boolean('is_active');
        $startsAt = filled($data['starts_at'] ?? null) ? Carbon::parse($data['starts_at']) : null;
        $endsAt = filled($data['ends_at'] ?? null) ? Carbon::parse($data['ends_at']) : null;
        $period = AdministrativeWeeklyPeriod::query()->updateOrCreate(
            ['week_start' => $weekStart->toDateString()],
            [
                'week_end' => $weekStart->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(),
                'due_date' => $weekStart->copy()->addDays(2)->toDateString(),
                'title' => trim((string) ($data['title'] ?? '')) ?: null,
                'is_active' => $isActive,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'created_by' => $request->user()->id,
                'activated_by' => $isActive ? $request->user()->id : null,
                'activated_at' => $isActive ? now() : null,
            ]
        );
        $period->assignedUsers()->sync($assigneeIds);

        return redirect()->route('administration.weekly.index')->with('success', 'Đã tạo kỳ báo cáo tuần.');
    }

    public function togglePeriod(Request $request, AdministrativeWeeklyPeriod $period): RedirectResponse
    {
        abort_unless($request->user()->isLeader(), 403);
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $isActive = (bool) $data['is_active'];
        $period->update([
            'is_active' => $isActive,
            'starts_at' => null,
            'ends_at' => null,
            'activated_by' => $isActive ? $request->user()->id : null,
            'activated_at' => $isActive ? now() : null,
        ]);

        return back()->with('success', $isActive ? 'Đã bật kỳ báo cáo. Các account đã nhìn thấy thẻ.' : 'Đã tắt kỳ báo cáo. Thẻ đã ẩn khỏi các account.');
    }

    public function updatePeriod(Request $request, AdministrativeWeeklyPeriod $period): RedirectResponse
    {
        abort_unless($request->user()->isLeader(), 403);
        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:180'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'assigned_user_ids' => ['required', 'array', 'min:1'],
            'assigned_user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);
        $assigneeIds = $this->validAssigneeIds($data['assigned_user_ids']);
        if ($assigneeIds === []) throw ValidationException::withMessages(['assigned_user_ids' => 'Hãy chọn ít nhất một tài khoản đang hoạt động, không bao gồm admin.']);
        $period->update([
            'title' => trim((string) ($data['title'] ?? '')) ?: null,
            'starts_at' => filled($data['starts_at'] ?? null) ? Carbon::parse($data['starts_at']) : null,
            'ends_at' => filled($data['ends_at'] ?? null) ? Carbon::parse($data['ends_at']) : null,
            'is_active' => filled($data['starts_at'] ?? null) || filled($data['ends_at'] ?? null) ? false : $period->is_active,
        ]);
        $period->assignedUsers()->sync($assigneeIds);

        return back()->with('success', 'Đã cập nhật tên và lịch hoạt động của kỳ báo cáo.');
    }

    public function destroyPeriod(Request $request, AdministrativeWeeklyPeriod $period): RedirectResponse
    {
        abort_unless($request->user()->isLeader(), 403);
        $reportQuery = AdministrativeWeeklyReport::query()->whereDate('week_start', $period->week_start);
        $reportCount = (clone $reportQuery)->count();
        $hasReports = $reportCount > 0;
        if ($hasReports) {
            if (! $request->boolean('delete_with_data')) {
                return back()->with('error', 'Tuần này đã có dữ liệu. Chỉ Admin mới được dùng chức năng “Xóa toàn bộ tuần”.');
            }
            abort_unless($request->user()->isAdmin(), 403);

            $confirmation = 'XOA TUAN '.$period->week_start->isoWeek();
            if (! hash_equals($confirmation, mb_strtoupper(trim((string) $request->input('confirmation'))))) {
                return back()->with('error', 'Câu xác nhận chưa đúng. Hãy nhập chính xác: '.$confirmation);
            }

            $draftCount = (clone $reportQuery)->where('status', 'draft')->count();
            $submittedCount = (clone $reportQuery)->where('status', 'submitted')->count();
            DB::transaction(function () use ($period, $reportQuery): void {
                AdministrativeWeeklyCompilation::query()->whereDate('week_start', $period->week_start)->delete();
                (clone $reportQuery)->delete();
                $period->delete();
            });

            return redirect()->route('administration.weekly.index')
                ->with('success', 'Đã xóa toàn bộ tuần '.$period->week_start->isoWeek().', gồm '.$submittedCount.' báo cáo đã gửi và '.$draftCount.' bản nháp. Dữ liệu không thể khôi phục.');
        }
        $period->delete();

        return back()->with('success', 'Đã xóa kỳ báo cáo chưa có dữ liệu.');
    }

    public function save(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'week_start' => ['required', 'date'],
            'action' => ['required', 'in:draft,submit'],
            'items' => ['required', 'array'],
            'items.*' => ['nullable', 'array', 'max:10'],
            'items.*.*' => ['nullable', 'string', 'max:12000'],
            'work_areas' => ['nullable', 'array'],
            'work_areas.*' => ['nullable', 'array', 'max:10'],
            'work_areas.*.*' => ['nullable', 'string', 'in:'.implode(',', array_keys(self::WORK_AREAS))],
        ]);
        $weekStart = Carbon::parse($data['week_start'])->startOfWeek(Carbon::MONDAY)->startOfDay();
        $period = AdministrativeWeeklyPeriod::query()->whereDate('week_start', $weekStart)->first();
        if (! $period || (! $request->user()->isLeader() && ! $period->isCurrentlyActive())) {
            throw ValidationException::withMessages([
                'items' => 'Kỳ báo cáo này chưa được admin bật hoạt động hoặc đã được tắt.',
            ]);
        }
        if (! $request->user()->isAdmin() && ! $period->assignedUsers()->whereKey($request->user()->id)->exists()) {
            throw ValidationException::withMessages(['items' => 'Bạn không nằm trong danh sách phải báo cáo của tuần này.']);
        }
        $workAreas = collect($data['work_areas'] ?? []);
        $rows = collect($data['items'])
            ->only(array_keys(self::TYPES))
            ->flatMap(function ($items, string $type) use ($workAreas) {
                $areas = collect($workAreas->get($type, []));
                return collect($items)->map(fn ($content, $index) => [
                    'type' => $type,
                    'work_area' => array_key_exists((string) $areas->get($index), self::WORK_AREAS) ? $areas->get($index) : 'other',
                    'content' => $this->sanitizeRichText((string) $content),
                ]);
            })
            ->filter(fn (array $row) => $this->plainText((string) $row['content']) !== '')
            ->values();

        if ($rows->where('type', 'results')->isEmpty()) {
            throw ValidationException::withMessages(['items.results' => 'Cần nhập ít nhất một kết quả công việc trong tuần.']);
        }

        $reviewedRows = $rows->map(function (array $row): array {
            $review = $this->reviewer->review($row['content']);
            return $row + ['review' => $review];
        });
        if ($data['action'] === 'submit' && $reviewedRows->contains(fn (array $row) => ! $row['review']['passed'])) {
            throw ValidationException::withMessages([
                'items' => 'Báo cáo còn ý chưa cụ thể (dưới 60 điểm). Hãy xem cảnh báo dưới từng nội dung rồi bổ sung trước khi gửi.',
            ]);
        }

        $averageScore = (int) round($reviewedRows->avg(fn (array $row) => $row['review']['score']));
        DB::transaction(function () use ($request, $weekStart, $period, $data, $reviewedRows, $averageScore): void {
            $report = AdministrativeWeeklyReport::query()->updateOrCreate(
                ['user_id' => $request->user()->id, 'week_start' => $weekStart->toDateString()],
                [
                    'week_end' => $period->week_end->toDateString(),
                    'due_date' => $period->due_date->toDateString(),
                    'status' => $data['action'] === 'submit' ? 'submitted' : 'draft',
                    'quality_score' => $averageScore,
                    'review_payload' => ['item_count' => $reviewedRows->count()],
                    'submitted_at' => $data['action'] === 'submit' ? now() : null,
                ]
            );
            $report->items()->delete();
            foreach ($reviewedRows as $sortOrder => $row) {
                $report->items()->create([
                    'type' => $row['type'],
                    'work_area' => $row['work_area'],
                    'content' => $row['content'],
                    'normalized_content' => $row['review']['normalized'],
                    'quality_score' => $row['review']['score'],
                    'review_payload' => $row['review'],
                    'sort_order' => $sortOrder,
                ]);
            }
        });

        return redirect()->route('administration.weekly.index', ['week' => $weekStart->toDateString()])
            ->with('success', $data['action'] === 'submit' ? 'Đã gửi báo cáo tuần về quản lý.' : 'Đã lưu bản nháp báo cáo tuần.');
    }

    public function check(Request $request): JsonResponse
    {
        $data = $request->validate(['content' => ['nullable', 'string', 'max:3000']]);
        return response()->json($this->reviewer->review((string) ($data['content'] ?? '')));
    }

    public function updateWorkArea(Request $request, AdministrativeWeeklyReportItem $item): JsonResponse
    {
        abort_unless($request->user()->isLeader(), 403);
        $data = $request->validate([
            'work_area' => ['required', 'string', 'in:'.implode(',', array_keys(self::WORK_AREAS))],
        ]);
        $item->update(['work_area' => $data['work_area']]);

        return response()->json(['saved' => true, 'label' => self::WORK_AREAS[$data['work_area']]]);
    }

    public function destroyReport(Request $request, AdministrativeWeeklyReport $report): RedirectResponse
    {
        abort_unless($request->user()->isLeader() || $report->user_id === $request->user()->id, 403);
        $weekStart = $report->week_start->toDateString();
        $report->delete();

        return redirect()->route('administration.weekly.index', ['week' => $weekStart])
            ->with('success', 'Đã xóa báo cáo tuần.');
    }

    public function summary(Request $request): View
    {
        abort_unless($request->user()->isLeader(), 403);
        $weekStart = $this->weekStart($request->query('week'));
        $period = AdministrativeWeeklyPeriod::query()->with('assignedUsers:id,name,email')->whereDate('week_start', $weekStart)->first();
        $assignedUserIds = $period?->assignedUsers->pluck('id') ?? collect();
        $reports = AdministrativeWeeklyReport::query()
            ->with(['user:id,name,email', 'items'])
            ->whereDate('week_start', $weekStart)
            ->whereIn('user_id', $assignedUserIds)
            ->where('status', 'submitted')
            ->orderBy('submitted_at')
            ->get();
        $workArea = $request->query('work_area');
        $workArea = is_string($workArea) && array_key_exists($workArea, self::WORK_AREAS) ? $workArea : null;
        $allItems = $reports->flatMap->items->values();
        $items = $workArea ? $allItems->where('work_area', $workArea)->values() : $allItems;
        $duplicateGroups = $this->reviewer->duplicateGroups($items);
        $submittedUserIds = $reports->pluck('user_id');
        $missingUsers = ($period?->assignedUsers ?? collect())
            ->whereNotIn('id', $submittedUserIds)
            ->sortBy('name')->values();
        $compilation = AdministrativeWeeklyCompilation::query()->whereDate('week_start', $weekStart)->first();
        $assignedCount = $period?->assignedUsers->count() ?? 0;
        $submittedCount = $reports->count();
        $onTimeCount = $reports->filter(fn ($report) => $report->submitted_at?->lessThanOrEqualTo($report->due_date->copy()->endOfDay()))->count();
        $workAreaStats = collect(self::WORK_AREAS)->map(function (string $label, string $key) use ($allItems): array {
            $areaItems = $allItems->where('work_area', $key);
            return ['key' => $key, 'label' => $label, 'items' => $areaItems->count(), 'people' => $areaItems->pluck('report.user_id')->unique()->count()];
        })->values();

        return view('administration.weekly.summary', [
            'weekStart' => $weekStart,
            'weekEnd' => $weekStart->copy()->endOfWeek(Carbon::SUNDAY),
            'dueDate' => $weekStart->copy()->addDays(2),
            'reports' => $reports,
            'items' => $items,
            'duplicateGroups' => $duplicateGroups,
            'duplicateItemIds' => collect($duplicateGroups)->flatMap(fn (array $group) => $group['item_ids'])->unique(),
            'missingUsers' => $missingUsers,
            'compilation' => $compilation,
            'types' => self::TYPES,
            'workAreas' => self::WORK_AREAS,
            'workArea' => $workArea,
            'assignedCount' => $assignedCount,
            'completionRate' => $assignedCount > 0 ? round($submittedCount / $assignedCount * 100, 1) : 0,
            'averageQuality' => $submittedCount > 0 ? (int) round($reports->avg('quality_score')) : 0,
            'onTimeCount' => $onTimeCount,
            'lateCount' => $submittedCount - $onTimeCount,
            'workAreaStats' => $workAreaStats,
        ]);
    }

    public function compile(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isLeader(), 403);
        $data = $request->validate([
            'week_start' => ['required', 'date'],
            'selected_item_ids' => ['nullable', 'array'],
            'selected_item_ids.*' => ['integer'],
            'content' => ['nullable', 'string', 'max:50000'],
            'official_content' => ['nullable', 'string', 'max:50000'],
            'regenerate' => ['nullable', 'boolean'],
            'regenerate_official' => ['nullable', 'boolean'],
        ]);
        $weekStart = Carbon::parse($data['week_start'])->startOfWeek(Carbon::MONDAY)->startOfDay();
        $assignedUserIds = AdministrativeWeeklyPeriod::query()->with('assignedUsers:id')->whereDate('week_start', $weekStart)
            ->first()?->assignedUsers->pluck('id') ?? collect();
        $availableItems = AdministrativeWeeklyReportItem::query()
            ->with('report.user:id,name')
            ->whereHas('report', fn ($query) => $query->whereDate('week_start', $weekStart)->where('status', 'submitted')->whereIn('user_id', $assignedUserIds))
            ->get();
        $selectedIds = collect($data['selected_item_ids'] ?? [])->map(fn ($id) => (int) $id)->unique();
        $selected = $availableItems->whereIn('id', $selectedIds)->values();

        if ($selected->isEmpty() && trim((string) ($data['content'] ?? '')) === '') {
            throw ValidationException::withMessages(['selected_item_ids' => 'Hãy chọn ít nhất một ý hoặc nhập nội dung tổng hợp.']);
        }
        if ($selectedIds->diff($availableItems->pluck('id'))->isNotEmpty()) {
            abort(422, 'Có nội dung không thuộc báo cáo tuần đã gửi.');
        }

        $uniqueSelected = $this->reviewer->deduplicate($selected);
        $regenerateOfficial = $request->boolean('regenerate_official');
        $content = $request->boolean('regenerate') ? '' : trim((string) ($data['content'] ?? ''));
        $usedAi = false;
        if ($content === '' && ! $regenerateOfficial) {
            $content = $this->aiCompiler->compile($uniqueSelected);
            $usedAi = $content !== null;
        }
        if ($content === null || $content === '') {
            $content = collect(self::TYPES)->map(function (string $heading, string $type) use ($uniqueSelected): string {
                $number = array_search($type, array_keys(self::TYPES), true) + 1;
                if ($type === 'results') {
                    $heading .= ' (căn cứ theo nhiệm vụ được giao trong phân công công việc)';
                }

                $lines = $uniqueSelected->where('type', $type)
                    ->flatMap(fn ($item) => $this->plainTextLines($item->content))
                    ->map(fn (string $line) => '- '.$line)
                    ->implode("\n");

                return $number.'. '.$heading."\n".($lines !== '' ? $lines : '- Không có nội dung.');
            })->implode("\n\n");
        }

        $officialContent = $regenerateOfficial ? '' : trim((string) ($data['official_content'] ?? ''));
        $usedOfficialAi = false;
        if ($officialContent === '' && $regenerateOfficial) {
            $officialContent = $this->aiCompiler->compileOfficial($uniqueSelected);
            $usedOfficialAi = $officialContent !== null;
        }
        if ($officialContent === null || $officialContent === '') {
            $officialHeadings = [
                'consulting_care' => 'Công tác tư vấn – chăm sóc',
                'academic_affairs' => 'Công tác giáo vụ',
                'teaching' => 'Công tác giảng dạy',
                'other' => 'Công tác khác',
            ];
            $officialContent = collect($officialHeadings)->map(function (string $heading, string $workArea) use ($uniqueSelected, $officialHeadings): string {
                $number = array_search($workArea, array_keys($officialHeadings), true) + 1;
                $lines = $uniqueSelected->where('work_area', $workArea)
                    ->flatMap(fn ($item) => $this->plainTextLines($item->content))
                    ->map(fn (string $line) => '- '.$line)
                    ->implode("\n");

                return $number.'. '.$heading."\n".($lines !== '' ? $lines : '- Không có nội dung.');
            })->implode("\n\n");
        }

        AdministrativeWeeklyCompilation::query()->updateOrCreate(
            ['week_start' => $weekStart->toDateString()],
            [
                'week_end' => $weekStart->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(),
                'content' => $content,
                'official_content' => $officialContent,
                'source_item_ids' => $uniqueSelected->pluck('id')->values()->all(),
                'duplicate_groups' => $this->reviewer->duplicateGroups($availableItems),
                'compiled_by' => $request->user()->id,
                'compiled_at' => now(),
            ]
        );

        return redirect()->route('administration.weekly.summary', ['week' => $weekStart->toDateString()])
            ->with('success', ($usedAi || $usedOfficialAi)
                ? 'AI đã lọc trùng, phân loại và lưu nội dung tổng hợp cùng báo cáo chính thức.'
                : 'Đã lưu bản tổng hợp và mỗi nội dung trùng chỉ giữ một ý. Muốn AI tự phân tích, hãy cấu hình OPENAI_API_KEY.');
    }

    private function weekStart(mixed $value): Carbon
    {
        try {
            return ($value ? Carbon::parse((string) $value) : now())->startOfWeek(Carbon::MONDAY)->startOfDay();
        } catch (\Throwable) {
            return now()->startOfWeek(Carbon::MONDAY)->startOfDay();
        }
    }

    private function validAssigneeIds(array $ids): array
    {
        return User::query()->whereIn('id', $ids)->where('active', true)
            ->whereDoesntHave('role', fn ($role) => $role->where('code', 'admin'))
            ->pluck('id')->all();
    }

    private function applyPeriodFilter($query, Request $request): void
    {
        $type = $request->query('filter_type', 'all');
        try {
            if ($type === 'date' && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('filter_date'))) {
                $date = Carbon::createFromFormat('Y-m-d', $request->query('filter_date'))->startOfDay();
                $query->whereDate('week_start', '<=', $date)->whereDate('week_end', '>=', $date);
            } elseif ($type === 'month' && preg_match('/^\d{4}-\d{2}$/', (string) $request->query('filter_month'))) {
                $month = Carbon::createFromFormat('Y-m', $request->query('filter_month'))->startOfMonth();
                $query->whereDate('week_start', '<=', $month->copy()->endOfMonth())->whereDate('week_end', '>=', $month);
            } elseif ($type === 'year' && preg_match('/^\d{4}$/', (string) $request->query('filter_year'))) {
                $query->whereYear('week_start', (int) $request->query('filter_year'));
            }
        } catch (\Throwable) {
            // Giá trị lọc sai định dạng được bỏ qua thay vì làm hỏng trang danh sách.
        }
    }

    private function plainText(string $html): string
    {
        $html = preg_replace('/<(?:br\s*\/?|\/(?:p|div|li|blockquote|h[1-6]))>/i', ' ', $html) ?? $html;
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $text) ?? '');
    }

    private function plainTextLines(string $html): array
    {
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html) ?? $html;
        $html = preg_replace('/<li\b[^>]*>/i', "\n", $html) ?? $html;
        $html = preg_replace('/<\/(?:li|p|div|blockquote|h[1-6])>/i', "\n", $html) ?? $html;
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\xC2\xA0", ' ', $text);

        return collect(preg_split('/\R+/u', $text) ?: [])
            ->flatMap(fn (string $line) => preg_split('/\s+-\s+(?=[\p{L}\p{N}])/u', trim($line)) ?: [])
            ->map(fn (string $line) => trim(preg_replace('/^[-•]+\s*/u', '', $line) ?? $line))
            ->filter()
            ->values()
            ->all();
    }

    private function sanitizeRichText(string $html): string
    {
        $html = strip_tags($html, '<p><div><br><strong><b><em><i><u><ul><ol><li><blockquote><a>');
        $html = preg_replace('/\s+(on\w+|style|class|id)\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/is', '', $html) ?? $html;
        $html = preg_replace_callback('/<a\b([^>]*)>/i', function (array $match): string {
            if (! preg_match('/href\s*=\s*(["\'])(.*?)\1/i', $match[1], $href)) return '<a>';
            $url = trim(html_entity_decode($href[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if (! preg_match('#^(https?://|mailto:)#i', $url)) return '<a>';
            return '<a href="'.e($url).'" target="_blank" rel="noopener noreferrer">';
        }, $html) ?? $html;

        return trim($html);
    }
}
