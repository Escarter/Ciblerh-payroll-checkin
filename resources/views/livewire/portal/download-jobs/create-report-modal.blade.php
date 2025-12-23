<!-- Create Report Modal -->
<div wire:ignore.self class="modal side-layout-modal fade" id="createReportModal" tabindex="-1" aria-labelledby="modal-form" data-bs-backdrop="static" data-bs-keyboard="false" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document" style="max-width:65%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{__('reports.generate_new_report')}}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" wire:click="closeCreateModal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0">
                        <p>{{__('download_jobs.generate_reports_and_download_files')}} &#128522;</p>
                    </div>
                <form wire:submit.prevent="generateNewReport">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="reportType" class="form-label">{{__('Report Type')}} <span class="text-danger">*</span></label>
                                <select wire:model.live="newReport.job_type" class="form-select" id="reportType" required>
                                    <option value="">{{__('download_jobs.select_report_type')}}</option>
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
                                    <option value="xlsx">{{__('download_jobs.excel_xlsx')}}</option>
                                    <option value="pdf">{{__('download_jobs.pdf')}}</option>
                                    <option value="zip">{{__('download_jobs.zip_archive')}}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic filters based on report type -->
                    @if($newReport['job_type'] ?? false)
                    <div class="mb-3">
                        <h6>{{__('download_jobs.report_filters')}}</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">{{__('companies.company')}}</label>
                                <select wire:model.live="newReport.filters.selectedCompanyId" class="form-select" id="filterCompany">
                                    <option value="all">{{__('download_jobs.all_companies')}}</option>
                                    @foreach($companies as $company)
                                    <option value="{{$company->id}}">{{$company->name}}</option>
                                    @endforeach
                                </select>
                                @error('newReport.filters.selectedCompanyId') <span class="text-danger small">{{$message}}</span> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{__('departments.department')}}</label>
                                <select wire:model.live="newReport.filters.selectedDepartmentId" 
                                        class="form-select" 
                                        id="filterDepartment"
                                        @if($newReport['filters']['selectedCompanyId'] === 'all' || empty($newReport['filters']['selectedCompanyId']))
                                        disabled
                                        @endif>
                                    <option value="all">{{__('download_jobs.all_departments')}}</option>
                                    @foreach($departments as $department)
                                    <option value="{{$department->id}}">
                                        {{$department->name}}
                                        @if($department->company)
                                        ({{$department->company->name}})
                                        @endif
                                    </option>
                                    @endforeach
                                </select>
                                @if($newReport['filters']['selectedCompanyId'] === 'all' || empty($newReport['filters']['selectedCompanyId']))
                                <small class="text-muted">{{__('download_jobs.select_company_first')}}</small>
                                @endif
                                @error('newReport.filters.selectedDepartmentId') <span class="text-danger small">{{$message}}</span> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">{{__('employees.employee')}}</label>
                                <div wire:key="employee-select-{{ $newReport['filters']['selectedCompanyId'] }}-{{ $newReport['filters']['selectedDepartmentId'] }}-{{ md5(json_encode(collect($employees)->pluck('id')->toArray())) }}">
                                    <x-choices-multi-select
                                        id="filterEmployees"
                                        wireModel="newReport.filters.employee_id"
                                        :options="collect($employees)->mapWithKeys(function($employee) {
                                            return [$employee->id => $employee->first_name . ' ' . $employee->last_name . ' (' . $employee->matricule . ')'];
                                        })->toArray()"
                                        :selected="is_array($newReport['filters']['employee_id'] ?? null) ? array_map('intval', $newReport['filters']['employee_id']) : []"
                                        label=""
                                        class="form-select"
                                    />
                                </div>
                                @if($newReport['filters']['selectedCompanyId'] === 'all' || empty($newReport['filters']['selectedCompanyId']))
                                <small class="text-muted d-block mt-1">{{__('download_jobs.select_company_first')}}</small>
                                @elseif($newReport['filters']['selectedDepartmentId'] === 'all' || empty($newReport['filters']['selectedDepartmentId']))
                                <small class="text-muted d-block mt-1">{{__('download_jobs.select_department_first')}}</small>
                                @elseif(count($employees) === 0)
                                <small class="text-warning d-block mt-1">{{__('download_jobs.no_employees_found')}}</small>
                                @endif
                                @error('newReport.filters.employee_id') <span class="text-danger small">{{$message}}</span> @enderror
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">{{__('common.status')}}</label>
                                <select wire:model.live="newReport.filters.status" class="form-select">
                                    <option value="all">{{__('common.all_statuses')}}</option>
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
                                <label class="form-label">{{__('download_jobs.start_date')}}</label>
                                <input wire:model.live="newReport.filters.start_date" type="date" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">{{__('download_jobs.end_date')}}</label>
                                <input wire:model.live="newReport.filters.end_date" type="date" class="form-control">
                            </div>
                        </div>
                    </div>

                    <!-- Payslip specific filters -->
                    @if($newReport['job_type'] === 'payslip_report')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">{{__('download_jobs.email_status')}}</label>
                                <select wire:model.live="newReport.filters.email_status" class="form-select">
                                    <option value="">{{__('download_jobs.all_email_statuses')}}</option>
                                    <option value="successful">{{__('common.successful')}}</option>
                                    <option value="pending">{{__('common.pending')}}</option>
                                    <option value="failed">{{__('common.failed')}}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">{{__('download_jobs.sms_status')}}</label>
                                <select wire:model.live="newReport.filters.sms_status" class="form-select">
                                    <option value="">{{__('download_jobs.all_sms_statuses')}}</option>
                                    <option value="successful">{{__('common.successful')}}</option>
                                    <option value="pending">{{__('common.pending')}}</option>
                                    <option value="failed">{{__('common.failed')}}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">{{__('download_jobs.search_query')}}</label>
                        <input wire:model.live="newReport.filters.query_string" type="text" class="form-control" placeholder="{{__('download_jobs.search_in_records')}}">
                    </div>
                    @endif

                    <!-- Schedule Options -->
                    <div class="mb-3 mt-4 border-top pt-3">
                        <div class="form-check mb-3">
                            <input wire:model.live="newReport.is_scheduled" class="form-check-input" type="checkbox" id="isScheduled">
                            <label class="form-check-label" for="isScheduled">
                                <strong>{{__('scheduled_reports.schedule_this_report')}}</strong>
                            </label>
                        </div>

                        @if($newReport['is_scheduled'])
                        <div class="card bg-light">
                            <div class="card-body">
                                <!-- Schedule Name -->
                                <div class="mb-3">
                                    <label for="scheduleName" class="form-label">{{__('scheduled_reports.report_name')}} <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input wire:model.live="newReport.schedule_name" type="text" class="form-control" id="scheduleName" required placeholder="{{__('scheduled_reports.report_name_placeholder')}}">
                                        <button type="button" class="btn btn-outline-secondary" wire:click="$set('newReport.schedule_name', $wire.generateReportName())" title="{{__('scheduled_reports.auto_generate_name')}}">
                                            <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    @error('newReport.schedule_name') <span class="text-danger">{{$message}}</span> @enderror
                                </div>

                                <!-- Email Recipients -->
                                <div class="mb-3">
                                    <label class="form-label">{{__('scheduled_reports.email_recipients')}} <span class="text-danger">*</span></label>
                                    <div class="input-group mb-2">
                                        <input wire:model="newReport.recipient_email" type="email" class="form-control" placeholder="{{__('scheduled_reports.enter_email_address')}}" wire:keydown.enter.prevent="addRecipient">
                                        <button type="button" class="btn btn-outline-primary" wire:click="addRecipient">
                                            <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    @if(!empty($newReport['recipients']))
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($newReport['recipients'] as $index => $email)
                                        <span class="badge bg-primary badge-lg d-flex align-items-center gap-1">
                                            {{$email}}
                                            <button type="button" class="btn-close btn-close-white" style="font-size: 0.7em;" wire:click="removeRecipient({{$index}})"></button>
                                        </span>
                                        @endforeach
                                    </div>
                                    @endif
                                    @error('newReport.recipients') <span class="text-danger">{{$message}}</span> @enderror
                                </div>

                                <!-- Schedule Settings -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">{{__('scheduled_reports.frequency')}} <span class="text-danger">*</span></label>
                                            <select wire:model.live="newReport.frequency" class="form-select" required>
                                                <option value="monthly">{{__('scheduled_reports.monthly')}}</option>
                                                <option value="weekly">{{__('scheduled_reports.weekly')}}</option>
                                                <option value="daily">{{__('scheduled_reports.daily')}}</option>
                                            </select>
                                            @error('newReport.frequency') <span class="text-danger">{{$message}}</span> @enderror
                                        </div>
                                    </div>
                                    @if($newReport['frequency'] === 'monthly')
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">{{__('scheduled_reports.day_of_month')}} <span class="text-danger">*</span></label>
                                            <input wire:model="newReport.day_of_month" type="number" class="form-control" min="1" max="28" required>
                                            @error('newReport.day_of_month') <span class="text-danger">{{$message}}</span> @enderror
                                        </div>
                                    </div>
                                    @endif
                                </div>

                                @if($newReport['frequency'] === 'monthly')
                                <div class="mb-3">
                                    <label class="form-label">{{__('scheduled_reports.time')}} <span class="text-danger">*</span></label>
                                    <input wire:model="newReport.time" type="time" class="form-control" required>
                                    @error('newReport.time') <span class="text-danger">{{$message}}</span> @enderror
                                </div>
                                @else
                                <div class="mb-3">
                                    <label class="form-label">{{__('scheduled_reports.time')}} <span class="text-danger">*</span></label>
                                    <input wire:model="newReport.time" type="time" class="form-control" required>
                                    @error('newReport.time') <span class="text-danger">{{$message}}</span> @enderror
                                </div>
                                @endif

                                <div class="form-check">
                                    <input wire:model="newReport.is_active" class="form-check-input" type="checkbox" id="scheduleActive">
                                    <label class="form-check-label" for="scheduleActive">
                                        {{__('scheduled_reports.active')}}
                                    </label>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button type="button" class="btn btn-secondary" wire:click="closeCreateModal">{{__('common.cancel')}}</button>
                        <button type="submit" class="btn btn-primary">
                            @if($newReport['is_scheduled'])
                            <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{__('scheduled_reports.create_schedule')}}
                            @else
                            <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h1m4 0h1m-6-8h8a2 2 0 012 2v8a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2z"></path>
                            </svg>
                            {{__('download_jobs.generate_report')}}
                            @endif
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
        
        // Refresh choices when departments or employees are updated
        Livewire.hook('morph.updated', ({ el, component }) => {
            // Trigger refresh for choices components when filters change
            window.dispatchEvent(new CustomEvent('refreshChoices'));
        });
    }
});
</script>
