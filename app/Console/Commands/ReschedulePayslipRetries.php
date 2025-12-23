<?php

namespace App\Console\Commands;

use App\Models\Payslip;
use App\Jobs\RetryPayslipEmailJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ReschedulePayslipRetries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payslips:reschedule-retries 
                            {--max-retry-count= : Maximum retry count to reschedule (default: config max - 1)}
                            {--min-retry-count=0 : Minimum retry count to reschedule (default: 0)}
                            {--process-id= : Only reschedule payslips for a specific process ID}
                            {--dry-run : Show what would be rescheduled without actually scheduling}
                            {--immediate : Schedule retries immediately without delay (for testing)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reschedule retry jobs for payslips that were only retried once or partially retried';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $maxRetryCount = $this->option('max-retry-count');
        $minRetryCount = (int) $this->option('min-retry-count');
        $processId = $this->option('process-id');
        $dryRun = $this->option('dry-run');
        $immediate = $this->option('immediate');

        // Get max retries from config if not specified
        if ($maxRetryCount === null) {
            $configMaxRetries = config('ciblerh.email_retry_attempts', 3);
            $maxRetryCount = $configMaxRetries - 1; // Default to one less than max
        } else {
            $maxRetryCount = (int) $maxRetryCount;
        }

        $this->info("Finding payslips with retry count between {$minRetryCount} and {$maxRetryCount}...");

        // Build query
        $query = Payslip::where('email_sent_status', Payslip::STATUS_FAILED)
            ->where('encryption_status', Payslip::STATUS_SUCCESSFUL) // Only if encryption succeeded
            ->whereNotNull('file') // Must have a file
            ->whereBetween('email_retry_count', [$minRetryCount, $maxRetryCount]);

        // Filter by process ID if specified
        if ($processId) {
            $query->where('send_payslip_process_id', $processId);
        }

        $payslips = $query->get();

        if ($payslips->isEmpty()) {
            $this->info('No payslips found matching the criteria.');
            return Command::SUCCESS;
        }

        $this->info("Found {$payslips->count()} payslip(s) to reschedule.");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No jobs will be scheduled');
            $this->newLine();
        }

        $scheduled = 0;
        $skipped = 0;
        $tableData = [];

        foreach ($payslips as $payslip) {
            // Check if file still exists
            if (!Storage::disk('modified')->exists($payslip->file)) {
                $this->warn("Skipping payslip ID {$payslip->id} - file not found: {$payslip->file}");
                $skipped++;
                continue;
            }

            // Check if employee exists and has email
            $employee = \App\Models\User::find($payslip->employee_id);
            if (empty($employee)) {
                $this->warn("Skipping payslip ID {$payslip->id} - employee not found");
                $skipped++;
                continue;
            }

            if (empty($employee->email) && empty($employee->alternative_email)) {
                $this->warn("Skipping payslip ID {$payslip->id} - no email address");
                $skipped++;
                continue;
            }

            // Check if email notifications are enabled
            if ($employee->receive_email_notifications === false) {
                $this->warn("Skipping payslip ID {$payslip->id} - email notifications disabled");
                $skipped++;
                continue;
            }

            // Check if email has bounced
            if ($employee->email_bounced) {
                $this->warn("Skipping payslip ID {$payslip->id} - email has bounced");
                $skipped++;
                continue;
            }

            if (!$dryRun) {
                // Calculate delay based on current retry count (or 0 if immediate)
                $retryDelay = $immediate ? 0 : (config('ciblerh.email_retry_delay', 60) * pow(2, $payslip->email_retry_count));
                
                // Schedule the retry job
                if ($immediate) {
                    // Dispatch immediately without delay
                    RetryPayslipEmailJob::dispatch($payslip->id);
                    $this->line("  → Scheduled immediately for payslip ID {$payslip->id}");
                } else {
                    // Dispatch with delay
                    RetryPayslipEmailJob::dispatch($payslip->id)
                        ->delay(now()->addSeconds($retryDelay));
                    $this->line("  → Scheduled with {$retryDelay}s delay for payslip ID {$payslip->id}");
                }

                Log::info('ReschedulePayslipRetries: Scheduled retry job', [
                    'payslip_id' => $payslip->id,
                    'current_retry_count' => $payslip->email_retry_count,
                    'retry_delay_seconds' => $retryDelay,
                    'immediate' => $immediate,
                    'employee_id' => $employee->id,
                    'matricule' => $employee->matricule
                ]);
            }

            $tableData[] = [
                'ID' => $payslip->id,
                'Employee' => $payslip->name ?? "{$payslip->first_name} {$payslip->last_name}",
                'Matricule' => $payslip->matricule,
                'Retry Count' => $payslip->email_retry_count,
                'Last Retry' => $payslip->last_email_retry_at 
                    ? $payslip->last_email_retry_at->format('Y-m-d H:i:s') 
                    : 'Never',
                'Status' => $dryRun ? 'Would Schedule' : 'Scheduled'
            ];

            $scheduled++;
        }

        // Display results table
        if (!empty($tableData)) {
            $this->table(
                ['ID', 'Employee', 'Matricule', 'Retry Count', 'Last Retry', 'Status'],
                $tableData
            );
        }

        $this->newLine();
        $this->info("Summary:");
        $this->line("  - Scheduled: {$scheduled}");
        $this->line("  - Skipped: {$skipped}");
        $this->line("  - Total found: {$payslips->count()}");

        if ($dryRun) {
            $this->warn("\nThis was a dry run. Use without --dry-run to actually schedule the retries.");
        } else {
            $this->info("\nRetry jobs have been scheduled. They will run with exponential backoff delays.");
        }

        return Command::SUCCESS;
    }
}


