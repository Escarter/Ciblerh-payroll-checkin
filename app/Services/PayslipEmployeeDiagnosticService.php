<?php

namespace App\Services;

use App\Models\Payslip;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class PayslipEmployeeDiagnosticService
{
    public function diagnose(User $employee): array
    {
        $linkedPayslips = $this->linkedPayslips($employee);
        $visiblePayslips = $this->visiblePayslips($employee);
        $deletedPayslips = $this->deletedPayslips($employee);
        $duplicateAccounts = $this->duplicateAccounts($employee);
        $onSiblingAccounts = $this->payslipsOnSiblingAccounts($employee, $duplicateAccounts);
        $matriculeMismatches = $this->matriculeMismatches($linkedPayslips, $employee);
        $missingFiles = $this->missingFilePayslips($visiblePayslips);
        $unencryptedWithFile = $this->unencryptedWithFilePayslips($visiblePayslips);

        $issues = [];

        if ($duplicateAccounts->isNotEmpty()) {
            $issues[] = [
                'type' => 'duplicate_accounts',
                'severity' => 'warning',
                'message' => __('payslips.diagnostic_duplicate_accounts', ['count' => $duplicateAccounts->count()]),
                'details' => $duplicateAccounts->map(fn (User $user) => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'active_payslips' => Payslip::where('employee_id', $user->id)->whereNull('deleted_at')->count(),
                ])->values()->all(),
            ];
        }

        if ($onSiblingAccounts->isNotEmpty()) {
            $issues[] = [
                'type' => 'payslips_on_sibling_accounts',
                'severity' => 'warning',
                'message' => __('payslips.diagnostic_payslips_on_sibling_accounts', ['count' => $onSiblingAccounts->count()]),
                'details' => $onSiblingAccounts->map(fn (Payslip $payslip) => [
                    'id' => $payslip->id,
                    'month' => $payslip->month,
                    'year' => $payslip->year,
                    'employee_id' => $payslip->employee_id,
                ])->values()->all(),
            ];
        }

        if ($deletedPayslips->isNotEmpty()) {
            $issues[] = [
                'type' => 'soft_deleted',
                'severity' => 'info',
                'message' => __('payslips.diagnostic_soft_deleted', ['count' => $deletedPayslips->count()]),
                'details' => [],
            ];
        }

        if ($matriculeMismatches->isNotEmpty()) {
            $issues[] = [
                'type' => 'matricule_mismatch',
                'severity' => 'warning',
                'message' => __('payslips.diagnostic_matricule_mismatch', ['count' => $matriculeMismatches->count()]),
                'details' => $matriculeMismatches->map(fn (Payslip $payslip) => [
                    'id' => $payslip->id,
                    'payslip_matricule' => $payslip->matricule,
                    'employee_matricule' => $employee->matricule,
                    'month' => $payslip->month,
                    'year' => $payslip->year,
                ])->values()->all(),
            ];
        }

        if ($missingFiles->isNotEmpty()) {
            $issues[] = [
                'type' => 'missing_files',
                'severity' => 'danger',
                'message' => __('payslips.diagnostic_missing_files', ['count' => $missingFiles->count()]),
                'details' => $missingFiles->map(fn (Payslip $payslip) => [
                    'id' => $payslip->id,
                    'month' => $payslip->month,
                    'year' => $payslip->year,
                ])->values()->all(),
            ];
        }

        if ($unencryptedWithFile->isNotEmpty()) {
            $issues[] = [
                'type' => 'unencrypted_with_file',
                'severity' => 'info',
                'message' => __('payslips.diagnostic_unencrypted_with_file', ['count' => $unencryptedWithFile->count()]),
                'details' => [],
            ];
        }

        if ($visiblePayslips->count() > $linkedPayslips->count()) {
            $issues[] = [
                'type' => 'visible_more_than_linked',
                'severity' => 'info',
                'message' => __('payslips.diagnostic_visible_more_than_linked', [
                    'visible' => $visiblePayslips->count(),
                    'linked' => $linkedPayslips->count(),
                ]),
                'details' => [],
            ];
        }

        if ($issues === [] && $linkedPayslips->isNotEmpty()) {
            $issues[] = [
                'type' => 'healthy',
                'severity' => 'success',
                'message' => __('payslips.diagnostic_healthy'),
                'details' => [],
            ];
        }

        if ($linkedPayslips->isEmpty() && $visiblePayslips->isEmpty()) {
            $issues[] = [
                'type' => 'no_payslips',
                'severity' => 'info',
                'message' => __('payslips.diagnostic_no_payslips'),
                'details' => [],
            ];
        }

        return [
            'issues' => $issues,
            'summary' => [
                'linked_count' => $linkedPayslips->count(),
                'visible_count' => $visiblePayslips->count(),
                'deleted_count' => $deletedPayslips->count(),
                'duplicate_account_count' => $duplicateAccounts->count(),
                'on_sibling_account_count' => $onSiblingAccounts->count(),
                'matricule_mismatch_count' => $matriculeMismatches->count(),
                'missing_file_count' => $missingFiles->count(),
                'unencrypted_with_file_count' => $unencryptedWithFile->count(),
            ],
            'can_relink' => $onSiblingAccounts->isNotEmpty() || $matriculeMismatches->isNotEmpty(),
            'can_restore_deleted' => $deletedPayslips->isNotEmpty(),
            'can_encrypt' => $unencryptedWithFile->isNotEmpty(),
        ];
    }

    /**
     * @return array{relinked: int, matricules_synced: int}
     */
    public function relinkPayslipsToEmployee(User $employee): array
    {
        $normalizedMatricule = User::normalizeMatricule($employee->matricule);
        if ($normalizedMatricule === null || $normalizedMatricule === '') {
            return ['relinked' => 0, 'matricules_synced' => 0];
        }

        $relinkQuery = Payslip::withTrashed()
            ->whereRaw('UPPER(TRIM(matricule)) = ?', [$normalizedMatricule])
            ->where('employee_id', '!=', $employee->id);

        if ($employee->company_id) {
            $relinkQuery->where('company_id', $employee->company_id);
        }

        $relinked = (clone $relinkQuery)->update([
            'employee_id' => $employee->id,
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'email' => $employee->email,
            'phone' => $employee->professional_phone_number,
        ]);

        $matriculesSynced = Payslip::query()
            ->where('employee_id', $employee->id)
            ->whereNull('deleted_at')
            ->where(function ($query) use ($normalizedMatricule) {
                $query->whereNull('matricule')
                    ->orWhereRaw('UPPER(TRIM(matricule)) != ?', [$normalizedMatricule]);
            })
            ->update(['matricule' => $employee->matricule]);

        return [
            'relinked' => $relinked,
            'matricules_synced' => $matriculesSynced,
        ];
    }

    public function restoreDeletedPayslips(User $employee): int
    {
        $normalizedMatricule = User::normalizeMatricule($employee->matricule);

        $query = Payslip::onlyTrashed()->where(function ($q) use ($employee, $normalizedMatricule) {
            $q->where('employee_id', $employee->id);

            if ($normalizedMatricule !== null && $normalizedMatricule !== '') {
                $q->orWhere(function ($q2) use ($employee, $normalizedMatricule) {
                    if ($employee->company_id) {
                        $q2->where('company_id', $employee->company_id);
                    }
                    $q2->whereRaw('UPPER(TRIM(matricule)) = ?', [$normalizedMatricule]);
                });
            }
        });

        $ids = $query->pluck('id');
        foreach ($ids as $id) {
            Payslip::withTrashed()->find($id)?->restore();
        }

        return $ids->count();
    }

    public function encryptVisiblePayslips(User $employee): array
    {
        $encryptionService = app(PayslipEncryptionService::class);
        $encrypted = 0;
        $failed = 0;

        foreach ($this->unencryptedWithFilePayslips($this->visiblePayslips($employee)) as $payslip) {
            if ($encryptionService->ensureEncrypted($payslip->fresh(), $employee)) {
                $encrypted++;
            } else {
                $failed++;
            }
        }

        return ['encrypted' => $encrypted, 'failed' => $failed];
    }

    private function linkedPayslips(User $employee): Collection
    {
        return Payslip::query()
            ->where('employee_id', $employee->id)
            ->whereNull('deleted_at')
            ->get();
    }

    private function visiblePayslips(User $employee): Collection
    {
        return Payslip::visibleToEmployee($employee)->get();
    }

    private function deletedPayslips(User $employee): Collection
    {
        $normalizedMatricule = User::normalizeMatricule($employee->matricule);

        return Payslip::onlyTrashed()->where(function ($q) use ($employee, $normalizedMatricule) {
            $q->where('employee_id', $employee->id);

            if ($normalizedMatricule !== null && $normalizedMatricule !== '') {
                $q->orWhere(function ($q2) use ($employee, $normalizedMatricule) {
                    if ($employee->company_id) {
                        $q2->where('company_id', $employee->company_id);
                    }
                    $q2->whereRaw('UPPER(TRIM(matricule)) = ?', [$normalizedMatricule]);
                });
            }
        })->get();
    }

    private function duplicateAccounts(User $employee): Collection
    {
        $normalizedMatricule = User::normalizeMatricule($employee->matricule);
        if ($normalizedMatricule === null || $normalizedMatricule === '') {
            return collect();
        }

        $query = User::query()
            ->where('id', '!=', $employee->id)
            ->whereRaw('UPPER(TRIM(matricule)) = ?', [$normalizedMatricule]);

        if ($employee->company_id) {
            $query->where('company_id', $employee->company_id);
        }

        return $query->get(['id', 'email', 'matricule', 'company_id', 'status']);
    }

    private function payslipsOnSiblingAccounts(User $employee, Collection $duplicateAccounts): Collection
    {
        if ($duplicateAccounts->isEmpty()) {
            return collect();
        }

        $normalizedMatricule = User::normalizeMatricule($employee->matricule);
        if ($normalizedMatricule === null || $normalizedMatricule === '') {
            return collect();
        }

        $query = Payslip::query()
            ->whereIn('employee_id', $duplicateAccounts->pluck('id'))
            ->whereNull('deleted_at')
            ->whereRaw('UPPER(TRIM(matricule)) = ?', [$normalizedMatricule]);

        if ($employee->company_id) {
            $query->where('company_id', $employee->company_id);
        }

        return $query->get();
    }

    private function matriculeMismatches(Collection $linkedPayslips, User $employee): Collection
    {
        $normalizedEmployeeMatricule = User::normalizeMatricule($employee->matricule);

        return $linkedPayslips->filter(function (Payslip $payslip) use ($normalizedEmployeeMatricule) {
            if (blank($payslip->matricule) || $normalizedEmployeeMatricule === null || $normalizedEmployeeMatricule === '') {
                return false;
            }

            return User::normalizeMatricule($payslip->matricule) !== $normalizedEmployeeMatricule;
        });
    }

    private function missingFilePayslips(Collection $payslips): Collection
    {
        return $payslips->filter(function (Payslip $payslip) {
            return empty($payslip->file) || !Storage::disk('modified')->exists($payslip->file);
        });
    }

    private function unencryptedWithFilePayslips(Collection $payslips): Collection
    {
        return $payslips->filter(function (Payslip $payslip) {
            return (int) $payslip->encryption_status !== Payslip::STATUS_SUCCESSFUL
                && !empty($payslip->file)
                && Storage::disk('modified')->exists($payslip->file);
        });
    }
}
