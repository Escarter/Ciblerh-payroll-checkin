@php
    $mfaUser = $mfaUser ?? auth()->user();
    $mfaEnabled = (bool) ($mfaEnabled ?? false);
    $mfaRequired = (bool) ($mfaRequired ?? false);
    $mfaMethods = $mfaMethods ?? [];
    $mfaAuthenticator = $mfaAuthenticator ?? null;
    $mfaRecoveryRemaining = (int) ($mfaRecoveryRemaining ?? 0);
    $mfaTrustedDevices = $mfaTrustedDevices ?? collect();
    $mfaSecurityEvents = $mfaSecurityEvents ?? collect();
    $currentMethod = $mfaUser?->mfa_method;
    $currentMethodLabel = match ($currentMethod) {
        'authenticator' => __('two_factor.method_authenticator'),
        'email' => __('two_factor.method_email'),
        'sms' => __('two_factor.method_sms'),
        default => __('two_factor.status_disabled'),
    };
@endphp

<div class="card card-body border-0 shadow mt-3 mb-4 text-gray-700" id="two-factor-section">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
        <div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <h2 class="h5 mb-0 text-dark">{{ __('two_factor.card_title') }}</h2>
                @if($mfaEnabled)
                    <span class="badge bg-success">
                        {{ __('two_factor.status_enabled_via', ['method' => $currentMethodLabel]) }}
                    </span>
                @else
                    <span class="badge bg-secondary">
                        {{ __('two_factor.status_disabled') }}
                    </span>
                @endif

                @if($mfaRequired)
                    <span class="badge bg-warning text-dark">
                        {{ __('two_factor.required_badge') }}
                    </span>
                @endif
            </div>
            <p class="small text-muted mb-0 mt-1">
                {{ __('two_factor.card_subtitle') }}
            </p>
        </div>

        @if($mfaEnabled && $mfaUser?->mfa_last_used_at)
            <div class="small text-muted">
                {{ __('two_factor.last_verified') }}:
                <span class="fw-semibold text-dark">{{ $mfaUser->mfa_last_used_at->diffForHumans() }}</span>
            </div>
        @endif
    </div>

    {{-- Newly generated recovery codes banner (shown once right after setup or regeneration) --}}
    @if(!empty($twoFactorRecoveryCodes))
        <div class="mb-3">
            @include('livewire.partials.two-factor.recovery-codes', [
                'codes' => $twoFactorRecoveryCodes,
                'finishAction' => 'dismissRecoveryCodes',
            ])
        </div>
    @endif

    @if($mfaEnabled && $twoFactorChosenMethod === '')
        {{-- Active enrollment summary + quick actions --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 p-3 rounded-3 bg-light border mb-3">
            <div class="small text-gray-700">
                @if($currentMethod === 'authenticator')
                    {{ __('two_factor.active_summary_authenticator') }}
                @elseif($currentMethod === 'sms')
                    {{ __('two_factor.active_summary_sms', ['dest' => $mfaUser?->mfa_phone ?: $mfaUser?->professional_phone_number ?: $mfaUser?->phone_number]) }}
                @else
                    {{ __('two_factor.active_summary_email', ['dest' => $mfaUser?->email]) }}
                @endif
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <button
                    type="button"
                    wire:click="startTwoFactorTest"
                    wire:loading.attr="disabled"
                    class="btn btn-sm btn-outline-primary"
                >
                    {{ __('two_factor.send_test_code') }}
                </button>

                <button
                    type="button"
                    wire:click="selectTwoFactorMethod('{{ $currentMethod }}')"
                    class="btn btn-sm btn-outline-secondary"
                >
                    {{ __('two_factor.change_method') }}
                </button>

                @if(!$mfaRequired)
                    <button
                        type="button"
                        wire:click="disableTwoFactor"
                        wire:confirm="{{ __('two_factor.confirm_disable') }}"
                        class="btn btn-sm btn-outline-danger"
                    >
                        {{ __('two_factor.turn_off') }}
                    </button>
                @endif
            </div>
        </div>

        {{-- In-place test verification drawer --}}
        @if($twoFactorTesting)
            <div class="alert alert-info p-3 mb-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-bold small">
                        {{ __('two_factor.test_panel_title') }}
                    </span>
                    <button type="button" wire:click="cancelTwoFactorTest" class="btn btn-link btn-sm p-0 text-decoration-none">
                        {{ __('two_factor.cancel') }}
                    </button>
                </div>
                <p class="small mb-2">
                    @if($currentMethod === 'authenticator')
                        {{ __('two_factor.test_instructions_authenticator') }}
                    @else
                        {{ __('two_factor.test_instructions_code_sent', ['dest' => $twoFactorTestMasked ?? '']) }}
                    @endif
                </p>

                @if($twoFactorTestError)
                    <div class="alert alert-danger py-1 px-2 small mb-2">
                        {{ $twoFactorTestError }}
                    </div>
                @endif

                <form wire:submit.prevent="verifyTwoFactorTest" class="d-flex flex-wrap gap-2">
                    <input
                        type="text"
                        inputmode="numeric"
                        maxlength="6"
                        wire:model="twoFactorTestCode"
                        placeholder="000000"
                        class="form-control form-control-sm text-center fw-bold bg-white"
                        style="max-width: 160px; letter-spacing: 0.3em; font-family: monospace;"
                    >
                    <button type="submit" wire:loading.attr="disabled" class="btn btn-sm btn-primary">
                        {{ __('two_factor.check_code') }}
                    </button>
                </form>
            </div>
        @endif

        {{-- Recovery codes & Trusted devices row --}}
        <div class="row g-3 mt-1">
            {{-- Recovery codes status --}}
            <div class="col-12 col-md-6">
                <div class="p-3 rounded-3 border h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="fw-bold small text-dark">{{ __('two_factor.recovery_codes_heading') }}</span>
                            <span class="badge {{ $mfaRecoveryRemaining <= 2 ? 'bg-danger' : 'bg-secondary' }}">
                                {{ __('two_factor.recovery_codes_remaining', ['count' => $mfaRecoveryRemaining]) }}
                            </span>
                        </div>
                        <p class="small text-muted mt-1 mb-2">
                            {{ __('two_factor.recovery_codes_card_help') }}
                        </p>
                    </div>
                    <div class="mt-2">
                        <button
                            type="button"
                            wire:click="regenerateTwoFactorRecoveryCodes"
                            wire:confirm="{{ __('two_factor.confirm_regenerate_recovery_codes') }}"
                            class="btn btn-sm btn-outline-secondary"
                        >
                            {{ __('two_factor.regenerate_recovery_codes') }}
                        </button>
                    </div>
                </div>
            </div>

            {{-- Trusted devices list --}}
            <div class="col-12 col-md-6">
                <div class="p-3 rounded-3 border h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="fw-bold small text-dark">{{ __('two_factor.trusted_devices_heading') }}</span>
                            <span class="badge bg-secondary">
                                {{ $mfaTrustedDevices->count() }}
                            </span>
                        </div>

                        @if($mfaTrustedDevices->isEmpty())
                            <p class="small text-muted mt-1 mb-0">
                                {{ __('two_factor.trusted_devices_empty') }}
                            </p>
                        @else
                            <ul class="list-unstyled mt-2 mb-0 small">
                                @foreach($mfaTrustedDevices as $device)
                                    <li class="d-flex align-items-center justify-content-between gap-2 py-1 border-bottom">
                                        <div class="text-truncate">
                                            <span class="fw-semibold text-dark">{{ $device->name ?: __('two_factor.browser') }}</span>
                                            <span class="text-muted">· {{ $device->ip_address }}</span>
                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                {{ __('two_factor.expires_in', ['when' => $device->expires_at?->diffForHumans() ?? '-']) }}
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            wire:click="revokeTrustedDevice({{ $device->id }})"
                                            class="btn btn-link btn-sm text-danger p-0 text-decoration-none"
                                        >
                                            {{ __('two_factor.remove') }}
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    @if($mfaTrustedDevices->count() > 1)
                        <div class="mt-2">
                            <button
                                type="button"
                                wire:click="revokeAllTrustedDevices"
                                class="btn btn-link btn-sm text-danger p-0 text-decoration-none"
                            >
                                {{ __('two_factor.revoke_all_trusted_devices') }}
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @else
        {{-- Enrolment or method-switch wizard --}}
        @include('livewire.partials.two-factor.enrol', [
            'methods' => $mfaMethods,
            'chosenMethod' => $twoFactorChosenMethod,
            'authenticator' => $mfaAuthenticator,
            'setupDestination' => $twoFactorSetupDestination,
            'setupError' => $twoFactorSetupError,
            'showCancel' => true,
        ])
    @endif

    {{-- Recent MFA Security Activity --}}
    <div class="mt-4 pt-3 border-top" x-data="{ showActivity: false }">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <button
                type="button"
                data-no-collapse
                @click="showActivity = !showActivity"
                class="d-inline-flex align-items-center gap-1 border-0 bg-transparent p-0 fw-semibold text-gray-600 small"
                style="cursor: pointer;"
            >
                <span class="text-gray-600">{{ __('two_factor.recent_security_activity') }} ({{ method_exists($mfaSecurityEvents, 'total') ? $mfaSecurityEvents->total() : $mfaSecurityEvents->count() }})</span>
                <span x-text="showActivity ? '▲' : '▼'" class="ms-1 text-gray-500" style="font-size: 0.7rem;"></span>
            </button>

            <div x-show="showActivity" x-cloak style="min-width: 220px;">
                <input
                    type="search"
                    wire:model.live.debounce.300ms="twoFactorEventSearch"
                    placeholder="{{ __('two_factor.search_events_placeholder') }}"
                    class="form-control form-control-sm"
                >
            </div>
        </div>

        <div x-show="showActivity" x-cloak class="mt-3">
            @if($mfaSecurityEvents->count() === 0)
                <p class="small text-muted mb-0">{{ __('two_factor.no_matching_security_events') }}</p>
            @else
                <div class="table-responsive border rounded-3">
                    <table class="table table-sm table-hover align-middle mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>{{ __('two_factor.event_column') }}</th>
                                <th>{{ __('two_factor.method_column') }}</th>
                                <th>{{ __('two_factor.ip_column') }}</th>
                                <th class="text-end">{{ __('two_factor.when_column') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($mfaSecurityEvents as $secEvent)
                                @php
                                    $badgeClass = match ($secEvent->status) {
                                        'success' => 'bg-success',
                                        'warning' => 'bg-warning text-dark',
                                        'danger' => 'bg-danger',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <tr>
                                    <td>
                                        <span class="badge {{ $badgeClass }}">
                                            {{ __('two_factor.event_' . $secEvent->event) }}
                                        </span>
                                        @if($secEvent->reason)
                                            <span class="ms-1 text-muted">({{ $secEvent->reason }})</span>
                                        @endif
                                    </td>
                                    <td class="text-muted">{{ $secEvent->method ?? '-' }}</td>
                                    <td class="text-muted font-monospace">{{ $secEvent->ip_address ?? '-' }}</td>
                                    <td class="text-end text-muted">{{ $secEvent->created_at?->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if(method_exists($mfaSecurityEvents, 'hasPages') && $mfaSecurityEvents->hasPages())
                    <div class="mt-2">
                        {{ $mfaSecurityEvents->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
