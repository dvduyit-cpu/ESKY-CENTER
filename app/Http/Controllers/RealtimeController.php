<?php

namespace App\Http\Controllers;

use App\Models\{LanguageLead,LanguageMonthlyTargetRecord,LanguageTargetSubmission,LanguageTuitionPayment,UpcomingPlan,WorkTask};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RealtimeController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $since = $request->date('since')?->utc();
        if (! $user->notifications_enabled) return response()->json(['server_time'=>now()->utc()->toIso8601String(),'enabled'=>false,'changed'=>false,'total'=>0,'items'=>[],'reminders'=>[]]);

        $canViewAll = $user->isAdmin() || $user->allowed('language_dashboard_all');
        $consulting = ($user->isAdmin() || $user->allowed('language_consulting')) ? LanguageLead::whereNotIn('status',['registered','not_interested'])->when(! $canViewAll,fn($q)=>$q->where('consultant_user_id',$user->id))->where(fn($q)=>$q->whereNull('last_consulted_at')->where('created_at','<=',now()->subDays(3))->orWhere('last_consulted_at','<=',now()->subDays(3)))->count() : 0;
        $submissionUpdates = $since && ($user->isAdmin() || $user->allowed('language_target_submissions')) ? LanguageTargetSubmission::where('submitted_by',$user->id)->whereHas('lead',fn($q)=>$q->where('updated_at','>',$since))->count() : 0;
        $pendingReceipts = $user->isAdmin() || $user->allowed('language_tuition') ? LanguageTuitionPayment::where('receipt_status','pending')->count() : 0;
        $newTargets = $since && ($user->isAdmin() || $user->allowed('language_targets')) ? LanguageMonthlyTargetRecord::where('updated_at','>',$since)->count() : 0;
        $workTasks = $user->allowed('work_tasks') ? WorkTask::query()
            ->whereNull('closed_at')
            ->whereHas('assignees', fn ($query) => $query->where('user_id', $user->id)->whereNull('completed_at'))
            ->where(fn ($query) => $query
                ->whereHas('assignees', fn ($assignee) => $assignee->where('user_id', $user->id)->whereNull('acknowledged_at'))
                ->orWhereBetween('due_at', [now(), now()->addDays(3)->endOfDay()]))
            ->orderBy('due_at')
            ->get(['id', 'title', 'due_at']) : collect();
        $workTasksDue = $workTasks->count();

        $reminders = UpcomingPlan::query()->where('user_id',$user->id)->whereNull('completed_at')->where('scheduled_for','<=',now()->addDays(30)->endOfDay())->orderBy('scheduled_for')->get()->filter->is_due_for_reminder->take(10)->values();
        $newPlans = $since ? UpcomingPlan::query()->where('user_id',$user->id)->where('created_at','>',$since)->count() : 0;
        $items = collect([
            ['key'=>'consulting','label'=>'Công việc tư vấn quá hạn','count'=>$consulting,'url'=>route('language-consulting.index')],
            ['key'=>'submissions','label'=>'Chỉ tiêu gửi đã được cập nhật','count'=>$submissionUpdates,'url'=>route('language-target-submissions.index')],
            ['key'=>'receipts','label'=>'Phiếu thu chờ bổ sung','count'=>$pendingReceipts,'url'=>route('language-tuition.index',['status'=>'pending_receipt'])],
            ['key'=>'targets','label'=>'Chỉ tiêu trung tâm mới','count'=>$newTargets,'url'=>route('language-targets.index')],
            ['key'=>'work_tasks','label'=>'Công việc mới hoặc sắp đến hạn','count'=>$workTasksDue,'names'=>$workTasks->take(3)->pluck('title')->values(),'url'=>route('tasks.index',['status'=>'unread'])],
        ])->filter(fn($item)=>$item['count']>0)->values();

        return response()->json([
            'server_time'=>now()->utc()->toIso8601String(),'enabled'=>true,
            'changed'=>(bool)($since && ($submissionUpdates+$newTargets+$newPlans>0)),
            'total'=>$items->sum('count')+$reminders->count(),'items'=>$items,
            'reminders'=>$reminders->map(fn(UpcomingPlan $plan)=>['id'=>$plan->id,'title'=>$plan->title,'time'=>$plan->scheduled_for->format('H:i d/m/Y'),'overdue'=>$plan->scheduled_for->isPast(),'url'=>route('plans.index',['month'=>$plan->scheduled_for->format('Y-m')])])->values(),
        ]);
    }
}
