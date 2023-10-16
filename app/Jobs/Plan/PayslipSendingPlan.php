<?php

namespace App\Jobs\Plan;

use App\Models\Group;
use App\Jobs\SplitPdfJob;
use App\Models\Department;
use App\Jobs\SendPayslipJob;
use App\Jobs\RenameEncryptPdfJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

class PayslipSendingPlan
{

    public static function start($payslip_process)
    {
        Bus::chain([
            new SplitPdfJob($payslip_process),
            function () use ($payslip_process) {
                static::step2($payslip_process);
            },
            function () use ($payslip_process) {
                static::step3($payslip_process);
            }
        ])->catch(function () use ($payslip_process) {
            static::failed($payslip_process);
        })->dispatch();
    }
    private static function step2($payslip_process)
    {
        $files =  Storage::disk('splitted')->allFiles($payslip_process->destination_directory);

        if (count($files) > 0) {

            $chunks = collect($files)->chunk(config('ciblerh.chunk_size'));

            $jobs = collect($chunks)->map(function ($chunk) use ($payslip_process) {
                return new  RenameEncryptPdfJob($chunk, $payslip_process->id);
            });

            Bus::batch($jobs)->then(function ($batch) use ($payslip_process) {
                $payslip_process->update(['status' => 'successful', 'percentage_completion' => $batch->progress()]);
            })->catch(function () use ($payslip_process) {
                static::failed($payslip_process);
            })->allowFailures()
                ->name('Rename, Encrypt and record payslip')->dispatch();
        }
    }
    private static function step3($payslip_process)
    {
        $department = Department::findOrFail($payslip_process->department_id);

        if (!empty($department)) {

            $email_jobs = $department->employees->chunk(config('ciblerh.chunk_size'))->map(function ($employee_chunk) use ($payslip_process) {
                return new SendPayslipJob($employee_chunk, $payslip_process);
            });

            $batch = Bus::batch($email_jobs)->then(function ($batch) use ($payslip_process) {
                $payslip_process->update(['status' => 'successful', 'percentage_completion' => $batch->progress()]);
            })->catch(function () use ($payslip_process) {
                static::failed($payslip_process);
            })->name('Send Payslips')->allowFailures()->dispatch();
        }
    }

    private static function failed($payslip_process)
    {
        // Run any cleaning work ...
        $payslip_process->update([
            'status' => 'failed',
            'failure_reason' => 'Something went wrong in the proccess!'
        ]);
    }
}
