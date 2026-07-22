<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        $groups=DB::table('language_tuition_charges')->whereNotNull('language_class_id')->select('language_student_id','language_class_id',DB::raw('COUNT(*) total'))->groupBy('language_student_id','language_class_id')->having('total','>',1)->get();
        foreach($groups as $group){
            $charges=DB::table('language_tuition_charges')->where('language_student_id',$group->language_student_id)->where('language_class_id',$group->language_class_id)->orderBy('id')->get();
            $keeper=$charges->first(); $duplicateIds=$charges->skip(1)->pluck('id');
            DB::table('language_tuition_payments')->whereIn('language_tuition_charge_id',$duplicateIds)->update(['language_tuition_charge_id'=>$keeper->id]);
            $payable=(float)$charges->sum('payable_amount'); $paid=(float)DB::table('language_tuition_payments')->where('language_tuition_charge_id',$keeper->id)->sum('amount');
            $pending=DB::table('language_tuition_payments')->where('language_tuition_charge_id',$keeper->id)->where('receipt_status','pending')->exists();
            $status=$pending?'pending_receipt':($paid>=$payable?'paid':($paid>0?'partial':'unpaid'));
            DB::table('language_tuition_charges')->where('id',$keeper->id)->update(['original_amount'=>$charges->sum('original_amount'),'discount_amount'=>$charges->sum('discount_amount'),'payable_amount'=>$payable,'paid_amount'=>$paid,'status'=>$status,'updated_at'=>now()]);
            DB::table('language_tuition_charges')->whereIn('id',$duplicateIds)->delete();
        }
        Schema::table('language_tuition_charges',fn(Blueprint $table)=>$table->unique(['language_student_id','language_class_id'],'tuition_student_class_unique'));
    }
    public function down(): void { Schema::table('language_tuition_charges',fn(Blueprint $table)=>$table->dropUnique('tuition_student_class_unique')); }
};
