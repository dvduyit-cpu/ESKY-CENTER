<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdministrativeWeeklyReportItem extends Model
{
    protected $fillable = ['report_id','type','work_area','content','normalized_content','quality_score','review_payload','sort_order'];
    protected function casts(): array { return ['quality_score'=>'integer','review_payload'=>'array']; }
    public function report(): BelongsTo { return $this->belongsTo(AdministrativeWeeklyReport::class, 'report_id'); }
}
