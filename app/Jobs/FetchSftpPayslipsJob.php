<?php

namespace App\Jobs;

use App\Models\PayslipMatchingProposal;
use App\Models\Setting;
use App\Services\SftpPayslipService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

class FetchSftpPayslipsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $maxExceptions = 1;
    public $timeout = 300;
    public $failOnTimeout = true;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->onQueue('processing');
    }

    /**
     * Execute the job.
     * Fetches PDF files from SFTP, downloads them locally, and creates matching proposals for admin validation
     */
    public function handle(): void
    {
        try {
            $setting = $this->resolveSftpSetting();
            
            // Check if SFTP sync is enabled
            if (!$setting || !$setting->sftp_sync_enabled) {
                \Log::info('SFTP sync is not enabled. Job skipped.');
                return;
            }

            $sftpHost = trim((string) ($setting->sftp_host ?? ''));
            $sftpUsername = trim((string) ($setting->sftp_username ?? ''));

            // Validate SFTP configuration is complete
            if ($sftpHost === '' || $sftpUsername === '') {
                \Log::warning('SFTP configuration is incomplete. Please configure SFTP settings in the admin panel (host and username are required).', [
                    'setting_id' => $setting->id,
                    'company_id' => $setting->company_id,
                ]);
                return;
            }

            try {
                $sftpService = new SftpPayslipService($setting);
            } catch (\RuntimeException $configError) {
                \Log::warning('SFTP configuration error: ' . $configError->getMessage());
                return;
            }
            
            // Get matching strategies
            $strategies = $setting->sftp_matching_strategies ?? [];
            
            if (empty($strategies)) {
                \Log::warning('No SFTP matching strategies configured.');
                return;
            }

            // Get last sync time for incremental fetching
            $lastSyncTime = \Cache::get('sftp_last_sync_time', 0);
            
            // Fetch payslips from SFTP with filtering options
            $result = $sftpService->fetchPayslipsFromSftp([
                'extensions' => ['pdf'],          // Only PDF files
                'maxDepth' => 5,                  // Don't search too deep
                'lastModifiedAfter' => $lastSyncTime,  // Only new/updated files
                'limit' => 100,                   // Batch process to avoid timeouts
            ]);

            if (!$result['success']) {
                \Log::error('Failed to fetch payslips from SFTP: ' . $result['error']);
                return;
            }

            \Log::info("Fetched {$result['count']} new PDF files from SFTP (skipped {$result['skipped']} non-PDF/old files)");

            // Handle pagination - if there are more files, queue another job
            if ($result['hasMore'] ?? false) {
                $this->dispatch(new self())
                    ->delay(now()->addSeconds(30))
                    ->onQueue('processing');
                    
                \Log::info("More files available. Queuing another fetch job.");
            }

            // Create organized directory for today's download
            $downloadDate = now()->format('Y-m-d');
            $localDirectory = "sftp/{$downloadDate}";

            // Process each PDF file found on SFTP
            // Download immediately and store locally for reliability and performance
            $proposalCount = 0;
            $downloadedCount = 0;
            $errorsCount = 0;
            
            foreach ($result['files'] as $file) {
                try {
                    // Check if this file already has a pending/validated proposal
                    $existingProposal = PayslipMatchingProposal::where('file_path', $file['path'])
                        ->whereIn('status', ['pending', 'validated'])
                        ->first();

                    if ($existingProposal) {
                        \Log::info("File {$file['basename']} already has a proposal. Skipping.");
                        continue;
                    }

                    // Extract metadata from filename/path to suggest department/company/month/year
                    $metadata = $sftpService->parsePayslipMetadata(
                        $file['basename'],
                        $file['path'],
                        $file['timestamp'],
                        $strategies
                    );

                    // Generate match candidates (ranked by confidence)
                    $matches = $sftpService->matchPayslipToEntities($metadata);

                    // Download the file from SFTP to local storage
                    $localFilePath = null;
                    $downloadStatus = 'failed';
                    $downloadError = null;

                    try {
                        $localFileName = sprintf(
                            '%s_%d.pdf',
                            Str::slug(pathinfo($file['basename'], PATHINFO_FILENAME)),
                            time()
                        );
                        
                        $localFilePath = $sftpService->downloadPayslip($file['path'], "{$localDirectory}/{$localFileName}");

                        if ($localFilePath) {
                            $downloadStatus = 'downloaded';
                            $downloadedCount++;
                            \Log::info("Downloaded SFTP file {$file['basename']} to {$localFilePath}");
                        } else {
                            $downloadError = 'Failed to download file from SFTP server';
                            \Log::warning("Failed to download {$file['basename']}: {$downloadError}");
                        }
                    } catch (\Exception $e) {
                        $downloadStatus = 'failed';
                        $downloadError = $e->getMessage();
                        \Log::error("Error downloading {$file['basename']}: {$downloadError}");
                    }

                    // Create a proposal for this payslip FILE
                    // File is already downloaded locally - ready for processing after validation
                    PayslipMatchingProposal::create([
                        'file_path' => $file['path'],  // Original SFTP path (for reference)
                        'local_file_path' => $localFilePath,  // Local storage path
                        'file_name' => $file['basename'],
                        'file_size' => $file['size'],
                        'file_timestamp' => $file['timestamp'],
                        'proposed_match' => $matches,  // Contains candidate departments/companies
                        'download_status' => $downloadStatus,
                        'download_error' => $downloadError,
                        'status' => PayslipMatchingProposal::STATUS_PENDING,
                    ]);

                    $proposalCount++;
                } catch (\Exception $fileError) {
                    $errorsCount++;
                    \Log::error("Error processing file {$file['basename']}: {$fileError->getMessage()}");
                    continue;
                }
            }

            // Update last sync time
            \Cache::put('sftp_last_sync_time', time(), 86400); // Cache for 24 hours

            \Log::info("Created {$proposalCount} new payslip file proposals. Downloaded: {$downloadedCount}, Errors: {$errorsCount}");

            // Dispatch notification to admins if any new proposals
            if ($proposalCount > 0) {
                $this->dispatchNotification($proposalCount, $downloadedCount);
            }

        } catch (Throwable $e) {
            \Log::error('SFTP payslip fetch job failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(Throwable $exception): void
    {
        \Log::error('FetchSftpPayslipsJob permanently failed: ' . $exception->getMessage());
    }

    /**
     * Dispatch notification to admins
     */
    private function dispatchNotification(int $proposalCount, int $downloadedCount): void
    {
        if ($proposalCount > 0) {
            // Get admin users
            $admins = \App\Models\User::role('admin')->get();

            foreach ($admins as $admin) {
                $message = "New SFTP payslip proposals ready: {$proposalCount} files ({$downloadedCount} downloaded successfully)";
                $admin->notify(new \App\Notifications\SftpPayslipProposalsReadyNotification($proposalCount));
            }
        }
    }

    /**
     * Resolve the settings row used by this fetch job.
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
            ->whereRaw("TRIM(COALESCE(sftp_host, '')) <> ''")
            ->whereRaw("TRIM(COALESCE(sftp_username, '')) <> ''")
            ->latest('id')
            ->first();

        if ($configured) {
            return $configured;
        }

        return Setting::query()->latest('id')->first();
    }
}
