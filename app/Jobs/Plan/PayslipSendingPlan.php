<?php

namespace App\Jobs\Plan;

use App\Models\Group;
use App\Models\Payslip;
use App\Jobs\SplitPdfJob;
use App\Models\Department;
use App\Jobs\SendPayslipJob;
use App\Jobs\RenameEncryptPdfJob;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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
                // Reconciliation: Create failed records for employees whose matricule wasn't found
                static::reconcileUnmatchedEmployees($payslip_process);
                
                $payslip_process->update(['status' => 'successful', 'percentage_completion' => $batch->progress()]);
            })->catch(function () use ($payslip_process) {
                static::failed($payslip_process);
            })->allowFailures()->name('Rename, Encrypt and record payslip')->dispatch();
        }
    }
    private static function step3($payslip_process)
    {
        $department = Department::findOrFail($payslip_process->department_id);

        if (!empty($department)) {

            $email_jobs = $department->employees->chunk(config('ciblerh.chunk_size'))->map(function ($employee_chunk) use ($payslip_process) {
                return new SendPayslipJob($employee_chunk, $payslip_process);
            });

            $batch = Bus::batch($email_jobs)
                ->then(function ($batch) use ($payslip_process) {
                    // Store batch ID for tracking
                    $payslip_process->update(['batch_id' => $batch->id]);
                    
                    // Check if any payslips failed before marking process as successful
                    $totalPayslips = $payslip_process->payslips()->count();
                    $failedPayslips = $payslip_process->payslips()
                        ->where('email_sent_status', \App\Models\Payslip::STATUS_FAILED)
                        ->count();
                    
                    if ($failedPayslips > 0) {
                        // Some emails failed - batch completed but with failures
                        $payslip_process->update([
                            'status' => 'successful', // Batch completed successfully
                            'percentage_completion' => 100,
                            'failure_reason' => __('payslips.process_completed_with_failures', [
                                'failed' => $failedPayslips,
                                'total' => $totalPayslips
                            ])
                        ]);
                    } else {
                        // All emails succeeded
                        $payslip_process->update([
                            'status' => 'successful',
                            'percentage_completion' => 100,
                            'failure_reason' => null
                        ]);
                    }
                })
                ->catch(function ($batch, $exception) use ($payslip_process) {
                    $payslip_process->update(['batch_id' => $batch->id]);
                static::failed($payslip_process);
                })
                ->name('Send Payslips')
                ->allowFailures()
                ->dispatch();
        }
    }

    /**
     * Reconcile unmatched employees after all encryption jobs complete
     * Creates failed payslip records for employees whose matricule wasn't found in any PDF file
     */
    private static function reconcileUnmatchedEmployees($payslip_process)
    {
        $department = Department::findOrFail($payslip_process->department_id);
        
        if (empty($department)) {
            return;
        }

        // Get all employees in the department
        $allEmployees = $department->employees;
        
        // Get all employees who already have payslip records for this month/process
        $matchedEmployeeIds = Payslip::where('send_payslip_process_id', $payslip_process->id)
            ->where('month', $payslip_process->month)
            ->where('year', $payslip_process->year ?? now()->year)
            ->pluck('employee_id')
            ->toArray();

        // Find employees without payslip records (unmatched)
        $unmatchedEmployees = $allEmployees->whereNotIn('id', $matchedEmployeeIds);
        
        $unmatchedCount = 0;
        
        foreach ($unmatchedEmployees as $employee) {
            // Create failed payslip record for unmatched employee
            Payslip::create([
                'user_id' => $payslip_process->user_id,
                'send_payslip_process_id' => $payslip_process->id,
                'employee_id' => $employee->id,
                'company_id' => $employee->company_id ?? $payslip_process->company_id,
                'department_id' => $employee->department_id ?? $payslip_process->department_id,
                'service_id' => $employee->service_id,
                'first_name' => $employee->first_name,
                'last_name' => $employee->last_name,
                'email' => $employee->email,
                'phone' => !is_null($employee->professional_phone_number) ? $employee->professional_phone_number : $employee->personal_phone_number,
                'matricule' => $employee->matricule,
                'month' => $payslip_process->month,
                'year' => $payslip_process->year ?? now()->year,
                'file' => null,
                'encryption_status' => Payslip::STATUS_FAILED,
                'email_sent_status' => Payslip::STATUS_FAILED,
                'sms_sent_status' => Payslip::STATUS_FAILED,
                'failure_reason' => empty($employee->matricule)
                    ? __('payslips.user_matricule_empty')
                    : __('payslips.matricule_not_found_in_pdf', [
                        'matricule' => $employee->matricule,
                        'month' => $payslip_process->month
                    ])
            ]);
            
            $unmatchedCount++;
            
            Log::info('Unmatched employee payslip record created', [
                'employee_id' => $employee->id,
                'matricule' => $employee->matricule,
                'process_id' => $payslip_process->id
            ]);
        }

        // Update process failure_reason if there are unmatched employees
        if ($unmatchedCount > 0) {
            $totalEmployees = $allEmployees->count();
            $existingFailureReason = $payslip_process->failure_reason;
            
            $unmatchedMessage = __('payslips.unmatched_employees_summary', [
                'unmatched' => $unmatchedCount,
                'total' => $totalEmployees
            ]);
            
            // Append to existing failure reason or create new one
            $failureReason = $existingFailureReason 
                ? $existingFailureReason . ' | ' . $unmatchedMessage
                : $unmatchedMessage;
            
            $payslip_process->update([
                'failure_reason' => $failureReason
            ]);
            
            Log::info('Reconciliation completed', [
                'process_id' => $payslip_process->id,
                'unmatched_count' => $unmatchedCount,
                'total_employees' => $totalEmployees
            ]);
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
