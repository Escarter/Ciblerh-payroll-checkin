<?php

namespace App\Jobs;

use App\Models\ScheduledReport;
use App\Models\DownloadJob;
use App\Services\ReportGenerationService;
use App\Mail\ScheduledReportMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ScheduledReportJob implements ShouldQueue
{
    use Queueable;

    public $scheduledReport;

    /**
     * Create a new job instance.
     */
    public function __construct(ScheduledReport $scheduledReport)
    {
        $this->scheduledReport = $scheduledReport;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Processing scheduled report', [
                'scheduled_report_id' => $this->scheduledReport->id,
                'name' => $this->scheduledReport->name,
            ]);

            // Create a download job using the scheduled report configuration
            $downloadJob = DownloadJob::create([
                'uuid' => Str::uuid(),
                'user_id' => $this->scheduledReport->user_id,
                'job_type' => $this->scheduledReport->job_type,
                'report_format' => $this->scheduledReport->report_format,
                'filters' => $this->scheduledReport->filters,
                'report_config' => $this->scheduledReport->report_config ?? [],
                'status' => DownloadJob::STATUS_PENDING,
                'expires_at' => now()->addDays(7),
                'metadata' => [
                    'scheduled_report_id' => $this->scheduledReport->id,
                    'scheduled_report_name' => $this->scheduledReport->name,
                    'created_by' => 'scheduled_report',
                ]
            ]);

            // Dispatch the appropriate job to generate the report
            ReportGenerationService::dispatchJob($downloadJob);

            // Dispatch a delayed job to check and send emails
            // This job will check if the report is ready and send emails
            // We delay it to give the report generation job time to complete
            \App\Jobs\SendScheduledReportEmailJob::dispatch($this->scheduledReport, $downloadJob)
                ->delay(now()->addMinutes(2));
            
            Log::info('Scheduled report job dispatched', [
                'scheduled_report_id' => $this->scheduledReport->id,
                'download_job_id' => $downloadJob->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Scheduled report job failed', [
                'scheduled_report_id' => $this->scheduledReport->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Update scheduled report with error
            $this->scheduledReport->update([
                'last_error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Send report emails to recipients
     */
    private function sendReportEmails(DownloadJob $downloadJob): void
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
                    $downloadJob
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
