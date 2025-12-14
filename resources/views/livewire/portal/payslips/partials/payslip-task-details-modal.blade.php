<div wire:key="payslip-task-details-root">

<style>
.card-stats {
    transition: all 0.2s ease-in-out;
    border-width: 2px !important;
}

.card-stats:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.progress {
    border-radius: 6px;
    overflow: hidden;
}

.progress-bar {
    transition: width 0.3s ease;
}

.card.bg-light {
    border: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.2s ease-in-out;
}

.card.bg-light:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}

@media (max-width: 768px) {
    .card-stats {
        margin-bottom: 1rem;
    }

    .card.bg-light {
        margin-bottom: 0.75rem;
    }

    /* Task Overview & Information responsive improvements */
    .task-info-item {
        margin-bottom: 1rem;
    }

    .task-info-item .d-flex {
        flex-direction: column;
        text-align: center;
    }

    .task-info-item .bg-secondary,
    .task-info-item .bg-warning,
    .task-info-item .bg-primary,
    .task-info-item .bg-info {
        margin: 0 auto 0.5rem auto;
        width: 48px !important;
        height: 48px !important;
    }

    .task-overview-card .col-lg-6 {
        margin-bottom: 1rem;
    }
}

/* Timeline Styles */
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e5e7eb;
}

.timeline-sm {
    padding-left: 20px;
}

.timeline-sm::before {
    left: 9px;
    width: 2px;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-marker {
    position: absolute;
    left: -27px;
    top: 4px;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 3px solid #fff;
    box-shadow: 0 0 0 2px #e5e7eb;
}

.timeline-sm .timeline-marker {
    left: -15px;
    width: 10px;
    height: 10px;
}

.timeline-content {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 12px 16px;
    border-left: 3px solid #007bff;
}

.timeline-item .timeline-content {
    border-left-color: #007bff;
}

.progress-sm {
    height: 4px !important;
}

.fs-7 {
    font-size: 0.75rem !important;
}
</style>

    <div class="modal side-layout-modal fade"
        id="payslipTaskDetailsModal"
        tabindex="-1"
        aria-labelledby="payslipTaskDetailsModalLabel"
        aria-hidden="true"
        wire:ignore.self>

        <div class="modal-dialog modal-dialog-centered" style="max-width: 800px;">
            <div class="modal-content">

                {{-- ================= HEADER ================= --}}
                <div class="modal-header">
                    <h5 class="modal-title" id="payslipTaskDetailsModalLabel">
                        <svg class="icon icon-sm me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        {{ __('payslips.payslip_task_details') }}
                    </h5>

                    <div class="d-flex align-items-center gap-2">
                        <button type="button"
                            class="btn btn-sm btn-outline-secondary"
                            wire:click="refreshTaskData"
                            title="{{ __('common.refresh') }}">
                            <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                        </button>

                        <button type="button"
                            class="btn-close"
                            wire:click="closeTaskDetailsModal"
                            data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                </div>

                {{-- ================= BODY ================= --}}
                <div class="modal-body">

                    @if ($selectedProcess)

                    <div class="px-3 pt-1 pb-3">

                        {{-- ================= TASK OVERVIEW ================= --}}
                        <div class="mb-4">
                            <div class="card border-0 shadow-sm task-overview-card">
                                <div class="card-header bg-light border-0 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary rounded-circle p-2 me-3">
                                            <svg class="icon icon-sm text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                            </svg>
                                        </div>
                                        <h6 class="mb-0 fw-bold text-primary">{{ __('payslips.task_overview') }}</h6>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row g-4">
                                        {{-- Total Payslips --}}
                                        <div class="col-lg-6 col-md-12">
                                            <div class="d-flex align-items-center p-3 bg-primary bg-opacity-5 rounded-3 border border-primary border-opacity-10">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="bg-primary rounded-circle p-3">
                                                        <svg class="icon icon-lg text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                        </svg>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="h2 text-white mb-1 fw-bold">
                                                        {{ number_format($selectedProcess->payslips()->count()) }}
                                                    </div>
                                                    <small class="text-muted fw-medium">{{ __('payslips.total_payslips') }}</small>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Target Employees --}}
                                        <div class="col-lg-6 col-md-12">
                                            <div class="d-flex align-items-center p-3 bg-success bg-opacity-5 rounded-3 border border-success border-opacity-10">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="bg-success rounded-circle p-3">
                                                        <svg class="icon icon-lg text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                                        </svg>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="h2 text-white mb-1 fw-bold">
                                                        {{ optional($selectedProcess->department)->employees->count() ?? 'N/A' }}
                                                    </div>
                                                    <small class="text-muted fw-medium">{{ __('payslips.target_employees') }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ================= TASK INFORMATION ================= --}}
                        <div class="mb-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light border-0 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-info rounded-circle p-2 me-3">
                                            <svg class="icon icon-sm text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <h6 class="mb-0 fw-bold text-dark">{{ __('payslips.task_information') }}</h6>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        {{-- Department --}}
                                        <div class="col-md-6 task-info-item">
                                            <div class="d-flex align-items-start">
                                                <div class="bg-secondary rounded-circle p-2 me-3 flex-shrink-0" style="width: 40px; height: 40px;">
                                                    <svg class="icon icon-sm text-white w-100 h-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                    </svg>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <small class="text-muted d-block fw-medium">{{ __('departments.department') }}</small>
                                                    <span class="fw-semibold text-dark">
                                                        {{ optional($selectedProcess->department)->name ?? __('payslips.department_deleted') }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Period --}}
                                        <div class="col-md-6 task-info-item">
                                            <div class="d-flex align-items-start">
                                                <div class="bg-warning rounded-circle p-2 me-3 flex-shrink-0" style="width: 40px; height: 40px;">
                                                    <svg class="icon icon-sm text-white w-100 h-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <small class="text-muted d-block fw-medium">{{ __('common.period') }}</small>
                                                    <span class="fw-semibold text-dark">
                                                        {{ $selectedProcess->month }} {{ $selectedProcess->year }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Created By --}}
                                        <div class="col-md-6 task-info-item">
                                            <div class="d-flex align-items-start">
                                                <div class="bg-primary rounded-circle p-2 me-3 flex-shrink-0" style="width: 40px; height: 40px;">
                                                    <svg class="icon icon-sm text-white w-100 h-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                    </svg>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <small class="text-muted d-block fw-medium">{{ __('common.created_by') }}</small>
                                                    <span class="fw-semibold text-dark">
                                                        {{ optional($selectedProcess->owner)->first_name ?? __('common.unknown') }}
                                                        @if($selectedProcess->owner && $selectedProcess->owner->last_name)
                                                            {{ $selectedProcess->owner->last_name }}
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Created At --}}
                                        <div class="col-md-6 task-info-item">
                                            <div class="d-flex align-items-start">
                                                <div class="bg-info rounded-circle p-2 me-3 flex-shrink-0" style="width: 40px; height: 40px;">
                                                    <svg class="icon icon-sm text-white w-100 h-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <small class="text-muted d-block fw-medium">{{ __('common.created_at') }}</small>
                                                    <span class="fw-semibold text-dark">
                                                        {{ $selectedProcess->created_at->format('M d, Y') }}
                                                    </span>
                                                    <small class="text-muted d-block">
                                                        {{ $selectedProcess->created_at->format('H:i') }}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- ================= TASK STATUS BANNER ================= --}}
                        <div class="mb-4">
                            @php
                                $statusConfig = [
                                    'pending' => ['color' => 'warning', 'icon' => 'clock', 'text' => __('payslips.status_pending')],
                                    'processing' => ['color' => 'primary', 'icon' => 'cog', 'text' => __('payslips.status_processing')],
                                    'successful' => ['color' => 'success', 'icon' => 'check-circle', 'text' => __('payslips.status_successful')],
                                    'failed' => ['color' => 'danger', 'icon' => 'x-circle', 'text' => __('payslips.status_failed')],
                                    'cancelled' => ['color' => 'secondary', 'icon' => 'x', 'text' => __('payslips.status_cancelled')]
                                ];
                                $currentStatus = $statusConfig[$selectedProcess->status] ?? $statusConfig['pending'];
                                $timeElapsed = $selectedProcess->created_at->diffForHumans();
                            @endphp
                            <div class="alert alert-{{ $currentStatus['color'] }} d-flex align-items-center mb-0">
                                <svg class="icon icon-lg me-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if($currentStatus['icon'] === 'clock')
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12,6 12,12 16,14"></polyline>
                                    @elseif($currentStatus['icon'] === 'cog')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    @elseif($currentStatus['icon'] === 'check-circle')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    @elseif($currentStatus['icon'] === 'x-circle')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    @endif
                                </svg>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">{{ $currentStatus['text'] }}</h6>
                                    <small class="text-muted">{{ __('payslips.started') }} {{ $timeElapsed }}</small>
                                </div>
                                @if($selectedProcess->status === 'processing')
                                    <div class="spinner-border spinner-border-sm text-{{ $currentStatus['color'] }}" role="status">
                                        <span class="visually-hidden">{{ __('common.loading') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- ================= STATISTICS & PROGRESS ================= --}}
                        <div class="mb-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white border-0 pb-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 fw-bold text-dark">{{ __('payslips.progress_statistics') }}</h6>
                                        @php
                                            $stats = $this->getTaskStatistics();
                                        @endphp
                                        @if(($stats['failed_percentage'] ?? 0) > 10)
                                            <span class="badge bage bg-danger fs-7" data-bs-toggle="tooltip" title="{{ __('payslips.high_failure_rate_detected') }}">
                                                <svg class="icon icon-xs me-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                </svg>
                                                {{ __('payslips.anomaly') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="card-body">

                                    {{-- Enhanced Stats Dashboard --}}
                                    <div class="row g-3 mb-4">
                                        {{-- Total Payslips --}}
                                        <div class="col-lg-3 col-md-6">
                                            <div class="card h-100 border-2 border-primary border-opacity-25 card-stats">
                                                <div class="card-body text-center p-4">
                                                    <div class="d-flex align-items-center justify-content-center mb-2">
                                                        <svg class="icon icon-lg text-primary me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                                        </svg>
                                                        <span class="h2 text-primary mb-0 fw-bold">{{ number_format($stats['total'] ?? 0) }}</span>
                                                    </div>
                                                    <small class="text-muted fw-medium">{{ __('payslips.total_payslips') }}</small>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Delivered Successfully --}}
                                        <div class="col-lg-3 col-md-6">
                                            <div class="card h-100 border-2 border-success border-opacity-25 card-stats">
                                                <div class="card-body text-center p-4">
                                                    <div class="d-flex align-items-center justify-content-center mb-2">
                                                        <svg class="icon icon-lg text-success me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        <span class="h2 text-success mb-0 fw-bold">{{ number_format($stats['successful'] ?? 0) }}</span>
                                                    </div>
                                                    <div class="progress progress-sm mb-2" style="height: 4px;">
                                                        <div class="progress-bar bg-success" style="width: {{ $stats['successful_percentage'] ?? 0 }}%"></div>
                                                    </div>
                                                    <small class="text-muted fw-medium">{{ __('payslips.delivered_successfully') }}</small>
                                                    <div class="mt-1">
                                                        <small class="text-success fw-bold">{{ number_format($stats['successful_percentage'] ?? 0, 1) }}%</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Failed --}}
                                        <div class="col-lg-3 col-md-6">
                                            <div class="card h-100 border-2 {{ ($stats['failed_percentage'] ?? 0) > 10 ? 'border-danger border-opacity-50 bg-light' : 'border-danger border-opacity-25' }} card-stats">
                                                <div class="card-body text-center p-4">
                                                    <div class="d-flex align-items-center justify-content-center mb-2">
                                                        <svg class="icon icon-lg {{ ($stats['failed_percentage'] ?? 0) > 10 ? 'text-danger' : 'text-danger' }} me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                                        </svg>
                                                        <span class="h2 {{ ($stats['failed_percentage'] ?? 0) > 10 ? 'text-danger' : 'text-danger' }} mb-0 fw-bold">{{ number_format($stats['failed'] ?? 0) }}</span>
                                                    </div>
                                                    <div class="progress progress-sm mb-2" style="height: 4px;">
                                                        <div class="progress-bar {{ ($stats['failed_percentage'] ?? 0) > 10 ? 'bg-danger' : 'bg-danger' }}" style="width: {{ $stats['failed_percentage'] ?? 0 }}%"></div>
                                                    </div>
                                                    <small class="text-muted fw-medium">{{ __('payslips.failed') }}</small>
                                                    <div class="mt-1">
                                                        <small class="{{ ($stats['failed_percentage'] ?? 0) > 10 ? 'text-danger fw-bold' : 'text-danger fw-bold' }}">{{ number_format($stats['failed_percentage'] ?? 0, 1) }}%</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Pending/Processing --}}
                                        <div class="col-lg-3 col-md-6">
                                            <div class="card h-100 border-2 border-warning border-opacity-25 card-stats">
                                                <div class="card-body text-center p-4">
                                                    <div class="d-flex align-items-center justify-content-center mb-2">
                                                        <svg class="icon icon-lg text-warning me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        <span class="h2 text-warning mb-0 fw-bold">{{ number_format(($stats['pending'] ?? 0) + ($stats['processing'] ?? 0)) }}</span>
                                                    </div>
                                                    <div class="progress progress-sm mb-2" style="height: 4px;">
                                                        <div class="progress-bar bg-warning" style="width: {{ ($stats['pending_percentage'] ?? 0) + ($stats['processing_percentage'] ?? 0) }}%"></div>
                                                    </div>
                                                    <small class="text-muted fw-medium">{{ __('payslips.pending_processing') }}</small>
                                                    <div class="mt-1">
                                                        <small class="text-warning fw-bold">{{ number_format(($stats['pending_percentage'] ?? 0) + ($stats['processing_percentage'] ?? 0), 1) }}%</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Enhanced Progress Visualization --}}
                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h6 class="mb-0 fw-semibold text-dark">{{ __('payslips.overall_progress') }}</h6>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge badge-lg bg-primary fs-7">{{ number_format($this->getTaskProgressPercentage(), 1) }}% {{ __('common.complete') }}</span>
                                                @if($selectedProcess->status === 'processing')
                                                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                                                        <span class="visually-hidden">{{ __('common.loading') }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Main Progress Bar --}}
                                        <div class="progress mb-3" style="height: 16px; border-radius: 8px; box-shadow: inset 0 1px 2px rgba(0,0,0,.1);">
                                            @if(($stats['successful_percentage'] ?? 0) > 0)
                                                <div class="progress-bar bg-success progress-bar-striped"
                                                    style="width: {{ $stats['successful_percentage'] ?? 0 }}%"
                                                    data-bs-toggle="tooltip"
                                                    title="{{ __('payslips.delivered_successfully') }}: {{ number_format($stats['successful'] ?? 0) }} ({{ number_format($stats['successful_percentage'] ?? 0, 1) }}%)">
                                                    <span class="fw-bold">{{ number_format($stats['successful_percentage'] ?? 0, 0) }}%</span>
                                                </div>
                                            @endif
                                            @if(($stats['processing_percentage'] ?? 0) > 0)
                                                <div class="progress-bar bg-primary"
                                                    style="width: {{ $stats['processing_percentage'] ?? 0 }}%"
                                                    data-bs-toggle="tooltip"
                                                    title="{{ __('payslips.processing') }}: {{ number_format($stats['processing'] ?? 0) }} ({{ number_format($stats['processing_percentage'] ?? 0, 1) }}%)">
                                                    <span class="fw-bold">{{ number_format($stats['processing_percentage'] ?? 0, 0) }}%</span>
                                                </div>
                                            @endif
                                            @if(($stats['pending_percentage'] ?? 0) > 0)
                                                <div class="progress-bar bg-warning"
                                                    style="width: {{ $stats['pending_percentage'] ?? 0 }}%"
                                                    data-bs-toggle="tooltip"
                                                    title="{{ __('payslips.pending') }}: {{ number_format($stats['pending'] ?? 0) }} ({{ number_format($stats['pending_percentage'] ?? 0, 1) }}%)">
                                                    <span class="fw-bold">{{ number_format($stats['pending_percentage'] ?? 0, 0) }}%</span>
                                                </div>
                                            @endif
                                            @if(($stats['failed_percentage'] ?? 0) > 0)
                                                <div class="progress-bar bg-danger"
                                                    style="width: {{ $stats['failed_percentage'] ?? 0 }}%"
                                                    data-bs-toggle="tooltip"
                                                    title="{{ __('payslips.failed') }}: {{ number_format($stats['failed'] ?? 0) }} ({{ number_format($stats['failed_percentage'] ?? 0, 1) }}%)">
                                                    <span class="fw-bold">{{ number_format($stats['failed_percentage'] ?? 0, 0) }}%</span>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Progress Legend --}}
                                        <div class="row g-2">
                                            <div class="col-6 col-md-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-success rounded me-2" style="width: 12px; height: 12px;"></div>
                                                    <small class="text-muted">{{ __('payslips.delivered_successfully') }}</small>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-primary rounded me-2" style="width: 12px; height: 12px;"></div>
                                                    <small class="text-muted">{{ __('payslips.processing') }}</small>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-warning rounded me-2" style="width: 12px; height: 12px;"></div>
                                                    <small class="text-muted">{{ __('payslips.pending') }}</small>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-danger rounded me-2" style="width: 12px; height: 12px;"></div>
                                                    <small class="text-muted">{{ __('payslips.failed') }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Performance Metrics Dashboard --}}
                                    <div class="row g-3">
                                        @php
                                            $advancedStats = $this->getAdvancedTaskStatistics();
                                        @endphp

                                        <div class="col-lg-6">
                                            <div class="card border-0 bg-light h-100">
                                                <div class="card-body p-3">
                                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                                        <div class="d-flex align-items-center">
                                                            <div class="bg-info rounded-circle p-2 me-3">
                                                                <svg class="icon icon-sm text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <circle cx="12" cy="12" r="10"></circle>
                                                                    <polyline points="12,6 12,12 16,14"></polyline>
                                                                </svg>
                                                            </div>
                                                            <div>
                                                                <small class="text-muted d-block">{{ __('payslips.estimated_completion_time') }}</small>
                                                                <span class="fw-bold text-dark">{{ $advancedStats['estimated_completion_time'] ?? 'N/A' }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div class="d-flex align-items-center">
                                                            <div class="bg-primary rounded-circle p-2 me-3">
                                                                <svg class="icon icon-sm text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                                </svg>
                                                            </div>
                                                            <div>
                                                                <small class="text-muted d-block">{{ __('payslips.avg_processing_time') }}</small>
                                                                <span class="fw-bold text-dark">{{ $advancedStats['average_processing_time'] ?? 'N/A' }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-lg-6">
                                            <div class="card border-0 bg-light h-100">
                                                <div class="card-body p-3">
                                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                                        <div class="d-flex align-items-center">
                                                            <div class="bg-danger rounded-circle p-2 me-3">
                                                                <svg class="icon icon-sm text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                                                </svg>
                                                            </div>
                                                            <div>
                                                                <small class="text-muted d-block">{{ __('payslips.first_error_timestamp') }}</small>
                                                                <span class="fw-bold text-dark">{{ $advancedStats['first_error_timestamp'] ?? 'N/A' }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div class="d-flex align-items-center">
                                                            <div class="bg-success rounded-circle p-2 me-3">
                                                                <svg class="icon icon-sm text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                                </svg>
                                                            </div>
                                                            <div>
                                                                <small class="text-muted d-block">{{ __('payslips.last_success_timestamp') }}</small>
                                                                <span class="fw-bold text-dark">{{ $advancedStats['last_success_timestamp'] ?? 'N/A' }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        {{-- ================= TASK TIMELINE & ACTIVITY ================= --}}
                        <div class="mb-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white border-0 pb-0">
                                    <h6 class="mb-0 fw-bold text-dark">{{ __('payslips.task_timeline') }}</h6>
                                </div>
                                <div class="card-body">
                                    @php
                                        $timeline = $this->getTaskTimeline();
                                        $activities = $this->getRecentActivity();
                                    @endphp

                                    {{-- Timeline --}}
                                    <div class="timeline timeline-sm mb-4">
                                        @foreach($timeline as $event)
                                            <div class="timeline-item">
                                                <div class="timeline-marker bg-{{ $event['type'] }}"></div>
                                                <div class="timeline-content">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <h6 class="mb-1 fw-semibold">{{ $event['title'] }}</h6>
                                                            <p class="mb-0 text-muted small">{{ $event['description'] }}</p>
                                                        </div>
                                                        <small class="text-muted">{{ $event['time'] }}</small>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    {{-- Recent Activity --}}
                                    @if($activities)
                                        <div class="border-top pt-3">
                                            <h6 class="mb-3 fw-semibold text-dark">{{ __('payslips.recent_activity') }}</h6>
                                            <div class="row g-2">
                                                @foreach(array_slice($activities, 0, 6) as $activity)
                                                    <div class="col-md-6">
                                                        <div class="d-flex align-items-start p-2 rounded bg-light">
                                                            <div class="me-3">
                                                                @if($activity['type'] === 'success')
                                                                    <div class="bg-success rounded-circle p-1">
                                                                        <svg class="icon icon-xs text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                                        </svg>
                                                                    </div>
                                                                @elseif($activity['type'] === 'error')
                                                                    <div class="bg-danger rounded-circle p-1">
                                                                        <svg class="icon icon-xs text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                                        </svg>
                                                                    </div>
                                                                @else
                                                                    <div class="bg-primary rounded-circle p-1">
                                                                        <svg class="icon icon-xs text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                                        </svg>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            <div class="flex-grow-1">
                                                                <small class="fw-medium text-dark">{{ $activity['title'] }}</small>
                                                                <p class="mb-0 text-muted small">{{ $activity['description'] }}</p>
                                                                <small class="text-muted">{{ $activity['time'] }}</small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- ================= PERFORMANCE METRICS ================= --}}
                        <div class="mb-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-white border-0 pb-0">
                                    <h6 class="mb-0 fw-bold text-dark">{{ __('payslips.performance_metrics') }}</h6>
                                </div>
                                <div class="card-body">
                                    @php
                                        $performanceMetrics = $this->getPerformanceMetrics();
                                    @endphp

                                    <div class="row g-3">
                                        {{-- Throughput Rate --}}
                                        <div class="col-lg-4 col-md-6">
                                            <div class="text-center p-3 bg-light rounded">
                                                <div class="d-flex align-items-center justify-content-center mb-2">
                                                    <svg class="icon icon-lg text-primary me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                                    </svg>
                                                    <span class="h4 text-primary mb-0 fw-bold">{{ $performanceMetrics['throughput_rate'] ?? 'N/A' }}</span>
                                                </div>
                                                <small class="text-muted">{{ __('payslips.throughput_rate') }}</small>
                                            </div>
                                        </div>

                                        {{-- Success Rate --}}
                                        <div class="col-lg-4 col-md-6">
                                            <div class="text-center p-3 bg-light rounded">
                                                <div class="d-flex align-items-center justify-content-center mb-2">
                                                    <svg class="icon icon-lg text-success me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                                    </svg>
                                                    <span class="h4 text-success mb-0 fw-bold">{{ $performanceMetrics['success_rate'] ?? '0%' }}</span>
                                                </div>
                                                <small class="text-muted">{{ __('payslips.success_rate') }}</small>
                                            </div>
                                        </div>

                                        {{-- Processing Efficiency --}}
                                        <div class="col-lg-4 col-md-6">
                                            <div class="text-center p-3 bg-light rounded">
                                                <div class="d-flex align-items-center justify-content-center mb-2">
                                                    <svg class="icon icon-lg text-info me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                                    </svg>
                                                    <span class="h4 text-info mb-0 fw-bold">{{ $performanceMetrics['processing_efficiency'] ?? '0%' }}</span>
                                                </div>
                                                <small class="text-muted">{{ __('payslips.processing_efficiency') }}</small>
                                            </div>
                                        </div>

                                        {{-- Error Rate --}}
                                        <div class="col-lg-4 col-md-6">
                                            <div class="text-center p-3 bg-light rounded">
                                                <div class="d-flex align-items-center justify-content-center mb-2">
                                                    <svg class="icon icon-lg text-danger me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                                                    </svg>
                                                    <span class="h4 text-danger mb-0 fw-bold">{{ $performanceMetrics['error_rate'] ?? '0%' }}</span>
                                                </div>
                                                <small class="text-muted">{{ __('payslips.error_rate') }}</small>
                                            </div>
                                        </div>

                                        {{-- Items per Minute --}}
                                        <div class="col-lg-4 col-md-6">
                                            <div class="text-center p-3 bg-light rounded">
                                                <div class="d-flex align-items-center justify-content-center mb-2">
                                                    <svg class="icon icon-lg text-warning me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    <span class="h4 text-warning mb-0 fw-bold">{{ $performanceMetrics['avg_items_per_minute'] ?? '0' }}</span>
                                                </div>
                                                <small class="text-muted">{{ __('payslips.avg_per_minute') }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    @else
                    <div class="p-5 text-center">
                        <h5 class="text-muted">{{ __('payslips.no_task_selected') }}</h5>
                        <p class="text-muted">{{ __('payslips.select_a_task_to_view_details') }}</p>
                    </div>
                    @endif

                </div>

                {{-- ================= FOOTER ================= --}}
                <div class="modal-footer d-flex justify-content-between">

                    @if ($selectedProcess && $selectedProcess->status === 'processing')
                    <button type="button"
                        class="btn btn-outline-danger"
                        wire:click="cancelTask({{ $selectedProcess->id }})">
                        {{ __('common.cancel') }}
                    </button>
                    @endif

                    <button type="button"
                        class="btn btn-outline-secondary"
                        wire:click="closeTaskDetailsModal"
                        data-bs-dismiss="modal">
                        {{ __('common.close') }}
                    </button>
                </div>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:loaded', function () {
            // Initialize tooltips when modal is shown
            const modal = document.getElementById('payslipTaskDetailsModal');
            if (modal) {
                modal.addEventListener('shown.bs.modal', function () {
                    // Initialize tooltips
                    const tooltipTriggerList = [].slice.call(modal.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                        return new bootstrap.Tooltip(tooltipTriggerEl);
                    });
                });
            }
        });
    </script>

</div>