<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{Schema::table('language_target_submissions',fn(Blueprint $table)=>$table->foreignId('language_lead_id')->nullable()->after('course_key')->constrained('language_leads')->nullOnDelete());}
 public function down():void{Schema::table('language_target_submissions',fn(Blueprint $table)=>$table->dropConstrainedForeignId('language_lead_id'));}
};
