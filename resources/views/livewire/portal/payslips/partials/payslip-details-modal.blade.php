<div>
    <div class="modal side-layout-modal fade" id="payslipDetailsModal" tabindex="-1" aria-labelledby="payslipDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 700px;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="payslipDetailsModalLabel">
                        <svg class="icon icon-sm me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        {{__('payslips.payslip_details')}}
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="refreshTaskData" title="{{__('common.refresh')}}">
                            <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </button>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body">
                    @if($selectedPayslip)
                    <div class="px-3 pt-1 pb-3">
                        <!-- Employee Information Section -->
                        <div class="mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        {{__('payslips.employee_information')}}
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="text-center">
                                                <div class="d-flex align-items-center justify-content-center mb-3">
                                                <div class="avatar avatar-lg d-flex align-items-center justify-content-center fw-bold rounded bg-primary text-white me-3">
                                                    <span class="h4 mb-0">{{ strtoupper(substr($selectedPayslip->name, 0, 1)) }}</span>
                                                </div>
                                                </div>
                                                <h6 class="mb-1">{{ $selectedPayslip->name }}</h6>
                                                <small class="text-muted">{{__('common.employee')}}</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-flex flex-column gap-2">
                                                <div>
                                                    <strong class="text-sm">{{__('common.email')}}:</strong>
                                                    <p class="mb-0 text-sm">{{ $selectedPayslip->email }}</p>
                                                </div>
                                                @if($selectedPayslip->phone)
                                                <div>
                                                    <strong class="text-sm">{{__('common.phone')}}:</strong>
                                                    <p class="mb-0 text-sm">{{ $selectedPayslip->phone }}</p>
                                                </div>
                                                @endif
                                                <div>
                                                    <strong class="text-sm">{{__('payslips.department')}}:</strong>
                                                    <p class="mb-0 text-sm">{{ $selectedPayslip->sendProcess->department?->name ?? __('payslips.department_deleted') }}</p>
                                                </div>
                                                <div>
                                                    <strong class="text-sm">{{__('common.period')}}:</strong>
                                                    <p class="mb-0 text-sm">{{ $selectedPayslip->sendProcess->month }} {{ $selectedPayslip->sendProcess->year }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Processing Status Section -->
                        <div class="mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                        {{__('payslips.processing_status')}}
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <!-- Encryption Status -->
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="status-indicator {{ $selectedPayslip->encryption_status == 1 ? 'bg-success' : ($selectedPayslip->encryption_status == 2 ? 'bg-danger' : 'bg-warning') }}"></div>
                                                <div>
                                                    <strong class="text-sm">{{__('payslips.encryption')}}</strong>
                                                    <p class="mb-0 text-sm">
                                                        @if($selectedPayslip->encryption_status == 1)
                                                            <span class="badge badge-lg bg-success">{{__('common.successful')}}</span>
                                                        @elseif($selectedPayslip->encryption_status == 2)
                                                            <span class="badge badge-lg bg-danger">{{__('common.failed')}}</span>
                                                        @else
                                                            <span class="badge badge-lg bg-warning">{{__('common.pending')}}</span>
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Email Status -->
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="status-indicator {{ $selectedPayslip->email_sent_status == 1 ? 'bg-success' : ($selectedPayslip->email_sent_status == 2 ? 'bg-danger' : ($selectedPayslip->email_sent_status == 3 ? 'bg-secondary' : 'bg-warning')) }}"></div>
                                                <div>
                                                    <strong class="text-sm">{{__('common.email')}}</strong>
                                                    <p class="mb-0 text-sm">
                                                        @if($selectedPayslip->email_sent_status == 1)
                                                            <span class="badge badge-lg bg-success">{{__('common.sent')}}</span>
                                                        @elseif($selectedPayslip->email_sent_status == 2)
                                                            <span class="badge badge-lg bg-danger">{{__('common.failed')}}</span>
                                                        @elseif($selectedPayslip->email_sent_status == 3)
                                                            <span class="badge badge-lg bg-secondary">{{__('payslips.disabled')}}</span>
                                                        @else
                                                            <span class="badge badge-lg bg-warning">{{__('common.pending')}}</span>
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- SMS Status -->
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="status-indicator {{ $selectedPayslip->sms_sent_status == 1 ? 'bg-success' : ($selectedPayslip->sms_sent_status == 2 ? 'bg-danger' : ($selectedPayslip->sms_sent_status == 3 ? 'bg-secondary' : 'bg-warning')) }}"></div>
                                                <div>
                                                    <strong class="text-sm">{{__('common.sms')}}</strong>
                                                    <p class="mb-0 text-sm">
                                                        @if($selectedPayslip->sms_sent_status == 1)
                                                            <span class="badge badge-lg bg-success">{{__('common.sent')}}</span>
                                                        @elseif($selectedPayslip->sms_sent_status == 2)
                                                            <span class="badge badge-lg bg-danger">{{__('common.failed')}}</span>
                                                        @elseif($selectedPayslip->sms_sent_status == 3)
                                                            <span class="badge badge-lg bg-secondary">{{__('payslips.disabled')}}</span>
                                                        @else
                                                            <span class="badge badge-lg bg-warning">{{__('common.pending')}}</span>
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Overall Status -->
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="status-indicator {{ $this->getPayslipOverallStatus($selectedPayslip) == 'success' ? 'bg-success' : ($this->getPayslipOverallStatus($selectedPayslip) == 'failed' ? 'bg-danger' : 'bg-warning') }}"></div>
                                                <div>
                                                    <strong class="text-sm">{{__('common.overall_status')}}</strong>
                                                    <p class="mb-0 text-sm">
                                                        @if($this->getPayslipOverallStatus($selectedPayslip) == 'success')
                                                            <span class="badge badge-lg bg-success">{{__('common.successful')}}</span>
                                                        @elseif($this->getPayslipOverallStatus($selectedPayslip) == 'failed')
                                                            <span class="badge badge-lg bg-danger">{{__('common.failed')}}</span>
                                                        @else
                                                            <span class="badge badge-lg bg-warning">{{__('common.processing')}}</span>
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Error Details Section -->
                        @if($selectedPayslip->encryption_status == 2 || $selectedPayslip->email_sent_status == 2 || $selectedPayslip->sms_sent_status == 2)
                        <div class="mb-4">
                            <div class="card border-danger">
                                <div class="card-header bg-danger text-white">
                                    <h6 class="mb-0">
                                        <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                        {{__('common.errors')}}
                                    </h6>
                                </div>
                                <div class="card-body">
                                    @if($selectedPayslip->encryption_status_note)
                                    <div class="mb-3">
                                        <strong class="text-danger small">{{__('payslips.encryption_error')}}:</strong>
                                        <p class="text-muted small mb-0">{{ $selectedPayslip->encryption_status_note }}</p>
                                    </div>
                                    @endif

                                    @if($selectedPayslip->email_status_note)
                                    <div class="mb-3">
                                        <strong class="text-danger small">{{__('payslips.email_error_short')}}:</strong>
                                        <p class="text-muted small mb-0">{{ $selectedPayslip->email_status_note }}</p>
                                    </div>
                                    @endif

                                    @if($selectedPayslip->sms_status_note)
                                    <div class="mb-3">
                                        <strong class="text-danger small">{{__('payslips.sms_error')}}:</strong>
                                        <p class="text-muted small mb-0">{{ $selectedPayslip->sms_status_note }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Timeline Section -->
                        <div class="mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        {{__('payslips.payslip_timeline')}}
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="timeline">
                                        <!-- Created -->
                                        <div class="timeline-item">
                                            <div class="timeline-marker bg-primary"></div>
                                            <div class="timeline-content">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1">{{__('payslips.payslip_created')}}</h6>
                                                        <p class="mb-1 text-muted small">{{__('payslips.payslip_added_to_processing_queue')}}</p>
                                                    </div>
                                                    <small class="text-muted">{{ $selectedPayslip->created_at->diffForHumans() }}</small>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Encrypted -->
                                        @if($selectedPayslip->encryption_status == 1)
                                        <div class="timeline-item">
                                            <div class="timeline-marker bg-success"></div>
                                            <div class="timeline-content">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1">{{__('payslips.payslip_encrypted')}}</h6>
                                                        <p class="mb-1 text-muted small">{{__('payslips.payslip_successfully_encrypted')}}</p>
                                                    </div>
                                                    <small class="text-muted">{{ $selectedPayslip->updated_at->diffForHumans() }}</small>
                                                </div>
                                            </div>
                                        </div>
                                        @elseif($selectedPayslip->encryption_status == 2)
                                        <div class="timeline-item">
                                            <div class="timeline-marker bg-danger"></div>
                                            <div class="timeline-content">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1">{{__('payslips.encryption_failed')}}</h6>
                                                        <p class="mb-1 text-muted small">{{__('payslips.failed_to_encrypt_payslip')}}</p>
                                                    </div>
                                                    <small class="text-muted">{{ $selectedPayslip->updated_at->diffForHumans() }}</small>
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        <!-- Email Sent -->
                                        @if($selectedPayslip->email_sent_status == 1)
                                        <div class="timeline-item">
                                            <div class="timeline-marker bg-success"></div>
                                            <div class="timeline-content">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1">{{__('payslips.email_sent')}}</h6>
                                                        <p class="mb-1 text-muted small">{{__('payslips.payslip_email_sent_successfully')}}</p>
                                                    </div>
                                                    <small class="text-muted">{{ $selectedPayslip->updated_at->diffForHumans() }}</small>
                                                </div>
                                            </div>
                                        </div>
                                        @elseif($selectedPayslip->email_sent_status == 2)
                                        <div class="timeline-item">
                                            <div class="timeline-marker bg-danger"></div>
                                            <div class="timeline-content">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1">{{__('payslips.email_failed')}}</h6>
                                                        <p class="mb-1 text-muted small">{{__('payslips.failed_to_send_payslip_email')}}</p>
                                                    </div>
                                                    <small class="text-muted">{{ $selectedPayslip->updated_at->diffForHumans() }}</small>
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        <!-- SMS Sent -->
                                        @if($selectedPayslip->sms_sent_status == 1)
                                        <div class="timeline-item">
                                            <div class="timeline-marker bg-success"></div>
                                            <div class="timeline-content">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1">{{__('payslips.sms_sent')}}</h6>
                                                        <p class="mb-1 text-muted small">{{__('payslips.payslip_sms_sent_successfully')}}</p>
                                                    </div>
                                                    <small class="text-muted">{{ $selectedPayslip->updated_at->diffForHumans() }}</small>
                                                </div>
                                            </div>
                                        </div>
                                        @elseif($selectedPayslip->sms_sent_status == 2)
                                        <div class="timeline-item">
                                            <div class="timeline-marker bg-danger"></div>
                                            <div class="timeline-content">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1">{{__('payslips.sms_failed')}}</h6>
                                                        <p class="mb-1 text-muted small">{{__('payslips.failed_to_send_payslip_sms')}}</p>
                                                    </div>
                                                    <small class="text-muted">{{ $selectedPayslip->updated_at->diffForHumans() }}</small>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="p-5 text-center">
                        <svg class="icon icon-lg text-muted mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6-4h6m2 5.291A7.962 7.962 0 0112 15c-2.34 0-4.29-.98-5.5-2.5"></path>
                        </svg>
                        <h5 class="text-muted">{{__('payslips.no_payslip_selected')}}</h5>
                        <p class="text-muted">{{__('payslips.select_a_payslip_to_view_details')}}</p>
                    </div>
                    @endif
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    @if($selectedPayslip)
                    <div>
                        @if($selectedPayslip->encryption_status == 1 && $selectedPayslip->sendProcess->status === 'processing' && ($selectedPayslip->encryption_status == 0 || $selectedPayslip->email_sent_status == 0 || $selectedPayslip->sms_sent_status == 0))
                        <button type="button" class="btn btn-outline-warning btn-sm" wire:click="resendPayslip({{ $selectedPayslip->id }})">
                            <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            {{__('payslips.retry')}}
                        </button>
                        @endif
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-secondary me-2" data-bs-dismiss="modal">
                            {{__('common.close')}}
                        </button>
                        @if($selectedPayslip && $selectedPayslip->encryption_status == 1 && $selectedPayslip->file_path && Storage::disk('modified')->exists($selectedPayslip->file_path))
                        <a href="{{ route('portal.payslips.download', $selectedPayslip->id) }}" class="btn btn-primary" target="_blank">
                            <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            {{__('common.download')}}
                        </a>
                        @endif
                    </div>
                    @else
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        {{__('common.close')}}
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>