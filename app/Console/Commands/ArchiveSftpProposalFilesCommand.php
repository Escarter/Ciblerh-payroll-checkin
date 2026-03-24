<?php

namespace App\Console\Commands;

use App\Models\PayslipMatchingProposal;
use App\Models\SendPayslipProcess;
use App\Services\FeatureConfigurationService;
use Illuminate\Console\Command;

class ArchiveSftpProposalFilesCommand extends Command
{
    protected $signature = 'sftp:archive-proposal-files {--dry-run : List moves without applying them}';
    protected $description = 'Move terminal-state SFTP proposal files from incoming/ to processed/ or failed/ folders.';

    public function handle(): int
    {
        $config = FeatureConfigurationService::getSftpConfig();

        $moveProcessed = (bool) ($config['push_archive_move_processed'] ?? true);
        $moveRejected = (bool) ($config['push_archive_move_rejected'] ?? true);
        // Never auto-move generic failed proposals here.
        // Failed can be a transient/system processing outcome after validation.
        // Business rule: only user-rejected proposals should go to /failed.
        $requireSuccessfulProcess = (bool) ($config['push_archive_require_successful_process'] ?? true);
        $minAgeMinutes = max(0, (int) ($config['push_archive_min_age_minutes'] ?? 5));

        $terminalStatuses = array_values(array_filter([
            $moveProcessed ? PayslipMatchingProposal::STATUS_PROCESSED : null,
            $moveRejected ? PayslipMatchingProposal::STATUS_REJECTED : null,
        ]));

        if (empty($terminalStatuses)) {
            $this->info('Archive rules disabled: no statuses configured for movement.');
            return self::SUCCESS;
        }

        $proposals = PayslipMatchingProposal::query()
            ->whereIn('status', $terminalStatuses)
            ->whereNotNull('local_file_path')
            ->orderBy('created_at')
            ->get();

        if ($proposals->isEmpty()) {
            $this->info('No terminal-state proposals found to archive.');
            return self::SUCCESS;
        }

        $moved = 0;
        $skipped = 0;

        foreach ($proposals as $proposal) {
            /** @var PayslipMatchingProposal $proposal */
            $sourcePath = (string) $proposal->local_file_path;
            if ($sourcePath === '' || !file_exists($sourcePath)) {
                $skipped++;
                continue;
            }

            $mtime = @filemtime($sourcePath);
            if ($mtime && (time() - $mtime) < ($minAgeMinutes * 60)) {
                $this->line("[skip] {$proposal->id}: file age below archive threshold ({$minAgeMinutes} min).");
                $skipped++;
                continue;
            }

            $targetFolder = $this->targetFolderForStatus($proposal->status);
            if (!$targetFolder) {
                $skipped++;
                continue;
            }

            if ($proposal->status === PayslipMatchingProposal::STATUS_PROCESSED
                && $requireSuccessfulProcess
                && !$this->canArchiveProcessedProposal($proposal, $sourcePath)) {
                $this->line("[skip] {$proposal->id}: waiting for payslip process completion.");
                $skipped++;
                continue;
            }

            if (basename(dirname($sourcePath)) === $targetFolder) {
                $skipped++;
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("[dry-run] {$proposal->id}: {$sourcePath} -> {$targetFolder}/");
                $moved++;
                continue;
            }

            $destPath = $this->moveFileToArchiveFolder($sourcePath, $targetFolder);
            if (!$destPath) {
                $skipped++;
                continue;
            }

            $proposal->update([
                'file_path'       => $destPath,
                'local_file_path' => $destPath,
            ]);

            $this->line("[moved] {$proposal->id}: " . basename($sourcePath) . " -> {$targetFolder}/");
            $moved++;
        }

        $this->info(sprintf('Archive complete. Moved: %d, Skipped: %d.', $moved, $skipped));

        return self::SUCCESS;
    }

    private function targetFolderForStatus(string $status): ?string
    {
        return match ($status) {
            PayslipMatchingProposal::STATUS_PROCESSED => 'processed',
            PayslipMatchingProposal::STATUS_REJECTED => 'failed',
            default => null,
        };
    }

    /**
     * Processed proposal files can be archived only after downstream
     * SendPayslipProcess reaches terminal success for this raw file path.
     */
    private function canArchiveProcessedProposal(PayslipMatchingProposal $proposal, string $rawFilePath): bool
    {
        $latestProcess = SendPayslipProcess::query()
            ->where('raw_file', $rawFilePath)
            ->where('company_id', $proposal->matched_to_company_id)
            ->where('month', $proposal->matched_month)
            ->where('year', $proposal->matched_year)
            ->latest('id')
            ->first();

        if (!$latestProcess) {
            return false;
        }

        return $latestProcess->status === 'successful';
    }

    /**
     * Move a file from incoming/ to a sibling archive folder like processed/ or failed/.
     */
    private function moveFileToArchiveFolder(string $sourcePath, string $archiveFolder): ?string
    {
        if (!file_exists($sourcePath)) {
            return null;
        }

        $currentDir = dirname($sourcePath);
        $baseDir = basename($currentDir) === 'incoming'
            ? dirname($currentDir)
            : $currentDir;

        $archiveDir = $baseDir . '/' . $archiveFolder;

        if (!is_dir($archiveDir) && !@mkdir($archiveDir, 0775, true) && !is_dir($archiveDir)) {
            \Log::warning('ArchiveSftpProposalFilesCommand: Unable to create archive directory.', [
                'source'       => $sourcePath,
                'archive_dir'  => $archiveDir,
                'archive_type' => $archiveFolder,
            ]);
            return null;
        }

        if (!is_writable($archiveDir)) {
            \Log::warning('ArchiveSftpProposalFilesCommand: Archive directory is not writable.', [
                'source'       => $sourcePath,
                'archive_dir'  => $archiveDir,
                'archive_type' => $archiveFolder,
            ]);
            return null;
        }

        $destPath = $archiveDir . '/' . basename($sourcePath);
        if (@rename($sourcePath, $destPath)) {
            return $destPath;
        }

        \Log::warning('ArchiveSftpProposalFilesCommand: Failed to move file to archive folder.', [
            'source'       => $sourcePath,
            'destination'  => $destPath,
            'archive_type' => $archiveFolder,
        ]);

        return null;
    }
}
