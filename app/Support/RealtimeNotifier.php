<?php

namespace App\Support;

use App\Events\NotificationChanged;
use Illuminate\Support\Facades\Log;
use Throwable;

class RealtimeNotifier
{
    private static bool $unavailable = false;

    public static function user(int $userId, string $message = 'Có thông báo mới.'): void
    {
        self::send(new NotificationChanged($userId, $message));
    }

    public static function users(iterable $userIds, string $message = 'Có thông báo mới.'): void
    {
        $ids = collect($userIds)->map(fn ($id)=>(int)$id)->filter()->unique()->values()->all();
        foreach (array_chunk($ids, 90) as $chunk) self::send(new NotificationChanged($chunk, $message));
    }

    public static function system(string $message = 'Dữ liệu hệ thống vừa được cập nhật.'): void
    {
        self::send(new NotificationChanged(null, $message));
    }

    private static function send(NotificationChanged $event): void
    {
        if (self::$unavailable || config('broadcasting.default') === 'null') return;

        try {
            event($event);
        } catch (Throwable $exception) {
            self::$unavailable = true;
            Log::warning('Không thể phát thông báo tức thời; nghiệp vụ vẫn tiếp tục.', [
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
