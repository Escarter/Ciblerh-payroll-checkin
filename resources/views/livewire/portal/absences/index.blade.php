<div>
    @include('livewire.portal.absences.edit-absence')
    @include('livewire.portal.absences.bulk-approval')
    @include('livewire.partials.delete-modal')
    @include('livewire.partials.bulk-delete-modal-generic', ['selectedItems' => $selectedAbsencesForDelete, 'itemType' => count($selectedAbsencesForDelete) === 1 ? __('absence record') : __('absence records')])
    @include('livewire.partials.bulk-force-delete-modal-generic', ['selectedItems' => $selectedAbsencesForDelete, 'itemType' => count($selectedAbsencesForDelete) === 1 ? __('absence record') : __('absence records')])
    <div class='p-0'>
        <div class="d-flex justify-content-between w-100 flex-wrap align-items-center">
            <div class="mb-lg-0">
                <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
                    <ol class="breadcrumb breadcrumb-dark breadcrumb-transparent">
                        <li class="breadcrumb-item">
                            <a href="#">
                                <svg class="icon icon-xxs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                            </a>
                        </li>
                        <li class="breadcrumb-item"><a href="/" wire:navigate>Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{__('Absences')}}</li>
                    </ol>
                </nav>
                <h1 class="h4 mt-n2 d-flex justify-content-start align-items-end">
                    <svg class="icon me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    {{__('Absences Management')}}
                </h1>
                <p class="mt-n1 mx-2">{{__('Manage Employees Absences request!')}} &#x23F0; </p>
            </div>
            @can('absence-export')
            <div class="mb-2 mx-3">
                <div class="btn-toolbar" wire:loading.remove>
                    <a wire:click="export" class="btn btn-sm btn-gray-500 d-inline-flex align-items-center">
                        <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        {{__('Export')}}
                    </a>
                </div>
                <div class="text-center" wire:loading wire:target="export">
                    <div class="text-center">
                        <div class="spinner-grow text-grey-300" style="width: 0.9rem; height: 0.9rem;" role="status"></div>
                        <div class="spinner-grow text-grey-300" style="width: 0.9rem; height: 0.9rem;" role="status"></div>
                        <div class="spinner-grow text-grey-300" style="width: 0.9rem; height: 0.9rem;" role="status"></div>
                        <div class="spinner-grow text-grey-300" style="width: 0.9rem; height: 0.9rem;" role="status"></div>
                    </div>
                </div>
            </div>
            @endcan
        </div>
    </div>
    <div class='mb-3 mt-0'>
        <div class='row'>
            <div class="col-12 col-sm-6 col-xl-3 mb-2">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="row d-block d-xl-flex align-items-center">
                            <div class="col-12 col-xl-4 text-xl-center mb-2 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                                <div class="icon-shape icon-shape-tertiary rounded me-2 me-sm-0">
                                    <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="fw-extrabold h5">{{__('Total Absences')}}</h2>
                                    <h3 class="mb-1">{{numberFormat(count($absences))}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{__('Total Absences')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat(count($absences))}}</h3>
                                </a>
                                <div class="small d-flex mt-1">
                                    <div>{{ \Str::plural(__('Absence'), count($absences)) }} {{__('recorded by employees')}}</div>
                                </div>
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="fw-extrabold h5">{{ __(\Str::plural('Absences', $approved_absences_count)) }} {{__("Approved")}}</h2>
                                    <h3 class="mb-1">{{numberFormat($approved_absences_count)}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{ __(\Str::plural('Absences', $approved_absences_count)) }} {{__("Approved")}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat($approved_absences_count)}}</h3>
                                </a>
                                <div class="small d-flex mt-1">
                                    <div>{{ __(\Str::plural(__('Absence'), $approved_absences_count)) }} {{__('you approved!')}}</div>
                                </div>
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
                                <div class="icon-shape icon-shape-warning rounded me-2 me-sm-0">
                                    <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="fw-extrabold h5">{{ __(\Str::plural(__('Absence'), $pending_absences_count)) }} {{__("pending")}}</h2>
                                    <h3 class="mb-1">{{numberFormat($pending_absences_count)}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{ __(\Str::plural(__('Absence'), $pending_absences_count)) }} {{__("pending")}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat($pending_absences_count)}}</h3>
                                </a>
                                <div class="small d-flex mt-1">
                                    <div>{{ __(\Str::plural(__('Absence'), $pending_absences_count)) }} {{__('pending your validation')}}</div>
                                </div>
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="fw-extrabold h5">{{ __(\Str::plural(__('Absence'), $rejected_absences_count)) }} {{__('Rejected')}}</h2>
                                    <h3 class="mb-1">{{numberFormat($rejected_absences_count)}} </h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{ __(\Str::plural(__('Absence'), $rejected_absences_count)) }} {{__('Rejected')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat($rejected_absences_count)}} </h3>
                                </a>
                                <div class="small d-flex mt-1">
                                    <div>{{ __(\Str::plural(__('Absence'), $rejected_absences_count)) }} {{__('you rejected!')}}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <x-alert />
    <div class="row py-3">
        <div class="col-md-3">
            <label for="search">{{__('Search')}}: </label>
            <input wire:model.live="query" id="search" type="text" placeholder="{{__('Search...')}}" class="form-control">
            <p class="badge badge-info" wire:model.live="resultCount">{{$resultCount}}</p>
        </div>
        <div class="col-md-3">
            <label for="orderBy">{{__('Order By')}}: </label>
            <select wire:model.live="orderBy" id="orderBy" class="form-select">
                <option value="absence_date">{{__('Absence date')}}</option>
                <option value="absence_reason">{{__('Reason')}}</option>
                <option value="approval_status">{{__('Approval status')}}</option>
                <option value="created_at">{{__('Created Date')}}</option>
            </select>
        </div>

        <div class="col-md-3">
            <label for="direction">{{__('Order direction')}}: </label>
            <select wire:model.live="orderAsc" id="direction" class="form-select">
                <option value="asc">{{__('Ascending')}}</option>
                <option value="desc">{{__('Descending')}}</option>
            </select>
        </div>

        <div class="col-md-3">
            <label for="perPage">{{__('Items Per Page')}}: </label>
            <select wire:model.live="perPage" id="perPage" class="form-select">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="20">20</option>
                <option value="25">25</option>
            </select>
        </div>
    </div>

    <!-- Table Controls: Bulk Actions (Left) + Tab Buttons (Right) -->
    <div class="d-flex justify-content-between align-items-center mb-3">

        <!-- Tab Buttons (Right) -->
        <div class="d-flex gap-2">
            <button class="btn {{ $activeTab === 'active' ? 'btn-primary' : 'btn-outline-primary' }}"
                wire:click="switchTab('active')"
                type="button">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                {{__('Active')}}
                <span class="badge {{ $activeTab === 'active' ? 'bg-light text-white' : 'bg-primary text-white' }} ms-1">{{ $active_absences ?? 0 }}</span>
            </button>

            <button class="btn {{ $activeTab === 'deleted' ? 'btn-tertiary' : 'btn-outline-tertiary' }}"
                wire:click="switchTab('deleted')"
                type="button">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                {{__('Deleted')}}
                <span class="badge {{ $activeTab === 'deleted' ? 'bg-light text-white' : 'bg-tertiary text-white' }} ms-1">{{ $deleted_absences ?? 0 }}</span>
            </button>
        </div>

        <!-- Bulk Actions (Left) -->
        <div>
            @if($activeTab === 'active')
                <!-- Bulk Actions when items are selected -->
                @if(!$bulkDisabled && count($selectedAbsences) > 0)
                <div class="d-flex align-items-center gap-2">
                    <!-- Bulk Approval Actions -->
                    @can('absence-update')
                    <button wire:click.prevent="initDataBulk('approve')" 
                        data-bs-toggle="modal" 
                        data-bs-target="#EditBulkAbsenceModal" 
                        class="btn btn-sm btn-success d-flex align-items-center">
                        <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        {{__('Bulk Approve')}}
                        <span class="badge bg-light text-dark ms-1">{{ count($selectedAbsences) }}</span>
                    </button>

                    <button wire:click.prevent="initDataBulk('reject')" 
                        data-bs-toggle="modal" 
                        data-bs-target="#EditBulkAbsenceModal" 
                        class="btn btn-sm btn-danger d-flex align-items-center">
                        <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        {{__('Bulk Reject')}}
                        <span class="badge bg-light text-dark ms-1">{{ count($selectedAbsences) }}</span>
                    </button>
                    @endcan

                    <!-- Soft Delete Actions -->
                    @can('absence-delete')
                    <button type="button"
                        class="btn btn-sm btn-outline-danger d-flex align-items-center"
                        title="{{ __('Move Selected Absence Records to Trash') }}"
                        data-bs-toggle="modal" 
                        data-bs-target="#BulkDeleteModal">
                        <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        {{__('Move to Trash')}}
                        <span class="badge bg-danger text-white ms-1">{{ count($selectedAbsences) }}</span>
                    </button>
                    @endcan

                    <!-- Clear Selection -->
                    <button wire:click="$set('selectedAbsences', [])"
                        class="btn btn-sm btn-outline-secondary d-flex align-items-center">
                        <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        {{__('Clear')}}
                    </button>
                </div>
                @endif
            @else
                <!-- Deleted Tab Bulk Actions -->
                @if(count($selectedAbsencesForDelete) > 0)
                <div class="d-flex align-items-center gap-2">
                    @can('absence-delete')
                    <button wire:click="bulkRestore"
                        class="btn btn-sm btn-outline-success d-flex align-items-center me-2"
                        title="{{ __('Restore Selected Absence Records') }}">
                        <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        {{__('Restore Selected')}}
                        <span class="badge bg-success text-white ms-1">{{ count($selectedAbsencesForDelete) }}</span>
                    </button>

                    <button type="button"
                        class="btn btn-sm btn-outline-danger d-flex align-items-center"
                        title="{{ __('Permanently Delete Selected Absence Records') }}"
                        data-bs-toggle="modal" 
                        data-bs-target="#BulkForceDeleteModal">
                        <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        {{__('Delete Forever')}}
                        <span class="badge bg-danger text-white ms-1">{{ count($selectedAbsencesForDelete) }}</span>
                    </button>
                    @endcan

                    <button wire:click="$set('selectedAbsencesForDelete', [])"
                        class="btn btn-sm btn-outline-secondary d-flex align-items-center">
                        <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        {{__('Clear')}}
                    </button>
                </div>
                @endif
            @endif
        </div>
    </div>

    <div class="card pb-3">
        <div class="table-responsive  text-gray-700">
            <table class="table employee-table table-bordered table-hover align-items-center ">
                <thead>
                    <tr>
                        <th class="border-bottom">
                            <div class="form-check d-flex justify-content-center align-items-center">
                                @if($activeTab === 'active')
                                    <!-- For active tab, use existing bulk approval selection -->
                                    <input class="form-check-input p-2" wire:model.live="selectAll" type="checkbox">
                                @else
                                    <!-- For deleted tab, use soft delete selection -->
                                    <input class="form-check-input p-2" 
                                        wire:model.live="selectAllForDelete" 
                                        type="checkbox"
                                        wire:click="toggleSelectAllForDelete">
                                @endif
                            </div>
                        </th>
                        <th class="border-bottom">{{__('Employee')}}</th>
                        <th class="border-bottom">{{__('Absence Date')}}</th>
                        <th class="border-bottom">{{__('Reason')}}</th>
                        <th class="border-bottom">{{__('Attachment Link')}}</th>
                        <th class="border-bottom">{{__('Status')}}</th>
                        <th class="border-bottom">{{__('Date created')}}</th>
                        @canany('absence-update','absence-delete')
                        <th class="border-bottom">{{__('Action')}}</th>
                        @endcanany
                    </tr>
                </thead>
                <tbody>
                    @forelse($absences as $absence)
                    <tr>
                        <td>
                            <div class="form-check d-flex justify-content-center align-items-center">
                                @if($activeTab === 'active')
                                    <!-- For active tab, use existing bulk approval selection -->
                                    <input class="form-check-input" wire:model.live="selectedAbsences" value="{{$absence->id}}" type="checkbox">
                                @else
                                    <!-- For deleted tab, use soft delete selection -->
                                    <input class="form-check-input" 
                                        type="checkbox"
                                        wire:click="toggleAbsenceSelectionForDelete({{ $absence->id }})"
                                        {{ in_array($absence->id, $selectedAbsencesForDelete) ? 'checked' : '' }}>
                                @endif
                            </div>
                        </td>
                        <td>
                            <a href="#" class="d-flex align-items-center">
                                <div class="avatar d-flex align-items-center justify-content-center fw-bold rounded bg-secondary me-3"><span class="text-white">{{$absence->user->initials}}</span></div>
                                <div class="d-block"><span class="fw-bold">{{$absence->user->name}}</span>
                                    <div class="small text-gray">{{$absence->user->email}}</div>
                                </div>
                            </a>
                        </td>
                        <td>
                            <span class="fs-bold">{{$absence->absence_date->ISOFormat('LL')}}</span>
                        </td>
                        <td>
                            <span class="fs-normal">{{\Str::limit($absence->absence_reason,50)}}</span>
                        </td>
                        <td>
                            <span class="fs-normal"><a href="{{asset('storage/attachments/'.$absence->attachment_path)}}" target="_blank">{{__('view attachment')}}</a></span>
                        </td>

                        <td>
                            <span class="fw-normal badge super-badge badge-lg bg-{{$absence->approvalStatusStyle('')}} rounded">{{$absence->approvalStatusText('')}}</span>
                        </td>
                        <td>
                            <span class="fw-normal">{{$absence->created_at->format('Y-m-d')}}</span>
                        </td>
                        @canany('absence-update','absence-delete')
                        <td>
                            @if($activeTab === 'active')
                                @can('absence-update')
                                <a href='#' wire:click="initData({{ $absence->id }})" data-bs-toggle="modal" data-bs-target="#EditAbsenceModal">
                                    <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                @endcan
                                @can('absence-delete')
                                <a href='#' wire:click="initData({{ $absence->id }})" data-bs-toggle="modal" data-bs-target="#DeleteModal">
                                    <svg class="icon icon-xs text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </a>
                                @endcan
                            @else
                                @can('absence-delete')
                                <a href="#" wire:click="restore({{ $absence->id }})" class="text-success me-2" title="{{__('Restore')}}">
                                    <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                </a>
                                <a href="#" wire:click="forceDelete({{ $absence->id }})" class="text-danger" title="{{__('Delete Forever')}}">
                                    <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </a>
                                @endcan
                            @endif
                        </td>
                        @endcanany
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center">
                            <div class="text-center text-gray-800 mt-2">
                                <h4 class="fs-4 fw-bold">{{__('Opps nothing here')}} &#128540;</h4>
                                <p>{{__('No Record Found..!')}}</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class='pt-3 px-3 '>
                {{ $absences->links() }}
            </div>
        </div>
    </div>
</div>