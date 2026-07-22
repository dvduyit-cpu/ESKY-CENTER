<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up():void{DB::table('language_target_submissions')->whereNull('language_lead_id')->orderBy('id')->each(function($submission){$phone=preg_replace('/\D+/','',$submission->phone)?:trim($submission->phone);$lead=DB::table('language_leads')->whereNull('deleted_at')->when($submission->language_course_id,fn($q)=>$q->where('language_course_id',$submission->language_course_id),fn($q)=>$q->whereNull('language_course_id'))->orderByDesc('id')->get()->first(fn($row)=>(preg_replace('/\D+/','',$row->phone)?:trim($row->phone))===$phone);if($lead)DB::table('language_target_submissions')->where('id',$submission->id)->update(['language_lead_id'=>$lead->id]);});}
 public function down():void{}
};
