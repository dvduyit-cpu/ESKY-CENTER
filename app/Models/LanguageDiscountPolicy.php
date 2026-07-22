<?php
namespace App\Models; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\SoftDeletes;
class LanguageDiscountPolicy extends Model { use SoftDeletes; protected $guarded=[]; protected $casts=['active'=>'boolean','percentage'=>'decimal:2','starts_at'=>'date','ends_at'=>'date']; }
