@php
    $methods = $methods ?? [];
    $chosenMethod = $chosenMethod ?? '';
    $authenticator = $authenticator ?? null;
    $setupDestination = $setupDestination ?? null;
    $setupError = $setupError ?? null;
    $showCancel = $showCancel ?? true;
@endphp

<div>
    @if(empty($methods))
        <div class="alert alert-warning mb-0">
            {{ __('two_factor.no_methods_available_contact_admin') }}
        </div>
    @else
        {{-- Step 1: Choose how to receive codes --}}
        <div class="mb-3">
            <label class="form-label fw-semibold small text-uppercase text-muted mb-2">
                1. {{ __('two_factor.step_choose_method') }}
            </label>
            <div class="row g-2">
                @foreach($methods as $key => $info)
                    @php
                        $isSelected = $chosenMethod === $key;
                        $isReady = (bool) ($info['ready'] ?? false);
                    @endphp
                    <div class="col-12 col-md-4">
                        <button
                            type="button"
                            wire:click="selectTwoFactorMethod('{{ $key }}')"
                            @if(!$isReady) disabled @endif
                            class="btn w-100 h-100 text-start p-3 rounded-3 border {{ !$isReady ? 'opacity-50' : '' }}"
                            style="{{ $isSelected ? 'background-color: #eff6ff; border: 2px solid var(--rf-brand, #1d4ba0) !important; box-shadow: 0 2px 8px rgba(29, 75, 160, 0.10);' : 'background-color: #ffffff; border: 1px solid #e2e8f0 !important;' }}"
                        >
                            <div class="d-flex align-items-start justify-content-between gap-1 mb-1">
                                <span class="fw-bold text-dark small">{{ $info['label'] }}</span>
                                @if($isSelected)
                                    <span class="badge bg-primary flex-shrink-0">{{ __('two_factor.selected') }}</span>
                                @endif
                            </div>
                            <div class="text-muted" style="font-size: 0.78rem; line-height: 1.35;">
                                {{ $info['description'] }}
                            </div>
                            @if(!empty($info['hint']))
                                <div class="mt-2 small {{ $isReady ? 'text-muted' : 'text-warning-emphasis' }}" style="font-size: 0.75rem;">
                                    {{ $info['hint'] }}
                                </div>
                            @endif
                        </button>
                    </div>
                @endforeach
            </div>
        </div>

        @if($setupError)
            <div class="alert alert-danger py-2 px-3 small mb-3">
                {{ $setupError }}
            </div>
        @endif

        {{-- Step 2: Method-specific instructions + 6-digit confirmation --}}
        @if($chosenMethod === 'authenticator' && $authenticator)
            <div class="card border bg-light-subtle p-3 mb-3">
                <div class="fw-semibold small text-uppercase text-muted mb-2">
                    2. {{ __('two_factor.step_scan_qr') }}
                </div>
                <div class="row g-3 align-items-center">
                    <div class="col-auto">
                        @if(!empty($authenticator['qr_svg']))
                            <div class="bg-white p-2 rounded-3 border d-inline-block">
                                <img
                                    src="{{ $authenticator['qr_svg'] }}"
                                    alt="{{ __('two_factor.qr_code_alt') }}"
                                    style="width: 168px; height: 168px; display: block;"
                                >
                            </div>
                        @endif
                    </div>
                    <div class="col">
                        <p class="small text-muted mb-2">
                            {{ __('two_factor.authenticator_scan_instructions') }}
                        </p>
                        <div class="p-2 rounded bg-white border">
                            <div class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">
                                {{ __('two_factor.manual_entry_key') }}
                            </div>
                            <code class="d-block mt-1 fw-bold text-dark user-select-all" style="font-size: 0.9rem; letter-spacing: 0.06em;">
                                {{ $authenticator['formatted_secret'] }}
                            </code>
                        </div>
                    </div>
                </div>

                <form wire:submit.prevent="confirmTwoFactorSetup" class="mt-3 pt-3 border-top">
                    <label class="form-label small fw-semibold">
                        3. {{ __('two_factor.step_enter_6_digit_from_app') }}
                    </label>
                    <div class="d-flex flex-wrap gap-2">
                        <input
                            type="text"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            maxlength="6"
                            wire:model="twoFactorSetupCode"
                            placeholder="000000"
                            class="form-control text-center fw-bold"
                            style="max-width: 180px; letter-spacing: 0.3em; font-family: monospace;"
                        >
                        <button type="submit" wire:loading.attr="disabled" class="btn btn-primary">
                            <span wire:loading.remove wire:target="confirmTwoFactorSetup">{{ __('two_factor.verify_and_turn_on') }}</span>
                            <span wire:loading wire:target="confirmTwoFactorSetup">{{ __('two_factor.verifying') }}</span>
                        </button>
                        @if($showCancel)
                            <button type="button" wire:click="cancelTwoFactorSetup" class="btn btn-outline-secondary">
                                {{ __('two_factor.cancel') }}
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        @elseif($chosenMethod === 'email')
            <div class="card border bg-light-subtle p-3 mb-3">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <div>
                        <div class="fw-semibold small text-uppercase text-muted">
                            2. {{ __('two_factor.step_check_your_email') }}
                        </div>
                        <p class="small text-muted mb-0 mt-1">
                            {{ __('two_factor.we_sent_6_digit_to', ['dest' => $setupDestination ?: __('two_factor.your_email')]) }}
                        </p>
                    </div>
                    <button
                        type="button"
                        wire:click="resendTwoFactorSetupCode"
                        wire:loading.attr="disabled"
                        class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold"
                    >
                        {{ __('two_factor.send_new_code') }}
                    </button>
                </div>

                <form wire:submit.prevent="confirmTwoFactorSetup" class="mt-2">
                    <div class="d-flex flex-wrap gap-2">
                        <input
                            type="text"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            maxlength="6"
                            wire:model="twoFactorSetupCode"
                            placeholder="000000"
                            class="form-control text-center fw-bold"
                            style="max-width: 180px; letter-spacing: 0.3em; font-family: monospace;"
                        >
                        <button type="submit" wire:loading.attr="disabled" class="btn btn-primary">
                            <span wire:loading.remove wire:target="confirmTwoFactorSetup">{{ __('two_factor.verify_and_turn_on') }}</span>
                            <span wire:loading wire:target="confirmTwoFactorSetup">{{ __('two_factor.verifying') }}</span>
                        </button>
                        @if($showCancel)
                            <button type="button" wire:click="cancelTwoFactorSetup" class="btn btn-outline-secondary">
                                {{ __('two_factor.cancel') }}
                            </button>
                        @endif
                    </div>
                </form>
            </div>
        @elseif($chosenMethod === 'sms')
            <div class="card border bg-light-subtle p-3 mb-3">
                <div class="fw-semibold small text-uppercase text-muted mb-2">
                    2. {{ __('two_factor.step_enter_mobile_number') }}
                </div>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <input
                        type="tel"
                        wire:model="twoFactorSetupPhone"
                        placeholder="+237 6XX XXX XXX"
                        class="form-control"
                        style="max-width: 260px;"
                    >
                    <button
                        type="button"
                        wire:click="sendTwoFactorSmsSetupCode"
                        wire:loading.attr="disabled"
                        class="btn btn-outline-primary"
                    >
                        <span wire:loading.remove wire:target="sendTwoFactorSmsSetupCode">
                            {{ $setupDestination ? __('two_factor.send_new_code') : __('two_factor.send_code') }}
                        </span>
                        <span wire:loading wire:target="sendTwoFactorSmsSetupCode">{{ __('two_factor.sending') }}</span>
                    </button>
                </div>

                @if($setupDestination)
                    <div class="pt-3 border-top">
                        <p class="small text-muted mb-2">
                            {{ __('two_factor.we_sent_6_digit_to', ['dest' => $setupDestination]) }}
                        </p>
                        <form wire:submit.prevent="confirmTwoFactorSetup" class="d-flex flex-wrap gap-2">
                            <input
                                type="text"
                                inputmode="numeric"
                                autocomplete="one-time-code"
                                maxlength="6"
                                wire:model="twoFactorSetupCode"
                                placeholder="000000"
                                class="form-control text-center fw-bold"
                                style="max-width: 180px; letter-spacing: 0.3em; font-family: monospace;"
                            >
                            <button type="submit" wire:loading.attr="disabled" class="btn btn-primary">
                                <span wire:loading.remove wire:target="confirmTwoFactorSetup">{{ __('two_factor.verify_and_turn_on') }}</span>
                                <span wire:loading wire:target="confirmTwoFactorSetup">{{ __('two_factor.verifying') }}</span>
                            </button>
                            @if($showCancel)
                                <button type="button" wire:click="cancelTwoFactorSetup" class="btn btn-outline-secondary">
                                    {{ __('two_factor.cancel') }}
                                </button>
                            @endif
                        </form>
                    </div>
                @endif
            </div>
        @endif
    @endif
</div>
