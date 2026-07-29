<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UpcomingPlan extends Model
{
    protected $fillable = ['user_id', 'assigned_by_id', 'title', 'note', 'scheduled_for', 'reminder_days', 'priority', 'kind', 'completed_at'];

    protected function casts(): array
    {
        return ['scheduled_for' => 'datetime', 'completed_at' => 'datetime', 'reminder_days' => 'integer'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_id')->withTrashed();
    }

    public function getIsDueForReminderAttribute(): bool
    {
        return ! $this->completed_at && $this->scheduled_for->lte(now()->addDays($this->reminder_days));
    }
}
