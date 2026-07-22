<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('language_target_submissions', function (Blueprint $table) {
            $table->string('phone_normalized',30)->nullable()->after('phone');
            $table->string('course_key')->nullable()->after('other_course');
            $table->index(['phone_normalized','course_key'],'lts_duplicate_lookup_idx');
        });
        DB::table('language_target_submissions')->orderBy('id')->each(function ($row) {
            $phone=preg_replace('/\D+/', '', $row->phone) ?: trim($row->phone);
            $courseKey=$row->language_course_id ? 'course:'.$row->language_course_id : 'other:'.Str::lower(Str::ascii(Str::squish($row->other_course ?? '')));
            DB::table('language_target_submissions')->where('id',$row->id)->update(['phone_normalized'=>$phone,'course_key'=>$courseKey]);
        });
    }

    public function down(): void
    {
        Schema::table('language_target_submissions', function (Blueprint $table) {
            $table->dropIndex('lts_duplicate_lookup_idx');
            $table->dropColumn(['phone_normalized','course_key']);
        });
    }
};
