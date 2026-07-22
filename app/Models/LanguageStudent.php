<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class LanguageStudent extends Model { use SoftDeletes; protected $guarded=[]; protected $casts=['date_of_birth'=>'date','registered_at'=>'date','official_enrollment_date'=>'date']; public function guardians(){return $this->hasMany(LanguageGuardian::class);} public function enrollments(){return $this->hasMany(LanguageEnrollment::class);} public function course(){return $this->belongsTo(LanguageCourse::class,'language_course_id');} public function discountPolicy(){return $this->belongsTo(LanguageDiscountPolicy::class,'language_discount_policy_id');} }
