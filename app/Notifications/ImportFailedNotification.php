<?php

namespace App\Notifications;

use App\Models\ImportJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ImportFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $importJob;

    /**
     * Create a new notification instance.
     */
    public function __construct(ImportJob $importJob)
    {
        $this->importJob = $importJob;
    }

    /**
     * Get the notification's delivery channels.
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
            ->subject(__('import_jobs.import_failed_subject', [
                'type' => __('common.' . $this->importJob->import_type)
            ]))
            ->greeting(__('import_jobs.import_failed_greeting', [
                'name' => $notifiable->name
            ]))
            ->line(__('import_jobs.import_failed_message', [
                'type' => __('common.' . $this->importJob->import_type),
                'error' => $this->importJob->error_message
            ]))
            ->action(__('common.view_details'), url('/portal/import-jobs'))
            ->line(__('common.thank_you'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'import_job_id' => $this->importJob->id,
            'type' => 'import_failed',
            'import_type' => $this->importJob->import_type,
            'error_message' => $this->importJob->error_message,
            'message' => __('import_jobs.import_failed_notification', [
                'type' => __('common.' . $this->importJob->import_type)
            ])
        ];
    }
}