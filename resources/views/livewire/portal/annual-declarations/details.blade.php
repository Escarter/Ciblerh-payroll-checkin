<div @if($process->status === 'processing') wire:poll.5s @endif>
    <x-alert />

    @php
        $tone = ['processing' => 'info', 'successful' => 'success', 'failed' => 'danger'][$process->status] ?? 'secondary';
        $total = $counts['sendable'];
        $emailOk = $process->declarations()->sendable()->where('email_sent_status', 1)->count();
        $smsOk = $process->declarations()->sendable()->where('sms_sent_status', 1)->count();
        $pct = $total > 0 ? round($emailOk / $total * 100) : 0;
        $badge = fn ($s) => match ((int) $s) {
            1 => ['success', 'status_successful', 'M5 13l4 4L19 7'],
            2 => ['danger', 'status_failed', 'M6 18L18 6M6 6l12 12'],
            3 => ['gray-400', 'status_disabled', 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636'],
            4 => ['gray-400', 'status_skipped', 'M20 12H4'],
            default => ['warning', 'status_pending', 'M12 8v4l3 3'],
        };
    @endphp

    <div class="py-2 pb-2 d-flex justify-content-between flex-wrap align-items-start">
        <div>
            <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
                <ol class="breadcrumb breadcrumb-dark breadcrumb-transparent">
                    <li class="breadcrumb-item"><a href="{{ route('portal.dashboard') }}" wire:navigate>{{ __('dashboard.home') }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('portal.annual-declarations.index') }}" wire:navigate>{{ __('annual_declarations.annual_declarations') }}</a></li>
                    <li class="breadcrumb-item active">{{ $process->year }}</li>
                </ol>
            </nav>
            <h1 class="h4 mb-1 d-flex align-items-center flex-wrap">
                {{ __('annual_declarations.annual_declarations') }} {{ $process->year }}
                <span class="badge bg-{{ $tone }} ms-2 fs-7">{{ __('annual_declarations.status_' . $process->status) }}</span>
            </h1>
            <div class="rf-meta">
                <span class="rf-chip"><svg class="icon icon-xxs" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5"></path></svg>{{ $process->company?->name }}</span>
                <span class="rf-chip"><svg class="icon icon-xxs" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>{{ $process->department?->name ?? __('annual_declarations.all_departments') }}</span>
                <span class="rf-chip"><svg class="icon icon-xxs" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>{{ $process->created_at->format('Y-m-d H:i') }}</span>
            </div>
        </div>
        <div class="d-flex gap-2 mt-2 mt-md-0">
            <a href="{{ route('portal.annual-declarations.index') }}" wire:navigate class="btn btn-sm btn-outline-gray-600">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>{{ __('common.back') }}
            </a>
            @can('payslip-sending')
                @if($counts['failed'] > 0)
                    <button class="btn btn-sm btn-warning" wire:click="retryFailed" wire:loading.attr="disabled" wire:target="retryFailed">
                        <span wire:loading wire:target="retryFailed" class="spinner-border spinner-border-sm me-1"></span>
                        {{ __('annual_declarations.retry_failed') }} ({{ $counts['failed'] }})
                    </button>
                @endif
            @endcan
        </div>
    </div>

    @if($process->failure_reason)
        <div class="alert alert-danger d-flex align-items-start" role="alert">
            <svg class="icon icon-xs me-2 mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.34 16c-.77 1.33.19 3 1.73 3z"></path></svg>
            <div>{{ $process->failure_reason }}</div>
        </div>
    @endif

    {{-- Progress overview --}}
    <div class="rf-tiles mb-3">
        @foreach ([
            [$process->total_pages, __('annual_declarations.pages'), 'ink'],
            [$emailOk, __('annual_declarations.emails_sent'), 'success'],
            [$smsOk, __('annual_declarations.sms_sent'), 'success'],
            [$counts['review'], __('annual_declarations.to_review'), 'warning'],
            [$counts['failed'], __('annual_declarations.tab_failed'), 'danger'],
        ] as [$value, $label, $color])
            <div class="rf-tile rf-tile-{{ $color }}">
                <div class="rf-tile-label">{{ $label }}</div>
                <div class="rf-tile-value">{{ number_format((int) $value, 0, ',', ' ') }}</div>
            </div>
        @endforeach
    </div>

    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between small mb-2">
                <span class="fw-semibold">{{ __('annual_declarations.delivery') }}</span>
                <span class="text-muted">{{ $process->status === 'processing' ? (int) $process->percentage_completion . '%' : $emailOk . ' / ' . $total . ' · ' . $pct . '%' }}</span>
            </div>
            <div class="progress rf-progress">
                @if($process->status === 'processing')
                    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width:{{ max((int) $process->percentage_completion, 5) }}%"></div>
                @else
                    <div class="progress-bar" style="width:{{ $pct }}%"></div>
                @endif
            </div>
        </div>
    </div>

    {{-- Tabs + search --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <ul class="nav nav-tabs border-0" role="tablist">
            @foreach ([
                'sendable' => 'tab_sendable',
                'review' => 'tab_review',
                'failed' => 'tab_failed',
            ] as $key => $label)
                <li class="nav-item">
                    <button type="button" wire:click="switchTab('{{ $key }}')" class="nav-link {{ $tab === $key ? 'active' : '' }}">
                        {{ __('annual_declarations.' . $label) }}
                        <span class="rf-count rf-count-{{ $key }}">{{ $counts[$key] }}</span>
                    </button>
                </li>
            @endforeach
        </ul>
        <div class="input-group" style="max-width:300px;">
            <span class="input-group-text"><svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg></span>
            <input type="text" wire:model.live.debounce.400ms="query" class="form-control" placeholder="{{ __('common.search_placeholder') }}">
        </div>
    </div>

    @if($tab === 'review' && $counts['review'] > 0)
        <div class="alert alert-warning small py-2">{{ __('annual_declarations.review_hint') }}</div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-centered table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th class="border-0">{{ __('annual_declarations.page') }}</th>
                        <th class="border-0">{{ __('annual_declarations.employee') }}</th>
                        <th class="border-0 text-end">{{ __('annual_declarations.gross_amount') }}</th>
                        @if($tab === 'review')
                            <th class="border-0">{{ __('annual_declarations.review_reason') }}</th>
                        @else
                            <th class="border-0 text-center">{{ __('annual_declarations.encryption') }}</th>
                            <th class="border-0 text-center">{{ __('annual_declarations.email') }}</th>
                            <th class="border-0 text-center">{{ __('annual_declarations.sms') }}</th>
                            <th class="border-0"></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($declarations as $d)
                        @php
                            $name = trim($d->first_name . ' ' . $d->last_name) ?: ($d->extracted_name ?: '—');
                            $initials = mb_strtoupper(mb_substr($name, 0, 1));
                        @endphp
                        <tr wire:key="decl-{{ $d->id }}">
                            <td class="text-muted">#{{ $d->page_number }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="rf-avatar me-3">{{ $initials }}</div>
                                    <div>
                                        <div class="fw-semibold text-ink">{{ $name }}</div>
                                        <div class="small text-muted">{{ $d->matricule ?? '—' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end">{{ $d->gross_amount !== null ? number_format($d->gross_amount, 0, ',', ' ') : '—' }}</td>

                            @if($tab === 'review')
                                <td>
                                    <span class="badge bg-{{ $d->page_status === 'empty' ? 'warning text-dark' : 'danger' }}">{{ __('annual_declarations.page_' . $d->page_status) }}</span>
                                    <div class="small text-muted mt-1">{{ $d->failure_reason }}</div>
                                </td>
                            @else
                                @foreach ([
                                    [$d->encryption_status, $d->encryption_status_note, null],
                                    [$d->email_sent_status, $d->email_status_note, null],
                                    [$d->sms_sent_status, $d->sms_status_note, $d->sms_delivery_status],
                                ] as [$status, $note, $delivery])
                                    @php [$color, $label, $path] = $badge($status); @endphp
                                    <td class="text-center">
                                        <span class="rf-status rf-status-{{ str_replace('gray-400', 'muted', $color) }}" title="{{ $note }}" data-bs-toggle="tooltip">
                                            <svg class="icon icon-xxs" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="{{ $path }}"></path></svg>
                                            {{ __('annual_declarations.' . $label) }}
                                        </span>
                                        @if($delivery === 'delivered')
                                            <div class="small text-success">{{ __('annual_declarations.delivered') }}</div>
                                        @elseif(in_array($delivery, ['failed', 'expired', 'rejected'], true))
                                            <div class="small text-danger">{{ __('annual_declarations.not_delivered') }}</div>
                                        @endif
                                        @if((int) $status === 2 && $note)
                                            <div class="small text-danger text-truncate mx-auto" style="max-width:140px;">{{ $note }}</div>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('portal.annual-declarations.view-pdf', $d->id) }}" target="_blank" class="btn btn-sm btn-outline-gray-600" title="{{ __('payslips.view_pdf') }}">
                                        <svg class="icon icon-xxs" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </a>
                                    @can('payslip-sending')
                                        <button class="btn btn-sm btn-outline-primary" wire:click="resend({{ $d->id }})" wire:loading.attr="disabled" wire:target="resend({{ $d->id }})" title="{{ __('annual_declarations.resend') }}">
                                            <span wire:loading.remove wire:target="resend({{ $d->id }})">{{ __('annual_declarations.resend') }}</span>
                                            <span wire:loading wire:target="resend({{ $d->id }})" class="spinner-border spinner-border-sm"></span>
                                        </button>
                                    @endcan
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <svg class="icon icon-lg text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                <div class="text-muted">{{ __('annual_declarations.no_records') }}</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($declarations->hasPages())
            <div class="card-footer border-0">{{ $declarations->links() }}</div>
        @endif
    </div>
</div>
