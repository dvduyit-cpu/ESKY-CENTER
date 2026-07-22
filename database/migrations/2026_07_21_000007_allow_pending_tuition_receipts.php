<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('language_tuition_payments', function (Blueprint $table) {
            $table->string('receipt_code', 30)->nullable()->change();
            $table->string('receipt_status', 20)->default('confirmed')->after('receipt_code')->index();
            $table->dateTime('confirmed_at')->nullable()->after('paid_at');
        });
        DB::table('language_tuition_payments')->update(['receipt_status'=>'confirmed','confirmed_at'=>DB::raw('paid_at')]);
    }

    public function down(): void
    {
        DB::table('language_tuition_payments')->whereNull('receipt_code')->delete();
        Schema::table('language_tuition_payments', function (Blueprint $table) {
            $table->dropIndex(['receipt_status']);
            $table->dropColumn(['receipt_status','confirmed_at']);
            $table->string('receipt_code',30)->nullable(false)->change();
        });
    }
};
