<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int|array|null $userId = null,
        public readonly string $message = 'Có thông báo mới.',
        public readonly ?string $url = null,
    ) {}

    public function broadcastOn(): array
    {
        if ($this->userId === null) return [new PrivateChannel('system.notifications')];
        $ids = is_array($this->userId) ? $this->userId : [$this->userId];
        return array_map(fn ($id) => new PrivateChannel('users.'.(int)$id), $ids);
    }

    public function broadcastAs(): string
    {
        return 'notification.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'message'=>$this->message,
            'url'=>$this->url,
            'sent_at'=>now()->utc()->toIso8601String(),
        ];
    }
}
