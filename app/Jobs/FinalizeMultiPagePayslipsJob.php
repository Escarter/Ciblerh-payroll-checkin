<?php

namespace App\Jobs;

use App\Models\Payslip;
use App\Models\SendPayslipProcess;
use mikehaertl\pdftk\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * FinalizeMultiPagePayslipsJob
 * 
 * Encrypts all combined but unencrypted payslips after page combination is complete.
 * This is the final step after RenameEncryptPdfJob batch, before SendPayslipJob.
 * 
 * Purpose: Ensure multi-page payslips are fully combined BEFORE encryption.
 * This avoids the critical bug where encrypted page 1 cannot be combined with unencrypted page 2.
 * 
 * Critical Implementation Details:
 * - Files are stored in the SAME directory as SendPayslipProcess->destination_directory
 * - This ensures SendPayslipJob can find them when scanning allFiles()
 * - Temp files are validated before deletion (must contain "temp_unenc")
 * - Individual payslip failures don't crash the entire job
 */
class FinalizeMultiPagePayslipsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected $process_id;

    public function __construct($process_id)
    {
        $this->process_id = $process_id;
        $this->queue = 'pdf-processing';
    }

    public function handle(): void
    {
        // Load the process to get destination directory and other context
        $process = SendPayslipProcess::findOrFail($this->process_id);
        $destination_directory = $process->destination_directory;

        // Find all payslips for this process that are PENDING encryption
        $pendingPayslips = Payslip::where('send_payslip_process_id', $this->process_id)
            ->where('encryption_status', Payslip::STATUS_PENDING)
            ->get();

        if ($pendingPayslips->isEmpty()) {
            Log::info('FinalizeMultiPagePayslipsJob: No pending payslips to encrypt', [
                'process_id' => $this->process_id
            ]);
            return;
        }

        Log::info('FinalizeMultiPagePayslipsJob: Starting encryption of pending payslips', [
            'process_id' => $this->process_id,
            'count' => $pendingPayslips->count(),
            'destination' => $destination_directory
        ]);

        $tempDir = config('ciblerh.temp_dir');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $successCount = 0;
        $failureCount = 0;

        foreach ($pendingPayslips as $payslip) {
            try {
                // IDEMPOTENCY CHECK: Skip if already encrypted successfully
                // This handles job retries - don't re-encrypt already-done payslips
                if ($payslip->encryption_status === Payslip::STATUS_SUCCESSFUL) {
                    Log::info('FinalizeMultiPagePayslipsJob: Skipping already-encrypted payslip', [
                        'payslip_id' => $payslip->id,
                        'file' => $payslip->file
                    ]);
                    $successCount++;
                    continue;
                }
                
                $this->encryptPayslip($payslip, $destination_directory, $tempDir);
                $successCount++;
            } catch (\Throwable $e) {
                Log::error('FinalizeMultiPagePayslipsJob: Failed to encrypt payslip', [
                    'payslip_id' => $payslip->id,
                    'employee_id' => $payslip->employee_id,
                    'matricule' => $payslip->matricule,
                    'error' => $e->getMessage(),
                    'process_id' => $this->process_id
                ]);
                $failureCount++;

                // Mark THIS payslip as failed, but continue processing others
                try {
                    $payslip->update([
                        'encryption_status' => Payslip::STATUS_FAILED,
                        'failure_reason' => 'Final encryption failed: ' . substr($e->getMessage(), 0, 100)
                    ]);
                } catch (\Throwable $updateError) {
                    Log::error('FinalizeMultiPagePayslipsJob: Could not update failed payslip status', [
                        'payslip_id' => $payslip->id,
                        'error' => $updateError->getMessage()
                    ]);
                }
            }
        }

        Log::info('FinalizeMultiPagePayslipsJob: Encryption batch completed', [
            'process_id' => $this->process_id,
            'success' => $successCount,
            'failed' => $failureCount,
            'total' => $pendingPayslips->count()
        ]);
    }

    /**
     * Encrypt a single payslip's unencrypted temp file
     * 
     * @throws \Exception if encryption fails
     */
    private function encryptPayslip(Payslip $payslip, string $destination_directory, string $tempDir): void
    {
        // Get employee - be defensive about deletion
        $employee = $payslip->employee;
        if (!$employee) {
            throw new \Exception('Employee not found or deleted');
        }

        // Load relationships if needed
        if (!$employee->relationLoaded('company') || !$employee->relationLoaded('department')) {
            $employee->load('company', 'department');
        }
        
        if (empty($payslip->file)) {
            throw new \Exception('Payslip file path is empty');
        }

        // Verify temp file exists
        if (!Storage::disk('modified')->exists($payslip->file)) {
            throw new \Exception('Unencrypted temp file not found: ' . $payslip->file);
        }

        // SAFETY: Verify this is actually a temp file before we trust deleting it
        if (strpos($payslip->file, 'temp_unenc') === false) {
            throw new \Exception('File does not appear to be a temp file (missing temp_unenc marker): ' . $payslip->file);
        }

        // Create final encrypted filename - store WITHIN destination directory
        $finalFilename = $employee->matricule . '_' . $payslip->month . '.pdf';
        $finalFile = $destination_directory . '/' . $finalFilename;  // Store in same directory as process

        // Get unencrypted file path
        $unencryptedPath = Storage::disk('modified')->path($payslip->file);
        $finalPath = Storage::disk('modified')->path($finalFile);

        // Encrypt the combined file
        $pdf = new Pdf($unencryptedPath, ['command' => config('ciblerh.pdftk_path')]);
        $pdf->tempDir = $tempDir;
        
        $encryptResult = $pdf->setUserPassword($employee->pdf_password)
            ->passwordEncryption(128)
            ->saveAs($finalPath);

        if (!$encryptResult) {
            throw new \Exception('PDF encryption operation returned false');
        }

        // Verify encrypted file was actually created
        if (!Storage::disk('modified')->exists($finalFile)) {
            throw new \Exception('Encrypted output file was not created: ' . $finalFile);
        }

        // Verify encrypted file has content
        $fileSize = Storage::disk('modified')->size($finalFile);
        if ($fileSize <= 0) {
            // Clean up the empty file
            Storage::disk('modified')->delete($finalFile);
            throw new \Exception('Encrypted file created but is empty (0 bytes)');
        }

        // NOW it's safe to delete the temp file (we verified encrypted output exists)
        try {
            if (Storage::disk('modified')->exists($payslip->file)) {
                Storage::disk('modified')->delete($payslip->file);
            }
        } catch (\Throwable $e) {
            Log::warning('FinalizeMultiPagePayslipsJob: Could not delete temp file', [
                'payslip_id' => $payslip->id,
                'temp_file' => $payslip->file,
                'error' => $e->getMessage()
            ]);
            // Don't fail the entire payslip for this, just log it
        }

        // Update payslip record with encrypted file path
        $payslip->update([
            'file' => $finalFile,
            'encryption_status' => Payslip::STATUS_SUCCESSFUL,
            'encryption_status_note' => null
        ]);

        Log::info('FinalizeMultiPagePayslipsJob: Successfully encrypted payslip', [
            'payslip_id' => $payslip->id,
            'employee_id' => $employee->id,
            'matricule' => $employee->matricule,
            'final_file' => $finalFile,
            'file_size_bytes' => $fileSize,
            'process_id' => $this->process_id
        ]);
    }
}
