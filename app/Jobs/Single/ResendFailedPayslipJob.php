<?php

namespace App\Jobs\Single;

use App\Models\User;
use App\Mail\SendPayslip;
use mikehaertl\pdftk\Pdf;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Escarter\PopplerPhp\PdfToText;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;

class ResendFailedPayslipJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $employee;
    protected $user_id;
    protected $destination;
    protected $chunk;
    protected $month;
    protected $record;
    protected $sms_balance = null;
    protected $sms_balance_checked = false;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $chunk, $employee_id, $record, $month, $destination, $user_id)
    {
        $this->employee = User::findOrFail($employee_id);
        $this->destination = $destination;
        $this->month = $month;
        $this->chunk = $chunk;
        $this->record = $record;
        $this->user_id = $user_id;
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
                    Log::warning('Failed to check SMS balance in resend failed job: ' . $e->getMessage());
                }
            }
        }

        foreach ($this->chunk as $file) {

            $from_path = Storage::disk('splitted')->path($file);
            // $pdf_text = PdfToText::getText($from_path, '/usr/local/bin/pdftotext');
            $pdf_text = PdfToText::getText($from_path, config('ciblerh.pdftotext_path'));
            // dd(strpos(PdfToText::getText($from_path, '/usr/local/bin/pdftotext'), 'Matricule 135121') !== FALSE);

            if (empty($this->employee->matricule)) {
                $this->record->update([
                    'email_sent_status' => 'failed',
                    'sms_sent_status' => 'failed',
                    'failure_reason' => __('payslips.user_matricule_empty')
                ]);
            } else {
                if (strpos($pdf_text, 'Matricule ' . $this->employee->matricule) !== FALSE) {
                    $destination_file = $this->destination . '/' . $this->employee->matricule . '_' . $pay_month . '.pdf';
                    if (Storage::disk('splitted')->exists($file)) {
                        //  Storage::disk('modified')->put($employee['matricule'].'.pdf', Storage::disk('splitted')->get($file));
                        $pdf = new Pdf(Storage::disk('splitted')->path($file), ['command' => config('ciblerh.pdftk_path')]);
                        // $pdf->tempDir = config('ciblerh.temp_dir');
                        $result = $pdf->setUserPassword($this->employee->pdf_password)
                            ->passwordEncryption(128)
                            ->saveAs(Storage::disk('modified')->path($destination_file));

                        if (Storage::disk('modified')->exists($destination_file)) {
                            $this->sendSlip($this->employee, $this->record, $pay_month, $destination_file);
                        }
                    }
                }
            }
        }
    }

    public function sendSlip($employee, $record, $month, $destination)
    {

        // if ($record->successful()) {
        //     return;
        // }

        if (!empty($employee->email)) {

            Mail::to(cleanString($employee->email))->send(new SendPayslip($employee, $destination, $month));

            // Email accepted by mail server - delivery will be confirmed via webhooks
            $record->update([
                'email_sent_status' => Payslip::STATUS_SUCCESSFUL,
                'email_delivery_status' => Payslip::DELIVERY_STATUS_SENT,
                'email_sent_at' => now(),
                'file' => Storage::disk('modified')->exists($destination)
            ]);
            sendSmsAndUpdateRecord($employee, $month, $record, $this->sms_balance);
        } else {
            $record->update([
                'email_sent_status' => 'failed',
                'sms_sent_status' => 'failed',
                'failure_reason' => __('payslips.no_valid_email_address')
            ]);
        }
    }
}
