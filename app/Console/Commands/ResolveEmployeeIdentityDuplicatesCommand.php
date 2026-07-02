<?php

namespace App\Console\Commands;

use App\Services\EmployeeIdentityDuplicateReportService;
use App\Services\EmployeeIdentityDuplicateResolutionService;
use Illuminate\Console\Command;

class ResolveEmployeeIdentityDuplicatesCommand extends Command
{
    protected $signature = 'employees:resolve-identity-duplicates
                            {--dry-run : Show planned changes without writing}
                            {--force : Apply changes without confirmation}
                            {--keep-active : Leave duplicate accounts active (only rename matricule)}';

    protected $description = 'Merge duplicate matricule accounts so the unique matricule migration can run';

    public function handle(
        EmployeeIdentityDuplicateReportService $reportService,
        EmployeeIdentityDuplicateResolutionService $resolutionService,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $deactivate = !$this->option('keep-active');

        $report = $reportService->report();

        if ($report['migration_ready']) {
            $this->info('No duplicate matricules found. Nothing to resolve.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('Dry run — no database changes will be made.');
        } else {
            if (!$this->option('force') && !$this->confirm('This will relink payslips, rename duplicate matricules, and deactivate duplicate accounts. Continue?')) {
                $this->info('Cancelled.');

                return self::SUCCESS;
            }
        }

        $result = $resolutionService->resolve($dryRun, $deactivate);

        $this->info(($dryRun ? 'Would process' : 'Processed') . " {$result['groups_processed']} duplicate matricule group(s).");
        $this->line('Payslips ' . ($dryRun ? 'to relink' : 'relinked') . ": {$result['payslips_relinked']}");
        $this->newLine();

        if (!empty($result['canonical_users'])) {
            $this->info('Canonical accounts kept:');
            foreach ($result['canonical_users'] as $user) {
                $this->line("  #{$user['id']} {$user['name']} <{$user['email']}> matricule={$user['matricule']} payslips={$user['active_payslips']}");
            }
        }

        if (!empty($result['archived_users'])) {
            $this->newLine();
            $this->warn('Duplicate accounts ' . ($dryRun ? 'to archive' : 'archived') . ':');
            foreach ($result['archived_users'] as $user) {
                $suffix = $dryRun
                    ? " → matricule {$user['new_matricule']}" . ($user['would_deactivate'] ?? false ? ', deactivate' : '')
                    : " → matricule {$user['new_matricule']}" . (($user['deactivated'] ?? false) ? ', deactivated' : '');
                $this->line("  #{$user['id']} {$user['name']} <{$user['email']}>{$suffix}");
            }
        }

        if ($dryRun) {
            $this->newLine();
            $this->line('Run without --dry-run to apply, then:');
        } else {
            $followUp = $reportService->report();
            if (!$followUp['migration_ready']) {
                $this->newLine();
                $this->error('Duplicate matricules still remain. Re-run employees:audit-identity-duplicates.');

                return self::FAILURE;
            }

            $this->newLine();
            $this->info('Duplicate matricules resolved.');
        }

        $this->line('  php artisan employees:audit-identity-duplicates');
        $this->line('  php artisan migrate --force');

        return self::SUCCESS;
    }
}
