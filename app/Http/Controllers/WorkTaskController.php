<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UpcomingPlan;
use App\Models\WorkTask;
use App\Models\WorkTaskAssignee;
use App\Models\WorkTaskAttachment;
use App\Models\WorkTaskComment;
use App\Notifications\WorkTaskAssigned;
use App\Notifications\WorkTaskActivityNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Support\RealtimeNotifier;

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
        $assignedTaskBase = WorkTask::query()->where('created_by_id', $me->id);
        if ($filterYear >= 2000 && $filterYear <= 2100) $assignedTaskBase->whereYear('due_at', $filterYear);
        if ($filterMonth >= 1 && $filterMonth <= 12) {
            $assignedTaskBase->whereMonth('due_at', $filterMonth);
        } elseif ($filterQuarter >= 1 && $filterQuarter <= 4) {
            $assignedTaskBase->whereMonth('due_at', '>=', (($filterQuarter - 1) * 3) + 1)
                ->whereMonth('due_at', '<=', $filterQuarter * 3);
        }
        $taskStats['assigned'] = (clone $assignedTaskBase)->count();
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
        $canCreateTasks = $me->allowed('work_tasks', 'create');
        $canDeleteTasks = $me->allowed('work_tasks', 'delete');
        $users = $canCreateTasks ? User::query()->where('active',true)->orderBy('name')->get(['id','name','email']) : collect();
        $memberTaskStats = $canCreateTasks
            ? WorkTaskAssignee::query()
                ->select('user_id')
                ->selectRaw('COUNT(*) AS total_tasks')
                ->selectRaw('SUM(CASE WHEN acknowledged_at IS NOT NULL THEN 1 ELSE 0 END) AS acknowledged_tasks')
                ->selectRaw('SUM(CASE WHEN completed_at IS NOT NULL THEN 1 ELSE 0 END) AS completed_tasks')
                ->selectRaw('SUM(CASE WHEN acknowledged_at IS NULL THEN 1 ELSE 0 END) AS unacknowledged_tasks')
                ->whereIn('work_task_id', (clone $assignedTaskBase)->select('id'))
                ->with('user:id,name,email')
                ->groupBy('user_id')
                ->orderByDesc('total_tasks')
                ->get()
            : collect();
        $personalBase = UpcomingPlan::query()->where('user_id', $me->id)->where('kind', 'personal');
        $personalStats = [
            'today' => (clone $personalBase)->whereNull('completed_at')->whereBetween('scheduled_for', [now()->startOfDay(), now()->endOfDay()])->count(),
            'upcoming' => (clone $personalBase)->whereNull('completed_at')->where('scheduled_for', '>', now()->endOfDay())->count(),
            'overdue' => (clone $personalBase)->whereNull('completed_at')->where('scheduled_for', '<', now())->count(),
        ];
        $personalPlans = (clone $personalBase)->whereNull('completed_at')->orderBy('scheduled_for')->limit(8)->get();
        return view('work-tasks.index', compact('tasks','users','taskStats','memberTaskStats','personalPlans','personalStats','filterYears','filterYear','filterMonth','filterQuarter','canCreateTasks','canDeleteTasks'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->allowed('work_tasks', 'create'), 403);
        $data = $request->validate([
            'title'=>'required|string|max:180', 'description'=>'nullable|string|max:5000', 'due_at'=>'required|date',
            'priority'=>'required|in:low,normal,high', 'assignee_ids'=>'required|array|min:1',
            'assignee_ids.*'=>['integer', Rule::exists('users','id')->where('active',true)],
            'lead_id'=>'required|integer',
            'repeat_unit'=>'nullable|in:none,week,month',
            'repeat_count'=>'nullable|integer|min:1|max:4',
            'attachments'=>'nullable|array|max:5',
            'attachments.*'=>'file|max:10240',
        ]);
        $data['description'] = $this->sanitizeRichText($data['description'] ?? null);
        $ids = collect($data['assignee_ids'])->map(fn ($id)=>(int)$id)->unique();
        if (! $ids->contains((int) $data['lead_id'])) {
            throw ValidationException::withMessages([
                'lead_id' => 'Vui lòng chọn người chủ trì trong danh sách người nhận đã tích.',
            ]);
        }
        $repeatUnit = $data['repeat_unit'] ?? 'none';
        $repeatCount = $repeatUnit === 'none' ? 1 : (int) ($data['repeat_count'] ?? 1);
        if ($repeatUnit === 'month' && $repeatCount > 2) {
            throw ValidationException::withMessages(['repeat_count' => 'Lặp theo tháng chỉ được chọn 1 hoặc 2 tháng.']);
        }
        $firstDueAt = \Carbon\Carbon::parse($data['due_at']);
        $stagedAttachments = $this->stageAttachments($request);
        $storedPaths = [];
        try {
            $tasks = DB::transaction(function () use ($request, $data, $ids, $repeatUnit, $repeatCount, $firstDueAt, $stagedAttachments, &$storedPaths) {
                $createdTasks = collect();
                for ($index = 0; $index < $repeatCount; $index++) {
                    $dueAt = match ($repeatUnit) {
                        'week' => $firstDueAt->copy()->addWeeks($index),
                        'month' => $firstDueAt->copy()->addMonthsNoOverflow($index),
                        default => $firstDueAt->copy(),
                    };
                    $task = WorkTask::create(['created_by_id'=>$request->user()->id, 'title'=>$data['title'], 'description'=>$data['description'] ?? null, 'due_at'=>$dueAt, 'priority'=>$data['priority']]);
                    foreach ($ids as $id) $task->assignees()->create(['user_id'=>$id, 'is_lead'=>$id===(int)$data['lead_id']]);
                    $task->activities()->create(['user_id'=>$request->user()->id, 'action'=>'created', 'description'=>'Đã tạo và giao công việc cho '.$ids->count().' người.']);
                    $this->persistStagedAttachments(
                        $task,
                        $stagedAttachments,
                        null,
                        $storedPaths,
                        $index < $repeatCount - 1,
                    );
                    $createdTasks->push($task);
                }
                return $createdTasks;
            });
        } catch (\Throwable $exception) {
            $this->cleanupStoredPaths($storedPaths);
            $this->cleanupStagedAttachments($stagedAttachments);
            throw $exception;
        }
        $this->cleanupStagedAttachments($stagedAttachments);
        RealtimeNotifier::users($ids, ($repeatCount > 1 ? 'Bạn được giao công việc định kỳ: ' : 'Bạn được giao công việc: ').$data['title']);
        $emailRecipients = User::query()
            ->whereKey($ids)
            ->where('notifications_enabled', true)
            ->get();
        if ($emailRecipients->isNotEmpty()) {
            $assigner = $request->user();
            app()->terminating(function () use ($emailRecipients, $assigner, $tasks): void {
                try {
                    $assignmentNotification = new WorkTaskAssigned($assigner, $tasks);
                } catch (\Throwable $exception) {
                    Log::warning('Không thể chuẩn bị email thông báo giao việc.', [
                        'task_id'=>$tasks->first()?->id,
                        'error'=>$exception->getMessage(),
                    ]);
                    return;
                }

                $emailRecipients->each(function (User $recipient) use ($assignmentNotification, $tasks): void {
                    try {
                        $recipient->notify($assignmentNotification);
                    } catch (\Throwable $exception) {
                        Log::warning('Không thể gửi email thông báo giao việc.', [
                            'recipient_id'=>$recipient->id,
                            'task_id'=>$tasks->first()?->id,
                            'error'=>$exception->getMessage(),
                        ]);
                    }
                });
            });
        }
        $periodLabel = $repeatUnit === 'week' ? 'hàng tuần' : 'hàng tháng';
        return redirect()->route('tasks.index')->with('success', $repeatCount > 1 ? 'Đã giao '.$repeatCount.' kỳ công việc '.$periodLabel.'.' : 'Đã giao công việc.');
    }

    public function show(Request $request, WorkTask $task): View
    {
        $this->ensureParticipant($request,$task);
        $task->load(['creator', 'assignees.user', 'attachments']);
        $comments = $task->comments()
            ->with(['user', 'parent.user', 'attachments'])
            ->latest()
            ->paginate(20, ['*'], 'comments_page')
            ->withQueryString()
            ->fragment('taskComments');
        $activities = $task->activities()
            ->with('user')
            ->latest()
            ->paginate(50, ['*'], 'activities_page')
            ->withQueryString()
            ->fragment('taskHistory');
        $isCreator = $task->created_by_id === $request->user()->id;
        $canEdit = $isCreator && $request->user()->allowed('work_tasks', 'update');
        $canClose = $canEdit;
        $canDelete = $isCreator && $request->user()->allowed('work_tasks', 'delete');
        $users = $canEdit
            ? User::query()->where('active', true)->orderBy('name')->get(['id', 'name', 'email'])
            : collect();
        return view('work-tasks.show', compact('task', 'comments', 'activities', 'users', 'canEdit', 'canClose', 'canDelete'));
    }

    public function update(Request $request, WorkTask $task): RedirectResponse
    {
        abort_unless($task->created_by_id === $request->user()->id, 403, 'Chỉ người giao mới được chỉnh sửa công việc.');
        if ($task->closed_at) return back()->with('warning', 'Task đã đóng. Chỉ người giao việc mới có thể mở lại task.');
        $data = $request->validate([
            'title' => 'required|string|max:180', 'description' => 'nullable|string|max:5000',
            'due_at' => 'required|date', 'priority' => 'required|in:low,normal,high',
            'assignee_ids' => 'required|array|min:1',
            'assignee_ids.*' => ['integer', Rule::exists('users', 'id')->where('active', true)],
            'lead_id' => 'required|integer',
            'attachments'=>'nullable|array|max:5',
            'attachments.*'=>'file|max:10240',
        ]);
        $data['description'] = $this->sanitizeRichText($data['description'] ?? null);
        $ids = collect($data['assignee_ids'])->map(fn ($id) => (int) $id)->unique()->values();
        if (! $ids->contains((int) $data['lead_id'])) {
            throw ValidationException::withMessages(['lead_id' => 'Vui lòng chọn người chủ trì trong danh sách người nhận đã tích.']);
        }

        $stagedAttachments = $this->stageAttachments($request);
        $storedPaths = [];
        try {
            $result = DB::transaction(function () use ($request, $task, $data, $ids, $stagedAttachments, &$storedPaths): array {
                $lockedTask = WorkTask::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();
                if ($lockedTask->closed_at) {
                    return ['changed'=>false, 'level'=>'warning', 'message'=>'Task đã đóng. Chỉ người giao việc mới có thể mở lại task.'];
                }

                $previousIds = WorkTaskAssignee::query()
                    ->where('work_task_id', $lockedTask->id)
                    ->lockForUpdate()
                    ->pluck('user_id');
                $newAttachmentCount = $stagedAttachments['files']->count();
                if ($newAttachmentCount > 0 && $lockedTask->attachments()->count() + $newAttachmentCount > 5) {
                    throw ValidationException::withMessages([
                        'attachments' => 'Mỗi công việc chỉ được có tối đa 5 file đính kèm.',
                    ]);
                }
                $lockedTask->update(['title' => $data['title'], 'description' => $data['description'] ?? null,
                    'due_at' => $data['due_at'], 'priority' => $data['priority']]);
                $lockedTask->assignees()->whereNotIn('user_id', $ids)->delete();
                foreach ($ids as $id) {
                    $lockedTask->assignees()->updateOrCreate(['user_id' => $id], ['is_lead' => $id === (int) $data['lead_id']]);
                }
                $this->persistStagedAttachments($lockedTask, $stagedAttachments, null, $storedPaths);
                $this->log($lockedTask, $request, 'Đã chỉnh sửa nội dung và phân công công việc.', 'updated');

                return ['changed'=>true, 'previousIds'=>$previousIds, 'title'=>$lockedTask->title];
            });
        } catch (\Throwable $exception) {
            $this->cleanupStoredPaths($storedPaths);
            $this->cleanupStagedAttachments($stagedAttachments);
            throw $exception;
        }
        $this->cleanupStagedAttachments($stagedAttachments);
        if (! $result['changed']) {
            return back()->with($result['level'], $result['message']);
        }

        $previousIds = $result['previousIds'];
        $task->forceFill(['title'=>$result['title']]);
        $updatedParticipantIds = $previousIds->merge($ids)->unique()->values();
        RealtimeNotifier::users($updatedParticipantIds, 'Đã cập nhật công việc: '.$task->title);
        $this->sendActivityEmails(
            $request,
            $task,
            $ids,
            'Công việc đã được cập nhật',
            'đã cập nhật nội dung hoặc phân công công việc.'
        );
        $this->sendActivityEmails(
            $request,
            $task,
            $previousIds->diff($ids),
            'Bạn đã được gỡ khỏi công việc',
            'đã gỡ bạn khỏi danh sách người thực hiện.',
            false
        );
        return redirect()->route('tasks.show', $task)->with('success', 'Đã cập nhật công việc.');
    }

    public function acknowledge(Request $request, WorkTask $task): RedirectResponse
    {
        if ($task->closed_at) return back()->with('warning', 'Task đã đóng. Bạn không thể thay đổi xác nhận nhận việc.');
        $request->validate(['acknowledged'=>'sometimes|boolean']);

        $result = DB::transaction(function () use ($request, $task): array {
            $lockedTask = WorkTask::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();
            if ($lockedTask->closed_at) {
                return ['changed'=>false, 'level'=>'warning', 'message'=>'Task đã đóng. Bạn không thể thay đổi xác nhận nhận việc.'];
            }

            $assignment = WorkTaskAssignee::query()
                ->where('work_task_id', $lockedTask->id)
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->firstOrFail();
            $acknowledged = $request->has('acknowledged')
                ? $request->boolean('acknowledged')
                : ! (bool) $assignment->acknowledged_at;
            if (! $acknowledged && $assignment->completed_at) {
                return ['changed'=>false, 'level'=>'warning', 'message'=>'Hãy mở lại công việc trước khi bỏ xác nhận nhận việc.'];
            }
            if ((bool) $assignment->acknowledged_at === $acknowledged) {
                return ['changed'=>false, 'level'=>'success', 'message'=>'Trạng thái nhận việc đã được cập nhật trước đó.'];
            }

            $assignment->update(['acknowledged_at'=>$acknowledged ? now() : null]);
            $this->log($lockedTask,$request,$acknowledged ? 'Đã xác nhận nhận công việc.' : 'Đã bỏ xác nhận nhận công việc.','acknowledged');

            return [
                'changed'=>true,
                'target'=>$acknowledged,
                'title'=>$lockedTask->title,
                'recipientIds'=>$this->participantIds($lockedTask),
            ];
        });
        if (! $result['changed']) return back()->with($result['level'], $result['message']);

        $acknowledged = $result['target'];
        $task->forceFill(['title'=>$result['title']]);
        $recipients = $result['recipientIds']->reject(fn ($id)=>(int)$id===$request->user()->id);
        RealtimeNotifier::users($recipients, $request->user()->name.' vừa cập nhật xác nhận: '.$task->title);
        $this->sendActivityEmails(
            $request,
            $task,
            $recipients,
            $acknowledged ? 'Đã nhận việc' : 'Đã bỏ xác nhận nhận việc',
            $acknowledged ? 'đã xác nhận nhận việc.' : 'đã bỏ xác nhận nhận việc.'
        );
        return back()->with('success','Đã cập nhật xác nhận.');
    }

    public function complete(Request $request, WorkTask $task): RedirectResponse
    {
        if ($task->closed_at) return back()->with('warning', 'Task đã đóng. Chỉ người giao việc mới có thể mở lại task.');
        $data = $request->validate(['note'=>'nullable|string|max:2000', 'completed'=>'sometimes|boolean']);
        $data['note'] = $this->sanitizeRichText($data['note'] ?? null);

        $result = DB::transaction(function () use ($request, $task, $data): array {
            $lockedTask = WorkTask::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();
            if ($lockedTask->closed_at) {
                return ['changed'=>false, 'level'=>'warning', 'message'=>'Task đã đóng. Chỉ người giao việc mới có thể mở lại task.'];
            }

            $assignment = WorkTaskAssignee::query()
                ->where('work_task_id', $lockedTask->id)
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->firstOrFail();
            $completed = $request->has('completed')
                ? $request->boolean('completed')
                : ! (bool) $assignment->completed_at;
            if ($completed && ! $assignment->acknowledged_at) {
                return ['changed'=>false, 'level'=>'warning', 'message'=>'Vui lòng xác nhận nhận việc trước khi báo cáo hoàn thành.'];
            }
            $note = array_key_exists('note', $data) ? $data['note'] : $assignment->note;
            if ((bool) $assignment->completed_at === $completed && $assignment->note === $note) {
                return ['changed'=>false, 'level'=>'success', 'message'=>'Trạng thái thực hiện đã được cập nhật trước đó.'];
            }

            $assignment->update(['completed_at'=>$completed ? now() : null, 'note'=>$note]);
            $this->log($lockedTask,$request,$completed ? 'Đã hoàn thành công việc.' : 'Đã mở lại công việc.','status');

            return [
                'changed'=>true,
                'target'=>$completed,
                'title'=>$lockedTask->title,
                'recipientIds'=>$this->participantIds($lockedTask),
            ];
        });
        if (! $result['changed']) return back()->with($result['level'], $result['message']);

        $completed = $result['target'];
        $task->forceFill(['title'=>$result['title']]);
        $recipients = $result['recipientIds']->reject(fn ($id)=>(int)$id===$request->user()->id);
        RealtimeNotifier::users($recipients, $request->user()->name.' vừa cập nhật trạng thái: '.$task->title);
        $this->sendActivityEmails(
            $request,
            $task,
            $recipients,
            $completed ? 'Công việc đã hoàn thành' : 'Công việc đã được mở lại',
            $completed ? 'đã báo cáo hoàn thành công việc.' : 'đã mở lại công việc để tiếp tục thực hiện.'
        );
        return back()->with('success','Đã cập nhật trạng thái thực hiện.');
    }

    public function comment(Request $request, WorkTask $task): RedirectResponse
    {
        $this->ensureParticipant($request,$task);
        if ($task->closed_at) return back()->with('warning', 'Task đã đóng. Không thể gửi thêm phản hồi.');
        $data=$request->validate([
            'body'=>'required|string|max:3000',
            'parent_comment_id'=>'nullable|integer',
            'mentioned_user_ids'=>'nullable|array',
            'mentioned_user_ids.*'=>'integer',
            'attachments'=>'nullable|array|max:5',
            'attachments.*'=>'file|max:10240',
        ]);
        $data['body'] = $this->sanitizeRichText($data['body']);
        if ($data['body'] === null || trim(html_entity_decode(strip_tags($data['body']), ENT_QUOTES | ENT_HTML5, 'UTF-8')) === '') {
            throw ValidationException::withMessages(['body' => 'Vui lòng nhập nội dung phản hồi.']);
        }

        $stagedAttachments = $this->stageAttachments($request);
        $storedPaths = [];
        try {
            $result = DB::transaction(function () use ($request, $task, $data, $stagedAttachments, &$storedPaths): array {
                $lockedTask = WorkTask::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();
                if ($lockedTask->closed_at) {
                    return ['changed'=>false, 'level'=>'warning', 'message'=>'Task đã đóng. Không thể gửi thêm phản hồi.'];
                }
                abort_unless(
                    $lockedTask->created_by_id === $request->user()->id
                        || WorkTaskAssignee::query()
                            ->where('work_task_id', $lockedTask->id)
                            ->where('user_id', $request->user()->id)
                            ->exists(),
                    403
                );

                $parentComment = null;
                if (($data['parent_comment_id'] ?? null) !== null) {
                    $parentComment = WorkTaskComment::query()
                        ->with('user')
                        ->whereKey((int) $data['parent_comment_id'])
                        ->where('work_task_id', $lockedTask->id)
                        ->lockForUpdate()
                        ->first();
                    if (! $parentComment) {
                        throw ValidationException::withMessages([
                            'parent_comment_id'=>'Phản hồi được trả lời không tồn tại trong công việc này.',
                        ]);
                    }
                }

                $participantIds = $this->participantIds($lockedTask)
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();
                $requestedMentionIds = collect($data['mentioned_user_ids'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();
                if ($requestedMentionIds->diff($participantIds)->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'mentioned_user_ids'=>'Chỉ được nhắc đến người giao hoặc người nhận công việc.',
                    ]);
                }
                $replyRecipientIds = collect($parentComment ? [(int) $parentComment->user_id] : [])
                    ->intersect($participantIds)
                    ->reject(fn ($id) => $id === (int) $request->user()->id)
                    ->unique()
                    ->values();
                $mentionedRecipientIds = $requestedMentionIds
                    ->reject(fn ($id) => $id === (int) $request->user()->id)
                    ->diff($replyRecipientIds)
                    ->values();
                $bodyPreview = Str::limit($this->plainTextExcerpt($data['body']) ?? '', 160, '…');

                $comment = $lockedTask->comments()->create([
                    'user_id'=>$request->user()->id,
                    'parent_id'=>$parentComment?->id,
                    'reply_to_user_name'=>$parentComment?->user?->name,
                    'reply_excerpt'=>$parentComment ? $this->plainTextExcerpt($parentComment->body) : null,
                    'body'=>$data['body'],
                ]);
                $this->persistStagedAttachments($lockedTask, $stagedAttachments, $comment->id, $storedPaths);
                $this->log(
                    $lockedTask,
                    $request,
                    $parentComment ? 'Đã trả lời một phản hồi.' : 'Đã gửi một phản hồi.',
                    'comment'
                );

                return [
                    'changed'=>true,
                    'isReply'=>(bool) $parentComment,
                    'bodyPreview'=>$bodyPreview,
                    'title'=>$lockedTask->title,
                    'recipientIds'=>$participantIds,
                    'replyRecipientIds'=>$replyRecipientIds,
                    'mentionedRecipientIds'=>$mentionedRecipientIds,
                ];
            });
        } catch (\Throwable $exception) {
            $this->cleanupStoredPaths($storedPaths);
            $this->cleanupStagedAttachments($stagedAttachments);
            throw $exception;
        }
        $this->cleanupStagedAttachments($stagedAttachments);
        if (! $result['changed']) return back()->with($result['level'], $result['message']);

        $task->forceFill(['title'=>$result['title']]);
        $recipients = $result['recipientIds']
            ->reject(fn ($id)=>(int)$id===$request->user()->id)
            ->values();
        $replyRecipients = $result['replyRecipientIds'];
        $mentionedRecipients = $result['mentionedRecipientIds']->diff($replyRecipients)->values();
        $generalRecipients = $recipients
            ->diff($replyRecipients)
            ->diff($mentionedRecipients)
            ->values();
        $bodyPreview = $result['bodyPreview'];
        $realtimeSuffix = $bodyPreview === '' ? '' : ' — '.$bodyPreview;
        $emailSuffix = $bodyPreview === '' ? '' : ' Nội dung: “'.$bodyPreview.'”';
        $commentUrl = route('tasks.show', $task).'#taskComments';
        RealtimeNotifier::users(
            $replyRecipients,
            $request->user()->name.' đã trả lời phản hồi của bạn trong công việc: '.$task->title.$realtimeSuffix,
            $commentUrl
        );
        $this->sendActivityEmails(
            $request,
            $task,
            $replyRecipients,
            'Phản hồi của bạn đã được trả lời',
            'đã trả lời phản hồi của bạn.'.$emailSuffix
        );
        RealtimeNotifier::users(
            $mentionedRecipients,
            $request->user()->name.' đã nhắc đến bạn trong công việc: '.$task->title.$realtimeSuffix,
            $commentUrl
        );
        $this->sendActivityEmails(
            $request,
            $task,
            $mentionedRecipients,
            'Bạn được nhắc đến trong công việc',
            'đã nhắc đến bạn trong một phản hồi.'.$emailSuffix
        );
        RealtimeNotifier::users(
            $generalRecipients,
            'Phản hồi mới từ '.$request->user()->name.' trong công việc: '.$task->title.$realtimeSuffix,
            $commentUrl
        );
        $this->sendActivityEmails(
            $request,
            $task,
            $generalRecipients,
            'Phản hồi mới',
            'vừa gửi một phản hồi mới.'.$emailSuffix
        );
        return redirect()
            ->to(route('tasks.show', $task).'#taskComments')
            ->with('success', $result['isReply'] ? 'Đã gửi câu trả lời.' : 'Đã gửi phản hồi.');
    }

    public function retractComment(Request $request, WorkTask $task, WorkTaskComment $comment): RedirectResponse
    {
        $this->ensureParticipant($request, $task);
        $trash = ['directory'=>null, 'files'=>[]];
        try {
            $result = DB::transaction(function () use ($request, $task, $comment, &$trash): array {
                $lockedTask = WorkTask::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();
                abort_unless(
                    $lockedTask->created_by_id === $request->user()->id
                        || WorkTaskAssignee::query()
                            ->where('work_task_id', $lockedTask->id)
                            ->where('user_id', $request->user()->id)
                            ->exists(),
                    403
                );
                $lockedComment = WorkTaskComment::query()
                    ->whereKey($comment->id)
                    ->where('work_task_id', $lockedTask->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                abort_unless($lockedComment->user_id === $request->user()->id, 403, 'Bạn chỉ được thu hồi phản hồi của chính mình.');
                if ($lockedComment->created_at->lt(now()->subHours(24))) {
                    throw ValidationException::withMessages([
                        'comment' => 'Phản hồi đã quá 24 giờ nên không thể thu hồi.',
                    ]);
                }

                $lockedComment->load('attachments');
                $trash = $this->movePathsToTrash($lockedComment->attachments->pluck('storage_path'));
                $lockedComment->delete();
                $this->log($lockedTask, $request, 'Đã thu hồi một phản hồi.', 'comment_retracted');

                return [
                    'title'=>$lockedTask->title,
                    'recipientIds'=>$this->participantIds($lockedTask),
                ];
            });
        } catch (\Throwable $exception) {
            $this->restoreTrashedPaths($trash);
            throw $exception;
        }
        $this->purgeTrashedPaths($trash);

        $task->forceFill(['title'=>$result['title']]);
        $recipients = $result['recipientIds']->reject(fn ($id) => (int) $id === $request->user()->id);
        RealtimeNotifier::users(
            $recipients,
            $request->user()->name.' đã thu hồi một phản hồi trong công việc: '.$task->title
        );
        $this->sendActivityEmails(
            $request,
            $task,
            $recipients,
            'Phản hồi đã được thu hồi',
            'đã thu hồi một phản hồi.'
        );

        return redirect()->to(route('tasks.show', $task).'#taskComments')->with('success', 'Đã thu hồi phản hồi.');
    }

    public function downloadAttachment(Request $request, WorkTask $task, WorkTaskAttachment $attachment)
    {
        $this->ensureParticipant($request, $task);
        abort_unless($attachment->work_task_id === $task->id, 404);
        abort_unless(Storage::disk('local')->exists($attachment->storage_path), 404);

        $extension = strtolower(pathinfo($attachment->original_name, PATHINFO_EXTENSION));
        $previewMimeTypes = [
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'txt' => 'text/plain; charset=UTF-8',
            'csv' => 'text/plain; charset=UTF-8',
        ];
        $previewMime = $previewMimeTypes[$extension] ?? null;

        if ($request->boolean('preview') && $previewMime) {
            return response()
                ->file(Storage::disk('local')->path($attachment->storage_path), ['Content-Type' => $previewMime])
                ->setContentDisposition('inline', $attachment->original_name);
        }

        return Storage::disk('local')->download($attachment->storage_path, $attachment->original_name);
    }

    public function destroyAttachment(Request $request, WorkTask $task, WorkTaskAttachment $attachment): RedirectResponse|JsonResponse
    {
        abort_unless($task->created_by_id === $request->user()->id, 403, 'Chỉ người giao công việc mới được xóa file.');
        if ($task->closed_at) {
            if ($request->expectsJson()) {
                return response()->json(['message'=>'Task đã đóng. Mở lại task trước khi xóa file đính kèm.'], 409);
            }
            return back()->with('warning', 'Task đã đóng. Mở lại task trước khi xóa file đính kèm.');
        }

        $trash = ['directory'=>null, 'files'=>[]];
        try {
            $result = DB::transaction(function () use ($request, $task, $attachment, &$trash): array {
                $lockedTask = WorkTask::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();
                abort_unless($lockedTask->created_by_id === $request->user()->id, 403, 'Chỉ người giao công việc mới được xóa file.');
                if ($lockedTask->closed_at) {
                    return ['changed'=>false, 'level'=>'warning', 'message'=>'Task đã đóng. Mở lại task trước khi xóa file đính kèm.'];
                }

                $lockedAttachment = WorkTaskAttachment::query()
                    ->whereKey($attachment->id)
                    ->where('work_task_id', $lockedTask->id)
                    ->whereNull('work_task_comment_id')
                    ->lockForUpdate()
                    ->firstOrFail();
                $trash = $this->movePathsToTrash([$lockedAttachment->storage_path]);
                $fileName = $lockedAttachment->original_name;
                $lockedAttachment->delete();
                $this->log($lockedTask, $request, 'Đã xóa file đính kèm: '.$fileName, 'attachment_deleted');

                return [
                    'changed'=>true,
                    'fileName'=>$fileName,
                    'title'=>$lockedTask->title,
                    'recipientIds'=>$this->participantIds($lockedTask),
                ];
            });
        } catch (\Throwable $exception) {
            $this->restoreTrashedPaths($trash);
            throw $exception;
        }
        if (! $result['changed']) {
            if ($request->expectsJson()) {
                return response()->json(['message'=>$result['message']], 409);
            }
            return back()->with($result['level'], $result['message']);
        }

        $this->purgeTrashedPaths($trash);
        $fileName = $result['fileName'];
        $task->forceFill(['title'=>$result['title']]);
        $recipients = $result['recipientIds']->reject(fn ($id) => (int) $id === $request->user()->id);
        RealtimeNotifier::users($recipients, 'Đã xóa file đính kèm trong công việc: '.$task->title);
        $this->sendActivityEmails(
            $request,
            $task,
            $recipients,
            'File đính kèm đã bị xóa',
            'đã xóa file đính kèm “'.$fileName.'”.'
        );

        if ($request->expectsJson()) {
            return response()->json(['message'=>'Đã xóa file đính kèm.']);
        }
        return redirect()->route('tasks.show', $task)->with('success', 'Đã xóa file đính kèm.');
    }

    public function close(Request $request, WorkTask $task): RedirectResponse
    {
        abort_unless(
            $task->created_by_id === $request->user()->id,
            403,
            'Chỉ người giao công việc mới được đóng hoặc mở lại task.'
        );
        $request->validate(['closed'=>'sometimes|boolean']);

        $result = DB::transaction(function () use ($task, $request): array {
            $lockedTask = WorkTask::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();
            $closing = $request->has('closed')
                ? $request->boolean('closed')
                : ! (bool) $lockedTask->closed_at;
            if ((bool) $lockedTask->closed_at === $closing) {
                return ['changed'=>false, 'level'=>'success', 'message'=>'Trạng thái đóng/mở công việc đã được cập nhật trước đó.'];
            }

            if ($closing) {
                $assignments = WorkTaskAssignee::query()
                    ->where('work_task_id', $lockedTask->id)
                    ->lockForUpdate()
                    ->get(['id', 'completed_at']);
                if ($assignments->contains(fn (WorkTaskAssignee $assignment) => $assignment->completed_at === null)) {
                    return ['changed'=>false, 'level'=>'warning', 'message'=>'Chỉ có thể đóng khi tất cả người nhận đã hoàn thành.'];
                }
            }

            $lockedTask->update(['closed_at' => $closing ? now() : null, 'closed_by_id' => $closing ? $request->user()->id : null]);
            $this->log($lockedTask, $request, $closing ? 'Đã đóng task.' : 'Đã mở lại task.', 'closed');

            return [
                'changed'=>true,
                'target'=>$closing,
                'title'=>$lockedTask->title,
                'recipientIds'=>$this->participantIds($lockedTask),
            ];
        });
        if (! $result['changed']) return back()->with($result['level'], $result['message']);

        $closing = $result['target'];
        $task->forceFill(['title'=>$result['title']]);
        $recipients = $result['recipientIds']->reject(fn ($id)=>(int)$id===$request->user()->id);
        RealtimeNotifier::users($recipients, ($closing ? 'Đã đóng công việc: ' : 'Đã mở lại công việc: ').$task->title);
        $this->sendActivityEmails(
            $request,
            $task,
            $recipients,
            $closing ? 'Công việc đã đóng' : 'Công việc đã được mở lại',
            $closing ? 'đã đóng công việc.' : 'đã mở lại công việc.'
        );

        return redirect()->route('tasks.show', $task)->with('success', $closing ? 'Đã đóng task.' : 'Đã mở lại task.');
    }
    public function destroy(Request $request, WorkTask $task): RedirectResponse
    {
        abort_unless($task->created_by_id === $request->user()->id, 403, 'Chỉ người giao mới được xóa công việc.');

        $trash = ['directory'=>null, 'files'=>[]];
        try {
            $deleted = DB::transaction(function () use ($request, $task, &$trash): array {
                $lockedTask = WorkTask::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();
                abort_unless($lockedTask->created_by_id === $request->user()->id, 403, 'Chỉ người giao mới được xóa công việc.');
                $recipients = WorkTaskAssignee::query()
                    ->where('work_task_id', $lockedTask->id)
                    ->lockForUpdate()
                    ->pluck('user_id')
                    ->reject(fn ($id)=>(int)$id===$request->user()->id)
                    ->unique()
                    ->values();
                $attachmentPaths = WorkTaskAttachment::query()
                    ->where('work_task_id', $lockedTask->id)
                    ->lockForUpdate()
                    ->pluck('storage_path');
                $trash = $this->movePathsToTrash($attachmentPaths);
                $title = $lockedTask->title;
                $taskId = $lockedTask->id;
                $lockedTask->delete();

                return ['recipients'=>$recipients, 'title'=>$title, 'taskId'=>$taskId];
            });
        } catch (\Throwable $exception) {
            $this->restoreTrashedPaths($trash);
            throw $exception;
        }
        $this->cleanupTaskDirectory($deleted['taskId']);
        $this->purgeTrashedPaths($trash);
        $recipients = $deleted['recipients'];
        $task->title = $deleted['title'];
        RealtimeNotifier::users($recipients, 'Đã xóa công việc: '.$deleted['title']);
        $this->sendActivityEmails(
            $request,
            $task,
            $recipients,
            'Công việc đã bị xóa',
            'đã xóa công việc.',
            false
        );
        return redirect()->route('tasks.index')->with('success','Đã xóa công việc.');
    }

    private function ensureParticipant(Request $request, WorkTask $task): void
    { abort_unless($task->created_by_id===$request->user()->id || $task->assignees()->where('user_id',$request->user()->id)->exists(),403); }
    private function log(WorkTask $task, Request $request, string $description, string $action): void
    { $task->activities()->create(['user_id'=>$request->user()->id,'action'=>$action,'description'=>$description]); }
    private function participantIds(WorkTask $task)
    { return $task->assignees()->pluck('user_id')->push($task->created_by_id)->unique(); }

    private function sendActivityEmails(
        Request $request,
        WorkTask $task,
        iterable $recipientIds,
        string $eventTitle,
        string $eventDescription,
        bool $includeTaskLink = true,
    ): void {
        $ids = collect($recipientIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0 && $id !== (int) $request->user()->id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) return;

        $notification = new WorkTaskActivityNotification(
            $request->user()->name,
            $task->title,
            $eventTitle,
            $eventDescription,
            $includeTaskLink ? route('tasks.show', $task) : null,
        );

        $recipients = User::query()
            ->whereKey($ids)
            ->where('active', true)
            ->where('notifications_enabled', true)
            ->get();

        app()->terminating(function () use ($recipients, $notification, $task, $eventTitle): void {
            $recipients->each(function (User $recipient) use ($notification, $task, $eventTitle): void {
                try {
                    $recipient->notify($notification);
                } catch (\Throwable $exception) {
                    Log::warning('Không thể gửi email hoạt động công việc.', [
                        'recipient_id' => $recipient->id,
                        'task_id' => $task->id,
                        'event' => $eventTitle,
                        'error' => $exception->getMessage(),
                    ]);
                }
            });
        });
    }

    private function stageAttachments(Request $request): array
    {
        $files = collect($request->file('attachments', []));
        if ($files->isEmpty()) return ['directory'=>null, 'files'=>collect()];

        $directory = 'work-task-attachments/.staging/'.Str::uuid();
        $staged = ['directory'=>$directory, 'files'=>collect()];
        try {
            foreach ($files as $file) {
                $path = $file->store($directory, 'local');
                if (! is_string($path) || $path === '') {
                    throw new \RuntimeException('Filesystem did not return a stored attachment path.');
                }
                $staged['files']->push([
                    'storage_path'=>$path,
                    'uploaded_by_id'=>$request->user()->id,
                    'original_name'=>mb_substr($file->getClientOriginalName(), 0, 255),
                    'mime_type'=>$file->getMimeType() ?: null,
                    'size'=>(int) $file->getSize(),
                ]);
            }
        } catch (\Throwable $exception) {
            $this->cleanupStagedAttachments($staged);
            Log::warning('Không thể đưa file công việc vào vùng lưu tạm.', [
                'error'=>$exception->getMessage(),
            ]);
            throw ValidationException::withMessages([
                'attachments'=>'Không thể lưu file lên host. Vui lòng kiểm tra dung lượng và quyền ghi thư mục lưu trữ.',
            ]);
        }

        return $staged;
    }

    private function persistStagedAttachments(
        WorkTask $task,
        array $staged,
        ?int $commentId,
        array &$storedPaths,
        bool $copy = false,
    ): void {
        $disk = Storage::disk('local');
        foreach ($staged['files'] as $file) {
            $sourcePath = $file['storage_path'];
            $finalPath = 'work-task-attachments/'.$task->id.'/'.basename($sourcePath);
            $storedPaths[] = $finalPath;
            $stored = $copy
                ? $disk->copy($sourcePath, $finalPath)
                : $disk->move($sourcePath, $finalPath);
            if (! $stored || ! $disk->exists($finalPath)) {
                throw new \RuntimeException('Could not move a staged work-task attachment.');
            }

            $task->attachments()->create([
                'work_task_comment_id'=>$commentId,
                'uploaded_by_id'=>$file['uploaded_by_id'],
                'original_name'=>$file['original_name'],
                'storage_path'=>$finalPath,
                'mime_type'=>$file['mime_type'],
                'size'=>$file['size'],
            ]);
        }
    }

    private function cleanupStagedAttachments(array $staged): void
    {
        $directory = $staged['directory'] ?? null;
        if (! $directory) return;

        try {
            $disk = Storage::disk('local');
            if ($disk->directoryExists($directory) && ! $disk->deleteDirectory($directory)) {
                Log::warning('Không thể xóa thư mục file công việc tạm.', ['directory'=>$directory]);
            }
        } catch (\Throwable $exception) {
            Log::warning('Không thể dọn thư mục file công việc tạm.', [
                'directory'=>$directory,
                'error'=>$exception->getMessage(),
            ]);
        }
    }

    private function cleanupStoredPaths(array $paths): void
    {
        try {
            $disk = Storage::disk('local');
            $existing = collect($paths)->unique()->filter(fn ($path) => $disk->exists($path))->values();
            if ($existing->isNotEmpty() && ! $disk->delete($existing->all())) {
                Log::warning('Không thể dọn file công việc sau khi giao dịch thất bại.', [
                    'paths'=>$existing->all(),
                ]);
            }
        } catch (\Throwable $exception) {
            Log::warning('Không thể dọn file công việc sau khi giao dịch thất bại.', [
                'paths'=>$paths,
                'error'=>$exception->getMessage(),
            ]);
        }
    }

    private function movePathsToTrash(iterable $paths): array
    {
        $directory = 'work-task-attachments/.trash/'.Str::uuid();
        $trash = ['directory'=>$directory, 'files'=>[]];
        $disk = Storage::disk('local');

        try {
            foreach (collect($paths)->filter()->unique() as $sourcePath) {
                if (! $disk->exists($sourcePath)) continue;
                $trashPath = $directory.'/'.Str::uuid().'-'.basename($sourcePath);
                $trash['files'][] = ['source'=>$sourcePath, 'trash'=>$trashPath];
                if (! $disk->move($sourcePath, $trashPath) || ! $disk->exists($trashPath)) {
                    throw new \RuntimeException('Could not move a work-task attachment to trash.');
                }
            }
        } catch (\Throwable $exception) {
            $this->restoreTrashedPaths($trash);
            Log::warning('Không thể chuyển file công việc sang vùng thu hồi.', [
                'error'=>$exception->getMessage(),
            ]);
            throw ValidationException::withMessages([
                'attachments'=>'Không thể cập nhật file trên host. Vui lòng thử lại.',
            ]);
        }

        return $trash;
    }

    private function restoreTrashedPaths(array $trash): void
    {
        try {
            $disk = Storage::disk('local');
            $restoreFailed = false;
            foreach (array_reverse($trash['files'] ?? []) as $file) {
                if (! $disk->exists($file['trash'])) continue;
                if (
                    $disk->exists($file['source'])
                    || ! $disk->move($file['trash'], $file['source'])
                    || ! $disk->exists($file['source'])
                ) {
                    $restoreFailed = true;
                    Log::error('Không thể phục hồi file công việc sau khi giao dịch thất bại.', $file);
                }
            }
            $directory = $trash['directory'] ?? null;
            if (! $restoreFailed && $directory && $disk->directoryExists($directory) && ! $disk->deleteDirectory($directory)) {
                Log::warning('Không thể dọn vùng thu hồi sau khi phục hồi file.', ['directory'=>$directory]);
            }
        } catch (\Throwable $exception) {
            Log::error('Không thể phục hồi vùng file công việc đã thu hồi.', [
                'trash'=>$trash,
                'error'=>$exception->getMessage(),
            ]);
        }
    }

    private function purgeTrashedPaths(array $trash): void
    {
        $directory = $trash['directory'] ?? null;
        if (! $directory) return;

        try {
            $disk = Storage::disk('local');
            if ($disk->directoryExists($directory) && ! $disk->deleteDirectory($directory)) {
                Log::warning('Không thể dọn vùng thu hồi file công việc.', ['directory'=>$directory]);
            }
        } catch (\Throwable $exception) {
            Log::warning('Không thể dọn vùng thu hồi file công việc.', [
                'directory'=>$directory,
                'error'=>$exception->getMessage(),
            ]);
        }
    }

    private function cleanupTaskDirectory(int $taskId): void
    {
        $directory = 'work-task-attachments/'.$taskId;
        try {
            $disk = Storage::disk('local');
            if ($disk->directoryExists($directory) && ! $disk->deleteDirectory($directory)) {
                Log::warning('Không thể xóa hết thư mục file của công việc.', [
                    'task_id'=>$taskId,
                    'directory'=>$directory,
                ]);
            }
        } catch (\Throwable $exception) {
            Log::warning('Không thể xóa hết thư mục file của công việc.', [
                'task_id'=>$taskId,
                'directory'=>$directory,
                'error'=>$exception->getMessage(),
            ]);
        }
    }

    private function plainTextExcerpt(string $html): ?string
    {
        $withSpacing = preg_replace(
            '/<(?:br\s*\/?|\/(?:p|div|li|blockquote|h[1-3]))>/i',
            ' ',
            $html
        ) ?? $html;
        $plainText = html_entity_decode(strip_tags($withSpacing), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plainText = preg_replace('/[\s\x{00A0}]+/u', ' ', $plainText) ?? $plainText;
        $plainText = trim($plainText);

        return $plainText === '' ? null : Str::limit($plainText, 500, '');
    }

    private function sanitizeRichText(?string $html): ?string
    {
        if ($html === null || trim($html) === '') return null;
        $html = strip_tags($html, '<p><div><br><strong><b><em><i><u><s><ul><ol><li><blockquote><h1><h2><h3><a><mark>');
        $html = preg_replace('/<mark\b[^>]*>/i', '<mark>', $html) ?? $html;
        $html = preg_replace('/<\/mark\b[^>]*>/i', '</mark>', $html) ?? $html;
        $html = preg_replace('/\s+(on\w+|style|class|id)\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/is', '', $html);
        $html = preg_replace_callback('/<a\b([^>]*)>/i', function ($match) {
            if (! preg_match('/href\s*=\s*(["\'])(.*?)\1/i', $match[1], $href)) return '<a>';
            $url = trim(html_entity_decode($href[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if (! preg_match('#^(https?://|mailto:)#i', $url)) return '<a>';
            return '<a href="'.e($url).'" target="_blank" rel="noopener noreferrer">';
        }, $html);
        $html = preg_replace(
            '/(?:[\s\x{00A0}]|&nbsp;|&#0*160;|&#x0*a0;)+(?=(?:<\/(?:p|div|strong|b|em|i|u|s|ul|ol|li|blockquote|h[1-3]|a|mark)>\s*)*$)/iu',
            '',
            $html
        ) ?? $html;
        return trim($html);
    }
}
