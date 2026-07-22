<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $senders = DB::table('language_target_submissions as submissions')
            ->join('users', 'users.id', '=', 'submissions.submitted_by')
            ->leftJoin('personnels', 'personnels.id', '=', 'users.personnel_id')
            ->whereNotNull('submissions.language_lead_id')
            ->select('users.id as user_id', 'users.name as user_name', 'users.email as user_email',
                'personnels.id as personnel_id', 'personnels.name as personnel_name',
                'personnels.phone as personnel_phone', 'personnels.email as personnel_email')
            ->distinct()->get();

        foreach ($senders as $sender) {
            $name = $sender->personnel_name ?: $sender->user_name;
            $email = $sender->personnel_email ?: $sender->user_email;
            $collaborator = DB::table('language_collaborators')->whereNull('deleted_at')
                ->where(function ($query) use ($sender, $name, $email) {
                    if ($sender->personnel_id) $query->where('personnel_id', $sender->personnel_id);
                    else $query->where('name', $name);
                    if ($sender->personnel_phone) $query->orWhere('phone', $sender->personnel_phone);
                    if ($email) $query->orWhere('email', $email);
                })->first();

            if (! $collaborator) {
                $id = DB::table('language_collaborators')->insertGetId([
                    'personnel_id'=>$sender->personnel_id, 'code'=>'CTV-GUI-'.$sender->user_id,
                    'name'=>$name, 'phone'=>$sender->personnel_phone, 'email'=>$email,
                    'commission_rate'=>0, 'active'=>true,
                    'note'=>'Tự động tạo từ account gửi chỉ tiêu.', 'created_at'=>now(), 'updated_at'=>now(),
                ]);
                $collaborator = (object) ['id'=>$id];
            } elseif ($sender->personnel_id && ! $collaborator->personnel_id) {
                DB::table('language_collaborators')->where('id',$collaborator->id)
                    ->update(['personnel_id'=>$sender->personnel_id,'updated_at'=>now()]);
            }

            $leadIds = DB::table('language_target_submissions')->where('submitted_by',$sender->user_id)
                ->whereNotNull('language_lead_id')->pluck('language_lead_id');
            DB::table('language_leads')->whereIn('id',$leadIds)->whereNull('language_collaborator_id')
                ->update(['language_collaborator_id'=>$collaborator->id,'updated_at'=>now()]);
        }
    }

    public function down(): void {}
};
