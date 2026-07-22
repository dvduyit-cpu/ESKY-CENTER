<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('upcoming_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('note')->nullable();
            $table->dateTime('scheduled_for');
            $table->unsignedTinyInteger('reminder_days')->default(1);
            $table->string('priority', 20)->default('normal');
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'scheduled_for']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upcoming_plans');
    }
};
