<?php

namespace App\Jobs\Single;

use App\Models\Payslip;
use App\Models\User;
use App\Mail\SendPayslip;
use mikehaertl\pdftk\Pdf;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Escarter\PopplerPhp\PdfToText;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class SinglePayslipProcessingJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $employee;
    protected $user_id;
    protected $destination;
    protected $chunk;
    protected $month;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $chunk, $employee_id, $month, $destination, $user_id)
    {
        $this->employee = User::findOrFail($employee_id);
        $this->destination = $destination;
        $this->month = $month;
        $this->chunk = $chunk;
        $this->user_id = $user_id;
        $this->queue = 'pdf-processing';

    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $pay_month = $this->month;

        Storage::disk('modified')->makeDirectory($this->destination);

        foreach ($this->chunk as $file) {

            $from_path = Storage::disk('splitted')->path($file);
            // $pdf_text = PdfToText::getText($from_path, '/usr/local/bin/pdftotext');
            $pdf_text = PdfToText::getText($from_path, config('ciblerh.pdftotext_path'));
            // dd(strpos(PdfToText::getText($from_path, '/usr/local/bin/pdftotext'), 'Matricule 135121') !== FALSE);

                if (empty($this->employee->matricule)) {
                    $created_record = $this->createPayslipRecord($this->employee, $pay_month);
                    $created_record->update([
                        'email_sent_status' => 'failed',
                        'sms_sent_status' => 'failed',
                        'failure_reason' => __('payslips.user_matricule_empty')
                    ]);
                } else {
                    if (strpos($pdf_text, 'Matricule ' . $this->employee->matricule) !== FALSE) {
                        $destination_file = $this->destination . '/' . $this->employee->matricule . '_' . $pay_month . '.pdf';
                        if (Storage::disk('splitted')->exists($file)) {
                            // Check if employee already has a payslip record (might have multiple pages)
                            $record_exists = Payslip::where('employee_id', $this->employee->id)
                                ->where('month', $pay_month)
                                ->where('year', now()->year)
                                ->first();

                            if (empty($record_exists) || empty($record_exists->file)) {
                                // First file for this employee - encrypt directly
                            $pdf = new Pdf(Storage::disk('splitted')->path($file), ['command' => config('ciblerh.pdftk_path')]);
                            $result = $pdf->setUserPassword($this->employee->pdf_password)
                                ->passwordEncryption(128)
                                ->saveAs(Storage::disk('modified')->path($destination_file));

                            if (Storage::disk('modified')->exists($destination_file)) {
                                $this->sendSlip($this->employee, $pay_month, $destination_file);
                                }
                            } else {
                                // Employee already has a file - combine with existing one
                                $this->combinePdfFiles($this->employee, $file, $record_exists->file, $destination_file, $pay_month);
                            }
                        }
                    }
                }
          
        }
    }

    public function sendSlip($employee, $month, $destination)
    {
        $record_exists = Payslip::where('employee_id', $employee->id)
            ->where('month', $month)
            ->where('year', now()->year)
            ->first();

        if ($record_exists === null) {
            $record = $this->createPayslipRecord($employee, $month);
        } else {
            if ($record_exists->successful()) {
                return;
            }
            $record = $record_exists;
        }

        if (!empty($employee->email)) {

            Mail::to(cleanString($employee->email))->send(new SendPayslip($employee, $destination, $month));

            if (Mail::failures()) {
                $record->update([
                    'email_sent_status' => 'failed',
                    'sms_sent_status' => 'failed',
                    'failure_reason' => __('payslips.failed_sending_email_sms')
                ]);
            } else {
                $record->update(['email_sent_status' => 'successful']);
                sendSmsAndUpdateRecord($employee, $month, $record);
            }
        } else {
            $record->update([
                'email_sent_status' => 'failed',
                'sms_sent_status' => 'failed',
                'failure_reason' => __('payslips.no_valid_email_address')
            ]);
        }
    }
    public function createPayslipRecord($employee, $month)
    {
        return
            Payslip::create([
                'user_id' => $this->user_id,
                'employee_id' => $employee->id,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'email' => $employee->email,
                'phone' => !is_null($employee->professional_phone_number) ? $employee->professional_phone_number : $employee->personal_phone_number,
                'matricule' => $employee->matricule,
                'month' => $this->month,
                'year' => now()->year,
                'file' => $this->destination,
            ]);
    }

    /**
     * Combine multiple PDF files for an employee (multi-page payslip)
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
                $result = $pdf->setUserPassword($employee->pdf_password)
                    ->passwordEncryption(128)
                    ->saveAs(Storage::disk('modified')->path($destinationFile));
                
                if (Storage::disk('modified')->exists($destinationFile)) {
                    $this->sendSlip($employee, $pay_month, $destinationFile);
                }
                return;
            }

            // Create temporary combined file path
            $tempCombinedPath = $this->destination . '/temp_' . $employee->matricule . '_' . $pay_month . '_' . time() . '.pdf';
            $tempCombinedFile = Storage::disk('modified')->path($tempCombinedPath);
            
            // Use pdftk to combine PDFs
            $pdf = new Pdf([$existingFilePath, $newFilePath], ['command' => config('ciblerh.pdftk_path')]);
            
            // Combine the PDFs
            $combinedResult = $pdf->saveAs($tempCombinedFile);
            
            if ($combinedResult && file_exists($tempCombinedFile)) {
                // Now encrypt the combined file
                $combinedPdf = new Pdf($tempCombinedFile, ['command' => config('ciblerh.pdftk_path')]);
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
                    $this->sendSlip($employee, $pay_month, $destinationFile);
                    
                    Log::info('Combined multi-page PDF for single employee', [
                        'employee_id' => $employee->id,
                        'matricule' => $employee->matricule,
                        'files_combined' => 2
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error combining PDF files for single employee', [
                'employee_id' => $employee->id,
                'matricule' => $employee->matricule,
                'error' => $e->getMessage()
            ]);
        }
    }
}
