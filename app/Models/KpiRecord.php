<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class KpiRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'import_batch_id', 'source_row_no', 'personnel_id', 'collaborator_id', 'course_id',
        'student_name', 'class_name', 'raw_quantity', 'revenue', 'receipt_no', 'record_date',
        'record_year', 'record_quarter', 'record_month', 'conversion_quantity',
        'conversion_kpi', 'conversion_mode', 'note', 'created_by',
    ];

    protected $casts = [
        'raw_quantity' => 'decimal:2', 'revenue' => 'decimal:2',
        'conversion_quantity' => 'decimal:2', 'conversion_kpi' => 'decimal:2',
        'record_date' => 'date',
    ];

    public function batch(): BelongsTo { return $this->belongsTo(ImportBatch::class, 'import_batch_id'); }
    public function personnel(): BelongsTo { return $this->belongsTo(Personnel::class); }
    public function collaborator(): BelongsTo { return $this->belongsTo(Personnel::class, 'collaborator_id'); }
    public function course(): BelongsTo { return $this->belongsTo(Course::class); }
}
