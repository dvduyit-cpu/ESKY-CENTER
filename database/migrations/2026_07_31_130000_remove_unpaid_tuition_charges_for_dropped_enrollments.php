<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $chargeIds = DB::table('language_tuition_charges as charge')
            ->join('language_enrollments as enrollment', function ($join) {
                $join->on('enrollment.language_student_id', '=', 'charge.language_student_id')
                    ->on('enrollment.language_class_id', '=', 'charge.language_class_id');
            })
            ->where('enrollment.status', 'dropped')
            ->where('charge.paid_amount', 0)
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('language_tuition_payments as payment')
                    ->whereColumn('payment.language_tuition_charge_id', 'charge.id');
            })
            ->pluck('charge.id');

        foreach ($chargeIds->chunk(500) as $chunk) {
            DB::table('language_tuition_charges')->whereIn('id', $chunk)->delete();
        }
    }

    public function down(): void
    {
        // Deleted empty charges contain no payment data and cannot be reconstructed reliably.
    }
};
