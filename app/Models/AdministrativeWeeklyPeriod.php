<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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

    public function isCurrentlyActive(): bool
    {
        if ($this->starts_at || $this->ends_at) {
            return (! $this->starts_at || now()->greaterThanOrEqualTo($this->starts_at))
                && (! $this->ends_at || now()->lessThan($this->ends_at));
        }

        return $this->is_active;
    }

    public function scopeActiveNow(Builder $query): Builder
    {
        return $query->where(function (Builder $active): void {
            $active->where(function (Builder $manual): void {
                $manual->whereNull('starts_at')->whereNull('ends_at')->where('is_active', true);
            })->orWhere(function (Builder $scheduled): void {
                $scheduled->where(fn (Builder $start) => $start->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                    ->where(fn (Builder $end) => $end->whereNull('ends_at')->orWhere('ends_at', '>', now()))
                    ->where(fn (Builder $hasSchedule) => $hasSchedule->whereNotNull('starts_at')->orWhereNotNull('ends_at'));
            });
        });
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
}
