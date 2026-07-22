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
        $upcoming = UpcomingPlan::query()->with('assignedBy')->where('user_id', $request->user()->id)
            ->whereNull('completed_at')->where('scheduled_for', '>=', now()->startOfDay())
            ->orderBy('scheduled_for')->limit(8)->get();
        return view('plans.index', compact('current', 'calendarDays', 'plansByDate', 'upcoming'));
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
        $tasks = $query->latest('scheduled_for')->paginate(20)->withQueryString();
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
        $repeatCount = (int) ($data['repeat_weeks'] ?? 1);
        unset($data['repeat_weeks']);
        $data['assigned_by_id'] = $request->user()->id;
        $data['kind'] = 'personal';
        $firstDate = Carbon::parse($data['scheduled_for']);

        DB::transaction(function () use ($request, $data, $repeatCount, $firstDate): void {
            for ($week = 0; $week < $repeatCount; $week++) {
                $occurrence = $data;
                $occurrence['scheduled_for'] = $firstDate->copy()->addWeeks($week);
                $request->user()->upcomingPlans()->create($occurrence);
            }
        });

        return back()->with('success', $repeatCount > 1
            ? 'Đã tạo lịch hẹn nhắc hàng tuần trong '.$repeatCount.' tuần.'
            : 'Đã lưu kế hoạch cá nhân và thiết lập nhắc việc.');
    }

public function storeTask(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isLeader(), 403);
        $data = $this->validatedPlan($request) + $request->validate(['assignee_id' => 'required|string|max:30']);
        $assigneeId = $data['assignee_id'];
        unset($data['assignee_id']);
        $data['assigned_by_id'] = $request->user()->id;
        $data['kind'] = 'task';

        if ($assigneeId === 'all') {
            $accounts = User::query()->where('active', true)->get();
            DB::transaction(fn () => $accounts->each(fn (User $account) => $account->upcomingPlans()->create($data)));
            return back()->with('success', 'Đã giao task cho tất cả '.$accounts->count().' tài khoản đang hoạt động.');
        }
        $assignee = User::query()->whereKey($assigneeId)->where('active', true)->first();
        if (! $assignee) throw ValidationException::withMessages(['assignee_id' => 'Tài khoản nhận task không tồn tại hoặc đã ngừng hoạt động.']);
        $assignee->upcomingPlans()->create($data);
        return back()->with('success', 'Đã giao task cho '.$assignee->name.'.');
    }

    public function toggle(Request $request, UpcomingPlan $plan): RedirectResponse
    {
        $this->ensureCanManage($request, $plan);
        $plan->update(['completed_at' => $plan->completed_at ? null : now()]);
        return back()->with('success', $plan->completed_at ? 'Đã đánh dấu hoàn thành.' : 'Đã mở lại công việc.');
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
        ], ['title.required' => 'Vui lòng nhập tên công việc.', 'scheduled_for.required' => 'Vui lòng chọn ngày giờ thực hiện.']);
    }

    private function ensureCanManage(Request $request, UpcomingPlan $plan): void
    {
        abort_unless($plan->user_id === $request->user()->id || $plan->assigned_by_id === $request->user()->id || $request->user()->isAdmin(), 403);
    }
}
