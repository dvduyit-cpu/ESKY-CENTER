<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\WorkTask;
use App\Models\SystemSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Schema;
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

        $logoPath = Schema::hasTable('system_settings') ? SystemSetting::valueOf('logo_path') : null;
        if (! $logoPath || ! is_file(public_path($logoPath))) {
            $logoPath = 'uploads/branding/logo-20260722101948.png';
        }

        if ($this->tasks->count() > 1) {
            /** @var WorkTask $lastTask */
            $lastTask = $this->tasks->last();
            $schedule = $this->tasks->count().' kỳ hàng tháng, từ '
                .$firstTask->due_at->format('H:i d/m/Y').' đến '.$lastTask->due_at->format('H:i d/m/Y');
        } else {
            $schedule = $firstTask->due_at->format('H:i d/m/Y');
        }

        return (new MailMessage)
            ->subject('[E-SKY] Công việc mới: '.$firstTask->title)
            ->view('emails.work-task-assigned', [
                'recipient'=>$notifiable,
                'assigner'=>$this->assigner,
                'task'=>$firstTask,
                'priority'=>$priority,
                'schedule'=>$schedule,
                'taskUrl'=>route('tasks.show', $firstTask),
                'logoUrl'=>asset($logoPath),
            ]);
    }
}
