<?php

namespace App\Console\Commands;

use App\Models\SendPayslipProcess;
use App\Services\PayslipFileRecoveryService;
use Illuminate\Console\Command;

class RecoverMissingPayslipFilesCommand extends Command
{
    protected $signature = 'payslips:recover-missing-files
                            {--process= : SendPayslipProcess ID}
                            {--dry-run : Show what would be recovered without writing}';

    protected $description = 'Recover missing modified payslip files from process split files';

    public function handle(PayslipFileRecoveryService $service): int
    {
        $processId = $this->option('process');
        if (empty($processId)) {
            $this->error('Option --process is required.');
            return self::FAILURE;
        }

        $process = SendPayslipProcess::find($processId);
        if (!$process) {
            $this->error("Process #{$processId} not found.");
            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $this->info("Recovering missing payslip files for process #{$process->id} ({$process->month}/{$process->year})");
        if ($dryRun) {
            $this->warn('Dry run mode: no files will be written.');
        }

        $result = $service->recoverProcess($process, $dryRun);

        $this->table(['Metric', 'Value'], [
            ['Scanned payslips', $result['scanned']],
            ['Missing files', $result['missing']],
            ['Recoverable/recovered', $result['recovered']],
            ['Skipped/errors', $result['skipped']],
        ]);

        if (!empty($result['errors'])) {
            $this->warn('Errors/skips (first 15):');
            foreach (array_slice($result['errors'], 0, 15) as $error) {
                $this->line(" - {$error}");
            }
        }

        return self::SUCCESS;
    }
}
