<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LanguageTargetSubmission extends Model
{
    protected $guarded=[];
    public function course(){return $this->belongsTo(LanguageCourse::class,'language_course_id');}
    public function submitter(){return $this->belongsTo(User::class,'submitted_by');}
    public function lead(){return $this->belongsTo(LanguageLead::class,'language_lead_id');}
}
