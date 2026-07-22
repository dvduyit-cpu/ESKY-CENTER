<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personnels', function (Blueprint $table) {
            $table->boolean('is_consultant')->default(false)->after('has_kpi')->index();
        });
    }

    public function down(): void
    {
        Schema::table('personnels', function (Blueprint $table) {
            $table->dropIndex(['is_consultant']);
            $table->dropColumn('is_consultant');
        });
    }
};
