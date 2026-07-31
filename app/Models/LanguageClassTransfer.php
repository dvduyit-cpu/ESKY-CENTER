<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LanguageClassTransfer extends Model
{
    protected $guarded = [];

    protected $casts = [
        'effective_date' => 'date',
        'sessions_used' => 'integer',
        'source_payable_amount' => 'decimal:2',
        'source_paid_amount' => 'decimal:2',
        'source_credit_amount' => 'decimal:2',
        'used_amount' => 'decimal:2',
        'transferred_amount' => 'decimal:2',
        'applied_amount' => 'decimal:2',
        'surplus_amount' => 'decimal:2',
    ];

    public function student() { return $this->belongsTo(LanguageStudent::class, 'language_student_id')->withTrashed(); }
    public function fromClass() { return $this->belongsTo(LanguageClass::class, 'from_language_class_id')->withTrashed(); }
    public function toClass() { return $this->belongsTo(LanguageClass::class, 'to_language_class_id')->withTrashed(); }
    public function fromEnrollment() { return $this->belongsTo(LanguageEnrollment::class, 'from_enrollment_id'); }
    public function toEnrollment() { return $this->belongsTo(LanguageEnrollment::class, 'to_enrollment_id'); }
    public function fromCharge() { return $this->belongsTo(LanguageTuitionCharge::class, 'from_tuition_charge_id'); }
    public function toCharge() { return $this->belongsTo(LanguageTuitionCharge::class, 'to_tuition_charge_id'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by')->withTrashed(); }
}
