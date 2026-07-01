<?php

namespace App\Services;

use App\Models\Payslip;
use App\Models\SendPayslipProcess;

class PayslipProcessGuardService
{
    /**
     * Latest non-deleted process for the same department/company and pay period.
     */
    public function findLatestForPeriod(
        ?int $departmentId,
        int $companyId,
        int|string $month,
        int $year,
    ): ?SendPayslipProcess {
        $month = normalizeMonthToEnglishName($month) ?? (string) $month;

        $query = SendPayslipProcess::query()
            ->where('year', $year)
            ->where('month', $month);

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        } else {
            $query->where('company_id', $companyId)->whereNull('department_id');
        }

        return $query->orderByDesc('id')->first();
    }

    /**
     * Decide whether a new payslip run may start for this period.
     */
    public function evaluateStart(
        ?int $departmentId,
        int $companyId,
        int|string $month,
        int $year,
        bool $allowResumeOnFailed = true,
    ): PayslipProcessStartResult {
        $existing = $this->findLatestForPeriod($departmentId, $companyId, $month, $year);

        if (!$existing) {
            return new PayslipProcessStartResult(PayslipProcessStartResult::ACTION_ALLOW_NEW);
        }

        if ($existing->status === 'processing') {
            return new PayslipProcessStartResult(
                PayslipProcessStartResult::ACTION_BLOCK,
                $existing,
                'payslips.period_process_already_running',
                $this->messageParams($existing),
            );
        }

        if (in_array($existing->status, ['failed', 'cancelled'], true) && $allowResumeOnFailed) {
            return new PayslipProcessStartResult(
                PayslipProcessStartResult::ACTION_RESUME_EXISTING,
                $existing,
                'payslips.period_process_resuming',
                $this->messageParams($existing),
            );
        }

        if ($this->hasIncompleteWork($existing)) {
            return new PayslipProcessStartResult(
                PayslipProcessStartResult::ACTION_BLOCK,
                $existing,
                'payslips.period_has_incomplete_process',
                $this->messageParams($existing),
            );
        }

        return new PayslipProcessStartResult(
            PayslipProcessStartResult::ACTION_BLOCK,
            $existing,
            'payslips.period_already_completed',
            $this->messageParams($existing),
        );
    }

    public function hasIncompleteWork(SendPayslipProcess $process): bool
    {
        return Payslip::query()
            ->where('send_payslip_process_id', $process->id)
            ->where(function ($query) {
                $query->where('encryption_status', Payslip::STATUS_PENDING)
                    ->orWhere('encryption_status', Payslip::STATUS_FAILED)
                    ->orWhere('email_sent_status', Payslip::STATUS_FAILED)
                    ->orWhere('sms_sent_status', Payslip::STATUS_FAILED);
            })
            ->exists();
    }

    /**
     * Reset an existing process for a new PDF run (same period, same process row).
     */
    public function prepareForResume(
        SendPayslipProcess $process,
        string $rawFile,
        string $destinationDirectory,
        ?int $userId = null,
        ?int $sftpProposalId = null,
    ): SendPayslipProcess {
        $attrs = [
            'status' => 'processing',
            'raw_file' => $rawFile,
            'destination_directory' => $destinationDirectory,
            'batch_id' => '',
            'percentage_completion' => 0,
            'failure_reason' => null,
        ];

        if ($userId !== null) {
            $attrs['user_id'] = $userId;
            $attrs['author_id'] = $userId;
        }

        if ($sftpProposalId !== null) {
            $attrs['sftp_proposal_id'] = $sftpProposalId;
        }

        $process->update($attrs);

        return $process->fresh();
    }

    private function messageParams(SendPayslipProcess $process): array
    {
        $process->loadMissing('departmentWithTrashed');

        return [
            'process_id' => $process->id,
            'month' => $process->month,
            'year' => $process->year,
            'department' => $process->departmentWithTrashed?->name ?? __('common.unknown'),
            'details_url' => route('portal.payslips.details', $process->id),
        ];
    }
}
