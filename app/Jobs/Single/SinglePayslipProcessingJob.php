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
            Mail::to(cleanString($emailToUse))->send(new SendPayslip($employee, $destination, $month));

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
