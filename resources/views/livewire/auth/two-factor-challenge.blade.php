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
        :badge="__('two_factor.security_verification_badge')"
    >
        @if($errorMessage)
            <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center gap-2 mb-3 py-2.5 px-3 rounded-3" role="alert">
                <svg class="icon icon-xs text-danger flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="small fw-medium">{{ $errorMessage }}</div>
            </div>
        @endif

        <form wire:submit.prevent="verify" class="rf-auth-fields">
            @if(!$useRecoveryCode)
                <div class="rf-field">
                    <label for="twoFactorCode" class="d-flex align-items-center justify-content-between mb-2">
                        <span>{{ __('two_factor.code_label') }}</span>
                    </label>
                    <div class="rf-input @if($errorMessage) is-invalid @endif justify-content-center text-center" style="height: 3.5rem;">
                        <input
                            id="twoFactorCode"
                            type="text"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            maxlength="6"
                            autofocus
                            wire:model="code"
                            placeholder="000000"
                            class="text-center fw-bold"
                            style="letter-spacing: 0.35em; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 1.6rem !important; width: 100%; text-align: center;"
                            @if($lockedSeconds > 0) disabled @endif
                        >
                    </div>
                </div>
            @else
                <div class="rf-field">
                    <label for="twoFactorRecoveryCode" class="mb-2">
                        {{ __('two_factor.recovery_code_label') }}
                    </label>
                    <div class="rf-input @if($errorMessage) is-invalid @endif justify-content-center text-center" style="height: 3.5rem;">
                        <input
                            id="twoFactorRecoveryCode"
                            type="text"
                            autocomplete="off"
                            maxlength="14"
                            autofocus
                            wire:model="recoveryCode"
                            placeholder="XXXXX-XXXXX"
                            class="text-center fw-bold text-uppercase"
                            style="letter-spacing: 0.15em; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 1.25rem !important; width: 100%; text-align: center;"
                            @if($lockedSeconds > 0) disabled @endif
                        >
                    </div>
                    <small class="text-muted d-block mt-1">
                        {{ __('two_factor.recovery_code_hint') }}
                    </small>
                </div>
            @endif

            @if(($rememberDays ?? 0) > 0)
                <div class="rf-auth-row">
                    <label class="rf-check" for="trustDevice">
                        <input id="trustDevice" type="checkbox" wire:model="trustDevice">
                        <span>{{ __('two_factor.trust_this_device_days', ['days' => $rememberDays]) }}</span>
                    </label>
                </div>
            @endif

            <button
                type="submit"
                class="rf-auth-submit btn-loading"
                wire:loading.attr="disabled"
                @if($lockedSeconds > 0) disabled @endif
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 1.15rem; height: 1.15rem;">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
                <span wire:loading.remove wire:target="verify">{{ __('two_factor.verify_and_continue') }}</span>
                <span wire:loading wire:target="verify" class="d-inline-flex align-items-center gap-2">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    {{ __('two_factor.verifying') }}
                </span>
            </button>
        </form>

        <div class="mt-4 pt-3 border-top d-flex flex-column gap-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <button
                    type="button"
                    wire:click="toggleRecoveryMode"
                    class="rf-auth-link btn btn-link p-0 text-decoration-none fw-semibold"
                    style="background: none; border: none; font-size: 0.875rem;"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1 d-inline-block" style="width: 0.95rem; height: 0.95rem; vertical-align: -0.1em;">
                        @if(!$useRecoveryCode)
                            <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/>
                        @else
                            <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/><line x1="12" y1="18" x2="12.01" y2="18"/>
                        @endif
                    </svg>
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
                        class="rf-auth-link btn btn-link p-0 text-decoration-none fw-semibold"
                        style="background: none; border: none; font-size: 0.875rem;"
                    >
                        <span wire:loading.remove wire:target="resend">{{ __('two_factor.send_new_code') }}</span>
                        <span wire:loading wire:target="resend">{{ __('two_factor.sending') }}</span>
                    </button>
                @endif
            </div>

            <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 pt-2 border-top text-muted" style="font-size: 0.8125rem;">
                <span class="text-muted">
                    {{ __('two_factor.having_trouble_contact_admin') }}
                </span>
                <a href="{{ url('/logout') }}" class="rf-auth-link text-nowrap fw-semibold" style="font-size: 0.825rem;">
                    {{ __('two_factor.sign_out') }}
                </a>
            </div>
        </div>
    </x-auth-shell>
</main>
