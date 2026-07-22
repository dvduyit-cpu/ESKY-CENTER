<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model;
class LanguageTuitionPayment extends Model { protected $guarded=[]; protected $casts=['paid_at'=>'datetime','confirmed_at'=>'datetime','amount'=>'decimal:2']; public function charge(){return $this->belongsTo(LanguageTuitionCharge::class,'language_tuition_charge_id');} }
