<?php

namespace App\Livewire\Traits;

use App\Models\User;
use App\Services\TwoFactorSetupService;

trait WithAdminTwoFactorActions
{
    public function toggleUserMfaRequired(int $userId): void
    {
        if (method_exists($this, 'authorizeAbility')) {
            $this->authorizeAbility('employee-update');
        }

        $target = User::findOrFail($userId);
        $newState = !$target->mfa_required;

        app(TwoFactorSetupService::class)->setRequired($target, $newState, auth()->user());

        $message = $newState
            ? __('two_factor.admin_mfa_required_enabled_for', ['user' => $target->name])
            : __('two_factor.admin_mfa_required_disabled_for', ['user' => $target->name]);

        if (method_exists($this, 'showToast')) {
            $this->showToast($message, 'info');
        } else {
            $this->dispatch('showToast', message: $message, type: 'info');
        }
    }

    public function resetUserMfa(int $userId): void
    {
        if (method_exists($this, 'authorizeAbility')) {
            $this->authorizeAbility('employee-update');
        }

        $target = User::findOrFail($userId);
        app(TwoFactorSetupService::class)->resetFor($target, auth()->user());

        $message = __('two_factor.admin_mfa_reset_for', ['user' => $target->name]);

        if (method_exists($this, 'showToast')) {
            $this->showToast($message, 'warning');
        } else {
            $this->dispatch('showToast', message: $message, type: 'warning');
        }
    }
}
