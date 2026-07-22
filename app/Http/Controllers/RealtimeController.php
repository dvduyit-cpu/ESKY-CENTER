<?php

namespace App\Http\Controllers;

use App\Models\{LanguageLead,LanguageMonthlyTargetRecord,LanguageTargetSubmission,LanguageTuitionPayment};
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RealtimeController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $user=$request->user();
        $since=$request->date('since')?->utc();
        $canViewAll=$user->isAdmin()||$user->allowed('language_dashboard_all');

        $consulting=($user->isAdmin()||$user->allowed('language_consulting'))?LanguageLead::whereNotIn('status',['registered','not_interested'])
            ->when(! $canViewAll,fn($query)=>$query->where('consultant_user_id',$user->id))
            ->where(fn($query)=>$query->whereNull('last_consulted_at')->where('created_at','<=',now()->subDays(3))->orWhere('last_consulted_at','<=',now()->subDays(3)))
            ->count():0;

        $submissionUpdates=$since&&($user->isAdmin()||$user->allowed('language_target_submissions'))?LanguageTargetSubmission::where('submitted_by',$user->id)
            ->whereHas('lead',fn($query)=>$query->where(fn($q)=>$q->whereNotNull('last_consulted_at')->orWhere('status','!=','new')))
            ->whereHas('lead',fn($lead)=>$lead->where('updated_at','>',$since))
            ->count():0;

        $pendingReceipts=$user->isAdmin()||$user->allowed('language_tuition')
            ? LanguageTuitionPayment::where('receipt_status','pending')->count() : 0;
        $newTargets=$since&&($user->isAdmin()||$user->allowed('language_targets'))
            ? LanguageMonthlyTargetRecord::where('updated_at','>',$since)->count() : 0;

        return response()->json([
            'server_time'=>now()->utc()->toIso8601String(),
            'changed'=>$since&&($submissionUpdates>0||$newTargets>0),
            'total'=>$consulting+$submissionUpdates+$pendingReceipts+$newTargets,
            'items'=>[
                ['key'=>'consulting','label'=>'Công việc tư vấn quá hạn','count'=>$consulting,'url'=>route('language-consulting.index')],
                ['key'=>'submissions','label'=>'Chỉ tiêu gửi đã được cập nhật','count'=>$submissionUpdates,'url'=>route('language-target-submissions.index')],
                ['key'=>'receipts','label'=>'Phiếu thu chờ bổ sung','count'=>$pendingReceipts,'url'=>route('language-tuition.index',['status'=>'pending_receipt'])],
                ['key'=>'targets','label'=>'Chỉ tiêu trung tâm mới','count'=>$newTargets,'url'=>route('language-targets.index')],
            ],
        ]);
    }
}
