<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class LanguageLevel extends Model { use SoftDeletes; protected $guarded=[]; public function program(){return $this->belongsTo(LanguageProgram::class,'language_program_id');} }
