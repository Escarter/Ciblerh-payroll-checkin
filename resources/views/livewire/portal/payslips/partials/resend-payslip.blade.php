<div wire:ignore.self class="modal fade" id="resendPayslipModal" tabindex="-1" role="dialog" aria-labelledby="resendPayslipModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <!-- Modal Header -->
            <div class="modal-header border-0 pb-0">
                <div class="d-flex align-items-center w-100">
                    <div class="flex-shrink-0 me-3">
                        <div class="bg-primary bg-opacity-10 rounded-circle p-3 d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <svg class="icon icon-lg text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                            </svg>
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="modal-title fw-bold mb-1" id="resendPayslipModalLabel">{{__('payslips.resend_payslip_title')}}</h5>
                        <p class="text-muted small mb-0">{{__('payslips.you_are_about_to_resend_email_and_sms_with_employee_payslip')}}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="$set('forceResendEmail', false); $set('forceResendSms', false);"></button>
                </div>
            </div>
            
            <!-- Modal Body -->
            <div class="modal-body pt-4">
                @if($payslip)
                    @php
                        $emailStatus = $payslip->email_sent_status;
                        $smsStatus = $payslip->sms_sent_status;
                        $emailSuccessful = $emailStatus === \App\Models\Payslip::STATUS_SUCCESSFUL;
                        $smsSuccessful = $smsStatus === \App\Models\Payslip::STATUS_SUCCESSFUL;
                        $emailFailed = $emailStatus === \App\Models\Payslip::STATUS_FAILED;
                        $smsFailed = $smsStatus === \App\Models\Payslip::STATUS_FAILED;
                        $emailPending = $emailStatus === \App\Models\Payslip::STATUS_PENDING;
                        $smsPending = $smsStatus === \App\Models\Payslip::STATUS_PENDING;
                    @endphp
                    
                    <!-- Employee Info Card -->
                    <div class="card border mb-4" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <div class="bg-primary bg-opacity-10 rounded-circle p-2 d-inline-flex align-items-center justify-content-center">
                                        <svg class="icon icon-sm text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0 fw-semibold">{{ $payslip->first_name }} {{ $payslip->last_name }}</h6>
                                    <small class="text-muted">{{ $payslip->matricule }} • {{ getMonthName($payslip->month) }} {{ $payslip->year }}</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Resend Options Section -->
                    @if($emailSuccessful || $smsSuccessful || $emailFailed || $smsFailed || $emailPending || $smsPending)
                        <div class="mb-4">
                            <h6 class="fw-semibold mb-3 d-flex align-items-center">
                                <svg class="icon icon-sm me-2 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                {{__('payslips.resend_options')}}
                            </h6>
                            
                            <div class="row g-3">
                                <!-- Email Option -->
                                <div class="col-12">
                                    <div class="card border h-100" style="transition: all 0.3s ease; cursor: pointer;" 
                                         onclick="document.getElementById('forceResendEmail').click()"
                                         onmouseover="this.style.borderColor='#0d6efd'; this.style.boxShadow='0 0 0 0.2rem rgba(13, 110, 253, 0.1)'"
                                         onmouseout="this.style.borderColor=''; this.style.boxShadow=''">
                                        <div class="card-body p-3">
                                            <div class="form-check d-flex align-items-start">
                                                <input class="form-check-input mt-1" type="checkbox" wire:model="forceResendEmail" id="forceResendEmail" 
                                                       @if($emailSuccessful || $emailFailed || $emailPending) checked @endif>
                                                <label class="form-check-label flex-grow-1 ms-3" for="forceResendEmail" style="cursor: pointer;">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div class="d-flex align-items-center">
                                                            <svg class="icon icon-sm me-2 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                                            </svg>
                                                            <span class="fw-semibold">{{__('payslips.resend_email')}}</span>
                                                        </div>
                                                        <div>
                                                            @if($emailSuccessful)
                                                                <span class="badge bg-success bg-opacity-10 text-white border border-success border-opacity-25 px-3 py-2">
                                                                    <svg class="icon icon-xs me-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                                    </svg>
                                                                    {{__('common.successful')}}
                                                                </span>
                                                            @elseif($emailFailed)
                                                                <span class="badge bg-danger bg-opacity-10 text-white border border-danger border-opacity-25 px-3 py-2">
                                                                    <svg class="icon icon-xs me-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                                    </svg>
                                                                    {{__('common.failed')}}
                                                                </span>
                                                            @elseif($emailPending)
                                                                <span class="badge bg-warning bg-opacity-10 text-white border border-warning border-opacity-25 px-3 py-2">
                                                                    <svg class="icon icon-xs me-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                    </svg>
                                                                    {{__('common.pending')}}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- SMS Option -->
                                <div class="col-12">
                                    <div class="card border h-100" style="transition: all 0.3s ease; cursor: pointer;" 
                                         onclick="document.getElementById('forceResendSms').click()"
                                         onmouseover="this.style.borderColor='#0d6efd'; this.style.boxShadow='0 0 0 0.2rem rgba(13, 110, 253, 0.1)'"
                                         onmouseout="this.style.borderColor=''; this.style.boxShadow=''">
                                        <div class="card-body p-3">
                                            <div class="form-check d-flex align-items-start">
                                                <input class="form-check-input mt-1" type="checkbox" wire:model="forceResendSms" id="forceResendSms"
                                                       @if($smsSuccessful || $smsFailed || $smsPending) checked @endif>
                                                <label class="form-check-label flex-grow-1 ms-3" for="forceResendSms" style="cursor: pointer;">
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div class="d-flex align-items-center">
                                                            <svg class="icon icon-sm me-2 text-success" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                                            </svg>
                                                            <span class="fw-semibold">{{__('payslips.resend_sms')}}</span>
                                                        </div>
                                                        <div>
                                                            @if($smsSuccessful)
                                                                <span class="badge bg-success bg-opacity-10 text-white border border-success border-opacity-25 px-3 py-2">
                                                                    <svg class="icon icon-xs me-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                                                    </svg>
                                                                    {{__('common.successful')}}
                                                                </span>
                                                            @elseif($smsFailed)
                                                                <span class="badge bg-danger bg-opacity-10 text-white border border-danger border-opacity-25 px-3 py-2">
                                                                    <svg class="icon icon-xs me-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                                    </svg>
                                                                    {{__('common.failed')}}
                                                                </span>
                                                            @elseif($smsPending)
                                                                <span class="badge bg-warning bg-opacity-10 text-white border border-warning border-opacity-25 px-3 py-2">
                                                                    <svg class="icon icon-xs me-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                    </svg>
                                                                    {{__('common.pending')}}
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Info Note -->
                            <div class="alert alert-light border mt-3 mb-0 d-flex align-items-start" role="alert">
                                <svg class="icon icon-sm text-info me-2 mt-1 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                </svg>
                                <div class="small">
                                    <strong class="d-block mb-1">{{__('payslips.select_what_to_resend')}}</strong>
                                    <span class="text-muted">{{__('payslips.resend_note')}}</span>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
            
            <!-- Modal Footer -->
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal" wire:click="$set('forceResendEmail', false); $set('forceResendSms', false);">
                    {{__('common.cancel')}}
                </button>
                <button type="button" wire:click="resendPayslip" class="btn btn-primary px-4" data-bs-dismiss="modal">
                    <svg class="icon icon-xs me-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    {{__('common.confirm')}}
                </button>
            </div>
        </div>
    </div>
</div>