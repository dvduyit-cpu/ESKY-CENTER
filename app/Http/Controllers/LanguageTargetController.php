<?php

namespace App\Http\Controllers;

use App\Models\LanguageMonthlyTargetRecord;
use App\Support\ExcelExporter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LanguageTargetController extends Controller
{
    public function index(Request $request): View
    {
        [$filters,$query]=$this->filteredQuery($request);

        return view('language.targets.index',[
            'filters'=>$filters,
            'items'=>(clone $query)->latest()->paginate(\App\Support\Pagination::perPage())->withQueryString(),
            'totalQuantity'=>(clone $query)->sum('quantity'),
            'totalRevenue'=>(clone $query)->sum('revenue'),
        ]);
    }

    public function export(Request $request)
    {
        [$filters,$query]=$this->filteredQuery($request);
        $rows=$query->get()->map(fn($item)=>[
            $item->payment?->paid_at?->format('d/m/Y'),
            $item->payment?->receipt_code,
            $item->student?->code,
            $item->student?->name,
            $item->lead?->code,
            $item->collaborator?->code,
            $item->collaborator?->name,
            $item->course?->name,
            $item->quantity,
            $item->revenue,
        ]);
        $period=$filters['period']==='quarter'
            ? 'quy-'.$filters['quarter'].'-'.$filters['year']
            : 'thang-'.$filters['month'].'-'.$filters['year'];

        return ExcelExporter::download(
            'doi-chieu-chi-tieu-ctv-'.$period.'.xlsx',
            ['Ngày thanh toán','Phiếu thu','Mã HV','Học viên','Mã khách','Mã CTV','Cộng tác viên','Khóa học','Số lượng','Doanh thu'],
            $rows
        );
    }

    private function filteredQuery(Request $request): array
    {
        $validated=$request->validate([
            'year'=>['nullable','integer','between:2020,2100'],
            'period'=>['nullable','in:month,quarter'],
            'month'=>['nullable','integer','between:1,12'],
            'quarter'=>['nullable','integer','between:1,4'],
            'collaborator'=>['nullable','string','max:255'],
        ]);
        $filters=[
            'year'=>(int)($validated['year']??now()->year),
            'period'=>$validated['period']??'month',
            'month'=>(int)($validated['month']??now()->month),
            'quarter'=>(int)($validated['quarter']??(int)ceil(now()->month / 3)),
            'collaborator'=>trim((string)($validated['collaborator']??'')),
        ];
        $query=LanguageMonthlyTargetRecord::with(['student','lead','collaborator','course','payment'])
            ->whereNotNull('language_collaborator_id')
            ->where('record_year',$filters['year'])
            ->whereIn('language_tuition_payment_id',function($payments){
                $payments->select('payment.id')->from('language_tuition_payments as payment')
                    ->where('payment.receipt_status','confirmed')
                    ->whereNotExists(function($newer){
                        $newer->selectRaw('1')->from('language_tuition_payments as newer_payment')
                            ->whereColumn('newer_payment.language_tuition_charge_id','payment.language_tuition_charge_id')
                            ->where('newer_payment.receipt_status','confirmed')
                            ->where(function($order){
                                $order->whereColumn('newer_payment.paid_at','>','payment.paid_at')
                                    ->orWhere(function($sameTime){
                                        $sameTime->whereColumn('newer_payment.paid_at','=','payment.paid_at')
                                            ->whereColumn('newer_payment.id','>','payment.id');
                                    });
                            });
                    });
            })
            ->whereHas('lead',fn($lead)=>$lead->where('status','registered'))
            ->whereHas('payment',fn($payment)=>$payment
                ->where('receipt_status','confirmed')
                ->whereHas('charge',fn($charge)=>$charge->where('status','paid')));
        if ($filters['period']==='quarter') {
            $firstMonth=($filters['quarter']-1)*3+1;
            $query->whereBetween('record_month',[$firstMonth,$firstMonth+2]);
        } else {
            $query->where('record_month',$filters['month']);
        }
        if ($filters['collaborator']!=='') {
            $search=$filters['collaborator'];
            $query->whereHas('collaborator',fn($builder)=>$builder
                ->where('name','like',"%{$search}%")
                ->orWhere('code','like',"%{$search}%")
                ->orWhere('phone','like',"%{$search}%"));
        }

        return [$filters,$query];
    }
}
