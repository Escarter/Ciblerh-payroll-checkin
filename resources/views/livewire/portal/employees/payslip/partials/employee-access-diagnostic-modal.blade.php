<div class="modal fade" id="EmployeePayslipDiagnosticModal" tabindex="-1" aria-labelledby="EmployeePayslipDiagnosticModalLabel" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="EmployeePayslipDiagnosticModalLabel">{{ __('payslips.employee_access_diagnostic') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">{{ __('payslips.employee_access_diagnostic_description') }}</p>

                @if($diagnosticRan && !empty($employeeDiagnostic['summary']))
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">{{ __('payslips.linked_to_employee') }}</div>
                            <div class="h4 mb-0">{{ number_format($employeeDiagnostic['summary']['linked_count'] ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">{{ __('payslips.visible_on_employee_dashboard') }}</div>
                            <div class="h4 mb-0">{{ number_format($employeeDiagnostic['summary']['visible_count'] ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 h-100">
                            <div class="text-muted small">{{ __('common.deleted') }}</div>
                            <div class="h4 mb-0">{{ number_format($employeeDiagnostic['summary']['deleted_count'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>

                @if(!empty($employeeDiagnostic['issues']))
                <div class="list-group mb-3">
                    @foreach($employeeDiagnostic['issues'] as $issue)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <span class="badge bg-{{ $issue['severity'] === 'danger' ? 'danger' : ($issue['severity'] === 'warning' ? 'warning text-dark' : ($issue['severity'] === 'success' ? 'success' : 'info')) }}">
                                    {{ __('payslips.diagnostic_severity_' . $issue['severity']) }}
                                </span>
                                <div class="mt-2">{{ $issue['message'] }}</div>
                            </div>
                        </div>
                        @if(!empty($issue['details']))
                        <ul class="small text-muted mb-0 mt-2">
                            @foreach($issue['details'] as $detail)
                            <li>
                                @if(isset($detail['email']))
                                    #{{ $detail['id'] }} — {{ $detail['email'] }} ({{ $detail['active_payslips'] ?? 0 }} {{ __('common.payslips') }})
                                @elseif(isset($detail['payslip_matricule']))
                                    #{{ $detail['id'] }} — {{ $detail['month'] }}/{{ $detail['year'] }} ({{ $detail['payslip_matricule'] }} vs {{ $detail['employee_matricule'] }})
                                @elseif(isset($detail['month']))
                                    #{{ $detail['id'] }} — {{ $detail['month'] }}/{{ $detail['year'] }}
                                @else
                                    {{ json_encode($detail) }}
                                @endif
                            </li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
                @else
                <div class="text-center text-muted py-4">
                    {{ __('payslips.run_diagnostic_to_see_results') }}
                </div>
                @endif
            </div>
            <div class="modal-footer flex-wrap gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.close') }}</button>
                <button type="button" class="btn btn-outline-primary" wire:click="runEmployeePayslipDiagnostic">
                    {{ __('payslips.run_diagnostic') }}
                </button>
                @if($diagnosticRan && ($employeeDiagnostic['can_relink'] ?? false))
                <button type="button" class="btn btn-warning" wire:click="relinkEmployeePayslips">
                    {{ __('payslips.relink_payslips_to_employee') }}
                </button>
                @endif
                @if($diagnosticRan && ($employeeDiagnostic['can_restore_deleted'] ?? false))
                <button type="button" class="btn btn-info" wire:click="restoreEmployeeDeletedPayslips">
                    {{ __('payslips.restore_deleted_payslips') }}
                </button>
                @endif
                @if($diagnosticRan && ($employeeDiagnostic['can_encrypt'] ?? false))
                <button type="button" class="btn btn-success" wire:click="encryptEmployeePayslips">
                    {{ __('payslips.encrypt_unencrypted_payslips') }}
                </button>
                @endif
            </div>
        </div>
    </div>
</div>
