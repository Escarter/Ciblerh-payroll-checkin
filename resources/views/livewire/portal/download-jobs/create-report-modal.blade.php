<!-- Create Report Modal -->
<div wire:ignore.self class="modal side-layout-modal fade" id="createReportModal" tabindex="-1" aria-labelledby="modal-form" data-bs-backdrop="static" data-bs-keyboard="false" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered " role="document" style="max-width:65%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{__('Generate New Report')}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" wire:click="closeCreateModal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0">
                        <p>{{__('Generate reports and download files')}} &#128522;</p>
                    </div>
                <form wire:submit.prevent="generateNewReport">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="reportType" class="form-label">{{__('Report Type')}} <span class="text-danger">*</span></label>
                                <select wire:model.live="newReport.job_type" class="form-select" id="reportType" required>
                                    <option value="">{{__('Select Report Type')}}</option>
                                    @foreach($availableJobTypes as $type => $label)
                                    <option value="{{$type}}">{{$label}}</option>
                                    @endforeach
                                </select>
                                @error('newReport.job_type') <span class="text-danger">{{$message}}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="reportFormat" class="form-label">{{__('Format')}}</label>
                                <select wire:model.live="newReport.format" class="form-select" id="reportFormat">
                                    <option value="xlsx">{{__('Excel (XLSX)')}}</option>
                                    <option value="pdf">{{__('PDF')}}</option>
                                    <option value="zip">{{__('ZIP Archive')}}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic filters based on report type -->
                    @if($newReport['job_type'] ?? false)
                    <div class="mb-3">
                        <h6>{{__('Report Filters')}}</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">{{__('companies.company')}}</label>
                                <select wire:model.live="newReport.filters.selectedCompanyId" class="form-select">
                                    <option value="all">{{__('All Companies')}}</option>
                                    @foreach($companies as $company)
                                    <option value="{{$company->id}}">{{$company->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{__('departments.department')}}</label>
                                <select wire:model.live="newReport.filters.selectedDepartmentId" class="form-select">
                                    <option value="all">{{__('All Departments')}}</option>
                                    @foreach($departments as $department)
                                    <option value="{{$department->id}}">{{$department->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">{{__('employees.employee')}}</label>
                                <select wire:model.live="newReport.filters.employee_id" class="form-select">
                                    <option value="all">{{__('All Employees')}}</option>
                                    @foreach($employees as $employee)
                                    <option value="{{$employee->id}}">{{$employee->first_name}} {{$employee->last_name}} ({{$employee->matricule}})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">{{__('common.status')}}</label>
                                <select wire:model.live="newReport.filters.status" class="form-select">
                                    <option value="all">{{__('All Statuses')}}</option>
                                    <option value="approved">{{__('common.approved')}}</option>
                                    <option value="pending">{{__('common.pending')}}</option>
                                    <option value="rejected">{{__('common.rejected')}}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">{{__('Start Date')}}</label>
                                <input wire:model.live="newReport.filters.start_date" type="date" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">{{__('End Date')}}</label>
                                <input wire:model.live="newReport.filters.end_date" type="date" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Payslip specific filters -->
                    @if($newReport['job_type'] === 'payslip_report')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">{{__('Email Status')}}</label>
                                <select wire:model.live="newReport.filters.email_status" class="form-select">
                                    <option value="">{{__('All Email Statuses')}}</option>
                                    <option value="successful">{{__('common.successful')}}</option>
                                    <option value="pending">{{__('common.pending')}}</option>
                                    <option value="failed">{{__('common.failed')}}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">{{__('SMS Status')}}</label>
                                <select wire:model.live="newReport.filters.sms_status" class="form-select">
                                    <option value="">{{__('All SMS Statuses')}}</option>
                                    <option value="successful">{{__('common.successful')}}</option>
                                    <option value="pending">{{__('common.pending')}}</option>
                                    <option value="failed">{{__('common.failed')}}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">{{__('Search Query')}}</label>
                        <input wire:model.live="newReport.filters.query_string" type="text" class="form-control" placeholder="{{__('Search in records...')}}">
                    </div>
                    @endif
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button type="button" class="btn btn-secondary" wire:click="closeCreateModal">{{__('common.cancel')}}</button>
                        <button type="submit" class="btn btn-primary">
                            <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h1m4 0h1m-6-8h8a2 2 0 012 2v8a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2z"></path>
                            </svg>
                            {{__('Generate Report')}}
                        </button>
                    </div>
                </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('createReportModal');
    if (modal) {
        // Listen for Livewire events
        Livewire.on('show-create-modal', () => {
            const bootstrapModal = new bootstrap.Modal(modal);
            bootstrapModal.show();
        });
        
        Livewire.on('hide-create-modal', () => {
            const bootstrapModal = bootstrap.Modal.getInstance(modal);
            if (bootstrapModal) {
                bootstrapModal.hide();
            }
        });
    }
});
</script>
