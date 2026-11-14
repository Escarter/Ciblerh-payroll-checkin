<?php

namespace App\Livewire\Auth;

use App\Livewire\Traits\EnrolsTwoFactor;
use App\Models\User;
use App\Services\TwoFactorService;
use App\Services\TwoFactorSetupService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TwoFactorSetup extends Component
{
    use EnrolsTwoFactor;

    public bool $setupCompleted = false;

    public function mount(TwoFactorService $twoFactor)
    {
        $user = Auth::user();
        if (!$user instanceof User) {
            return redirect()->route('login');
        }

        $this->initTwoFactorEnrolment();

        // Pre-select the first ready method so the user can finish in one step
        if ($this->twoFactorChosenMethod === '') {
            $methods = $twoFactor->allowedMethods($user);
            foreach ($methods as $key => $info) {
                if ($info['ready']) {
                    if ($key === TwoFactorService::METHOD_AUTHENTICATOR) {
                        $this->selectTwoFactorMethod($key);
                    } else {
                        $this->twoFactorChosenMethod = $key;
                    }
                    break;
                }
            }
        }
    }

    protected function onTwoFactorConfirmed(User $user): void
    {
        $this->setupCompleted = true;
        $this->dispatch('showToast', message: __('two_factor.Two-step verification is now active on your account.'), type: 'success');
    }

    public function finishSetup()
    {
        $user = Auth::user();
        $this->dismissRecoveryCodes();

        if (!$user instanceof User) {
            return redirect()->route('login');
        }

        $target = session()->pull('url.intended') ?: $this->defaultRedirectUrl($user);

        return redirect()->to($target);
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

    public function render(TwoFactorService $twoFactor, TwoFactorSetupService $setup)
    {
        $user = Auth::user();
        $methods = $user instanceof User ? $twoFactor->allowedMethods($user) : [];
        $required = $user instanceof User ? $twoFactor->isRequiredFor($user) : false;

        $authenticator = ($user instanceof User && $this->twoFactorChosenMethod === TwoFactorService::METHOD_AUTHENTICATOR)
            ? $setup->pendingAuthenticator($user)
            : null;

        return view('livewire.auth.two-factor-setup', [
            'user' => $user,
            'methods' => $methods,
            'required' => $required,
            'authenticator' => $authenticator,
        ]);
    }
}
