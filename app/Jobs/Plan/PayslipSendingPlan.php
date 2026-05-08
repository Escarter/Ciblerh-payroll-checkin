<?php

namespace App\Jobs\Plan;

use App\Models\Payslip;
use App\Models\PayslipMatchingProposal;
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
            }
        ])->catch(function (\Throwable $e) use ($payslip_process) {
            static::failed($payslip_process, $e->getMessage());
        })->dispatch();
    }
    private static function step2($payslip_process)
    {
        try {
            $files = Storage::disk('splitted')->allFiles($payslip_process->destination_directory);
        } catch (\Throwable $e) {
            Log::error('PayslipSendingPlan: Cannot list splitted files (permission or path error)', [
                'payslip_process_id' => $payslip_process->id,
                'destination_directory' => $payslip_process->destination_directory,
                'error' => $e->getMessage(),
            ]);
            static::failed($payslip_process, $e->getMessage());
            return;
        }

        if (count($files) === 0) {
            Log::warning('PayslipSendingPlan: No splitted files to process', [
                'payslip_process_id' => $payslip_process->id,
                'destination_directory' => $payslip_process->destination_directory,
            ]);
            static::failed($payslip_process, __('payslips.no_splitted_files_to_process'));
            return;
        }

        $chunks = collect($files)->chunk(config('ciblerh.chunk_size'));
        $jobs = collect($chunks)->map(function ($chunk) use ($payslip_process) {
            return new RenameEncryptPdfJob($chunk, $payslip_process->id);
        });

        Bus::batch($jobs)
            ->onQueue('pdf-processing')
            ->then(function ($batch) use ($payslip_process) {
                static::syncPayslipPeriodsToProcess($payslip_process);
                static::reconcileUnmatchedEmployees($payslip_process);
                $payslip_process->update(['status' => 'successful', 'percentage_completion' => $batch->progress()]);
                // After combination batch completes, finalize encryption (for multi-page payslips)
                // Then run step3 (SendPayslipJob) only after encryption is finalized
                static::step2_finalize($payslip_process);
            })
            ->catch(function ($batch, $exception) use ($payslip_process) {
                static::failed($payslip_process, $exception instanceof \Throwable ? $exception->getMessage() : null);
            })
            ->allowFailures()
            ->name('Rename, Encrypt and record payslip')
            ->dispatch();
    }

    /**
     * Ensure all payslips created for a process inherit the process month/year.
     *
     * This self-heals records created through older code paths that defaulted to
     * the current year instead of the schedule year.
     */
    private static function syncPayslipPeriodsToProcess($payslip_process): void
    {
        Payslip::query()
            ->where('send_payslip_process_id', $payslip_process->id)
            ->where(function ($query) use ($payslip_process) {
                $query->where('month', '!=', $payslip_process->month)
                    ->orWhere('year', '!=', $payslip_process->year)
                    ->orWhereNull('month')
                    ->orWhereNull('year');
            })
            ->update([
                'month' => $payslip_process->month,
                'year' => $payslip_process->year,
            ]);
    }

    /**
     * Finalize multi-page payslips: encrypt all pending items after combination
     * Then proceed to step3 (SendPayslipJob)
     * 
     * Uses Bus::chain() to ensure finalization completes BEFORE sending emails.
     * This prevents race condition where PENDING payslips would be skipped by SendPayslipJob.
     */
    private static function step2_finalize($payslip_process)
    {
        // Chain jobs sequentially:
        // 1. FinalizeMultiPagePayslipsJob encrypts all combined/pending payslips (becomes SUCCESSFUL)
        // 2. SendPayslipJob sends them (only runs after step 1 completes)
        Bus::chain([
            new \App\Jobs\FinalizeMultiPagePayslipsJob($payslip_process->id),
            function () use ($payslip_process) {
                static::step3($payslip_process);
            }
        ])
        ->onQueue('pdf-processing')
        ->catch(function (\Throwable $e) use ($payslip_process) {
            Log::error('PayslipSendingPlan: Error in finalization chain', [
                'process_id' => $payslip_process->id,
                'error' => $e->getMessage()
            ]);
            static::failed($payslip_process, $e->getMessage());
        })
        ->dispatch();
    }

    private static function step3($payslip_process)
    {
        try {
            $employees = $payslip_process->department_id
                ? Department::withTrashed()->findOrFail($payslip_process->department_id)->employees
                : \App\Models\User::where('company_id', $payslip_process->company_id)
                    ->whereHas('roles', fn($q) => $q->where('name', 'employee'))
                    ->get();
        } catch (\Throwable $e) {
            \Log::error('PayslipSendingPlan::step3 - Cannot load employees (department might be deleted)', [
                'process_id' => $payslip_process->id,
                'department_id' => $payslip_process->department_id,
                'error' => $e->getMessage(),
            ]);
            static::failed($payslip_process, __('payslips.failed_to_load_employees',  ['error' => $e->getMessage()]));
            return;
        }

        $email_jobs = $employees->chunk(config('ciblerh.chunk_size'))->map(function ($employee_chunk) use ($payslip_process) {
            return new SendPayslipJob($employee_chunk, $payslip_process);
        });

        Bus::batch($email_jobs)
            ->onQueue('emails')
            ->then(function ($batch) use ($payslip_process) {
                $payslip_process->update(['batch_id' => $batch->id]);

                $totalPayslips = $payslip_process->payslips()->count();
                $failedPayslips = $payslip_process->payslips()
                    ->where('email_sent_status', \App\Models\Payslip::STATUS_FAILED)
                    ->count();

                if ($failedPayslips > 0) {
                    $payslip_process->update([
                        'status' => 'successful',
                        'percentage_completion' => 100,
                        'failure_reason' => __('payslips.process_completed_with_failures', [
                            'failed' => $failedPayslips,
                            'total' => $totalPayslips
                        ])
                    ]);
                } else {
                    $payslip_process->update([
                        'status' => 'successful',
                        'percentage_completion' => 100,
                        'failure_reason' => null
                    ]);
                }

                static::markRelatedSftpProposalProcessed($payslip_process);
            })
            ->catch(function ($batch, $exception) use ($payslip_process) {
                $payslip_process->update(['batch_id' => $batch->id]);
                static::failed($payslip_process, $exception instanceof \Throwable ? $exception->getMessage() : null);
            })
            ->name('Send Payslips')
            ->allowFailures()
            ->dispatch();
    }

    /**
     * Reconcile unmatched employees after all encryption jobs complete
     * Creates failed payslip records for employees whose matricule wasn't found in any PDF file
     */
    private static function reconcileUnmatchedEmployees($payslip_process)
    {
        try {
            $allEmployees = $payslip_process->department_id
                ? Department::withTrashed()->findOrFail($payslip_process->department_id)->employees
                : \App\Models\User::where('company_id', $payslip_process->company_id)
                    ->whereHas('roles', fn($q) => $q->where('name', 'employee'))
                    ->get();
        } catch (\Throwable $e) {
            \Log::error('PayslipSendingPlan::reconcileUnmatchedEmployees - Cannot load employees (department might be deleted)', [
                'process_id' => $payslip_process->id,
                'department_id' => $payslip_process->department_id,
                'error' => $e->getMessage(),
            ]);
            return;
        }
        
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
                        'month' => translateMonthName($payslip_process->month)
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

    private static function failed($payslip_process, ?string $reason = null): void
    {
        $generic = __('payslips.process_failed_generic');
        $failure_reason = $reason
            ? $generic . ' | ' . $reason
            : $generic;

        $payslip_process->update([
            'status' => 'failed',
            'failure_reason' => $failure_reason,
        ]);

        static::markRelatedSftpProposalFailed($payslip_process, $failure_reason);
    }

    /**
     * Mark SFTP matching proposal as processed only when downstream process completed.
     */
    private static function markRelatedSftpProposalProcessed($payslip_process): void
    {
        $query = PayslipMatchingProposal::query()
            ->whereIn('status', [
                PayslipMatchingProposal::STATUS_VALIDATED,
                PayslipMatchingProposal::STATUS_PENDING,
            ])
            ->orderByDesc('created_at')
            ->limit(1);

        // Prefer direct ID lookup; fall back to path+context matching for legacy records
        if (!empty($payslip_process->sftp_proposal_id)) {
            $query->where('id', $payslip_process->sftp_proposal_id);
        } else {
            $query
                ->where('local_file_path', $payslip_process->raw_file)
                ->where('matched_to_company_id', $payslip_process->company_id)
                ->where('matched_month', self::convertMonthToNumber($payslip_process->month))
                ->where('matched_year', $payslip_process->year);
        }

        $query->update([
            'status' => PayslipMatchingProposal::STATUS_PROCESSED,
            'processed_at' => now(),
        ]);
    }

    /**
     * Convert month name to number for database compatibility.
     */
    private static function convertMonthToNumber($month): ?int
    {
        if ($month === null) {
            return null;
        }
        
        $monthMap = [
            'January' => 1, 'Jan' => 1,
            'February' => 2, 'Feb' => 2,
            'March' => 3, 'Mar' => 3,
            'April' => 4, 'Apr' => 4,
            'May' => 5,
            'June' => 6, 'Jun' => 6,
            'July' => 7, 'Jul' => 7,
            'August' => 8, 'Aug' => 8,
            'September' => 9, 'Sep' => 9, 'Sept' => 9,
            'October' => 10, 'Oct' => 10,
            'November' => 11, 'Nov' => 11,
            'December' => 12, 'Dec' => 12,
        ];
        
        $normalized = ucfirst(strtolower(trim($month)));
        return $monthMap[$normalized] ?? null;
    }

    /**
     * Mark related SFTP proposal as failed when downstream process fails.
     */
    private static function markRelatedSftpProposalFailed($payslip_process, string $failureReason): void
    {
        $query = PayslipMatchingProposal::query()
            ->whereIn('status', [
                PayslipMatchingProposal::STATUS_VALIDATED,
                PayslipMatchingProposal::STATUS_PENDING,
            ])
            ->orderByDesc('created_at')
            ->limit(1);

        // Prefer direct ID lookup; fall back to path+context matching for legacy records
        if (!empty($payslip_process->sftp_proposal_id)) {
            $query->where('id', $payslip_process->sftp_proposal_id);
        } else {
            $query
                ->where('local_file_path', $payslip_process->raw_file)
                ->where('matched_to_company_id', $payslip_process->company_id)
                ->where('matched_month', self::convertMonthToNumber($payslip_process->month))
                ->where('matched_year', $payslip_process->year);
        }

        $query->update([
            'status' => PayslipMatchingProposal::STATUS_FAILED,
            'rejection_reason' => $failureReason,
        ]);
    }
}
