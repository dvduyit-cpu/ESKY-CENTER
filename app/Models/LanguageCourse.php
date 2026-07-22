<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class LanguageCourse extends Model { use SoftDeletes; protected $guarded=[]; protected $casts=['active'=>'boolean','tuition'=>'decimal:2','duration_hours'=>'decimal:2']; }
