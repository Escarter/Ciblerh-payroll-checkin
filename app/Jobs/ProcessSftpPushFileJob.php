<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\Department;
use App\Models\PayslipMatchingProposal;
use App\Models\Setting;
use App\Jobs\ProcessValidatedPayslipsJob;
use App\Notifications\SftpAutoMatchNotification;
use App\Notifications\SftpProposalCreatedNotification;
use App\Services\FeatureConfigurationService;
use App\Services\PayslipProcessGuardService;
use App\Services\PayslipProcessStartResult;
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

    private const CODE_DUPLICATE_LOCK = 'SP_DUPLICATE_LOCK';
    private const CODE_EXISTING_PROPOSAL = 'SP_EXISTING_PROPOSAL';
    private const CODE_LOCAL_FILE_MISSING_PRECREATE = 'SP_LOCAL_FILE_MISSING_PRECREATE';
    private const CODE_INNER_PROCESSING_FAILED = 'SP_INNER_PROCESSING_FAILED';
    private const CODE_JOB_PERMANENT_FAILURE = 'SP_JOB_PERMANENT_FAILURE';

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
            \Log::info('[' . self::CODE_DUPLICATE_LOCK . '] ProcessSftpPushFileJob: Processing lock active, skipping duplicate run.', [
                'failure_code' => self::CODE_DUPLICATE_LOCK,
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
            \Log::info('[' . self::CODE_EXISTING_PROPOSAL . '] ProcessSftpPushFileJob: Proposal already exists (status='.$existing->status.'), skipping.', [
                'failure_code' => self::CODE_EXISTING_PROPOSAL,
                'file' => $basename,
                'proposal_id' => $existing->id,
                'status' => $existing->status,
            ]);
            return;
        }

        // ── Verify file still exists ─────────────────────────────────────────
        if (!file_exists($this->absoluteFilePath)) {
            \Log::error('[' . self::CODE_LOCAL_FILE_MISSING_PRECREATE . '] ProcessSftpPushFileJob: File not found on disk.', [
                'failure_code' => self::CODE_LOCAL_FILE_MISSING_PRECREATE,
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

        // ── Send proposal creation notification (if enabled) ──────────────────
        $this->sendProposalCreatedNotification($proposal);

        // ── Match routing ─────────────────────────────────────────────────────
        // Business rule:
        // - Auto-match: auto-validate and auto-send when threshold/strategy are met
        // - Manual match: validate in UI, then click Process to send
        // - No match / lower-confidence match => notify admins for manual review
        $autoMatchConfig = FeatureConfigurationService::getSftpAutoMatchConfig();
        $best = !empty($candidates) ? $candidates[0] : null;
        $bestConfidence = (float) ($best['confidence'] ?? 0);
        $meetsConfiguredAutoCriteria = $best !== null
            && FeatureConfigurationService::canAutoMatch($best, $autoMatchConfig);
        $autoMatchEnabled = (bool) ($autoMatchConfig['enabled'] ?? false);
        $configuredThreshold = (int) ($autoMatchConfig['threshold'] ?? 80);
        $notificationEmails = $autoMatchConfig['notification_email'] ?? '';
        $hasEmailConfig = !empty(trim($notificationEmails));

        if ($best === null) {
            \Log::info('ProcessSftpPushFileJob: No company match found; manual review required.', [
                'file' => $basename,
                'notification_configured' => $hasEmailConfig,
            ]);

            if ($hasEmailConfig) {
                $this->notifyEmailsSafely(
                    $notificationEmails,
                    new SftpAutoMatchNotification($proposal, 'no_match')
                );
            } else {
                \Log::warning('ProcessSftpPushFileJob: No company match but notification emails not configured.', [
                    'file' => $basename,
                    'proposal_id' => $proposal->id,
                ]);
            }
        } elseif (!$meetsConfiguredAutoCriteria) {
            // Add detailed logging to show why auto-processing didn't happen
            $candidateStrategy = $best['strategy'] ?? 'unknown';
            $candidateRank = \App\Services\FeatureConfigurationService::getStrategyRank($candidateStrategy);
            $minStrategy = $autoMatchConfig['min_strategy'] ?? 'fuzzy';
            $minRank = \App\Services\FeatureConfigurationService::getStrategyRank($minStrategy);
            $confidencePct = $bestConfidence * 100;
            
            \Log::info('ProcessSftpPushFileJob: Match does not meet auto-processing criteria; manual review required.', [
                'file' => $basename,
                'confidence_pct' => $confidencePct,
                'confidence_threshold' => $configuredThreshold,
                'strategy' => $candidateStrategy,
                'strategy_rank' => $candidateRank,
                'min_strategy' => $minStrategy,
                'min_rank' => $minRank,
                'auto_match_enabled' => $autoMatchEnabled,
                'meets_confidence' => $confidencePct >= $configuredThreshold,
                'meets_strategy' => $candidateRank >= $minRank,
                'notification_configured' => $hasEmailConfig,
            ]);

            if ($hasEmailConfig) {
                $this->notifyEmailsSafely(
                    $notificationEmails,
                    new SftpAutoMatchNotification($proposal, 'manual_review')
                );
            } else {
                \Log::warning('ProcessSftpPushFileJob: Match below threshold but notification emails not configured.', [
                    'file' => $basename,
                    'confidence' => $bestConfidence,
                    'threshold' => $configuredThreshold,
                ]);
            }
        } elseif ($bestCompany && !$bestDepartment) {
            // Company matched but department is ambiguous → notify, keep pending
            \Log::info('ProcessSftpPushFileJob: Company matched at auto-threshold but department ambiguous.', [
                'file'    => $basename,
                'company' => $bestCompany->name,
                'threshold' => $configuredThreshold,
                'notification_configured' => $hasEmailConfig,
            ]);

            if ($hasEmailConfig) {
                $this->notifyEmailsSafely(
                    $notificationEmails,
                    new SftpAutoMatchNotification($proposal, 'dept_required')
                );
            } else {
                \Log::warning('ProcessSftpPushFileJob: Department ambiguous but notification emails not configured.', [
                    'file' => $basename,
                    'company' => $bestCompany->name,
                ]);
            }
        } elseif ($meetsConfiguredAutoCriteria && $bestCompany && $bestDepartment && $autoMatchEnabled) {
            $proposal->update([
                'status'          => PayslipMatchingProposal::STATUS_VALIDATED,
                'is_auto_matched' => true,
                'matched_at'      => now(),
            ]);

            $guard = app(PayslipProcessGuardService::class);
            $periodEvaluation = $guard->evaluateStart(
                $bestDepartment->id,
                (int) $bestCompany->id,
                $proposal->matched_month,
                (int) $proposal->matched_year,
            );

            if ($periodEvaluation->action === PayslipProcessStartResult::ACTION_BLOCK) {
                $blockMessage = $periodEvaluation->message() ?? __('payslips.process_already_running_or_completed');
                $proposal->update(['rejection_reason' => $blockMessage]);

                \Log::warning('ProcessSftpPushFileJob: Auto-match blocked — period already handled.', [
                    'file' => $basename,
                    'proposal_id' => $proposal->id,
                    'existing_process_id' => $periodEvaluation->process?->id,
                    'message' => $blockMessage,
                ]);

                if ($hasEmailConfig) {
                    $this->notifyEmailsSafely(
                        $notificationEmails,
                        new SftpAutoMatchNotification($proposal, 'processing_failed')
                    );
                }

                return;
            }

            try {
                ProcessValidatedPayslipsJob::dispatchSync($proposal);

                \Log::info('ProcessSftpPushFileJob: Match met configured threshold and was auto-processed.', [
                    'file'       => $basename,
                    'company'    => $bestCompany?->name,
                    'department' => $bestDepartment?->name,
                    'confidence' => $bestConfidence,
                    'strategy'   => $best['strategy'] ?? null,
                    'threshold'  => $configuredThreshold,
                    'notification_configured' => $hasEmailConfig,
                ]);

                if ($hasEmailConfig) {
                    $this->notifyEmailsSafely(
                        $notificationEmails,
                        new SftpAutoMatchNotification($proposal, 'auto_validated')
                    );
                } else {
                    \Log::warning('ProcessSftpPushFileJob: Auto-process successful but notification emails not configured.', [
                        'file' => $basename,
                        'proposal_id' => $proposal->id,
                    ]);
                }
            } catch (\Throwable $processingException) {
                \Log::error('[' . self::CODE_INNER_PROCESSING_FAILED . '] ProcessSftpPushFileJob: Auto-process inner job failed; notifying admin.', [
                    'failure_code' => self::CODE_INNER_PROCESSING_FAILED,
                    'file'  => $basename,
                    'error' => $processingException->getMessage(),
                    'notification_configured' => $hasEmailConfig,
                ]);

                $proposal->refresh();

                if ($hasEmailConfig) {
                    $this->notifyEmailsSafely(
                        $notificationEmails,
                        new SftpAutoMatchNotification($proposal, 'processing_failed')
                    );
                } else {
                    \Log::warning('ProcessSftpPushFileJob: Processing failed but notification emails not configured.', [
                        'file' => $basename,
                        'error' => $processingException->getMessage(),
                    ]);
                }
            }
        } else {
            // Auto-match disabled: keep pending and notify for manual flow.
            \Log::info('ProcessSftpPushFileJob: Match met configured threshold but auto-match is disabled; manual review required.', [
                'file'       => $basename,
                'confidence' => $bestConfidence,
                'threshold'  => $configuredThreshold,
                'notification_configured' => $hasEmailConfig,
            ]);

            if ($hasEmailConfig) {
                $this->notifyEmailsSafely(
                    $notificationEmails,
                    new SftpAutoMatchNotification($proposal, 'manual_review')
                );
            } else {
                \Log::warning('ProcessSftpPushFileJob: Auto-match disabled but notification emails not configured.', [
                    'file' => $basename,
                    'proposal_id' => $proposal->id,
                ]);
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
        \Log::error('[' . self::CODE_JOB_PERMANENT_FAILURE . '] ProcessSftpPushFileJob permanently failed.', [
            'failure_code' => self::CODE_JOB_PERMANENT_FAILURE,
            'file'        => $this->absoluteFilePath,
            'moved_to'    => null,
            'error'       => $exception->getMessage(),
            'note'        => 'No direct file move on failure; archival is handled by sftp:archive-proposal-files according to settings.',
        ]);
    }

    /**
     * Send notification about proposal creation if enabled in settings
     */
    private function sendProposalCreatedNotification(PayslipMatchingProposal $proposal): void
    {
        $setting = Setting::first();

        if (!$setting || !$setting->sftp_match_created_notification_enabled) {
            return;
        }

        $emailList = $setting->sftp_match_created_notification_email ?? '';

        if (empty(trim($emailList))) {
            return;
        }

        try {
            $this->notifyEmails(
                $emailList,
                new SftpProposalCreatedNotification($proposal)
            );
        } catch (Throwable $e) {
            \Log::warning('ProcessSftpPushFileJob: Proposal creation notification failed (non-fatal).', [
                'proposal_id' => $proposal->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send the given notification to every email address in a
     * comma-separated string (safe no-op when the string is empty).
     */
    private function notifyEmails(string $emailList, \Illuminate\Notifications\Notification $notification): void
    {
        $emails = array_filter(array_map('trim', preg_split('/[\s,]+/', $emailList, -1, PREG_SPLIT_NO_EMPTY)));

        if (empty($emails)) {
            \Log::warning('ProcessSftpPushFileJob: Empty email list provided to notifyEmails', [
                'file' => basename($this->absoluteFilePath),
                'notification_type' => get_class($notification),
            ]);
            return;
        }

        foreach ($emails as $email) {
            try {
                Notification::route('mail', $email)->notify(clone $notification);
                \Log::info('ProcessSftpPushFileJob: Notification email queued', [
                    'recipient' => $email,
                    'notification_type' => get_class($notification),
                    'file' => basename($this->absoluteFilePath),
                ]);
            } catch (\Throwable $e) {
                \Log::error('ProcessSftpPushFileJob: Failed to queue notification email', [
                    'recipient' => $email,
                    'error' => $e->getMessage(),
                    'file' => basename($this->absoluteFilePath),
                ]);
            }
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
