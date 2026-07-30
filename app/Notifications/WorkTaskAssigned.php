<?php

namespace App\Notifications;

use App\Models\User;
use App\Models\WorkTask;
use App\Models\SystemSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

class WorkTaskAssigned extends Notification implements ShouldQueue
{
    use Queueable;

    private readonly string $logoPath;

    public function __construct(
        private readonly User $assigner,
        private readonly Collection $tasks,
    ) {
        $logoPath = Schema::hasTable('system_settings') ? SystemSetting::valueOf('logo_path') : null;
        $this->logoPath = $logoPath && is_file(public_path($logoPath))
            ? $logoPath
            : 'uploads/branding/logo-20260722101948.png';
    }

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

        if ($this->tasks->count() > 1) {
            /** @var WorkTask $lastTask */
            $lastTask = $this->tasks->last();
            /** @var WorkTask $secondTask */
            $secondTask = $this->tasks->get(1);
            $frequency = $firstTask->due_at->diffInDays($secondTask->due_at) <= 8
                ? 'hàng tuần'
                : 'hàng tháng';
            $schedule = $this->tasks->count().' kỳ '.$frequency.', từ '
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
                'logoUrl'=>asset($this->logoPath),
            ]);
    }
}
