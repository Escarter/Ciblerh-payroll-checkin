<?php

namespace App\Jobs;

use Exception;
use App\Models\Payslip;
use mikehaertl\pdftk\Pdf;
use App\Models\Department;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use App\Models\SendPayslipProcess;
use Escarter\PopplerPhp\PdfToText;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RenameEncryptPdfJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 20;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 3;

    protected $process;
    protected $department; // null when processing company-level (no department)
    protected $destination;
    protected $chunk;
    protected $month;
    protected $process_id;
    protected $user_id;
        protected $year;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Collection $chunk, $process_id)
    {
        $this->process = SendPayslipProcess::findOrFail($process_id);
        $this->department = $this->process->department_id
            ? Department::findOrFail($this->process->department_id)
            : null;
        $this->destination = $this->process->destination_directory;
        $this->month = $this->process->month;
        $this->chunk = $chunk;
        $this->user_id = $this->process->user_id;
        $this->process_id = $process_id;
            $this->year = $this->process->year ?? now()->year;
        $this->queue = 'pdf-processing';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->batch()->cancelled()) {
            // Determine if the batch has been cancelled...
            return;
        }

        $pay_month = $this->month;

        // Ensure temp directory exists for PDF processing
        $tempDir = config('ciblerh.temp_dir');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        Storage::disk('modified')->makeDirectory($this->destination);

        // Resolve employee pool: department employees, or all company employees with 'employee' role if no department
        $employees = $this->department
            ? $this->department->employees
            : \App\Models\User::where('company_id', $this->process->company_id)
                ->whereHas('roles', fn($q) => $q->where('name', 'employee'))
                ->get();

        foreach ($this->chunk as $file) {

            $from_path = Storage::disk('splitted')->path($file);
            // $pdf_text = PdfToText::getText($from_path, '/usr/local/bin/pdftotext');
            $pdf_text = PdfToText::getText($from_path, config('ciblerh.pdftotext_path'));

            collect($employees)->each(function ($employee) use ($pdf_text, $file, $pay_month) {

                if (empty($employee->matricule)) {
                    $created_record = Payslip::create([
                        'user_id' => $this->user_id,
                        'send_payslip_process_id' => $this->process_id,
                        'employee_id' => $employee->id,
                        'company_id' => $employee->company_id,
                        'department_id' => $employee->department_id,
                        'service_id' => $employee->service_id,
                        'first_name' => $employee->first_name,
                        'last_name' => $employee->last_name,
                        'email' => $employee->email,
                        'phone' => !is_null($employee->professional_phone_number) ? $employee->professional_phone_number : $employee->personal_phone_number,
                        'matricule' => $employee->matricule,
                        'month' => $pay_month,
                            'year' => $this->year,
                    ]);
                    $created_record->update([
                        'encryption_status' => Payslip::STATUS_FAILED,
                        'email_sent_status' => Payslip::STATUS_FAILED,
                        'sms_sent_status' => Payslip::STATUS_FAILED,
                        'failure_reason' => __('payslips.user_matricule_empty')
                    ]);
                } else {

                    if (\App\Models\User::matriculeTokenExistsInPdfText($pdf_text, $employee->matricule)) {

                        if (Storage::disk('splitted')->exists($file)) {
                            // CRITICAL: Use database locking to prevent race conditions
                            // Multiple jobs may process pages for same employee in parallel
                            // Lock ensures we get the most recent record and prevent duplicate creation
                            $record_exists = Payslip::where('employee_id', $employee->id)
                                ->where('month', $pay_month)
                                    ->where('year', $this->year)
                                ->lockForUpdate()  // Acquire lock until transaction ends
                                ->first();

                            if (empty($record_exists) || empty($record_exists->file)) {
                                // First file for this employee - copy to temp unencrypted (encryption deferred)
                                // Use unique filename: process_id + timestamp to prevent overwrites in concurrent scenarios
                                $unique_suffix = $this->process_id . '_' . now()->timestamp;
                                $temp_unencrypted_file = $this->destination . '/temp_unenc_' . $employee->matricule . '_' . $pay_month . '_' . $unique_suffix . '.pdf';
                                $source_path = Storage::disk('splitted')->path($file);
                                $dest_path = Storage::disk('modified')->path($temp_unencrypted_file);
                                
                                try {
                                    if (!copy($source_path, $dest_path)) {
                                        throw new \Exception('Failed to copy PDF file');
                                    }

                                    if (file_exists($dest_path)) {
                                        if (empty($record_exists)) {
                                            // Create record pointing to temp unencrypted file
                                            $record = createPayslipRecord($employee, $pay_month, $this->process_id, $this->user_id, $temp_unencrypted_file, $this->year);
                                            // Mark encryption as PENDING (will be finalized after combination)
                                            $record->update([
                                                'encryption_status' => Payslip::STATUS_PENDING,
                                                'encryption_status_note' => 'Pending page combination and encryption'
                                            ]);
                                        } else {
                                            $record_exists->update([
                                                'file' => $temp_unencrypted_file,
                                                'encryption_status' => Payslip::STATUS_PENDING,
                                                'encryption_status_note' => 'Pending page combination and encryption'
                                            ]);
                                        }
                                    }
                                } catch (\Exception $e) {
                                    Log::error('Failed to copy PDF file for employee', [
                                        'employee_id' => $employee->id,
                                        'matricule' => $employee->matricule,
                                        'error' => $e->getMessage()
                                    ]);
                                }
                            } else {
                                // Employee already has a file - combine with existing one (both unencrypted)
                                // Generate new combined filename with unique suffix
                                $unique_suffix = $this->process_id . '_' . now()->timestamp;
                                $temp_combined_file = $this->destination . '/temp_unenc_' . $employee->matricule . '_' . $pay_month . '_' . $unique_suffix . '.pdf';
                                $this->combinePdfFiles($employee, $file, $record_exists->file, $temp_combined_file, $pay_month);
                            }
                        }
                    }
                }
            });
        }
    }

    /**
     * Combine multiple unencrypted PDF files for an employee (multi-page payslip)
     * 
     * IMPORTANT: Files are combined UNENCRYPTED. Encryption happens later in
     * the FinalizeMultiPagePayslipsJob after all page combinations are complete.
     * 
     * This architecture fix prevents the critical bug where encrypted page 1
     * cannot be combined with unencrypted page 2 (pdftk can't decrypt without password).
     */
    private function combinePdfFiles($employee, $newFile, $existingFile, $tempCombinedPath, $pay_month)
    {
        try {
            // Both files are unencrypted temp files at this stage
            $existingFilePath = Storage::disk('modified')->path($existingFile);
            $newFilePath = Storage::disk('splitted')->path($newFile);
            $tempCombinedFile = Storage::disk('modified')->path($tempCombinedPath);
            
            // Check if existing temp file exists
            if (!Storage::disk('modified')->exists($existingFile)) {
                // Existing file doesn't exist, just copy the new one as temp
                try {
                    if (!copy(Storage::disk('splitted')->path($newFile), $tempCombinedFile)) {
                        throw new \Exception('Failed to copy new page file');
                    }
                    
                    // Use locking for safe concurrent updates
                    $payslip = Payslip::where('employee_id', $employee->id)
                        ->where('month', $pay_month)
                            ->where('year', $this->year)
                        ->lockForUpdate()
                        ->first();
                    
                    if ($payslip) {
                        $payslip->update([
                            'file' => $tempCombinedPath,
                            'encryption_status' => Payslip::STATUS_PENDING,
                            'encryption_status_note' => 'Pending encryption after page combination'
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to copy page file for combination', [
                        'employee_id' => $employee->id,
                        'matricule' => $employee->matricule,
                        'error' => $e->getMessage()
                    ]);
                    
                    Payslip::where('employee_id', $employee->id)
                        ->where('month', $pay_month)
                            ->where('year', $this->year)
                        ->update([
                            'encryption_status' => Payslip::STATUS_FAILED,
                            'failure_reason' => 'Failed to prepare page for combination'
                        ]);
                }
                return;
            }

            // Combine unencrypted files
            $pdf = new Pdf([$existingFilePath, $newFilePath], ['command' => config('ciblerh.pdftk_path')]);
            $pdf->tempDir = config('ciblerh.temp_dir');
            
            Log::info('Attempting to combine PDF files', [
                'employee_id' => $employee->id,
                'matricule' => $employee->matricule,
                'existing_file' => $existingFile,
                'existing_path' => $existingFilePath,
                'new_file' => $newFile,
                'new_path' => $newFilePath,
                'output_path' => $tempCombinedFile,
                'pdftk_command' => config('ciblerh.pdftk_path'),
                'temp_dir' => config('ciblerh.temp_dir'),
                'existing_exists' => file_exists($existingFilePath),
                'new_exists' => file_exists($newFilePath),
            ]);
            
            // Combine the unencrypted PDFs
            $combinedResult = $pdf->saveAs($tempCombinedFile);
            
            // Log PDF command result
            Log::info('PDF combination result', [
                'employee_id' => $employee->id,
                'matricule' => $employee->matricule,
                'combined_result' => $combinedResult,
                'output_exists' => file_exists($tempCombinedFile),
                'pdf_error' => $pdf->getError() ?: 'No error',
            ]);
            
            if ($combinedResult && file_exists($tempCombinedFile)) {
                // Delete old temp file if different
                if ($existingFile !== $tempCombinedPath && Storage::disk('modified')->exists($existingFile)) {
                    Storage::disk('modified')->delete($existingFile);
                }
                
                // Use locking for safe concurrent updates
                $payslip = Payslip::where('employee_id', $employee->id)
                    ->where('month', $pay_month)
                    ->where('year', $this->year)
                    ->lockForUpdate()
                    ->first();
                
                if ($payslip) {
                    $payslip->update([
                        'file' => $tempCombinedPath,
                        'encryption_status' => Payslip::STATUS_PENDING,
                        'encryption_status_note' => 'Multi-page combination complete, pending encryption'
                    ]);
                }
                
                Log::info('Combined unencrypted multi-page PDF for employee', [
                    'employee_id' => $employee->id,
                    'matricule' => $employee->matricule,
                    'temp_file' => $tempCombinedPath
                ]);
            } else {
                // Combination failed
                Log::error('Failed to combine unencrypted PDF files', [
                    'employee_id' => $employee->id,
                    'matricule' => $employee->matricule,
                    'existing_file' => $existingFile,
                    'existing_path' => $existingFilePath,
                    'new_file' => $newFile,
                    'new_path' => $newFilePath,
                    'output_path' => $tempCombinedFile,
                    'combined_result' => $combinedResult,
                    'pdf_error' => $pdf->getError() ?: 'No PDF error available',
                    'pdftk_command' => config('ciblerh.pdftk_path'),
                    'temp_dir' => config('ciblerh.temp_dir'),
                    'existing_exists' => file_exists($existingFilePath),
                    'new_exists' => file_exists($newFilePath),
                    'output_exists' => file_exists($tempCombinedFile),
                ]);
                
                // Mark as failed in DB with locking
                $payslip = Payslip::where('employee_id', $employee->id)
                    ->where('month', $pay_month)
                    ->where('year', $this->year)
                    ->lockForUpdate()
                    ->first();
                
                if ($payslip) {
                    $payslip->update([
                        'encryption_status' => Payslip::STATUS_FAILED,
                        'failure_reason' => 'Failed to combine PDF pages'
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error combining unencrypted PDF files', [
                'employee_id' => $employee->id,
                'matricule' => $employee->matricule,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Mark as failed in DB with error details and locking
            try {
                $payslip = Payslip::where('employee_id', $employee->id)
                    ->where('month', $pay_month)
                    ->where('year', $this->year)
                    ->lockForUpdate()
                    ->first();
                
                if ($payslip) {
                    $payslip->update([
                        'encryption_status' => Payslip::STATUS_FAILED,
                        'failure_reason' => 'PDF combination error: ' . substr($e->getMessage(), 0, 100)
                    ]);
                }
            } catch (Exception $updateError) {
                Log::error('Failed to update payslip status after combination error', [
                    'employee_id' => $employee->id,
                    'original_error' => $e->getMessage(),
                    'update_error' => $updateError->getMessage()
                ]);
            }
        }
    }

}
