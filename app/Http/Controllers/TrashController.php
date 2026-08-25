<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Course;
use App\Models\KpiRecord;
use App\Models\KpiTarget;
use App\Models\LanguageClass;
use App\Models\LanguageCollaborator;
use App\Models\LanguageCourse;
use App\Models\LanguageDiscountPolicy;
use App\Models\LanguageLead;
use App\Models\LanguageLevel;
use App\Models\LanguageProgram;
use App\Models\LanguageStudent;
use App\Models\Personnel;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TrashController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);

        $type = $request->string('type')->toString();
        $search = trim($request->string('q')->toString());
        $configs = $this->trashConfigs();

        if ($type !== '' && ! array_key_exists($type, $configs)) {
            abort(404);
        }

        $items = collect($configs)
            ->filter(fn (array $config, string $key) => $type === '' || $key === $type)
            ->flatMap(fn (array $config, string $key) => $this->fetchItemsForType($key, $config, $search))
            ->sortByDesc(fn (array $item) => optional($item['deleted_at'])->getTimestamp() ?? 0)
            ->values();

        $paginated = $this->paginateCollection($items, $request);
        $deleteLogs = $this->deleteLogsForPage(collect($paginated->items()));

        return view('admin.trash', [
            'items' => $paginated,
            'types' => collect($configs)->map(fn (array $config, string $key) => [
                'key' => $key,
                'label' => $config['label'],
            ])->values(),
            'deleteLogs' => $deleteLogs,
        ]);
    }

    public function restore(Request $request, string $type, int $id): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $config = $this->trashConfigs()[$type] ?? null;
        abort_unless($config, 404);

        /** @var class-string<Model> $modelClass */
        $modelClass = $config['model'];
        $item = $modelClass::onlyTrashed()->findOrFail($id);
        $before = $item->toArray();
        $item->restore();

        ActivityLogger::log(
            'trash',
            'restore',
            'Khôi phục '.$config['singular'].' '.$this->displayName($item, $config),
            $item,
            $before,
            $item->fresh()?->toArray()
        );

        return back()->with('success', 'Đã khôi phục '.$config['singular'].'.');
    }

    /**
     * @return array<string, array{label:string,singular:string,model:class-string<Model>,search:string[],title:string,subtitle:string|null,module:string}>
     */
    private function trashConfigs(): array
    {
        return [
            'users' => [
                'label' => 'Tài khoản',
                'singular' => 'tài khoản',
                'model' => User::class,
                'search' => ['name', 'email'],
                'title' => 'name',
                'subtitle' => 'email',
                'module' => 'Quản trị hệ thống',
            ],
            'personnels' => [
                'label' => 'Nhân sự',
                'singular' => 'nhân sự',
                'model' => Personnel::class,
                'search' => ['name', 'code', 'email'],
                'title' => 'name',
                'subtitle' => 'code',
                'module' => 'Quản trị hệ thống',
            ],
            'courses' => [
                'label' => 'Khóa học KPI',
                'singular' => 'khóa học KPI',
                'model' => Course::class,
                'search' => ['name', 'code', 'category'],
                'title' => 'name',
                'subtitle' => 'code',
                'module' => 'KPI & báo cáo',
            ],
            'language_students' => [
                'label' => 'Học viên',
                'singular' => 'học viên',
                'model' => LanguageStudent::class,
                'search' => ['name', 'code', 'phone', 'email'],
                'title' => 'name',
                'subtitle' => 'code',
                'module' => 'Học viên',
            ],
            'language_leads' => [
                'label' => 'Học viên tiềm năng',
                'singular' => 'học viên tiềm năng',
                'model' => LanguageLead::class,
                'search' => ['name', 'code', 'phone', 'email'],
                'title' => 'name',
                'subtitle' => 'code',
                'module' => 'Tư vấn & tuyển sinh',
            ],
            'language_programs' => [
                'label' => 'Chương trình',
                'singular' => 'chương trình',
                'model' => LanguageProgram::class,
                'search' => ['name', 'code', 'audience'],
                'title' => 'name',
                'subtitle' => 'code',
                'module' => 'Đào tạo',
            ],
            'language_levels' => [
                'label' => 'Cấp độ',
                'singular' => 'cấp độ',
                'model' => LanguageLevel::class,
                'search' => ['name', 'code'],
                'title' => 'name',
                'subtitle' => 'code',
                'module' => 'Đào tạo',
            ],
            'language_classes' => [
                'label' => 'Lớp học',
                'singular' => 'lớp học',
                'model' => LanguageClass::class,
                'search' => ['name', 'code', 'room'],
                'title' => 'name',
                'subtitle' => 'code',
                'module' => 'Đào tạo',
            ],
            'language_courses' => [
                'label' => 'Khóa học trung tâm',
                'singular' => 'khóa học trung tâm',
                'model' => LanguageCourse::class,
                'search' => ['name', 'code', 'textbook'],
                'title' => 'name',
                'subtitle' => 'code',
                'module' => 'Đào tạo',
            ],
            'language_discount_policies' => [
                'label' => 'Miễn giảm',
                'singular' => 'chế độ miễn giảm',
                'model' => LanguageDiscountPolicy::class,
                'search' => ['name'],
                'title' => 'name',
                'subtitle' => null,
                'module' => 'Điều hành trung tâm',
            ],
            'language_collaborators' => [
                'label' => 'Cộng tác viên',
                'singular' => 'cộng tác viên',
                'model' => LanguageCollaborator::class,
                'search' => ['name', 'code', 'phone', 'email'],
                'title' => 'name',
                'subtitle' => 'code',
                'module' => 'Tư vấn & tuyển sinh',
            ],
            'kpi_records' => [
                'label' => 'Dữ liệu KPI',
                'singular' => 'dữ liệu KPI',
                'model' => KpiRecord::class,
                'search' => ['student_name', 'class_name', 'receipt_no'],
                'title' => 'student_name',
                'subtitle' => 'class_name',
                'module' => 'KPI & báo cáo',
            ],
            'kpi_targets' => [
                'label' => 'Chỉ tiêu KPI',
                'singular' => 'chỉ tiêu KPI',
                'model' => KpiTarget::class,
                'search' => ['note'],
                'title' => 'note',
                'subtitle' => null,
                'module' => 'KPI & báo cáo',
            ],
        ];
    }

    /**
     * @param  array{label:string,singular:string,model:class-string<Model>,search:string[],title:string,subtitle:string|null,module:string}  $config
     * @return Collection<int, array<string, mixed>>
     */
    private function fetchItemsForType(string $type, array $config, string $search): Collection
    {
        /** @var Builder $query */
        $query = $config['model']::onlyTrashed();
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($config, $search): void {
                foreach ($config['search'] as $index => $column) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $builder->{$method}($column, 'like', "%{$search}%");
                }
            });
        }

        return $query->get()->map(function (Model $item) use ($config, $type): array {
            $title = trim((string) data_get($item, $config['title']));
            if ($type === 'kpi_targets' && $title === '') {
                $title = 'Chỉ tiêu #'.$item->getKey();
            }
            if ($title === '') {
                $title = $config['label'].' #'.$item->getKey();
            }

            return [
                'type' => $type,
                'type_label' => $config['label'],
                'module_label' => $config['module'],
                'id' => $item->getKey(),
                'title' => $title,
                'subtitle' => $config['subtitle'] ? trim((string) data_get($item, $config['subtitle'])) : '',
                'deleted_at' => $item->deleted_at,
                'subject_type' => $item::class,
            ];
        });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     */
    private function paginateCollection(Collection $items, Request $request): LengthAwarePaginator
    {
        $perPage = \App\Support\Pagination::perPage();
        $page = LengthAwarePaginator::resolveCurrentPage();
        $slice = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $slice,
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $pageItems
     * @return Collection<string, ActivityLog>
     */
    private function deleteLogsForPage(Collection $pageItems): Collection
    {
        if ($pageItems->isEmpty()) {
            return collect();
        }

        $subjectTypes = $pageItems->pluck('subject_type')->unique()->values()->all();
        $subjectIds = $pageItems->pluck('id')->map(fn ($id) => (int) $id)->unique()->values()->all();

        return ActivityLog::query()
            ->with('user')
            ->whereNotNull('subject_type')
            ->whereNotNull('subject_id')
            ->whereIn('subject_type', $subjectTypes)
            ->whereIn('subject_id', $subjectIds)
            ->get()
            ->filter(fn (ActivityLog $log) => str_contains($log->action, 'delete'))
            ->filter(fn (ActivityLog $log) => $pageItems->contains(
                fn (array $item) => $item['subject_type'] === $log->subject_type
                    && (int) $item['id'] === (int) $log->subject_id
            ))
            ->sortByDesc('created_at')
            ->unique(fn (ActivityLog $log) => $log->subject_type.'#'.$log->subject_id)
            ->keyBy(fn (ActivityLog $log) => $log->subject_type.'#'.$log->subject_id);
    }

    /**
     * @param  array{title:string}  $config
     */
    private function displayName(Model $item, array $config): string
    {
        $value = trim((string) data_get($item, $config['title']));
        return $value !== '' ? $value : '#'.$item->getKey();
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403, 'Chỉ admin mới được quản lý thùng rác chung.');
    }
}
