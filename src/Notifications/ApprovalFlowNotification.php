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
        $mailMessage = (new MailMessage)
            ->subject($this->title);

        $mailMessage->line($this->task->flow->name);

        $mailMessage->line(__('approval-flow::mail.request_from', ['name' => $this->task->user->name, 'flow' => $this->task->flow->name]));
        $mailMessage->line(__('approval-flow::mail.check'));

        // タスクのメッセージがある場合は本文に追加
        if (!empty($this->task->msg)) {
            $mailMessage->line($this->task->msg);
        }

        // タスクの詳細情報を追加（必要に応じて）
        if (isset($this->task->status)) {
            $mailMessage->line(__('approval-flow::mail.labels.status') . __('approval-flow::mail.status.' . $this->task->status));
        }



        return $mailMessage
            ->action(__('approval-flow::mail.confirm'), $this->task->link ?? url('/'))
            ->line(__('approval-flow::mail.ignore'))
            ->line(__('approval-flow::mail.thank'))
            ->salutation(config('approval-flow.notification.salutation'));

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
