<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkTaskActivity extends Model
{
    protected $fillable = ['user_id', 'action', 'description'];
    public function task(): BelongsTo { return $this->belongsTo(WorkTask::class, 'work_task_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class)->withTrashed(); }
}
