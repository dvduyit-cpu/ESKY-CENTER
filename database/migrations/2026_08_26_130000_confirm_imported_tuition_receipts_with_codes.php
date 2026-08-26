<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $payments = DB::table('language_tuition_payments')
            ->where('receipt_status', 'pending')
            ->whereNotNull('receipt_code')
            ->where('receipt_code', '!=', '')
            ->orderBy('id')
            ->get([
                'id',
                'language_tuition_charge_id',
                'paid_at',
                'amount',
                'book_amount',
            ]);

        if ($payments->isEmpty()) {
            return;
        }

        $paymentIds = [];
        $chargeIds = [];

        foreach ($payments as $payment) {
            $paymentIds[] = $payment->id;
            $chargeIds[] = $payment->language_tuition_charge_id;

            DB::table('language_tuition_payments')
                ->where('id', $payment->id)
                ->update([
                    'receipt_status' => 'confirmed',
                    'confirmed_at' => $now,
                    'updated_at' => $now,
                ]);

            $charge = DB::table('language_tuition_charges')
                ->leftJoin('language_leads', 'language_leads.id', '=', 'language_tuition_charges.language_lead_id')
                ->where('language_tuition_charges.id', $payment->language_tuition_charge_id)
                ->first([
                    'language_tuition_charges.code',
                    'language_tuition_charges.language_student_id',
                    'language_tuition_charges.language_lead_id',
                    'language_tuition_charges.language_course_id',
                    'language_leads.language_collaborator_id',
                ]);

            if ($charge && $payment->paid_at) {
                $paidAt = \Carbon\Carbon::parse($payment->paid_at);

                DB::table('language_monthly_target_records')->updateOrInsert(
                    ['language_tuition_payment_id' => $payment->id],
                    [
                        'record_year' => $paidAt->year,
                        'record_month' => $paidAt->month,
                        'language_student_id' => $charge->language_student_id,
                        'language_lead_id' => $charge->language_lead_id,
                        'language_collaborator_id' => $charge->language_collaborator_id,
                        'language_course_id' => $charge->language_course_id,
                        'quantity' => 1,
                        'revenue' => (float) $payment->amount + (float) $payment->book_amount,
                        'note' => 'Thu học phí '.$charge->code,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        foreach (array_values(array_unique($chargeIds)) as $chargeId) {
            $charge = DB::table('language_tuition_charges')
                ->where('id', $chargeId)
                ->first(['id', 'payable_amount', 'credit_amount']);

            if (! $charge) {
                continue;
            }

            $paidAmount = (float) DB::table('language_tuition_payments')
                ->where('language_tuition_charge_id', $chargeId)
                ->where('receipt_status', 'confirmed')
                ->sum('amount');

            $hasPendingReceipt = DB::table('language_tuition_payments')
                ->where('language_tuition_charge_id', $chargeId)
                ->where('receipt_status', 'pending')
                ->exists();

            $settledAmount = $paidAmount + (float) $charge->credit_amount;
            $status = $hasPendingReceipt
                ? 'pending_receipt'
                : ($settledAmount >= (float) $charge->payable_amount ? 'paid' : ($settledAmount > 0 ? 'partial' : 'unpaid'));

            DB::table('language_tuition_charges')
                ->where('id', $chargeId)
                ->update([
                    'paid_amount' => $paidAmount,
                    'status' => $status,
                    'updated_at' => $now,
                ]);
        }
    }

    public function down(): void
    {
        // Khong tu dong rollback vi khong phan biet duoc du lieu nao da duoc xac nhan thu cong sau khi backfill.
    }
};
