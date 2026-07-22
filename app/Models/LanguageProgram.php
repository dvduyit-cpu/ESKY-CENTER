<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class LanguageProgram extends Model { use SoftDeletes; protected $guarded=[]; protected $casts=['active'=>'boolean']; public function levels(){return $this->hasMany(LanguageLevel::class);} public function classes(){return $this->hasMany(LanguageClass::class);} }
