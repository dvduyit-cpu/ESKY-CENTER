<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkTaskAssignee extends Model
{
    protected $fillable = ['user_id', 'is_lead', 'acknowledged_at', 'completed_at', 'note'];
    protected function casts(): array { return ['is_lead'=>'boolean', 'acknowledged_at'=>'datetime', 'completed_at'=>'datetime']; }
    public function task(): BelongsTo { return $this->belongsTo(WorkTask::class, 'work_task_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class)->withTrashed(); }
}
