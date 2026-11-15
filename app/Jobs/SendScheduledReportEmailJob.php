<?php

namespace App\Jobs;

use App\Models\ScheduledReport;
use App\Models\DownloadJob;
use App\Mail\ScheduledReportMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendScheduledReportEmailJob implements ShouldQueue
{
    use Queueable;

    public $scheduledReport;
    public $downloadJob;

    /**
     * Create a new job instance.
     */
    public function __construct(ScheduledReport $scheduledReport, DownloadJob $downloadJob)
    {
        $this->scheduledReport = $scheduledReport;
        $this->downloadJob = $downloadJob;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Refresh the download job to get latest status
        $this->downloadJob->refresh();

        // Check if report is ready
        if ($this->downloadJob->status === DownloadJob::STATUS_COMPLETED) {
            // Report is ready, send emails
            $this->sendReportEmails();
        } elseif ($this->downloadJob->status === DownloadJob::STATUS_FAILED) {
            // Report generation failed
            Log::error('Scheduled report generation failed', [
                'scheduled_report_id' => $this->scheduledReport->id,
                'download_job_id' => $this->downloadJob->id,
                'error' => $this->downloadJob->error_message,
            ]);

            $this->scheduledReport->update([
                'last_error' => 'Report generation failed: ' . ($this->downloadJob->error_message ?? 'Unknown error'),
            ]);
        } elseif ($this->downloadJob->status === DownloadJob::STATUS_PROCESSING || $this->downloadJob->status === DownloadJob::STATUS_PENDING) {
            // Report is still processing, retry after 1 minute
            $this->release(60); // Release back to queue for 60 seconds
        }
    }

    /**
     * Send report emails to recipients
     */
    private function sendReportEmails(): void
    {
        $recipients = $this->scheduledReport->recipients ?? [];
        
        if (empty($recipients)) {
            Log::warning('No recipients configured for scheduled report', [
                'scheduled_report_id' => $this->scheduledReport->id,
            ]);
            return;
        }

        foreach ($recipients as $email) {
            try {
                // Set SMTP credentials
                setSavedSmtpCredentials();

                Mail::to($email)->send(new ScheduledReportMail(
                    $this->scheduledReport,
                    $this->downloadJob
                ));

                Log::info('Scheduled report email sent', [
                    'scheduled_report_id' => $this->scheduledReport->id,
                    'recipient' => $email,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send scheduled report email', [
                    'scheduled_report_id' => $this->scheduledReport->id,
                    'recipient' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
