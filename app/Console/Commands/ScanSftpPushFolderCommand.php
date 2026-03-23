<?php

namespace App\Console\Commands;

use App\Jobs\ProcessSftpPushFileJob;
use App\Models\PayslipMatchingProposal;
use App\Models\Setting;
use App\Models\SftpUser;
use Illuminate\Console\Command;

class ScanSftpPushFolderCommand extends Command
{
    protected $signature   = 'sftp:scan-push-folder {--dry-run : List files without dispatching jobs}';
    protected $description = 'Scan all SFTP user incoming folders for unprocessed PDF files and queue metadata extraction.';

    public function handle(): int
    {
        $setting = Setting::first();

        if (!$setting || !$setting->sftp_sync_enabled) {
            $this->info('SFTP sync is disabled. Skipping scan.');
            return self::SUCCESS;
        }

        // Build the list of incoming directories to scan.
        // When multi-user SFTP users exist, scan each user's home_directory/incoming.
        // Fall back to the legacy single sftp_push_path when no SftpUser rows are present.
        $incomingPaths = $this->resolveIncomingPaths($setting);

        if (empty($incomingPaths)) {
            $this->warn('No incoming folders configured. Add SFTP users in Settings or configure a push path.');
            return self::SUCCESS;
        }

        $totalDispatched = 0;
        $totalSkipped    = 0;

        foreach ($incomingPaths as ['label' => $label, 'path' => $incomingPath]) {
            if (!is_dir($incomingPath)) {
                $this->warn("Incoming folder does not exist: {$incomingPath} ({$label})");
                continue;
            }

            $files = glob($incomingPath . '/*.pdf') ?: [];

            if (empty($files)) {
                $this->info("No PDF files found for {$label}.");
                continue;
            }

            $this->info(sprintf('Found %d PDF file(s) for %s.', count($files), $label));

            foreach ($files as $absolutePath) {
                $basename = basename($absolutePath);

                // Skip if a non-rejected proposal already exists for this filename
                $exists = PayslipMatchingProposal::where('file_name', $basename)
                    ->whereNotIn('status', [
                        PayslipMatchingProposal::STATUS_REJECTED,
                        PayslipMatchingProposal::STATUS_FAILED,
                    ])
                    ->exists();

                if ($exists) {
                    $this->line("  [skip] {$basename} — proposal already exists.");
                    $totalSkipped++;
                    continue;
                }

                if ($this->option('dry-run')) {
                    $this->line("  [dry-run] Would dispatch: {$basename}");
                } else {
                    ProcessSftpPushFileJob::dispatch($absolutePath, $basename);
                    $this->line("  [queued] {$basename}");
                    $totalDispatched++;
                }
            }
        }

        $this->info(sprintf(
            'Scan complete. Dispatched: %d, Skipped: %d.',
            $totalDispatched,
            $totalSkipped
        ));

        return self::SUCCESS;
    }

    /**
     * Resolve the list of incoming directories to scan.
     *
     * Returns an array of ['label' => string, 'path' => string] entries.
     */
    private function resolveIncomingPaths(Setting $setting): array
    {
        $sftpUsers = SftpUser::where('is_active', true)->get();

        if ($sftpUsers->isNotEmpty()) {
            return $sftpUsers->map(fn (SftpUser $u) => [
                'label' => $u->username,
                'path'  => $u->absoluteIncomingPath(),
            ])->all();
        }

        // Legacy single-path fallback
        $legacy = base_path($setting->sftp_push_path ?? 'storage/app/sftp-push') . '/incoming';

        return [['label' => 'default', 'path' => $legacy]];
    }
}
