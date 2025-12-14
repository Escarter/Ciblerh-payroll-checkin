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
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

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

        if (!is_null($this->employee->email)) {

            setSavedSmtpCredentials();
            
            Mail::to($this->employee->email)->send(new SendPayslip($this->employee, $destination, $this->record->month))->onQueue('emails');

            // Email accepted by mail server - delivery will be confirmed via webhooks
            $this->record->update([
                'email_sent_status' => Payslip::STATUS_SUCCESSFUL,
                'email_delivery_status' => Payslip::DELIVERY_STATUS_SENT,
                'email_sent_at' => now(),
            ]);
            sendSmsAndUpdateRecord($this->employee, $this->record->month, $this->record);
        } else {
            $this->record->update([
                'email_sent_status' => 'failed',
                'sms_sent_status' => 'failed',
                'failure_reason' => __('payslips.no_valid_email_address')
            ]);
        }
    }
}
