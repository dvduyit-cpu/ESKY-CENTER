<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdministrativeDocument extends Model
{
    protected $fillable = ['document_number','document_symbol','drafter','document_date','signer','summary','destination','receiver','storage_link','notes','created_by','updated_by'];
    protected function casts(): array { return ['document_date'=>'date']; }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by')->withTrashed(); }
    public function updater(): BelongsTo { return $this->belongsTo(User::class, 'updated_by')->withTrashed(); }
    public function attachments(): HasMany { return $this->hasMany(AdministrativeDocumentAttachment::class, 'document_id'); }
}
