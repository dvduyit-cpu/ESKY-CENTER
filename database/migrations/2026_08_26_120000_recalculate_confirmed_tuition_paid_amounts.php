<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('language_tuition_charges')
            ->orderBy('id')
            ->chunkById(200, function ($charges): void {
                foreach ($charges as $charge) {
                    $confirmedPaid = (float) DB::table('language_tuition_payments')
                        ->where('language_tuition_charge_id', $charge->id)
                        ->where('receipt_status', 'confirmed')
                        ->sum('amount');

                    $hasPendingReceipt = DB::table('language_tuition_payments')
                        ->where('language_tuition_charge_id', $charge->id)
                        ->where('receipt_status', 'pending')
                        ->exists();

                    $settledAmount = $confirmedPaid + (float) $charge->credit_amount;
                    $status = $hasPendingReceipt
                        ? 'pending_receipt'
                        : ($settledAmount >= (float) $charge->payable_amount
                            ? 'paid'
                            : ($settledAmount > 0 ? 'partial' : 'unpaid'));

                    DB::table('language_tuition_charges')
                        ->where('id', $charge->id)
                        ->update([
                            'paid_amount' => $confirmedPaid,
                            'status' => $status,
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        // Backfill only; no rollback needed.
    }
};
