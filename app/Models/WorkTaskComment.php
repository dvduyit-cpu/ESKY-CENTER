<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkTaskComment extends Model
{
    protected $fillable = ['user_id', 'body'];
    public function task(): BelongsTo { return $this->belongsTo(WorkTask::class, 'work_task_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class)->withTrashed(); }
    public function attachments(): HasMany
    {
        return $this->hasMany(WorkTaskAttachment::class, 'work_task_comment_id');
    }
}
