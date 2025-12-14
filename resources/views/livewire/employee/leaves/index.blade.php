<div>
    @include('livewire.employee.leaves.create-leave-modal')
    @include('livewire.employee.leaves.edit-leave-modal')
    @include('livewire.partials.delete-modal')
    @include('livewire.partials.restore-modal')
    @include('livewire.partials.bulk-restore-modal')
    @include('livewire.partials.bulk-delete-modal-generic', ['selectedItems' => $selectedLeaves, 'itemType' => count($selectedLeaves) === 1 ? __('employees.leave') : __('employees.leaves')])
    @include('livewire.partials.bulk-delete-modal-generic', ['selectedItems' => $selectedLeavesForDelete, 'itemType' => count($selectedLeavesForDelete) === 1 ? __('employees.leave') : __('employees.leaves')])
    @include('livewire.partials.bulk-force-delete-modal-generic', ['selectedItems' => $selectedLeavesForDelete, 'itemType' => count($selectedLeavesForDelete) === 1 ? __('employees.leave') : __('employees.leaves')])
    @include('livewire.partials.force-delete-modal-generic', ['selectedItems' => $selectedLeavesForDelete, 'itemType' => __('employees.leave_record')])
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span>
                        {{__('employees.leaves_history')}}
                    </span>
                </h1>
                <p class="text-gray-800">{{__('employees.view_all_leaves_recorded')}} &#128517;</p>
            </div>
            <div class=''>
                @can('leave-create')
                <a href='#' class='btn btn-primary mx-2' data-bs-toggle="modal" data-bs-target="#CreateLeaveModal">
                    <svg class="icon icon-sm me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    {{__('employees.request_leave')}}
                </a>
                @endcan
            </div>
        </div>
        <div class='mb-2 mt-3'>
            <div class='row mb-3'>
                <div class='col-md-6 col-sm-12'>
                    <div class='border-prim bg-yellow-400 p-3 rounded'>
                        <a href="#" class="d-flex  justify-content-between align-items-center gap-1">
                            <div class="d-flex justify-content-center align-items-center gap-2">
                                <svg class="icon me-1 text-gray-500 bg-gray-300 shadow rounded-circle p-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>

                                <div class="mb-3 mb-md-0">
                                    <h6 class="text-gray-700 fw-bold"><span class="display-4">{{number_format(auth()->user()->remaining_leave_days)}}</span> {{__('employees.remaining_days')}} | {{__('employees.monthly_allocation')}} <strong>{{number_format(auth()->user()->monthly_leave_allocation)}} </strong>{{__('employees.days')}} </h6>
                                    <div class="text-gray-500 ">{{__('employees.remaining_leave_days_allocation')}}</div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                <div class='col-md-6 col-sm-12 mt-3 mt-md-0'>
                    <div class='border-prim  p-3 rounded'>
                        <a href="#" class="d-flex  justify-content-between align-items-center gap-1">
                            <div class="d-flex justify-content-center align-items-center gap-2">
                                <svg class="icon me-1 text-gray-500 bg-gray-300 shadow rounded-circle p-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                </svg>
                                <div class="mb-3 mb-md-0">
                                    <h5 class="text-gray-700 fw-bold"><span class="display-4">{{number_format($approved_leave) }} </span> {{__('employees.leave_days_taken')}} </h5>
                                    <div class="text-gray-500 ">{{__('employees.all_leave_days_taken')}}</div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <div class='row'>
                <div class='col-md-4 col-sm-12'>
                    <div class='border-prim p-3 rounded'>
                        <a href="#" class="d-flex  justify-content-between align-items-center gap-1">
                            <div class="d-flex justify-content-center align-items-center gap-2">
                                <svg class="icon me-1 text-gray-50 bg-success shadow rounded-circle p-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                </svg>
                                <div class="mb-3 mb-md-0">
                                    <h5 class="text-gray-700 fw-bold mb-0">{{numberFormat($approved_leave)}} {{ __(\Str::plural('employees.leave', $approved_leave)) }} </h5>
                                    <div class=" text-gray-500 ">{{__('employees.approved')}} &#128516;</div>
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
                                    <h5 class="text-gray-700 fw-bold mb-0">{{numberFormat($pending_leave)}} {{ __(\Str::plural('employees.leave', $pending_leave)) }} </h5>
                                    <div class=" text-gray-500 ">{{__('employees.pending_approval')}} &#128516;</div>
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
                                    <h5 class="text-gray-700 fw-bold mb-0">{{numberFormat($rejected_leave)}} {{ __(\Str::plural('employees.leave', $rejected_leave)) }}</h5>
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
                    <option value="start_date">{{__('employees.start_date')}}</option>
                    <option value="end_date">{{__('employees.end_date')}}</option>
                    <option value="leave_reason">{{__('common.reason')}}</option>
                    <option value="supervisor_approval_status">{{__('employees.sup_approval_status')}}</option>
                    <option value="manager_approval_status">{{__('employees.mgr_approval_status')}}</option>
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
                <button id="active-leaves-tab" class="btn {{ $activeTab === 'active' ? 'btn-primary' : 'btn-outline-primary' }}"
                    wire:click="switchTab('active')"
                    type="button">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    {{__('common.active')}}
                    <span class="badge {{ $activeTab === 'active' ? 'bg-light text-white' : 'bg-primary text-white' }} ms-1">{{ $activeLeavesCount }}</span>
                </button>

                <button id="deleted-leaves-tab" class="btn {{ $activeTab === 'deleted' ? 'btn-danger' : 'btn-outline-danger' }}"
                    wire:click="switchTab('deleted')"
                    type="button">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    {{__('common.deleted')}}
                    <span class="badge {{ $activeTab === 'deleted' ? 'bg-light text-white' : 'bg-danger text-white' }} ms-1">{{ $deletedLeavesCount }}</span>
                </button>
            </div>

            <!-- Bulk Actions (Right) -->
            @if(count($leaves) > 0)
                @if($activeTab === 'active')
                    <!-- Selection Controls -->
                    <div class="dropdown me-2">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                            <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            {{__('common.select')}}
                            @if(count($selectedLeaves) > 0)
                                <span class="badge bg-primary text-white ms-1">{{ count($selectedLeaves) }}</span>
                            @endif
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" wire:click.prevent="selectAllVisible" href="#">{{__('absences.select_all_visible')}}</a></li>
                            <li><a class="dropdown-item" wire:click.prevent="selectAllLeaves" href="#">{{__('leaves.select_all_leaves')}}</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" wire:click.prevent="$set('selectedLeaves', [])" href="#">{{__('absences.deselect_all')}}</a></li>
                        </ul>
                    </div>

                    <!-- Active Tab Bulk Actions -->
                    @if(count($selectedLeaves) > 0)
                    <div class="d-flex align-items-center gap-2">
                        @can('leave-delete')
                        <button type="button"
                            class="btn btn-sm btn-outline-danger d-flex align-items-center"
                            data-bs-toggle="modal"
                            data-bs-target="#BulkDeleteModal">
                            <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            {{__('absences.bulk_delete_selected_absences')}}
                            <span class="badge bg-danger text-white ms-1">{{ count($selectedLeaves) }}</span>
                        </button>
                        @endcan

                        <button wire:click="$set('selectedLeaves', [])"
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
                            @if(count($selectedLeavesForDelete) > 0)
                                <span class="badge bg-primary text-white ms-1">{{ count($selectedLeavesForDelete) }}</span>
                            @endif
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" wire:click.prevent="selectAllVisibleForDelete" href="#">{{__('absences.select_all_visible')}}</a></li>
                            <li><a class="dropdown-item" wire:click.prevent="selectAllDeletedLeaves" href="#">{{__('leaves.select_all_deleted_leaves')}}</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" wire:click.prevent="$set('selectedLeavesForDelete', [])" href="#">{{__('absences.deselect_all')}}</a></li>
                        </ul>
                    </div>

                    <!-- Deleted Tab Bulk Actions -->
                    @if(count($selectedLeavesForDelete) > 0)
                    <div class="d-flex align-items-center gap-2">
                        @can('leave-delete')
                        <button data-bs-toggle="modal" data-bs-target="#BulkRestoreModal"
                            class="btn btn-sm btn-outline-success d-flex align-items-center me-2"
                            title="{{ __('absences.restore_selected_absence_records') }}">
                            <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            {{__('common.restore_selected')}}
                            <span class="badge bg-success text-white ms-1">{{ count($selectedLeavesForDelete) }}</span>
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
                            <span class="badge bg-danger text-white ms-1">{{ count($selectedLeavesForDelete) }}</span>
                        </button>
                        @endcan

                        <button wire:click="$set('selectedLeavesForDelete', [])"
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

        @if(count($leaves) > 0)
        <div class="card">
            <div class="table-responsive py-4 text-gray-700">
                <table class="table employee-table table-hover align-items-center dataTable" id="datatable">
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
                            <th class="border-bottom">{{__('employees.leave_type')}}</th>
                            <th class="border-bottom">{{__('employees.period')}}</th>
                            <th class="border-bottom">{{__('common.reason')}}</th>
                            <th class="border-bottom">{{__('common.sup_approval')}}</th>
                            <th class="border-bottom">{{__('common.mgr_approval')}}</th>
                            <th class="border-bottom">{{__('common.created_date')}}</th>
                            <th class="border-bottom">{{__('common.action')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($leaves as $leave)
                        <tr>
                            <td>
                                <div class="form-check d-flex justify-content-center align-items-center">
                                    @if($activeTab === 'active')
                                        <input class="form-check-input" wire:model.live="selectedLeaves" value="{{$leave->id}}" type="checkbox">
                                    @else
                                        <input class="form-check-input"
                                            type="checkbox"
                                            wire:click="toggleLeaveSelectionForDelete({{ $leave->id }})"
                                            {{ in_array($leave->id, $selectedLeavesForDelete) ? 'checked' : '' }}>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span class="fs-bold">{{$leave->leaveType->name}}</span>
                            </td>
                            <td>
                                <span class="fw-bold">{{__('employees.start_date')}}</span>: <span class="fw-normal">{{$leave->start_date->ISOFormat('LL')}}</span><br>
                                <span class="fw-bold">{{__('employees.end_date')}}</span>: <span class="fw-normal">{{$leave->end_date->ISOFormat('LL')}}</span><br>
                                <span class="fw-bold">{{__('employees.number_of_days')}}</span>: <span class="fw-normal">{{$leave->period}}</span><br>
                            </td>
                            <td>
                                <span class="fs-normal">{{\Str::limit($leave->leave_reason,50)}}</span>
                            </td>
                            <td>
                                <span class="fw-normal badge super-badge badge-lg bg-{{$leave->approvalStatusStyle('supervisor')}} rounded">{{$leave->approvalStatusText('supervisor')}}</span>
                            </td>
                            <td>
                                <span class="fw-normal badge super-badge badge-lg bg-{{$leave->approvalStatusStyle('manager')}} rounded">{{$leave->approvalStatusText('manager')}}</span>
                            </td>
                            <td>
                                <span class="fw-normal">{{$leave->created_at->format('Y-m-d')}}</span>
                            </td>
                            <td>
                                @if($activeTab === 'active')
                                    @if(!$leave->isApproved())
                                    @can('leave-update')
                                    <a href='#' wire:click.prevent="initData({{$leave->id}})" data-bs-toggle="modal" data-bs-target="#EditLeaveModal">
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                    @endcan
                                    @can('leave-delete')
                                    <a href='#' wire:click="initData({{ $leave->id }})" data-bs-toggle="modal" data-bs-target="#DeleteModal">
                                        <svg class="icon icon-xs text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </a>
                                    @endcan
                                    @endif
                                @else
                                    @can('leave-delete')
                                    <a href="#" id="restore-leave-{{ $leave->id }}" wire:click.prevent="$set('{{ $leave->id }}', {{ $leave->id }})" data-bs-toggle="modal" data-bs-target="#RestoreModal" class="text-success me-2" title="{{__('common.restore')}}">
                                        <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                        </svg>
                                    </a>
                                    <a href="#" wire:click.prevent="$set('selectedLeavesForDelete', [{{ $leave->id }}])" data-bs-toggle="modal" data-bs-target="#ForceDeleteModal" class="text-danger" title="{{__('common.delete_forever')}}">
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
                    {{ $leaves->links() }}
                </div>
            </div>
        </div>
        @else
        <div class='border-prim rounded p-4 d-flex justify-content-center align-items-center flex-column'>
            <img src="{{asset('/img/empty.svg')}}" alt='{{__("Empty")}}' class="text-center  w-25 h-25">
            <div class="text-center text-gray-800 mt-2">
                <h4 class="fs-4 fw-bold">{{__('common.oops_nothing_here')}} &#128540;</h4>
                <p>{{__('employees.record_leave_message')}}</p>
            </div>
        </div>
        @endif
    </div>
</div>