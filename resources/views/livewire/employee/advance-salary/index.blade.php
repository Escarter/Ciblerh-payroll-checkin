<div>
    @include('livewire.employee.advance-salary.create-advance-salary-modal')
    @include('livewire.employee.advance-salary.edit-advance-salary-modal')
    @include('livewire.partials.delete-modal')
    @include('livewire.partials.restore-modal')
    @include('livewire.partials.bulk-restore-modal')
    @include('livewire.partials.bulk-delete-modal-generic', ['selectedItems' => $selectedAdvanceSalaries, 'itemType' => count($selectedAdvanceSalaries) === 1 ? __('employees.advance_salary') : __('employees.advance_salaries')])
    @include('livewire.partials.bulk-delete-modal-generic', ['selectedItems' => $selectedAdvanceSalariesForDelete, 'itemType' => count($selectedAdvanceSalariesForDelete) === 1 ? __('employees.advance_salary') : __('employees.advance_salaries')])
    @include('livewire.partials.bulk-force-delete-modal-generic', ['selectedItems' => $selectedAdvanceSalariesForDelete, 'itemType' => count($selectedAdvanceSalariesForDelete) === 1 ? __('employees.advance_salary') : __('employees.advance_salaries')])
    @include('livewire.partials.force-delete-modal-generic', ['selectedItems' => $selectedAdvanceSalariesForDelete, 'itemType' => __('employees.advance_salary_record')])
    <div class='container pt-3 pt-lg-4 pb-4 pb-lg-3 text-white'>
        <div class='d-flex flex-wrap align-items-center  justify-content-between '>
            <a href="{{route('employee.dashboard')}}" wire:navigate class="">
                <svg class="icon me-1 text-gray-500 bg-gray-300 rounded-circle p-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <x-layouts.navigation.employee-nav />
            </div>
        </div>
        <div class='d-flex flex-wrap justify-content-between align-items-center pt-3'>
            <div class=''>
                <h1 class='fw-bold display-4 text-gray-600 d-inline-flex align-items-end'>
                    <svg class="icon icon-md me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span>
                        {{__('employees.advance_salary')}}
                    </span>
                </h1>
                <p class="text-gray-800">{{__('employees.view_all_advance_salary_requests')}} &#129297; </p>
            </div>
            <div class=''>
                @can('advance_salary-create')
                <a href='#' class='btn btn-secondary mx-2' data-bs-toggle="modal" data-bs-target="#CreateAdvanceSalaryModal">
                    <svg class="icon icon-sm me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    {{__('employees.request_advance_salary')}}
                </a>
                @endcan
            </div>
        </div>
        <div class=' mt-3'>
            <div class='row'>
                <div class='col-md-4 col-sm-12'>
                    <div class='border-prim p-3 rounded'>
                        <a href="#" class="d-flex  justify-content-between align-items-center gap-1">
                            <div class="d-flex justify-content-center align-items-center gap-2">
                                <svg class="icon me-1 text-gray-50 bg-success shadow rounded-circle p-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                </svg>
                                <div class="mb-3 mb-md-0">
                                    <h5 class="text-gray-700 fw-bold mb-0">{{number_format($approved_advance_salary)}} {{ __(Str::plural(__('employees.advance_salary'), $approved_advance_salary)) }}</h5>
                                    <div class=" text-gray-500 ">{{__('common.approved')}} &#128516;</div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                <div class='col-md-4 col-sm-12 mt-3 mt-md-0'>
                    <div class='border-prim p-3 rounded'>
                        <a href="#" class="d-flex  justify-content-between align-items-center gap-1">
                            <div class="d-flex justify-content-center align-items-center gap-2">
                                <svg class="icon icon-md me-1 text-gray-50 bg-warning shadow rounded-circle p-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="mb-3 mb-md-0">
                                    <h5 class="text-gray-700 fw-bold mb-0">{{number_format($pending_advance_salary)}} {{ __(\Str::plural(__('employees.advance_salary'), $pending_advance_salary))}} </h5>
                                    <div class=" text-gray-500 ">{{__('common.pending_approval')}} &#128516;</div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                <div class='col-md-4 col-sm-12 mt-3 mt-md-0'>
                    <div class='border-prim p-3 rounded'>
                        <a href="#" class="d-flex  justify-content-between align-items-center gap-1">
                            <div class="d-flex justify-content-center align-items-center gap-2">
                                <svg class="icon icon-md me-1 text-gray-50 bg-danger shadow rounded-circle p-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <div class="mb-3 mb-md-0">
                                    <h5 class="text-gray-700 fw-bold mb-0">{{number_format($rejected_advance_salary)}} {{ __(\Str::plural(__('employees.advance_salary'), $rejected_advance_salary)) }}</h5>
                                    <div class="text-gray-500 ">{{__('common.rejected')}} &#128560;</div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="row py-2 text-gray-600 mt-3">
            <div class="col-md-3 mb-2">
                <label for="search">{{__('common.search')}}: </label>
                <input wire:model="query" id="search" type="text" placeholder="{{__('common.search_placeholder')}}" class="form-control">
                <p class="badge badge-info" wire:model="resultCount">{{$resultCount}}</p>
            </div>
            <div class="col-md-3 mb-2">
                <label for="orderBy">{{__('common.order_by')}}: </label>
                <select wire:model="orderBy" id="orderBy" class="form-select">
                    <option value="reason">{{__('common.reason')}}</option>
                    <option value="approval_status">{{__('common.approval_status')}}</option>
                    <option value="created_at">{{__('common.created_date')}}</option>
                </select>
            </div>

            <div class="col-md-3 mb-2">
                <label for="direction">{{__('common.order_direction')}}: </label>
                <select wire:model="orderAsc" id="direction" class="form-select">
                    <option value="asc">{{__('common.ascending')}}</option>
                    <option value="desc">{{__('common.descending')}}</option>
                </select>
            </div>

            <div class="col-md-3 mb-2">
                <label for="perPage">{{__('common.items_per_page')}}: </label>
                <select wire:model="perPage" id="perPage" class="form-select">
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="20">20</option>
                    <option value="25">25</option>
                </select>
            </div>
        </div>
        <x-alert />

        <!-- Tab Buttons and Bulk Actions (Same Line) -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <!-- Tab Buttons (Left) -->
            <div class="d-flex gap-2">
                <button id="active-advance-salaries-tab" class="btn {{ $activeTab === 'active' ? 'btn-primary' : 'btn-outline-primary' }}"
                    wire:click="switchTab('active')"
                    type="button">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    {{__('common.active')}}
                    <span class="badge {{ $activeTab === 'active' ? 'bg-light text-white' : 'bg-primary text-white' }} ms-1">{{ $activeAdvanceSalariesCount }}</span>
                </button>

                <button id="deleted-advance-salaries-tab" class="btn {{ $activeTab === 'deleted' ? 'btn-danger' : 'btn-outline-danger' }}"
                    wire:click="switchTab('deleted')"
                    type="button">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    {{__('common.deleted')}}
                    <span class="badge {{ $activeTab === 'deleted' ? 'bg-light text-white' : 'bg-danger text-white' }} ms-1">{{ $deletedAdvanceSalariesCount }}</span>
                </button>
            </div>

            <!-- Bulk Actions (Right) -->
            @if(count($advance_salaries) > 0)
                @if($activeTab === 'active')
                    <!-- Selection Controls -->
                    <div class="dropdown me-2">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                            <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            {{__('common.select')}}
                            @if(count($selectedAdvanceSalaries) > 0)
                                <span class="badge bg-primary text-white ms-1">{{ count($selectedAdvanceSalaries) }}</span>
                            @endif
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" wire:click.prevent="selectAllVisible" href="#">{{__('absences.select_all_visible')}}</a></li>
                            <li><a class="dropdown-item" wire:click.prevent="selectAllAdvanceSalaries" href="#">{{__('advance_salary.select_all_advance_salaries')}}</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" wire:click.prevent="$set('selectedAdvanceSalaries', [])" href="#">{{__('absences.deselect_all')}}</a></li>
                        </ul>
                    </div>

                    <!-- Active Tab Bulk Actions -->
                    @if(count($selectedAdvanceSalaries) > 0)
                    <div class="d-flex align-items-center gap-2">
                        @can('advance_salary-delete')
                        <button type="button"
                            class="btn btn-sm btn-outline-danger d-flex align-items-center"
                            data-bs-toggle="modal"
                            data-bs-target="#BulkDeleteModal">
                            <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            {{__('absences.bulk_delete_selected_absences')}}
                            <span class="badge bg-danger text-white ms-1">{{ count($selectedAdvanceSalaries) }}</span>
                        </button>
                        @endcan

                        <button wire:click="$set('selectedAdvanceSalaries', [])"
                            class="btn btn-sm btn-outline-secondary d-flex align-items-center">
                            <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            {{__('common.clear')}}
                        </button>
                    </div>
                    @endif
                @else
                    <!-- Selection Controls for Deleted Tab -->
                    <div class="dropdown me-2">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                            <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            {{__('common.select')}}
                            @if(count($selectedAdvanceSalariesForDelete) > 0)
                                <span class="badge bg-primary text-white ms-1">{{ count($selectedAdvanceSalariesForDelete) }}</span>
                            @endif
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" wire:click.prevent="selectAllVisibleForDelete" href="#">{{__('absences.select_all_visible')}}</a></li>
                            <li><a class="dropdown-item" wire:click.prevent="selectAllDeletedAdvanceSalaries" href="#">{{__('advance_salary.select_all_deleted_advance_salaries')}}</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" wire:click.prevent="$set('selectedAdvanceSalariesForDelete', [])" href="#">{{__('absences.deselect_all')}}</a></li>
                        </ul>
                    </div>

                    <!-- Deleted Tab Bulk Actions -->
                    @if(count($selectedAdvanceSalariesForDelete) > 0)
                    <div class="d-flex align-items-center gap-2">
                        @can('advance_salary-delete')
                        <button data-bs-toggle="modal" data-bs-target="#BulkRestoreModal"
                            class="btn btn-sm btn-outline-success d-flex align-items-center me-2"
                            title="{{ __('absences.restore_selected_absence_records') }}">
                            <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            {{__('common.restore_selected')}}
                            <span class="badge bg-success text-white ms-1">{{ count($selectedAdvanceSalariesForDelete) }}</span>
                        </button>

                        <button type="button"
                            class="btn btn-sm btn-outline-danger d-flex align-items-center"
                            title="{{ __('absences.permanently_delete_selected_absence_records') }}"
                            data-bs-toggle="modal"
                            data-bs-target="#BulkForceDeleteModal">
                            <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            {{__('common.delete_forever')}}
                            <span class="badge bg-danger text-white ms-1">{{ count($selectedAdvanceSalariesForDelete) }}</span>
                        </button>
                        @endcan

                        <button wire:click="$set('selectedAdvanceSalariesForDelete', [])"
                            class="btn btn-sm btn-outline-secondary d-flex align-items-center">
                            <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            {{__('common.clear')}}
                        </button>
                    </div>
                    @endif
                @endif
            @endif
        </div>

        @if(count($advance_salaries) > 0)
        <div class="card">
            <div class="table-responsive pb-3 text-gray-700">
                <table class="table employee-table table-hover align-items-center ">
                    <thead>
                        <tr>
                            <th class="border-bottom">
                                <div class="form-check d-flex justify-content-center align-items-center">
                                    @if($activeTab === 'active')
                                        <input class="form-check-input p-2" wire:model.live="selectAll" type="checkbox">
                                    @else
                                        <input class="form-check-input p-2"
                                            wire:model.live="selectAllForDelete"
                                            type="checkbox"
                                            wire:click="toggleSelectAllForDelete">
                                    @endif
                                </div>
                            </th>
                            <th class="border-bottom">{{__('employees.beneficiary')}}</th>
                            <th class="border-bottom">{{__('common.amount')}}</th>
                            <th class="border-bottom">{{__('employees.repayment_start')}}</th>
                            <th class="border-bottom">{{__('employees.repayment_end')}}</th>
                            <th class="border-bottom">{{__('common.status')}}</th>
                            <th class="border-bottom">{{__('common.created_date')}}</th>
                            <th class="border-bottom">{{__('common.action')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($advance_salaries as $advance_salary)
                        <tr>
                            <td>
                                <div class="form-check d-flex justify-content-center align-items-center">
                                    @if($activeTab === 'active')
                                        @if($advance_salary->approval_status !== AdvanceSalary::APPROVAL_STATUS_APPROVED)
                                        <input class="form-check-input" wire:model.live="selectedAdvanceSalaries" value="{{$advance_salary->id}}" type="checkbox">
                                        @endif
                                    @else
                                        <input class="form-check-input"
                                            type="checkbox"
                                            wire:click="toggleAdvanceSalarySelectionForDelete({{ $advance_salary->id }})"
                                            {{ in_array($advance_salary->id, $selectedAdvanceSalariesForDelete) ? 'checked' : '' }}>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <a href="#" class="d-flex align-items-center">
                                    <div class="avatar d-flex align-items-center justify-content-center fw-bold rounded bg-primary me-3"><span class="text-white">{{$advance_salary->initials}}</span></div>
                                    <div class="d-block"><span class="fw-bold">{{$advance_salary->beneficiary_name}}</span>
                                        <div class="small text-gray">{{$advance_salary->beneficiary_mobile_money_number}}</div>
                                    </div>
                                </a>
                            </td>
                            <td>
                                <span class="fw-bold">{{number_format($advance_salary->amount)}} {{__('employees.currency_xaf')}}</span>
                            </td>
                            <td>
                                <span class="fs-normal">{{$advance_salary->repayment_from_month->format('M, Y')}}</span>
                            </td>
                            <td>
                                <span class="fs-normal">{{$advance_salary->repayment_to_month->format('M, Y')}}</span>
                            </td>

                            <td>
                                <span class="fw-normal badge super-badge badge-lg bg-{{$advance_salary->approvalStatusStyle()}} rounded">{{$advance_salary->approvalStatusText()}}</span>
                            </td>
                            <td>
                                <span class="fw-normal">{{$advance_salary->created_at->format('Y-m-d')}}</span>
                            </td>
                            <td>
                                @if($activeTab === 'active')
                                    @if(!$advance_salary->isApproved())
                                    @can('advance_salary-update')
                                    <a href='#' wire:click="initData({{ $advance_salary->id }})" data-bs-toggle="modal" data-bs-target="#EditAdvanceSalaryModal">
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                    @endcan
                                    @can('advance_salary-delete')
                                    <a href='#' wire:click="initData({{ $advance_salary->id }})" data-bs-toggle="modal" data-bs-target="#DeleteModal">
                                        <svg class="icon icon-xs text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </a>
                                    @endcan
                                    @endif
                                @else
                                    @can('advance_salary-delete')
                                    <a href="#" id="restore-advance-salary-{{ $advance_salary->id }}" wire:click.prevent="$set('{{ $advance_salary->id }}', {{ $advance_salary->id }})" data-bs-toggle="modal" data-bs-target="#RestoreModal" class="text-success me-2" title="{{__('common.restore')}}">
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                    </a>
                                    <a href="#" wire:click.prevent="$set('selectedAdvanceSalariesForDelete', [{{ $advance_salary->id }}])" data-bs-toggle="modal" data-bs-target="#ForceDeleteModal" class="text-danger" title="{{__('common.delete_forever')}}">
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
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
                <div class='pt-3 px-3 '>
                    {{ $advance_salaries->links() }}
                </div>
            </div>
        </div>
        @else
        <div class='border-prim rounded p-4 d-flex justify-content-center align-items-center flex-column'>
            <img src="{{asset('/img/empty.svg')}}" alt='{{__("common.nothing_here")}}' class="text-center  w-25 h-25">
            <div class="text-center text-gray-800 mt-2">
                <h4 class="fs-4 fw-bold">{{__('common.oops_nothing_here')}} &#128540;</h4>
                <p>{{__('employees.request_advance_salary_message')}}</p>
            </div>
        </div>
        @endif
    </div>

</div>