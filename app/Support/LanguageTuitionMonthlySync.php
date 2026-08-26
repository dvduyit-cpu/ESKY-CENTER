<?php

namespace App\Support;

use App\Models\LanguageMonthlyTargetRecord;
use App\Models\LanguageTuitionCharge;
use App\Models\LanguageTuitionPayment;
use Carbon\CarbonInterface;

class LanguageTuitionMonthlySync
{
    /**
     * @return array{
     *     scanned_payments:int,
     *     scanned_charges:int,
     *     pending_with_receipt_code:int,
     *     missing_monthly_records:int,
     *     stale_monthly_records:int,
     *     orphan_monthly_records:int,
     *     charge_mismatches:int,
     *     fixable_count:int,
     *     has_issues:bool,
     *     sample_issues:array<int, array{type:string,label:string}>
     * }
     */
    public function inspect(int $sampleLimit = 12): array
    {
        $report = [
            'scanned_payments' => 0,
            'scanned_charges' => 0,
            'pending_with_receipt_code' => 0,
            'missing_monthly_records' => 0,
            'stale_monthly_records' => 0,
            'orphan_monthly_records' => 0,
            'charge_mismatches' => 0,
            'fixable_count' => 0,
            'has_issues' => false,
            'sample_issues' => [],
        ];

        LanguageTuitionPayment::query()
            ->with(['charge.lead'])
            ->orderBy('id')
            ->chunkById(200, function ($payments) use (&$report, $sampleLimit): void {
                $paymentIds = $payments->pluck('id')->all();
                $records = LanguageMonthlyTargetRecord::query()
                    ->whereIn('language_tuition_payment_id', $paymentIds)
                    ->get()
                    ->keyBy('language_tuition_payment_id');

                foreach ($payments as $payment) {
                    $report['scanned_payments']++;
                    $record = $records->get($payment->id);
                    $expected = $this->expectedMonthlyRecord($payment);
                    $hasReceiptCode = filled($payment->receipt_code);

                    if ($payment->receipt_status === 'pending' && $hasReceiptCode) {
                        $report['pending_with_receipt_code']++;
                        $this->pushSample(
                            $report['sample_issues'],
                            $sampleLimit,
                            'Phiếu chờ đã có số phiếu',
                            $this->paymentLabel($payment)
                        );
                    }

                    if ($expected === null) {
                        if ($record) {
                            $report['orphan_monthly_records']++;
                            $this->pushSample(
                                $report['sample_issues'],
                                $sampleLimit,
                                'Bản ghi tháng dư',
                                $this->paymentLabel($payment)
                            );
                        }

                        continue;
                    }

                    if (! $record) {
                        $report['missing_monthly_records']++;
                        $this->pushSample(
                            $report['sample_issues'],
                            $sampleLimit,
                            'Thiếu ở thu học phí theo tháng',
                            $this->paymentLabel($payment)
                        );
                        continue;
                    }

                    if ($this->recordNeedsUpdate($record, $expected)) {
                        $report['stale_monthly_records']++;
                        $this->pushSample(
                            $report['sample_issues'],
                            $sampleLimit,
                            'Dữ liệu tháng bị lệch',
                            $this->paymentLabel($payment)
                        );
                    }
                }
            });

        LanguageTuitionCharge::query()
            ->with(['payments:id,language_tuition_charge_id,receipt_status,amount', 'lead'])
            ->orderBy('id')
            ->chunkById(200, function ($charges) use (&$report, $sampleLimit): void {
                foreach ($charges as $charge) {
                    $report['scanned_charges']++;

                    $confirmedPaid = (float) $charge->payments
                        ->where('receipt_status', 'confirmed')
                        ->sum('amount');
                    $hasPendingReceipt = $charge->payments
                        ->contains(fn (LanguageTuitionPayment $payment) => $payment->receipt_status === 'pending');
                    $settledAmount = $confirmedPaid + (float) $charge->credit_amount;
                    $expectedStatus = $hasPendingReceipt
                        ? 'pending_receipt'
                        : ($settledAmount >= (float) $charge->payable_amount
                            ? 'paid'
                            : ($settledAmount > 0 ? 'partial' : 'unpaid'));

                    if (
                        abs($confirmedPaid - (float) $charge->paid_amount) > 0.001
                        || $expectedStatus !== $charge->status
                    ) {
                        $report['charge_mismatches']++;
                        $this->pushSample(
                            $report['sample_issues'],
                            $sampleLimit,
                            'Khoản thu cần tính lại trạng thái',
                            $this->chargeLabel($charge)
                        );
                    }
                }
            });

        $report['fixable_count'] = $report['pending_with_receipt_code']
            + $report['missing_monthly_records']
            + $report['stale_monthly_records']
            + $report['orphan_monthly_records']
            + $report['charge_mismatches'];
        $report['has_issues'] = $report['fixable_count'] > 0;

        return $report;
    }

    /**
     * @return array{
     *     confirmed_pending_payments:int,
     *     created_monthly_records:int,
     *     updated_monthly_records:int,
     *     removed_monthly_records:int,
     *     refreshed_charges:int
     * }
     */
    public function sync(): array
    {
        $result = [
            'confirmed_pending_payments' => 0,
            'created_monthly_records' => 0,
            'updated_monthly_records' => 0,
            'removed_monthly_records' => 0,
            'refreshed_charges' => 0,
        ];

        LanguageTuitionPayment::query()
            ->with(['charge.lead'])
            ->orderBy('id')
            ->chunkById(200, function ($payments) use (&$result): void {
                $paymentIds = $payments->pluck('id')->all();
                $records = LanguageMonthlyTargetRecord::query()
                    ->whereIn('language_tuition_payment_id', $paymentIds)
                    ->get()
                    ->keyBy('language_tuition_payment_id');

                foreach ($payments as $payment) {
                    if ($payment->receipt_status === 'pending' && filled($payment->receipt_code)) {
                        $payment->update([
                            'receipt_status' => 'confirmed',
                            'confirmed_at' => $payment->confirmed_at ?: now(),
                        ]);
                        $payment->refresh();
                        $result['confirmed_pending_payments']++;
                    }

                    $record = $records->get($payment->id);
                    $expected = $this->expectedMonthlyRecord($payment);

                    if ($expected === null) {
                        if ($record) {
                            $record->delete();
                            $result['removed_monthly_records']++;
                        }

                        continue;
                    }

                    if (! $record) {
                        LanguageMonthlyTargetRecord::create(
                            ['language_tuition_payment_id' => $payment->id] + $expected
                        );
                        $result['created_monthly_records']++;
                        continue;
                    }

                    if ($this->recordNeedsUpdate($record, $expected)) {
                        $record->update($expected);
                        $result['updated_monthly_records']++;
                    }
                }
            });

        LanguageTuitionCharge::query()
            ->with('payments:id,language_tuition_charge_id,receipt_status,amount')
            ->orderBy('id')
            ->chunkById(200, function ($charges) use (&$result): void {
                foreach ($charges as $charge) {
                    $confirmedPaid = (float) $charge->payments
                        ->where('receipt_status', 'confirmed')
                        ->sum('amount');
                    $hasPendingReceipt = $charge->payments
                        ->contains(fn (LanguageTuitionPayment $payment) => $payment->receipt_status === 'pending');
                    $settledAmount = $confirmedPaid + (float) $charge->credit_amount;
                    $status = $hasPendingReceipt
                        ? 'pending_receipt'
                        : ($settledAmount >= (float) $charge->payable_amount
                            ? 'paid'
                            : ($settledAmount > 0 ? 'partial' : 'unpaid'));

                    if (
                        abs($confirmedPaid - (float) $charge->paid_amount) <= 0.001
                        && $status === $charge->status
                    ) {
                        continue;
                    }

                    $charge->update([
                        'paid_amount' => $confirmedPaid,
                        'status' => $status,
                    ]);
                    $result['refreshed_charges']++;
                }
            });

        return $result;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function expectedMonthlyRecord(LanguageTuitionPayment $payment): ?array
    {
        $payment->loadMissing(['charge.lead']);
        $charge = $payment->charge;

        if (! $charge || $payment->receipt_status !== 'confirmed' || ! $payment->paid_at instanceof CarbonInterface) {
            return null;
        }

        return [
            'record_year' => $payment->paid_at->year,
            'record_month' => $payment->paid_at->month,
            'language_student_id' => $charge->language_student_id,
            'language_lead_id' => $charge->language_lead_id,
            'language_collaborator_id' => $charge->lead?->language_collaborator_id,
            'language_course_id' => $charge->language_course_id,
            'quantity' => 1,
            'revenue' => round((float) $payment->amount + (float) $payment->book_amount, 2),
            'note' => 'Thu học phí '.$charge->code,
        ];
    }

    /**
     * @param array<string, mixed> $expected
     */
    private function recordNeedsUpdate(LanguageMonthlyTargetRecord $record, array $expected): bool
    {
        foreach ([
            'record_year',
            'record_month',
            'language_student_id',
            'language_lead_id',
            'language_collaborator_id',
            'language_course_id',
            'note',
        ] as $field) {
            if ((string) $record->{$field} !== (string) ($expected[$field] ?? '')) {
                return true;
            }
        }

        return abs((float) $record->quantity - (float) ($expected['quantity'] ?? 0)) > 0.001
            || abs((float) $record->revenue - (float) ($expected['revenue'] ?? 0)) > 0.001;
    }

    /**
     * @param array<int, array{type:string,label:string}> $samples
     */
    private function pushSample(array &$samples, int $limit, string $type, string $label): void
    {
        if (count($samples) >= $limit) {
            return;
        }

        $samples[] = [
            'type' => $type,
            'label' => $label,
        ];
    }

    private function paymentLabel(LanguageTuitionPayment $payment): string
    {
        $payment->loadMissing('charge.student');
        $chargeCode = $payment->charge?->code ?: 'Không rõ khoản thu';
        $studentName = $payment->charge?->student?->name ?: 'Không rõ học viên';
        $receiptCode = $payment->receipt_code ?: 'chưa có số phiếu';

        return $chargeCode.' - '.$studentName.' - '.$receiptCode;
    }

    private function chargeLabel(LanguageTuitionCharge $charge): string
    {
        $charge->loadMissing('student');

        return $charge->code.' - '.($charge->student?->name ?: 'Không rõ học viên');
    }
}
