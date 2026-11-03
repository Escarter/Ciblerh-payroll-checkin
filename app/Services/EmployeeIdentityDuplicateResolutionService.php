<?php

namespace App\Services;

use App\Models\Payslip;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeIdentityDuplicateResolutionService
{
    public function __construct(
        private EmployeeIdentityDuplicateReportService $reportService,
        private PayslipEmployeeDiagnosticService $payslipDiagnosticService,
    ) {}

    /**
     * @return array{
     *     groups_processed: int,
     *     canonical_users: array<int, array<string, mixed>>,
     *     archived_users: array<int, array<string, mixed>>,
     *     payslips_relinked: int,
     *     dry_run: bool
     * }
     */
    public function resolve(bool $dryRun = false, bool $deactivateDuplicates = true): array
    {
        $report = $this->reportService->report();
        $groups = collect($report['duplicate_matricules']);

        if ($groups->isEmpty()) {
            return [
                'groups_processed' => 0,
                'canonical_users' => [],
                'archived_users' => [],
                'payslips_relinked' => 0,
                'dry_run' => $dryRun,
            ];
        }

        $canonicalUsers = [];
        $archivedUsers = [];
        $payslipsRelinked = 0;

        foreach ($groups as $group) {
            $users = User::query()
                ->whereIn('id', collect($group['users'])->pluck('id'))
                ->get();

            $canonical = $this->pickCanonicalUser($users);
            $duplicates = $users->where('id', '!=', $canonical->id)->values();

            $canonicalUsers[] = $this->userSummary($canonical);

            if ($dryRun) {
                $relinkCount = $this->countRelinkablePayslips($canonical, $duplicates);
                $payslipsRelinked += $relinkCount;

                foreach ($duplicates as $duplicate) {
                    $archivedUsers[] = array_merge($this->userSummary($duplicate), [
                        'new_matricule' => $this->archivedMatricule($duplicate),
                        'would_deactivate' => $deactivateDuplicates,
                    ]);
                }

                continue;
            }

            DB::transaction(function () use ($canonical, $duplicates, $deactivateDuplicates, &$payslipsRelinked, &$archivedUsers) {
                $relinkResult = $this->payslipDiagnosticService->relinkPayslipsToEmployee($canonical);
                $payslipsRelinked += $relinkResult['relinked'];

                foreach ($duplicates as $duplicate) {
                    $archivedMatricule = $this->archivedMatricule($duplicate);

                    $duplicate->update([
                        'matricule' => $archivedMatricule,
                        'status' => $deactivateDuplicates ? User::STATUS_BANNED : $duplicate->status,
                    ]);

                    $archivedUsers[] = array_merge($this->userSummary($duplicate->fresh()), [
                        'new_matricule' => $archivedMatricule,
                        'deactivated' => $deactivateDuplicates,
                    ]);

                    Log::info('Archived duplicate employee matricule', [
                        'canonical_user_id' => $canonical->id,
                        'duplicate_user_id' => $duplicate->id,
                        'original_matricule' => $canonical->matricule,
                        'archived_matricule' => $archivedMatricule,
                    ]);
                }
            });
        }

        return [
            'groups_processed' => $groups->count(),
            'canonical_users' => $canonicalUsers,
            'archived_users' => $archivedUsers,
            'payslips_relinked' => $payslipsRelinked,
            'dry_run' => $dryRun,
        ];
    }

    public function pickCanonicalUser(Collection $users): User
    {
        return $users->sort(function (User $a, User $b) {
            $payslipsA = $this->activePayslipCount($a);
            $payslipsB = $this->activePayslipCount($b);

            if ($payslipsA !== $payslipsB) {
                return $payslipsB <=> $payslipsA;
            }

            if ((int) $a->status !== (int) $b->status) {
                return (int) $b->status <=> (int) $a->status;
            }

            return $a->id <=> $b->id;
        })->first();
    }

    public function archivedMatricule(User $user): string
    {
        $base = User::normalizeMatricule($user->matricule) ?: 'UNKNOWN';

        return $base . '_DUP_' . $user->id;
    }

    private function activePayslipCount(User $user): int
    {
        return Payslip::query()
            ->where('employee_id', $user->id)
            ->whereNull('deleted_at')
            ->count();
    }

    private function countRelinkablePayslips(User $canonical, Collection $duplicates): int
    {
        $normalizedMatricule = User::normalizeMatricule($canonical->matricule);
        if ($normalizedMatricule === null || $normalizedMatricule === '') {
            return 0;
        }

        return Payslip::query()
            ->whereIn('employee_id', $duplicates->pluck('id'))
            ->whereNull('deleted_at')
            ->whereRaw('UPPER(TRIM(matricule)) = ?', [$normalizedMatricule])
            ->when($canonical->company_id, fn ($query) => $query->where('company_id', $canonical->company_id))
            ->count();
    }

    private function userSummary(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => trim($user->first_name . ' ' . $user->last_name),
            'email' => $user->getRawOriginal('email') ?? $user->email,
            'matricule' => $user->matricule,
            'company_id' => $user->company_id,
            'active_payslips' => $this->activePayslipCount($user),
            'status' => (int) $user->status === User::STATUS_ACTIVE ? 'active' : 'inactive',
        ];
    }
}
