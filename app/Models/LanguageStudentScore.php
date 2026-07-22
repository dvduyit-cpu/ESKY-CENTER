<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LanguageStudentScore extends Model
{
    protected $guarded = [];
    protected $casts = ['test_date'=>'date','score'=>'decimal:2','max_score'=>'decimal:2'];
    public function enrollment(){return $this->belongsTo(LanguageEnrollment::class,'language_enrollment_id');}
    public function teacher(){return $this->belongsTo(User::class,'teacher_user_id');}
}
