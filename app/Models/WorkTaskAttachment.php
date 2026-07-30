<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkTaskAttachment extends Model
{
    protected $fillable = ['work_task_comment_id', 'uploaded_by_id', 'original_name', 'storage_path', 'mime_type', 'size'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(WorkTask::class, 'work_task_id');
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(WorkTaskComment::class, 'work_task_comment_id');
    }
}
