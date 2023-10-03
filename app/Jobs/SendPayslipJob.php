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

        foreach ($this->employee_chunk as $employee) {

            collect($encrypted_files)->each(function ($file) use ($employee, $pay_month, $dest) {

                if (strpos($file, $employee->matricule .'_'.$pay_month.'.pdf') !== FALSE) {

                    if (Storage::disk('modified')->exists($file)) {

                        $record_exists = Payslip::where('employee_id',$employee->id)
                                                ->where('month',$this->month)
                                                ->where('year',now()->year)
                                                ->first();

                        if($record_exists){
                            if($record_exists->successful()){
                                return ;
                            }
                            $record = $record_exists;
                        }else{
                            $record = $this->createPayslipRecord($employee,$pay_month);
                        }
                        
                        if(!is_null($employee->email)){

                            Mail::to($employee->email)->send(new SendPayslip($employee,$dest,$pay_month));

                            if(Mail::failures()){
                                $record->update([
                                        'email_sent_status' => 'failed',
                                        'sms_sent_status' => 'failed',
                                        'failure_reason'=> __('Failed sending Email & SMS')
                                    ]);
                            }else{
                                    $record->update(['email_sent_status' => 'successful']);
                                    sendSmsAndUpdateRecord($employee,$pay_month,$record);
                            }
                        }else{
                            $record->update([
                                'email_sent_status' => 'failed',
                                'sms_sent_status' => 'failed',
                                'failure_reason' => __('No valid email address for User')
                            ]);
                        }
                    }
                }
            });
        }
    }
 
    public function createPayslipRecord($employee,$month)
    {
       return
        Payslip::create([
            'user_id' => $this->user_id,
            'author_id' => $this->user_id,
            'send_payslip_process_id' => $this->process_id,
            'employee_id' => $employee->id,
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'email' => $employee->email,
            'phone' => !is_null($employee->professional_phone_number) ? $employee->professional_phone_number : $employee->personal_phone_number,
            'matricule' => $employee->matricule,
            'month' => $this->month,
            'year' => now()->year,
        ]);
    }
}
