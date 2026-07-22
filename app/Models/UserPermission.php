<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'module_id', 'can_view', 'can_create', 'can_update', 'can_delete', 'can_export',
    ];

    protected $casts = [
        'can_view' => 'boolean', 'can_create' => 'boolean', 'can_update' => 'boolean',
        'can_delete' => 'boolean', 'can_export' => 'boolean',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function module(): BelongsTo { return $this->belongsTo(Module::class); }
}
