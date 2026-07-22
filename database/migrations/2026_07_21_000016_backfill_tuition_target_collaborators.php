<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('language_tuition_charges')->whereNull('language_lead_id')->orderBy('id')->each(function ($charge) {
            $lead=DB::table('language_leads')->whereNull('deleted_at')->where('converted_student_id',$charge->language_student_id)
                ->orderByRaw('language_course_id = ? desc',[$charge->language_course_id])->orderByDesc('id')->first();
            if ($lead) DB::table('language_tuition_charges')->where('id',$charge->id)->update(['language_lead_id'=>$lead->id,'updated_at'=>now()]);
        });

        DB::table('language_monthly_target_records')->orderBy('id')->each(function ($record) {
            $charge=DB::table('language_tuition_payments as payment')
                ->join('language_tuition_charges as charge','charge.id','=','payment.language_tuition_charge_id')
                ->where('payment.id',$record->language_tuition_payment_id)->select('charge.language_lead_id')->first();
            if (! $charge?->language_lead_id) return;
            $collaboratorId=DB::table('language_leads')->where('id',$charge->language_lead_id)->value('language_collaborator_id');
            DB::table('language_monthly_target_records')->where('id',$record->id)->update(['language_lead_id'=>$charge->language_lead_id,'language_collaborator_id'=>$collaboratorId,'updated_at'=>now()]);
        });
    }

    public function down(): void {}
};
