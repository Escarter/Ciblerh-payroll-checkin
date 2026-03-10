<?php

namespace App\Console\Commands;

use App\Jobs\ProcessSftpPushFileJob;
use App\Models\PayslipMatchingProposal;
use App\Models\Setting;
use Illuminate\Console\Command;

class ScanSftpPushFolderCommand extends Command
{
    protected $signature   = 'sftp:scan-push-folder {--dry-run : List files without dispatching jobs}';
    protected $description = 'Scan the SFTP push folder for unprocessed PDF files and queue metadata extraction.';

    public function handle(): int
    {
        $setting = Setting::first();

        if (!$setting || !$setting->sftp_sync_enabled) {
            $this->info('SFTP sync is disabled. Skipping scan.');
            return self::SUCCESS;
        }

        $pushPath     = base_path($setting->sftp_push_path ?? 'storage/app/sftp-push');
        $incomingPath = $pushPath . '/incoming';

        if (!is_dir($incomingPath)) {
            $this->warn("Incoming folder does not exist: {$incomingPath}");
            return self::SUCCESS;
        }

        $files = glob($incomingPath . '/*.pdf');

        if (empty($files)) {
            $this->info('No PDF files found in push folder.');
            return self::SUCCESS;
        }

        $this->info(sprintf('Found %d PDF file(s) in push folder.', count($files)));

        $dispatched = 0;
        $skipped    = 0;

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
                $skipped++;
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("  [dry-run] Would dispatch: {$basename}");
            } else {
                ProcessSftpPushFileJob::dispatch($absolutePath, $basename);
                $this->line("  [queued] {$basename}");
                $dispatched++;
            }
        }

        $this->info(sprintf(
            'Scan complete. Dispatched: %d, Skipped: %d.',
            $dispatched,
            $skipped
        ));

        return self::SUCCESS;
    }
}
