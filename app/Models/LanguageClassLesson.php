<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LanguageClassLesson extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'lesson_date' => 'date',
            'attendance_marked_at' => 'datetime',
        ];
    }

    public function languageClass()
    {
        return $this->belongsTo(LanguageClass::class, 'language_class_id')->withTrashed();
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_user_id')->withTrashed();
    }

    public function attendanceMarker()
    {
        return $this->belongsTo(User::class, 'attendance_marked_by')->withTrashed();
    }

    public function attendances()
    {
        return $this->hasMany(LanguageClassAttendance::class, 'language_class_lesson_id');
    }
}
