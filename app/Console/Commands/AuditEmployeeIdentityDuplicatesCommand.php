<?php

namespace App\Console\Commands;

use App\Services\EmployeeIdentityDuplicateReportService;
use Illuminate\Console\Command;

class AuditEmployeeIdentityDuplicatesCommand extends Command
{
    protected $signature = 'employees:audit-identity-duplicates
                            {--include-trashed : Include soft-deleted users in the report}
                            {--json : Output the report as JSON}';

    protected $description = 'List duplicate matricules and duplicate email local parts before running the unique matricule migration';

    public function handle(EmployeeIdentityDuplicateReportService $service): int
    {
        $report = $service->report((bool) $this->option('include-trashed'));

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return $report['migration_ready'] ? self::SUCCESS : self::FAILURE;
        }

        $this->info('Employee identity duplicate audit');
        $this->line('Run this before: php artisan migrate (unique index on users.matricule)');
        $this->newLine();

        $summary = $report['summary'];
        $this->table(['Metric', 'Count'], [
            ['Users scanned', $summary['users_scanned']],
            ['Duplicate matricule groups', $summary['duplicate_matricule_groups']],
            ['Users in matricule conflicts', $summary['users_in_matricule_conflicts']],
            ['Duplicate email local-part groups', $summary['duplicate_email_local_part_groups']],
            ['Users in email local-part conflicts', $summary['users_in_email_local_part_conflicts']],
        ]);

        if (!empty($report['duplicate_matricules'])) {
            $this->newLine();
            $this->warn('Duplicate matricules (blocks matricule unique migration):');

            foreach ($report['duplicate_matricules'] as $group) {
                $this->line("  Matricule {$group['matricule']}:");
                foreach ($group['users'] as $user) {
                    $deleted = $user['deleted'] ? ', deleted' : '';
                    $this->line(sprintf(
                        '    #%d %s <%s> [%s] payslips=%d%s',
                        $user['id'],
                        $user['name'],
                        $user['email'],
                        $user['company'] ?? 'no company',
                        $user['active_payslips'],
                        $deleted
                    ));
                }
            }
        }

        if (!empty($report['duplicate_email_local_parts'])) {
            $this->newLine();
            $this->warn('Duplicate email local parts (same text before @, different domains):');

            foreach ($report['duplicate_email_local_parts'] as $group) {
                $this->line("  Local part \"{$group['local_part']}\":");
                foreach ($group['users'] as $user) {
                    $deleted = $user['deleted'] ? ', deleted' : '';
                    $this->line(sprintf(
                        '    #%d %s <%s> matricule=%s payslips=%d%s',
                        $user['id'],
                        $user['name'],
                        $user['email'],
                        $user['matricule'] ?? 'n/a',
                        $user['active_payslips'],
                        $deleted
                    ));
                }
            }
        }

        if ($report['migration_ready']) {
            $this->newLine();
            $this->info('No duplicate matricules found. Safe to run the unique matricule migration.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->error('Duplicate matricules must be resolved before migrating.');
        $this->line('Use Portal → Employee payslip history → Employee Access Diagnostic → Relink payslips,');
        $this->line('then deactivate or remove duplicate accounts and re-run this command.');

        return self::FAILURE;
    }
}
