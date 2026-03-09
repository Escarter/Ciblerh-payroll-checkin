<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\Department;
use App\Models\PayslipMatchingProposal;
use App\Services\SftpPayslipService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
        $candidates     = [];
        $bestCompany    = null;
        $bestDepartment = null;

        if (!empty($metadata['company_raw'])) {
            $candidates = $service->matchCompanyFuzzy($metadata['company_raw']);
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
            'company_raw'      => $metadata['company_raw'],
            'raw_text_preview' => $metadata['raw_text_preview'],
            'candidates'       => $candidates,
            'best_match'       => !empty($candidates) ? $candidates[0] : null,
        ];

        // ── Create the proposal ──────────────────────────────────────────────
        $proposal = PayslipMatchingProposal::create([
            'file_path'               => $this->absoluteFilePath,
            'local_file_path'         => $this->absoluteFilePath,
            'file_name'               => $basename,
            'file_size'               => filesize($this->absoluteFilePath) ?: null,
            'file_timestamp'          => filemtime($this->absoluteFilePath) ?: null,
            'proposed_match'          => $proposedMatch,
            'matched_to_company_id'   => $bestCompany?->id,
            'matched_to_department_id'=> $bestDepartment?->id,
            'matched_month'           => $metadata['month'],
            'matched_year'            => $metadata['year'],
            'download_status'         => 'downloaded',
            'status'                  => PayslipMatchingProposal::STATUS_PENDING,
        ]);

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
}
