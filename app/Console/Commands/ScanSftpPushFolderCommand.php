<?php

namespace App\Console\Commands;

use App\Jobs\ProcessSftpPushFileJob;
use App\Models\PayslipMatchingProposal;
use App\Models\Setting;
use App\Models\SftpUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ScanSftpPushFolderCommand extends Command
{
    /**
     * Minimum file age (seconds) before scanner considers it stable enough
     * to process. Prevents reading half-uploaded SFTP files.
     */
    private const MIN_SETTLE_SECONDS = 30;

    protected $signature   = 'sftp:scan-push-folder {--dry-run : List files without dispatching jobs} {--sync : Process files immediately instead of queueing jobs}';
    protected $description = 'Scan all SFTP user incoming folders for unprocessed PDF files and queue metadata extraction.';

    public function handle(): int
    {
        $setting = $this->resolveSftpSetting();

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

        $totalHandled = 0;
        $totalSkipped = 0;

        foreach ($incomingPaths as ['label' => $label, 'path' => $incomingPath]) {
            if (!is_dir($incomingPath)) {
                $this->warn("Incoming folder does not exist: {$incomingPath} ({$label})");
                continue;
            }

            // Case-insensitive PDF discovery (handles .pdf, .PDF, .Pdf, etc.)
            // while staying non-recursive inside each incoming/ root.
            $files = [];
            foreach (scandir($incomingPath) ?: [] as $entry) {
                if ($entry === '.' || $entry === '..') {
                    continue;
                }

                $absolutePath = $incomingPath . '/' . $entry;
                if (!is_file($absolutePath)) {
                    continue;
                }

                if (preg_match('/\.pdf$/i', $entry)) {
                    $files[] = $absolutePath;
                }
            }

            if (empty($files)) {
                $this->info("No PDF files found for {$label}.");
                continue;
            }

            $this->info(sprintf('Found %d PDF file(s) for %s.', count($files), $label));

            foreach ($files as $absolutePath) {
                $basename = basename($absolutePath);

                // Skip files that are still being uploaded / recently touched.
                $mtime = @filemtime($absolutePath);
                if ($mtime && (time() - $mtime) < self::MIN_SETTLE_SECONDS) {
                    $this->line("  [skip] {$basename} — file still settling.");
                    $totalSkipped++;
                    continue;
                }

                $fingerprint = @hash_file('sha256', $absolutePath) ?: null;
                $supportsFingerprint = PayslipMatchingProposal::supportsFileFingerprint();

                // Skip when a non-rejected/non-failed proposal already exists for
                // the same fingerprint (preferred) or same legacy filename.
                $exists = PayslipMatchingProposal::query()
                    ->whereNotIn('status', [
                        PayslipMatchingProposal::STATUS_REJECTED,
                        PayslipMatchingProposal::STATUS_FAILED,
                    ])
                    ->when(
                        $fingerprint && $supportsFingerprint,
                        fn($q) => $q->where('file_fingerprint', $fingerprint),
                        fn($q) => $q->where('file_name', $basename)
                    )
                    ->exists();

                if ($exists) {
                    $this->line("  [skip] {$basename} — proposal already exists.");
                    $totalSkipped++;
                    continue;
                }

                if ($this->option('dry-run')) {
                    $this->line("  [dry-run] Would dispatch: {$basename}");
                } else {
                    $lockKey = 'sftp-push:dispatch:' . sha1($absolutePath . '|' . (@filesize($absolutePath) ?: 0) . '|' . ($mtime ?: 0));
                    $lock = Cache::lock($lockKey, 15);

                    if ($lock->get()) {
                        try {
                            if ($this->option('sync')) {
                                ProcessSftpPushFileJob::dispatchSync($absolutePath, $basename, $fingerprint);
                                $this->line("  [processed] {$basename}");
                            } else {
                                ProcessSftpPushFileJob::dispatch($absolutePath, $basename, $fingerprint);
                                $this->line("  [queued] {$basename}");
                            }

                            $totalHandled++;
                        } finally {
                            $lock->release();
                        }
                    } else {
                        $this->line("  [skip] {$basename} — dispatch lock active.");
                        $totalSkipped++;
                    }
                }
            }
        }

        $summaryLabel = $this->option('sync') ? 'Processed' : 'Dispatched';

        $this->info(sprintf(
            'Scan complete. %s: %d, Skipped: %d.',
            $summaryLabel,
            $totalHandled,
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

    /**
     * Resolve the settings row used for SFTP features.
     */
    private function resolveSftpSetting(): ?Setting
    {
        $primary = Setting::query()
            ->where('company_id', 1)
            ->latest('id')
            ->first();

        if ($primary) {
            return $primary;
        }

        $configured = Setting::query()
            ->where(function ($query) {
                $query->whereRaw("TRIM(COALESCE(sftp_host, '')) <> ''")
                    ->orWhereRaw("TRIM(COALESCE(sftp_push_path, '')) <> ''");
            })
            ->latest('id')
            ->first();

        if ($configured) {
            return $configured;
        }

        return Setting::query()->latest('id')->first();
    }
}
