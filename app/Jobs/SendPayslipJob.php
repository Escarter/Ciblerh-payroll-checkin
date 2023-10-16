<?php

namespace App\Jobs;

use App\Models\Group;
use App\Models\Payslip;
use App\Models\Employee;
use App\Mail\SendPayslip;
use Illuminate\Support\Str;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use App\Models\SendPayslipProcess;
use Illuminate\Support\Collection;
use Facade\FlareClient\Http\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Log;

class SendPayslipJob implements ShouldQueue
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

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Indicate if the job should be marked as failed on timeout.
     *
     * @var bool
     */
    public $failOnTimeout = true;

    protected $employee_chunk;
    protected $destination;
    protected $month;
    protected $process_id;
    protected $user_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Collection $employee_chunk,  SendPayslipProcess $process)
    {
        $this->employee_chunk = $employee_chunk;
        $this->destination = $process->destination_directory;
        $this->month = $process->month;
        $this->user_id = $process->user_id;
        $this->process_id = $process->id;
        
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
        $dest = $this->destination;

        $encrypted_files = Storage::disk('modified')->allFiles($this->destination);

        Log::info($encrypted_files);

        foreach ($this->employee_chunk as $employee) {

            collect($encrypted_files)->each(function ($file) use ($employee, $pay_month, $dest) {

                if (strpos($file, $employee->matricule .'_'.$pay_month.'.pdf') !== FALSE) {

                    Log::info(Storage::disk('modified')->path($file));

                    if (Storage::disk('modified')->exists($file)) {

                        $destination_file = $this->destination . '/' . $employee->matricule . '_' . $pay_month . '.pdf';

                        $record_exists = Payslip::where('employee_id',$employee->id)
                                                ->where('month',$this->month)
                                                ->where('year',now()->year)
                                                ->first();
                        

                        if (empty($record_exists)) {
                            // global utility function
                            $record = createPayslipRecord($employee, $pay_month, $this->process_id, $this->user_id, $destination_file);
                        } else {
                            if ($record_exists->email_sent_status === Payslip::STATUS_SUCCESSFUL && $record_exists->sms_sent_status === Payslip::STATUS_SUCCESSFUL) {
                                return;
                            }
                            $record = $record_exists;
                        }


                        if (!empty($employee->email)) {

                            try {
                                setSavedSmtpCredentials();

                                Mail::to(cleanString($employee->email))->send(new SendPayslip($employee, $destination_file, $pay_month));

                                $record->update([
                                    'email_sent_status' => Payslip::STATUS_SUCCESSFUL,
                                ]);

                                sendSmsAndUpdateRecord($employee, $pay_month, $record);

                                Log::info('mail-sent');

                            } catch (\Swift_TransportException $e) {

                                Log::info('------> err swift:--  ' . $e->getMessage()); // for log, remove if you not want it
                                Log::info('' . PHP_EOL . '');
                                $record->update([
                                    'email_sent_status' => Payslip::STATUS_FAILED,
                                    'sms_sent_status' => Payslip::STATUS_FAILED,
                                    'failure_reason' => $e->getMessage()
                                ]);
                            } catch (\Swift_RfcComplianceException $e) {
                                Log::info('------> err Swift_Rfc:' . $e->getMessage());
                                Log::info('' . PHP_EOL . '');

                                $record->update([
                                    'email_sent_status' => Payslip::STATUS_FAILED,
                                    'sms_sent_status' => Payslip::STATUS_FAILED,
                                    'failure_reason' => $e->getMessage()
                                ]);
                            } catch (Exception $e) {
                                Log::info('------> err' . $e->getMessage());
                                Log::info('' . PHP_EOL . '');

                                $record->update([
                                    'email_sent_status' => Payslip::STATUS_FAILED,
                                    'sms_sent_status' => Payslip::STATUS_FAILED,
                                    'failure_reason' => $e->getMessage()
                                ]);
                            }
                        } else {
                            $record->update([
                                'email_sent_status' => Payslip::STATUS_FAILED,
                                'sms_sent_status' => Payslip::STATUS_FAILED,
                                'failure_reason' => __('No valid email address for User')
                            ]);
                        }
                    }
                }
            });
        }
    }

}
