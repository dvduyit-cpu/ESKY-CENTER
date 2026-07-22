<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class LanguageCourse extends Model { use SoftDeletes; protected $guarded=[]; protected $casts=['active'=>'boolean','tuition'=>'decimal:2','duration_hours'=>'decimal:2']; public function program(){return $this->belongsTo(LanguageProgram::class,'language_program_id');} public function level(){return $this->belongsTo(LanguageLevel::class,'language_level_id');} public function classes(){return $this->hasMany(LanguageClass::class,'language_course_id');} }
