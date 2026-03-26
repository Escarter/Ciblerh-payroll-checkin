<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\Department;
use App\Models\PayslipMatchingProposal;
use App\Jobs\ProcessValidatedPayslipsJob;
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
        // ── Idempotency: skip if a proposal already exists (any status) ───────
        // This prevents duplicate fingerprints from violating the UNIQUE constraint
        // Rejected/failed/soft-deleted proposals are also checked; their prior processing is authoritative
        $basename = basename($this->absoluteFilePath);
        $existing = PayslipMatchingProposal::query()
            ->withTrashed()  // Include soft-deleted proposals (UNIQUE constraint applies to them too)
            ->when(
                $fingerprint && $supportsFingerprint,
                fn($q) => $q->where('file_fingerprint', $fingerprint),
                fn($q) => $q->where('file_name', $basename)
            )
            ->first();

        if ($existing) {
            \Log::info('ProcessSftpPushFileJob: Proposal already exists (status='.$existing->status.'), skipping.', [
                'file' => $basename,
                'proposal_id' => $existing->id,
                'status' => $existing->status,
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

        // ── Match routing ─────────────────────────────────────────────────────
        // Business rule:
        // - Use configured auto-match threshold/strategy for automatic processing
        // - No match / lower-confidence match => notify admins for manual review
        $autoMatchConfig = FeatureConfigurationService::getSftpAutoMatchConfig();
        $best = !empty($candidates) ? $candidates[0] : null;
        $bestConfidence = (float) ($best['confidence'] ?? 0);
        $meetsConfiguredAutoCriteria = $best !== null
            && FeatureConfigurationService::canAutoMatch($best, $autoMatchConfig);
        $autoMatchEnabled = (bool) ($autoMatchConfig['enabled'] ?? false);
        $configuredThreshold = (int) ($autoMatchConfig['threshold'] ?? 80);
        $notificationEmails = $autoMatchConfig['notification_email'] ?? '';

        if ($best === null) {
            \Log::info('ProcessSftpPushFileJob: No company match found; manual review required.', [
                'file' => $basename,
            ]);

            $this->notifyEmailsSafely(
                $notificationEmails,
                new SftpAutoMatchNotification($proposal, 'no_match')
            );
        } elseif (!$meetsConfiguredAutoCriteria) {
            \Log::info('ProcessSftpPushFileJob: Match below configured auto threshold; manual review required.', [
                'file'       => $basename,
                'confidence' => $bestConfidence,
                'strategy'   => $best['strategy'] ?? null,
                'threshold'  => $configuredThreshold,
            ]);

            $this->notifyEmailsSafely(
                $notificationEmails,
                new SftpAutoMatchNotification($proposal, 'manual_review')
            );
        } elseif ($bestCompany && !$bestDepartment) {
            // Company matched but department is ambiguous → notify, keep pending
            \Log::info('ProcessSftpPushFileJob: Company matched at auto-threshold but department ambiguous.', [
                'file'    => $basename,
                'company' => $bestCompany->name,
                'threshold' => $configuredThreshold,
            ]);

            $this->notifyEmailsSafely(
                $notificationEmails,
                new SftpAutoMatchNotification($proposal, 'dept_required')
            );
        } elseif ($meetsConfiguredAutoCriteria && $bestCompany && $bestDepartment && $autoMatchEnabled) {
            // Auto-validate + auto-process synchronously so the pipeline starts immediately
            $proposal->update([
                'status'          => PayslipMatchingProposal::STATUS_VALIDATED,
                'is_auto_matched' => true,
                'matched_at'      => now(),
            ]);

            try {
                ProcessValidatedPayslipsJob::dispatchSync($proposal);

                \Log::info('ProcessSftpPushFileJob: Match met configured threshold and was auto-processed.', [
                    'file'       => $basename,
                    'company'    => $bestCompany?->name,
                    'department' => $bestDepartment?->name,
                    'confidence' => $bestConfidence,
                    'strategy'   => $best['strategy'] ?? null,
                    'threshold'  => $configuredThreshold,
                ]);

                $this->notifyEmailsSafely(
                    $notificationEmails,
                    new SftpAutoMatchNotification($proposal, 'auto_validated')
                );
            } catch (\Throwable $processingException) {
                // The inner job already set the proposal to STATUS_FAILED.
                // Don't let its exception kill the intake job — the proposal record
                // exists and an admin needs to be alerted so they can intervene.
                \Log::error('ProcessSftpPushFileJob: Auto-process inner job failed; notifying admin.', [
                    'file'  => $basename,
                    'error' => $processingException->getMessage(),
                ]);

                // Reload proposal to pick up the rejection_reason written by the inner job
                $proposal->refresh();

                $this->notifyEmailsSafely(
                    $notificationEmails,
                    new SftpAutoMatchNotification($proposal, 'processing_failed')
                );
            }
        } else {
            // Auto-match disabled: keep pending and notify for manual flow.
            \Log::info('ProcessSftpPushFileJob: Match met configured threshold but auto-match is disabled; manual review required.', [
                'file'       => $basename,
                'confidence' => $bestConfidence,
                'threshold'  => $configuredThreshold,
            ]);

            $this->notifyEmailsSafely(
                $notificationEmails,
                new SftpAutoMatchNotification($proposal, 'manual_review')
            );
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
        \Log::error('ProcessSftpPushFileJob permanently failed.', [
            'file'        => $this->absoluteFilePath,
            'moved_to'    => null,
            'error'       => $exception->getMessage(),
            'note'        => 'No direct file move on failure; archival is handled by sftp:archive-proposal-files according to settings.',
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

}
