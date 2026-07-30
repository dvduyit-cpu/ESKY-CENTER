<?php

namespace App\Support;

use App\Events\NotificationChanged;
use Illuminate\Support\Facades\Log;
use Throwable;

class RealtimeNotifier
{
    private static float $unavailableUntil = 0.0;
    private static ?bool $reachable = null;
    private static float $reachabilityCheckedUntil = 0.0;

    public static function user(int $userId, string $message = 'Có thông báo mới.', ?string $url = null): void
    {
        self::send(new NotificationChanged($userId, $message, $url));
    }

    public static function users(iterable $userIds, string $message = 'Có thông báo mới.', ?string $url = null): void
    {
        $ids = collect($userIds)->map(fn ($id)=>(int)$id)->filter()->unique()->values()->all();
        foreach (array_chunk($ids, 90) as $chunk) self::send(new NotificationChanged($chunk, $message, $url));
    }

    public static function system(string $message = 'Dữ liệu hệ thống vừa được cập nhật.', ?string $url = null): void
    {
        self::send(new NotificationChanged(null, $message, $url));
    }

    private static function send(NotificationChanged $event): void
    {
        if (self::isUnavailable() || config('broadcasting.default') === 'null') return;
        if (! self::broadcastServerIsReachable()) return;

        app()->terminating(function () use ($event): void {
            if (self::isUnavailable()) return;

            try {
                event($event);
            } catch (Throwable $exception) {
                self::$unavailableUntil = microtime(true) + 5;
                self::$reachable = null;
                self::$reachabilityCheckedUntil = 0.0;
                Log::warning('Không thể phát thông báo tức thời; nghiệp vụ vẫn tiếp tục.', [
                    'error' => $exception->getMessage(),
                ]);
            }
        });
    }

    private static function broadcastServerIsReachable(): bool
    {
        if (config('broadcasting.default') !== 'reverb') return true;
        if (! function_exists('stream_socket_client')) return true;
        if (self::$reachable !== null && self::$reachabilityCheckedUntil > microtime(true)) {
            return self::$reachable;
        }

        $host = trim((string) config('broadcasting.connections.reverb.options.host'));
        $port = (int) config('broadcasting.connections.reverb.options.port');
        if ($host === '' || $port <= 0) {
            self::$unavailableUntil = microtime(true) + 5;
            self::$reachable = false;
            self::$reachabilityCheckedUntil = self::$unavailableUntil;
            return false;
        }

        $targetHost = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? '['.$host.']' : $host;
        $connectTimeout = in_array(strtolower($host), ['127.0.0.1', 'localhost', '::1'], true)
            ? 0.02
            : 0.2;
        $errorCode = 0;
        $errorMessage = '';
        $socket = @stream_socket_client(
            'tcp://'.$targetHost.':'.$port,
            $errorCode,
            $errorMessage,
            $connectTimeout,
            STREAM_CLIENT_CONNECT,
        );

        if (is_resource($socket)) {
            fclose($socket);
            self::$reachable = true;
            self::$reachabilityCheckedUntil = microtime(true) + 1;
            return true;
        }

        self::$unavailableUntil = microtime(true) + 5;
        self::$reachable = false;
        self::$reachabilityCheckedUntil = self::$unavailableUntil;
        Log::warning('Reverb không sẵn sàng; đã bỏ qua phát chuông để thao tác không bị chậm.', [
            'host' => $host,
            'port' => $port,
            'error' => $errorMessage ?: 'Không thể kết nối.',
            'code' => $errorCode,
        ]);

        return false;
    }

    private static function isUnavailable(): bool
    {
        return self::$unavailableUntil > microtime(true);
    }
}
