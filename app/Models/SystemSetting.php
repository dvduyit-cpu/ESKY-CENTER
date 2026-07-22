<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    public static function valueOf(string $key, mixed $default = null): mixed
    {
        return static::query()->whereKey($key)->value('value') ?? $default;
    }
}
