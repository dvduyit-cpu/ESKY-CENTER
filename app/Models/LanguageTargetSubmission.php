<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LanguageTargetSubmission extends Model
{
    public const SOURCE_LABELS = [
        'walk_in' => 'Tự đến',
        'fanpage' => 'Fanpage',
        'zalo' => 'Zalo',
        'zalo_oa' => 'Zalo OA',
        'web' => 'Web',
        'hotline' => 'Hotline',
    ];

    protected $guarded=[];
    public function sourceLabel(): string{return self::SOURCE_LABELS[$this->source]??($this->source?:'Chưa chọn');}
    public function course(){return $this->belongsTo(LanguageCourse::class,'language_course_id')->withTrashed();}
    public function submitter(){return $this->belongsTo(User::class,'submitted_by')->withTrashed();}
    public function lead(){return $this->belongsTo(LanguageLead::class,'language_lead_id')->withTrashed();}
}
