<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('language_target_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone',30);
            $table->foreignId('language_course_id')->nullable()->constrained()->nullOnDelete();
            $table->string('other_course')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['submitted_by','created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('language_target_submissions');
    }
};
