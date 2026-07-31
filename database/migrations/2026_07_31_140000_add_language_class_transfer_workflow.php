<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('language_tuition_charges', function (Blueprint $table) {
            $table->decimal('credit_amount', 14, 2)->default(0)->after('paid_amount');
        });

        Schema::create('language_class_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('language_student_id')->constrained('language_students')->restrictOnDelete();
            $table->foreignId('from_language_class_id')->constrained('language_classes')->restrictOnDelete();
            $table->foreignId('to_language_class_id')->constrained('language_classes')->restrictOnDelete();
            $table->foreignId('from_enrollment_id')->unique()->constrained('language_enrollments')->restrictOnDelete();
            $table->foreignId('to_enrollment_id')->constrained('language_enrollments')->restrictOnDelete();
            $table->foreignId('from_tuition_charge_id')->nullable()->constrained('language_tuition_charges')->nullOnDelete();
            $table->foreignId('to_tuition_charge_id')->constrained('language_tuition_charges')->restrictOnDelete();
            $table->date('effective_date');
            $table->unsignedSmallInteger('sessions_used')->default(0);
            $table->decimal('source_payable_amount', 14, 2)->default(0);
            $table->decimal('source_paid_amount', 14, 2)->default(0);
            $table->decimal('used_amount', 14, 2)->default(0);
            $table->decimal('transferred_amount', 14, 2)->default(0);
            $table->decimal('applied_amount', 14, 2)->default(0);
            $table->decimal('surplus_amount', 14, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('language_class_transfers');
        Schema::table('language_tuition_charges', fn (Blueprint $table) => $table->dropColumn('credit_amount'));
    }
};
