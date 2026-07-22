<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    public static function log(
        string $module,
        string $action,
        string $description,
        ?Model $subject = null,
        ?array $before = null,
        ?array $after = null,
    ): void {
        $request = request();

        ActivityLog::create([
            'user_id' => auth()->id(),
            'module' => $module,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'old_values' => $before,
            'new_values' => $after,
            'ip_address' => $request?->ip(),
            'user_agent' => mb_substr((string) $request?->userAgent(), 0, 500),
            'created_at' => now(),
        ]);
    }
}
