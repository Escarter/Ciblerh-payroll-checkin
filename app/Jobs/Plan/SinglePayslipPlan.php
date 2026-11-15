<?php 
namespace App\Jobs\Plan;

use App\Models\Payslip;
use App\Models\User;
use App\Jobs\Single\SplitPdfSingleEmployee;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Jobs\Single\SinglePayslipProcessingJob;

class SinglePayslipPlan {

    public static function start($employee_id, $file, $month, $destination, $user_id)
    {
        Bus::chain([
            new SplitPdfSingleEmployee($file, $destination),
            function () use ($employee_id, $month, $destination, $user_id) {
                static::step2($employee_id, $month, $destination, $user_id);
            }
        ])->dispatch();
    }
    private static function step2($employee_id, $month, $destination, $user_id)
    {
        $files =  Storage::disk('splitted')->allFiles($destination);

        if (count($files) > 0) {

            $chunks = array_chunk($files, 50);

            $jobs = collect($chunks)->map(function ($chunk) use ($employee_id, $month, $destination, $user_id) {
                return new  SinglePayslipProcessingJob($chunk, $employee_id, $month, $destination, $user_id);
            });

            Bus::batch($jobs)
            ->then(function ($batch) use ($employee_id, $month, $user_id) {
                // Reconciliation: Check if employee's matricule was found
                static::reconcileSingleEmployee($employee_id, $month, $user_id);
            })
            ->allowFailures()
            ->name('Rename, Encrypt and send Payslips for single user')
            ->dispatch();
        }
    }

    /**
     * Reconcile single employee after all processing jobs complete
     * Creates failed payslip record if employee's matricule wasn't found in any PDF file
     */
    private static function reconcileSingleEmployee($employee_id, $month, $user_id)
    {
        $employee = User::findOrFail($employee_id);
        
        // Check if employee already has a payslip record for this month
        $existingRecord = Payslip::where('employee_id', $employee_id)
            ->where('month', $month)
            ->where('year', now()->year)
            ->first();
        
        // If no record exists, create a failed one
        if (empty($existingRecord)) {
            Payslip::create([
                'user_id' => $user_id,
                'employee_id' => $employee->id,
                'company_id' => $employee->company_id,
                'department_id' => $employee->department_id,
                'service_id' => $employee->service_id,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'email' => $employee->email,
                'phone' => !is_null($employee->professional_phone_number) ? $employee->professional_phone_number : $employee->personal_phone_number,
                'matricule' => $employee->matricule,
                'month' => $month,
                'year' => now()->year,
                'file' => null,
                'encryption_status' => Payslip::STATUS_FAILED,
                'email_sent_status' => Payslip::STATUS_FAILED,
                'sms_sent_status' => Payslip::STATUS_FAILED,
                'failure_reason' => empty($employee->matricule)
                    ? __('payslips.user_matricule_empty')
                    : __('payslips.matricule_not_found_in_pdf', [
                        'matricule' => $employee->matricule,
                        'month' => translateMonthName($month)
                    ])
            ]);
            
            Log::info('Unmatched single employee payslip record created', [
                'employee_id' => $employee_id,
                'matricule' => $employee->matricule,
                'month' => $month
            ]);
        }
    }

    private static function failed($payslip_process)
    {
        // Run any cleaning work ...
    }

}