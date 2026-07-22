<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_type', 'period_type', 'year', 'quarter', 'month', 'original_name',
        'stored_path', 'file_hash', 'status', 'total_rows', 'success_rows', 'error_rows',
        'total_revenue', 'error_details', 'imported_by',
    ];

    protected $casts = ['total_revenue' => 'decimal:2', 'error_details' => 'array'];

    public function user(): BelongsTo { return $this->belongsTo(User::class, 'imported_by'); }
    public function records(): HasMany { return $this->hasMany(KpiRecord::class); }
}
