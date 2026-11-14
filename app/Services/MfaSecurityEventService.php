<?php

namespace App\Services;

use App\Models\MfaSecurityEvent;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class MfaSecurityEventService
{
    public const EVENT_CHALLENGE_PASSED = 'challenge_passed';
    public const EVENT_CHALLENGE_FAILED = 'challenge_failed';
    public const EVENT_CHALLENGE_LOCKED = 'challenge_locked';
    public const EVENT_RECOVERY_USED = 'recovery_used';
    public const EVENT_RECOVERY_FAILED = 'recovery_failed';
    public const EVENT_RECOVERY_REGENERATED = 'recovery_regenerated';
    public const EVENT_TRUSTED_DEVICE_ADDED = 'trusted_device_added';
    public const EVENT_TRUSTED_DEVICE_USED = 'trusted_device_used';
    public const EVENT_TRUSTED_DEVICE_REVOKED = 'trusted_device_revoked';
    public const EVENT_MFA_ENABLED = 'mfa_enabled';
    public const EVENT_MFA_METHOD_CHANGED = 'mfa_method_changed';
    public const EVENT_MFA_DISABLED = 'mfa_disabled';
    public const EVENT_MFA_RESET_BY_ADMIN = 'mfa_reset_by_admin';
    public const EVENT_MFA_REQUIRED_CHANGED = 'mfa_required_changed';
    public const EVENT_MFA_TEST_PASSED = 'mfa_test_passed';
    public const EVENT_MFA_TEST_FAILED = 'mfa_test_failed';

    public function log(
        User $user,
        string $event,
        string $status = 'success',
        ?string $method = null,
        ?string $reason = null,
        array $metadata = [],
        ?int $actorId = null
    ): ?MfaSecurityEvent {
        try {
            $request = request();

            return MfaSecurityEvent::create([
                'user_id' => $user->id,
                'actor_id' => $actorId ?? auth()->id(),
                'event' => $event,
                'method' => $method ?? $user->mfa_method,
                'status' => $status,
                'reason' => $reason,
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? substr((string) $request->userAgent(), 0, 500) : null,
                'metadata' => $metadata ?: null,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    public function recentForUser(User $user, int $limit = 15): Collection
    {
        return MfaSecurityEvent::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function searchForUser(
        User $user,
        string $search = '',
        int $perPage = 10,
        string $pageName = 'mfaPage'
    ): LengthAwarePaginator {
        $term = trim($search);

        return MfaSecurityEvent::where('user_id', $user->id)
            ->when($term !== '', function ($query) use ($term) {
                $like = '%' . $term . '%';
                $query->where(function ($sub) use ($like) {
                    $sub->where('event', 'like', $like)
                        ->orWhere('method', 'like', $like)
                        ->orWhere('status', 'like', $like)
                        ->orWhere('reason', 'like', $like)
                        ->orWhere('ip_address', 'like', $like)
                        ->orWhere('user_agent', 'like', $like);
                });
            })
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], $pageName);
    }

    public function searchAll(
        string $eventType = '',
        string $search = '',
        int $perPage = 15,
        string $pageName = 'mfaEventsPage'
    ): LengthAwarePaginator {
        $term = trim($search);
        $eventFilter = trim($eventType);

        return MfaSecurityEvent::with(['user', 'actor'])
            ->when($eventFilter !== '', fn($q) => $q->where('event', $eventFilter))
            ->when($term !== '', function ($query) use ($term) {
                $like = '%' . $term . '%';
                $query->where(function ($sub) use ($like) {
                    $sub->where('event', 'like', $like)
                        ->orWhere('method', 'like', $like)
                        ->orWhere('status', 'like', $like)
                        ->orWhere('reason', 'like', $like)
                        ->orWhere('ip_address', 'like', $like)
                        ->orWhereHas('user', function ($uq) use ($like) {
                            $uq->where('first_name', 'like', $like)
                                ->orWhere('last_name', 'like', $like)
                                ->orWhere('email', 'like', $like);
                        });
                });
            })
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], $pageName);
    }
}
