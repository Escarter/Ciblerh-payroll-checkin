<?php

namespace App\Livewire\Traits;

use App\Models\User;
use App\Services\TwoFactorService;
use App\Services\TwoFactorSetupService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

/**
 * Shared Livewire actions for setting up or switching a user's two-step verification method.
 * Used by both the standalone TwoFactorSetup screen and the Profile pages.
 */
trait EnrolsTwoFactor
{
    public string $twoFactorChosenMethod = '';
    public string $twoFactorSetupCode = '';
    public string $twoFactorSetupPhone = '';
    public ?string $twoFactorSetupError = null;
    public ?string $twoFactorSetupDestination = null;

    /**
     * Plaintext recovery codes generated during the most recent setup or regeneration.
     *
     * @var array<int, string>
     */
    public array $twoFactorRecoveryCodes = [];

    protected function initTwoFactorEnrolment(): void
    {
        $user = $this->twoFactorUser();
        if (!$user) {
            return;
        }

        $service = app(TwoFactorService::class);
        $this->twoFactorSetupPhone = (string) ($service->defaultPhoneForUser($user) ?: '');

        $flashedCodes = Session::pull(TwoFactorService::SESSION_FLASH_RECOVERY_CODES);
        if (is_array($flashedCodes) && !empty($flashedCodes)) {
            $this->twoFactorRecoveryCodes = $flashedCodes;
        }

        $pending = app(TwoFactorSetupService::class)->pending();
        if ($pending && !empty($pending['method'])) {
            $this->twoFactorChosenMethod = (string) $pending['method'];
            $this->twoFactorSetupDestination = $pending['masked'] ?? null;
        }
    }

    public function selectTwoFactorMethod(string $method): void
    {
        $user = $this->twoFactorUser();
        if (!$user) {
            return;
        }

        $this->twoFactorSetupError = null;
        $this->twoFactorSetupCode = '';
        $this->twoFactorChosenMethod = $method;

        $setup = app(TwoFactorSetupService::class);

        if ($method === TwoFactorService::METHOD_AUTHENTICATOR) {
            $setup->beginAuthenticator($user);
            $this->twoFactorSetupDestination = null;
            return;
        }

        if ($method === TwoFactorService::METHOD_EMAIL) {
            $result = $setup->beginEmail($user);
            $this->twoFactorSetupDestination = $result['masked'];
            if (!$result['ok']) {
                $this->twoFactorSetupError = $result['error'];
            } else {
                $this->notifyTwoFactor(__('two_factor.Verification code sent to :dest', ['dest' => $result['masked']]), 'success');
            }
            return;
        }

        if ($method === TwoFactorService::METHOD_SMS) {
            $service = app(TwoFactorService::class);
            $this->twoFactorSetupPhone = (string) ($service->defaultPhoneForUser($user) ?: '');
            $this->twoFactorSetupDestination = null;
        }
    }

    public function sendTwoFactorSmsSetupCode(): void
    {
        $user = $this->twoFactorUser();
        if (!$user) {
            return;
        }

        $this->twoFactorSetupError = null;
        $result = app(TwoFactorSetupService::class)->beginSms($user, $this->twoFactorSetupPhone);

        if (!$result['ok']) {
            $this->twoFactorSetupError = $result['error'];
            return;
        }

        $this->twoFactorSetupDestination = $result['masked'];
        $this->notifyTwoFactor(__('two_factor.Verification code sent to :dest', ['dest' => $result['masked']]), 'success');
    }

    public function resendTwoFactorSetupCode(): void
    {
        $user = $this->twoFactorUser();
        if (!$user) {
            return;
        }

        $this->twoFactorSetupError = null;
        $result = app(TwoFactorSetupService::class)->resend($user);

        if (!$result['ok']) {
            $this->twoFactorSetupError = $result['error'];
            return;
        }

        $this->twoFactorSetupDestination = $result['masked'];
        $this->notifyTwoFactor(__('two_factor.A new verification code has been sent.'), 'success');
    }

    public function confirmTwoFactorSetup(): void
    {
        $user = $this->twoFactorUser();
        if (!$user) {
            return;
        }

        $this->twoFactorSetupError = null;
        $result = app(TwoFactorSetupService::class)->confirm($user, $this->twoFactorSetupCode);

        if (!$result['ok']) {
            $this->twoFactorSetupError = $result['error'];
            return;
        }

        // Pull from session so the codes stay bound to this component state
        Session::forget(TwoFactorService::SESSION_FLASH_RECOVERY_CODES);
        $this->twoFactorRecoveryCodes = $result['recovery_codes'] ?? [];

        $this->twoFactorChosenMethod = '';
        $this->twoFactorSetupCode = '';
        $this->twoFactorSetupDestination = null;

        $this->onTwoFactorConfirmed($user->fresh());
    }

    public function dismissRecoveryCodes(): void
    {
        $this->twoFactorRecoveryCodes = [];
        Session::forget(TwoFactorService::SESSION_FLASH_RECOVERY_CODES);
    }

    public function cancelTwoFactorSetup(): void
    {
        app(TwoFactorSetupService::class)->cancel();
        $this->twoFactorChosenMethod = '';
        $this->twoFactorSetupCode = '';
        $this->twoFactorSetupError = null;
        $this->twoFactorSetupDestination = null;
    }

    protected function twoFactorUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    protected function notifyTwoFactor(string $message, string $type = 'success'): void
    {
        if (method_exists($this, 'showToast')) {
            $this->showToast($message, $type);
        } else {
            $this->dispatch('showToast', message: $message, type: $type);
        }
    }

    protected function onTwoFactorConfirmed(User $user): void
    {
        $this->notifyTwoFactor(__('two_factor.Two-step verification is now active on your account.'), 'success');
    }
}
