<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code', 'name', 'normalized_name', 'category', 'conversion_quantity',
        'conversion_kpi', 'conversion_mode', 'default_excess_rate', 'active', 'note',
    ];

    protected $casts = [
        'conversion_quantity' => 'decimal:2', 'conversion_kpi' => 'decimal:2',
        'default_excess_rate' => 'decimal:2', 'active' => 'boolean',
    ];

    public function targets(): HasMany { return $this->hasMany(KpiTarget::class); }
    public function records(): HasMany { return $this->hasMany(KpiRecord::class); }

    public function conversionText(): string
    {
        $mode = $this->conversion_mode === 'full_group' ? 'đủ nhóm' : 'tỷ lệ';
        return rtrim(rtrim(number_format((float) $this->conversion_quantity, 2, '.', ''), '0'), '.')
            .' đầu vào = '.rtrim(rtrim(number_format((float) $this->conversion_kpi, 2, '.', ''), '0'), '.')
            .' KPI ('.$mode.')';
    }
}
