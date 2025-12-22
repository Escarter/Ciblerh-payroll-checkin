<?php

namespace App\Jobs;

use App\Models\Payslip;
use App\Models\Employee;
use App\Mail\SendPayslip;
use mikehaertl\pdftk\Pdf;
use Illuminate\Support\Str;
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

class sendSinglePayslipJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $raw_file_path;
    protected $employee;
    protected $record;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($raw_file_path,Employee $employee,Payslip $record)
    {
        $this->employee = $employee;
        $this->raw_file_path = $raw_file_path;
        $this->record = $record;
        $this->queue = 'emails';
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $destination = Str::random(10);

        Storage::disk('modified')->makeDirectory($destination);

        $pdf = new Pdf(Storage::disk('raw')->path($this->raw_file_path));
        $result = $pdf->setUserPassword($this->employee->pdf_password)
            ->passwordEncryption(128)
            ->saveAs(Storage::disk('modified')->path($destination . '/' . $this->employee->matricule . '_' . $this->record->month . '.pdf'));

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
                Log::warning('sendSinglePayslipJob: SMS status update failed for email disabled - retrying with model update', [
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
            
            Mail::to($emailToUse)->send(new SendPayslip($this->employee, $destination, $this->record->month))->onQueue('emails');

            // Email accepted by mail server - delivery will be confirmed via webhooks
            $this->record->update([
                'email_sent_status' => Payslip::STATUS_SUCCESSFUL,
                'email_delivery_status' => Payslip::DELIVERY_STATUS_SENT,
                'email_sent_at' => now(),
            ]);
            sendSmsAndUpdateRecord($this->employee, $this->record->month, $this->record);
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
