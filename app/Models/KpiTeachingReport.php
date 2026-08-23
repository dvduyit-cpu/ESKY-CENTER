<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiTeachingReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'plan_id', 'kpi_target_id', 'personnel_id', 'reported_by',
        'report_year', 'report_month', 'reported_teaching_load', 'note', 'report_rows',
    ];

    protected $casts = [
        'reported_teaching_load' => 'decimal:2',
        'report_rows' => 'array',
    ];

    public function plan(): BelongsTo { return $this->belongsTo(KpiPlan::class, 'plan_id'); }
    public function target(): BelongsTo { return $this->belongsTo(KpiTarget::class, 'kpi_target_id'); }
    public function personnel(): BelongsTo { return $this->belongsTo(Personnel::class)->withTrashed(); }
    public function reporter(): BelongsTo { return $this->belongsTo(User::class, 'reported_by')->withTrashed(); }
}
