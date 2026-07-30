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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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
        $tasks = DB::transaction(function () use ($request, $data, $ids, $repeatUnit, $repeatCount, $firstDueAt) {
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
                $createdTasks->push($task);
            }
            return $createdTasks;
        });
        foreach ($tasks as $task) {
            $this->storeAttachments($request, $task);
        }
        RealtimeNotifier::users($ids, ($repeatCount > 1 ? 'Bạn được giao công việc định kỳ: ' : 'Bạn được giao công việc: ').$data['title']);
        User::query()->whereKey($ids)->where('notifications_enabled', true)->get()->each(function (User $recipient) use ($request, $tasks): void {
            try {
                $recipient->notify(new WorkTaskAssigned($request->user(), $tasks));
            } catch (\Throwable $exception) {
                Log::warning('Không thể gửi email thông báo giao việc.', [
                    'recipient_id'=>$recipient->id,
                    'task_id'=>$tasks->first()?->id,
                    'error'=>$exception->getMessage(),
                ]);
            }
        });
        $periodLabel = $repeatUnit === 'week' ? 'hàng tuần' : 'hàng tháng';
        return redirect()->route('tasks.index')->with('success', $repeatCount > 1 ? 'Đã giao '.$repeatCount.' kỳ công việc '.$periodLabel.'.' : 'Đã giao công việc.');
    }

    public function show(Request $request, WorkTask $task): View
    {
        $this->ensureParticipant($request,$task);
        $task->load(['creator','assignees.user','comments.user','comments.attachments','activities.user','attachments']);
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
        $previousIds = $task->assignees()->pluck('user_id');
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
        $this->storeAttachments($request, $task);
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
        $assignment = $this->assignment($request,$task);
        $assignment->update(['acknowledged_at'=>$assignment->acknowledged_at ? null : now()]);
        $this->log($task,$request,$assignment->acknowledged_at ? 'Đã xác nhận nhận công việc.' : 'Đã bỏ xác nhận nhận công việc.','acknowledged');
        $recipients = $this->participantIds($task)->reject(fn ($id)=>(int)$id===$request->user()->id);
        RealtimeNotifier::users($recipients, $request->user()->name.' vừa cập nhật xác nhận: '.$task->title);
        $this->sendActivityEmails(
            $request,
            $task,
            $recipients,
            $assignment->acknowledged_at ? 'Đã nhận việc' : 'Đã bỏ xác nhận nhận việc',
            $assignment->acknowledged_at ? 'đã xác nhận nhận việc.' : 'đã bỏ xác nhận nhận việc.'
        );
        return back()->with('success','Đã cập nhật xác nhận.');
    }

    public function complete(Request $request, WorkTask $task): RedirectResponse
    {
        if ($task->closed_at) return back()->with('warning', 'Task đã đóng. Chỉ người giao việc mới có thể mở lại task.');
        $assignment = $this->assignment($request,$task);
        $data = $request->validate(['note'=>'nullable|string|max:2000']);
        $data['note'] = $this->sanitizeRichText($data['note'] ?? null);
        $assignment->update(['completed_at'=>$assignment->completed_at ? null : now(), 'note'=>$data['note'] ?? $assignment->note]);
        $this->log($task,$request,$assignment->completed_at ? 'Đã hoàn thành công việc.' : 'Đã mở lại công việc.','status');
        $recipients = $this->participantIds($task)->reject(fn ($id)=>(int)$id===$request->user()->id);
        RealtimeNotifier::users($recipients, $request->user()->name.' vừa cập nhật trạng thái: '.$task->title);
        $this->sendActivityEmails(
            $request,
            $task,
            $recipients,
            $assignment->completed_at ? 'Công việc đã hoàn thành' : 'Công việc đã được mở lại',
            $assignment->completed_at ? 'đã báo cáo hoàn thành công việc.' : 'đã mở lại công việc để tiếp tục thực hiện.'
        );
        return back()->with('success','Đã cập nhật trạng thái thực hiện.');
    }

    public function comment(Request $request, WorkTask $task): RedirectResponse
    {
        $this->ensureParticipant($request,$task);
        if ($task->closed_at) return back()->with('warning', 'Task đã đóng. Không thể gửi thêm phản hồi.');
        $data=$request->validate([
            'body'=>'required|string|max:3000',
            'attachments'=>'nullable|array|max:5',
            'attachments.*'=>'file|max:10240',
        ]);
        $data['body'] = $this->sanitizeRichText($data['body']);
        $comment = $task->comments()->create(['user_id'=>$request->user()->id,'body'=>$data['body']]);
        $this->storeAttachments($request, $task, $comment->id);
        $this->log($task,$request,'Đã gửi một phản hồi.','comment');
        $recipients = $this->participantIds($task)->reject(fn ($id)=>(int)$id===$request->user()->id);
        RealtimeNotifier::users($recipients, 'Phản hồi mới trong công việc: '.$task->title);
        $this->sendActivityEmails($request, $task, $recipients, 'Phản hồi mới', 'vừa gửi một phản hồi mới.');
        return back()->with('success','Đã gửi phản hồi.');
    }

    public function retractComment(Request $request, WorkTask $task, WorkTaskComment $comment): RedirectResponse
    {
        $this->ensureParticipant($request, $task);
        abort_unless($comment->work_task_id === $task->id, 404);
        abort_unless($comment->user_id === $request->user()->id, 403, 'Bạn chỉ được thu hồi phản hồi của chính mình.');

        if ($comment->created_at->lt(now()->subHours(24))) {
            throw ValidationException::withMessages([
                'comment' => 'Phản hồi đã quá 24 giờ nên không thể thu hồi.',
            ]);
        }

        DB::transaction(function () use ($request, $task, $comment) {
            $comment->load('attachments');
            foreach ($comment->attachments as $attachment) {
                Storage::disk('local')->delete($attachment->storage_path);
            }
            $comment->delete();
            $this->log($task, $request, 'Đã thu hồi một phản hồi.', 'comment_retracted');
        });

        $recipients = $this->participantIds($task)->reject(fn ($id) => (int) $id === $request->user()->id);
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

        return back()->with('success', 'Đã thu hồi phản hồi.');
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
            'svg' => 'image/svg+xml',
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

    public function destroyAttachment(Request $request, WorkTask $task, WorkTaskAttachment $attachment): RedirectResponse
    {
        abort_unless($task->created_by_id === $request->user()->id, 403, 'Chỉ người giao công việc mới được xóa file.');
        abort_unless($attachment->work_task_id === $task->id && $attachment->work_task_comment_id === null, 404);

        Storage::disk('local')->delete($attachment->storage_path);
        $fileName = $attachment->original_name;
        $attachment->delete();
        $this->log($task, $request, 'Đã xóa file đính kèm: '.$fileName, 'attachment_deleted');
        $this->sendActivityEmails(
            $request,
            $task,
            $this->participantIds($task),
            'File đính kèm đã bị xóa',
            'đã xóa file đính kèm “'.$fileName.'”.'
        );

        return back()->with('success', 'Đã xóa file đính kèm.');
    }

    public function close(Request $request, WorkTask $task): RedirectResponse
    {
        abort_unless(
            $task->created_by_id === $request->user()->id,
            403,
            'Chỉ người giao công việc mới được đóng hoặc mở lại task.'
        );

        if (! $task->closed_at && $task->assignees()->whereNull('completed_at')->exists()) {
            throw ValidationException::withMessages(['task' => 'Chỉ có thể đóng khi tất cả người nhận đã hoàn thành.']);
        }

        $closing = ! $task->closed_at;
        $task->update(['closed_at' => $closing ? now() : null, 'closed_by_id' => $closing ? $request->user()->id : null]);
        $this->log($task, $request, $closing ? 'Đã đóng task.' : 'Đã mở lại task.', 'closed');
        $recipients = $this->participantIds($task)->reject(fn ($id)=>(int)$id===$request->user()->id);
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
        $recipients = $this->participantIds($task)->reject(fn ($id)=>(int)$id===$request->user()->id);
        Storage::disk('local')->deleteDirectory('work-task-attachments/'.$task->id);
        $task->delete();
        RealtimeNotifier::users($recipients, 'Đã xóa công việc: '.$task->title);
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
    private function assignment(Request $request, WorkTask $task): WorkTaskAssignee
    { return $task->assignees()->where('user_id',$request->user()->id)->firstOrFail(); }
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

        User::query()
            ->whereKey($ids)
            ->where('active', true)
            ->where('notifications_enabled', true)
            ->get()
            ->each(function (User $recipient) use ($notification, $task, $eventTitle): void {
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
    }

    private function storeAttachments(Request $request, WorkTask $task, ?int $commentId = null): void
    {
        foreach ($request->file('attachments', []) as $file) {
            $path = $file->store('work-task-attachments/'.$task->id, 'local');
            $task->attachments()->create([
                'work_task_comment_id' => $commentId,
                'uploaded_by_id' => $request->user()->id,
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                'storage_path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }
    }

    private function sanitizeRichText(?string $html): ?string
    {
        if ($html === null || trim($html) === '') return null;
        $html = strip_tags($html, '<p><div><br><strong><b><em><i><u><s><ul><ol><li><blockquote><h1><h2><h3><a>');
        $html = preg_replace('/\s+(on\w+|style|class|id)\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/is', '', $html);
        $html = preg_replace_callback('/<a\b([^>]*)>/i', function ($match) {
            if (! preg_match('/href\s*=\s*(["\'])(.*?)\1/i', $match[1], $href)) return '<a>';
            $url = trim(html_entity_decode($href[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if (! preg_match('#^(https?://|mailto:)#i', $url)) return '<a>';
            return '<a href="'.e($url).'" target="_blank" rel="noopener noreferrer">';
        }, $html);
        return trim($html);
    }
}
