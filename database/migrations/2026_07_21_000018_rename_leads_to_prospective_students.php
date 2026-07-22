<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void { DB::table('modules')->where('code','language_leads')->update(['name'=>'Học viên tiềm năng','updated_at'=>now()]); }
    public function down(): void { DB::table('modules')->where('code','language_leads')->update(['name'=>'Khách hàng tiềm năng','updated_at'=>now()]); }
};
