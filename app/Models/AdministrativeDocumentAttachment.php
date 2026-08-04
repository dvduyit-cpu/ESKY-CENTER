<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdministrativeDocumentAttachment extends Model
{
    protected $fillable = ['document_id','kind','original_name','storage_path','mime_type','size','uploaded_by'];
    public function document(): BelongsTo { return $this->belongsTo(AdministrativeDocument::class, 'document_id'); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by')->withTrashed(); }
}
