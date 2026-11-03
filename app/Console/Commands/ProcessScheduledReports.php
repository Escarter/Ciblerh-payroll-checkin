<?php

namespace App\Console\Commands;

use App\Models\ScheduledReport;
use App\Jobs\ScheduledReportJob;
use Illuminate\Console\Command;

class ProcessScheduledReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:process-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process scheduled reports that are due to run';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing scheduled reports...');

        $scheduledReports = ScheduledReport::dueToRun()->get();

        if ($scheduledReports->isEmpty()) {
            $this->info('No scheduled reports due to run.');
            return 0;
        }

        $this->info("Found {$scheduledReports->count()} scheduled report(s) to process.");

        foreach ($scheduledReports as $scheduledReport) {
            try {
                $this->info("Processing scheduled report: {$scheduledReport->name} (ID: {$scheduledReport->id})");
                
                // Dispatch the job to generate and email the report
                ScheduledReportJob::dispatch($scheduledReport);
                
                // Update next run date
                $scheduledReport->updateNextRun();
                
                $this->info("✓ Scheduled report queued successfully.");
            } catch (\Exception $e) {
                $this->error("✗ Failed to process scheduled report ID {$scheduledReport->id}: " . $e->getMessage());
                
                // Update error message
                $scheduledReport->update([
                    'last_error' => $e->getMessage(),
                ]);
            }
        }

        $this->info('Scheduled reports processing completed.');
        return 0;
    }
}
