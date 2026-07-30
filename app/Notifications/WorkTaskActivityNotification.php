<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkTaskActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $actorName,
        private readonly string $taskTitle,
        private readonly string $eventTitle,
        private readonly string $eventDescription,
        private readonly ?string $taskUrl = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('[E-SKY] '.$this->eventTitle.': '.$this->taskTitle)
            ->greeting('Xin chào '.$notifiable->name.',')
            ->line($this->actorName.' '.$this->eventDescription)
            ->line('Công việc: '.$this->taskTitle);

        if ($this->taskUrl) {
            $message->action('Mở công việc', $this->taskUrl);
        }

        return $message
            ->line('Email được gửi tự động từ hệ thống E-SKY.')
            ->salutation('Trân trọng.');
    }
}
