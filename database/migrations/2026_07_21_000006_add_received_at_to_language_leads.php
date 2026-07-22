<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('language_leads', function (Blueprint $table) {
            $table->date('received_at')->nullable()->after('source')->index();
        });
        DB::table('language_leads')->whereNull('received_at')->update(['received_at'=>DB::raw('DATE(created_at)')]);
    }

    public function down(): void
    {
        Schema::table('language_leads', fn (Blueprint $table) => $table->dropColumn('received_at'));
    }
};
