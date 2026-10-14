<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\ImportBatch;
use App\Models\KpiRecord;
use App\Models\Personnel;
use App\Support\ActivityLogger;
use App\Support\Period;
use App\Support\SpreadsheetSupport;
use App\Support\TextNormalizer;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    public function index(Request $request): View
    {
        $query = ImportBatch::with('user')->where('import_type', 'result')->latest();
        if ($request->filled('year')) $query->where('year', $request->integer('year'));
        if ($request->filled('period_type')) $query->where('period_type', $request->string('period_type'));
        return view('imports.index', ['batches' => $query->paginate(\App\Support\Pagination::perPage())->withQueryString()]);
    }

    public function records(Request $request): View
    {
        $batch = $request->filled('batch_id')
            ? ImportBatch::where('import_type', 'result')->findOrFail($request->integer('batch_id')) : null;
        $query = KpiRecord::with(['personnel', 'collaborator', 'course'])
            ->orderByDesc('record_date')
            ->orderByDesc('id');
        if ($batch) $query->where('import_batch_id', $batch->id);

        if ($request->filled('q')) {
            $keyword = $request->string('q')->toString();
            $query->where(fn ($builder) => $builder
                ->where('student_name', 'like', "%{$keyword}%")
                ->orWhere('class_name', 'like', "%{$keyword}%")
                ->orWhere('receipt_no', 'like', "%{$keyword}%"));
        }
        if ($request->filled('year')) $query->where('record_year', $request->integer('year'));
        if ($request->filled('month')) $query->where('record_month', $request->integer('month'));
        if ($request->filled('personnel_id')) $query->where('personnel_id', $request->integer('personnel_id'));
        if ($request->filled('course_id')) $query->where('course_id', $request->integer('course_id'));

        return view('imports.records', [
            'batch' => $batch,
            'records' => $query->paginate(\App\Support\Pagination::perPage())->withQueryString(),
            'personnels' => Personnel::where('type', '!=', 'collaborator')->orderBy('name')->get(),
            'courses' => Course::orderBy('name')->get(),
        ]);
    }

    public function show(Request $request, ImportBatch $batch): View
    {
        abort_unless($batch->import_type === 'result', 404);
        $headers = []; $rows = []; $fileError = null;
        try {
            if (! Storage::exists($batch->stored_path)) {
                throw new \RuntimeException('File gốc không còn trên máy chủ. Bạn vẫn có thể xem dữ liệu hiện đang sử dụng bên dưới.');
            }
            $spreadsheet = IOFactory::load(Storage::path($batch->stored_path));
            $contents = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
            $headers = array_shift($contents) ?? [];
            foreach ($contents as $offset => $values) {
                if (collect($values)->every(fn ($value) => trim((string) $value) === '')) continue;
                $rows[] = ['number' => $offset + 2, 'values' => $values];
            }
            $spreadsheet->disconnectWorksheets();
        } catch (\Throwable $e) {
            report($e);
            $fileError = 'Không thể mở file gốc. '.$e->getMessage();
        }
        $perPage = \App\Support\Pagination::perPage();
        $page = max(1, $request->integer('page', 1));
        return view('imports.show', [
            'batch' => $batch->load('user'), 'headers' => $headers, 'fileError' => $fileError,
            'rows' => new LengthAwarePaginator(array_slice($rows, ($page - 1) * $perPage, $perPage), count($rows), $perPage, $page, [
                'path' => $request->url(), 'query' => $request->query(),
            ]),
            'currentCount' => $batch->records()->count(),
        ]);
    }

    public function download(ImportBatch $batch)
    {
        abort_unless($batch->import_type === 'result' && Storage::exists($batch->stored_path), 404);
        return Storage::download($batch->stored_path, $batch->original_name);
    }

    public function createRecord(): View
    {
        return view('imports.record-form', $this->recordFormData(new KpiRecord()));
    }

    public function storeRecord(Request $request): RedirectResponse
    {
        $data = $this->recordData($request);
        $record = KpiRecord::create($data + ['created_by' => $request->user()->id]);
        ActivityLogger::log('imports', 'create_record', 'Nhập thủ công dữ liệu KPI của '.$record->student_name, $record);

        return redirect()->route('imports.records')->with('success', 'Đã thêm dữ liệu KPI.');
    }

    public function editRecord(KpiRecord $record): View
    {
        return view('imports.record-form', $this->recordFormData($record));
    }

    public function updateRecord(Request $request, KpiRecord $record): RedirectResponse
    {
        $before = $record->toArray();
        $record->update($this->recordData($request));
        ActivityLogger::log('imports', 'update_record', 'Cập nhật dữ liệu KPI của '.$record->student_name, $record, $before, $record->fresh()->toArray());

        return redirect()->route('imports.records')->with('success', 'Đã cập nhật dữ liệu KPI.');
    }

    public function destroyRecord(KpiRecord $record): RedirectResponse
    {
        $record->delete();
        ActivityLogger::log('imports', 'delete_record', 'Xóa mềm dữ liệu KPI của '.$record->student_name, $record);

        return back()->with('success', 'Đã xóa mềm dòng dữ liệu KPI.');
    }

    public function bulkDestroyRecords(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required','array','min:1'],
            'ids.*' => ['integer'],
            'delete_type' => ['required', Rule::in(['soft','force'])],
        ]);
        $force = $data['delete_type'] === 'force';
        abort_if($force && ! $request->user()->isAdmin(), 403, 'Chỉ quản trị viên được xóa vĩnh viễn dữ liệu KPI.');

        $records = KpiRecord::withTrashed()->whereKey($data['ids'])->get();
        foreach ($records as $record) {
            $force ? $record->forceDelete() : $record->delete();
        }
        ActivityLogger::log(
            'imports',
            $force ? 'bulk_force_delete_records' : 'bulk_delete_records',
            ($force ? 'Xóa vĩnh viễn ' : 'Xóa mềm ').$records->count().' dòng dữ liệu KPI'
        );

        return back()->with('success', 'Đã '.($force ? 'xóa vĩnh viễn ' : 'xóa mềm ').$records->count().' dòng dữ liệu KPI.');
    }

    private function recordFormData(KpiRecord $record): array
    {
        return [
            'record' => $record,
            'personnels' => Personnel::where('type', '!=', 'collaborator')
                ->where(fn ($query) => $query->where('active', true)->orWhere('id', $record->personnel_id))
                ->orderBy('name')->get(),
            'collaborators' => Personnel::where('type', 'collaborator')
                ->where(fn ($query) => $query->where('active', true)->orWhere('id', $record->collaborator_id))
                ->orderBy('name')->get(),
            'courses' => Course::where(fn ($query) => $query->where('active', true)->orWhere('id', $record->course_id))
                ->orderBy('name')->get(),
        ];
    }

    private function recordData(Request $request): array
    {
        $data = $request->validate([
            'student_name' => ['required','string','max:200'],
            'personnel_id' => ['required','integer','exists:personnels,id'],
            'collaborator_id' => ['nullable','integer','exists:personnels,id'],
            'course_id' => ['required','integer','exists:courses,id'],
            'class_name' => ['nullable','string','max:200'],
            'revenue' => ['nullable','numeric','min:0'],
            'receipt_no' => ['nullable','string','max:100'],
            'record_date' => ['required','date'],
            'raw_quantity' => ['required','numeric','min:0'],
            'note' => ['nullable','string'],
        ]);
        $course = Course::findOrFail($data['course_id']);
        $date = Carbon::parse($data['record_date']);
        $data['revenue'] = $data['revenue'] ?? 0;
        $data['record_year'] = $date->year;
        $data['record_quarter'] = Period::quarterOfMonth($date->month);
        $data['record_month'] = $date->month;
        $data['conversion_quantity'] = $course->conversion_quantity;
        $data['conversion_kpi'] = $course->conversion_kpi;
        $data['conversion_mode'] = $course->conversion_mode;

        return $data;
    }

    public function create(): View
    {
        return view('imports.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'period_type' => ['required', Rule::in(['month','quarter'])],
            'year' => ['required','integer','min:2020','max:2100'],
            'quarter' => ['nullable','integer','min:1','max:4'],
            'month' => ['nullable','integer','min:1','max:12'],
            'file' => ['required','file','mimes:xlsx,xls,csv','max:20480'],
        ]);
        if ($data['period_type'] === 'month' && empty($data['month'])) return back()->withErrors(['month' => 'Vui lòng chọn tháng.']);
        if ($data['period_type'] === 'quarter' && empty($data['quarter'])) return back()->withErrors(['quarter' => 'Vui lòng chọn quý.']);

        $file = $request->file('file');
        if (! SpreadsheetSupport::canReadUpload($file)) {
            return back()->withErrors([
                'file' => SpreadsheetSupport::missingZipImportMessage(
                    SpreadsheetSupport::uploadedExtension($file)
                ),
            ]);
        }

        $hash = hash_file('sha256', $file->getRealPath());

        $storedName = (string) \Illuminate\Support\Str::uuid().'.'.$file->getClientOriginalExtension();
        $storedPath = $file->storeAs('imports', $storedName);
        $batch = ImportBatch::create([
            'import_type' => 'result',
            'period_type' => $data['period_type'],
            'year' => $data['year'],
            'quarter' => $data['period_type'] === 'month' ? Period::quarterOfMonth((int) $data['month']) : (int) $data['quarter'],
            'month' => $data['period_type'] === 'month' ? (int) $data['month'] : 0,
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'file_hash' => $hash,
            'status' => 'processing',
            'imported_by' => $request->user()->id,
        ]);

        try {
            $sheet = IOFactory::load($file->getRealPath())->getActiveSheet();
            // Read Excel dates as serial values rather than locale-dependent display strings.
            $rows = $sheet->toArray(null, true, false, false);
            if (count($rows) < 2) throw new \RuntimeException('File không có dữ liệu.');

            $headers = [];
            foreach ($rows[0] as $index => $header) $headers[TextNormalizer::header((string) $header)] = $index;
            foreach (['HO TEN HOC VIEN','NHAN SU GHI NHAN','KHOA HOC','THUC THU'] as $required) {
                if (! array_key_exists($required, $headers)) throw new \RuntimeException('Thiếu cột '.$required.'.');
            }

            $success = 0; $updated = 0; $errors = []; $totalRevenue = 0.0; $totalRows = 0;
            DB::transaction(function () use ($rows, $headers, $batch, $data, $request, &$success, &$updated, &$errors, &$totalRevenue, &$totalRows): void {
                foreach (array_slice($rows, 1) as $offset => $row) {
                    $rowNo = $offset + 2;
                    $student = trim((string) ($row[$headers['HO TEN HOC VIEN']] ?? ''));
                    $personName = trim((string) ($row[$headers['NHAN SU GHI NHAN']] ?? ''));
                    $courseName = trim((string) ($row[$headers['KHOA HOC']] ?? ''));
                    if ($student === '' && $personName === '' && $courseName === '') continue;
                    $totalRows++;
                    try {
                        if ($student === '' || $personName === '' || $courseName === '') {
                            throw new \RuntimeException('Thiếu học viên, nhân sự ghi nhận hoặc khóa học.');
                        }
                        $personnel = $this->findOrCreatePersonnel($personName, 'employee');
                        $collaboratorName = trim((string) ($row[$headers['CONG TAC VIEN'] ?? -1] ?? ''));
                        $collaborator = $collaboratorName !== '' ? $this->findOrCreatePersonnel($collaboratorName, 'collaborator') : null;
                        $course = $this->findOrCreateCourse($courseName);
                        $recordDate = $this->parseDate($row[$headers['NGAY GHI NHAN'] ?? ($headers['NGAY THU'] ?? -1)] ?? null, $data);
                        if ((int) $recordDate->year !== (int) $data['year']) throw new \RuntimeException('Ngày ghi nhận không thuộc năm đã chọn.');
                        if ($data['period_type'] === 'month' && (int) $recordDate->month !== (int) $data['month']) throw new \RuntimeException('Ngày ghi nhận không thuộc tháng đã chọn.');
                        if ($data['period_type'] === 'quarter' && Period::quarterOfMonth((int) $recordDate->month) !== (int) $data['quarter']) throw new \RuntimeException('Ngày ghi nhận không thuộc quý đã chọn.');

                        $revenue = $this->number($row[$headers['THUC THU']] ?? 0);
                        $quantity = max($this->number($row[$headers['SO LUONG'] ?? -1] ?? 1), 0);
                        $attributes = [
                            'import_batch_id' => $batch->id,
                            'source_row_no' => $rowNo,
                            'personnel_id' => $personnel->id,
                            'collaborator_id' => $collaborator?->id,
                            'course_id' => $course->id,
                            'student_name' => $student,
                            'class_name' => trim((string) ($row[$headers['LOP DANG KY'] ?? -1] ?? '')),
                            'raw_quantity' => $quantity,
                            'revenue' => $revenue,
                            'receipt_no' => trim((string) ($row[$headers['SO PHIEU THU'] ?? -1] ?? '')),
                            'record_date' => $recordDate->toDateString(),
                            'record_year' => $recordDate->year,
                            'record_quarter' => Period::quarterOfMonth($recordDate->month),
                            'record_month' => $recordDate->month,
                            'conversion_quantity' => $course->conversion_quantity,
                            'conversion_kpi' => $course->conversion_kpi,
                            'conversion_mode' => $course->conversion_mode,
                            'note' => trim((string) ($row[$headers['GHI CHU'] ?? -1] ?? '')),
                            'created_by' => $request->user()->id,
                        ];
                        $matches = KpiRecord::where('personnel_id', $personnel->id)
                            ->where('course_id', $course->id)
                            ->whereDate('record_date', $recordDate->toDateString())
                            ->lockForUpdate()->get()->filter(fn (KpiRecord $record) =>
                                TextNormalizer::exactName($record->student_name) === TextNormalizer::exactName($student)
                                && TextNormalizer::exactName($record->class_name) === TextNormalizer::exactName($attributes['class_name'])
                                && mb_strtolower(trim((string) $record->receipt_no)) === mb_strtolower($attributes['receipt_no'])
                            );
                        if ($existing = $matches->first()) {
                            $existing->update($attributes);
                            // Older imports may already contain several copies of the same result.
                            foreach ($matches->skip(1) as $duplicate) $duplicate->delete();
                            $updated++;
                        } else {
                            KpiRecord::create($attributes);
                        }
                        $success++;
                        $totalRevenue += $revenue;
                    } catch (\Throwable $e) {
                        $context = implode(' | ', array_filter([
                            $student !== '' ? 'Học viên: '.$student : null,
                            $personName !== '' ? 'Nhân sự: '.$personName : null,
                            $courseName !== '' ? 'Khóa học: '.$courseName : null,
                        ]));
                        $errors[] = "Dòng {$rowNo}".($context ? " ({$context})" : '').': '.$e->getMessage();
                    }
                }
            });

            $batch->update([
                'status' => 'completed', 'total_rows' => $totalRows, 'success_rows' => $success,
                'error_rows' => count($errors), 'total_revenue' => $totalRevenue,
                'error_details' => $errors,
            ]);
            ActivityLogger::log('imports', 'import_results', "Nhập {$success} dòng kết quả", $batch);
            $redirect = redirect()->route('imports.index')
                ->with('success', "Đã nhập {$success}/{$totalRows} dòng: ".($success - $updated)." dòng mới, {$updated} dòng trùng đã gộp và ghi đè bằng dữ liệu mới. Có ".count($errors).' dòng lỗi.');
            if ($errors) $redirect->with('import_errors', $errors);

            return $redirect;
        } catch (\Throwable $e) {
            $batch->update(['status' => 'failed', 'error_details' => [$e->getMessage()]]);
            return redirect()->route('imports.index')->withErrors(['file' => 'Nhập file thất bại: '.$e->getMessage()]);
        }
    }

    public function template(): StreamedResponse
    {
        $headers = ['STT','HỌ TÊN HỌC VIÊN','NHÂN SỰ GHI NHẬN','CỘNG TÁC VIÊN','KHÓA HỌC','LỚP ĐĂNG KÝ','THỰC THU','SỐ PHIẾU THU','NGÀY GHI NHẬN','SỐ LƯỢNG','GHI CHÚ'];
        $sampleRow = [1,'Nguyễn Văn A','Giáo viên mẫu','','Chứng nhận B1','B1-TVU',1500000,'PT001',now()->format('d/m/Y'),1,'B1: đủ 2 lượt tính 1 KPI'];

        if (! SpreadsheetSupport::hasZipArchive()) {
            return SpreadsheetSupport::streamCsvDownload(
                'mau-nhap-ket-qua-kpi.csv',
                $headers,
                [$sampleRow]
            );
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('DU LIEU KPI');
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray($sampleRow, null, 'A2');
        $sheet->getStyle('A1:K1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle('A1:K1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF0F766E');
        foreach (range('A','K') as $column) $sheet->getColumnDimension($column)->setAutoSize(true);
        $sheet->freezePane('A2');
        return response()->streamDownload(fn () => (new Xlsx($spreadsheet))->save('php://output'), 'mau-nhap-ket-qua-kpi.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function findOrCreatePersonnel(string $name, string $type): Personnel
    {
        $normalized = TextNormalizer::name($name);
        $personnel = Personnel::where('normalized_name', $normalized)->first();
        if ($personnel) return $personnel;
        return Personnel::create([
            'name' => $name, 'normalized_name' => $normalized, 'type' => $type,
            'position' => $type === 'collaborator' ? 'Cộng tác viên' : 'Nhân viên',
            'default_kpi' => $type === 'employee' ? 55 : 0,
            'has_kpi' => $type !== 'collaborator', 'payment_type' => 'none',
            'payment_value' => 0, 'active' => true,
        ]);
    }

    private function findOrCreateCourse(string $name): Course
    {
        $normalized = TextNormalizer::name($name);
        return Course::firstOrCreate(['normalized_name' => $normalized], [
            'name' => $name, 'conversion_quantity' => 1, 'conversion_kpi' => 1,
            'conversion_mode' => 'proportional', 'default_excess_rate' => 0, 'active' => true,
        ]);
    }

    private function parseDate(mixed $value, array $period): Carbon
    {
        if ($value instanceof \DateTimeInterface) return Carbon::instance($value);
        if (is_numeric($value) && (float) $value > 1000) return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
        if (is_string($value) && trim($value) !== '') {
            $text = trim($value);
            if (preg_match('/^(\d{1,2})([\/.-])(\d{1,2})\2(\d{4})$/', $text, $parts)) {
                [$day, $month, $year] = [(int) $parts[1], (int) $parts[3], (int) $parts[4]];
            } elseif (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $text, $parts)) {
                [$day, $month, $year] = [(int) $parts[3], (int) $parts[2], (int) $parts[1]];
            } else {
                throw new \RuntimeException('Ngày ghi nhận không hợp lệ: '.$text.'. Vui lòng nhập ngày/tháng/năm, ví dụ 14/10/2026.');
            }
            if (! checkdate($month, $day, $year)) {
                throw new \RuntimeException('Ngày ghi nhận không hợp lệ: '.$text.'. Vui lòng kiểm tra ngày/tháng/năm.');
            }
            return Carbon::create($year, $month, $day)->startOfDay();
        }
        $month = $period['period_type'] === 'month' ? (int) $period['month'] : (((int) $period['quarter'] - 1) * 3 + 1);
        return Carbon::create((int) $period['year'], $month, 1);
    }

    private function number(mixed $value): float
    {
        if (is_numeric($value)) return (float) $value;
        $clean = preg_replace('/[^0-9,.-]/', '', trim((string) $value));
        if ($clean === '' || $clean === '-') return 0;

        $lastComma = strrpos($clean, ',');
        $lastDot = strrpos($clean, '.');
        if ($lastComma !== false && $lastDot !== false) {
            $decimalPos = max($lastComma, $lastDot);
            $decimalDigits = strlen($clean) - $decimalPos - 1;
            $decimalSeparator = $clean[$decimalPos];
            $thousandSeparator = $decimalSeparator === ',' ? '.' : ',';
            $clean = str_replace($thousandSeparator, '', $clean);
            $clean = $decimalDigits <= 2 ? str_replace(',', '.', $clean) : str_replace([',','.'], '', $clean);
        } elseif (preg_match('/^-?\d+[,.]\d{1,2}$/', $clean)) {
            $clean = str_replace(',', '.', $clean);
        } else {
            $clean = str_replace([',','.'], '', $clean);
        }
        return (float) $clean;
    }
}
