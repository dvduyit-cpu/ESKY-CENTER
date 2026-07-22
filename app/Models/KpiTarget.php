<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class KpiTarget extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'plan_id', 'personnel_id', 'course_id', 'period_type', 'quarter', 'month',
        'target_quantity', 'target_revenue', 'is_mandatory', 'excess_payment_per_kpi',
        'note', 'created_by',
    ];

    protected $casts = [
        'target_quantity' => 'decimal:2', 'target_revenue' => 'decimal:2',
        'excess_payment_per_kpi' => 'decimal:2', 'is_mandatory' => 'boolean',
    ];

    public function plan(): BelongsTo { return $this->belongsTo(KpiPlan::class, 'plan_id'); }
    public function personnel(): BelongsTo { return $this->belongsTo(Personnel::class); }
    public function course(): BelongsTo { return $this->belongsTo(Course::class)->withDefault(['name' => 'Tất cả khóa học']); }
}
