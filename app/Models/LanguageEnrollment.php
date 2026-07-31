<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LanguageEnrollment extends Model { protected $guarded=[]; protected $casts=['enrolled_at'=>'date','ended_at'=>'date']; public function student(){return $this->belongsTo(LanguageStudent::class,'language_student_id')->withTrashed();} public function languageClass(){return $this->belongsTo(LanguageClass::class,'language_class_id')->withTrashed();} public function monthlyProgress(){return $this->hasMany(LanguageStudentMonthlyProgress::class,'language_enrollment_id');} public function scores(){return $this->hasMany(LanguageStudentScore::class,'language_enrollment_id');} public function attendances(){return $this->hasMany(LanguageClassAttendance::class,'language_enrollment_id');} }
