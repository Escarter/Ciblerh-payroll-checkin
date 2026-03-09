<div>
    <x-delete-modal />
    <x-alert />

    {{-- ── Page Header ──────────────────────────────────────────── --}}
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb breadcrumb-dark breadcrumb-transparent mb-1">
                    <li class="breadcrumb-item">
                        <a href="{{ route('portal.dashboard') }}">
                            <svg class="icon icon-xxs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                        </a>
                    </li>
                    <li class="breadcrumb-item"><a href="/" wire:navigate>{{ __('common.home') }}</a></li>
                    <li class="breadcrumb-item active">{{ __('payslips.sftp_validator') }}</li>
                </ol>
            </nav>
            <h1 class="h4 mb-0 d-flex align-items-center gap-2">
                <svg class="icon text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ __('payslips.sftp_validator') }}
            </h1>
            <p class="text-muted small mb-0">{{ __('payslips.sftp_validator_description') }}</p>
        </div>
    </div>

    {{-- ── Stats Row ────────────────────────────────────────────── --}}
    <div class="row g-3 mb-4">
        {{-- Pending --}}
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="icon-shape icon-shape-warning rounded flex-shrink-0">
                        <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-muted small">{{ __('payslips.total_pending') }}</div>
                        <div class="h4 mb-0 fw-bold">{{ numberFormat($totalPending) }}</div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Validated --}}
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="icon-shape icon-shape-info rounded flex-shrink-0">
                        <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-muted small">{{ __('payslips.total_validated') }}</div>
                        <div class="h4 mb-0 fw-bold">{{ numberFormat($totalValidated) }}</div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Processed --}}
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="icon-shape icon-shape-success rounded flex-shrink-0">
                        <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-muted small">{{ __('payslips.total_processed') }}</div>
                        <div class="h4 mb-0 fw-bold">{{ numberFormat($totalProcessed) }}</div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Rejected --}}
        <div class="col-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center gap-3 py-3">
                    <div class="icon-shape icon-shape-danger rounded flex-shrink-0">
                        <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-muted small">{{ __('payslips.total_rejected') }}</div>
                        <div class="h4 mb-0 fw-bold">{{ numberFormat($totalRejected) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Filters Card ─────────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-3">
                    <label class="form-label form-label-sm mb-1 text-muted">{{ __('common.search_filename') }}</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-gray-500 border-end-0">
                            <svg class="icon icon-xs text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </span>
                        <input type="text" wire:model.live.debounce.300ms="searchFilename"
                            class="form-control border-start-0"
                            placeholder="{{ __('common.search') }}…">
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label form-label-sm mb-1 text-muted">{{ __('common.company') }}</label>
                    <select wire:model.live="filterCompany" class="form-select form-select-sm">
                        <option value="">{{ __('common.all_companies') }}</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label form-label-sm mb-1 text-muted">{{ __('common.department') }}</label>
                    <select wire:model.live="filterDepartment" class="form-select form-select-sm" {{ empty($filterCompany) ? 'disabled' : '' }}>
                        <option value="">{{ empty($filterCompany) ? __('payslips.select_company_first') : __('common.all_departments') }}</option>
                        @foreach ($departmentsForFilter as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3 d-flex justify-content-end">
                    @if (!empty($selectedProposals) && $filterStatus === 'validated')
                        <button class="btn btn-sm btn-success d-flex align-items-center gap-1" wire:click="processSelected">
                            <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ __('payslips.process_selected') }}
                            <span class="badge bg-white text-dark ms-1 p-2">{{ count($selectedProposals) }}</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ── Status Tabs ──────────────────────────────────────────── --}}
    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach ([
            'all'       => ['label' => __('common.all'),            'color' => 'secondary', 'count' => null,             'text' => 'text-white'],
            'pending'   => ['label' => __('payslips.pending'),      'color' => 'warning',   'count' => $totalPending,   'text' => 'text-dark'],
            'validated' => ['label' => __('payslips.validated'),    'color' => 'info',      'count' => $totalValidated, 'text' => 'text-dark'],
            'processed' => ['label' => __('payslips.processed'),    'color' => 'success',   'count' => $totalProcessed, 'text' => 'text-white'],
            'rejected'  => ['label' => __('payslips.rejected'),     'color' => 'danger',    'count' => $totalRejected,  'text' => 'text-white'],
        ] as $tabStatus => $tab)
            <button type="button"
                class="btn {{ $filterStatus === $tabStatus ? 'btn-'.$tab['color'] : 'btn-outline-'.$tab['color'] }}"
                wire:click="$set('filterStatus', '{{ $tabStatus }}')">
                {{ $tab['label'] }}
                @if ($tab['count'] !== null)
                    <span class="badge {{ $filterStatus === $tabStatus ? 'bg-gray-500 text-dark' : 'bg-'.$tab['color'].' '.$tab['text'] }} ms-1 p-2">{{ $tab['count'] }}</span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- ── Proposals Table ──────────────────────────────────────── --}}
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="">
                    <tr>
                        @if ($filterStatus === 'validated')
                            <th class="ps-3" style="width:40px">
                                <input type="checkbox" class="form-check-input"
                                    wire:model.live="selectedProposals" value="all">
                            </th>
                        @endif
                        <th>{{ __('payslips.file_name') }}</th>
                        <th>{{ __('payslips.best_match') }}</th>
                        <th>{{ __('common.company') }} / {{ __('common.department') }}</th>
                        <th style="width:120px">{{ __('payslips.match_confidence') }}</th>
                        <th style="width:100px">{{ __('common.period') }}</th>
                        <th style="width:90px">{{ __('common.status') }}</th>
                        <th style="width:90px" class="text-end pe-3">{{ __('common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($proposals as $proposal)
                        @php
                            $bestMatch = $proposal->getBestCandidate();
                        @endphp
                        <tr>
                            @if ($filterStatus === 'validated')
                                <td class="ps-3">
                                    <input type="checkbox" class="form-check-input"
                                        wire:model.live="selectedProposals" value="{{ $proposal->id }}">
                                </td>
                            @endif

                            {{-- File name --}}
                            <td>
                                <div class="fw-semibold small">{{ $proposal->file_name }}</div>
                                <div class="text-muted" style="font-size:11px">
                                    {{ $proposal->file_timestamp?->format('d/m/Y H:i') ?? '-' }}
                                    @if ($proposal->file_size)
                                        · {{ formatBytes($proposal->file_size) }}
                                    @endif
                                </div>
                            </td>

                            {{-- Best match --}}
                            <td>
                                @if ($bestMatch)
                                    <div class="small">
                                        <span class="badge bg-gray-500 text-dark border mb-1 p-2">{{ ucfirst($bestMatch['strategy']) }}</span>
                                        @if (!empty($bestMatch['company_name']))
                                            <div class="fw-semibold">{{ $bestMatch['company_name'] }}</div>
                                        @endif
                                        @if (!empty($bestMatch['department_name']))
                                            <div class="text-muted">{{ $bestMatch['department_name'] }}</div>
                                        @endif
                                    </div>
                                @else
                                    <span class="badge bg-secondary bg-opacity-25 text-dark p-2">{{ __('payslips.no_match') }}</span>
                                @endif
                            </td>

                            {{-- Resolved company / department --}}
                            <td class="small">
                                @if ($proposal->company)
                                    <div class="fw-semibold">{{ $proposal->company->name }}</div>
                                @else
                                    <span class="text-muted fst-italic">{{ __('payslips.unresolved') }}</span>
                                @endif
                                @if ($proposal->department)
                                    <div class="text-muted">{{ $proposal->department->name }}</div>
                                @else
                                    <span class="text-muted" style="font-size:11px">{{ __('payslips.no_department') }}</span>
                                @endif
                            </td>

                            {{-- Confidence bar --}}
                            <td>
                                @if ($bestMatch)
                                    @php $pct = round($bestMatch['confidence'] * 100) @endphp
                                    <div class="d-flex align-items-center gap-1">
                                        <div class="progress flex-grow-1" style="height:6px">
                                            <div class="progress-bar
                                                @if($pct >= 80) bg-success
                                                @elseif($pct >= 50) bg-warning
                                                @else bg-danger @endif"
                                                style="width:{{ $pct }}%"></div>
                                        </div>
                                        <span class="small text-muted">{{ $pct }}%</span>
                                    </div>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            {{-- Period --}}
                            <td class="small text-center">
                                @if ($proposal->matched_month && $proposal->matched_year)
                                    <span class="badge bg-gray-500 text-dark border p-2">
                                        {{ str_pad($proposal->matched_month, 2, '0', STR_PAD_LEFT) }}/{{ $proposal->matched_year }}
                                    </span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            {{-- Status badge --}}
                            <td>
                                @switch($proposal->status)
                                    @case('pending')
                                        <span class="badge bg-warning text-dark p-2">{{ __('payslips.pending') }}</span>
                                        @break
                                    @case('validated')
                                        <span class="badge bg-info text-dark p-2">{{ __('payslips.validated') }}</span>
                                        @break
                                    @case('processed')
                                        <span class="badge bg-success text-white p-2">{{ __('payslips.processed') }}</span>
                                        @break
                                    @case('rejected')
                                        <span class="badge bg-danger text-white p-2">{{ __('payslips.rejected') }}</span>
                                        @break
                                    @case('failed')
                                        <span class="badge bg-dark text-white p-2">{{ __('payslips.failed') }}</span>
                                        @break
                                @endswitch
                            </td>

                            {{-- Actions --}}
                            <td class="text-end pe-3">
                                <div class="d-flex gap-2 justify-content-end align-items-center">
                                    {{-- View --}}
                                    <a href="#" wire:click.prevent="openViewModal('{{ $proposal->id }}')"
                                        class="text-info me-1" title="{{ __('common.view') }}">
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>

                                    @if ($proposal->status === 'pending')
                                        {{-- Re-match --}}
                                        <a href="#"
                                            wire:click.prevent="rematch('{{ $proposal->id }}')"
                                            wire:loading.class="opacity-50 pe-none"
                                            wire:target="rematch('{{ $proposal->id }}')"
                                            class="text-warning me-1" title="{{ __('payslips.rematch') }}">
                                            <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                            </svg>
                                        </a>
                                        {{-- Edit/Validate --}}
                                        <a href="#" wire:click.prevent="editProposal('{{ $proposal->id }}')"
                                            class="text-primary me-1" title="{{ __('payslips.validate') }}">
                                            <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </a>
                                        {{-- Reject --}}
                                        <a href="#" wire:click.prevent="rejectProposal('{{ $proposal->id }}')"
                                            class="text-danger" title="{{ __('common.reject') }}">
                                            <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <svg class="icon icon-lg mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <div>{{ __('common.no_records_found') }}</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($proposals->hasPages())
            <div class="card-footer bg-transparent border-top py-3">
                {{ $proposals->links() }}
            </div>
        @endif
    </div>

    {{-- ─────────────────────────────────────────────────────────── --}}
    {{-- View Modal (single instance, outside table, driven by Livewire) --}}
    {{-- ─────────────────────────────────────────────────────────── --}}
    <div class="modal fade" id="viewProposalModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <svg class="icon text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        {{ $viewingProposal?->file_name ?? __('common.view') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if ($viewingProposal)
                        {{-- File info --}}
                        <div class="row g-3 mb-4">
                            <div class="col-sm-6">
                                <div class="small text-muted mb-1">{{ __('payslips.file_path') }}</div>
                                <div class="small text-break font-monospace">{{ $viewingProposal->file_path }}</div>
                            </div>
                            <div class="col-sm-3">
                                <div class="small text-muted mb-1">{{ __('payslips.file_size') }}</div>
                                <div class="small">{{ formatBytes($viewingProposal->file_size) }}</div>
                            </div>
                            <div class="col-sm-3">
                                <div class="small text-muted mb-1">{{ __('common.period') }}</div>
                                <div class="small">
                                    @if ($viewingProposal->matched_month && $viewingProposal->matched_year)
                                        {{ str_pad($viewingProposal->matched_month, 2, '0', STR_PAD_LEFT) }}/{{ $viewingProposal->matched_year }}
                                    @else -
                                    @endif
                                </div>
                            </div>
                        </div>

                        @php $companyRaw = $viewingProposal->proposed_match['company_raw'] ?? null; @endphp
                        @if ($companyRaw)
                            <div class="mb-3">
                                <div class="small text-muted mb-1">{{ __('payslips.extracted_company_text') }}</div>
                                <div class="p-2 bg-gray-500 rounded border small font-monospace">{{ $companyRaw }}</div>
                            </div>
                        @endif

                        @php $preview = $viewingProposal->proposed_match['raw_text_preview'] ?? null; @endphp
                        @if ($preview)
                            <div class="mb-4">
                                <div class="small text-muted mb-1">{{ __('payslips.raw_text_preview') }}</div>
                                <pre class="p-2 bg-gray-500 rounded border small" style="max-height:150px;overflow-y:auto;white-space:pre-wrap;word-break:break-word;">{{ $preview }}</pre>
                            </div>
                        @endif

                        <hr>

                        <h6 class="text-primary mb-3">{{ __('payslips.match_candidates') }}</h6>
                        @php $candidates = $viewingProposal->proposed_match['candidates'] ?? []; @endphp
                        @forelse ($candidates as $candidate)
                            @php $conf = round($candidate['confidence'] * 100) @endphp
                            <div class="card border mb-2">
                                <div class="card-body py-2 px-3">
                                    <div class="d-flex align-items-start justify-content-between gap-3">
                                        <div class="small">
                                            <span class="badge bg-gray-500 text-dark border me-1 p-2">{{ ucfirst($candidate['strategy']) }}</span>
                                            @if (!empty($candidate['company_name']))
                                                <span class="fw-semibold">{{ $candidate['company_name'] }}</span>
                                            @endif
                                            @if (!empty($candidate['department_name']))
                                                <span class="text-muted ms-1">· {{ $candidate['department_name'] }}</span>
                                            @endif
                                        </div>
                                        <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                            <div class="progress" style="width:80px;height:6px">
                                                <div class="progress-bar
                                                    @if($conf >= 80) bg-success
                                                    @elseif($conf >= 50) bg-warning
                                                    @else bg-danger @endif"
                                                    style="width:{{ $conf }}%"></div>
                                            </div>
                                            <span class="small text-muted">{{ $conf }}%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="alert alert-secondary small py-2">{{ __('payslips.no_matches_found') }}</div>
                        @endforelse
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.close') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ─────────────────────────────────────────────────────────── --}}
    {{-- Edit / Validate Modal --}}
    {{-- ─────────────────────────────────────────────────────────── --}}
    <div class="modal fade" id="editProposalModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title d-flex align-items-center gap-2">
                        <svg class="icon text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        {{ __('payslips.validate_proposal') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form wire:submit="validateMatch">
                    <div class="modal-body">
                        {{-- Company --}}
                        <div class="mb-3">
                            <label for="editCompany" class="form-label">
                                {{ __('common.company') }}<span class="text-danger ms-1">*</span>
                            </label>
                            <select wire:model.live="editingCompanyId" id="editCompany"
                                class="form-select @error('editingCompanyId') is-invalid @enderror" required>
                                <option value="">{{ __('common.select') }}…</option>
                                @foreach ($companies as $comp)
                                    <option value="{{ $comp->id }}">{{ $comp->name }}</option>
                                @endforeach
                            </select>
                            @error('editingCompanyId')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Department — fully reactive, populated from $departments via Livewire --}}
                        <div class="mb-3">
                            <label for="editDept" class="form-label">
                                {{ __('common.department') }}
                                <span class="text-muted small">({{ __('common.optional') }})</span>
                            </label>
                            @if (empty($editingCompanyId))
                                <select class="form-select" disabled>
                                    <option>{{ __('payslips.select_company_first') }}</option>
                                </select>
                            @elseif ($departments->isEmpty())
                                <select class="form-select" disabled>
                                    <option>{{ __('payslips.no_departments_for_company') }}</option>
                                </select>
                                <div class="form-text text-warning">
                                    <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                    {{ __('payslips.payslip_will_process_company_level') }}
                                </div>
                            @else
                                <select wire:model="editingDepartmentId" id="editDept"
                                    class="form-select @error('editingDepartmentId') is-invalid @enderror">
                                    <option value="">{{ __('payslips.all_departments_company_level') }}</option>
                                    @foreach ($departments as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                                @error('editingDepartmentId')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>

                        {{-- Period --}}
                        <div class="row g-2">
                            <div class="col-6">
                                <label for="editMonth" class="form-label">
                                    {{ __('payslips.month') }}<span class="text-danger ms-1">*</span>
                                </label>
                                <select wire:model="editingMonth" id="editMonth"
                                    class="form-select @error('editingMonth') is-invalid @enderror" required>
                                    <option value="">{{ __('common.select') }}…</option>
                                    @for ($i = 1; $i <= 12; $i++)
                                        <option value="{{ $i }}">{{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}</option>
                                    @endfor
                                </select>
                                @error('editingMonth')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-6">
                                <label for="editYear" class="form-label">
                                    {{ __('payslips.year') }}<span class="text-danger ms-1">*</span>
                                </label>
                                <input type="number" wire:model="editingYear" id="editYear"
                                    min="2020" max="2099" placeholder="{{ date('Y') }}"
                                    class="form-control @error('editingYear') is-invalid @enderror" required>
                                @error('editingYear')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" class="btn btn-success">
                            <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ __('payslips.validate_and_process') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ─────────────────────────────────────────────────────────── --}}
    {{-- Reject Modal --}}
    {{-- ─────────────────────────────────────────────────────────── --}}
    <div class="modal fade" id="rejectProposalModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-danger">
                    <h5 class="modal-title d-flex align-items-center gap-2 text-danger">
                        <svg class="icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        {{ __('payslips.reject_proposal') }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form wire:submit="saveRejection">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="rejectReason" class="form-label">
                                {{ __('payslips.reason') }}<span class="text-danger ms-1">*</span>
                            </label>
                            <textarea wire:model="editingReason" id="rejectReason" rows="4"
                                class="form-control @error('editingReason') is-invalid @enderror"
                                placeholder="{{ __('payslips.rejection_reason_placeholder') }}" required></textarea>
                            @error('editingReason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" class="btn btn-danger">{{ __('payslips.reject') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('livewire:initialized', () => {
            const getModal = (id) => {
                const el = document.getElementById(id);
                if (!el) return null;
                return bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
            };

            Livewire.on('openViewModal',   () => getModal('viewProposalModal')?.show());
            Livewire.on('openEditModal',   () => getModal('editProposalModal')?.show());
            Livewire.on('openRejectModal', () => getModal('rejectProposalModal')?.show());
            Livewire.on('closeModals',     () => {
                ['viewProposalModal', 'editProposalModal', 'rejectProposalModal'].forEach(id => {
                    getModal(id)?.hide();
                });
            });
        });
    </script>
    @endpush
</div>
