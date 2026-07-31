<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('language_class_transfers', function (Blueprint $table) {
            $table->decimal('source_credit_amount', 14, 2)->default(0)->after('source_paid_amount');
        });
    }

    public function down(): void
    {
        Schema::table('language_class_transfers', fn (Blueprint $table) => $table->dropColumn('source_credit_amount'));
    }
};
