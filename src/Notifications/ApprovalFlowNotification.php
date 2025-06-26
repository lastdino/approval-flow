<?php

namespace Lastdino\ApprovalFlow\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Lastdino\ApprovalFlow\Models\ApprovalFlowTask;

class ApprovalFlowNotification extends Notification
{
    use Queueable;

    protected ApprovalFlowTask $task;
    protected string $title;


    /**
     * Create a new notification instance.
     */
    public function __construct(ApprovalFlowTask $task, string $title)
    {
        $this->task = $task;
        $this->title = $title;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database','broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title)
            ->line($this->task->msg ?? '承認フロー通知です。')
            ->action(__('確認する'), $this->task->link ?? url('/'))
            ->line(__('このメッセージに心当たりがない場合は、このメールを無視してください。'))
            ->line('ありがとうございます')
            ->salutation('HSK');
    }


    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'task_id' => $this->task->id,
            'message' => $this->task->msg ?? '',
            'link' => $this->task->link,
            'status' => $this->task->status,
        ];
    }

    /**
     * 通知のブロードキャスト表現の取得
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([]);
    }
}
