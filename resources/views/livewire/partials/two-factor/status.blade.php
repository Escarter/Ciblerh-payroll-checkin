@php
    $summary = $summary ?? app(\App\Services\TwoFactorService::class)->summary($forUser ?? null);
    $compact = $compact ?? false;
@endphp

<div class="card border-0 shadow-sm mb-4 {{ $compact ? 'p-3' : 'p-4' }}" style="background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%);">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-start gap-3">
            <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-primary bg-opacity-10 text-primary p-2 flex-shrink-0" style="width: 40px; height: 40px;">
                <i class="fas fa-user-shield"></i>
            </span>
            <div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="fw-bold text-dark">
                        {{ __('two_factor.summary_heading') }}
                    </span>
                    <span class="badge {{ $summary['policy'] === 'none' ? 'bg-secondary' : 'bg-primary' }}">
                        {{ $summary['policy_label'] }}
                    </span>
                </div>
                <p class="text-muted small mb-0 mt-1">
                    @if(!empty($summary['methods']))
                        {{ __('two_factor.summary_allowed_methods', ['methods' => implode(', ', $summary['methods'])]) }}
                    @else
                        {{ __('two_factor.summary_no_methods_enabled') }}
                    @endif
                </p>
            </div>
        </div>

        @if(($showEnrollmentCount ?? false) && $summary['staff_total'] > 0)
            <div class="text-end flex-shrink-0">
                <div class="small text-muted">{{ __('two_factor.summary_enrolled_staff') }}</div>
                <div class="fw-bold text-dark">
                    {{ $summary['enrolled_count'] }} / {{ $summary['staff_total'] }}
                </div>
            </div>
        @endif
    </div>
</div>
