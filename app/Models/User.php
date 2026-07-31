<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'personnel_id', 'language_collaborator_id', 'role_id', 'name', 'email', 'password', 'active',
        'must_change_password', 'notifications_enabled', 'is_registrar', 'is_instructor', 'theme_color', 'last_login_at', 'last_login_ip',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed', 'active' => 'boolean', 'is_registrar' => 'boolean', 'is_instructor' => 'boolean',
            'must_change_password' => 'boolean', 'notifications_enabled' => 'boolean', 'last_login_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo { return $this->belongsTo(Role::class); }
    public function personnel(): BelongsTo { return $this->belongsTo(Personnel::class)->withTrashed(); }
    public function languageCollaborator(): BelongsTo { return $this->belongsTo(LanguageCollaborator::class)->withTrashed(); }
    public function upcomingPlans(): HasMany { return $this->hasMany(UpcomingPlan::class); }
    public function preferences(): HasMany { return $this->hasMany(UserPreference::class); }
    public function permissions(): HasMany { return $this->hasMany(UserPermission::class); }

    public function isAdmin(): bool
    {
        return $this->role?->code === 'admin';
    }

    public function isDirector(): bool
    {
        return $this->role?->code === 'director';
    }

    public function isTeacher(): bool
    {
        return $this->role?->code === 'teacher';
    }

    public function canTeach(): bool
    {
        return $this->isTeacher() || $this->is_instructor;
    }

    public function scopeInstructors(Builder $query): Builder
    {
        return $query->where(fn (Builder $candidate) => $candidate
            ->where('is_instructor', true)
            ->orWhereHas('role', fn (Builder $role) => $role->where('code', 'teacher'))
        );
    }

    public function isRegistrar(): bool
    {
        return $this->isAdmin() || (! $this->isTeacher() && $this->is_registrar);
    }

    public function isDeputyDirector(): bool
    {
        return $this->role?->code === 'deputy_director';
    }

    public function isManagement(): bool
    {
        return $this->isAdmin() || $this->isDirector() || $this->isDeputyDirector();
    }

    public function isLeader(): bool
    {
        return in_array($this->role?->code, ['admin', 'director', 'deputy_director', 'leader'], true);
    }

    public function allowed(string $moduleCode, string $action = 'view'): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->isRegistrar()) {
            $registrarPermissions = [
                'language_students' => ['view', 'create', 'update'],
                'language_classes' => ['view', 'update'],
                'language_tuition' => ['view', 'create', 'update', 'export'],
                'teacher_classes' => ['view'],
            ];
            if (in_array($action, $registrarPermissions[$moduleCode] ?? [], true)) {
                return true;
            }
        }

        if ($this->canTeach() && $moduleCode === 'teacher_classes' && in_array($action, ['view', 'update'], true)) {
            return true;
        }

        $column = 'can_'.$action;
        if (! in_array($column, ['can_view','can_create','can_update','can_delete','can_export'], true)) {
            return false;
        }

        $module = Module::query()->where('code', $moduleCode)->first();
        if (! $module) {
            return false;
        }

        $override = UserPermission::query()
            ->where('user_id', $this->id)
            ->where('module_id', $module->id)
            ->first();

        if ($override) {
            return (bool) $override->{$column};
        }

        if (! $this->role_id) {
            return false;
        }

        $rolePermission = RolePermission::query()
            ->where('role_id', $this->role_id)
            ->where('module_id', $module->id)
            ->first();

        return (bool) ($rolePermission?->{$column} ?? false);
    }
}
