<?php

namespace App\Console\Commands;

use App\Models\Payslip;
use App\Models\User;
use App\Jobs\Single\SendSinglePayslipJob;
use App\Jobs\SendPayslipJob;
use App\Models\SendPayslipProcess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class ProcessPendingPayslips extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payslips:process-pending 
                            {--process-id= : Only process payslips for a specific process ID}
                            {--limit= : Limit the number of payslips to process}
                            {--dry-run : Show what would be processed without actually processing}
                            {--chunk-size=50 : Number of payslips to process per job chunk}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process payslips that are stuck in pending status for email/SMS sending';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $processId = $this->option('process-id');
        $limit = $this->option('limit');
        $dryRun = $this->option('dry-run');
        $chunkSize = (int) ($this->option('chunk-size') ?? 50);

        $this->info('Finding payslips with pending email or SMS status...');

        // Build query for pending payslips
        $query = Payslip::where(function ($q) {
                $q->where('email_sent_status', Payslip::STATUS_PENDING)
                  ->orWhere('sms_sent_status', Payslip::STATUS_PENDING);
            })
            ->where('encryption_status', Payslip::STATUS_SUCCESSFUL) // Only if encryption succeeded
            ->whereNotNull('file') // Must have a file
            ->whereNotNull('send_payslip_process_id'); // Must be part of a process

        // Filter by process ID if specified
        if ($processId) {
            $query->where('send_payslip_process_id', $processId);
        }

        // Apply limit if specified
        if ($limit) {
            $query->limit((int) $limit);
        }

        $payslips = $query->orderBy('id')->get();

        if ($payslips->isEmpty()) {
            $this->info('No pending payslips found matching the criteria.');
            return Command::SUCCESS;
        }

        $this->info("Found {$payslips->count()} pending payslip(s) to process.");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No jobs will be dispatched');
            $this->newLine();
        }

        // Group payslips by process_id and month for batch processing
        $grouped = $payslips->groupBy(function ($payslip) {
            return $payslip->send_payslip_process_id . '_' . $payslip->month . '_' . $payslip->year;
        });

        $processed = 0;
        $skipped = 0;
        $tableData = [];

        foreach ($grouped as $groupKey => $groupPayslips) {
            $processId = $groupPayslips->first()->send_payslip_process_id;
            $process = SendPayslipProcess::find($processId);

            if (!$process) {
                $this->warn("Skipping group - process ID {$processId} not found");
                $skipped += $groupPayslips->count();
                continue;
            }

            // Get employees for these payslips
            $employeeIds = $groupPayslips->pluck('employee_id')->unique();
            $employees = User::whereIn('id', $employeeIds)->get()->keyBy('id');

            // Filter out payslips where employee doesn't exist or file is missing
            $validPayslips = $groupPayslips->filter(function ($payslip) use ($employees) {
                if (!Storage::disk('modified')->exists($payslip->file)) {
                    $this->warn("Skipping payslip ID {$payslip->id} - file not found: {$payslip->file}");
                    return false;
                }

                if (!$employees->has($payslip->employee_id)) {
                    $this->warn("Skipping payslip ID {$payslip->id} - employee not found");
                    return false;
                }

                return true;
            });

            if ($validPayslips->isEmpty()) {
                $skipped += $groupPayslips->count();
                continue;
            }

            // Get valid employees
            $validEmployeeIds = $validPayslips->pluck('employee_id')->unique();
            $validEmployees = $employees->whereIn('id', $validEmployeeIds);

            if (!$dryRun) {
                // Chunk employees and dispatch SendPayslipJob for each chunk
                $employeeChunks = $validEmployees->chunk($chunkSize);

                foreach ($employeeChunks as $chunk) {
                    try {
                        SendPayslipJob::dispatch($chunk, $process);
                        $this->line("  → Dispatched SendPayslipJob for {$chunk->count()} employees (process ID: {$processId})");
                        
                        Log::info('ProcessPendingPayslips: Dispatched SendPayslipJob', [
                            'process_id' => $processId,
                            'employee_count' => $chunk->count(),
                            'payslip_ids' => $validPayslips->pluck('id')->toArray()
                        ]);
                    } catch (\Exception $e) {
                        $this->error("Failed to dispatch job for process ID {$processId}: " . $e->getMessage());
                        Log::error('ProcessPendingPayslips: Failed to dispatch job', [
                            'process_id' => $processId,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        $skipped += $chunk->count();
                        continue;
                    }
                }
            }

            // Add to table data
            foreach ($validPayslips as $payslip) {
                $employee = $employees->get($payslip->employee_id);
                $tableData[] = [
                    'ID' => $payslip->id,
                    'Employee' => $employee ? ($employee->name ?? "{$payslip->first_name} {$payslip->last_name}") : 'N/A',
                    'Matricule' => $payslip->matricule,
                    'Month' => $payslip->month,
                    'Year' => $payslip->year,
                    'Email Status' => $this->getStatusText($payslip->email_sent_status),
                    'SMS Status' => $this->getStatusText($payslip->sms_sent_status),
                    'Status' => $dryRun ? 'Would Process' : 'Dispatched'
                ];
            }

            $processed += $validPayslips->count();
        }

        // Display results table
        if (!empty($tableData)) {
            $this->table(
                ['ID', 'Employee', 'Matricule', 'Month', 'Year', 'Email Status', 'SMS Status', 'Status'],
                $tableData
            );
        }

        $this->newLine();
        $this->info("Summary:");
        $this->line("  - Processed: {$processed}");
        $this->line("  - Skipped: {$skipped}");
        $this->line("  - Total found: {$payslips->count()}");

        if ($dryRun) {
            $this->warn("\nThis was a dry run. Use without --dry-run to actually process the payslips.");
        } else {
            $this->info("\nJobs have been dispatched. Make sure queue workers are running to process them.");
            $this->line("  Run: php artisan queue:work --queue=emails");
        }

        return Command::SUCCESS;
    }

    /**
     * Get human-readable status text
     */
    private function getStatusText($status)
    {
        return match($status) {
            Payslip::STATUS_PENDING => 'Pending',
            Payslip::STATUS_SUCCESSFUL => 'Successful',
            Payslip::STATUS_FAILED => 'Failed',
            Payslip::STATUS_DISABLED => 'Disabled',
            Payslip::STATUS_SKIPPED => 'Skipped',
            default => 'Unknown'
        };
    }
}


