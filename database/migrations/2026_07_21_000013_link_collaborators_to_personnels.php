<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('language_collaborators', function (Blueprint $table) {
            $table->foreignId('personnel_id')->nullable()->after('id')->unique()
                ->constrained('personnels')->nullOnDelete();
        });

        $personnels = DB::table('personnels')
            ->where('type', 'collaborator')
            ->whereNull('deleted_at')
            ->get();

        foreach ($personnels as $personnel) {
            $collaborator = DB::table('language_collaborators')
                ->whereNull('deleted_at')
                ->where(function ($query) use ($personnel) {
                    $query->where('name', $personnel->name);
                    if ($personnel->phone) $query->orWhere('phone', $personnel->phone);
                    if ($personnel->email) $query->orWhere('email', $personnel->email);
                })
                ->first();

            if ($collaborator) {
                DB::table('language_collaborators')->where('id', $collaborator->id)
                    ->update(['personnel_id' => $personnel->id, 'updated_at' => now()]);
            } else {
                $collaboratorId = DB::table('language_collaborators')->insertGetId([
                    'personnel_id' => $personnel->id,
                    'code' => 'CTV-ACC-'.$personnel->id,
                    'name' => $personnel->name,
                    'phone' => $personnel->phone,
                    'email' => $personnel->email,
                    'commission_rate' => 0,
                    'active' => true,
                    'note' => 'Tự động liên kết từ account cộng tác viên.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $collaborator = (object) ['id' => $collaboratorId];
            }

            $userIds = DB::table('users')->where('personnel_id', $personnel->id)->pluck('id');
            if ($userIds->isEmpty()) continue;

            $leadIds = DB::table('language_target_submissions')
                ->whereIn('submitted_by', $userIds)
                ->whereNotNull('language_lead_id')
                ->pluck('language_lead_id');

            DB::table('language_leads')->whereIn('id', $leadIds)
                ->whereNull('language_collaborator_id')
                ->update(['language_collaborator_id' => $collaborator->id, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::table('language_collaborators', function (Blueprint $table) {
            $table->dropConstrainedForeignId('personnel_id');
        });
    }
};
