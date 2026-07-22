<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class LanguageCollaborator extends Model { use SoftDeletes; protected $guarded=[]; protected $casts=['active'=>'boolean']; public function personnel(){return $this->belongsTo(Personnel::class);} public function user(){return $this->hasOne(User::class)->withTrashed();} public function leads(){return $this->hasMany(LanguageLead::class);} }
