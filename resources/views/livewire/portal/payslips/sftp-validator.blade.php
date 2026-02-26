<div>
    <x-delete-modal />
    <x-alert />

    <!-- Header Section -->
    <div class='p-0'>
        <div class="d-flex justify-content-between w-100 flex-wrap align-items-center">
            <div class="mb-lg-0">
                <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
                    <ol class="breadcrumb breadcrumb-dark breadcrumb-transparent">
                        <li class="breadcrumb-item">
                            <a href="{{ route('portal.dashboard') }}">
                                <svg class="icon icon-xxs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                            </a>
                        </li>
                        <li class="breadcrumb-item"><a href="/" wire:navigate>{{ __('common.home') }}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ __('payslips.sftp_validator') }}</li>
                    </ol>
                </nav>
                <h1 class="h4 mt-n2 d-flex justify-content-start align-items-end">
                    <svg class="icon me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ __('payslips.sftp_validator') }}
                </h1>
                <p class="mt-n1 mx-2">{{ __('payslips.sftp_validator_description') }}</p>
            </div>
        </div>
    </div>

    <!-- Stats Section -->
    <div class='mb-3 mt-3'>
        <div class='row'>
            <div class="col-12 col-sm-6 col-xl-3 mb-2">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="row d-block d-xl-flex align-items-center">
                            <div class="col-12 col-xl-4 text-xl-center mb-2 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                                <div class="icon-shape icon-shape-warning rounded me-2 me-sm-0">
                                    <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="fw-extrabold h5">{{ __('payslips.total_pending') }}</h2>
                                    <h3 class="mb-1">{{ numberFormat($totalPending) }}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{ __('payslips.total_pending') }}</h2>
                                    <h3 class="fw-extrabold mb-1">{{ numberFormat($totalPending) }}</h3>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3 mb-2">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="row d-block d-xl-flex align-items-center">
                            <div class="col-12 col-xl-4 text-xl-center mb-2 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                                <div class="icon-shape icon-shape-info rounded me-2 me-sm-0">
                                    <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="fw-extrabold h5">{{ __('payslips.total_validated') }}</h2>
                                    <h3 class="mb-1">{{ numberFormat($totalValidated) }}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{ __('payslips.total_validated') }}</h2>
                                    <h3 class="fw-extrabold mb-1">{{ numberFormat($totalValidated) }}</h3>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3 mb-2">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="row d-block d-xl-flex align-items-center">
                            <div class="col-12 col-xl-4 text-xl-center mb-2 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                                <div class="icon-shape icon-shape-success rounded me-2 me-sm-0">
                                    <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="fw-extrabold h5">{{ __('payslips.total_processed') }}</h2>
                                    <h3 class="mb-1">{{ numberFormat($totalProcessed) }}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{ __('payslips.total_processed') }}</h2>
                                    <h3 class="fw-extrabold mb-1">{{ numberFormat($totalProcessed) }}</h3>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3 mb-2">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="row d-block d-xl-flex align-items-center">
                            <div class="col-12 col-xl-4 text-xl-center mb-2 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                                <div class="icon-shape icon-shape-danger rounded me-2 me-sm-0">
                                    <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="fw-extrabold h5">{{ __('payslips.total_rejected') }}</h2>
                                    <h3 class="mb-1">{{ numberFormat($totalRejected) }}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{ __('payslips.total_rejected') }}</h2>
                                    <h3 class="fw-extrabold mb-1">{{ numberFormat($totalRejected) }}</h3>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="pb-0 mt-4">
        <!-- Search and Filter Controls -->
        <div class="row mb-4">
            <div class="col-md-3">
                <input type="text" wire:model.live="searchFilename" placeholder="{{ __('common.search_filename') }}" class="form-control">
            </div>
            <div class="col-md-3">
                <select wire:model.live="filterCompany" class="form-control">
                    <option value="">{{ __('common.all_companies') }}</option>
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select wire:model.live="filterDepartment" class="form-control" {{ empty($filterCompany) ? 'disabled' : '' }}>
                    <option value="">{{ empty($filterCompany) ? __('payslips.select_company_first') : __('common.all_departments') }}</option>
                    @foreach ($departmentsForFilter as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                @if (!empty($selectedProposals) && $filterStatus === 'validated')
                    <button class="btn btn-success d-flex align-items-center" wire:click="processSelected">
                        <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        {{ __('payslips.process_selected') }}
                        <span class="badge bg-light text-dark ms-2 px-2 py-1">{{ count($selectedProposals) }}</span>
                    </button>
                @endif
            </div>
        </div>

        <!-- Filter Status Buttons -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-secondary" wire:click="$set('filterStatus', 'all')" @class(['active' => $filterStatus === 'all'])>
                        {{ __('common.all') }}
                    </button>
                    <button type="button" class="btn btn-outline-warning" wire:click="$set('filterStatus', 'pending')" @class(['active' => $filterStatus === 'pending'])>
                        {{ __('payslips.pending') }} <span class="badge bg-warning ms-1 px-2 py-1">{{ $totalPending }}</span>
                    </button>
                    <button type="button" class="btn btn-outline-info" wire:click="$set('filterStatus', 'validated')" @class(['active' => $filterStatus === 'validated'])>
                        {{ __('payslips.validated') }} <span class="badge bg-info ms-1 px-2 py-1">{{ $totalValidated }}</span>
                    </button>
                    <button type="button" class="btn btn-outline-success" wire:click="$set('filterStatus', 'processed')" @class(['active' => $filterStatus === 'processed'])>
                        {{ __('payslips.processed') }} <span class="badge bg-success ms-1 px-2 py-1">{{ $totalProcessed }}</span>
                    </button>
                    <button type="button" class="btn btn-outline-danger" wire:click="$set('filterStatus', 'rejected')" @class(['active' => $filterStatus === 'rejected'])>
                        {{ __('payslips.rejected') }} <span class="badge bg-danger ms-1 px-2 py-1">{{ $totalRejected }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Proposals Table -->
        <div class="card shadow-md card-raised">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            @if ($filterStatus === 'validated')
                                <th class="ps-3">
                                    <input type="checkbox" wire:model.live="selectedProposals" value="all">
                                </th>
                            @endif
                            <th>{{ __('payslips.file_name') }}</th>
                            <th>{{ __('payslips.best_match') }}</th>
                            <th>{{ __('common.department') }}</th>
                            <th>{{ __('common.company') }}</th>
                            <th>{{ __('payslips.match_confidence') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th>{{ __('common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($proposals as $proposal)
                            <tr>
                                @if ($filterStatus === 'validated')
                                    <td class="ps-3">
                                        <input type="checkbox" wire:model.live="selectedProposals" value="{{ $proposal->id }}">
                                    </td>
                                @endif
                                <td>
                                    <div class="small text-muted">{{ $proposal->file_name }}</div>
                                    <small class="text-secondary">{{ $proposal->file_path }}</small>
                                </td>
                                <td>
                                    @php
                                        $bestMatch = $proposal->getBestCandidate();
                                    @endphp
                                    @if ($bestMatch)
                                        <div>
                                            <strong>{{ $bestMatch['strategy'] }}</strong>
                                            @if (!empty($bestMatch['department_name']))
                                                <br><small>{{ $bestMatch['department_name'] }}</small>
                                            @endif
                                            @if (!empty($bestMatch['company_name']))
                                                <br><small>{{ $bestMatch['company_name'] }}</small>
                                            @endif
                                        </div>
                                    @else
                                        <span class="badge bg-secondary px-2 py-1">{{ __('payslips.no_match') }}</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $proposal->department?->name ?? '-' }}
                                </td>
                                <td>
                                    {{ $proposal->company?->name ?? '-' }}
                                </td>
                                <td>
                                    @if ($bestMatch)
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" role="progressbar" 
                                                style="width: {{ $bestMatch['confidence'] * 100 }}%"
                                                aria-valuenow="{{ $bestMatch['confidence'] * 100 }}" 
                                                aria-valuemin="0" aria-valuemax="100">
                                                {{ round($bestMatch['confidence'] * 100) }}%
                                            </div>
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @switch($proposal->status)
                                        @case('pending')
                                            <span class="badge bg-warning px-2 py-1">{{ __('payslips.pending') }}</span>
                                            @break
                                        @case('validated')
                                            <span class="badge bg-info px-2 py-1">{{ __('payslips.validated') }}</span>
                                            @break
                                        @case('processed')
                                            <span class="badge bg-success px-2 py-1">{{ __('payslips.processed') }}</span>
                                            @break
                                        @case('rejected')
                                            <span class="badge bg-danger px-2 py-1">{{ __('payslips.rejected') }}</span>
                                            @break
                                        @case('failed')
                                            <span class="badge bg-dark px-2 py-1">{{ __('payslips.failed') }}</span>
                                            @break
                                    @endswitch
                                </td>
                                <td>
                                    <!-- View -->
                                    <a href='#' id="view-proposal-{{ $proposal->id }}" 
                                        wire:click.prevent="openViewModal('{{ $proposal->id }}')" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#viewProposalModal-{{ $proposal->id }}"
                                        title="{{ __('common.view') }}">
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    @if ($proposal->status === 'pending')
                                        <!-- Edit/Validate -->
                                        <a href='#' id="validate-proposal-{{ $proposal->id }}" 
                                            wire:click.prevent="editProposal('{{ $proposal->id }}')" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editProposalModal"
                                            title="{{ __('payslips.validate') }}">
                                            <svg class="icon icon-xs text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </a>
                                        <!-- Delete/Reject -->
                                        <a href='#' id="reject-proposal-{{ $proposal->id }}" 
                                            wire:click.prevent="rejectProposal('{{ $proposal->id }}')" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#rejectProposalModal"
                                            title="{{ __('common.reject') }}">
                                            <svg class="icon icon-xs text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </a>
                                    @elseif ($proposal->status === 'rejected')
                                        <span class="badge bg-danger px-2 py-1">{{ __('payslips.rejected') }}</span>
                                    @elseif ($proposal->status === 'failed')
                                        <span class="badge bg-dark px-2 py-1">{{ __('payslips.failed') }}</span>
                                    @endif
                                </td>

                                    <!-- View Modal -->
                                    <div class="modal fade" id="viewProposalModal-{{ $proposal->id }}" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">{{ $proposal->file_name }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <strong>{{ __('payslips.file_path') }}:</strong>
                                                        <div class="text-muted small">{{ $proposal->file_path }}</div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <strong>{{ __('payslips.file_size') }}:</strong>
                                                        {{ formatBytes($proposal->file_size) }}
                                                    </div>
                                                    <hr>
                                                    <h6 class="text-primary mb-3">{{ __('payslips.match_candidates') }}</h6>
                                                    @php
                                                        $candidates = $proposal->proposed_match['candidates'] ?? [];
                                                    @endphp
                                                    @forelse ($candidates as $candidate)
                                                        <div class="card mb-2">
                                                            <div class="card-body">
                                                                <div class="d-flex justify-content-between align-items-start">
                                                                    <div>
                                                                        <strong>{{ ucfirst($candidate['strategy']) }}</strong>
                                                                        @if (!empty($candidate['department_name']))
                                                                            <div class="small">{{ $candidate['department_name'] }}</div>
                                                                        @endif
                                                                        @if (!empty($candidate['company_name']))
                                                                            <div class="small">{{ $candidate['company_name'] }}</div>
                                                                        @endif
                                                                    </div>
                                                                    <span class="badge bg-info px-2 py-1">{{ round($candidate['confidence'] * 100) }}%</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <div class="alert alert-info">{{ __('payslips.no_matches_found') }}</div>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    {{ __('common.no_records_found') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $proposals->links() }}
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editProposalModal" tabindex="-1" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('payslips.validate_proposal') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form wire:submit="validateMatch">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="editCompany" class="form-label">{{ __('common.company') }}<span class="text-danger">*</span></label>
                            <select wire:model.live="editingCompanyId" id="editCompany" class="form-control @error('editingCompanyId') is-invalid @enderror" required>
                                <option value="">{{ __('common.select') }}</option>
                                @foreach ($companies as $comp)
                                    <option value="{{ $comp->id }}">{{ $comp->name }}</option>
                                @endforeach
                            </select>
                            @error('editingCompanyId') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="editDept" class="form-label">{{ __('common.department') }}<span class="text-danger">*</span></label>
                            <select wire:model="editingDepartmentId" id="editDept" class="form-control @error('editingDepartmentId') is-invalid @enderror" required>
                                <option value="">{{ __('common.select') }}</option>
                            </select>
                            @error('editingDepartmentId') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="editMonth" class="form-label">{{ __('payslips.month') }}<span class="text-danger">*</span></label>
                            <select wire:model="editingMonth" id="editMonth" class="form-control @error('editingMonth') is-invalid @enderror" required>
                                <option value="">{{ __('common.select') }}</option>
                                @for ($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }}">{{ \Carbon\Carbon::create()->month($i)->format('F') }}</option>
                                @endfor
                            </select>
                            @error('editingMonth') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="editYear" class="form-label">{{ __('payslips.year') }}<span class="text-danger">*</span></label>
                            <input type="number" wire:model="editingYear" id="editYear" min="2020" max="2099" class="form-control @error('editingYear') is-invalid @enderror" required>
                            @error('editingYear') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                        <button type="submit" class="btn btn-success">{{ __('payslips.validate_and_process') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal fade" id="rejectProposalModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('payslips.reject_proposal') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form wire:submit="saveRejection">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="rejectReason" class="form-label">{{ __('payslips.reason') }}<span class="text-danger">*</span></label>
                            <textarea wire:model="editingReason" id="rejectReason" class="form-control @error('editingReason') is-invalid @enderror" rows="4" required></textarea>
                            @error('editingReason') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
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
            Livewire.on('openEditModal', () => {
                const modal = new bootstrap.Modal(document.getElementById('editProposalModal'));
                modal.show();
            });

            Livewire.on('openRejectModal', () => {
                const modal = new bootstrap.Modal(document.getElementById('rejectProposalModal'));
                modal.show();
            });

            // Update department options when company changes
            const editDeptSelect = document.getElementById('editDept');
            const editCompanySelect = document.getElementById('editCompany');

            if (editCompanySelect) {
                editCompanySelect.addEventListener('change', () => {
                    const companyId = editCompanySelect.value;
                    
                    if (!companyId) {
                        editDeptSelect.innerHTML = '<option value="">{{ __('common.select') }}</option>';
                        editDeptSelect.disabled = true;
                        return;
                    }

                    // Fetch departments for the selected company
                    fetch(`/api/companies/${companyId}/departments`)
                        .then(res => res.json())
                        .then(data => {
                            let html = '<option value="">{{ __('common.select') }}</option>';
                            data.forEach(dept => {
                                html += `<option value="${dept.id}">${dept.name}</option>`;
                            });
                            editDeptSelect.innerHTML = html;
                            editDeptSelect.disabled = false;

                            // Keep the modal open
                            const modalElement = document.getElementById('editProposalModal');
                            if (modalElement.classList.contains('show')) {
                                const modal = new bootstrap.Modal(modalElement);
                                modal.show();
                            }
                        })
                        .catch(err => console.error('Error loading departments:', err));
                });
            }
        });
    </script>
    @endpush
</div>
