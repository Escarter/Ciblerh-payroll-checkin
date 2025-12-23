<?php

namespace App\Mail;

use App\Models\ScheduledReport;
use App\Models\DownloadJob;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ScheduledReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public $scheduledReport;
    public $downloadJob;

    /**
     * Create a new message instance.
     */
    public function __construct(ScheduledReport $scheduledReport, DownloadJob $downloadJob)
    {
        $this->scheduledReport = $scheduledReport;
        $this->downloadJob = $downloadJob;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('scheduled_reports.email_subject', ['name' => $this->scheduledReport->name]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'email.scheduled-report',
            with: [
                'scheduledReport' => $this->scheduledReport,
                'downloadJob' => $this->downloadJob,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        if (!$this->downloadJob->file_path || !Storage::disk('public')->exists($this->downloadJob->file_path)) {
            return [];
        }

        return [
            Attachment::fromStorageDisk('public', $this->downloadJob->file_path)
                ->as($this->downloadJob->file_name)
        ];
    }
}
