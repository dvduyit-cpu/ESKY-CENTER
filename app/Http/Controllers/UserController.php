<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Personnel;
use App\Models\LanguageCollaborator;
use App\Models\Role;
use App\Models\User;
use App\Models\UserPermission;
use App\Support\ActivityLogger;
use App\Support\ModulePermissionCatalog;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::with(['role','personnel','languageCollaborator'])->withTrashed()->orderBy('name');
        if ($request->user()->isDirector()) {
            $query->whereHas('role', fn ($role) => $role->where('code', 'deputy_director'));
        }
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
            'users' => $query->paginate(\App\Support\Pagination::perPage())->withQueryString(),
            'roles' => $this->manageableRoles($request->user()),
        ]);
    }

    public function create(Request $request): View
    {
        return view('users.form', [
            'user' => new User(),
            'roles' => $this->manageableRoles($request->user()),
            'personnels' => Personnel::where('active', true)->whereDoesntHave('user')->orderBy('name')->get(),
            'collaborators' => LanguageCollaborator::where('active',true)->whereDoesntHave('personnel.user')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['password'] = Hash::make($data['password']);
        $data['active'] = $request->boolean('active', true);
        $data['is_registrar'] = $this->registrarValue($request, (int) $data['role_id']);
        $data['is_instructor'] = $this->instructorValue($request, (int) $data['role_id']);
        $data['must_change_password'] = $request->boolean('must_change_password', true);
        $user = User::create($data);
        ActivityLogger::log('users', 'create', 'Tạo tài khoản '.$user->email, $user, null, $user->only(['name','email','role_id','active','is_registrar','is_instructor']));
        return redirect()->route('users.index')->with('success', 'Đã tạo tài khoản.');
    }

    public function edit(Request $request, User $user): View
    {
        $this->ensureCanManageUser($request->user(), $user);

        return view('users.form', [
            'user' => $user,
            'roles' => $this->manageableRoles($request->user()),
            'personnels' => Personnel::where(fn ($q) => $q->where('active', true)->orWhere('id', $user->personnel_id))
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
        $this->ensureCanManageUser($request->user(), $user);
        $before = $user->only(['name','email','role_id','active','is_registrar','is_instructor','personnel_id']);
        $data = $this->validated($request, $user);
        unset($data['password']);
        $data['active'] = $request->boolean('active');
        $data['is_registrar'] = $this->registrarValue($request, (int) $data['role_id']);
        $data['is_instructor'] = $this->instructorValue($request, (int) $data['role_id']);
        if (! $data['is_instructor'] && $user->canTeach() && DB::table('language_classes')
            ->where('teacher_user_id', $user->id)
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->whereNull('deleted_at')
            ->exists()) {
            throw ValidationException::withMessages([
                'is_instructor' => 'Tài khoản vẫn đang phụ trách lớp. Hãy chuyển giáo viên cho các lớp đang hoạt động trước khi tắt Kiêm giảng dạy.',
            ]);
        }
        $data['must_change_password'] = $request->boolean('must_change_password');
        $user->update($data);
        ActivityLogger::log('users', 'update', 'Cập nhật tài khoản '.$user->email, $user, $before, $user->fresh()->only(['name','email','role_id','active','is_registrar','is_instructor','personnel_id']));
        return redirect()->route('users.index')->with('success', 'Đã cập nhật tài khoản.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->ensureCanManageUser($request->user(), $user);
        abort_if($request->user()->is($user), 422, 'Không thể xóa tài khoản đang đăng nhập.');
        $this->guardLastAdmin($user);
        $user->delete();
        ActivityLogger::log('users', 'delete', 'Xóa mềm tài khoản '.$user->email, $user);
        return back()->with('success', 'Đã xóa mềm tài khoản.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'Chỉ Admin được xóa nhiều tài khoản.');
        $data = $request->validate(['ids' => ['required','array','min:1'], 'ids.*' => ['integer'], 'delete_type' => ['required', Rule::in(['soft','force'])]]);
        $force = $data['delete_type'] === 'force';
        abort_if($force && ! $request->user()->isAdmin(), 403, 'Chỉ quản trị viên được xóa vĩnh viễn.');
        $users = User::withTrashed()->with('role')->whereKey($data['ids'])->whereKeyNot($request->user()->id)->get();
        $deleted = 0;
        foreach ($users as $user) {
            if ($user->role?->code === 'admin' && User::where('role_id', $user->role_id)->where('active', true)->count() <= 1) continue;
            if ($force && $this->hasPermanentDeleteDependencies($user)) continue;
            try { $force ? $user->forceDelete() : $user->delete(); $deleted++; } catch (QueryException) {}
        }
        ActivityLogger::log('users', $force ? 'bulk_force_delete' : 'bulk_delete', ($force ? 'Xóa vĩnh viễn ' : 'Xóa mềm ').$deleted.' tài khoản');

        return back()->with('success', 'Đã '.($force ? 'xóa vĩnh viễn ' : 'xóa mềm ').$deleted.' tài khoản. Tài khoản đang đăng nhập, Admin cuối cùng hoặc bản ghi còn ràng buộc được giữ lại.');
    }

    public function restore(Request $request, int $id): RedirectResponse
    {
        $user = User::withTrashed()->findOrFail($id);
        $this->ensureCanManageUser($request->user(), $user);
        $user->restore();
        ActivityLogger::log('users', 'restore', 'Khôi phục tài khoản '.$user->email, $user);
        return back()->with('success', 'Đã khôi phục tài khoản.');
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        $this->ensureCanManageUser($request->user(), $user);
        abort_if($request->user()->is($user), 422, 'Không thể khóa tài khoản đang đăng nhập.');
        if ($user->active) $this->guardLastAdmin($user);
        $user->update(['active' => ! $user->active]);
        ActivityLogger::log('users', 'toggle', ($user->active ? 'Mở khóa ' : 'Khóa ').$user->email, $user);
        return back()->with('success', $user->active ? 'Đã mở khóa tài khoản.' : 'Đã khóa tài khoản.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $this->ensureCanManageUser($request->user(), $user);
        $data = $request->validate(['password' => ['required','string','min:8','confirmed']]);
        $user->update(['password' => Hash::make($data['password']), 'must_change_password' => true]);
        ActivityLogger::log('users', 'reset_password', 'Đặt lại mật khẩu '.$user->email, $user);
        return back()->with('success', 'Đã đặt lại mật khẩu. Người dùng phải đổi mật khẩu khi đăng nhập.');
    }

    public function permissions(Request $request, User $user): View
    {
        abort_unless($request->user()->isAdmin(), 403, 'Chỉ Admin được ghi đè quyền riêng.');
        $modules = Module::orderBy('sort_order')->get();

        return view('users.permissions', [
            'user' => $user->load('role'),
            'modules' => $modules,
            'moduleGroups' => ModulePermissionCatalog::grouped($modules),
            'overrides' => $user->permissions()->get()->keyBy('module_id'),
        ]);
    }

    public function updatePermissions(Request $request, User $user): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'Chỉ Admin được ghi đè quyền riêng.');
        $modules = Module::all();
        DB::transaction(function () use ($request, $user, $modules): void {
            foreach ($modules as $module) {
                $override = $request->boolean("override.{$module->id}");
                if (! $override) {
                    UserPermission::where('user_id', $user->id)->where('module_id', $module->id)->delete();
                    continue;
                }
                $supportedActions = ModulePermissionCatalog::actionsFor($module->code);
                UserPermission::updateOrCreate(
                    ['user_id' => $user->id, 'module_id' => $module->id],
                    [
                        'can_view' => in_array('view', $supportedActions, true) && $request->boolean("permissions.{$module->id}.view"),
                        'can_create' => in_array('create', $supportedActions, true) && $request->boolean("permissions.{$module->id}.create"),
                        'can_update' => in_array('update', $supportedActions, true) && $request->boolean("permissions.{$module->id}.update"),
                        'can_delete' => in_array('delete', $supportedActions, true) && $request->boolean("permissions.{$module->id}.delete"),
                        'can_export' => in_array('export', $supportedActions, true) && $request->boolean("permissions.{$module->id}.export"),
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
            'role_id' => [
                'required',
                'integer',
                Rule::in($this->manageableRoles($request->user())->pluck('id')->all()),
            ],
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

    private function registrarValue(Request $request, int $roleId): bool
    {
        $roleCode = Role::whereKey($roleId)->value('code');
        if ($roleCode === 'teacher') return false;
        if ($roleCode === 'admin') return true;

        return $request->boolean('is_registrar');
    }

    private function instructorValue(Request $request, int $roleId): bool
    {
        $roleCode = Role::whereKey($roleId)->value('code');

        return $roleCode === 'teacher' || $request->boolean('is_instructor');
    }

    private function hasPermanentDeleteDependencies(User $user): bool
    {
        return DB::table('work_tasks')->where('created_by_id', $user->id)->exists()
            || DB::table('work_task_assignees')->where('user_id', $user->id)->exists()
            || DB::table('work_task_comments')->where('user_id', $user->id)->exists()
            || DB::table('upcoming_plans')->where('user_id', $user->id)->exists();
    }

    private function manageableRoles(User $actor)
    {
        if ($actor->isAdmin()) return Role::orderBy('name')->get();
        if ($actor->isDirector()) return Role::where('code', 'deputy_director')->orderBy('name')->get();

        return collect();
    }

    private function ensureCanManageUser(User $actor, User $target): void
    {
        if ($actor->isAdmin()) return;

        abort_unless(
            $actor->isDirector() && $target->role?->code === 'deputy_director',
            403,
            'Giám đốc chỉ được quản lý tài khoản Phó giám đốc.'
        );
    }
}
