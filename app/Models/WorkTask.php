<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkTask extends Model
{
    protected $fillable = ['created_by_id', 'closed_by_id', 'title', 'description', 'due_at', 'priority', 'closed_at'];

    protected function casts(): array { return ['due_at' => 'datetime', 'closed_at' => 'datetime']; }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by_id')->withTrashed(); }
    public function closedBy(): BelongsTo { return $this->belongsTo(User::class, 'closed_by_id')->withTrashed(); }
    public function assignees(): HasMany { return $this->hasMany(WorkTaskAssignee::class); }
    public function comments(): HasMany { return $this->hasMany(WorkTaskComment::class); }
    public function activities(): HasMany { return $this->hasMany(WorkTaskActivity::class); }
}
