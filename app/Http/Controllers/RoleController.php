<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Role;
use App\Models\RolePermission;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(): View
    {
        return view('roles.index', ['roles' => Role::withCount('users')->orderBy('name')->paginate(20)->withQueryString()]);
    }

    public function create(): View
    {
        return view('roles.form', ['role' => new Role(), 'modules' => Module::orderBy('sort_order')->get(), 'permissions' => collect()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required','alpha_dash','max:50','unique:roles,code'],
            'name' => ['required','string','max:100'],
            'description' => ['nullable','string','max:500'],
        ]);
        $role = Role::create($data + ['is_system' => false]);
        $this->savePermissions($request, $role);
        ActivityLogger::log('roles', 'create', 'Tạo vai trò '.$role->name, $role);
        return redirect()->route('roles.index')->with('success', 'Đã tạo vai trò.');
    }

    public function edit(Role $role): View
    {
        return view('roles.form', [
            'role' => $role,
            'modules' => Module::orderBy('sort_order')->get(),
            'permissions' => $role->permissions()->get()->keyBy('module_id'),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required','string','max:100'],
            'description' => ['nullable','string','max:500'],
            'code' => ['required','alpha_dash','max:50', Rule::unique('roles')->ignore($role->id)],
        ]);
        if ($role->is_system) unset($data['code']);
        $role->update($data);
        $this->savePermissions($request, $role);
        ActivityLogger::log('roles', 'update', 'Cập nhật vai trò '.$role->name, $role);
        return redirect()->route('roles.index')->with('success', 'Đã cập nhật vai trò và quyền.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if($role->is_system || $role->users()->exists(), 422, 'Không thể xóa vai trò hệ thống hoặc vai trò đang được sử dụng.');
        $role->delete();
        ActivityLogger::log('roles', 'delete', 'Xóa vai trò '.$role->name, $role);
        return back()->with('success', 'Đã xóa vai trò.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $ids = $request->validate(['ids' => ['required','array','min:1'], 'ids.*' => ['integer']])['ids'];
        $roles = Role::whereKey($ids)->where('is_system', false)->whereDoesntHave('users')->get();
        $roles->each->delete();
        ActivityLogger::log('roles', 'bulk_delete', 'Xóa '.$roles->count().' vai trò');

        return back()->with('success', 'Đã xóa '.$roles->count().' vai trò. Vai trò hệ thống hoặc đang sử dụng được giữ lại.');
    }

    private function savePermissions(Request $request, Role $role): void
    {
        DB::transaction(function () use ($request, $role): void {
            foreach (Module::all() as $module) {
                RolePermission::updateOrCreate(
                    ['role_id' => $role->id, 'module_id' => $module->id],
                    [
                        'can_view' => $request->boolean("permissions.{$module->id}.view"),
                        'can_create' => $request->boolean("permissions.{$module->id}.create"),
                        'can_update' => $request->boolean("permissions.{$module->id}.update"),
                        'can_delete' => $request->boolean("permissions.{$module->id}.delete"),
                        'can_export' => $request->boolean("permissions.{$module->id}.export"),
                    ]
                );
            }
        });
    }
}
