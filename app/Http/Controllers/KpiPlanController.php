<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\KpiPlan;
use App\Models\KpiTarget;
use App\Models\Personnel;
use App\Support\ActivityLogger;
use App\Support\Period;
use App\Support\TextNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KpiPlanController extends Controller
{
    public function index(): View
    {
        return view('kpis.index', [
            'plans' => KpiPlan::withCount('targets')->orderByDesc('year')->paginate(20)->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('kpis.plan-form', ['plan' => new KpiPlan()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'year' => ['required','integer','min:2020','max:2100','unique:kpi_plans,year'],
            'name' => ['required','string','max:200'],
            'status' => ['required', Rule::in(['draft','active','closed'])],
            'settlement_scope' => ['required', Rule::in(['month','quarter','year'])],
            'note' => ['nullable','string'],
        ]);
        $plan = KpiPlan::create($data + ['created_by' => $request->user()->id]);
        ActivityLogger::log('kpis', 'create_plan', 'Tạo kế hoạch chỉ tiêu năm '.$plan->year, $plan);
        return redirect()->route('kpis.show', $plan)->with('success', 'Đã tạo kế hoạch chỉ tiêu năm.');
    }

    public function edit(KpiPlan $plan): View
    {
        return view('kpis.plan-form', compact('plan'));
    }

    public function update(Request $request, KpiPlan $plan): RedirectResponse
    {
        $data = $request->validate([
            'year' => ['required','integer','min:2020','max:2100', Rule::unique('kpi_plans')->ignore($plan->id)],
            'name' => ['required','string','max:200'],
            'status' => ['required', Rule::in(['draft','active','closed'])],
            'settlement_scope' => ['required', Rule::in(['month','quarter','year'])],
            'note' => ['nullable','string'],
        ]);
        $before = $plan->toArray();
        $plan->update($data);
        ActivityLogger::log('kpis', 'update_plan', 'Cập nhật kế hoạch chỉ tiêu năm '.$plan->year, $plan, $before, $plan->fresh()->toArray());
        return redirect()->route('kpis.show', $plan)->with('success', 'Đã cập nhật kế hoạch.');
    }

    public function destroyPlan(KpiPlan $plan): RedirectResponse
    {
        $year = $plan->year;
        $plan->delete();
        ActivityLogger::log('kpis', 'delete_plan', 'Xóa kế hoạch chỉ tiêu năm '.$year);

        return redirect()->route('kpis.index')->with('success', 'Đã xóa kế hoạch chỉ tiêu năm '.$year.' và các dòng chỉ tiêu liên quan.');
    }

    public function bulkDestroyPlans(Request $request): RedirectResponse
    {
        $ids = $request->validate(['ids' => ['required','array','min:1'], 'ids.*' => ['integer']])['ids'];
        $plans = KpiPlan::whereKey($ids)->get();
        $count = $plans->count();
        foreach ($plans as $plan) $plan->delete();
        ActivityLogger::log('kpis', 'bulk_delete_plans', 'Xóa '.$count.' kế hoạch chỉ tiêu');

        return back()->with('success', 'Đã xóa '.$count.' kế hoạch và các dòng chỉ tiêu liên quan.');
    }

    public function show(Request $request, KpiPlan $plan): View
    {
        $query = $plan->targets()->with(['personnel','course'])->latest();
        if ($request->filled('period_type')) $query->where('period_type', $request->string('period_type'));
        if ($request->filled('quarter')) $query->where('quarter', $request->integer('quarter'));
        if ($request->filled('month')) $query->where('month', $request->integer('month'));
        if ($request->filled('personnel_id')) $query->where('personnel_id', $request->integer('personnel_id'));
        if ($request->filled('course_id')) $query->where('course_id', $request->integer('course_id'));

        return view('kpis.show', [
            'plan' => $plan,
            'targets' => $query->paginate(25)->withQueryString(),
            'personnels' => Personnel::where('active', true)->where('type', '!=', 'collaborator')->orderBy('name')->get(),
            'courses' => Course::where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function createTarget(KpiPlan $plan): View
    {
        return view('kpis.target-form', [
            'plan' => $plan,
            'target' => new KpiTarget(),
            'personnels' => Personnel::where('active', true)->where('type', '!=', 'collaborator')->orderBy('name')->get(),
            'courses' => Course::where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function storeTarget(Request $request, KpiPlan $plan): RedirectResponse
    {
        $data = $this->targetData($request);
        $this->guardDuplicate($plan, $data);
        $target = $plan->targets()->create($data + ['created_by' => $request->user()->id]);
        ActivityLogger::log('kpis', 'create_target', 'Giao chỉ tiêu cho '.$target->personnel->name, $target);
        return redirect()->route('kpis.show', $plan)->with('success', 'Đã giao chỉ tiêu.');
    }

    public function editTarget(KpiPlan $plan, KpiTarget $target): View
    {
        abort_unless($target->plan_id === $plan->id, 404);
        return view('kpis.target-form', [
            'plan' => $plan,
            'target' => $target,
            'personnels' => Personnel::where('active', true)->where('type', '!=', 'collaborator')->orderBy('name')->get(),
            'courses' => Course::where('active', true)->orderBy('name')->get(),
        ]);
    }

    public function updateTarget(Request $request, KpiPlan $plan, KpiTarget $target): RedirectResponse
    {
        abort_unless($target->plan_id === $plan->id, 404);
        $data = $this->targetData($request);
        $this->guardDuplicate($plan, $data, $target);
        $before = $target->toArray();
        $target->update($data);
        ActivityLogger::log('kpis', 'update_target', 'Cập nhật chỉ tiêu '.$target->personnel->name, $target, $before, $target->fresh()->toArray());
        return redirect()->route('kpis.show', $plan)->with('success', 'Đã cập nhật chỉ tiêu.');
    }

    public function destroyTarget(KpiPlan $plan, KpiTarget $target): RedirectResponse
    {
        abort_unless($target->plan_id === $plan->id, 404);
        $target->delete();
        ActivityLogger::log('kpis', 'delete_target', 'Xóa mềm chỉ tiêu', $target);
        return back()->with('success', 'Đã xóa chỉ tiêu.');
    }

    public function bulkDestroyTargets(Request $request, KpiPlan $plan): RedirectResponse
    {
        $data = $request->validate(['ids' => ['required','array','min:1'], 'ids.*' => ['integer'], 'delete_type' => ['required', Rule::in(['soft','force'])]]);
        $force = $data['delete_type'] === 'force';
        abort_if($force && ! $request->user()->isAdmin(), 403, 'Chỉ quản trị viên được xóa vĩnh viễn.');
        $targets = $plan->targets()->withTrashed()->whereKey($data['ids'])->get();
        $deleted = 0;
        foreach ($targets as $target) {
            try { $force ? $target->forceDelete() : $target->delete(); $deleted++; } catch (QueryException) {}
        }
        ActivityLogger::log('kpis', $force ? 'bulk_force_delete_targets' : 'bulk_delete_targets', ($force ? 'Xóa vĩnh viễn ' : 'Xóa mềm ').$deleted.' chỉ tiêu', $plan);

        return back()->with('success', 'Đã '.($force ? 'xóa vĩnh viễn ' : 'xóa mềm ').$deleted.' chỉ tiêu.');
    }

    public function importForm(KpiPlan $plan): View
    {
        return view('kpis.import', compact('plan'));
    }

    public function import(Request $request, KpiPlan $plan): RedirectResponse
    {
        $data = $request->validate([
            'period_type' => ['required', Rule::in(['month','quarter'])],
            'quarter' => ['nullable','integer','min:1','max:4'],
            'month' => ['nullable','integer','min:1','max:12'],
            'file' => ['required','file','mimes:xlsx,xls,csv','max:10240'],
        ]);
        $this->validatePeriod($data);

        $sheet = IOFactory::load($request->file('file')->getRealPath())->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);
        if (count($rows) < 2) return back()->withErrors(['file' => 'File không có dữ liệu.']);

        $headers = [];
        foreach ($rows[0] as $index => $header) $headers[TextNormalizer::header((string) $header)] = $index;
        foreach (['HO TEN NHAN SU','CHI TIEU'] as $required) {
            if (! array_key_exists($required, $headers)) return back()->withErrors(['file' => 'Thiếu cột '.$required.'.']);
        }

        $success = 0; $errors = [];
        DB::transaction(function () use ($rows, $headers, $plan, $data, $request, &$success, &$errors): void {
            foreach (array_slice($rows, 1) as $offset => $row) {
                $rowNo = $offset + 2;
                $personName = trim((string) ($row[$headers['HO TEN NHAN SU']] ?? ''));
                if ($personName === '') continue;
                try {
                    $personnel = Personnel::where('normalized_name', TextNormalizer::name($personName))->first();
                    if (! $personnel) throw new \RuntimeException('Không tìm thấy nhân sự: '.$personName);
                    $mandatoryText = TextNormalizer::header((string) ($row[$headers['BAT BUOC'] ?? -1] ?? 'CO'));
                    $targetData = [
                        'personnel_id' => $personnel->id,
                        'course_id' => null,
                        'period_type' => $data['period_type'],
                        'quarter' => $data['period_type'] === 'month' ? Period::quarterOfMonth((int) $data['month']) : (int) $data['quarter'],
                        'month' => $data['period_type'] === 'month' ? (int) $data['month'] : 0,
                        'target_quantity' => (float) ($row[$headers['CHI TIEU']] ?? 0),
                        'target_revenue' => (float) ($row[$headers['DOANH THU MUC TIEU'] ?? -1] ?? 0),
                        'is_mandatory' => in_array($mandatoryText, ['CO','YES','1','BAT BUOC'], true),
                        'excess_payment_per_kpi' => (float) ($row[$headers['MUC THANH TOAN KPI VUOT'] ?? -1] ?? 0),
                        'note' => (string) ($row[$headers['GHI CHU'] ?? -1] ?? ''),
                        'created_by' => $request->user()->id,
                    ];
                    KpiTarget::updateOrCreate([
                        'plan_id' => $plan->id,
                        'personnel_id' => $personnel->id,
                        'course_id' => null,
                        'period_type' => $targetData['period_type'],
                        'quarter' => $targetData['quarter'],
                        'month' => $targetData['month'],
                    ], $targetData);
                    $success++;
                } catch (\Throwable $e) {
                    $errors[] = "Dòng {$rowNo}: ".$e->getMessage();
                }
            }
        });
        ActivityLogger::log('kpis', 'import_targets', "Nhập {$success} chỉ tiêu vào năm {$plan->year}", $plan);
        return redirect()->route('kpis.show', $plan)->with('success', "Đã nhập {$success} dòng chỉ tiêu.".(count($errors) ? ' Có '.count($errors).' dòng lỗi: '.implode(' | ', array_slice($errors, 0, 3)) : ''));
    }

    public function template(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('NHAP CHI TIEU');
        $headers = ['STT','HỌ TÊN NHÂN SỰ','CHỈ TIÊU','DOANH THU MỤC TIÊU','BẮT BUỘC','MỨC THANH TOÁN KPI VƯỢT','GHI CHÚ'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray([1,'Giáo viên mẫu',42,0,'Có',100000,'Chỉ tiêu tổng'], null, 'A2');
        $sheet->getStyle('A1:G1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:G1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E3A8A');
        foreach (range('A','G') as $column) $sheet->getColumnDimension($column)->setAutoSize(true);
        $sheet->freezePane('A2');
        return response()->streamDownload(fn () => (new Xlsx($spreadsheet))->save('php://output'), 'mau-nhap-chi-tieu.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function targetData(Request $request): array
    {
        $data = $request->validate([
            'personnel_id' => ['required','exists:personnels,id'],
            'course_id' => ['nullable','exists:courses,id'],
            'period_type' => ['required', Rule::in(['month','quarter','year'])],
            'quarter' => ['nullable','integer','min:1','max:4'],
            'month' => ['nullable','integer','min:1','max:12'],
            'target_quantity' => ['required','numeric','min:0'],
            'target_revenue' => ['nullable','numeric','min:0'],
            'excess_payment_per_kpi' => ['nullable','numeric','min:0'],
            'note' => ['nullable','string'],
        ]);
        $data['is_mandatory'] = $request->boolean('is_mandatory');
        $data['course_id'] = null;
        $data['target_revenue'] = $data['target_revenue'] ?? 0;
        $data['excess_payment_per_kpi'] = $data['excess_payment_per_kpi'] ?? 0;
        if ($data['period_type'] === 'month') {
            $data['quarter'] = Period::quarterOfMonth((int) $data['month']);
        } elseif ($data['period_type'] === 'quarter') {
            $data['month'] = 0;
        } else {
            $data['quarter'] = 0; $data['month'] = 0;
        }
        $this->validatePeriod($data);
        return $data;
    }

    private function validatePeriod(array $data): void
    {
        if ($data['period_type'] === 'month' && empty($data['month'])) abort(422, 'Vui lòng chọn tháng.');
        if ($data['period_type'] === 'quarter' && empty($data['quarter'])) abort(422, 'Vui lòng chọn quý.');
    }

    private function guardDuplicate(KpiPlan $plan, array $data, ?KpiTarget $ignore = null): void
    {
        $query = KpiTarget::where('plan_id', $plan->id)
            ->where('personnel_id', $data['personnel_id'])->whereNull('course_id')
            ->where('period_type', $data['period_type'])->where('quarter', $data['quarter'])->where('month', $data['month']);
        if ($ignore) $query->whereKeyNot($ignore->id);
        abort_if($query->exists(), 422, 'Nhân sự đã có chỉ tiêu tổng cho kỳ này.');
    }
}
