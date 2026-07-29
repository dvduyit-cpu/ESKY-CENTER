<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\WorkTask;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class WorkTaskAssigned extends Notification
{
    use Queueable;

    public function __construct(
        private readonly User $assigner,
        private readonly Collection $tasks,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var WorkTask $firstTask */
        $firstTask = $this->tasks->first();
        $priority = match ($firstTask->priority) {
            'high' => 'Cao',
            'low' => 'Thấp',
            default => 'Bình thường',
        };

        $mail = (new MailMessage)
            ->subject('[E-SKY] Công việc mới: '.$firstTask->title)
            ->greeting('Xin chào '.$notifiable->name.',')
            ->line($this->assigner->name.' vừa giao cho bạn một công việc mới.')
            ->line('**Công việc:** '.$firstTask->title)
            ->line('**Mức độ ưu tiên:** '.$priority);

        if ($this->tasks->count() > 1) {
            /** @var WorkTask $lastTask */
            $lastTask = $this->tasks->last();
            $mail->line('**Lặp lại:** '.$this->tasks->count().' kỳ hàng tháng')
                ->line('**Thời gian:** '.$firstTask->due_at->format('H:i d/m/Y').' – '.$lastTask->due_at->format('H:i d/m/Y'));
        } else {
            $mail->line('**Hạn hoàn thành:** '.$firstTask->due_at->format('H:i d/m/Y'));
        }

        if (filled($firstTask->description)) {
            $mail->line('**Nội dung:** '.$firstTask->description);
        }

        return $mail
            ->action('Mở công việc', route('tasks.show', $firstTask))
            ->line('Vui lòng đăng nhập E-SKY để xác nhận nhận việc và cập nhật tiến độ.');
    }
}
