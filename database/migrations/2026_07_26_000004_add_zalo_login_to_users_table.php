<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('zalo_id', 100)->nullable()->unique()->after('email');
            $table->string('zalo_name')->nullable()->after('zalo_id');
            $table->timestamp('zalo_linked_at')->nullable()->after('zalo_name');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['zalo_id']);
            $table->dropColumn(['zalo_id', 'zalo_name', 'zalo_linked_at']);
        });
    }
};
