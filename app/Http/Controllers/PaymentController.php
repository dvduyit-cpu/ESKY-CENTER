<?php

namespace App\Http\Controllers;

use App\Models\ExcessPayment;
use App\Models\KpiRecord;
use App\Models\Personnel;
use App\Support\ActivityLogger;
use App\Support\KpiCalculator;
use App\Support\Period;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct(private readonly KpiCalculator $calculator) {}

    public function index(Request $request): View
    {
        $query = ExcessPayment::with(['personnel','course'])->latest();
        if ($request->filled('year')) $query->where('year', $request->integer('year'));
        if ($request->filled('period_type')) $query->where('period_type', $request->string('period_type'));
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        if ($request->filled('payment_kind')) $query->where('payment_kind', $request->string('payment_kind'));
        return view('payments.index', ['payments' => $query->paginate(25)->withQueryString()]);
    }

    public function calculate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'year' => ['required','integer','min:2020','max:2100'],
            'period_type' => ['required', Rule::in(['month','quarter','year'])],
            'period_value' => ['nullable','integer','min:0','max:12'],
        ]);
        $value = $data['period_type'] === 'year' ? 0 : (int) ($data['period_value'] ?? 0);
        if ($data['period_type'] === 'month' && ($value < 1 || $value > 12)) return back()->withErrors(['period_value' => 'Tháng phải từ 1 đến 12.']);
        if ($data['period_type'] === 'quarter' && ($value < 1 || $value > 4)) return back()->withErrors(['period_value' => 'Quý phải từ 1 đến 4.']);

        $report = $this->calculator->report([
            'year' => (int) $data['year'], 'period_type' => $data['period_type'], 'period_value' => $value,
        ]);
        $count = 0;
        foreach ($report['rows'] as $row) {
            if ($row['excess_quantity'] <= 0 || $row['payment_amount'] <= 0) continue;
            $key = 'excess:'.$row['personnel_id'].':all';
            $payment = ExcessPayment::firstOrNew([
                'payment_key' => $key,
                'year' => (int) $data['year'],
                'period_type' => $data['period_type'],
                'period_value' => $value,
            ]);
            if (! $payment->exists) $payment->status = 'pending';
            $payment->fill([
                'payment_kind' => 'excess_kpi',
                'personnel_id' => $row['personnel_id'],
                'course_id' => $row['course_id'],
                'target_quantity' => $row['target_quantity'],
                'actual_quantity' => $row['actual_quantity'],
                'excess_quantity' => $row['excess_quantity'],
                'revenue_amount' => $row['revenue'],
                'payment_rate' => $row['payment_rate'],
                'payment_amount' => $row['payment_amount'],
                'calculated_by' => $request->user()->id,
            ])->save();
            $count++;
        }

        $collaboratorQuery = KpiRecord::query()->whereNotNull('collaborator_id');
        Period::applyRecordFilter($collaboratorQuery, (int) $data['year'], $data['period_type'], $value);
        $collaborators = $collaboratorQuery
            ->groupBy('collaborator_id')
            ->selectRaw('collaborator_id, SUM(raw_quantity) quantity, SUM(revenue) revenue')
            ->get();
        foreach ($collaborators as $item) {
            $person = Personnel::find($item->collaborator_id);
            if (! $person || $person->payment_type === 'none') continue;
            $amount = match ($person->payment_type) {
                'percentage' => (float) $item->revenue * (float) $person->payment_value / 100,
                'per_student' => (float) $item->quantity * (float) $person->payment_value,
                'fixed' => (float) $person->payment_value,
                default => 0,
            };
            if ($amount <= 0) continue;
            $payment = ExcessPayment::firstOrNew([
                'payment_key' => 'collaborator:'.$person->id,
                'year' => (int) $data['year'], 'period_type' => $data['period_type'], 'period_value' => $value,
            ]);
            if (! $payment->exists) $payment->status = 'pending';
            $payment->fill([
                'payment_kind' => 'collaborator', 'personnel_id' => $person->id, 'course_id' => null,
                'target_quantity' => 0, 'actual_quantity' => (float) $item->quantity,
                'excess_quantity' => (float) $item->quantity, 'revenue_amount' => (float) $item->revenue,
                'payment_rate' => (float) $person->payment_value, 'payment_amount' => round($amount, 2),
                'calculated_by' => $request->user()->id,
            ])->save();
            $count++;
        }

        ActivityLogger::log('payments', 'calculate', 'Tính '.$count.' khoản thanh toán '.Period::label($data['period_type'], $value, (int) $data['year']));
        return redirect()->route('payments.index', ['year' => $data['year'], 'period_type' => $data['period_type']])->with('success', "Đã tính {$count} khoản thanh toán.");
    }

    public function approve(Request $request, ExcessPayment $payment): RedirectResponse
    {
        abort_unless($payment->status === 'pending', 422, 'Chỉ có thể duyệt khoản đang chờ.');
        $payment->update(['status' => 'approved', 'approved_at' => now(), 'approved_by' => $request->user()->id]);
        ActivityLogger::log('payments', 'approve', 'Duyệt thanh toán cho '.$payment->personnel?->name, $payment);
        return back()->with('success', 'Đã duyệt thanh toán.');
    }

    public function paid(Request $request, ExcessPayment $payment): RedirectResponse
    {
        abort_unless(in_array($payment->status, ['pending','approved'], true), 422, 'Khoản này không thể chuyển sang đã thanh toán.');
        $payment->update(['status' => 'paid', 'paid_at' => now(), 'paid_by' => $request->user()->id]);
        ActivityLogger::log('payments', 'paid', 'Xác nhận đã thanh toán cho '.$payment->personnel?->name, $payment);
        return back()->with('success', 'Đã xác nhận thanh toán.');
    }

    public function cancel(Request $request, ExcessPayment $payment): RedirectResponse
    {
        $payment->update(['status' => 'cancelled', 'note' => $request->string('note')->toString()]);
        ActivityLogger::log('payments', 'cancel', 'Hủy khoản thanh toán cho '.$payment->personnel?->name, $payment);
        return back()->with('success', 'Đã hủy khoản thanh toán.');
    }
}
