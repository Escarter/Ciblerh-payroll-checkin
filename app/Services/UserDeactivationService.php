<?php

namespace App\Services;

use App\Models\User;
use App\Models\Payslip;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class UserDeactivationService
{
    /**
     * Find inactive users based on payslip frequency and threshold
     *
     * @param int $monthsThreshold
     * @return Collection
     */
    public function findInactiveUsers(int $monthsThreshold = 6): Collection
    {
        $cutoffDate = now()->subMonths($monthsThreshold);

        return User::where('status', 1) // Only active users
            ->where(function ($query) use ($cutoffDate) {
                // Users who have no payslips at all
                $query->whereNotExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                        ->from('payslips')
                        ->whereColumn('payslips.user_id', 'users.id');
                })
                // OR users whose last payslip was before the cutoff date
                ->orWhere('last_payslip_received_at', '<', $cutoffDate)
                // OR users who never received a payslip (last_payslip_received_at is null)
                ->orWhereNull('last_payslip_received_at');
            })
            ->get();
    }

    /**
     * Deactivate a collection of users and log the action
     *
     * @param Collection $users
     * @return array
     */
    public function deactivateUsers(Collection $users): array
    {
        $deactivatedCount = 0;
        $failedCount = 0;
        $deactivatedUsers = [];

        foreach ($users as $user) {
            try {
                $user->update([
                    'status' => 0, // Deactivate
                    'deactivated_at' => now(),
                ]);

                // Log the deactivation action
                auditLog(
                    "User Deactivation",
                    "User {$user->id} ({$user->email}) automatically deactivated due to inactivity - no payslip received for {$this->getMonthsWithoutPayslip($user)} months",
                    $user->id,
                    User::class,
                    $user->id,
                    'deactivation'
                );

                $deactivatedUsers[] = [
                    'id' => $user->id,
                    'name' => $user->first_name . ' ' . $user->last_name,
                    'email' => $user->email,
                ];

                $deactivatedCount++;
            } catch (\Exception $e) {
                $failedCount++;
                \Log::error("Failed to deactivate user {$user->id}: " . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'deactivated_count' => $deactivatedCount,
            'failed_count' => $failedCount,
            'deactivated_users' => $deactivatedUsers,
            'executed_at' => now(),
        ];
    }

    /**
     * Get the number of months since user's last payslip
     *
     * @param User $user
     * @return int
     */
    private function getMonthsWithoutPayslip(User $user): int
    {
        if ($user->last_payslip_received_at === null) {
            return 999; // Never received
        }

        return now()->diffInMonths($user->last_payslip_received_at);
    }

    /**
     * Check if a user should be deactivated based on current settings
     *
     * @param User $user
     * @param int $monthsThreshold
     * @return bool
     */
    public function shouldDeactivateUser(User $user, int $monthsThreshold): bool
    {
        // Skip already inactive users
        if ($user->status === 0) {
            return false;
        }

        // Check if user has received payslip within threshold
        if ($user->last_payslip_received_at === null) {
            return true; // Never received any payslip
        }

        return $user->last_payslip_received_at->diffInMonths(now()) >= $monthsThreshold;
    }
}
