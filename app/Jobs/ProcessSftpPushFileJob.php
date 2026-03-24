<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\Department;
use App\Models\PayslipMatchingProposal;
use App\Notifications\SftpAutoMatchNotification;
use App\Services\FeatureConfigurationService;
use App\Services\SftpPayslipService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Throwable;

class ProcessSftpPushFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $maxExceptions = 1;
    public $timeout = 120;

    /**
     * @param string $absoluteFilePath Absolute path to the uploaded PDF on disk
     * @param string $originalFilename Original client filename (for display / dedup)
     */
    public function __construct(
        private readonly string $absoluteFilePath,
        private readonly string $originalFilename,
        private readonly ?string $knownFingerprint = null,
    ) {
        $this->onQueue('processing');
    }

    /**
     * Extract metadata from the pushed PDF and create a PayslipMatchingProposal.
     */
    public function handle(): void
    {
        $fingerprint = $this->knownFingerprint ?: (@hash_file('sha256', $this->absoluteFilePath) ?: null);
        $supportsFingerprint = PayslipMatchingProposal::supportsFileFingerprint();
        $lockKey = 'sftp-push:process:' . ($fingerprint ?: sha1($this->absoluteFilePath));
        $lock = Cache::lock($lockKey, 180);

        if (!$lock->get()) {
            \Log::info('ProcessSftpPushFileJob: Processing lock active, skipping duplicate run.', [
                'file' => basename($this->absoluteFilePath),
                'fingerprint' => $fingerprint,
            ]);
            return;
        }

        try {
        // ── Idempotency: skip if a non-rejected proposal already exists ──────
        $basename = basename($this->absoluteFilePath);
        $existing = PayslipMatchingProposal::query()
            ->whereNotIn('status', [
                PayslipMatchingProposal::STATUS_REJECTED,
                PayslipMatchingProposal::STATUS_FAILED,
            ])
            ->when(
                $fingerprint && $supportsFingerprint,
                fn($q) => $q->where('file_fingerprint', $fingerprint),
                fn($q) => $q->where('file_name', $basename)
            )
            ->first();

        if ($existing) {
            \Log::info('ProcessSftpPushFileJob: Proposal already exists, skipping.', [
                'file' => $basename,
                'proposal_id' => $existing->id,
            ]);
            return;
        }

        // ── Verify file still exists ─────────────────────────────────────────
        if (!file_exists($this->absoluteFilePath)) {
            \Log::error('ProcessSftpPushFileJob: File not found on disk.', [
                'path' => $this->absoluteFilePath,
            ]);
            return;
        }

        $service = new SftpPayslipService();

        // ── Extract company name + pay period from PDF content ───────────────
        $metadata = $service->extractPdfMetadata($this->absoluteFilePath);

        \Log::info('ProcessSftpPushFileJob: Extracted PDF metadata.', [
            'file'        => $basename,
            'company_raw' => $metadata['company_raw'],
            'month'       => $metadata['month'],
            'year'        => $metadata['year'],
        ]);

        // ── Fuzzy-match company ──────────────────────────────────────────────
        // Try every collected header line as well as the filename stem so that
        // an address on line-1 doesn't block the real company name on line-2+.
        $candidates     = [];
        $bestCompany    = null;
        $bestDepartment = null;

        $matchSources = $service->buildCompanyMatchSources($metadata, $this->originalFilename);

        if (!empty($matchSources)) {
            $candidates = $service->matchBestFromMultiple($matchSources);
        }

        if (!empty($candidates)) {
            $best = $candidates[0];

            $bestCompany = Company::find($best['company_id']);

            // Auto-resolve department when company has exactly one active department
            if ($bestCompany) {
                $departments = Department::where('company_id', $bestCompany->id)
                    ->where('is_active', true)
                    ->get();

                if ($departments->count() === 1) {
                    $bestDepartment = $departments->first();
                }
            }
        }

        // ── Build proposed_match payload ─────────────────────────────────────
        $proposedMatch = [
            'company_raw'          => $metadata['company_raw'],
            'company_header_lines' => $metadata['company_header_lines'] ?? [],
            'match_sources'        => $matchSources,
            'raw_text_preview'     => $metadata['raw_text_preview'],
            'candidates'           => $candidates,
            'best_match'           => !empty($candidates) ? $candidates[0] : null,
        ];

        // ── Create the proposal ──────────────────────────────────────────────
        $payload = [
            'file_path'               => $this->absoluteFilePath,
            'local_file_path'         => $this->absoluteFilePath,
            'file_name'               => $basename,
            'file_size'               => filesize($this->absoluteFilePath) ?: null,
            'file_timestamp'          => ($mtime = filemtime($this->absoluteFilePath)) ? \Carbon\Carbon::createFromTimestamp($mtime) : null,
            'proposed_match'          => $proposedMatch,
            'matched_to_company_id'   => $bestCompany?->id,
            'matched_to_department_id'=> $bestDepartment?->id,
            'matched_month'           => $metadata['month'],
            'matched_year'            => $metadata['year'],
            'download_status'         => 'downloaded',
            'status'                  => PayslipMatchingProposal::STATUS_PENDING,
        ];

        if ($supportsFingerprint) {
            $payload['file_fingerprint'] = $fingerprint;
        }

        $proposal = PayslipMatchingProposal::create($payload);

        // ── Auto-match: validate proposal if confidence meets configured threshold ──
        $autoMatchConfig = FeatureConfigurationService::getSftpAutoMatchConfig();

        if ($autoMatchConfig['enabled'] && !empty($candidates)) {
            $best = $candidates[0];

            if (FeatureConfigurationService::canAutoMatch($best, $autoMatchConfig)) {
                if ($bestCompany && !$bestDepartment) {
                    // Company matched but department is ambiguous → notify, keep pending
                    \Log::info('ProcessSftpPushFileJob: Auto-match skipped — department ambiguous.', [
                        'file'    => $basename,
                        'company' => $bestCompany->name,
                    ]);

                    $this->notifyEmailsSafely(
                        $autoMatchConfig['notification_email'] ?? '',
                        new SftpAutoMatchNotification($proposal, 'dept_required')
                    );
                } else {
                    // Auto-validate
                    $proposal->update([
                        'status'          => PayslipMatchingProposal::STATUS_VALIDATED,
                        'is_auto_matched' => true,
                        'matched_at'      => now(),
                    ]);

                    \Log::info('ProcessSftpPushFileJob: Proposal auto-validated.', [
                        'file'       => $basename,
                        'company'    => $bestCompany?->name,
                        'confidence' => $best['confidence'],
                        'strategy'   => $best['strategy'],
                    ]);

                    $this->notifyEmailsSafely(
                        $autoMatchConfig['notification_email'] ?? '',
                        new SftpAutoMatchNotification($proposal, 'auto_validated')
                    );
                }
            }
        }

        \Log::info('ProcessSftpPushFileJob: Proposal created.', [
            'file'                    => $basename,
            'matched_to_company'      => $bestCompany?->name,
            'matched_to_department'   => $bestDepartment?->name,
            'matched_month'           => $metadata['month'],
            'matched_year'            => $metadata['year'],
            'candidates_count'        => count($candidates),
        ]);
        } finally {
            $lock->release();
        }
    }

    public function failed(Throwable $exception): void
    {
        if (!$this->shouldMoveToFailedOnPermanentError()) {
            \Log::warning('ProcessSftpPushFileJob permanently failed, but skipping failed-folder move because an active proposal already exists.', [
                'file'  => $this->absoluteFilePath,
                'error' => $exception->getMessage(),
            ]);
            return;
        }

        $failedPath = $this->moveFileToArchiveFolder($this->absoluteFilePath, 'failed');

        \Log::error('ProcessSftpPushFileJob permanently failed.', [
            'file'        => $this->absoluteFilePath,
            'moved_to'    => $failedPath,
            'error'       => $exception->getMessage(),
        ]);
    }

    /**
     * Send the given notification to every email address in a
     * comma-separated string (safe no-op when the string is empty).
     */
    private function notifyEmails(string $emailList, \Illuminate\Notifications\Notification $notification): void
    {
        $emails = array_filter(array_map('trim', preg_split('/[\s,]+/', $emailList, -1, PREG_SPLIT_NO_EMPTY)));

        foreach ($emails as $email) {
            Notification::route('mail', $email)->notify(clone $notification);
        }
    }

    /**
     * Send notifications without failing the whole intake pipeline.
     */
    private function notifyEmailsSafely(string $emailList, \Illuminate\Notifications\Notification $notification): void
    {
        try {
            $this->notifyEmails($emailList, $notification);
        } catch (Throwable $e) {
            \Log::warning('ProcessSftpPushFileJob: Notification delivery failed (non-fatal).', [
                'file'  => basename($this->absoluteFilePath),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * If proposal creation already succeeded, avoid moving source to failed/ on
     * later non-critical failures (e.g. notification transport issues).
     */
    private function shouldMoveToFailedOnPermanentError(): bool
    {
        $basename = basename($this->absoluteFilePath);

        $hasActiveProposal = PayslipMatchingProposal::query()
            ->where('file_name', $basename)
            ->whereNotIn('status', [
                PayslipMatchingProposal::STATUS_REJECTED,
                PayslipMatchingProposal::STATUS_FAILED,
            ])
            ->exists();

        return !$hasActiveProposal;
    }

    /**
     * Move a file from incoming/ to a sibling archive folder like processed/ or failed/.
     * Returns the new absolute path on success, or null if the move could not be completed.
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
            \Log::warning('ProcessSftpPushFileJob: Unable to create archive directory.', [
                'source'       => $sourcePath,
                'archive_dir'  => $archiveDir,
                'archive_type' => $archiveFolder,
            ]);
            return null;
        }

        if (!is_writable($archiveDir)) {
            \Log::warning('ProcessSftpPushFileJob: Archive directory is not writable.', [
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

        \Log::warning('ProcessSftpPushFileJob: Failed to move file to archive folder.', [
            'source'       => $sourcePath,
            'destination'  => $destPath,
            'archive_type' => $archiveFolder,
        ]);

        return null;
    }
}
