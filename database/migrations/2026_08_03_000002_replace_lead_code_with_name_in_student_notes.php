<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('language_leads')
            ->whereNotNull('converted_student_id')
            ->select(['code','name','converted_student_id'])
            ->orderBy('id')
            ->each(function ($lead): void {
                DB::table('language_students')
                    ->where('id',$lead->converted_student_id)
                    ->where('note','Chuyển từ khách hàng '.$lead->code)
                    ->update(['note'=>'Chuyển từ khách hàng '.$lead->name]);
            });
    }

    public function down(): void
    {
        DB::table('language_leads')
            ->whereNotNull('converted_student_id')
            ->select(['code','name','converted_student_id'])
            ->orderBy('id')
            ->each(function ($lead): void {
                DB::table('language_students')
                    ->where('id',$lead->converted_student_id)
                    ->where('note','Chuyển từ khách hàng '.$lead->name)
                    ->update(['note'=>'Chuyển từ khách hàng '.$lead->code]);
            });
    }
};
