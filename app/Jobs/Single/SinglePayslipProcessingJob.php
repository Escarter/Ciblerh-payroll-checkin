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
use Exception;

class SinglePayslipProcessingJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $employee;
    protected $user_id;
    protected $destination;
    protected $chunk;
    protected $month;
    protected $sms_balance = null;
    protected $sms_balance_checked = false;

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

        // Check SMS balance once per job for optimization
        if (!$this->sms_balance_checked) {
            $setting = \App\Models\Setting::first();
            if (!empty($setting->sms_provider)) {
                $sms_client = match ($setting->sms_provider) {
                    'twilio' => new \App\Services\TwilioSMS($setting),
                    'nexah' => new \App\Services\Nexah($setting),
                    'aws_sns' => new \App\Services\AwsSnsSMS($setting),
                    default => new \App\Services\Nexah($setting)
                };

                try {
                    $this->sms_balance = $sms_client->getBalance();
                    $this->sms_balance_checked = true;
                } catch (\Exception $e) {
                    Log::warning('Failed to check SMS balance in single processing job: ' . $e->getMessage());
                }
            }
        }

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
                    if (\App\Models\User::matriculeTokenExistsInPdfText($pdf_text, $this->employee->matricule)) {
                        if (Storage::disk('splitted')->exists($file)) {
                            // CHANGED: Store unencrypted temp file, defer encryption until after all pages combined
                            $unique_suffix = md5($this->employee->id . '_' . time());
                            $temp_unenc_file = $this->destination . '/temp_unenc_' . $this->employee->matricule . '_' . $pay_month . '_' . $unique_suffix . '.pdf';
                            
                            // Use database locking to prevent race conditions
                            $record_exists = Payslip::where('employee_id', $this->employee->id)
                                ->where('month', $pay_month)
                                ->where('year', now()->year)
                                ->lockForUpdate()
                                ->first();

                            if (empty($record_exists) || empty($record_exists->file)) {
                                // First file - copy unencrypted temp file
                                try {
                                    $source_path = Storage::disk('splitted')->path($file);
                                    $dest_path = Storage::disk('modified')->path($temp_unenc_file);
                                    
                                    if (!copy($source_path, $dest_path)) {
                                        throw new Exception('Failed to copy PDF file');
                                    }

                                    if (file_exists($dest_path)) {
                                        if (empty($record_exists)) {
                                            $record = $this->createPayslipRecord($this->employee, $pay_month);
                                            $record->update([
                                                'file' => $temp_unenc_file,
                                                'encryption_status' => Payslip::STATUS_PENDING,
                                                'encryption_status_note' => 'Awaiting page combination and encryption'
                                            ]);
                                        } else {
                                            $record_exists->update([
                                                'file' => $temp_unenc_file,
                                                'encryption_status' => Payslip::STATUS_PENDING,
                                                'encryption_status_note' => 'Awaiting page combination and encryption'
                                            ]);
                                        }
                                    }
                                } catch (Exception $e) {
                                    Log::error('SinglePayslipProcessingJob: Failed to copy PDF', [
                                        'employee_id' => $this->employee->id,
                                        'error' => $e->getMessage()
                                    ]);
                                }
                            } else {
                                // Combine unencrypted pages
                                $temp_combined_file = $this->destination . '/temp_unenc_' . $this->employee->matricule . '_' . $pay_month . '_' . $unique_suffix . '.pdf';
                                $this->combinePdfFiles($this->employee, $file, $record_exists->file, $temp_combined_file, $pay_month);
                            }
                        }
                    }
                }
          
        }
        
        // After all pages processed, encrypt and send
        $this->finalizeAndSend($this->employee, $pay_month);
    }

    public function sendSlip($employee, $month, $destination)
    {
        $record_exists = Payslip::where('employee_id', $employee->id)
            ->where('month', $month)
            ->where('year', now()->year)
            ->first();

        // Check if employee has email notifications enabled BEFORE creating/updating record
        // Refresh employee to ensure we have the latest notification preferences
        $employee->refresh();
        if ($employee->receive_email_notifications === false) {
            // Update SMS status to skipped with clear message (SMS not attempted when email notifications disabled)
            $smsStatusNote = __('payslips.sms_not_attempted_email_disabled');
            
            if ($record_exists === null) {
                // Create record with correct statuses from the start
                $record = $this->createPayslipRecord($employee, $month);
                $record->update([
                    'file' => $destination,
                    'email_sent_status' => Payslip::STATUS_DISABLED,
                    'email_status_note' => __('payslips.email_notifications_disabled_for_this_employee'),
                    'sms_sent_status' => Payslip::STATUS_SKIPPED,
                    'sms_status_note' => $smsStatusNote
                ]);
            } else {
                if ($record_exists->successful()) {
                    return;
                }
                // Update existing record using direct database update to ensure persistence
                Payslip::where('id', $record_exists->id)->update([
                    'email_sent_status' => Payslip::STATUS_DISABLED,
                    'email_status_note' => __('payslips.email_notifications_disabled_for_this_employee'),
                    'sms_sent_status' => Payslip::STATUS_SKIPPED,
                    'sms_status_note' => $smsStatusNote
                ]);
                
                // Verify the update was successful
                $record_exists->refresh();
                if ($record_exists->sms_sent_status !== Payslip::STATUS_SKIPPED) {
                    Log::warning('SinglePayslipProcessingJob: SMS status update failed for email disabled - retrying with model update', [
                        'payslip_id' => $record_exists->id,
                        'employee_id' => $employee->id,
                        'expected_status' => Payslip::STATUS_SKIPPED,
                        'actual_status' => $record_exists->sms_sent_status
                    ]);
                    // Fallback: try model update
                    $record_exists->sms_sent_status = Payslip::STATUS_SKIPPED;
                    $record_exists->sms_status_note = $smsStatusNote;
                    $record_exists->email_sent_status = Payslip::STATUS_DISABLED;
                    $record_exists->email_status_note = __('payslips.email_notifications_disabled_for_this_employee');
                    $record_exists->save();
                }
            }
            return;
        }

        if ($record_exists === null) {
            $record = $this->createPayslipRecord($employee, $month);
        } else {
            if ($record_exists->successful()) {
                return;
            }
            $record = $record_exists;
        }

        // Check if email has bounced previously
        if ($employee->email_bounced) {
            // Update SMS status to skipped with clear message (SMS not attempted when email bounces)
            $smsStatusNote = __('payslips.sms_not_attempted_email_failed');
            
            $record->update([
                'email_sent_status' => Payslip::STATUS_FAILED,
                'sms_sent_status' => Payslip::STATUS_SKIPPED,
                'sms_status_note' => $smsStatusNote,
                'email_bounced' => true,
                'email_bounced_at' => now(),
                'email_bounce_reason' => __('payslips.email_previously_bounced') . ': ' . ($employee->email_bounce_reason ?? 'Unknown'),
                'failure_reason' => __('payslips.email_address_has_bounced_previously')
            ]);
            return;
        }

        // Use alternative email if primary email is empty
        $emailToUse = !empty($employee->email) ? $employee->email : $employee->alternative_email;
        
        if (empty($emailToUse)) {
            // Update SMS status to skipped with clear message (SMS not attempted when no email)
            $smsStatusNote = __('payslips.sms_not_attempted_email_failed');
            
            $record->update([
                'email_sent_status' => Payslip::STATUS_FAILED,
                'sms_sent_status' => Payslip::STATUS_SKIPPED,
                'sms_status_note' => $smsStatusNote,
                'failure_reason' => __('payslips.no_valid_email_address')
            ]);
            return;
        }

        try {
            Mail::to(cleanString($emailToUse))->send(new SendPayslip($employee, $destination, $month, $record->year));

            // Email accepted by mail server - delivery will be confirmed via webhooks
            $record->update([
                'email_sent_status' => Payslip::STATUS_SUCCESSFUL,
                'email_delivery_status' => Payslip::DELIVERY_STATUS_SENT,
                'email_sent_at' => now(),
            ]);
            sendSmsAndUpdateRecord($employee, $month, $record, $this->sms_balance);
        } catch (\Exception $e) {
            // Update SMS status to skipped with clear message (SMS not attempted when email fails)
            $smsStatusNote = __('payslips.sms_not_attempted_email_failed');
            
            $record->update([
                'email_sent_status' => Payslip::STATUS_FAILED,
                'sms_sent_status' => Payslip::STATUS_SKIPPED,
                'sms_status_note' => $smsStatusNote,
                'failure_reason' => __('payslips.email_error') . ': ' . $e->getMessage()
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
     * Combine multiple UNENCRYPTED PDF files for an employee (multi-page payslip)
     * Note: Files are combined unencrypted, encryption happens in finalizeAndSend()
     * This fixes the bug where encrypted page 1 cannot be combined with unencrypted page 2
     */
    private function combinePdfFiles($employee, $newFile, $existingFile, $tempCombinedPath, $pay_month)
    {
        try {
            $existingFilePath = Storage::disk('modified')->path($existingFile);
            $newFilePath = Storage::disk('splitted')->path($newFile);
            $tempCombinedFile = Storage::disk('modified')->path($tempCombinedPath);
            
            // Check if existing file exists
            if (!Storage::disk('modified')->exists($existingFile)) {
                // Existing file doesn't exist, just copy the new one as temp
                try {
                    if (!copy($newFilePath, $tempCombinedFile)) {
                        throw new \Exception('Failed to copy new page file');
                    }
                    
                    // Use locking for safe concurrent updates
                    $payslip = Payslip::where('employee_id', $employee->id)
                        ->where('month', $pay_month)
                        ->where('year', now()->year)
                        ->lockForUpdate()
                        ->first();
                    
                    if ($payslip) {
                        $payslip->update([
                            'file' => $tempCombinedPath,
                            'encryption_status' => Payslip::STATUS_PENDING,
                            'encryption_status_note' => 'Awaiting encryption after page combination'
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('SinglePayslipProcessingJob: Failed to copy page file for combination', [
                        'employee_id' => $employee->id,
                        'matricule' => $employee->matricule,
                        'error' => $e->getMessage()
                    ]);
                    
                    $payslip = Payslip::where('employee_id', $employee->id)
                        ->where('month', $pay_month)
                        ->where('year', now()->year)
                        ->lockForUpdate()
                        ->first();
                    
                    if ($payslip) {
                        $payslip->update([
                            'encryption_status' => Payslip::STATUS_FAILED,
                            'failure_reason' => 'Failed to prepare page for combination'
                        ]);
                    }
                }
                return;
            }

            // Combine unencrypted files
            $pdf = new Pdf([$existingFilePath, $newFilePath], ['command' => config('ciblerh.pdftk_path')]);
            $pdf->tempDir = config('ciblerh.temp_dir');
            
            $combinedResult = $pdf->saveAs($tempCombinedFile);
            
            if ($combinedResult && file_exists($tempCombinedFile)) {
                // Delete old temp file if different
                if ($existingFile !== $tempCombinedPath && Storage::disk('modified')->exists($existingFile)) {
                    Storage::disk('modified')->delete($existingFile);
                }
                
                // Use locking for safe concurrent updates
                $payslip = Payslip::where('employee_id', $employee->id)
                    ->where('month', $pay_month)
                    ->where('year', now()->year)
                    ->lockForUpdate()
                    ->first();
                
                if ($payslip) {
                    $payslip->update([
                        'file' => $tempCombinedPath,
                        'encryption_status' => Payslip::STATUS_PENDING,
                        'encryption_status_note' => 'Multi-page combination complete, pending encryption'
                    ]);
                }
                
                Log::info('SinglePayslipProcessingJob: Combined unencrypted multi-page PDF', [
                    'employee_id' => $employee->id,
                    'matricule' => $employee->matricule,
                    'temp_file' => $tempCombinedPath
                ]);
            } else {
                Log::error('SinglePayslipProcessingJob: Failed to combine unencrypted PDF files', [
                    'employee_id' => $employee->id,
                    'matricule' => $employee->matricule,
                    'existing_file' => $existingFile,
                    'new_file' => $newFile
                ]);
                
                $payslip = Payslip::where('employee_id', $employee->id)
                    ->where('month', $pay_month)
                    ->where('year', now()->year)
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
            Log::error('SinglePayslipProcessingJob: Error combining unencrypted PDF files', [
                'employee_id' => $employee->id,
                'matricule' => $employee->matricule,
                'error' => $e->getMessage()
            ]);
            
            try {
                $payslip = Payslip::where('employee_id', $employee->id)
                    ->where('month', $pay_month)
                    ->where('year', now()->year)
                    ->lockForUpdate()
                    ->first();
                
                if ($payslip) {
                    $payslip->update([
                        'encryption_status' => Payslip::STATUS_FAILED,
                        'failure_reason' => 'PDF combination error: ' . substr($e->getMessage(), 0, 100)
                    ]);
                }
            } catch (Exception $updateError) {
                Log::error('SinglePayslipProcessingJob: Failed to update payslip status after combination error', [
                    'employee_id' => $employee->id,
                    'original_error' => $e->getMessage(),
                    'update_error' => $updateError->getMessage()
                ]);
            }
        }
    }
    
    /**
     * Finalize single payslip: encrypt combined file and send email
     * Called after all pages have been processed and combined
     */
    private function finalizeAndSend($employee, $pay_month)
    {
        try {
            $payslip = Payslip::where('employee_id', $employee->id)
                ->where('month', $pay_month)
                ->where('year', now()->year)
                ->first();
            
            if (!$payslip) {
                return; // No payslip record found
            }
            
            // Skip if already processed successfully
            if ($payslip->encryption_status === Payslip::STATUS_SUCCESSFUL) {
                Log::info('SinglePayslipProcessingJob: Payslip already finalized', [
                    'payslip_id' => $payslip->id,
                    'employee_id' => $employee->id
                ]);
                return;
            }
            
            // Skip if marked as failed
            if ($payslip->encryption_status === Payslip::STATUS_FAILED) {
                Log::warning('SinglePayslipProcessingJob: Skipping failed payslip finalization', [
                    'payslip_id' => $payslip->id,
                    'employee_id' => $employee->id,
                    'reason' => $payslip->failure_reason
                ]);
                return;
            }
            
            // Verify temp file exists
            if (!Storage::disk('modified')->exists($payslip->file)) {
                $payslip->update([
                    'encryption_status' => Payslip::STATUS_FAILED,
                    'failure_reason' => 'Unencrypted temp file not found during finalization'
                ]);
                return;
            }
            
            // Encrypt the combined unencrypted file
            $unencryptedPath = Storage::disk('modified')->path($payslip->file);
            $finalFile = $this->destination . '/' . $employee->matricule . '_' . $pay_month . '.pdf';
            $finalPath = Storage::disk('modified')->path($finalFile);
            
            $pdf = new Pdf($unencryptedPath, ['command' => config('ciblerh.pdftk_path')]);
            $pdf->tempDir = config('ciblerh.temp_dir');
            
            $encryptResult = $pdf->setUserPassword($employee->pdf_password)
                ->passwordEncryption(128)
                ->saveAs($finalPath);
            
            if (!$encryptResult || !Storage::disk('modified')->exists($finalFile)) {
                throw new \Exception('Encryption failed or output file not created');
            }
            
            // Verify file has content
            $fileSize = Storage::disk('modified')->size($finalFile);
            if ($fileSize <= 0) {
                Storage::disk('modified')->delete($finalFile);
                throw new \Exception('Encrypted file is empty (0 bytes)');
            }
            
            // Delete temp unencrypted file
            if (Storage::disk('modified')->exists($payslip->file)) {
                Storage::disk('modified')->delete($payslip->file);
            }
            
            // Update payslip - mark as encrypted
            $payslip->update([
                'file' => $finalFile,
                'encryption_status' => Payslip::STATUS_SUCCESSFUL,
                'encryption_status_note' => null
            ]);
            
            Log::info('SinglePayslipProcessingJob: Successfully encrypted and finalized payslip', [
                'payslip_id' => $payslip->id,
                'employee_id' => $employee->id,
                'final_file' => $finalFile,
                'file_size_bytes' => $fileSize
            ]);
            
            // NOW send the email with encrypted file
            $this->sendSlip($employee, $pay_month, $finalFile);
            
        } catch (\Exception $e) {
            Log::error('SinglePayslipProcessingJob: Error during finalization', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage()
            ]);
            
            // Mark payslip as failed
            try {
                Payslip::where('employee_id', $employee->id)
                    ->where('month', $pay_month)
                    ->where('year', now()->year)
                    ->update([
                        'encryption_status' => Payslip::STATUS_FAILED,
                        'failure_reason' => 'Finalization error: ' . substr($e->getMessage(), 0, 100)
                    ]);
            } catch (Exception $updateError) {
                Log::error('SinglePayslipProcessingJob: Failed to mark payslip as failed', [
                    'employee_id' => $employee->id,
                    'error' => $updateError->getMessage()
                ]);
            }
        }
    }
}
