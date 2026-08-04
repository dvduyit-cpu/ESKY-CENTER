<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdministrativeWeeklyCompilation extends Model
{
    protected $fillable = ['week_start','week_end','content','official_content','source_item_ids','duplicate_groups','compiled_by','compiled_at'];
    protected function casts(): array { return ['week_start'=>'date','week_end'=>'date','source_item_ids'=>'array','duplicate_groups'=>'array','compiled_at'=>'datetime']; }
    public function compiler(): BelongsTo { return $this->belongsTo(User::class, 'compiled_by')->withTrashed(); }
}
