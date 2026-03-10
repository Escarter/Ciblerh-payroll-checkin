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
    ) {
        $this->onQueue('processing');
    }

    /**
     * Extract metadata from the pushed PDF and create a PayslipMatchingProposal.
     */
    public function handle(): void
    {
        // ── Idempotency: skip if a non-rejected proposal already exists ──────
        $basename = basename($this->absoluteFilePath);
        $existing = PayslipMatchingProposal::where('file_name', $basename)
            ->whereNotIn('status', [
                PayslipMatchingProposal::STATUS_REJECTED,
                PayslipMatchingProposal::STATUS_FAILED,
            ])
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

        $matchSources = $metadata['company_header_lines'] ?? [];
        if (!empty($metadata['company_raw']) && !in_array($metadata['company_raw'], $matchSources, true)) {
            $matchSources[] = $metadata['company_raw'];
        }
        // Filename stem — clean it before using it as a match source so that
        // date tokens, pay-period keywords and month names don't pollute matching.
        //
        // e.g. "PERENCO_WORK_OVER_JUILLET_2025.pdf"
        //    → strip separators   → "PERENCO WORK OVER JUILLET 2025"
        //    → strip years        → "PERENCO WORK OVER JUILLET"
        //    → strip month names  → "PERENCO WORK OVER"
        //    → strip noise words  → "PERENCO WORK OVER"   ← clean company token
        $filenameStem = pathinfo($this->originalFilename, PATHINFO_FILENAME);

        // 1. Replace separators (dash, underscore, dot) with spaces
        $filenameStem = preg_replace('/[-_.\s]+/', ' ', $filenameStem);

        // 2. Remove 4-digit years (1990–2099) and standalone 1-2-digit month numbers
        $filenameStem = preg_replace('/\b(19|20)\d{2}\b/', '', $filenameStem);
        $filenameStem = preg_replace('/\b(0?[1-9]|1[0-2])\b/', '', $filenameStem);

        // 3. Remove French and English month names (they encode the pay period, not the company)
        $monthPattern = '/\b(janvier|février|fevrier|mars|avril|mai|juin|juillet|août|aout'
            . '|septembre|octobre|novembre|décembre|decembre'
            . '|january|february|march|april|may|june|july|august|september|october|november|december)\b/iu';
        $filenameStem = preg_replace($monthPattern, '', $filenameStem);

        // 4. Remove common payslip noise keywords
        $noisePattern = '/\b(bulletin|paie|fiche|salaire|payslip|salary|wage|slip|pay|bulletin_de_paie)\b/iu';
        $filenameStem = preg_replace($noisePattern, '', $filenameStem);

        // 5. Collapse whitespace
        $filenameStem = trim(preg_replace('/\s+/', ' ', $filenameStem));

        if (!empty($filenameStem)) {
            $matchSources[] = $filenameStem;
        }

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
            'raw_text_preview'     => $metadata['raw_text_preview'],
            'candidates'           => $candidates,
            'best_match'           => !empty($candidates) ? $candidates[0] : null,
        ];

        // ── Create the proposal ──────────────────────────────────────────────
        $proposal = PayslipMatchingProposal::create([
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
        ]);

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

                    $this->notifyEmails(
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

                    $this->notifyEmails(
                        $autoMatchConfig['notification_email'] ?? '',
                        new SftpAutoMatchNotification($proposal, 'auto_validated')
                    );
                }
            }
        }

        // ── Move file to processed/ subfolder so scanner skips it on future runs ──
        $pushDir = dirname($this->absoluteFilePath);
        $processedDir = $pushDir . '/processed';

        if (is_dir($processedDir) && is_writable($processedDir)) {
            $destPath = $processedDir . '/' . $basename;
            if (@rename($this->absoluteFilePath, $destPath)) {
                // Update proposal with new location
                $proposal->update([
                    'file_path'       => $destPath,
                    'local_file_path' => $destPath,
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
    }

    public function failed(Throwable $exception): void
    {
        \Log::error('ProcessSftpPushFileJob permanently failed.', [
            'file'  => $this->absoluteFilePath,
            'error' => $exception->getMessage(),
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
}
