<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LanguageEnrollment extends Model { protected $guarded=[]; protected $casts=['enrolled_at'=>'date']; public function student(){return $this->belongsTo(LanguageStudent::class,'language_student_id');} public function languageClass(){return $this->belongsTo(LanguageClass::class,'language_class_id');} }
