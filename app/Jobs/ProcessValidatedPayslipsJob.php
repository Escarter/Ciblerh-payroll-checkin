<?php

namespace App\Jobs;

use App\Models\PayslipMatchingProposal;
use App\Models\SendPayslipProcess;
use App\Models\User;
use App\Jobs\Plan\PayslipSendingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessValidatedPayslipsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;
    public $timeout = 600;
    public $failOnTimeout = true;

    private const FAIL_NOT_VALIDATED = 'PV_NOT_VALIDATED';
    private const FAIL_NO_COMPANY = 'PV_NO_COMPANY';
    private const FAIL_NOT_DOWNLOADED = 'PV_NOT_DOWNLOADED';
    private const FAIL_LOCAL_FILE_MISSING = 'PV_LOCAL_FILE_MISSING';
    private const FAIL_PROCESSING_EXCEPTION = 'PV_PROCESSING_EXCEPTION';
    private const FAIL_JOB_PERMANENT = 'PV_JOB_PERMANENT_FAILURE';

    private PayslipMatchingProposal $proposal;

    /**
     * Create a new job instance.
     */
    public function __construct(PayslipMatchingProposal $proposal)
    {
        $this->proposal = $proposal;
        $this->onQueue('processing');
    }

    /**
     * Execute the job.
     * Uses pre-downloaded SFTP file and creates SendPayslipProcess
     * to plug into existing PayslipSendingPlan workflow
     */
    public function handle(): void
    {
        try {
            // Verify proposal is in validated state
            if ($this->proposal->status !== PayslipMatchingProposal::STATUS_VALIDATED) {
                \Log::warning("[" . self::FAIL_NOT_VALIDATED . "] Proposal {$this->proposal->id} is not validated. Current status: {$this->proposal->status}", [
                    'failure_code' => self::FAIL_NOT_VALIDATED,
                    'proposal_id' => $this->proposal->id,
                    'status' => $this->proposal->status,
                ]);
                return;
            }

            // Company must always be set
            if (!$this->proposal->matched_to_company_id) {
                $this->proposal->update([
                    'status' => PayslipMatchingProposal::STATUS_FAILED,
                    'rejection_reason' => $this->formatFailureReason(self::FAIL_NO_COMPANY, 'No company assigned to this proposal.'),
                ]);
                \Log::error("[" . self::FAIL_NO_COMPANY . "] Cannot process proposal {$this->proposal->id}: No company assigned", [
                    'failure_code' => self::FAIL_NO_COMPANY,
                    'proposal_id' => $this->proposal->id,
                ]);
                return;
            }

            // Verify file was successfully downloaded during fetch phase
            if ($this->proposal->download_status !== 'downloaded' || !$this->proposal->local_file_path) {
                $this->proposal->update([
                    'status' => PayslipMatchingProposal::STATUS_FAILED,
                    'rejection_reason' => $this->formatFailureReason(
                        self::FAIL_NOT_DOWNLOADED,
                        'File was not successfully downloaded from SFTP: ' . ($this->proposal->download_error ?? 'Unknown error')
                    ),
                ]);
                \Log::error("[" . self::FAIL_NOT_DOWNLOADED . "] Cannot process proposal {$this->proposal->id}: File download failed", [
                    'failure_code' => self::FAIL_NOT_DOWNLOADED,
                    'proposal_id' => $this->proposal->id,
                    'download_status' => $this->proposal->download_status,
                    'download_error' => $this->proposal->download_error,
                ]);
                return;
            }

            // Resolve local file path (self-heals stale path after archive moves)
            $rawFilePath = $this->proposal->resolveExistingLocalFilePath();

            // Verify file still exists in storage
            if (!$rawFilePath || !file_exists($rawFilePath)) {
                $this->proposal->update([
                    'status' => PayslipMatchingProposal::STATUS_FAILED,
                    'rejection_reason' => $this->formatFailureReason(
                        self::FAIL_LOCAL_FILE_MISSING,
                        'Local file no longer exists: ' . ($this->proposal->local_file_path ?? 'unknown path')
                    ),
                ]);
                \Log::error("[" . self::FAIL_LOCAL_FILE_MISSING . "] File not found for proposal {$this->proposal->id}: " . ($this->proposal->local_file_path ?? 'unknown path'), [
                    'failure_code' => self::FAIL_LOCAL_FILE_MISSING,
                    'proposal_id' => $this->proposal->id,
                    'local_file_path' => $this->proposal->local_file_path,
                    'resolved_raw_file_path' => $rawFilePath,
                ]);
                return;
            }

            // Create SendPayslipProcess record
            // This triggers the standard splitting/encryption/sending pipeline
            $sendPayslipProcess = SendPayslipProcess::create([
                'user_id' => auth()->id() ?? 1,  // System user or authenticated user
                'department_id' => $this->proposal->matched_to_department_id ?? null,
                'company_id' => $this->proposal->matched_to_company_id,
                'month' => $this->proposal->matched_month,
                'year' => $this->proposal->matched_year,
                'raw_file' => $rawFilePath,  // Full filesystem path to local file
                'sftp_proposal_id' => $this->proposal->id, // Direct link for reliable status callbacks
                'destination_directory' => $this->proposal->matched_to_department_id
                    ? "dept_{$this->proposal->matched_to_department_id}_" . date('YmdHis')
                    : "sftp_company_{$this->proposal->matched_to_company_id}_" . date('YmdHis'),
                'status' => 'processing',
                'percentage_completion' => 0,
            ]);

            // Log the processing action
            $user = auth()->user()
                ?? User::find($sendPayslipProcess->user_id)
                ?? User::role('admin')->first();

            if ($user) {
                auditLog(
                    $user,
                    'sftp_payslip_processing_started',
                    'queue',
                    "SFTP payslip {$this->proposal->file_name} queued for processing",
                    $sendPayslipProcess,
                    [],
                    $sendPayslipProcess->getAttributes(),
                    [
                        'sftp_proposal_id' => $this->proposal->id,
                        'source' => 'sftp',
                    ]
                );
            } else {
                \Log::warning('Skipping SFTP processing audit log: no user available', [
                    'proposal_id' => $this->proposal->id,
                    'process_id' => $sendPayslipProcess->id,
                ]);
            }

            // Start the full pipeline: split → rename/encrypt → finalize → send
            PayslipSendingPlan::start($sendPayslipProcess);

            \Log::info("SFTP payslip {$this->proposal->file_name} queued for processing via PayslipSendingPlan. Process ID: {$sendPayslipProcess->id}");

            // IMPORTANT: Do not mark proposal as processed here.
            // PayslipSendingPlan is asynchronous; proposal status is finalized
            // when the downstream SendPayslipProcess completes.

        } catch (Throwable $e) {
            \Log::error("[" . self::FAIL_PROCESSING_EXCEPTION . "] Error processing validated SFTP proposal {$this->proposal->id}: " . $e->getMessage(), [
                'failure_code' => self::FAIL_PROCESSING_EXCEPTION,
                'proposal_id' => $this->proposal->id,
                'exception' => $e,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $this->proposal->update([
                'status' => PayslipMatchingProposal::STATUS_FAILED,
                'rejection_reason' => $this->formatFailureReason(self::FAIL_PROCESSING_EXCEPTION, 'Processing error: ' . $e->getMessage()),
            ]);

            throw $e;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(Throwable $exception): void
    {
        \Log::error("[" . self::FAIL_JOB_PERMANENT . "] ProcessValidatedPayslipsJob permanently failed for proposal {$this->proposal->id}: " . $exception->getMessage(), [
            'failure_code' => self::FAIL_JOB_PERMANENT,
            'proposal_id' => $this->proposal->id,
            'exception' => $exception,
        ]);
    }

    private function formatFailureReason(string $code, string $message): string
    {
        return '[' . $code . '] ' . $message;
    }
}
