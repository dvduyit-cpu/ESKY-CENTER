<?php

namespace App\Http\Controllers;

use App\Models\UpcomingPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WelcomeController extends Controller
{
    public function index(Request $request): View
    {
        $query = UpcomingPlan::query()->where('user_id', $request->user()->id)->whereNull('completed_at');
        $todayCount = (clone $query)->whereBetween('scheduled_for', [now()->startOfDay(), now()->endOfDay()])->count();
        $upcomingCount = (clone $query)->whereBetween('scheduled_for', [now(), now()->addDays(7)->endOfDay()])->count();
        $overdueCount = (clone $query)->where('scheduled_for', '<', now())->count();
        $nextPlans = (clone $query)->where('scheduled_for', '>=', now())->orderBy('scheduled_for')->limit(4)->get();
        return view('welcome', compact('todayCount', 'upcomingCount', 'overdueCount', 'nextPlans'));
    }

    public function plans(Request $request): View
    {
        $month = $request->string('month')->toString();
        try {
            $current = $month ? Carbon::createFromFormat('Y-m', $month)->startOfMonth() : now()->startOfMonth();
        } catch (\Throwable) {
            $current = now()->startOfMonth();
        }
        $calendarStart = $current->copy()->startOfWeek(Carbon::MONDAY);
        $calendarEnd = $current->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $plans = UpcomingPlan::query()->with('assignedBy')
            ->where('user_id', $request->user()->id)
            ->whereBetween('scheduled_for', [$calendarStart->copy()->startOfDay(), $calendarEnd->copy()->endOfDay()])
            ->orderBy('scheduled_for')->get();
        $plansByDate = $plans->groupBy(fn (UpcomingPlan $plan) => $plan->scheduled_for->format('Y-m-d'));
        $calendarDays = collect();
        for ($day = $calendarStart->copy(); $day->lte($calendarEnd); $day->addDay()) $calendarDays->push($day->copy());
        $allPlans = UpcomingPlan::query()->where('user_id', $request->user()->id);
        $planStats = [
            'total' => (clone $allPlans)->count(),
            'completed' => (clone $allPlans)->whereNotNull('completed_at')->count(),
            'upcoming' => (clone $allPlans)->whereNull('completed_at')->where('scheduled_for', '>=', now())->count(),
            'overdue' => (clone $allPlans)->whereNull('completed_at')->where('scheduled_for', '<', now())->count(),
        ];
        $historyQuery = (clone $allPlans)->with('assignedBy');
        if ($request->filled('q')) {
            $keyword = $request->string('q')->toString();
            $historyQuery->where(fn ($query) => $query->where('title', 'like', "%{$keyword}%")->orWhere('note', 'like', "%{$keyword}%"));
        }
        if ($request->filled('priority')) $historyQuery->where('priority', $request->string('priority')->toString());
        if ($request->filled('from')) $historyQuery->whereDate('scheduled_for', '>=', $request->date('from'));
        if ($request->filled('to')) $historyQuery->whereDate('scheduled_for', '<=', $request->date('to'));
        match ($request->string('status')->toString()) {
            'completed' => $historyQuery->whereNotNull('completed_at'),
            'upcoming' => $historyQuery->whereNull('completed_at')->where('scheduled_for', '>=', now()),
            'overdue' => $historyQuery->whereNull('completed_at')->where('scheduled_for', '<', now()),
            default => null,
        };
        $historyPlans = $historyQuery->latest('scheduled_for')->paginate(10, ['*'], 'history_page')->withQueryString();
        $todayPlans = UpcomingPlan::query()->where('user_id', $request->user()->id)
            ->whereBetween('scheduled_for', [now()->startOfDay(), now()->endOfDay()])->orderBy('scheduled_for')->get();
        $modalPlans = $plans->concat($historyPlans->getCollection())->concat($todayPlans)->unique('id');
        return view('plans.index', compact('current', 'calendarDays', 'plansByDate', 'planStats', 'historyPlans', 'modalPlans', 'todayPlans'));
    }

    public function tasks(Request $request): View
    {
        abort_unless($request->user()->isLeader(), 403);
        $users = User::query()->where('active', true)->orderBy('name')->get(['id', 'name', 'email']);
        $base = UpcomingPlan::query()->with('user')->where('kind', 'task')->where('assigned_by_id', $request->user()->id);
        $query = clone $base;
        if ($request->filled('q')) {
            $keyword = $request->string('q')->toString();
            $query->where(fn ($q) => $q->where('title', 'like', "%{$keyword}%")->orWhere('note', 'like', "%{$keyword}%"));
        }
        if ($request->filled('user_id')) $query->where('user_id', $request->integer('user_id'));
        if ($request->filled('priority')) $query->where('priority', $request->string('priority'));
        if ($request->filled('from')) $query->whereDate('scheduled_for', '>=', $request->date('from'));
        if ($request->filled('to')) $query->whereDate('scheduled_for', '<=', $request->date('to'));
        match ($request->string('status')->toString()) {
            'completed' => $query->whereNotNull('completed_at'),
            'overdue' => $query->whereNull('completed_at')->where('scheduled_for', '<', now()),
            'pending' => $query->whereNull('completed_at')->where('scheduled_for', '>=', now()),
            default => null,
        };
        $tasks = $query->latest('scheduled_for')->paginate(\App\Support\Pagination::perPage())->withQueryString();
        $taskStats = [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->whereNull('completed_at')->where('scheduled_for', '>=', now())->count(),
            'overdue' => (clone $base)->whereNull('completed_at')->where('scheduled_for', '<', now())->count(),
            'completed' => (clone $base)->whereNotNull('completed_at')->count(),
        ];
        return view('tasks.index', compact('users', 'tasks', 'taskStats'));
    }

    public function guide(): View { return view('guide'); }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedPlan($request);
        [$repeatType, $repeatCount] = $this->recurrence($data);
        $data['assigned_by_id'] = $request->user()->id;
        $data['kind'] = 'personal';
        $firstDate = Carbon::parse($data['scheduled_for']);

        DB::transaction(function () use ($request, $data, $repeatType, $repeatCount, $firstDate): void {
            for ($index = 0; $index < $repeatCount; $index++) {
                $occurrence = $data;
                $occurrence['scheduled_for'] = $this->occurrenceDate($firstDate, $repeatType, $index);
                $request->user()->upcomingPlans()->create($occurrence);
            }
        });

        return back()->with('success', $repeatCount > 1
            ? 'Đã tạo '.$repeatCount.' lịch nhắc lặp '.($repeatType === 'monthly' ? 'hàng tháng.' : 'hàng tuần.')
            : 'Đã lưu kế hoạch cá nhân và thiết lập nhắc việc.');
    }

public function storeTask(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isLeader(), 403);
        $data = $this->validatedPlan($request) + $request->validate(['assignee_id' => 'required|string|max:30']);
        $assigneeId = $data['assignee_id'];
        unset($data['assignee_id']);
        [$repeatType, $repeatCount] = $this->recurrence($data);
        $data['assigned_by_id'] = $request->user()->id;
        $data['kind'] = 'task';
        $firstDate = Carbon::parse($data['scheduled_for']);

        $createFor = function (User $account) use ($data, $repeatType, $repeatCount, $firstDate): void {
            for ($index = 0; $index < $repeatCount; $index++) {
                $occurrence = $data;
                $occurrence['scheduled_for'] = $this->occurrenceDate($firstDate, $repeatType, $index);
                $account->upcomingPlans()->create($occurrence);
            }
        };

        if ($assigneeId === 'all') {
            $accounts = User::query()->where('active', true)->get();
            DB::transaction(fn () => $accounts->each($createFor));
            return back()->with('success', 'Đã giao '.$repeatCount.' kỳ công việc cho tất cả '.$accounts->count().' tài khoản đang hoạt động.');
        }
        $assignee = User::query()->whereKey($assigneeId)->where('active', true)->first();
        if (! $assignee) throw ValidationException::withMessages(['assignee_id' => 'Tài khoản nhận task không tồn tại hoặc đã ngừng hoạt động.']);
        DB::transaction(fn () => $createFor($assignee));
        return back()->with('success', 'Đã giao '.$repeatCount.' kỳ công việc cho '.$assignee->name.'.');
    }

    public function toggle(Request $request, UpcomingPlan $plan): RedirectResponse
    {
        $this->ensureCanManage($request, $plan);
        $plan->update(['completed_at' => $plan->completed_at ? null : now()]);
        return back()->with('success', $plan->completed_at ? 'Đã đánh dấu hoàn thành.' : 'Đã mở lại công việc.');
    }

    public function update(Request $request, UpcomingPlan $plan): RedirectResponse
    {
        $this->ensureCanManage($request, $plan);
        $data = $this->validatedPlan($request);
        [$repeatType, $repeatCount] = $this->recurrence($data);
        $firstDate = Carbon::parse($data['scheduled_for']);
        DB::transaction(function () use ($plan, $data, $repeatType, $repeatCount, $firstDate): void {
            $plan->update($data);
            for ($index = 1; $index < $repeatCount; $index++) {
                UpcomingPlan::create(array_merge($data, [
                    'user_id' => $plan->user_id,
                    'assigned_by_id' => $plan->assigned_by_id,
                    'kind' => $plan->kind,
                    'scheduled_for' => $this->occurrenceDate($firstDate, $repeatType, $index),
                ]));
            }
        });

        return back()->with('success', $repeatCount > 1 ? 'Đã cập nhật và tạo '.$repeatCount.' kỳ kế hoạch.' : 'Đã cập nhật kế hoạch.');
    }

    public function destroy(Request $request, UpcomingPlan $plan): RedirectResponse
    {
        $this->ensureCanManage($request, $plan);
        $plan->delete();
        return back()->with('success', 'Đã xóa công việc.');
    }

    private function validatedPlan(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:180', 'note' => 'nullable|string|max:2000',
            'scheduled_for' => 'required|date', 'reminder_days' => 'required|integer|min:0|max:30',
            'priority' => 'required|in:low,normal,high',
            'repeat_weeks' => 'nullable|integer|in:1,4,8,12,24,52',
            'repeat_type' => 'nullable|in:none,weekly,monthly',
            'repeat_count' => 'nullable|integer|min:1|max:60',
        ], ['title.required' => 'Vui lòng nhập tên công việc.', 'scheduled_for.required' => 'Vui lòng chọn ngày giờ thực hiện.']);
    }

    private function recurrence(array &$data): array
    {
        $legacyWeeks = (int) ($data['repeat_weeks'] ?? 1);
        $type = $data['repeat_type'] ?? ($legacyWeeks > 1 ? 'weekly' : 'none');
        $count = $type === 'none' ? 1 : (int) ($data['repeat_count'] ?? $legacyWeeks);
        unset($data['repeat_weeks'], $data['repeat_type'], $data['repeat_count']);
        return [$type, $count];
    }

    private function occurrenceDate(Carbon $firstDate, string $type, int $index): Carbon
    {
        return match ($type) {
            'weekly' => $firstDate->copy()->addWeeks($index),
            'monthly' => $firstDate->copy()->addMonthsNoOverflow($index),
            default => $firstDate->copy(),
        };
    }

    private function ensureCanManage(Request $request, UpcomingPlan $plan): void
    {
        abort_unless($plan->user_id === $request->user()->id || $plan->assigned_by_id === $request->user()->id || $request->user()->isAdmin(), 403);
    }
}
