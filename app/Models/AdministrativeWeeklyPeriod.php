<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AdministrativeWeeklyPeriod extends Model
{
    protected $fillable = [
        'week_start', 'week_end', 'due_date', 'title', 'is_active',
        'starts_at', 'ends_at', 'created_by', 'activated_by', 'activated_at',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date', 'week_end' => 'date', 'due_date' => 'date',
            'is_active' => 'boolean', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'activated_at' => 'datetime',
        ];
    }

    public function submissionStartsAt()
    {
        return $this->starts_at?->copy() ?? $this->week_start->copy()->startOfDay();
    }

    public function submissionEndsAt()
    {
        return $this->ends_at?->copy() ?? $this->due_date->copy()->endOfDay();
    }

    public function isSubmissionOpen(): bool
    {
        return now()->greaterThanOrEqualTo($this->submissionStartsAt())
            && now()->lessThanOrEqualTo($this->submissionEndsAt());
    }

    public function scopeActiveNow(Builder $query): Builder
    {
        return $query
            ->where(fn (Builder $start) => $start->whereNotNull('starts_at')->where('starts_at', '<=', now()))
            ->where(fn (Builder $end) => $end->whereNotNull('ends_at')->where('ends_at', '>=', now()));
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function activator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by')->withTrashed();
    }

    public function assignedUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'administrative_weekly_period_user', 'period_id', 'user_id')
            ->withTimestamps();
    }

    public function reports(): HasMany
    {
        return $this->hasMany(AdministrativeWeeklyReport::class, 'period_id');
    }

    public function compilation(): HasOne
    {
        return $this->hasOne(AdministrativeWeeklyCompilation::class, 'period_id');
    }
}
