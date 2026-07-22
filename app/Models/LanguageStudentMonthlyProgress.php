<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LanguageStudentMonthlyProgress extends Model
{
    protected $table = 'language_student_monthly_progress';
    protected $guarded = [];
    protected $casts = ['month'=>'date','participation_score'=>'decimal:2','homework_score'=>'decimal:2'];
    public function enrollment(){return $this->belongsTo(LanguageEnrollment::class,'language_enrollment_id');}
    public function teacher(){return $this->belongsTo(User::class,'teacher_user_id');}
}
