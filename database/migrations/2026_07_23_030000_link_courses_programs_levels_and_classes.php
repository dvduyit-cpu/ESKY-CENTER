<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{
  Schema::table('language_courses',function(Blueprint $t){$t->foreignId('language_program_id')->nullable()->after('code')->constrained()->restrictOnDelete();$t->foreignId('language_level_id')->nullable()->after('language_program_id')->constrained()->restrictOnDelete();});
  Schema::table('language_classes',fn(Blueprint $t)=>$t->foreignId('language_course_id')->nullable()->after('name')->constrained()->restrictOnDelete());
  foreach(DB::table('language_courses')->whereNull('deleted_at')->get() as $course){$class=DB::table('language_classes')->whereNull('deleted_at')->where(fn($q)=>$q->where('name',$course->name)->orWhere('default_tuition',$course->tuition))->orderByRaw('name = ? desc',[$course->name])->first();if(!$class)continue;$levelProgram=$class->language_level_id?DB::table('language_levels')->where('id',$class->language_level_id)->value('language_program_id'):null;$levelId=$levelProgram==(int)$class->language_program_id?$class->language_level_id:null;DB::table('language_courses')->where('id',$course->id)->update(['language_program_id'=>$class->language_program_id,'language_level_id'=>$levelId]);DB::table('language_classes')->where('id',$class->id)->update(['language_course_id'=>$course->id,'language_level_id'=>$levelId,'default_tuition'=>$course->tuition,'expected_sessions'=>$course->sessions]);}
 }
 public function down():void{Schema::table('language_classes',fn(Blueprint $t)=>$t->dropConstrainedForeignId('language_course_id'));Schema::table('language_courses',function(Blueprint $t){$t->dropConstrainedForeignId('language_level_id');$t->dropConstrainedForeignId('language_program_id');});}
};
