<?php

namespace App\Console\Commands;

use App\Models\Payslip;
use App\Models\SendPayslipProcess;
use App\Services\PayslipProcessLinkService;
use Illuminate\Console\Command;

class RelinkPayslipProcessCommand extends Command
{
    protected $signature = 'payslips:relink-process
                            {--process= : SendPayslipProcess ID to repair}
                            {--dry-run : Show what would change without writing}';

    protected $description = 'Re-link payslip file rows from a prior process and remove duplicate failed rows after a re-run';

    public function handle(PayslipProcessLinkService $service): int
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

        $employees = $service->resolveEmployeePool($process);
        $orphans = $service->findOrphanedFilePayslips($process, $employees);

        $this->info("Process #{$process->id} ({$process->month}/{$process->year})");
        $this->line("Employee pool: {$employees->count()}");

        $failedOnProcess = Payslip::query()
            ->where('send_payslip_process_id', $process->id)
            ->where('encryption_status', Payslip::STATUS_FAILED)
            ->where(function ($query) {
                $query->whereNull('file')->orWhere('file', '');
            })
            ->count();

        $withFileOnProcess = Payslip::query()
            ->where('send_payslip_process_id', $process->id)
            ->whereNotNull('file')
            ->where('file', '!=', '')
            ->count();

        $this->table(['Metric', 'Count'], [
            ['Failed rows on this process (no file)', $failedOnProcess],
            ['Rows with file on this process', $withFileOnProcess],
            ['Rows with file on OTHER process (same period)', $orphans->count()],
        ]);

        if ($orphans->isNotEmpty()) {
            $this->warn('Orphaned file rows on other processes (sample):');
            foreach ($orphans->take(5) as $row) {
                $this->line("  employee {$row->matricule} → process #{$row->send_payslip_process_id} file=" . basename($row->file));
            }
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run — no changes written.');

            return self::SUCCESS;
        }

        $result = $service->repairProcess($process);

        $this->info("Re-linked {$result['relinked']} payslip row(s) to process #{$process->id}.");
        $this->info("Removed {$result['removed_duplicates']} duplicate failed row(s).");

        if ($result['relinked'] === 0 && $result['removed_duplicates'] === 0) {
            $this->warn('Nothing to repair. If the UI still shows unmatched rows, re-run payslips:diagnose --process=' . $process->id);
        } else {
            $this->comment('Re-run FinalizeMultiPagePayslipsJob / send step if files are still pending encryption.');
        }

        return self::SUCCESS;
    }
}
