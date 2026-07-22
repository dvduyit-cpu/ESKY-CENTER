<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\ExcessPayment;
use App\Models\KpiRecord;
use App\Models\KpiTarget;
use App\Support\ActivityLogger;
use App\Support\TextNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $query = Course::withTrashed()->orderBy('name');
        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(fn ($b) => $b->where('name','like',"%{$q}%")->orWhere('code','like',"%{$q}%"));
        }
        if ($request->filled('status')) {
            match ($request->string('status')->toString()) {
                'active' => $query->whereNull('deleted_at')->where('active', true),
                'locked' => $query->whereNull('deleted_at')->where('active', false),
                'deleted' => $query->onlyTrashed(),
                default => null,
            };
        }
        return view('courses.index', ['courses' => $query->paginate(20)->withQueryString()]);
    }

    public function create(): View { return view('courses.form', ['course' => new Course()]); }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['normalized_name'] = TextNormalizer::name($data['name']);
        $course = Course::create($data);
        ActivityLogger::log('courses', 'create', 'Tạo khóa học '.$course->name, $course);
        return redirect()->route('courses.index')->with('success', 'Đã tạo khóa học.');
    }

    public function edit(Course $course): View { return view('courses.form', compact('course')); }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $before = $course->toArray();
        $data = $this->validated($request, $course);
        $data['normalized_name'] = TextNormalizer::name($data['name']);
        $course->update($data);
        ActivityLogger::log('courses', 'update', 'Cập nhật khóa học '.$course->name, $course, $before, $course->fresh()->toArray());
        return redirect()->route('courses.index')->with('success', 'Đã cập nhật khóa học.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $course->delete();
        ActivityLogger::log('courses', 'delete', 'Xóa mềm khóa học '.$course->name, $course);
        return back()->with('success', 'Đã xóa mềm khóa học.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate(['ids' => ['required','array','min:1'], 'ids.*' => ['integer'], 'delete_type' => ['required', Rule::in(['soft','force'])]]);
        $force = $data['delete_type'] === 'force';
        abort_if($force && ! $request->user()->isAdmin(), 403, 'Chỉ quản trị viên được xóa vĩnh viễn.');
        $courses = Course::withTrashed()->whereKey($data['ids'])->get();
        $deleted = 0;
        foreach ($courses as $course) {
            try { $force ? $this->forceDeleteCourse($course) : $course->delete(); $deleted++; } catch (QueryException) {}
        }
        ActivityLogger::log('courses', $force ? 'bulk_force_delete' : 'bulk_delete', ($force ? 'Xóa vĩnh viễn ' : 'Xóa mềm ').$deleted.' khóa học');

        return back()->with('success', 'Đã '.($force ? 'xóa vĩnh viễn ' : 'xóa mềm ').$deleted.' khóa học. Bỏ qua '.($courses->count() - $deleted).' bản ghi còn ràng buộc.');
    }

    public function restore(int $id): RedirectResponse
    {
        $course = Course::withTrashed()->findOrFail($id);
        $course->restore();
        ActivityLogger::log('courses', 'restore', 'Khôi phục khóa học '.$course->name, $course);
        return back()->with('success', 'Đã khôi phục khóa học.');
    }

    public function forceDestroy(Request $request, int $id): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'Chỉ quản trị viên được xóa vĩnh viễn khóa học.');

        $course = Course::onlyTrashed()->findOrFail($id);
        $name = $course->name;

        try {
            $this->forceDeleteCourse($course);
        } catch (QueryException) {
            return back()->withErrors([
                'course' => 'Không thể xóa vĩnh viễn vì khóa học còn được sử dụng trong chỉ tiêu hoặc dữ liệu KPI.',
            ]);
        }

        ActivityLogger::log('courses', 'force_delete', 'Xóa vĩnh viễn khóa học '.$name);

        return back()->with('success', 'Đã xóa vĩnh viễn khóa học khỏi database.');
    }

    public function toggle(Course $course): RedirectResponse
    {
        $course->update(['active' => ! $course->active]);
        ActivityLogger::log('courses', 'toggle', ($course->active ? 'Mở ' : 'Khóa ').$course->name, $course);
        return back()->with('success', $course->active ? 'Đã mở khóa học.' : 'Đã khóa khóa học.');
    }

    private function validated(Request $request, ?Course $course = null): array
    {
        $data = $request->validate([
            'code' => ['nullable','string','max:50', Rule::unique('courses')->ignore($course?->id)],
            'name' => ['required','string','max:200'],
            'category' => ['nullable','string','max:150'],
            'conversion_quantity' => ['required','numeric','gt:0'],
            'conversion_kpi' => ['required','numeric','gte:0'],
            'conversion_mode' => ['required', Rule::in(['proportional','full_group'])],
            'default_excess_rate' => ['nullable','numeric','min:0'],
            'note' => ['nullable','string'],
        ]);
        $data['active'] = $request->boolean('active', true);
        $data['default_excess_rate'] = $data['default_excess_rate'] ?? 0;
        return $data;
    }

    private function forceDeleteCourse(Course $course): void
    {
        DB::transaction(function () use ($course): void {
            KpiTarget::withTrashed()->where('course_id', $course->id)->forceDelete();
            KpiRecord::withTrashed()->where('course_id', $course->id)->forceDelete();
            ExcessPayment::where('course_id', $course->id)->update(['course_id' => null]);
            $course->forceDelete();
        });
    }
}
