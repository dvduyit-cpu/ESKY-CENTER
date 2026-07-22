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
        [$year,$month,$query]=$this->filteredQuery($request);
        return view('language.targets.index',[
            'year'=>$year,'month'=>$month,
            'items'=>(clone $query)->latest()->paginate(\App\Support\Pagination::perPage())->withQueryString(),
            'totalQuantity'=>(clone $query)->sum('quantity'),
            'totalRevenue'=>(clone $query)->sum('revenue'),
        ]);
    }

    public function export(Request $request)
    {
        [$year,$month,$query]=$this->filteredQuery($request);
        $rows=$query->get()->map(fn($item)=>[$item->payment?->paid_at?->format('d/m/Y'),$item->student->code,$item->student->name,$item->lead?->code,$item->collaborator?->name,$item->course->name,$item->quantity,$item->revenue,$item->payment?->receipt_code]);
        return ExcelExporter::download("chi-tieu-trung-tam-$year-$month.xlsx",['Ngày ghi nhận','Mã HV','Học viên','Mã khách','CTV','Khóa học','Số lượng','Doanh thu','Phiếu thu'],$rows);
    }

    private function filteredQuery(Request $request): array
    {
        $year=$request->integer('year',now()->year); $month=$request->integer('month',now()->month);
        $query=LanguageMonthlyTargetRecord::with(['student','lead','collaborator','course','payment'])->where('record_year',$year)->where('record_month',$month);
        if ($request->filled('collaborator')) {
            $search=trim($request->input('collaborator'));
            $query->whereHas('collaborator',fn($builder)=>$builder->where('name','like',"%{$search}%")->orWhere('code','like',"%{$search}%")->orWhere('phone','like',"%{$search}%"));
        }
        return [$year,$month,$query];
    }
}
