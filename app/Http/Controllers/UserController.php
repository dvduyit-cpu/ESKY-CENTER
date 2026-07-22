<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Personnel;
use App\Models\LanguageCollaborator;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPermission;
use App\Support\ActivityLogger;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::with(['role','personnel','languageCollaborator'])->withTrashed()->orderBy('name');
        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(fn ($b) => $b->where('name','like',"%{$q}%")->orWhere('email','like',"%{$q}%"));
        }
        if ($request->filled('role_id')) $query->where('role_id', $request->integer('role_id'));
        if ($request->filled('status')) {
            match ($request->string('status')->toString()) {
                'active' => $query->whereNull('deleted_at')->where('active', true),
                'locked' => $query->whereNull('deleted_at')->where('active', false),
                'deleted' => $query->onlyTrashed(),
                default => null,
            };
        }
        return view('users.index', [
            'users' => $query->paginate(20)->withQueryString(),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('users.form', [
            'user' => new User(),
            'roles' => Role::orderBy('name')->get(),
            'personnels' => Personnel::where('active', true)->whereDoesntHave('user')->orderBy('name')->get(),
            'collaborators' => LanguageCollaborator::where('active',true)->whereDoesntHave('personnel.user')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['password'] = Hash::make($data['password']);
        $data['active'] = $request->boolean('active', true);
        $data['must_change_password'] = $request->boolean('must_change_password', true);
        $user = User::create($data);
        ActivityLogger::log('users', 'create', 'Tạo tài khoản '.$user->email, $user, null, $user->only(['name','email','role_id','active']));
        return redirect()->route('users.index')->with('success', 'Đã tạo tài khoản.');
    }

    public function edit(User $user): View
    {
        return view('users.form', [
            'user' => $user,
            'roles' => Role::orderBy('name')->get(),
            'personnels' => Personnel::where('active', true)
                ->where(fn ($q) => $q->whereDoesntHave('user')->orWhere('id', $user->personnel_id))
                ->orderBy('name')->get(),
            'collaborators' => LanguageCollaborator::where(function ($query) use ($user) {
                $query->where('active',true);
                if ($user->language_collaborator_id) $query->orWhere('id',$user->language_collaborator_id);
            })->where(fn($query)=>$query->whereDoesntHave('personnel.user')->orWhere('id',$user->language_collaborator_id))->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $before = $user->only(['name','email','role_id','active','personnel_id']);
        $data = $this->validated($request, $user);
        unset($data['password']);
        $data['active'] = $request->boolean('active');
        $data['must_change_password'] = $request->boolean('must_change_password');
        $user->update($data);
        ActivityLogger::log('users', 'update', 'Cập nhật tài khoản '.$user->email, $user, $before, $user->fresh()->only(['name','email','role_id','active','personnel_id']));
        return redirect()->route('users.index')->with('success', 'Đã cập nhật tài khoản.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 422, 'Không thể xóa tài khoản đang đăng nhập.');
        $this->guardLastAdmin($user);
        $user->delete();
        ActivityLogger::log('users', 'delete', 'Xóa mềm tài khoản '.$user->email, $user);
        return back()->with('success', 'Đã xóa mềm tài khoản.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate(['ids' => ['required','array','min:1'], 'ids.*' => ['integer'], 'delete_type' => ['required', Rule::in(['soft','force'])]]);
        $force = $data['delete_type'] === 'force';
        abort_if($force && ! $request->user()->isAdmin(), 403, 'Chỉ quản trị viên được xóa vĩnh viễn.');
        $users = User::withTrashed()->with('role')->whereKey($data['ids'])->whereKeyNot($request->user()->id)->get();
        $deleted = 0;
        foreach ($users as $user) {
            if ($user->role?->code === 'admin' && User::where('role_id', $user->role_id)->where('active', true)->count() <= 1) continue;
            try { $force ? $user->forceDelete() : $user->delete(); $deleted++; } catch (QueryException) {}
        }
        ActivityLogger::log('users', $force ? 'bulk_force_delete' : 'bulk_delete', ($force ? 'Xóa vĩnh viễn ' : 'Xóa mềm ').$deleted.' tài khoản');

        return back()->with('success', 'Đã '.($force ? 'xóa vĩnh viễn ' : 'xóa mềm ').$deleted.' tài khoản. Tài khoản đang đăng nhập, Admin cuối cùng hoặc bản ghi còn ràng buộc được giữ lại.');
    }

    public function restore(int $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();
        ActivityLogger::log('users', 'restore', 'Khôi phục tài khoản '.$user->email, $user);
        return back()->with('success', 'Đã khôi phục tài khoản.');
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        abort_if($request->user()->is($user), 422, 'Không thể khóa tài khoản đang đăng nhập.');
        if ($user->active) $this->guardLastAdmin($user);
        $user->update(['active' => ! $user->active]);
        ActivityLogger::log('users', 'toggle', ($user->active ? 'Mở khóa ' : 'Khóa ').$user->email, $user);
        return back()->with('success', $user->active ? 'Đã mở khóa tài khoản.' : 'Đã khóa tài khoản.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['password' => ['required','string','min:8','confirmed']]);
        $user->update(['password' => Hash::make($data['password']), 'must_change_password' => true]);
        ActivityLogger::log('users', 'reset_password', 'Đặt lại mật khẩu '.$user->email, $user);
        return back()->with('success', 'Đã đặt lại mật khẩu. Người dùng phải đổi mật khẩu khi đăng nhập.');
    }

    public function permissions(User $user): View
    {
        return view('users.permissions', [
            'user' => $user->load('role'),
            'modules' => Module::orderBy('sort_order')->get(),
            'overrides' => $user->permissions()->get()->keyBy('module_id'),
        ]);
    }

    public function updatePermissions(Request $request, User $user): RedirectResponse
    {
        $modules = Module::all();
        DB::transaction(function () use ($request, $user, $modules): void {
            foreach ($modules as $module) {
                $override = $request->boolean("override.{$module->id}");
                if (! $override) {
                    UserPermission::where('user_id', $user->id)->where('module_id', $module->id)->delete();
                    continue;
                }
                UserPermission::updateOrCreate(
                    ['user_id' => $user->id, 'module_id' => $module->id],
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
        ActivityLogger::log('users', 'permissions', 'Cập nhật quyền riêng cho '.$user->email, $user);
        return redirect()->route('users.index')->with('success', 'Đã cập nhật quyền riêng.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'personnel_id' => ['nullable','integer','exists:personnels,id', Rule::unique('users')->ignore($user?->id)],
            'language_collaborator_id' => ['nullable','integer','exists:language_collaborators,id', Rule::unique('users')->ignore($user?->id)],
            'role_id' => ['required','integer','exists:roles,id'],
            'name' => ['required','string','max:150'],
            'email' => ['required','email','max:150', Rule::unique('users')->ignore($user?->id)],
            'password' => $user ? ['nullable'] : ['required','string','min:8','confirmed'],
        ]);
    }

    private function guardLastAdmin(User $user): void
    {
        if ($user->role?->code !== 'admin') return;
        $adminRoleId = Role::where('code', 'admin')->value('id');
        abort_if(User::where('role_id', $adminRoleId)->where('active', true)->count() <= 1, 422, 'Không thể khóa hoặc xóa Admin cuối cùng.');
    }
}
