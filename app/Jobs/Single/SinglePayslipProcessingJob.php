<?php

namespace App\Jobs\Single;

use App\Models\Payslip;
use App\Models\Employee;
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
        $this->employee = Employee::findOrFail($employee_id);
        $this->destination = $destination;
        $this->month = $month;
        $this->chunk = $chunk;
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
                        'failure_reason' => __('User Matricule is empty')
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
                                $this->sendSlip($this->employee, $pay_month, $destination_file);
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
                    'failure_reason' => __('Failed sending Email & SMS')
                ]);
            } else {
                $record->update(['email_sent_status' => 'successful']);
                sendSmsAndUpdateRecord($employee, $month, $record);
            }
        } else {
            $record->update([
                'email_sent_status' => 'failed',
                'sms_sent_status' => 'failed',
                'failure_reason' => __('No valid email address for User')
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
}
