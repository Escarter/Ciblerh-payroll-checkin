<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\TrustedDeviceService;
use App\Services\TwoFactorService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TwoFactorChallenge extends Component
{
    public string $code = '';
    public string $recoveryCode = '';
    public bool $useRecoveryCode = false;
    public bool $trustDevice = false;
    public ?string $errorMessage = null;
    public ?string $maskedDestination = null;

    public function mount(TwoFactorService $twoFactor)
    {
        $user = Auth::user();
        if (!$user instanceof User) {
            return redirect()->route('login');
        }

        if ($twoFactor->isVerified($user)) {
            return redirect()->to($this->defaultRedirectUrl($user));
        }

        if (!$twoFactor->isEnabledFor($user)) {
            if ($twoFactor->isRequiredFor($user)) {
                return redirect()->route('two-factor.setup');
            }
            return redirect()->to($this->defaultRedirectUrl($user));
        }

        $sent = $twoFactor->ensureChallengeCodeSent($user);
        if (is_array($sent)) {
            $this->maskedDestination = $sent['masked'] ?? null;
            if (!$sent['sent'] && !empty($sent['error']) && ($sent['cooldown'] ?? 0) <= 0) {
                $this->errorMessage = $sent['error'];
            }
        }
    }

    public function toggleRecoveryMode(): void
    {
        $this->useRecoveryCode = !$this->useRecoveryCode;
        $this->errorMessage = null;
        $this->code = '';
        $this->recoveryCode = '';
    }

    public function verify(TwoFactorService $twoFactor, TrustedDeviceService $trustedDevices)
    {
        $user = Auth::user();
        if (!$user instanceof User) {
            return redirect()->route('login');
        }

        $this->errorMessage = null;

        $result = $this->useRecoveryCode
            ? $twoFactor->verifyRecoveryCode($user, $this->recoveryCode)
            : $twoFactor->verifyChallenge($user, $this->code);

        if (!$result['ok']) {
            $this->errorMessage = $result['error'];
            $this->code = '';
            $this->recoveryCode = '';
            return null;
        }

        if ($this->trustDevice && $trustedDevices->isAllowed()) {
            $trustedDevices->trust($user, request());
        }

        $target = session()->pull('url.intended') ?: $this->defaultRedirectUrl($user);

        return redirect()->to($target);
    }

    public function resend(TwoFactorService $twoFactor): void
    {
        $user = Auth::user();
        if (!$user instanceof User) {
            return;
        }

        if (!in_array($user->mfa_method, [TwoFactorService::METHOD_EMAIL, TwoFactorService::METHOD_SMS], true)) {
            return;
        }

        $this->errorMessage = null;
        $result = $twoFactor->sendCode($user, $user->mfa_method, 'challenge');

        if (!$result['sent']) {
            $this->errorMessage = $result['error'];
            return;
        }

        $this->maskedDestination = $result['masked'];
        $this->dispatch('showToast', message: __('two_factor.A new verification code has been sent.'), type: 'success');
    }

    private function defaultRedirectUrl(User $user): string
    {
        if ($user->isAdminUser()) {
            return route('portal.dashboard');
        }

        if ($user->hasRole('employee')) {
            return route('employee.dashboard');
        }

        return route('portal.dashboard');
    }

    public function render(TwoFactorService $twoFactor, TrustedDeviceService $trustedDevices)
    {
        $user = Auth::user();
        $method = $user instanceof User ? (string) $user->mfa_method : TwoFactorService::METHOD_AUTHENTICATOR;
        $cooldown = $user instanceof User ? $twoFactor->resendWaitSeconds($user, 'challenge') : 0;
        $lockedSeconds = $user instanceof User ? $twoFactor->lockoutSeconds($user) : 0;

        return view('livewire.auth.two-factor-challenge', [
            'user' => $user,
            'method' => $method,
            'cooldown' => $cooldown,
            'lockedSeconds' => $lockedSeconds,
            'rememberDays' => $trustedDevices->rememberDays(),
        ]);
    }
}
