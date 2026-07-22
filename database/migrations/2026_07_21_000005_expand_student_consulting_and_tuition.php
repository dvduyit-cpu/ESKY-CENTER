<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('language_students', function (Blueprint $table) {
            $table->date('official_enrollment_date')->nullable()->after('registered_at');
            $table->foreignId('language_course_id')->nullable()->after('source')->constrained()->nullOnDelete();
            $table->foreignId('language_discount_policy_id')->nullable()->after('language_course_id')->constrained()->nullOnDelete();
        });

        Schema::table('language_leads', function (Blueprint $table) {
            $table->dateTime('last_consulted_at')->nullable()->after('appointment_at');
        });
    }

    public function down(): void
    {
        Schema::table('language_leads', fn (Blueprint $table) => $table->dropColumn('last_consulted_at'));
        Schema::table('language_students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('language_discount_policy_id');
            $table->dropConstrainedForeignId('language_course_id');
            $table->dropColumn('official_enrollment_date');
        });
    }
};
