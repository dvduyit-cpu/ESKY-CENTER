<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class LanguageClass extends Model { use SoftDeletes; protected $guarded=[]; protected $casts=['start_date'=>'date','expected_end_date'=>'date']; public function program(){return $this->belongsTo(LanguageProgram::class,'language_program_id');} public function level(){return $this->belongsTo(LanguageLevel::class,'language_level_id');} public function teacher(){return $this->belongsTo(User::class,'teacher_user_id');} public function enrollments(){return $this->hasMany(LanguageEnrollment::class);} }
