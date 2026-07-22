<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('language_collaborator_id')->nullable()->after('personnel_id')->unique()
                ->constrained('language_collaborators')->nullOnDelete();
        });

        DB::table('users')->whereNotNull('personnel_id')->orderBy('id')->each(function ($user) {
            $collaboratorId=DB::table('language_collaborators')->where('personnel_id',$user->personnel_id)->value('id');
            if ($collaboratorId) DB::table('users')->where('id',$user->id)->update(['language_collaborator_id'=>$collaboratorId]);
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('language_collaborator_id'));
    }
};
