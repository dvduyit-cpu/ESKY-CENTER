<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('language_tuition_payments', function (Blueprint $table) {
            $table->decimal('book_amount', 14, 2)->default(0)->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('language_tuition_payments', function (Blueprint $table) {
            $table->dropColumn('book_amount');
        });
    }
};