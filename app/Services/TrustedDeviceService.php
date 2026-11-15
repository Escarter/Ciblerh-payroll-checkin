<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Manages "Trust this browser for N days" cookies backed by server-side SHA-256 token hashes.
 *
 * Cookie format: "<deviceId>|<plainToken>"
 * Only the SHA-256 hash of <plainToken> is stored in `mfa_trusted_devices.token_hash`.
 */
class TrustedDeviceService
{
    public const COOKIE_PREFIX = 'ciblerh_mfa_trust_';
    public const DEFAULT_DAYS = 30;

    public function __construct(
        private readonly MfaSecurityEventService $events
    ) {}

    public function rememberDays(): int
    {
        try {
            $setting = Setting::first();
            $days = (int) ($setting?->mfa_remember_days ?? self::DEFAULT_DAYS);
            return max(0, min(180, $days));
        } catch (\Throwable) {
            return self::DEFAULT_DAYS;
        }
    }

    public function isAllowed(): bool
    {
        return $this->rememberDays() > 0;
    }

    /**
     * Issue a new trusted-device record and queue the browser cookie.
     */
    public function trust(User $user, Request $request): ?TrustedDevice
    {
        $days = $this->rememberDays();
        if ($days <= 0) {
            return null;
        }

        // Purge expired records for this user
        TrustedDevice::where('user_id', $user->id)
            ->where('expires_at', '<=', now())
            ->delete();

        $plainToken = Str::random(64);
        $expiresAt = now()->addDays($days);

        $device = TrustedDevice::create([
            'user_id' => $user->id,
            'name' => $this->describeUserAgent((string) $request->userAgent()),
            'token_hash' => $this->hash($plainToken),
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'last_used_at' => now(),
            'expires_at' => $expiresAt,
        ]);

        $cookieValue = $device->id . '|' . $plainToken;
        $minutes = $days * 24 * 60;

        Cookie::queue(Cookie::make(
            $this->cookieName($user),
            $cookieValue,
            $minutes,
            config('session.path', '/'),
            config('session.domain'),
            (bool) config('session.secure', false),
            true,
            false,
            config('session.same_site', 'lax')
        ));

        $this->events->log(
            $user,
            MfaSecurityEventService::EVENT_TRUSTED_DEVICE_ADDED,
            'success',
            $user->mfa_method,
            null,
            ['device_id' => $device->id, 'device_name' => $device->name, 'days' => $days]
        );

        return $device;
    }

    /**
     * Check whether the current request carries a valid trusted-device cookie for the user.
     */
    public function admit(User $user, Request $request): bool
    {
        if (!$this->isAllowed()) {
            return false;
        }

        $raw = (string) $request->cookie($this->cookieName($user), '');
        if ($raw === '' || !str_contains($raw, '|')) {
            return false;
        }

        [$deviceId, $plainToken] = explode('|', $raw, 2);
        if (!ctype_digit((string) $deviceId) || strlen($plainToken) < 32) {
            return false;
        }

        $device = TrustedDevice::where('id', (int) $deviceId)
            ->where('user_id', $user->id)
            ->first();

        if (!$device || $device->isExpired()) {
            if ($device) {
                $device->delete();
            }
            Cookie::queue(Cookie::forget($this->cookieName($user)));
            return false;
        }

        if (!hash_equals($device->token_hash, $this->hash($plainToken))) {
            return false;
        }

        $device->forceFill([
            'last_used_at' => now(),
            'ip_address' => $request->ip(),
        ])->save();

        $this->events->log(
            $user,
            MfaSecurityEventService::EVENT_TRUSTED_DEVICE_USED,
            'success',
            $user->mfa_method,
            null,
            ['device_id' => $device->id, 'device_name' => $device->name]
        );

        return true;
    }

    /**
     * List active (unexpired) trusted devices for a user.
     */
    public function listFor(User $user): Collection
    {
        return TrustedDevice::where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->orderByDesc('last_used_at')
            ->get();
    }

    public function forget(User $user, int|string $deviceId): bool
    {
        $deleted = TrustedDevice::where('user_id', $user->id)
            ->where('id', (int) $deviceId)
            ->delete();

        if ($deleted > 0) {
            $this->events->log(
                $user,
                MfaSecurityEventService::EVENT_TRUSTED_DEVICE_REVOKED,
                'success',
                $user->mfa_method,
                null,
                ['device_id' => $deviceId]
            );
        }

        return $deleted > 0;
    }

    public function forgetAll(User $user): void
    {
        TrustedDevice::where('user_id', $user->id)->delete();
        Cookie::queue(Cookie::forget($this->cookieName($user)));
    }

    public function cookieName(User $user): string
    {
        return self::COOKIE_PREFIX . sha1((string) $user->id);
    }

    private function hash(string $plainToken): string
    {
        $key = (string) config('app.key', 'ciblerh-mfa-trust');

        return hash_hmac('sha256', $plainToken, $key);
    }

    public function describeUserAgent(string $ua): string
    {
        if ($ua === '') {
            return __('two_factor.unknown_browser');
        }

        $browser = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') || str_contains($ua, 'Opera') => 'Opera',
            str_contains($ua, 'Chrome/') => 'Chrome',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Safari/') => 'Safari',
            default => __('two_factor.browser'),
        };

        $os = match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Macintosh') || str_contains($ua, 'Mac OS') => 'macOS',
            str_contains($ua, 'iPhone') || str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Linux') => 'Linux',
            default => __('two_factor.device'),
        };

        return "{$browser} · {$os}";
    }
}
