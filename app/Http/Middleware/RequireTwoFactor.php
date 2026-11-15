<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\TrustedDeviceService;
use App\Services\TwoFactorService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Holds authenticated sessions at /two-factor/challenge (or /two-factor/setup
 * when policy requires MFA and the user has not enrolled yet) until the
 * second factor has been verified for this session or satisfied by a trusted-device cookie.
 */
class RequireTwoFactor
{
    private const ALWAYS_OPEN = [
        'two-factor',
        'two-factor/*',
        'logout',
        'language/*',
        'webhooks/*',
        'up',
        'build/*',
        'favicon.ico',
        'vendor/*',
        'js/*',
        'css/*',
    ];

    private const LIVEWIRE_COMPONENTS = [
        'auth.two-factor-challenge',
        'auth.two-factor-setup',
    ];

    public function __construct(
        private readonly TwoFactorService $twoFactor,
        private readonly TrustedDeviceService $trustedDevices
    ) {}

    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        if (!$user instanceof User) {
            return $next($request);
        }

        if ($this->isAlwaysOpen($request)) {
            return $next($request);
        }

        if ($this->twoFactor->isVerified($user)) {
            return $next($request);
        }

        $enabled = $this->twoFactor->isEnabledFor($user);
        $required = $this->twoFactor->isRequiredFor($user);

        if (!$enabled && !$required) {
            return $next($request);
        }

        // If the user has enrolled in MFA and presents a valid trusted-device cookie,
        // mark the session verified automatically without prompting for a code.
        if ($enabled && $this->trustedDevices->admit($user, $request)) {
            $this->twoFactor->markVerified($user);
            return $next($request);
        }

        // Allow Livewire AJAX round-trips ONLY for the TwoFactorChallenge / TwoFactorSetup components;
        // block all other Livewire component updates while 2FA is still pending.
        if ($this->isLivewireRequest($request)) {
            if ($this->isTwoFactorLivewireRequest($request)) {
                return $next($request);
            }

            abort(403, __('two_factor.Please complete two-step verification first.'));
        }

        // Remember where the user was trying to go (for GET requests only)
        if ($request->isMethod('GET') && !$request->expectsJson() && !session()->has('url.intended')) {
            session()->put('url.intended', $request->fullUrl());
        }

        $targetRoute = $enabled ? 'two-factor.challenge' : 'two-factor.setup';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('two_factor.Two-step verification required.'),
                'redirect' => route($targetRoute),
            ], 423);
        }

        return redirect()->route($targetRoute);
    }

    private function isAlwaysOpen(Request $request): bool
    {
        foreach (self::ALWAYS_OPEN as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    private function isLivewireRequest(Request $request): bool
    {
        return $request->hasHeader('X-Livewire')
            || $request->is('livewire/*')
            || $request->is('*/livewire/*');
    }

    private function isTwoFactorLivewireRequest(Request $request): bool
    {
        // Livewire 3 payload: components[*].snapshot JSON string with memo.name
        $components = $request->input('components');
        if (is_array($components) && !empty($components)) {
            foreach ($components as $componentPayload) {
                $snapshotRaw = $componentPayload['snapshot'] ?? null;
                if (!is_string($snapshotRaw)) {
                    continue;
                }
                $snapshot = json_decode($snapshotRaw, true);
                $name = (string) ($snapshot['memo']['name'] ?? '');
                if (!in_array($name, self::LIVEWIRE_COMPONENTS, true)) {
                    return false;
                }
            }

            return true;
        }

        // Livewire 2 fallback: fingerprint.name or path suffix
        $component = (string) ($request->input('fingerprint.name')
            ?? $request->route('name')
            ?? '');

        if ($component !== '' && in_array($component, self::LIVEWIRE_COMPONENTS, true)) {
            return true;
        }

        $path = $request->path();
        foreach (self::LIVEWIRE_COMPONENTS as $allowed) {
            if (str_ends_with($path, '/' . $allowed)) {
                return true;
            }
        }

        return false;
    }
}
