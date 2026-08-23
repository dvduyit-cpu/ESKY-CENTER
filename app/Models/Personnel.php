<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Personnel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code', 'name', 'normalized_name', 'type', 'position', 'email', 'phone',
        'default_kpi', 'has_kpi', 'is_consultant', 'payment_type', 'payment_value', 'active', 'note',
    ];

    protected $casts = [
        'default_kpi' => 'decimal:2', 'payment_value' => 'decimal:2',
        'has_kpi' => 'boolean', 'is_consultant'=>'boolean', 'active' => 'boolean',
    ];

    public function user(): HasOne { return $this->hasOne(User::class)->withTrashed(); }
    public function targets(): HasMany { return $this->hasMany(KpiTarget::class); }
    public function teachingReports(): HasMany { return $this->hasMany(KpiTeachingReport::class); }
    public function records(): HasMany { return $this->hasMany(KpiRecord::class); }
    public function collaboratorRecords(): HasMany { return $this->hasMany(KpiRecord::class, 'collaborator_id'); }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'teacher' => 'Giáo viên', 'employee' => 'Nhân viên', 'leader' => 'Lãnh đạo',
            'collaborator' => 'Cộng tác viên', 'admin' => 'Admin', default => $this->type,
        };
    }
}
