<?php 

namespace App\Jobs\Plan;

use App\Models\Group;
use App\Jobs\SplitPdfJob;
use App\Jobs\SendPayslipJob;
use App\Jobs\RenameEncryptPdfJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

class PayslipSendingPlan {

    public static function start($payslip_process)
    {
        Bus::chain([
            new SplitPdfJob($payslip_process),
            function () use ($payslip_process) {
                static::step2($payslip_process);
            }
        ])->catch(function () use ($payslip_process) {
            static::failed($payslip_process);
        })->dispatch();
    }
    private static function step2($payslip_process)
    {
        $files =  Storage::disk('splitted')->allFiles($payslip_process->destination_directory);

        if(count($files) > 0){

            $chunks = array_chunk($files, 50);
    
            $jobs = collect($chunks)->map(function ($chunk) use ($payslip_process) {
                return new  RenameEncryptPdfJob($chunk, $payslip_process->id);
            });
    
            Bus::batch($jobs)->then(function ($batch) use ($payslip_process) {
                $payslip_process->update(['status' => 'successful', 'percentage_completion' => $batch->progress()]);
            })->catch(function () use ($payslip_process) {
                static::failed($payslip_process);
            })->allowFailures()
            ->name('Rename, Encrypt and send Payslips')->dispatch();
        }

        
    }
    private static function step3($payslip_process)
    {
        // $modified_files =  Storage::disk('modified')->allFiles($payslip_process->destination_directory);

        // if (count($modified_files) > 0) {

        //     $chunks = array_chunk($modified_files, 50);

        //     $email_jobs = collect($chunks)->map(function ($chunk) use ($payslip_process) {
        //         return new  SendPayslipJob($chunk, $payslip_process, $payslip_process->month);
        //     });

        //     Bus::batch($email_jobs)->then(function ($batch) use ($payslip_process) {
        //         $payslip_process->update(['status' => 'successful', 'percentage_completion' => $batch->progress()]);
        //     })->catch(function () use ($payslip_process) {
        //         static::failed($payslip_process);
        //     })->name('Send Payslips')->allowFailures()->dispatch();

        // }
        $group = Group::findOrFail($payslip_process->group_id);

        // $employee_chunks = collect($group->employees)->chunk(5);
        $email_jobs = $group->employees->chunk(10)->map(function ($employee_chunk) use ($payslip_process) {
            return new SendPayslipJob($employee_chunk, $payslip_process);
        });

        Bus::batch($email_jobs)->then(function ($batch) use ($payslip_process) {
            $payslip_process->update(['status' => 'successful', 'percentage_completion' => $batch->progress()]);
        })->catch(function () use ($payslip_process) {
            static::failed($payslip_process);
        })->name('Send Payslips')->allowFailures()->dispatch();
    }

    private static function failed($payslip_process)
    {
        // Run any cleaning work ...
        $payslip_process->update([
            'status' => 'failed',
            'failure_reason' => 'Something went wrong in the proccess!'
        ]);
    }

    // Bus::chain([
    //         new SplitPdfJob($payslip_process),
    //         function () use($payslip_process){
    //             $files =  Storage::disk('splitted')->allFiles($payslip_process->destination_directory);

    //             $chunks = array_chunk($files, 10);

    //             $jobs = collect($chunks)->map(function ($chunk) use ($payslip_process) {
    //                 return new  RenameEncryptPdfJob($chunk, $payslip_process);
    //             });

    //             Bus::batch($jobs)->then(function () use ($payslip_process) {

    //                 $group = Group::findOrFail($payslip_process->group_id);
                    
    //                 $employee_chunks = collect($group->employees)->chunk(10);
    //                 $email_jobs = $employee_chunks->map(function ($employee_chunk) use ($payslip_process) {
    //                                 return new SendPayslipJob($employee_chunk, $payslip_process);
    //                             });
                       
    //                 Bus::batch($email_jobs)->dispatch();
                  
    //             })->dispatch();
    //         }
    //     ])->dispatch();
}