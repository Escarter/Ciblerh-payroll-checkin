<?php

namespace App\Console\Commands;

use App\Models\Payslip;
use App\Models\SendPayslipProcess;
use Illuminate\Console\Command;

class BackSyncPayslipPeriodsCommand extends Command
{
    protected $signature = 'payslips:back-sync-periods
                            {--process-id= : Only sync payslips for a specific SendPayslipProcess ID}
                            {--sftp-only : Only sync payslips created from SFTP-triggered schedules}
                            {--dry-run : Show what would be updated without persisting changes}';

    protected $description = 'Back-sync payslip month/year from the linked payslip schedule (SendPayslipProcess).';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $processId = $this->option('process-id');
        $sftpOnly = (bool) $this->option('sftp-only');

        $query = SendPayslipProcess::query()
            ->when($processId, fn ($builder) => $builder->where('id', $processId))
            ->when($sftpOnly, fn ($builder) => $builder->whereNotNull('sftp_proposal_id'))
            ->whereNotNull('month')
            ->whereNotNull('year')
            ->whereHas('payslips', function ($builder) {
                $builder->where(function ($payslipQuery) {
                    $payslipQuery->whereColumn('payslips.month', '!=', 'send_payslip_processes.month')
                        ->orWhereColumn('payslips.year', '!=', 'send_payslip_processes.year')
                        ->orWhereNull('payslips.month')
                        ->orWhereNull('payslips.year');
                });
            });

        $processes = $query->orderBy('id')->get();

        if ($processes->isEmpty()) {
            $this->info('No payslip schedules found with out-of-sync payslip periods.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('DRY RUN MODE — no records will be updated.');
        }

        $rows = [];
        $updatedPayslipCount = 0;

        foreach ($processes as $process) {
            $mismatchedPayslips = Payslip::query()
                ->where('send_payslip_process_id', $process->id)
                ->where(function ($payslipQuery) use ($process) {
                    $payslipQuery->where('month', '!=', $process->month)
                        ->orWhere('year', '!=', $process->year)
                        ->orWhereNull('month')
                        ->orWhereNull('year');
                })
                ->orderBy('id')
                ->get();

            if ($mismatchedPayslips->isEmpty()) {
                continue;
            }

            $rows[] = [
                'process_id' => $process->id,
                'source' => $process->sftp_proposal_id ? 'SFTP' : 'Manual',
                'target_period' => sprintf('%s / %s', $process->month, $process->year),
                'payslips' => $mismatchedPayslips->count(),
            ];

            if (!$dryRun) {
                Payslip::query()
                    ->whereIn('id', $mismatchedPayslips->pluck('id'))
                    ->update([
                        'month' => $process->month,
                        'year' => $process->year,
                    ]);
            }

            $updatedPayslipCount += $mismatchedPayslips->count();
        }

        if (empty($rows)) {
            $this->info('No mismatched payslips found after inspection.');
            return self::SUCCESS;
        }

        $this->table(
            ['Process ID', 'Source', 'Target Period', 'Payslips'],
            array_map(static fn (array $row) => [
                $row['process_id'],
                $row['source'],
                $row['target_period'],
                $row['payslips'],
            ], $rows)
        );

        if ($dryRun) {
            $this->info("Dry run complete. {$updatedPayslipCount} payslip(s) would be back-synced.");
        } else {
            $this->info("Back-sync complete. {$updatedPayslipCount} payslip(s) were updated.");
        }

        return self::SUCCESS;
    }
}
