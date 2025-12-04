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
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class SendSinglePayslipJob implements ShouldQueue
{
    use  Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $raw_file_path;
    protected $employee;
    protected $record;
    protected $destination;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($raw_file_path,User $employee,Payslip $record, $destination)
    {
        $this->employee = $employee;
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

        $pdf = new Pdf(Storage::disk('raw')->path($this->raw_file_path));
        $result = $pdf->setUserPassword($this->employee->pdf_password)
            ->passwordEncryption(128)
            ->saveAs(Storage::disk('modified')->path($this->destination . '/' . $this->employee->matricule . '_' . $this->record->month . '.pdf'));

        $destination_file = $this->destination . '/' . $this->employee->matricule . '_' . $this->record->month . '.pdf';

        if (!empty($this->employee->email)) {

            Mail::to(cleanString($this->employee->email))->send(new SendPayslip($this->employee, $destination_file, $this->record->month));

            if (Mail::failures()) {
                $this->record->update([
                    'email_sent_status' => 'failed',
                    'sms_sent_status' => 'failed',
                    'failure_reason' => __('{{__('payslips.failed_sending_email_sms')}}')
                ]);
            } else {
                $this->record->update(['email_sent_status' => 'successful']);
                sendSmsAndUpdateRecord($this->employee, $this->record->month, $this->record);
            }
        } else {
            $this->record->update([
                'email_sent_status' => 'failed',
                'sms_sent_status' => 'failed',
                'failure_reason' => __('{{__('payslips.no_valid_email_address')}}')
            ]);
        }
    }
}
