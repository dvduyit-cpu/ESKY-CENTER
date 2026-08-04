<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('language_classes', function (Blueprint $table) {
            $table->foreignId('language_discount_policy_id')
                ->nullable()
                ->after('default_tuition')
                ->constrained('language_discount_policies')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('language_classes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('language_discount_policy_id');
        });
    }
};
