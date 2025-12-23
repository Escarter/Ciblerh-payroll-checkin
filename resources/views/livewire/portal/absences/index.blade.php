<div>
    @include('livewire.portal.absences.edit-absence')
    @include('livewire.portal.absences.bulk-approval')
    @include('livewire.partials.delete-modal')
    @include('livewire.portal.absences.restore-modal')
    @include('livewire.partials.bulk-restore-modal')
    @include('livewire.partials.bulk-delete-modal-generic', ['selectedItems' => $selectedAbsencesForDelete, 'itemType' => count($selectedAbsencesForDelete) === 1 ? __('employees.employee') : __('employees.employees')])
    @include('livewire.partials.bulk-force-delete-modal-generic', ['selectedItems' => $selectedAbsencesForDelete, 'itemType' => count($selectedAbsencesForDelete) === 1 ? __('employees.employee') : __('employees.employees')])
    @include('livewire.partials.force-delete-modal-generic', ['selectedItems' => $selectedAbsencesForDelete, 'itemType' => __('absences.absence_record')])
    <div class='p-0'>
        <div class="d-flex justify-content-between w-100 flex-wrap align-items-center">
            <div class="mb-lg-0">
                <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
                    <ol class="breadcrumb breadcrumb-dark breadcrumb-transparent">
                        <li class="breadcrumb-item">
                            <a href="{{ route('portal.dashboard') }}">
                                <svg class="icon icon-xxs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg>
                            </a>
                        </li>
                        <li class="breadcrumb-item"><a href="/" wire:navigate>Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{__('absences.absences')}}</li>
                    </ol>
                </nav>
                <h1 class="h4 mt-n2 d-flex justify-content-start align-items-end">
                    <svg class="icon me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    {{__('absences.absences_management')}}
                </h1>
                <p class="mt-n1 mx-2">{{__('absences.manage_employees_absences_request')}} &#x23F0; </p>
            </div>
            @can('absence-export')
            <div class="mb-2 mx-3">
                <div class="btn-toolbar" wire:loading.remove>
                    <a wire:click="export" class="btn btn-sm btn-gray-500 d-inline-flex align-items-center">
                        <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        {{__('common.export')}}
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
                                    <h2 class="fw-extrabold h5">{{__('absences.total_absences')}}</h2>
                                    <h3 class="mb-1">{{numberFormat(count($absences))}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{__('absences.total_absences')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat(count($absences))}}</h3>
                                </a>

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
                                    <h2 class="fw-extrabold h5"> {{__('common.approved')}}</h2>
                                    <h3 class="mb-1">{{numberFormat($approved_absences_count)}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5"> {{__('common.approved')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat($approved_absences_count)}}</h3>
                                </a>

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
                                    <h2 class="fw-extrabold h5"> {{__('common.pending')}}</h2>
                                    <h3 class="mb-1">{{numberFormat($pending_absences_count)}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5"> {{__('common.pending')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat($pending_absences_count)}}</h3>
                                </a>

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
                                    <h2 class="fw-extrabold h5"> {{__('common.rejected')}}</h2>
                                    <h3 class="mb-1">{{numberFormat($rejected_absences_count)}} </h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5"> {{__('common.rejected')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat($rejected_absences_count)}} </h3>
                                </a>

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
            <label for="search">{{__('common.search')}}: </label>
            <input wire:model.live="query" id="absences-search" type="text" placeholder="{{__('common.search_placeholder')}}" class="form-control">
            <p class="badge badge-info">{{ $resultCount }}</p>
        </div>
        <div class="col-md-3">
            <label for="orderBy">{{__('common.order_by')}}: </label>
            <select wire:model.live="orderBy" id="absences-order-by" class="form-select">
                <option value="absence_date">{{__('employees.absence_date')}}</option>
                <option value="absence_reason">{{__('common.reason')}}</option>
                <option value="approval_status">{{__('common.approval_status')}}</option>
                <option value="created_at">{{__('common.created_date')}}</option>
            </select>
        </div>

        <div class="col-md-3">
            <label for="direction">{{__('common.order_direction')}}: </label>
            <select wire:model.live="orderAsc" id="direction" class="form-select">
                <option value="asc">{{__('common.ascending')}}</option>
                <option value="desc">{{__('common.descending')}}</option>
            </select>
        </div>

        <div class="col-md-3">
            <label for="perPage">{{__('common.items_per_page')}}: </label>
            <select wire:model.live="perPage" id="absences-per-page" class="form-select">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="20">20</option>
                <option value="25">25</option>
            </select>
        </div>
    </div>

    <!-- Table Controls: Bulk Actions (Left) + Tab Buttons (Right) -->
    @if(auth()->user()->can('absence-bulkdelete') && auth()->user()->can('absence-bulkrestore'))
    <div class="d-flex justify-content-between align-items-center mb-3">

        <!-- Tab Buttons (Right) -->
        <div class="d-flex gap-2">
            <button id="active-absences-tab" class="btn {{ $activeTab === 'active' ? 'btn-primary' : 'btn-outline-primary' }}"
                wire:click="switchTab('active')"
                type="button">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                {{__('common.active')}}
                <span class="badge {{ $activeTab === 'active' ? 'bg-light text-white' : 'bg-primary text-white' }} ms-1">{{ $active_absences ?? 0 }}</span>
            </button>

            <button id="deleted-absences-tab" class="btn {{ $activeTab === 'deleted' ? 'btn-danger' : 'btn-outline-danger' }}"
                wire:click="switchTab('deleted')"
                type="button">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                {{__('common.deleted')}}
                <span class="badge {{ $activeTab === 'deleted' ? 'bg-light text-white' : 'bg-danger text-white' }} ms-1">{{ $deleted_absences ?? 0 }}</span>
            </button>
        </div>

        <!-- Bulk Actions (Left) -->
        <div class="d-flex align-items-center gap-2">
            @if($activeTab === 'active')
            <!-- Selection Controls -->
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    {{__('common.select')}}
                    @if(count($selectedAbsences) > 0)
                    <span class="badge bg-primary text-white ms-1">{{ count($selectedAbsences) }}</span>
                    @endif
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" wire:click.prevent="selectAllVisible" href="#">{{__('absences.select_all_visible')}}</a></li>
                    <li><a class="dropdown-item" wire:click.prevent="selectAllAbsences" href="#">{{__('absences.select_all_absences')}}</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" wire:click.prevent="$set('selectedAbsences', [])" href="#">{{__('absences.deselect_all')}}</a></li>
                </ul>
            </div>

            <!-- Bulk Actions when items are selected -->
            @if(!$bulkDisabled && count($selectedAbsences) > 0)
            <div class="d-flex align-items-center gap-2">
                <!-- Selection Info -->


                <!-- Bulk Approval Actions -->
                @can('absence-bulkapproval')
                <button id="bulk-approve-absences-btn" wire:click.prevent="initDataBulk('approve')"
                    data-bs-toggle="modal"
                    data-bs-target="#EditBulkAbsenceModal"
                    class="btn btn-sm btn-success d-flex align-items-center">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{__('common.bulk_approve')}}
                    <span class="badge bg-light text-dark ms-1">{{ count($selectedAbsences) }}</span>
                </button>
                @endcan
                @can('absence-bulkrejection')
                <button id="bulk-reject-absences-btn" wire:click.prevent="initDataBulk('reject')"
                    data-bs-toggle="modal"
                    data-bs-target="#EditBulkAbsenceModal"
                    class="btn btn-sm btn-danger d-flex align-items-center">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{__('common.bulk_reject')}}
                    <span class="badge bg-light text-dark ms-1">{{ count($selectedAbsences) }}</span>
                </button>
                @endcan

                <!-- Soft Delete Actions -->
                @can('absence-bulkdelete')
                <button type="button"
                    class="btn btn-sm btn-outline-danger d-flex align-items-center"
                    title="{{ __('absences.move_selected_absence_records_to_trash') }}"
                    data-bs-toggle="modal"
                    data-bs-target="#BulkDeleteModal">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    {{__('common.move_to_trash')}}
                    <span class="badge bg-danger text-white ms-1">{{ count($selectedAbsences) }}</span>
                </button>
                @endcan

                <!-- Clear Selection -->
                <button wire:click="$set('selectedAbsences', [])"
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
                    @if(count($selectedAbsencesForDelete) > 0)
                    <span class="badge bg-primary text-white ms-1">{{ count($selectedAbsencesForDelete) }}</span>
                    @endif
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" wire:click.prevent="selectAllVisibleForDelete" href="#">{{__('absences.select_all_visible')}}</a></li>
                    <li><a class="dropdown-item" wire:click.prevent="selectAllDeletedAbsences" href="#">{{__('absences.select_all_deleted_absences')}}</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" wire:click.prevent="$set('selectedAbsencesForDelete', [])" href="#">{{__('absences.deselect_all')}}</a></li>
                </ul>
            </div>

            <!-- Deleted Tab Bulk Actions -->
            @if(count($selectedAbsencesForDelete) > 0)
            <div class="d-flex align-items-center gap-2">
                @can('absence-bulkrestore')
                <button data-bs-toggle="modal" data-bs-target="#BulkRestoreModal"
                    class="btn btn-sm btn-outline-success d-flex align-items-center me-2"
                    title="{{ __('absences.restore_selected_absence_records') }}">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    {{__('common.restore_selected')}}
                    <span class="badge bg-success text-white ms-1">{{ count($selectedAbsencesForDelete) }}</span>
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
                    <span class="badge bg-danger text-white ms-1">{{ count($selectedAbsencesForDelete) }}</span>
                </button>
                @endcan

                <button wire:click="$set('selectedAbsencesForDelete', [])"
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

    <div class="card pb-3">
        <div class="table-responsive  text-gray-700">
            <table class="table employee-table table-bordered table-hover align-items-center ">
                <thead class="">
                    <tr>
                        <th class="border-0 px-4 py-2 text-muted fw-medium">
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
                        <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('employees.employee')}}</th>
                        <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('employees.absence_date')}}</th>
                        <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('common.reason')}}</th>
                        <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('common.attachment_link')}}</th>
                        <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('common.status')}}</th>
                        <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('common.created_date')}}</th>
                        @canany(['absence-update','absence-delete'])
                        <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('common.action')}}</th>
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
                                <div class="avatar d-flex align-items-center justify-content-center fw-bold rounded bg-primary me-3"><span class="text-white">{{$absence->user->initials}}</span></div>
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
                        @canany(['absence-update','absence-delete'])
                        <td>
                            @if($activeTab === 'active')
                            @can('absence-update')
                            <a href='#' id="edit-absence-{{ $absence->id }}" wire:click="initData({{ $absence->id }})" data-bs-toggle="modal" data-bs-target="#EditAbsenceModal">
                                <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </a>
                            @endcan
                            @can('absence-delete')
                            <a href='#' id="delete-absence-{{ $absence->id }}" wire:click="initData({{ $absence->id }})" data-bs-toggle="modal" data-bs-target="#DeleteModal">
                                <svg class="icon icon-xs text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </a>
                            @endcan
                            @else
                            @can('absence-delete')
                            <a href="#" id="restore-absence-{{ $absence->id }}" wire:click.prevent="$set('absence_id', {{ $absence->id }})" data-bs-toggle="modal" data-bs-target="#RestoreModal" class="text-success me-2" title="{{__('Restore')}}">
                                <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </a>
                            <a href="#" wire:click.prevent="$set('selectedAbsencesForDelete', [{{ $absence->id }}])" data-bs-toggle="modal" data-bs-target="#ForceDeleteModal" class="text-danger" title="{{__('common.delete_forever')}}">
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
                                <h4 class="fs-4 fw-bold">{{__('common.oops_nothing_here')}} &#128540;</h4>
                                <p>{{__('common.no_records_found')}}</p>
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