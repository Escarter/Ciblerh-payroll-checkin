<main>
    <x-auth-shell
        :title="__('two_factor.challenge_title')"
        :subtitle="$useRecoveryCode
            ? __('two_factor.challenge_subtitle_recovery')
            : ($method === 'authenticator'
                ? __('two_factor.challenge_subtitle_authenticator')
                : ($method === 'sms'
                    ? __('two_factor.challenge_subtitle_sms', ['dest' => $maskedDestination ?: __('two_factor.your_phone')])
                    : __('two_factor.challenge_subtitle_email', ['dest' => $maskedDestination ?: ($user?->email ?? '')])))"
    >
        @if($errorMessage)
            <div class="rf-alert rf-alert-danger mb-3" role="alert">
                {{ $errorMessage }}
            </div>
        @endif

        <form wire:submit.prevent="verify" class="rf-form">
            @if(!$useRecoveryCode)
                <div class="rf-field">
                    <label for="twoFactorCode" class="rf-label">
                        {{ __('two_factor.code_label') }}
                    </label>
                    <input
                        id="twoFactorCode"
                        type="text"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        maxlength="6"
                        autofocus
                        wire:model="code"
                        placeholder="000000"
                        class="rf-input text-center fw-bold fs-4"
                        style="letter-spacing: 0.35em; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;"
                        @if($lockedSeconds > 0) disabled @endif
                    >
                </div>
            @else
                <div class="rf-field">
                    <label for="twoFactorRecoveryCode" class="rf-label">
                        {{ __('two_factor.recovery_code_label') }}
                    </label>
                    <input
                        id="twoFactorRecoveryCode"
                        type="text"
                        autocomplete="off"
                        maxlength="14"
                        autofocus
                        wire:model="recoveryCode"
                        placeholder="XXXXX-XXXXX"
                        class="rf-input text-center fw-bold fs-5 text-uppercase"
                        style="letter-spacing: 0.15em; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;"
                        @if($lockedSeconds > 0) disabled @endif
                    >
                    <small class="text-muted d-block mt-1">
                        {{ __('two_factor.recovery_code_hint') }}
                    </small>
                </div>
            @endif

            @if(($rememberDays ?? 0) > 0)
                <div class="rf-row mt-2">
                    <label class="rf-check">
                        <input id="trustDevice" type="checkbox" wire:model="trustDevice">
                        <span>{{ __('two_factor.trust_this_device_days', ['days' => $rememberDays]) }}</span>
                    </label>
                </div>
            @endif

            <button
                type="submit"
                class="rf-submit mt-3"
                wire:loading.attr="disabled"
                @if($lockedSeconds > 0) disabled @endif
            >
                <span wire:loading.remove wire:target="verify">{{ __('two_factor.verify_and_continue') }}</span>
                <span wire:loading wire:target="verify">{{ __('two_factor.verifying') }}</span>
            </button>
        </form>

        <div class="mt-4 pt-3 border-top d-flex flex-column gap-2 small">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <button
                    type="button"
                    wire:click="toggleRecoveryMode"
                    class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold"
                >
                    @if(!$useRecoveryCode)
                        {{ __('two_factor.use_recovery_code_instead') }}
                    @else
                        {{ __('two_factor.use_verification_code_instead') }}
                    @endif
                </button>

                @if(!$useRecoveryCode && in_array($method, ['email', 'sms'], true))
                    <button
                        type="button"
                        wire:click="resend"
                        wire:loading.attr="disabled"
                        class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold"
                    >
                        <span wire:loading.remove wire:target="resend">{{ __('two_factor.send_new_code') }}</span>
                        <span wire:loading wire:target="resend">{{ __('two_factor.sending') }}</span>
                    </button>
                @endif
            </div>

            <div class="d-flex align-items-center justify-content-between text-muted mt-1">
                <span>{{ __('two_factor.having_trouble_contact_admin') }}</span>
                <a href="{{ url('/logout') }}" class="rf-link">
                    {{ __('two_factor.sign_out') }}
                </a>
            </div>
        </div>
    </x-auth-shell>
</main>
