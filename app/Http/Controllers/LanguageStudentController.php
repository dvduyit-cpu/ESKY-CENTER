<?php

namespace App\Http\Controllers;

use App\Models\{LanguageClass, LanguageCourse, LanguageDiscountPolicy, LanguageEnrollment, LanguageGuardian, LanguageStudent};
use App\Support\ActivityLogger;
use App\Support\LanguageStudentSpreadsheet;
use App\Support\LanguageEnrollmentManager;
use App\Support\LanguageStudentMergeService;
use App\Support\TextNormalizer;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LanguageStudentController extends Controller
{
    public function __construct(
        private readonly LanguageEnrollmentManager $enrollmentManager,
        private readonly LanguageStudentMergeService $studentMergeService,
    ) {}

    public function index(Request $request): View
    {
        $duplicateIds = $this->duplicateStudentIds();
        $query = LanguageStudent::with([
            'guardians' => fn ($guardians) => $guardians->orderByDesc('is_primary')->orderBy('id'),
        ])->latest();
        if ($request->filled('q')) {
            $search = (string) $request->string('q')->trim();
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('guardians', fn ($guardians) => $guardians
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"));
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->boolean('duplicates')) {
            $query->whereIn('id', $duplicateIds)->reorder()->orderBy('name')->orderBy('id');
        }
        return view('language.students.index', [
            'items' => $query->paginate(\App\Support\Pagination::perPage())->withQueryString(),
            'totalStudents' => LanguageStudent::count(),
            'duplicateStudentCount' => count($duplicateIds),
            'duplicateIdLookup' => array_fill_keys($duplicateIds, true),
        ]);
    }

    /**
     * @return array<int, int>
     */
    private function duplicateStudentIds(): array
    {
        return collect($this->duplicateStudentGroups())
            ->flatten(1)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<int, LanguageStudent>>
     */
    private function duplicateStudentGroups(): array
    {
        $studentsByName = [];
        $students = LanguageStudent::query()
            ->with('guardians:id,language_student_id,phone')
            ->withCount(['enrollments', 'tuitionCharges'])
            ->get();

        foreach ($students as $student) {
            $name = TextNormalizer::name($student->name);
            if ($name === '') {
                continue;
            }

            $studentsByName[$name][] = $student;
        }

        $groups = [];
        foreach ($studentsByName as $studentsWithSameName) {
            if (count($studentsWithSameName) > 1) {
                usort($studentsWithSameName, fn ($left, $right) => $left->id <=> $right->id);
                $groups[] = $studentsWithSameName;
            }
        }

        usort($groups, fn ($left, $right) => strcasecmp($left[0]->name, $right[0]->name));

        return $groups;
    }

    public function duplicates(): View
    {
        $groups = $this->duplicateStudentGroups();

        return view('language.students.duplicates', [
            'groups' => $groups,
            'duplicateRecordCount' => collect($groups)->flatten(1)->count(),
            'recommendedPrimaryIds' => collect($groups)
                ->map(fn ($group) => $this->preferredPrimary($group)->id)
                ->all(),
            'multipleContactGroupCount' => collect($groups)
                ->filter(fn ($group) => $this->groupContactPhones($group)->count() > 1)
                ->count(),
        ]);
    }

    public function mergeAllDuplicates(Request $request): RedirectResponse
    {
        $request->validate([
            'confirm_all' => ['required', Rule::in(['yes'])],
        ], [
            'confirm_all.required' => 'Vui lòng xác nhận thao tác gộp tất cả.',
            'confirm_all.in' => 'Xác nhận gộp tất cả không hợp lệ.',
        ]);
        $groups = $this->duplicateStudentGroups();
        if ($groups === []) {
            return redirect()->route('language-students.duplicates')
                ->with('warning', 'Không còn nhóm học viên trùng để gộp.');
        }

        [$mergedGroups, $mergedRecords] = DB::transaction(function () use ($groups): array {
            $mergedGroups = 0;
            $mergedRecords = 0;
            foreach ($groups as $group) {
                $primary = $this->preferredPrimary($group);
                $sourceIds = collect($group)
                    ->pluck('id')
                    ->reject(fn ($id) => (int) $id === (int) $primary->id)
                    ->map(fn ($id) => (int) $id)
                    ->values()
                    ->all();
                $mergedRecords += $this->studentMergeService->merge($primary, $sourceIds);
                $mergedGroups++;
            }

            return [$mergedGroups, $mergedRecords];
        });

        ActivityLogger::log(
            'language_students',
            'merge_all',
            "Gộp tất cả {$mergedGroups} nhóm trùng; xóa mềm {$mergedRecords} hồ sơ phụ"
        );

        return redirect()->route('language-students.duplicates')->with(
            'success',
            "Đã gộp {$mergedGroups} nhóm và chuyển dữ liệu từ {$mergedRecords} hồ sơ phụ."
        );
    }

    public function mergeDuplicates(Request $request, LanguageStudent $languageStudent): RedirectResponse
    {
        $data = $request->validate([
            'duplicate_ids' => ['required', 'array', 'min:1'],
            'duplicate_ids.*' => ['required', 'integer', 'distinct', 'exists:language_students,id'],
        ]);
        $sourceIds = collect($data['duplicate_ids'])->map(fn ($id) => (int) $id)->values();
        $group = collect($this->duplicateStudentGroups())->first(
            fn ($items) => collect($items)->contains('id', $languageStudent->id)
        );
        $groupIds = collect($group ?? [])->pluck('id')->map(fn ($id) => (int) $id);
        if (! $group || $sourceIds->contains($languageStudent->id) || $sourceIds->diff($groupIds)->isNotEmpty()) {
            return back()->withErrors([
                'duplicate_ids' => 'Các hồ sơ đã chọn không còn thuộc cùng một nhóm trùng.',
            ]);
        }

        $merged = $this->studentMergeService->merge($languageStudent, $sourceIds->all());
        ActivityLogger::log(
            'language_students',
            'merge',
            "Gộp {$merged} hồ sơ trùng vào học viên {$languageStudent->name}",
            $languageStudent
        );

        return redirect()->route('language-students.duplicates')
            ->with('success', "Đã gộp {$merged} hồ sơ vào {$languageStudent->name}.");
    }

    /**
     * @param array<int, LanguageStudent> $group
     */
    private function preferredPrimary(array $group): LanguageStudent
    {
        usort($group, function (LanguageStudent $left, LanguageStudent $right): int {
            $leftRank = $this->primaryRank($left);
            $rightRank = $this->primaryRank($right);
            foreach (array_keys($leftRank) as $index) {
                if ($leftRank[$index] !== $rightRank[$index]) {
                    return $rightRank[$index] <=> $leftRank[$index];
                }
            }

            return 0;
        });

        return $group[0];
    }

    /**
     * @return array<int, int>
     */
    private function primaryRank(LanguageStudent $student): array
    {
        $completeFields = collect([
            $student->phone, $student->email, $student->date_of_birth, $student->school,
            $student->school_class, $student->address, $student->registered_at,
            $student->official_enrollment_date, $student->language_course_id,
            $student->language_discount_policy_id, $student->note,
        ])->filter(fn ($value) => filled($value))->count();
        $guardianContacts = $student->guardians
            ->filter(fn ($guardian) => filled($guardian->phone) || filled($guardian->email))
            ->count();

        return [
            (int) $student->enrollments_count + (int) $student->tuition_charges_count,
            $completeFields,
            $guardianContacts,
            (int) $student->id,
        ];
    }

    /**
     * @param array<int, LanguageStudent> $group
     */
    private function groupContactPhones(array $group): \Illuminate\Support\Collection
    {
        return collect($group)
            ->flatMap(fn ($student) => [$student->phone, ...$student->guardians->pluck('phone')])
            ->map(fn ($phone) => TextNormalizer::phone($phone))
            ->filter()
            ->unique()
            ->values();
    }

    public function create(): View { return $this->form(new LanguageStudent); }

    public function import(Request $request, LanguageStudentSpreadsheet $spreadsheet): RedirectResponse|StreamedResponse|\Illuminate\Http\JsonResponse
    {
        abort_unless($request->user()?->isRegistrar(), 403, 'Chỉ giáo vụ hoặc quản trị viên được nhập danh sách và xếp lớp học viên.');
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
            'duplicate_action' => ['nullable', Rule::in(['skip', 'overwrite'])],
        ], [
            'file.required' => 'Vui lòng chọn file Excel.',
            'file.mimes' => 'File tải lên phải có định dạng .xlsx hoặc .xls.',
            'file.max' => 'File Excel không được lớn hơn 10 MB.',
            'duplicate_action.in' => 'Cách xử lý hồ sơ trùng không hợp lệ.',
        ]);

        $streamProgress = $request->header('X-Import-Progress') === 'stream';
        if (! class_exists(\ZipArchive::class)) {
            if ($streamProgress) {
                return response()->json([
                    'message' => 'Máy chủ chưa bật PHP ZipArchive nên chưa thể đọc file Excel. Vui lòng liên hệ quản trị viên để bật extension=zip.',
                ], 422);
            }

            return redirect()->route('language-students.index')->withErrors([
                'file' => 'Máy chủ chưa bật PHP ZipArchive nên chưa thể đọc file Excel. Vui lòng liên hệ quản trị viên để bật extension=zip.',
            ]);
        }

        if ($streamProgress) {
            $file = $request->file('file');
            $overwriteExisting = $request->input('duplicate_action') === 'overwrite';

            return response()->stream(function () use ($spreadsheet, $file, $overwriteExisting): void {
                $emit = static function (array $event): void {
                    echo json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";
                    if (ob_get_level() > 0) {
                        @ob_flush();
                    }
                    flush();
                };

                $emit([
                    'type' => 'preparing',
                    'message' => 'Đang đọc và kiểm tra file Excel...',
                ]);

                try {
                    $result = $spreadsheet->import($file, $overwriteExisting, $emit);

                    ActivityLogger::log(
                        'language_students',
                        'import',
                        "Nhập {$result['success']}/{$result['total']} học viên từ Excel; tạo {$result['created']}, ghi đè {$result['updated']}, bỏ qua {$result['skipped']}"
                    );

                    $emit([
                        'type' => 'complete',
                        'message' => "Đã xử lý {$result['total']} dòng: thêm {$result['created']}, ghi đè {$result['updated']}, bỏ qua {$result['skipped']}, lỗi {$result['failed']}.",
                        'result' => $result,
                    ]);
                } catch (\Throwable $exception) {
                    $emit([
                        'type' => 'error',
                        'message' => 'Không thể nhập file: '.$exception->getMessage(),
                    ]);
                }
            }, 200, [
                'Content-Type' => 'application/x-ndjson; charset=UTF-8',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'X-Accel-Buffering' => 'no',
            ]);
        }

        try {
            $result = $spreadsheet->import(
                $request->file('file'),
                $request->input('duplicate_action') === 'overwrite'
            );
        } catch (\Throwable $exception) {
            return back()->withErrors(['file' => 'Không thể nhập file: '.$exception->getMessage()]);
        }

        ActivityLogger::log(
            'language_students',
            'import',
            "Nhập {$result['success']}/{$result['total']} học viên từ Excel; tạo {$result['created']}, ghi đè {$result['updated']}, bỏ qua {$result['skipped']}"
        );

        $redirect = redirect()->route('language-students.index')->with(
            $result['failed'] > 0 || $result['skipped'] > 0 ? 'warning' : 'success',
            "Đã tạo {$result['created']}, ghi đè {$result['updated']}, bỏ qua {$result['skipped']} hồ sơ trùng. Có {$result['failed']} dòng lỗi."
        );
        if ($result['errors']) {
            $redirect->with('student_import_errors', $result['errors']);
        }
        if ($result['warnings']) {
            $redirect->with('student_import_warnings', $result['warnings']);
        }

        return $redirect;
    }

    public function template(LanguageStudentSpreadsheet $spreadsheet): StreamedResponse|RedirectResponse
    {
        if (! class_exists(\ZipArchive::class)) {
            return redirect()->route('language-students.index')->withErrors([
                'file' => 'Máy chủ chưa bật PHP ZipArchive nên chưa thể tạo file Excel mẫu. Vui lòng liên hệ quản trị viên để bật extension=zip.',
            ]);
        }

        return $spreadsheet->template();
    }

    public function show(LanguageStudent $languageStudent): View
    {
        $languageStudent->load([
            'guardians', 'course', 'discountPolicy',
            'enrollments'=>fn($query)=>$query->with([
                'languageClass.program', 'languageClass.level', 'languageClass.teacher',
                'monthlyProgress'=>fn($progress)=>$progress->with('teacher')->orderByDesc('month'),
                'scores'=>fn($scores)=>$scores->with('teacher')->orderByDesc('test_date'),
            ])->orderByDesc('enrolled_at'),
            'tuitionCharges'=>fn($query)=>$query->with(['course','languageClass','payments.collector'])->orderByDesc('created_at'),
            'classTransfers'=>fn($query)=>$query->with(['fromClass','toClass','creator'])->orderByDesc('effective_date'),
        ]);
        return view('language.students.show',['item'=>$languageStudent]);
    }

    public function store(Request $request): RedirectResponse
    {
        $student = DB::transaction(function () use ($request) {
            $student = LanguageStudent::create($this->data($request));
            $this->syncGuardians($request, $student);
            $this->syncCurrentClass($request, $student);
            return $student;
        });
        ActivityLogger::log('language_students','create','Tạo học viên '.$student->name,$student);
        return redirect()->route('language-students.index')->with('success','Đã thêm học viên.');
    }

    public function edit(LanguageStudent $languageStudent): View { return $this->form($languageStudent->load(['guardians','enrollments.languageClass.program','enrollments.languageClass.level','enrollments.monthlyProgress','enrollments.scores'])); }

    public function update(Request $request, LanguageStudent $languageStudent): RedirectResponse
    {
        DB::transaction(function () use ($request, $languageStudent) {
            $languageStudent->update($this->data($request, $languageStudent));
            $this->syncGuardians($request, $languageStudent);
            $this->syncCurrentClass($request, $languageStudent);
        });
        return redirect()->route('language-students.index')->with('success','Đã cập nhật học viên.');
    }

    public function destroy(LanguageStudent $languageStudent): RedirectResponse { $languageStudent->delete(); return back()->with('success','Đã xóa học viên.'); }

    private function form(LanguageStudent $item): View
    {
        return view('language.students.form', compact('item') + [
            'courses'=>LanguageCourse::where(fn($query)=>$query->where('active',1)->orWhere('id', $item->language_course_id))->orderBy('name')->get(),
            'discounts'=>LanguageDiscountPolicy::where(fn($query)=>$query->where('active',1)->orWhere('id', $item->language_discount_policy_id))->orderBy('name')->get(),
            'classes'=>LanguageClass::whereIn('status',['recruiting','upcoming','active'])->with(['program','level'])->orderBy('name')->get(),
        ]);
    }

    private function data(Request $request, ?LanguageStudent $student = null): array
    {
        return $request->validate([
            'code'=>['nullable','max:30',Rule::unique('language_students')->ignore($student)], 'name'=>'required|max:255',
            'gender'=>'nullable|in:male,female,other', 'date_of_birth'=>'nullable|date', 'school'=>'nullable|max:255',
            'school_class'=>'nullable|max:100', 'phone'=>'nullable|max:30', 'email'=>'nullable|email|max:255', 'address'=>'nullable|max:255',
            'registered_at'=>'nullable|date',
            'official_enrollment_date'=>[
                'nullable', 'date',
                Rule::when($request->filled('registered_at'), ['after_or_equal:registered_at']),
            ],
            'source'=>'nullable|max:255', 'language_course_id'=>'nullable|exists:language_courses,id',
            'language_discount_policy_id'=>'nullable|exists:language_discount_policies,id',
            'status'=>['required',Rule::in(['new','placement_test','waiting_class','studying','paused','reserved','completed','dropped'])], 'note'=>'nullable',
        ]);
    }

    private function syncGuardians(Request $request, LanguageStudent $student): void
    {
        $rows = $request->validate([
            'guardians'=>'nullable|array|max:3', 'guardians.*.name'=>'nullable|max:255',
            'guardians.*.relationship'=>'nullable|in:father,mother,guardian', 'guardians.*.phone'=>'nullable|max:30',
            'guardians.*.email'=>'nullable|email|max:255',
        ])['guardians'] ?? [];
        $student->guardians()->delete();
        foreach ($rows as $index => $row) {
            $name = trim((string) ($row['name'] ?? ''));
            $phone = trim((string) ($row['phone'] ?? ''));
            $email = trim((string) ($row['email'] ?? ''));
            if ($name === '' && $phone === '' && $email === '') continue;
            if ($name === '') {
                $name = match ($row['relationship'] ?? 'guardian') {
                    'father' => 'Cha',
                    'mother' => 'Mẹ',
                    default => 'Người giám hộ',
                };
            }
            $student->guardians()->create(array_merge($row, [
                'name'=>$name,
                'phone'=>$phone,
                'email'=>$email !== '' ? $email : null,
                'is_primary'=>$index === 0,
            ]));
        }
    }

    private function syncCurrentClass(Request $request, LanguageStudent $student): void
    {
        $data = $request->validate(['language_class_id'=>'nullable|exists:language_classes,id']);
        if (empty($data['language_class_id'])) return;
        $user = $request->user();
        abort_unless($user?->isRegistrar(), 403, 'Chỉ tài khoản được đánh dấu Giáo vụ hoặc quản trị viên mới được xếp học viên vào lớp.');
        $class = LanguageClass::findOrFail($data['language_class_id']);
        $this->enrollmentManager->enroll(
            $class,
            $student,
            $student->official_enrollment_date ?? $student->registered_at ?? now(),
            $request->user()?->id,
            in_array($student->status, ['paused', 'reserved', 'completed', 'dropped'], true)
                ? $student->status
                : 'studying'
        );
    }
}
