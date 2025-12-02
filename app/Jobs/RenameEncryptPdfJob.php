<?php

namespace App\Jobs;

use Exception;
use App\Models\Group;
use App\Models\Payslip;
use App\Mail\SendPayslip;
use mikehaertl\pdftk\Pdf;
use App\Models\Department;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use App\Models\SendPayslipProcess;
use Escarter\PopplerPhp\PdfToText;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Collection;

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
    protected $department;
    protected $destination;
    protected $chunk;
    protected $month;
    protected $process_id;
    protected $user_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Collection $chunk, $process_id)
    {
        $this->process = SendPayslipProcess::findOrFail($process_id);
        $this->department = Department::findOrFail($this->process->department_id);
        $this->destination = $this->process->destination_directory;
        $this->month = $this->process->month;
        $this->chunk = $chunk;
        $this->user_id = $this->process->user_id;
        $this->process_id = $process_id;
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

        Storage::disk('modified')->makeDirectory($this->destination);

        foreach ($this->chunk as $file) {

            $from_path = Storage::disk('splitted')->path($file);
            // $pdf_text = PdfToText::getText($from_path, '/usr/local/bin/pdftotext');
            $pdf_text = PdfToText::getText($from_path, config('ciblerh.pdftotext_path'));
            // dd(strpos(PdfToText::getText($from_path, '/usr/local/bin/pdftotext'), 'Matricule 135121') !== FALSE);


            collect($this->department->employees)->each(function ($employee) use ($pdf_text, $file, $pay_month) {

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
                        'year' => now()->year,
                    ]);
                    $created_record->update([
                        'encryption_status' => Payslip::STATUS_FAILED,
                        'email_sent_status' => Payslip::STATUS_FAILED,
                        'sms_sent_status' => Payslip::STATUS_FAILED,
                        'failure_reason' => __('User Matricule is empty')
                    ]);
                } else {

                    preg_match("/\b" . $employee->matricule . "\b/i", $pdf_text, $matches);

                    if (!empty($matches) && $matches[0] === $employee->matricule) {

                        if (Storage::disk('splitted')->exists($file)) {
                            // Check if employee already has a payslip record (might have multiple pages)
                            $record_exists = Payslip::where('employee_id', $employee->id)
                                ->where('month', $pay_month)
                                ->where('year', now()->year)
                                ->first();

                            $destination_file = $this->destination . '/' . $employee->matricule . '_' . $pay_month . '.pdf';

                            if (empty($record_exists) || empty($record_exists->file)) {
                                // First file for this employee - encrypt directly
                                $pdf = new Pdf(Storage::disk('splitted')->path($file), ['command' => config('ciblerh.pdftk_path')]);
                                $pdf->tempDir = config('ciblerh.temp_dir');
                                $result = $pdf->setUserPassword($employee->pdf_password)
                                    ->passwordEncryption(128)
                                    ->saveAs(Storage::disk('modified')->path($destination_file));

                                if (Storage::disk('modified')->exists($destination_file)) {
                                    if (empty($record_exists)) {
                                        createPayslipRecord($employee, $pay_month, $this->process_id, $this->user_id, $destination_file);
                                    } else {
                                        $record_exists->update([
                                            'file' => $destination_file,
                                            'encryption_status' => Payslip::STATUS_SUCCESSFUL,
                                        ]);
                                    }
                                }
                            } else {
                                // Employee already has a file - combine with existing one
                                $this->combinePdfFiles($employee, $file, $record_exists->file, $destination_file, $pay_month);
                            }
                        }
                    }
                }
            });
        }
    }

    /**
     * Combine multiple PDF files for an employee (multi-page payslip)
     * Note: This handles the case where an employee's payslip spans multiple pages
     */
    private function combinePdfFiles($employee, $newFile, $existingFile, $destinationFile, $pay_month)
    {
        try {
            $existingFilePath = Storage::disk('modified')->path($existingFile);
            $newFilePath = Storage::disk('splitted')->path($newFile);
            
            // Check if existing file exists
            if (!Storage::disk('modified')->exists($existingFile)) {
                // Existing file doesn't exist, just encrypt the new one
                $pdf = new Pdf($newFilePath, ['command' => config('ciblerh.pdftk_path')]);
                $pdf->tempDir = config('ciblerh.temp_dir');
                $result = $pdf->setUserPassword($employee->pdf_password)
                    ->passwordEncryption(128)
                    ->saveAs(Storage::disk('modified')->path($destinationFile));
                
                if (Storage::disk('modified')->exists($destinationFile)) {
                    Payslip::where('employee_id', $employee->id)
                        ->where('month', $pay_month)
                        ->where('year', now()->year)
                        ->update([
                            'file' => $destinationFile,
                            'encryption_status' => Payslip::STATUS_SUCCESSFUL,
                        ]);
                }
                return;
            }

            // Create temporary combined file path
            $tempCombinedPath = $this->destination . '/temp_' . $employee->matricule . '_' . $pay_month . '_' . time() . '.pdf';
            $tempCombinedFile = Storage::disk('modified')->path($tempCombinedPath);
            
            // Use pdftk to combine PDFs
            // Note: If existing file is encrypted, pdftk will need the password
            // For now, we'll combine unencrypted files, then encrypt the result
            $pdf = new Pdf([$existingFilePath, $newFilePath], ['command' => config('ciblerh.pdftk_path')]);
            $pdf->tempDir = config('ciblerh.temp_dir');
            
            // Combine the PDFs (this works if files are unencrypted or we provide passwords)
            $combinedResult = $pdf->saveAs($tempCombinedFile);
            
            if ($combinedResult && file_exists($tempCombinedFile)) {
                // Now encrypt the combined file
                $combinedPdf = new Pdf($tempCombinedFile, ['command' => config('ciblerh.pdftk_path')]);
                $combinedPdf->tempDir = config('ciblerh.temp_dir');
                $encryptedResult = $combinedPdf->setUserPassword($employee->pdf_password)
                    ->passwordEncryption(128)
                    ->saveAs(Storage::disk('modified')->path($destinationFile));
                
                // Clean up temp file
                if (file_exists($tempCombinedFile)) {
                    @unlink($tempCombinedFile);
                }
                
                // Delete old file if different
                if ($existingFile !== $destinationFile && Storage::disk('modified')->exists($existingFile)) {
                    Storage::disk('modified')->delete($existingFile);
                }
                
                if ($encryptedResult && Storage::disk('modified')->exists($destinationFile)) {
                    Payslip::where('employee_id', $employee->id)
                        ->where('month', $pay_month)
                        ->where('year', now()->year)
                        ->update([
                            'file' => $destinationFile,
                            'encryption_status' => Payslip::STATUS_SUCCESSFUL,
                        ]);
                    
                    Log::info('Combined multi-page PDF for employee', [
                        'employee_id' => $employee->id,
                        'matricule' => $employee->matricule,
                        'files_combined' => 2
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error combining PDF files', [
                'employee_id' => $employee->id,
                'matricule' => $employee->matricule,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

}
