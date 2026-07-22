<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('language_classes', fn (Blueprint $table) => $table->unsignedSmallInteger('completed_sessions')->default(0)->after('expected_sessions'));
    }

    public function down(): void
    {
        Schema::table('language_classes', fn (Blueprint $table) => $table->dropColumn('completed_sessions'));
    }
};
