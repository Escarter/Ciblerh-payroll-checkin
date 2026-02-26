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
            $setting = Setting::first();
            
            // Check if SFTP sync is enabled
            if (!$setting || !$setting->sftp_sync_enabled) {
                \Log::info('SFTP sync is not enabled. Job skipped.');
                return;
            }

            $sftpService = new SftpPayslipService();
            
            // Get matching strategies
            $strategies = $setting->sftp_matching_strategies ?? [];
            
            if (empty($strategies)) {
                \Log::warning('No SFTP matching strategies configured.');
                return;
            }

            // Fetch payslips from SFTP
            $result = $sftpService->fetchPayslipsFromSftp();

            if (!$result['success']) {
                \Log::error('Failed to fetch payslips from SFTP: ' . $result['error']);
                return;
            }

            \Log::info("Fetched {$result['count']} files from SFTP");

            // Create organized directory for today's download
            $downloadDate = now()->format('Y-m-d');
            $localDirectory = "sftp/{$downloadDate}";

            // Process each PDF file found on SFTP
            // Download immediately and store locally for reliability and performance
            $proposalCount = 0;
            $downloadedCount = 0;
            
            foreach ($result['files'] as $file) {
                // Only process PDF files
                if (!str_ends_with(strtolower($file['basename']), '.pdf')) {
                    continue;
                }

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
                        str_slug(pathinfo($file['basename'], PATHINFO_FILENAME)),
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
            }

            \Log::info("Created {$proposalCount} new payslip file proposals. Downloaded: {$downloadedCount}");

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
}
