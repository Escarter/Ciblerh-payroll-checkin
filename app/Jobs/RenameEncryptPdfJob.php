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
                    $created_record = $this->createPayslipRecord($employee, $pay_month, $this->process_id, $this->user_id);
                    $created_record->update([
                        'email_sent_status' => Payslip::STATUS_FAILED,
                        'sms_sent_status' => Payslip::STATUS_FAILED,
                        'failure_reason' => __('User Matricule is empty')
                    ]);
                } else {

                    preg_match("/\b" . $employee->matricule . "\b/i", $pdf_text, $matches);

                    if (!empty($matches) && $matches[0] === $employee->matricule) {

                        $destination_file = $this->destination . '/' . $employee->matricule . '_' . $pay_month . '.pdf';

                        if (Storage::disk('splitted')->exists($file)) {
                            //  Storage::disk('modified')->put($employee['matricule'].'.pdf', Storage::disk('splitted')->get($file));
                            $pdf = new Pdf(Storage::disk('splitted')->path($file), ['command' => config('ciblerh.pdftk_path')]);
                            $pdf->tempDir = config('ciblerh.temp_dir');
                            $result = $pdf->setUserPassword($employee->pdf_password)
                                ->passwordEncryption(128)
                                ->saveAs(Storage::disk('modified')->path($destination_file));

                            if (Storage::disk('modified')->exists($destination_file)) {

                                $record_exists = Payslip::where('employee_id', $employee->id)
                                    ->where('month', $pay_month)
                                    ->where('year', now()->year)
                                    ->first();

                                if (empty($record_exists)) {
                                    // global utility function
                                    createPayslipRecord($employee, $pay_month, $this->process_id, $this->user_id,$destination_file);
                                } else {
                                    if ($record_exists->successful()) {
                                        return;
                                    }
                                    $record_exists->update([
                                        'file' =>  $destination_file
                                    ]);
                                }

                            }
                        }
                    }
                }
            });
        }
    }

}
