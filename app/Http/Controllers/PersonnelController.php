<?php

namespace App\Http\Controllers;

use App\Models\Personnel;
use App\Support\ActivityLogger;
use App\Support\TextNormalizer;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PersonnelController extends Controller
{
    public function index(Request $request): View
    {
        $query = Personnel::with('user')->withTrashed()->orderBy('name');
        if ($request->filled('type')) $query->where('type', $request->string('type'));
        if ($request->filled('status')) {
            match ($request->string('status')->toString()) {
                'active' => $query->whereNull('deleted_at')->where('active', true),
                'locked' => $query->whereNull('deleted_at')->where('active', false),
                'deleted' => $query->onlyTrashed(),
                default => null,
            };
        }
        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(fn ($builder) => $builder->where('name', 'like', "%{$q}%")
                ->orWhere('code', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%"));
        }
        return view('personnels.index', ['personnels' => $query->paginate(\App\Support\Pagination::perPage())->withQueryString()]);
    }

    public function create(): View
    {
        return view('personnels.form', ['personnel' => new Personnel()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['normalized_name'] = TextNormalizer::name($data['name']);
        $personnel = Personnel::create($data);
        ActivityLogger::log('personnel', 'create', 'Tạo nhân sự '.$personnel->name, $personnel, null, $personnel->toArray());
        return redirect()->route('personnels.index')->with('success', 'Đã tạo nhân sự.');
    }

    public function edit(Personnel $personnel): View
    {
        return view('personnels.form', compact('personnel'));
    }

    public function update(Request $request, Personnel $personnel): RedirectResponse
    {
        $before = $personnel->toArray();
        $data = $this->validated($request, $personnel);
        $data['normalized_name'] = TextNormalizer::name($data['name']);
        $personnel->update($data);
        ActivityLogger::log('personnel', 'update', 'Cập nhật nhân sự '.$personnel->name, $personnel, $before, $personnel->fresh()->toArray());
        return redirect()->route('personnels.index')->with('success', 'Đã cập nhật nhân sự.');
    }

    public function destroy(Personnel $personnel): RedirectResponse
    {
        abort_if($personnel->user()->withTrashed()->exists(), 422, 'Nhân sự đang liên kết tài khoản. Hãy xóa hoặc gỡ tài khoản trước.');
        $personnel->delete();
        ActivityLogger::log('personnel', 'delete', 'Xóa mềm nhân sự '.$personnel->name, $personnel);
        return back()->with('success', 'Đã xóa mềm nhân sự.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate(['ids' => ['required','array','min:1'], 'ids.*' => ['integer'], 'delete_type' => ['required', Rule::in(['soft','force'])]]);
        $force = $data['delete_type'] === 'force';
        abort_if($force && ! $request->user()->isAdmin(), 403, 'Chỉ quản trị viên được xóa vĩnh viễn.');
        $personnels = Personnel::withTrashed()->whereKey($data['ids'])->whereDoesntHave('user')->get();
        $deleted = 0;
        foreach ($personnels as $personnel) {
            try { $force ? $personnel->forceDelete() : $personnel->delete(); $deleted++; } catch (QueryException) {}
        }
        $skipped = count(array_unique($data['ids'])) - $deleted;
        ActivityLogger::log('personnel', $force ? 'bulk_force_delete' : 'bulk_delete', ($force ? 'Xóa vĩnh viễn ' : 'Xóa mềm ').$deleted.' nhân sự');

        return back()->with('success', 'Đã '.($force ? 'xóa vĩnh viễn ' : 'xóa mềm ').$deleted." nhân sự. Bỏ qua {$skipped} bản ghi còn liên kết.");
    }

    public function restore(int $id): RedirectResponse
    {
        $personnel = Personnel::withTrashed()->findOrFail($id);
        $personnel->restore();
        ActivityLogger::log('personnel', 'restore', 'Khôi phục nhân sự '.$personnel->name, $personnel);
        return back()->with('success', 'Đã khôi phục nhân sự.');
    }

    public function forceDestroy(Request $request, int $id): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'Chỉ quản trị viên được xóa vĩnh viễn nhân sự.');

        $personnel = Personnel::onlyTrashed()->findOrFail($id);
        $name = $personnel->name;

        try {
            $personnel->forceDelete();
        } catch (QueryException) {
            return back()->withErrors([
                'personnel' => 'Không thể xóa vĩnh viễn vì nhân sự còn được sử dụng trong dữ liệu KPI hoặc thanh toán.',
            ]);
        }

        ActivityLogger::log('personnel', 'force_delete', 'Xóa vĩnh viễn nhân sự '.$name);

        return back()->with('success', 'Đã xóa vĩnh viễn nhân sự khỏi database.');
    }

    public function toggle(Personnel $personnel): RedirectResponse
    {
        $personnel->update(['active' => ! $personnel->active]);
        if ($personnel->user) $personnel->user->update(['active' => $personnel->active]);
        ActivityLogger::log('personnel', 'toggle', ($personnel->active ? 'Mở khóa ' : 'Khóa ').$personnel->name, $personnel);
        return back()->with('success', $personnel->active ? 'Đã mở khóa nhân sự.' : 'Đã khóa nhân sự.');
    }

    private function validated(Request $request, ?Personnel $personnel = null): array
    {
        $data = $request->validate([
            'code' => ['nullable','string','max:50', Rule::unique('personnels')->ignore($personnel?->id)],
            'name' => ['required','string','max:150'],
            'type' => ['required', Rule::in(['teacher','employee','leader','collaborator','admin'])],
            'position' => ['nullable','string','max:150'],
            'email' => ['nullable','email','max:150'],
            'phone' => ['nullable','string','max:30'],
            'default_kpi' => ['nullable','numeric','min:0'],
            'payment_type' => ['required', Rule::in(['none','percentage','per_student','fixed'])],
            'payment_value' => ['nullable','numeric','min:0'],
            'note' => ['nullable','string'],
        ]);
        $data['has_kpi'] = $request->boolean('has_kpi');
        $data['is_consultant'] = $request->boolean('is_consultant');
        $data['active'] = $request->boolean('active', true);
        $data['default_kpi'] = $data['default_kpi'] ?? match ($data['type']) {
            'teacher' => 42, 'employee' => 55, default => 0,
        };
        $data['payment_value'] = $data['payment_value'] ?? 0;
        return $data;
    }
}
