<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkTaskComment extends Model
{
    protected $fillable = ['user_id', 'parent_id', 'reply_to_user_name', 'reply_excerpt', 'body'];
    public function task(): BelongsTo { return $this->belongsTo(WorkTask::class, 'work_task_id'); }
    public function user(): BelongsTo { return $this->belongsTo(User::class)->withTrashed(); }
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
    public function attachments(): HasMany
    {
        return $this->hasMany(WorkTaskAttachment::class, 'work_task_comment_id');
    }
}
