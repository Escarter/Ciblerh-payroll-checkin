<?php

namespace App\Notifications;

use App\Models\ImportJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ImportCompletedNotification extends Notification implements ShouldQueue
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
        $message = (new MailMessage)
            ->subject(__('import_jobs.import_completed_subject', [
                'type' => __('common.' . $this->importJob->import_type)
            ]))
            ->greeting(__('import_jobs.import_completed_greeting', [
                'name' => $notifiable->name
            ]))
            ->line(__('import_jobs.import_completed_message', [
                'type' => __('common.' . $this->importJob->import_type),
                'total' => $this->importJob->total_rows,
                'successful' => $this->importJob->successful_imports,
                'failed' => $this->importJob->failed_imports
            ]));

        if ($this->importJob->failed_imports > 0) {
            $message->line(__('import_jobs.import_completed_with_errors'));
        }

        return $message
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
            'type' => 'import_completed',
            'import_type' => $this->importJob->import_type,
            'total_rows' => $this->importJob->total_rows,
            'successful_imports' => $this->importJob->successful_imports,
            'failed_imports' => $this->importJob->failed_imports,
            'message' => __('import_jobs.import_completed_notification', [
                'type' => __('common.' . $this->importJob->import_type),
                'successful' => $this->importJob->successful_imports,
                'total' => $this->importJob->total_rows
            ])
        ];
    }
}