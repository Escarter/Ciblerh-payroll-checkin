<div>
    @include('livewire.partials.delete-modal')
    @include('livewire.partials.restore-modal')
    @include('livewire.partials.bulk-restore-modal')
    @include('livewire.partials.bulk-delete-modal-generic', ['selectedItems' => $selectedSendPayslipProcesses, 'itemType' => count($selectedSendPayslipProcesses) === 1 ? __('payslips.payslip') : __('payslips.payslips')])
    @include('livewire.portal.payslips.partials.payslip-task-details-modal')
    @include('livewire.portal.payslips.partials.payslip-details-modal')
    @include('livewire.partials.bulk-force-delete-modal-generic', ['selectedItems' => $selectedSendPayslipProcesses, 'itemType' => count($selectedSendPayslipProcesses) === 1 ? __('payslips.payslip') : __('payslips.payslips')])

    <x-alert />

    <div class='py-2 pb-2'>
        <div class="d-flex justify-content-between w-100 flex-wrap mb-4 align-items-center">
            <div class="mb-lg-0">
                <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
                    <ol class="breadcrumb breadcrumb-dark breadcrumb-transparent">
                        <li class="breadcrumb-item"><a href="{{ route('portal.dashboard') }}"><svg class="icon icon-xxs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg></a></li>
                        <li class="breadcrumb-item"><a href="{{route('portal.dashboard')}}" wire:navigate>{{__('dashboard.home')}}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{__('payslips.payslips_management')}}</li>
                    </ol>
                </nav>
                <h1 class="h4">
                    <svg class="icon me-1" fill="none" stroke="currentColor" viewBox="0 0 25 25" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    {{__('payslips.payslips_management')}}
                </h1>
                <p class="mb-0">{{__('payslips.manage_payslips_processes')}}</p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card p-3 p-lg-4">
                <div class="text-center text-md-center mb-4 mt-md-0">
                    <h1 class="mb-0 h4">{{__('payslips.send_payslips')}}</h1>
                </div>

                <!-- Fixed form structure -->
                <form wire:submit="send">
                    @hasanyrole('manager|admin')
                    <div class="form-group mb-4">
                        <label for="company">{{__('companies.company')}}</label>
                        <select wire:model.live="company_id" class="form-select @error('company_id') is-invalid @enderror" id="company">
                            <option value=''>{{__('payslips.select_company')}}</option>
                            @foreach ($companies as $company)
                            <option value='{{$company->id}}' wire:key="company-{{ $company->id }}">{{$company->name . __('payslips.company_with_departments', ['count' => count($company->departments)])}}</option>
                            @endforeach
                        </select>
                        <div>
                            @error('company_id')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                    </div>
                    @endhasanyrole

                    <div class="form-group mb-4">
                        <label for="department">{{__('departments.department')}}</label>
                        <select wire:model.live="department_id" class="form-select @error('department_id') is-invalid @enderror" id="department">
                            <option value=''>{{__('payslips.select_department')}}</option>
                            @foreach ($departments as $department)
                            <option value='{{$department->id}}' wire:key="department-{{ $department->id }}">{{$department->name ." - with ". count($department->employees) ." employees"}}</option>
                            @endforeach
                        </select>
                        <div>
                            @error('department_id')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                    </div>

                    <div class='form-group mb-4'>
                        <label for="month">{{__('common.month')}}</label>
                        <select wire:model.live="month" class="form-select @error('month') is-invalid @enderror">
                            <option value=''>{{__('common.--select_month--')}}</option>
                            <option value='January'>{{__('common.january')}}</option>
                            <option value='February'>{{__('common.february')}}</option>
                            <option value='March'>{{__('common.march')}}</option>
                            <option value='April'>{{__('common.april')}}</option>
                            <option value='May'>{{__('common.may')}}</option>
                            <option value='June'>{{__('common.june')}}</option>
                            <option value='July'>{{__('common.july')}}</option>
                            <option value='August'>{{__('common.august')}}</option>
                            <option value='September'>{{__('common.september')}}</option>
                            <option value='October'>{{__('common.october')}}</option>
                            <option value='November'>{{__('common.november')}}</option>
                            <option value='December'>{{__('common.december')}}</option>
                        </select>
                        <div>
                            @error('month')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label for="payslip_file" class="form-label">{{__('payslips.select_payslip')}}</label>

                        <div x-data="{ uploading: false, progress: 0 }" x-on:livewire-upload-start="uploading = true" x-on:livewire-upload-finish="uploading = false" x-on:livewire-upload-error="uploading = false" x-on:livewire-upload-progress="progress = $event.detail.progress">
                            <!-- File Input -->
                            <input class="form-control  @error('payslip_file') is-invalid @enderror" type="file" wire:model.live="payslip_file" id="payslip_file">

                            <!-- Progress Bar -->
                            <div x-show="uploading">
                                <progress max="100" x-bind:value="progress"></progress>
                            </div>
                        </div>
                        <div>
                            @error('payslip_file')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" wire:click.prevent="send" wire:loading.attr="disabled" class="btn btn-gray-800" {{empty($payslip_file) ? "disabled" : '' }}>{{__('payslips.start_processing')}}</button>
                    </div>
                </form>
            </div>
        </div>

        <div class='col-md-8'>
            <!-- Table Controls: Bulk Actions (Left) + Tab Buttons (Right) -->
            @if(auth()->user()->can('payslip-bulkdelete') && auth()->user()->can('payslip-bulkrestore'))
            <div class="d-flex justify-content-between align-items-center mb-3">

                <!-- Tab Buttons (Right) -->
                <div class="d-flex gap-2">
                    <button id="active-payslips-tab" class="btn {{ $activeTab === 'active' ? 'btn-primary' : 'btn-outline-primary' }}"
                        wire:click="switchTab('active')"
                        type="button">
                        <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        {{__('common.active')}}
                        <span class="badge {{ $activeTab === 'active' ? 'bg-light text-white' : 'bg-primary text-white' }} ms-1">{{ $active_processes ?? 0 }}</span>
                    </button>

                    <button id="deleted-payslips-tab" class="btn {{ $activeTab === 'deleted' ? 'btn-danger' : 'btn-outline-danger' }}"
                        wire:click="switchTab('deleted')"
                        type="button">
                        <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        {{__('common.deleted')}}
                        <span class="badge {{ $activeTab === 'deleted' ? 'bg-light text-white' : 'bg-danger text-white' }} ms-1">{{ $deleted_processes ?? 0 }}</span>
                    </button>
                </div>

                <!-- Bulk Actions (Left) -->
                <div>
                    @if($activeTab === 'active')
                    <!-- Soft Delete Bulk Actions (when items selected for delete) -->
                    @if(count($selectedSendPayslipProcesses) > 0)
                    <div class="d-flex align-items-center gap-2">
                        @can('payslip-bulkdelete')
                        <button type="button"
                            id="bulk-delete-payslips-btn"
                            class="btn btn-sm btn-outline-danger d-flex align-items-center"
                            title="{{ __('payslips.move_selected_payslip_processes_to_trash') }}"
                            data-bs-toggle="modal"
                            data-bs-target="#BulkDeleteModal">
                            <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            {{__('common.move_to_trash')}}
                            <span class="badge bg-danger text-white ms-1">{{ count($selectedSendPayslipProcesses) }}</span>
                        </button>
                        @endcan

                        <button wire:click="$set('selectedSendPayslipProcesses', [])"
                            class="btn btn-sm btn-outline-secondary d-flex align-items-center">
                            <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            {{__('common.clear')}}
                        </button>
                    </div>
                    @endif
                    @else
                    <!-- Deleted Tab Bulk Actions -->
                    @if(count($selectedSendPayslipProcesses) > 0)
                    <div class="d-flex align-items-center gap-2">
                        @can('payslip-bulkrestore')
                        <button data-bs-toggle="modal" data-bs-target="#BulkRestoreModal"
                            class="btn btn-sm btn-outline-success d-flex align-items-center me-2"
                            title="{{ __('payslips.restore_selected_payslip_processes') }}">
                            <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            {{__('common.restore_selected')}}
                            <span class="badge bg-success text-white ms-1">{{ count($selectedSendPayslipProcesses) }}</span>
                        </button>

                        <button type="button"
                            class="btn btn-sm btn-outline-danger d-flex align-items-center"
                            title="{{ __('payslips.permanently_delete_selected_payslip_processes') }}"
                            data-bs-toggle="modal"
                            data-bs-target="#BulkForceDeleteModal">
                            <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            {{__('common.delete_forever')}}
                            <span class="badge bg-danger text-white ms-1">{{ count($selectedSendPayslipProcesses) }}</span>
                        </button>
                        @endcan

                        <button wire:click="$set('selectedSendPayslipProcesses', [])"
                            class="btn btn-sm btn-outline-secondary d-flex align-items-center">
                            <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            {{__('common.clear')}}
                        </button>
                    </div>
                    @endif
                    @endif
                </div>
            </div>
            @endif

            <div class="card">
                @if(count($jobs)>0)
                <div class="table-responsive pb-4">
                    <table class="table user-table table-bordered table-hover align-items-center dataTable">
                        <thead class="">
                            <tr>
                                <th class="border-0 px-4 py-2 text-muted fw-medium">
                                    <div class="form-check d-flex justify-content-center align-items-center">
                                        <input id="select-all-payslips"
                                            class="form-check-input p-2"
                                            wire:model.live="selectAll"
                                            type="checkbox"
                                            wire:click="toggleSelectAll">
                                    </div>
                                </th>
                                <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('departments.department')}}</th>
                                <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('common.details')}}</th>
                                <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('common.status')}}</th>
                                <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('common.action')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($jobs as $job)
                            <tr>
                                <td>
                                    <div class="form-check d-flex justify-content-center align-items-center">
                                        <input class="form-check-input"
                                            type="checkbox"
                                            wire:click="toggleSendPayslipProcessSelection({{ $job->id }})"
                                            {{ in_array($job->id, $selectedSendPayslipProcesses) ? 'checked' : '' }}>
                                    </div>
                                </td>
                                <td>
                                    @if (!is_null($job->department))
                                    <a href="/portal/payslips/{{$job->id}}/details" wire:navigate class="d-flex align-items-center">
                                        <div class="avatar d-flex align-items-center justify-content-center fw-bold rounded bg-primary me-3 text-white"><span>{{initials($job->department->name)}}</span></div>
                                        <div class="d-block"><span class="fw-bold">{{$job->department->name}}</span>
                                            @hasanyrole('admin')
                                            <div class="small text-gray">
                                                <span class="d-flex align-items-baseline">
                                                    <svg class="icon icon-xxs small me-1" fill="currentColor" stroke="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                    </svg>
                                                    {{!is_null($job->owner) ? $job->owner->first_name : ''}}
                                                </span>
                                            </div>
                                            @endhasanyrole
                                        </div>
                                    </a>
                                    @else
                                    <p>{{__('payslips.department_deleted')}}</p>
                                    @endif
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <div class="mb-1">
                                            <span class="fw-bold text-primary">{{__('payslips.target')}}:</span>
                                            <span class="ms-1">{{!is_null($job->department) ? count($job->department->employees) : 0 }} {{__('common.employees')}}</span>
                                        </div>
                                        <div class="mb-1">
                                            <span class="fw-bold text-primary">{{__('common.period')}}:</span>
                                            <span class="ms-1">{{$job->month}} - {{$job->year}}</span>
                                        </div>
                                        <div>
                                            <span class="fw-bold text-primary">{{__('common.date_created')}}:</span>
                                            <span class="ms-1">{{$job->created_at->diffForHumans()}}</span>
                                        </div>
                                        <div>
                                            <span class="fw-bold text-primary">{{__('By')}}:</span>
                                            <span class="ms-1">{{!is_null($job->owner) ? $job->owner->first_name : ''}}</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($job->status == 'successful')
                                    <span class="badge badge-lg text-md bg-success">{{__('common.successful')}}</span>
                                    @elseif($job->status == 'failed')
                                    <span class="badge badge-lg text-md bg-danger">{{__('common.failed')}}</span>
                                    @else
                                    <span class="badge badge-lg text-md bg-warning">{{__('common.processing')}}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($activeTab === 'active')
                                    <!-- View Task Details Button -->
                                    <a href='#' wire:click.prevent="showTaskDetails({{$job->id}})" data-bs-toggle="modal" data-bs-target="#payslipTaskDetailsModal" class="me-2" title="{{__('payslips.view_task_details')}}" id="view-details-payslip-{{$job->id}}">
                                        <svg class="icon icon-xs text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                    <!-- Delete Button -->
                                    <a href='#' wire:click.prevent="initData({{$job->id}})" data-bs-toggle="modal" data-bs-target="#DeleteModal" id="delete-payslip-{{$job->id}}">
                                        <svg class="icon icon-xs text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </a>
                                    @else
                                    @can('payslip-delete')
                                    <a href="#" wire:click.prevent="$set('job_id', {{ $job->id }})" data-bs-toggle="modal" data-bs-target="#RestoreModal" class="text-success me-2" title="{{__('Restore')}}" id="restore-payslip-{{$job->id}}">
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                    </a>
                                    <a href="#" wire:click="forceDelete({{ $job->id }})" class="text-danger" title="{{__('common.delete_forever')}}">
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </a>
                                    @endcan
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class='mb-4 text-center'>
                    <a href="{{route('portal.payslips.history')}}" class="btn btn-secondary text-dark">{{__('payslips.view_past_history')}}</a>
                </div>
                @else
                <div class='p-5 text-center '>
                    <img src="{{asset('img/empty.svg')}}" alt='' class="w-25 h-25">
                    <p class="py-4 h5 text-muted">{{__('payslips.start_processing_payslip_to_see_the_outcome_here')}}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:initialized', function() {
        // Handle task details modal
        const taskModal = document.getElementById('payslipTaskDetailsModal');
        if (taskModal) {
            taskModal.addEventListener('hidden.bs.modal', function() {
                // Clean up backdrop when modal is closed
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
                // Reset body classes
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            });
        }

        // Handle payslip details modal
        const payslipModal = document.getElementById('payslipDetailsModal');
        if (payslipModal) {
            payslipModal.addEventListener('hidden.bs.modal', function() {
                // Clean up backdrop when modal is closed
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                    backdrop.remove();
                }
                // Reset body classes
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            });
        }
    });
</script>
@endpush