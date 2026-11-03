<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Payslip;
use App\Models\SendPayslipProcess;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PayslipProcessLinkService
{
    /**
     * Re-link payslip rows that have files for this period but belong to another process.
     */
    public function relinkPayslipsWithFilesToProcess(SendPayslipProcess $process, Collection $employees): int
    {
        $employeeIds = $employees->pluck('id')->filter()->all();
        if ($employeeIds === []) {
            return 0;
        }

        $year = $process->year ?? now()->year;

        return Payslip::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('month', $process->month)
            ->where('year', $year)
            ->whereNotNull('file')
            ->where('file', '!=', '')
            ->where('send_payslip_process_id', '!=', $process->id)
            ->whereIn('encryption_status', [
                Payslip::STATUS_PENDING,
                Payslip::STATUS_SUCCESSFUL,
            ])
            ->update([
                'send_payslip_process_id' => $process->id,
                'user_id' => $process->user_id,
            ]);
    }

    /**
     * Remove stale failed payslip rows on a process when the same employee already
     * has a matched row (with file) on that process — typical after a re-run.
     */
    public function removeDuplicateFailedPayslips(SendPayslipProcess $process): int
    {
        $matchedEmployeeIds = Payslip::query()
            ->where('send_payslip_process_id', $process->id)
            ->whereNotNull('file')
            ->where('file', '!=', '')
            ->whereIn('encryption_status', [
                Payslip::STATUS_PENDING,
                Payslip::STATUS_SUCCESSFUL,
            ])
            ->pluck('employee_id');

        if ($matchedEmployeeIds->isEmpty()) {
            return 0;
        }

        return Payslip::query()
            ->where('send_payslip_process_id', $process->id)
            ->whereIn('employee_id', $matchedEmployeeIds)
            ->where('encryption_status', Payslip::STATUS_FAILED)
            ->where(function ($query) {
                $query->whereNull('file')->orWhere('file', '');
            })
            ->delete();
    }

    public function resolveEmployeePool(SendPayslipProcess $process): Collection
    {
        if ($process->department_id) {
            $department = Department::withTrashed()->find($process->department_id);

            return $department ? $department->employees : collect();
        }

        return User::where('company_id', $process->company_id)
            ->whereHas('roles', fn ($q) => $q->where('name', 'employee'))
            ->get();
    }

    /**
     * Find payslip rows (other processes) that already hold files for this period.
     *
     * @return Collection<int, Payslip>
     */
    public function findOrphanedFilePayslips(SendPayslipProcess $process, Collection $employees): Collection
    {
        $employeeIds = $employees->pluck('id')->filter()->all();
        if ($employeeIds === []) {
            return collect();
        }

        $year = $process->year ?? now()->year;

        return Payslip::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('month', $process->month)
            ->where('year', $year)
            ->whereNotNull('file')
            ->where('file', '!=', '')
            ->where('send_payslip_process_id', '!=', $process->id)
            ->whereIn('encryption_status', [
                Payslip::STATUS_PENDING,
                Payslip::STATUS_SUCCESSFUL,
            ])
            ->get(['id', 'employee_id', 'matricule', 'send_payslip_process_id', 'file', 'encryption_status']);
    }

    /**
     * @return array{relinked: int, removed_duplicates: int}
     */
    public function repairProcess(SendPayslipProcess $process): array
    {
        $employees = $this->resolveEmployeePool($process);
        $relinked = $this->relinkPayslipsWithFilesToProcess($process, $employees);
        $removed = $this->removeDuplicateFailedPayslips($process);

        if ($relinked > 0 || $removed > 0) {
            Log::info('PayslipProcessLinkService: repaired process links', [
                'process_id' => $process->id,
                'relinked' => $relinked,
                'removed_duplicates' => $removed,
            ]);
        }

        return [
            'relinked' => $relinked,
            'removed_duplicates' => $removed,
        ];
    }
}
