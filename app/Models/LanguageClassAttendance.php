<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LanguageClassAttendance extends Model
{
    protected $guarded = [];

    public function lesson()
    {
        return $this->belongsTo(LanguageClassLesson::class, 'language_class_lesson_id');
    }

    public function enrollment()
    {
        return $this->belongsTo(LanguageEnrollment::class, 'language_enrollment_id');
    }

    public function marker()
    {
        return $this->belongsTo(User::class, 'marked_by')->withTrashed();
    }
}
