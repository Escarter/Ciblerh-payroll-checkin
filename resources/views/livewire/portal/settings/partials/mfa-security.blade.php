<div x-show="$wire.activeTab === 'security'" x-cloak>
    @include('livewire.partials.two-factor.status', [
        'summary' => $mfaSummary ?? null,
        'showEnrollmentCount' => true,
    ])

    <div class="row g-4">
        {{-- Left Column: Global MFA Policy & Methods --}}
        <div class="col-lg-6">
            <div class="mb-4 card shadow-md card-raised">
                <div class="py-4 px-5 card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="card-title h5 mb-0">
                            <i class="fas fa-shield-alt text-primary me-2"></i>{{ __('two_factor.admin_section_title') }}
                        </div>
                        <span class="badge {{ $mfa_required_for === 'none' ? 'bg-secondary' : 'bg-success' }}">
                            {{ $mfa_required_for === 'none' ? __('two_factor.status_disabled') : __('two_factor.required_badge') }}
                        </span>
                    </div>
                    <p class="text-muted small mb-4">{{ __('two_factor.admin_section_subtitle') }}</p>

                    <form wire:submit.prevent="saveMfaConfiguration">
                        <div class="mb-4">
                            <label for="mfa_required_for" class="form-label fw-semibold">
                                {{ __('two_factor.enforcement_policy_label') }}
                            </label>
                            <select id="mfa_required_for" wire:model.live="mfa_required_for" class="form-select">
                                <option value="none">{{ __('two_factor.policy_optional') }}</option>
                                <option value="admins_only">{{ __('two_factor.policy_admins_only') }}</option>
                                <option value="portal_staff">{{ __('two_factor.policy_portal_staff') }}</option>
                                <option value="everyone">{{ __('two_factor.policy_everyone') }}</option>
                            </select>
                            <div class="form-text text-muted small mt-1">
                                {{ __('two_factor.enforcement_policy_help') }}
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold d-block mb-2">
                                {{ __('two_factor.allowed_methods_label') }}
                            </label>

                            <div class="p-3 bg-light rounded border mb-2">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="mfa_authenticator_enabled" wire:model="mfa_authenticator_enabled">
                                    <label class="form-check-label fw-semibold" for="mfa_authenticator_enabled">
                                        {{ __('two_factor.method_authenticator') }}
                                    </label>
                                    <div class="text-muted small">{{ __('two_factor.method_authenticator_help') }}</div>
                                </div>
                            </div>

                            <div class="p-3 bg-light rounded border mb-2">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="mfa_email_enabled" wire:model="mfa_email_enabled">
                                    <label class="form-check-label fw-semibold" for="mfa_email_enabled">
                                        {{ __('two_factor.method_email') }}
                                    </label>
                                    <div class="text-muted small">{{ __('two_factor.method_email_help') }}</div>
                                </div>
                            </div>

                            <div class="p-3 bg-light rounded border">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="mfa_sms_enabled" wire:model="mfa_sms_enabled">
                                    <label class="form-check-label fw-semibold" for="mfa_sms_enabled">
                                        {{ __('two_factor.method_sms') }}
                                    </label>
                                    <div class="text-muted small">{{ __('two_factor.method_sms_help') }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label for="mfa_code_minutes" class="form-label fw-semibold small">
                                    {{ __('two_factor.code_lifetime_minutes_label') }}
                                </label>
                                <input type="number" id="mfa_code_minutes" wire:model="mfa_code_minutes" min="3" max="30" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label for="mfa_max_attempts" class="form-label fw-semibold small">
                                    {{ __('two_factor.max_attempts_label') }}
                                </label>
                                <input type="number" id="mfa_max_attempts" wire:model="mfa_max_attempts" min="3" max="10" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label for="mfa_remember_days" class="form-label fw-semibold small">
                                    {{ __('two_factor.trusted_device_days_label') }}
                                </label>
                                <input type="number" id="mfa_remember_days" wire:model="mfa_remember_days" min="0" max="180" class="form-control">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end border-top pt-3">
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                                <i class="fas fa-save me-1"></i>
                                <span wire:loading.remove wire:target="saveMfaConfiguration">{{ __('two_factor.save_security_settings') }}</span>
                                <span wire:loading wire:target="saveMfaConfiguration">{{ __('common.saving') }}...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Right Column: System-wide MFA Security Events Log --}}
        <div class="col-lg-6">
            <div class="mb-4 card shadow-md card-raised">
                <div class="py-4 px-5 card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="card-title h5 mb-0">
                            <i class="fas fa-history text-info me-2"></i>{{ __('two_factor.system_security_events_title') }}
                        </div>
                    </div>
                    <p class="text-muted small mb-3">{{ __('two_factor.system_security_events_subtitle') }}</p>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-5">
                            <select wire:model.live="mfaEventType" class="form-select form-select-sm">
                                <option value="">{{ __('two_factor.all_event_types') }}</option>
                                <option value="challenge_passed">{{ __('two_factor.event_challenge_passed') }}</option>
                                <option value="challenge_failed">{{ __('two_factor.event_challenge_failed') }}</option>
                                <option value="challenge_locked">{{ __('two_factor.event_challenge_locked') }}</option>
                                <option value="recovery_used">{{ __('two_factor.event_recovery_used') }}</option>
                                <option value="recovery_failed">{{ __('two_factor.event_recovery_failed') }}</option>
                                <option value="mfa_enabled">{{ __('two_factor.event_mfa_enabled') }}</option>
                                <option value="mfa_method_changed">{{ __('two_factor.event_mfa_method_changed') }}</option>
                                <option value="mfa_disabled">{{ __('two_factor.event_mfa_disabled') }}</option>
                                <option value="mfa_reset_by_admin">{{ __('two_factor.event_mfa_reset_by_admin') }}</option>
                                <option value="mfa_required_changed">{{ __('two_factor.event_mfa_required_changed') }}</option>
                                <option value="trusted_device_added">{{ __('two_factor.event_trusted_device_added') }}</option>
                                <option value="trusted_device_used">{{ __('two_factor.event_trusted_device_used') }}</option>
                                <option value="trusted_device_revoked">{{ __('two_factor.event_trusted_device_revoked') }}</option>
                            </select>
                        </div>
                        <div class="col-sm-7">
                            <input
                                type="search"
                                wire:model.live.debounce.300ms="mfaEventSearch"
                                placeholder="{{ __('two_factor.search_events_placeholder') }}"
                                class="form-control form-control-sm"
                            >
                        </div>
                    </div>

                    @if(($mfaSecurityEvents ?? collect())->count() === 0)
                        <div class="text-center py-4 text-muted small">
                            {{ __('two_factor.no_matching_security_events') }}
                        </div>
                    @else
                        <div class="table-responsive border rounded-3">
                            <table class="table table-sm table-hover align-middle mb-0 small">
                                <thead class="table-light">
                                    <tr>
                                        <th>{{ __('two_factor.user_column') }}</th>
                                        <th>{{ __('two_factor.event_column') }}</th>
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
                                                <div class="fw-semibold text-dark">{{ $secEvent->user?->name ?? '-' }}</div>
                                                <div class="text-muted" style="font-size: 0.72rem;">{{ $secEvent->user?->email ?? '' }}</div>
                                            </td>
                                            <td>
                                                <span class="badge {{ $badgeClass }}">
                                                    {{ __('two_factor.event_' . $secEvent->event) }}
                                                </span>
                                                @if($secEvent->method)
                                                    <span class="text-muted ms-1">({{ $secEvent->method }})</span>
                                                @endif
                                                @if($secEvent->reason)
                                                    <div class="text-muted" style="font-size: 0.72rem;">{{ $secEvent->reason }}</div>
                                                @endif
                                            </td>
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
    </div>
</div>
