<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('language_target_submissions', function (Blueprint $table) {
            $table->string('source', 30)->nullable()->after('phone_normalized');
            $table->text('note')->nullable()->after('course_key');
        });
    }

    public function down(): void
    {
        Schema::table('language_target_submissions', function (Blueprint $table) {
            $table->dropColumn(['source', 'note']);
        });
    }
};
