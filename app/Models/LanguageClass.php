<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class LanguageClass extends Model {
 use SoftDeletes; protected $guarded=[];
 protected $casts=['start_date'=>'date','expected_end_date'=>'date','default_tuition'=>'decimal:2','completed_sessions'=>'integer','completion_requested_at'=>'datetime','completed_at'=>'datetime'];
 public function course(){return $this->belongsTo(LanguageCourse::class,'language_course_id')->withTrashed();}
 public function program(){return $this->belongsTo(LanguageProgram::class,'language_program_id')->withTrashed();}
 public function level(){return $this->belongsTo(LanguageLevel::class,'language_level_id')->withTrashed();}
 public function discountPolicy(){return $this->belongsTo(LanguageDiscountPolicy::class,'language_discount_policy_id')->withTrashed();}
 public function teacher(){return $this->belongsTo(User::class,'teacher_user_id')->withTrashed();}
 public function completionRequester(){return $this->belongsTo(User::class,'completion_requested_by')->withTrashed();}
 public function completer(){return $this->belongsTo(User::class,'completed_by')->withTrashed();}
 public function enrollments(){return $this->hasMany(LanguageEnrollment::class);}
 public function tuitionCharges(){return $this->hasMany(LanguageTuitionCharge::class,'language_class_id');}
 public function lessons(){return $this->hasMany(LanguageClassLesson::class);}
 public function isCompletionDue():bool{return ($this->expected_end_date&&$this->expected_end_date->isPast())||($this->expected_sessions>0&&$this->completed_sessions>=$this->expected_sessions);}
}
