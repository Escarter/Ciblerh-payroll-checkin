<?php

namespace App\Jobs\Single;

use App\Models\Payslip;
use App\Models\User;
use App\Mail\SendPayslip;
use mikehaertl\pdftk\Pdf;
use Illuminate\Support\Str;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Exception;

class SendSinglePayslipJob implements ShouldQueue
{
    use  Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $raw_file_path;
    protected $employee;
    protected $record;
    protected $destination;
    protected $sms_balance = null;
    protected $sms_balance_checked = false;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($raw_file_path,User $employee,Payslip $record, $destination)
    {
        $this->employee = $employee;
        $this->queue = 'emails';
        $this->raw_file_path = $raw_file_path;
        $this->record = $record;
        $this->destination = $destination;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
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
                    Log::warning('Failed to check SMS balance in single send job: ' . $e->getMessage());
                }
            }
        }

        $pdf = new Pdf(Storage::disk('raw')->path($this->raw_file_path));
        $result = $pdf->setUserPassword($this->employee->pdf_password)
            ->passwordEncryption(128)
            ->saveAs(Storage::disk('modified')->path($this->destination . '/' . $this->employee->matricule . '_' . $this->record->month . '.pdf'));

        $destination_file = $this->destination . '/' . $this->employee->matricule . '_' . $this->record->month . '.pdf';

        // Check if employee has email notifications enabled
        // Refresh employee to ensure we have the latest notification preferences
        $this->employee->refresh();
        if ($this->employee->receive_email_notifications === false) {
            // Update SMS status to skipped with clear message (SMS not attempted when email notifications disabled)
            $smsStatusNote = __('payslips.sms_not_attempted_email_disabled');
            
            // Use direct database update to ensure persistence
            Payslip::where('id', $this->record->id)->update([
                'email_sent_status' => Payslip::STATUS_DISABLED,
                'email_status_note' => __('payslips.email_notifications_disabled_for_this_employee'),
                'sms_sent_status' => Payslip::STATUS_SKIPPED,
                'sms_status_note' => $smsStatusNote
            ]);
            
            // Verify the update was successful
            $this->record->refresh();
            if ($this->record->sms_sent_status !== Payslip::STATUS_SKIPPED) {
                Log::warning('SendSinglePayslipJob: SMS status update failed for email disabled - retrying with model update', [
                    'payslip_id' => $this->record->id,
                    'employee_id' => $this->employee->id,
                    'expected_status' => Payslip::STATUS_SKIPPED,
                    'actual_status' => $this->record->sms_sent_status
                ]);
                // Fallback: try model update
                $this->record->sms_sent_status = Payslip::STATUS_SKIPPED;
                $this->record->sms_status_note = $smsStatusNote;
                $this->record->email_sent_status = Payslip::STATUS_DISABLED;
                $this->record->email_status_note = __('payslips.email_notifications_disabled_for_this_employee');
                $this->record->save();
            }
            return;
        }

        // Check if email has bounced previously
        if ($this->employee->email_bounced) {
            // Update SMS status to skipped with clear message (SMS not attempted when email bounces)
            $smsStatusNote = __('payslips.sms_not_attempted_email_failed');
            
            $this->record->update([
                'email_sent_status' => Payslip::STATUS_FAILED,
                'sms_sent_status' => Payslip::STATUS_SKIPPED,
                'sms_status_note' => $smsStatusNote,
                'email_bounced' => true,
                'email_bounced_at' => now(),
                'email_bounce_reason' => __('payslips.email_previously_bounced') . ': ' . ($this->employee->email_bounce_reason ?? 'Unknown'),
                'failure_reason' => __('payslips.email_address_has_bounced_previously')
            ]);
            return;
        }

        // Use alternative email if primary email is empty
        $emailToUse = !empty($this->employee->email) ? $this->employee->email : $this->employee->alternative_email;
        
        if (empty($emailToUse)) {
            // Update SMS status to skipped with clear message (SMS not attempted when no email)
            $smsStatusNote = __('payslips.sms_not_attempted_email_failed');
            
            $this->record->update([
                'email_sent_status' => Payslip::STATUS_FAILED,
                'sms_sent_status' => Payslip::STATUS_SKIPPED,
                'sms_status_note' => $smsStatusNote,
                'failure_reason' => __('payslips.no_valid_email_address')
            ]);
            return;
        }

        try {
            setSavedSmtpCredentials();
            
            Mail::to(cleanString($emailToUse))->send(new SendPayslip($this->employee, $destination_file, $this->record->month));

            // Email accepted by mail server - delivery will be confirmed via webhooks
            $this->record->update([
                'email_sent_status' => Payslip::STATUS_SUCCESSFUL,
                'email_delivery_status' => Payslip::DELIVERY_STATUS_SENT,
                'email_sent_at' => now(),
            ]);
            sendSmsAndUpdateRecord($this->employee, $this->record->month, $this->record, $this->sms_balance);
        } catch (\Exception $e) {
            // Update SMS status to skipped with clear message (SMS not attempted when email fails)
            $smsStatusNote = __('payslips.sms_not_attempted_email_failed');
            
            $this->record->update([
                'email_sent_status' => Payslip::STATUS_FAILED,
                'sms_sent_status' => Payslip::STATUS_SKIPPED,
                'sms_status_note' => $smsStatusNote,
                'failure_reason' => __('payslips.email_error') . ': ' . $e->getMessage()
            ]);
        }
    }
}
