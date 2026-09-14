<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('language_leads', function (Blueprint $table) {
            $table->foreignId('language_class_id')
                ->nullable()
                ->after('language_course_id')
                ->constrained()
                ->nullOnDelete();
        });

        Schema::table('language_target_submissions', function (Blueprint $table) {
            $table->foreignId('language_class_id')
                ->nullable()
                ->after('language_course_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('language_target_submissions', fn (Blueprint $table) => $table->dropConstrainedForeignId('language_class_id'));
        Schema::table('language_leads', fn (Blueprint $table) => $table->dropConstrainedForeignId('language_class_id'));
    }
};
