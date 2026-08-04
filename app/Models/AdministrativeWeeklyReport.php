<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdministrativeWeeklyReport extends Model
{
    protected $fillable = ['period_id','user_id','week_start','week_end','due_date','status','quality_score','review_payload','submitted_at','reviewed_by','reviewed_at','admin_note'];

    protected function casts(): array
    {
        return ['week_start'=>'date','week_end'=>'date','due_date'=>'date','review_payload'=>'array','submitted_at'=>'datetime','reviewed_at'=>'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class)->withTrashed(); }
    public function period(): BelongsTo { return $this->belongsTo(AdministrativeWeeklyPeriod::class, 'period_id'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by')->withTrashed(); }
    public function items(): HasMany { return $this->hasMany(AdministrativeWeeklyReportItem::class, 'report_id')->orderBy('sort_order'); }
}
