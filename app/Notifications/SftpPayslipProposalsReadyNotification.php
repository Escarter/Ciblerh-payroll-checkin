<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SftpPayslipProposalsReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $proposalCount;

    /**
     * Create a new notification instance.
     */
    public function __construct(int $proposalCount)
    {
        $this->proposalCount = $proposalCount;
        $this->queue = 'notifications';
        $this->delay = 0;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.sftp_proposals_ready_subject'))
            ->greeting(__('notifications.hello', ['name' => $notifiable->first_name]))
            ->line(__('notifications.sftp_proposals_ready_message', ['count' => $this->proposalCount]))
            ->action(__('notifications.review_proposals'), route('portal.payslips.sftp-validator'))
            ->line(__('notifications.thanks'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'sftp_proposals_ready',
            'proposal_count' => $this->proposalCount,
            'action_url' => route('portal.payslips.sftp-validator'),
        ];
    }
}
