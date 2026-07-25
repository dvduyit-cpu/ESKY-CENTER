<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UpcomingPlan;
use App\Models\WorkTask;
use App\Models\WorkTaskAssignee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WorkTaskController extends Controller
{
    public function index(Request $request): View
    {
        $me = $request->user();
        $base = WorkTask::query()
            ->where(fn ($q) => $q->where('created_by_id', $me->id)->orWhereHas('assignees', fn ($a) => $a->where('user_id', $me->id)));
        $filterYear = $request->integer('year');
        $filterMonth = $request->integer('month');
        $filterQuarter = $request->integer('quarter');
        if (($filterMonth >= 1 && $filterMonth <= 12) || ($filterQuarter >= 1 && $filterQuarter <= 4)) {
            $filterYear = $filterYear ?: now()->year;
        }
        if ($filterYear >= 2000 && $filterYear <= 2100) $base->whereYear('due_at', $filterYear);
        if ($filterMonth >= 1 && $filterMonth <= 12) {
            $base->whereMonth('due_at', $filterMonth);
        } elseif ($filterQuarter >= 1 && $filterQuarter <= 4) {
            $base->whereMonth('due_at', '>=', (($filterQuarter - 1) * 3) + 1)
                ->whereMonth('due_at', '<=', $filterQuarter * 3);
        }
        $filterYears = collect(range(now()->year + 2, now()->year - 5));
        $activeBase = (clone $base)->whereNull('closed_at');
        $taskStats = [
            'total' => (clone $activeBase)->count(),
            'completed' => (clone $activeBase)->whereDoesntHave('assignees', fn ($q) => $q->whereNull('completed_at'))->count(),
            'pending' => (clone $activeBase)->whereHas('assignees', fn ($q) => $q->whereNull('completed_at'))->count(),
            'due' => (clone $activeBase)->where('due_at', '<=', now())->whereHas('assignees', fn ($q) => $q->whereNull('completed_at'))->count(),
            'closed' => (clone $base)->whereNotNull('closed_at')->count(),
        ];
        $status = $request->string('status')->toString();
        $query = ($status === 'closed' ? (clone $base)->whereNotNull('closed_at') : clone $activeBase)
            ->with(['creator', 'assignees.user']);
        if ($request->filled('q')) $query->where(fn ($q) => $q->where('title','like','%'.$request->string('q').'%')->orWhere('description','like','%'.$request->string('q').'%'));
        match ($status) {
            'completed' => $query->whereDoesntHave('assignees', fn ($q) => $q->whereNull('completed_at')),
            'overdue' => $query->where('due_at','<',now())->whereHas('assignees', fn ($q) => $q->whereNull('completed_at')),
            'pending' => $query->whereHas('assignees', fn ($q) => $q->whereNull('completed_at')),
            'unread' => $query->whereHas('assignees', fn ($q) => $q->where('user_id',$me->id)->whereNull('acknowledged_at')),
            default => null,
        };
        $tasks = $query->latest('due_at')->paginate(\App\Support\Pagination::perPage())->withQueryString();
        $users = $me->allowed('work_tasks', 'create') ? User::query()->where('active',true)->orderBy('name')->get(['id','name','email']) : collect();
        $personalBase = UpcomingPlan::query()->where('user_id', $me->id)->where('kind', 'personal');
        $personalStats = [
            'today' => (clone $personalBase)->whereNull('completed_at')->whereBetween('scheduled_for', [now()->startOfDay(), now()->endOfDay()])->count(),
            'upcoming' => (clone $personalBase)->whereNull('completed_at')->where('scheduled_for', '>', now()->endOfDay())->count(),
            'overdue' => (clone $personalBase)->whereNull('completed_at')->where('scheduled_for', '<', now())->count(),
        ];
        $personalPlans = (clone $personalBase)->whereNull('completed_at')->orderBy('scheduled_for')->limit(8)->get();
        return view('work-tasks.index', compact('tasks','users','taskStats','personalPlans','personalStats','filterYears','filterYear','filterMonth','filterQuarter'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->allowed('work_tasks', 'create'), 403);
        $data = $request->validate([
            'title'=>'required|string|max:180', 'description'=>'nullable|string|max:5000', 'due_at'=>'required|date',
            'priority'=>'required|in:low,normal,high', 'assignee_ids'=>'required|array|min:1',
            'assignee_ids.*'=>['integer', Rule::exists('users','id')->where('active',true)],
            'lead_id'=>'required|integer',
            'repeat_months'=>'nullable|integer|min:1|max:60',
        ]);
        $ids = collect($data['assignee_ids'])->map(fn ($id)=>(int)$id)->unique();
        if (! $ids->contains((int) $data['lead_id'])) {
            throw ValidationException::withMessages([
                'lead_id' => 'Vui lòng chọn người chủ trì trong danh sách người nhận đã tích.',
            ]);
        }
        $repeatMonths = (int) ($data['repeat_months'] ?? 1);
        $firstDueAt = \Carbon\Carbon::parse($data['due_at']);
        DB::transaction(function () use ($request, $data, $ids, $repeatMonths, $firstDueAt) {
            for ($index = 0; $index < $repeatMonths; $index++) {
                $task = WorkTask::create(['created_by_id'=>$request->user()->id, 'title'=>$data['title'], 'description'=>$data['description'] ?? null, 'due_at'=>$firstDueAt->copy()->addMonthsNoOverflow($index), 'priority'=>$data['priority']]);
                foreach ($ids as $id) $task->assignees()->create(['user_id'=>$id, 'is_lead'=>$id===(int)$data['lead_id']]);
                $task->activities()->create(['user_id'=>$request->user()->id, 'action'=>'created', 'description'=>'Đã tạo và giao công việc cho '.$ids->count().' người.']);
            }
        });
        return redirect()->route('tasks.index')->with('success', $repeatMonths > 1 ? 'Đã giao '.$repeatMonths.' kỳ công việc hàng tháng.' : 'Đã giao công việc.');
    }

    public function show(Request $request, WorkTask $task): View
    {
        $this->ensureParticipant($request,$task);
        $task->load(['creator','assignees.user','comments.user','activities.user']);
        $canEdit = $task->created_by_id === $request->user()->id
            && $request->user()->allowed('work_tasks', 'update');
        $users = $canEdit
            ? User::query()->where('active', true)->orderBy('name')->get(['id', 'name', 'email'])
            : collect();
        return view('work-tasks.show', compact('task', 'users', 'canEdit'));
    }

    public function update(Request $request, WorkTask $task): RedirectResponse
    {
        abort_unless($task->created_by_id === $request->user()->id, 403, 'Chỉ người giao mới được chỉnh sửa công việc.');
        abort_if($task->closed_at, 422, 'Task đã đóng, hãy mở lại trước khi chỉnh sửa.');
        $data = $request->validate([
            'title' => 'required|string|max:180', 'description' => 'nullable|string|max:5000',
            'due_at' => 'required|date', 'priority' => 'required|in:low,normal,high',
            'assignee_ids' => 'required|array|min:1',
            'assignee_ids.*' => ['integer', Rule::exists('users', 'id')->where('active', true)],
            'lead_id' => 'required|integer',
        ]);
        $ids = collect($data['assignee_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        if (! $ids->contains((int) $data['lead_id'])) {
            throw ValidationException::withMessages(['lead_id' => 'Vui lòng chọn người chủ trì trong danh sách người nhận đã tích.']);
        }
        DB::transaction(function () use ($request, $task, $data, $ids) {
            $task->update(['title' => $data['title'], 'description' => $data['description'] ?? null,
                'due_at' => $data['due_at'], 'priority' => $data['priority']]);
            $task->assignees()->whereNotIn('user_id', $ids)->delete();
            foreach ($ids as $id) {
                $task->assignees()->updateOrCreate(['user_id' => $id], ['is_lead' => $id === (int) $data['lead_id']]);
            }
            $this->log($task, $request, 'Đã chỉnh sửa nội dung và phân công công việc.', 'updated');
        });
        return redirect()->route('tasks.show', $task)->with('success', 'Đã cập nhật công việc.');
    }

    public function acknowledge(Request $request, WorkTask $task): RedirectResponse
    {
        abort_if($task->closed_at, 422, 'Task đã đóng, hãy mở lại trước khi cập nhật.');
        $assignment = $this->assignment($request,$task);
        $assignment->update(['acknowledged_at'=>$assignment->acknowledged_at ? null : now()]);
        $this->log($task,$request,$assignment->acknowledged_at ? 'Đã xác nhận nhận công việc.' : 'Đã bỏ xác nhận nhận công việc.','acknowledged');
        return back()->with('success','Đã cập nhật xác nhận.');
    }

    public function complete(Request $request, WorkTask $task): RedirectResponse
    {
        abort_if($task->closed_at, 422, 'Task đã đóng, hãy mở lại trước khi cập nhật.');
        $assignment = $this->assignment($request,$task);
        $data = $request->validate(['note'=>'nullable|string|max:2000']);
        $assignment->update(['completed_at'=>$assignment->completed_at ? null : now(), 'note'=>$data['note'] ?? $assignment->note]);
        $this->log($task,$request,$assignment->completed_at ? 'Đã hoàn thành công việc.' : 'Đã mở lại công việc.','status');
        return back()->with('success','Đã cập nhật trạng thái thực hiện.');
    }

    public function comment(Request $request, WorkTask $task): RedirectResponse
    {
        $this->ensureParticipant($request,$task);
        abort_if($task->closed_at, 422, 'Task đã đóng, hãy mở lại trước khi phản hồi.');
        $data=$request->validate(['body'=>'required|string|max:3000']);
        $task->comments()->create(['user_id'=>$request->user()->id,'body'=>$data['body']]);
        $this->log($task,$request,'Đã gửi một phản hồi.','comment');
        return back()->with('success','Đã gửi phản hồi.');
    }

    public function close(Request $request, WorkTask $task): RedirectResponse
    {
        $this->ensureParticipant($request, $task);
        $canClose = $task->created_by_id === $request->user()->id
            || $task->assignees()->where('user_id', $request->user()->id)->where('is_lead', true)->exists();
        abort_unless($canClose, 403, 'Chỉ người giao hoặc người chủ trì được đóng task.');

        if (! $task->closed_at && $task->assignees()->whereNull('completed_at')->exists()) {
            throw ValidationException::withMessages(['task' => 'Chỉ có thể đóng khi tất cả người nhận đã hoàn thành.']);
        }

        $closing = ! $task->closed_at;
        $task->update(['closed_at' => $closing ? now() : null, 'closed_by_id' => $closing ? $request->user()->id : null]);
        $this->log($task, $request, $closing ? 'Đã đóng task.' : 'Đã mở lại task.', 'closed');

        return redirect()->route('tasks.show', $task)->with('success', $closing ? 'Đã đóng task.' : 'Đã mở lại task.');
    }
    public function destroy(Request $request, WorkTask $task): RedirectResponse
    {
        abort_unless($task->created_by_id === $request->user()->id, 403, 'Chỉ người giao mới được xóa công việc.');
        $task->delete();
        return redirect()->route('tasks.index')->with('success','Đã xóa công việc.');
    }

    private function ensureParticipant(Request $request, WorkTask $task): void
    { abort_unless($task->created_by_id===$request->user()->id || $task->assignees()->where('user_id',$request->user()->id)->exists(),403); }
    private function assignment(Request $request, WorkTask $task): WorkTaskAssignee
    { return $task->assignees()->where('user_id',$request->user()->id)->firstOrFail(); }
    private function log(WorkTask $task, Request $request, string $description, string $action): void
    { $task->activities()->create(['user_id'=>$request->user()->id,'action'=>$action,'description'=>$description]); }
}
