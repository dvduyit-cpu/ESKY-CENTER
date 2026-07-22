<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExcessPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_key', 'payment_kind', 'personnel_id', 'course_id', 'year', 'period_type',
        'period_value', 'target_quantity', 'actual_quantity', 'excess_quantity',
        'revenue_amount', 'payment_rate', 'payment_amount', 'status', 'approved_at',
        'approved_by', 'paid_at', 'paid_by', 'note', 'calculated_by',
    ];

    protected $casts = [
        'target_quantity' => 'decimal:2', 'actual_quantity' => 'decimal:2',
        'excess_quantity' => 'decimal:2', 'revenue_amount' => 'decimal:2',
        'payment_rate' => 'decimal:2', 'payment_amount' => 'decimal:2',
        'approved_at' => 'datetime', 'paid_at' => 'datetime',
    ];

    public function personnel(): BelongsTo { return $this->belongsTo(Personnel::class); }
    public function course(): BelongsTo { return $this->belongsTo(Course::class); }
}
