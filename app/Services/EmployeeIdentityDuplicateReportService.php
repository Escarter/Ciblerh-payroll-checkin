<?php

namespace App\Services;

use App\Models\Payslip;
use App\Models\User;
use Illuminate\Support\Collection;

class EmployeeIdentityDuplicateReportService
{
    public function report(bool $includeTrashed = false): array
    {
        $users = $this->loadUsers($includeTrashed);

        $duplicateMatricules = $this->findDuplicateMatricules($users);
        $duplicateEmailLocalParts = $this->findDuplicateEmailLocalParts($users);

        $matriculeUserIds = $duplicateMatricules
            ->flatMap(fn (array $group) => collect($group['users'])->pluck('id'))
            ->unique()
            ->count();

        $emailLocalPartUserIds = $duplicateEmailLocalParts
            ->flatMap(fn (array $group) => collect($group['users'])->pluck('id'))
            ->unique()
            ->count();

        return [
            'duplicate_matricules' => $duplicateMatricules->values()->all(),
            'duplicate_email_local_parts' => $duplicateEmailLocalParts->values()->all(),
            'summary' => [
                'users_scanned' => $users->count(),
                'duplicate_matricule_groups' => $duplicateMatricules->count(),
                'duplicate_email_local_part_groups' => $duplicateEmailLocalParts->count(),
                'users_in_matricule_conflicts' => $matriculeUserIds,
                'users_in_email_local_part_conflicts' => $emailLocalPartUserIds,
            ],
            'migration_ready' => $duplicateMatricules->isEmpty(),
        ];
    }

    private function loadUsers(bool $includeTrashed): Collection
    {
        $query = User::query()
            ->select(['id', 'email', 'matricule', 'company_id', 'status', 'deleted_at', 'first_name', 'last_name'])
            ->with(['company:id,name']);

        if ($includeTrashed) {
            $query->withTrashed();
        }

        return $query->get();
    }

    private function findDuplicateMatricules(Collection $users): Collection
    {
        return $users
            ->filter(fn (User $user) => filled($user->matricule))
            ->groupBy(fn (User $user) => User::normalizeMatricule($user->matricule))
            ->filter(fn (Collection $group) => $group->count() > 1)
            ->map(function (Collection $group, string $matricule) {
                return [
                    'matricule' => $matricule,
                    'users' => $group->map(fn (User $user) => $this->userSnapshot($user))->values()->all(),
                ];
            });
    }

    private function findDuplicateEmailLocalParts(Collection $users): Collection
    {
        return $users
            ->filter(fn (User $user) => filled($user->email) && str_contains($user->email, '@'))
            ->groupBy(fn (User $user) => User::normalizeEmailLocalPart($user->email))
            ->filter(fn (Collection $group, ?string $localPart) => filled($localPart) && $group->count() > 1)
            ->filter(function (Collection $group) {
                $emails = $group->map(
                    fn (User $user) => strtolower(trim((string) $user->getRawOriginal('email')))
                )->unique();

                return $emails->count() > 1;
            })
            ->map(function (Collection $group, string $localPart) {
                return [
                    'local_part' => $localPart,
                    'users' => $group->map(fn (User $user) => $this->userSnapshot($user))->values()->all(),
                ];
            });
    }

    private function userSnapshot(User $user): array
    {
        $activePayslips = Payslip::query()
            ->where('employee_id', $user->id)
            ->whereNull('deleted_at')
            ->count();

        return [
            'id' => $user->id,
            'name' => trim($user->first_name . ' ' . $user->last_name),
            'email' => $user->getRawOriginal('email') ?? $user->email,
            'matricule' => $user->matricule,
            'company' => $user->company?->name,
            'company_id' => $user->company_id,
            'status' => (int) $user->status === User::STATUS_ACTIVE ? 'active' : 'inactive',
            'deleted' => $user->trashed(),
            'active_payslips' => $activePayslips,
        ];
    }
}
