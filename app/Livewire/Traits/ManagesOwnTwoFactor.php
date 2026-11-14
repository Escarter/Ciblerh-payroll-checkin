<?php

namespace App\Livewire\Traits;

use App\Models\User;
use App\Services\MfaRecoveryCodeService;
use App\Services\MfaSecurityEventService;
use App\Services\TrustedDeviceService;
use App\Services\TwoFactorService;
use App\Services\TwoFactorSetupService;
use Illuminate\Support\Facades\Session;

/**
 * Adds self-service two-step verification controls (setup, switch method, test code,
 * recovery codes, trusted devices, security event history, disable) to Profile components.
 */
trait ManagesOwnTwoFactor
{
    use EnrolsTwoFactor;

    public bool $twoFactorTesting = false;
    public string $twoFactorTestCode = '';
    public ?string $twoFactorTestMasked = null;
    public ?string $twoFactorTestError = null;
    public string $twoFactorEventSearch = '';

    public function updatedTwoFactorEventSearch(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage('mfaPage');
        }
    }

    public function startTwoFactorTest(): void
    {
        $user = $this->twoFactorUser();
        if (!$user) {
            return;
        }

        $this->twoFactorTestError = null;
        $this->twoFactorTestCode = '';

        $result = app(TwoFactorService::class)->startTest($user);
        if (!$result['ok']) {
            $this->twoFactorTestError = $result['error'];
            return;
        }

        $this->twoFactorTesting = true;
        $this->twoFactorTestMasked = $result['masked'];

        if ($result['masked']) {
            $this->notifyTwoFactor(__('two_factor.Test code sent to :dest', ['dest' => $result['masked']]), 'success');
        }
    }

    public function verifyTwoFactorTest(): void
    {
        $user = $this->twoFactorUser();
        if (!$user) {
            return;
        }

        $this->twoFactorTestError = null;
        $result = app(TwoFactorService::class)->verifyTest($user, $this->twoFactorTestCode);

        if (!$result['ok']) {
            $this->twoFactorTestError = $result['error'];
            return;
        }

        $this->twoFactorTesting = false;
        $this->twoFactorTestCode = '';
        $this->twoFactorTestMasked = null;
        $this->notifyTwoFactor(__('two_factor.Great! Your two-step verification works as expected.'), 'success');
    }

    public function cancelTwoFactorTest(): void
    {
        app(TwoFactorService::class)->cancelTest();
        $this->twoFactorTesting = false;
        $this->twoFactorTestCode = '';
        $this->twoFactorTestMasked = null;
        $this->twoFactorTestError = null;
    }

    public function regenerateTwoFactorRecoveryCodes(): void
    {
        $user = $this->twoFactorUser();
        if (!$user) {
            return;
        }

        $codes = app(TwoFactorSetupService::class)->regenerateRecoveryCodes($user);
        Session::forget(TwoFactorService::SESSION_FLASH_RECOVERY_CODES);
        $this->twoFactorRecoveryCodes = $codes;

        $this->notifyTwoFactor(__('two_factor.New recovery codes generated. Save them in a safe place.'), 'success');
    }

    public function revokeTrustedDevice(int $deviceId): void
    {
        $user = $this->twoFactorUser();
        if (!$user) {
            return;
        }

        if (app(TrustedDeviceService::class)->forget($user, $deviceId)) {
            $this->notifyTwoFactor(__('two_factor.Trusted device removed.'), 'info');
        }
    }

    public function revokeAllTrustedDevices(): void
    {
        $user = $this->twoFactorUser();
        if (!$user) {
            return;
        }

        app(TrustedDeviceService::class)->forgetAll($user);
        $this->notifyTwoFactor(__('two_factor.All trusted devices have been removed.'), 'info');
    }

    public function disableTwoFactor(): void
    {
        $user = $this->twoFactorUser();
        if (!$user) {
            return;
        }

        $result = app(TwoFactorSetupService::class)->disable($user);
        if (!$result['ok']) {
            $this->notifyTwoFactor((string) $result['error'], 'error');
            return;
        }

        $this->cancelTwoFactorSetup();
        $this->cancelTwoFactorTest();
        $this->twoFactorRecoveryCodes = [];
        $this->notifyTwoFactor(__('two_factor.Two-step verification has been turned off.'), 'info');
    }

    /**
     * Build the view data array needed by the shared two-factor management partial.
     */
    protected function twoFactorViewData(): array
    {
        $user = $this->twoFactorUser()?->fresh();
        $service = app(TwoFactorService::class);
        $setup = app(TwoFactorSetupService::class);

        if (!$user instanceof User) {
            return [
                'mfaUser' => null,
                'mfaEnabled' => false,
                'mfaRequired' => false,
                'mfaMethods' => [],
                'mfaAuthenticator' => null,
                'mfaRecoveryRemaining' => 0,
                'mfaTrustedDevices' => collect(),
                'mfaSecurityEvents' => collect(),
            ];
        }

        $enabled = $service->isEnabledFor($user);

        return [
            'mfaUser' => $user,
            'mfaEnabled' => $enabled,
            'mfaRequired' => $service->isRequiredFor($user),
            'mfaMethods' => $service->allowedMethods($user),
            'mfaAuthenticator' => $this->twoFactorChosenMethod === TwoFactorService::METHOD_AUTHENTICATOR
                ? $setup->pendingAuthenticator($user)
                : null,
            'mfaRecoveryRemaining' => $enabled ? app(MfaRecoveryCodeService::class)->remaining($user) : 0,
            'mfaTrustedDevices' => $enabled ? app(TrustedDeviceService::class)->listFor($user) : collect(),
            'mfaSecurityEvents' => app(MfaSecurityEventService::class)->searchForUser($user, $this->twoFactorEventSearch, 8),
        ];
    }
}
