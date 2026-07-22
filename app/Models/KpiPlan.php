<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiPlan extends Model
{
    use HasFactory;

    protected $fillable = ['year', 'name', 'status', 'settlement_scope', 'note', 'created_by'];

    public function targets(): HasMany { return $this->hasMany(KpiTarget::class, 'plan_id'); }
}
