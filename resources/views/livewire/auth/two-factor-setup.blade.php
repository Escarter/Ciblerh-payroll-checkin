<main>
    <x-auth-shell
        :title="__('two_factor.setup_page_title')"
        :subtitle="$required ? __('two_factor.setup_required_subtitle') : __('two_factor.setup_optional_subtitle')"
        :wide="true"
    >
        @if($required)
            <div class="alert alert-warning py-2 px-3 small mb-3">
                <strong>{{ __('two_factor.required_badge') }}:</strong>
                {{ __('two_factor.setup_required_notice') }}
            </div>
        @endif

        @if(!empty($twoFactorRecoveryCodes))
            @include('livewire.partials.two-factor.recovery-codes', [
                'codes' => $twoFactorRecoveryCodes,
                'finishAction' => 'finishSetup',
            ])
        @else
            @include('livewire.partials.two-factor.enrol', [
                'methods' => $methods,
                'chosenMethod' => $twoFactorChosenMethod,
                'authenticator' => $authenticator,
                'setupDestination' => $twoFactorSetupDestination,
                'setupError' => $twoFactorSetupError,
                'showCancel' => false,
            ])
        @endif

        <div class="mt-4 pt-3 border-top d-flex align-items-center justify-content-between small text-muted">
            <span>{{ __('two_factor.signed_in_as', ['name' => $user?->name ?? $user?->email]) }}</span>
            <div class="d-flex align-items-center gap-3">
                @if(!$required && empty($twoFactorRecoveryCodes))
                    <button type="button" wire:click="finishSetup" class="btn btn-link btn-sm p-0 text-decoration-none">
                        {{ __('two_factor.skip_for_now') }}
                    </button>
                @endif
                <a href="{{ url('/logout') }}" class="rf-link">
                    {{ __('two_factor.sign_out') }}
                </a>
            </div>
        </div>
    </x-auth-shell>
</main>
