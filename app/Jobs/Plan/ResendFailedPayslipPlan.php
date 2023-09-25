<?php 

namespace App\Jobs\Plan;

use App\Jobs\Single\ResendFailedPayslipJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use App\Jobs\Single\SplitPdfSingleEmployee;

class ResendFailedPayslipPlan
{ 
    public static function start($employee_id, $file, $record, $month, $destination, $user_id)
    {
        Bus::chain([
            new SplitPdfSingleEmployee($file, $destination),
            function () use ($employee_id, $month, $record, $destination, $user_id) {
                static::step2($employee_id, $month, $record,$destination, $user_id);
            }
        ])->dispatch();
    }
    private static function step2($employee_id, $month, $record, $destination, $user_id)
    {
        $files =  Storage::disk('splitted')->allFiles($destination);

        if (count($files) > 0) {

            $chunks = array_chunk($files, 50);

            $jobs = collect($chunks)->map(function ($chunk) use ($employee_id, $record, $month, $destination, $user_id) {
                return new ResendFailedPayslipJob($chunk, $employee_id, $record, $month, $destination, $user_id);
            });

            Bus::batch($jobs)
                ->allowFailures()
                ->name('Rename, Encrypt and resend Payslips for single user')
                ->dispatch();
        }
    }

    private static function failed($payslip_process)
    {
        // Run any cleaning work ...
    }
}
