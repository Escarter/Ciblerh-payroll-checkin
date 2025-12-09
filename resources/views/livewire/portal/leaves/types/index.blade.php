<div>
    <x-alert />
    @include('livewire.portal.leaves.types.partials.create-type')
    @include('livewire.portal.leaves.types.partials.edit-type')
    @include('livewire.partials.delete-modal')
    @include('livewire.partials.restore-modal')
    @include('livewire.partials.bulk-restore-modal')
    @include('livewire.partials.bulk-delete-modal-generic', ['selectedItems' => $selectedLeaveTypes, 'itemType' => count($selectedLeaveTypes) === 1 ? __('leaves.leave_type') : __('common.leave_types')])
    @include('livewire.partials.bulk-force-delete-modal-generic', ['selectedItems' => $selectedLeaveTypes, 'itemType' => count($selectedLeaveTypes) === 1 ? __('leaves.leave_type') : __('common.leave_types')])
    @include('livewire.partials.force-delete-modal-generic', ['selectedItems' => $selectedLeaveTypes, 'itemType' => __('leaves.leave_type')])
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
                        <li class="breadcrumb-item"><a href="/" wire:navigate>{{__('dashboard.home')}}</a></li>
                        <li class="breadcrumb-item "><a href="{{route('portal.leaves.index')}}" wire:navigate>{{__('leaves.leaves')}}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{__('leaves.leave_types')}}</li>
                    </ol>
                </nav>
                <h1 class="h4 mt-n2 d-flex justify-content-start align-items-end">
                    <svg class="icon me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    {{__('leaves.leave_types')}}
                </h1>
                <p class="mt-n1 mx-2">{{__('leaves.manage_leave_types')}}&#x23F0; </p>
            </div>
            <div>
                <div class="d-flex justify-content-between">
                    @can('leave_type-create')
                    <a href="#" data-bs-toggle="modal" data-bs-target="#CreateLeaveTypeModal" class="btn btn-sm btn-primary py-2 d-inline-flex align-items-center mx-2">
                        <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg> {{__('common.new')}}
                    </a>
                    @endcan
                    @can('leave_type-import')
                    <a href="{{ route('portal.import-jobs.index') }}" class="btn btn-sm btn-tertiary py-2 d-inline-flex align-items-center">
                        <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg> {{__('common.import')}}
                    </a>
                    @endcan
                    @can('leave_type-export')
                    <div class="mx-2" wire:loading.remove>
                        <a wire:click="export()" class="btn btn-sm btn-gray-500  py-2 d-inline-flex align-items-center">
                            <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                            </svg>
                            {{__('common.export')}}
                        </a>
                    </div>
                    <div class="text-center mx-2" wire:loading wire:target="export">
                        <div class="text-center">
                            <div class="spinner-grow text-grey-300" style="width: 0.9rem; height: 0.9rem;" role="status"></div>
                            <div class="spinner-grow text-grey-300" style="width: 0.9rem; height: 0.9rem;" role="status"></div>
                            <div class="spinner-grow text-grey-300" style="width: 0.9rem; height: 0.9rem;" role="status"></div>
                            <div class="spinner-grow text-grey-300" style="width: 0.9rem; height: 0.9rem;" role="status"></div>
                        </div>
                    </div>
                    @endcan
                    
                </div>
            </div>
        </div>
    </div>

    <div class='mb-3 mt-0'>
        <div class='row'>
            <div class="col-12 col-sm-6 col-xl-4 mb-2">
                <div class="card border-0 shadow">
                    <div class="card-body">
                        <div class="row d-block d-xl-flex align-items-center">
                            <div class="col-12 col-xl-4 text-xl-center mb-2 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                                <div class="icon-shape icon-shape-tertiary rounded me-2 me-sm-0">
                                    <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="fw-extrabold h5">{{__('leaves.total_leavetypes')}}</h2>
                                    <h3 class="mb-1">{{numberFormat($leave_types_count)}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{__('leaves.total_leavetypes')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat($leave_types_count)}}</h3>
                                </a>
                              
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-4 mb-2">
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
                                    <h2 class="fw-extrabold h5"> {{__('common.active') }}</h2>
                                    <h3 class="mb-1">{{numberFormat($active_leave_types)}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5"> {{__('common.active') }}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat($active_leave_types)}}</h3>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-4 mb-2">
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
                                    <h2 class="fw-extrabold h5"> {{__('common.inactive') }}</h2>
                                    <h3 class="mb-1">{{numberFormat($inactive_leave_types)}} </h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5"> {{__('common.inactive') }}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat($inactive_leave_types)}} </h3>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row pt-2 pb-3 px-3">
        <div class="col-md-3">
            <label for="search">{{__('common.search')}}: </label>
            <input wire:model.live="query" id="search" type="text" placeholder="{{__('common.search_placeholder')}}" class="form-control">
            <p class="badge badge-info" wire:model.live="resultCount">{{$resultCount}}</p>
        </div>
        <div class="col-md-3">
            <label for="orderBy">{{__('common.order_by')}}: </label>
            <select wire:model.live="orderBy" id="orderBy" class="form-select">
                <option value="name">{{__('common.name')}}</option>
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
                {{__('common.active')}}
                <span class="badge {{ $activeTab === 'active' ? 'bg-light text-white' : 'bg-primary text-white' }} ms-1">{{ ($active_leave_types + $inactive_leave_types) ?? 0 }}</span>
            </button>

            <button class="btn {{ $activeTab === 'deleted' ? 'btn-danger' : 'btn-outline-danger' }}"
                wire:click="switchTab('deleted')"
                type="button">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                {{__('common.deleted')}}
                <span class="badge {{ $activeTab === 'deleted' ? 'bg-light text-white' : 'bg-danger text-white' }} ms-1">{{ $deleted_leave_types ?? 0 }}</span>
            </button>
        </div>

        <!-- Bulk Actions (Left) -->
        <div>
            @if($activeTab === 'active')
                <!-- Soft Delete Bulk Actions (when items selected for delete) -->
                @if(count($selectedLeaveTypes) > 0)
                <div class="d-flex align-items-center gap-2">
                    @can('leave_type-delete')
                    <button type="button"
                        class="btn btn-sm btn-outline-danger d-flex align-items-center"
                        title="{{ __('leaves.move_selected_leave_types_to_trash') }}"
                        data-bs-toggle="modal" 
                        data-bs-target="#BulkDeleteModal">
                        <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        {{__('common.move_to_trash')}}
                        <span class="badge bg-danger text-white ms-1">{{ count($selectedLeaveTypes) }}</span>
                    </button>
                    @endcan

                    <button wire:click="$set('selectedLeaveTypes', [])"
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
                @if(count($selectedLeaveTypes) > 0)
                <div class="d-flex align-items-center gap-2">
                    @can('leave_type-delete')
                    <button data-bs-toggle="modal" data-bs-target="#BulkRestoreModal"
                        class="btn btn-sm btn-outline-success d-flex align-items-center me-2"
                        title="{{ __('leaves.restore_selected_leave_types') }}">
                        <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        {{__('common.restore_selected')}}
                        <span class="badge bg-success text-white ms-1">{{ count($selectedLeaveTypes) }}</span>
                    </button>

                    <button type="button"
                        class="btn btn-sm btn-outline-danger d-flex align-items-center"
                        title="{{ __('leaves.permanently_delete_selected_leave_types') }}"
                        data-bs-toggle="modal" 
                        data-bs-target="#BulkForceDeleteModal">
                        <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        {{__('common.delete_forever')}}
                        <span class="badge bg-danger text-white ms-1">{{ count($selectedLeaveTypes) }}</span>
                    </button>
                    @endcan

                    <button wire:click="$set('selectedLeaveTypes', [])"
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

    <div class="card pb-3">
        <div class="table-responsive text-gray-700">
            <table class="table employee-table table-bordered table-hover align-items-center " id="">
                <thead>
                    <tr>
                        <th class="border-bottom">
                            <div class="form-check d-flex justify-content-center align-items-center">
                                <input class="form-check-input p-2" 
                                    wire:model.live="selectAll" 
                                    type="checkbox"
                                    wire:click="toggleSelectAll">
                            </div>
                        </th>
                        <th class="border-bottom">{{__('leaves.leave_type_id')}}</th>
                        <th class="border-bottom">{{__('leaves.leave_type')}}</th>
                        <th class="border-bottom">{{__('leaves.default_number_of_days')}}</th>
                        <th class="border-bottom">{{__('common.status')}}</th>
                        <th class="border-bottom">{{__('common.created_date')}}</th>
                        @canany('leave_type-update','leave_type-delete')
                        <th class="border-bottom">{{__('common.action')}}</th>
                        @endcanany
                    </tr>
                </thead>
                <tbody>
                    @forelse($leave_types as $leave_type)
                    <tr>
                        <td>
                            <div class="form-check d-flex justify-content-center align-items-center">
                                <input class="form-check-input" 
                                    type="checkbox"
                                    wire:click="toggleLeaveTypeSelection({{ $leave_type->id }})"
                                    {{ in_array($leave_type->id, $selectedLeaveTypes) ? 'checked' : '' }}>
                            </div>
                        </td>
                        <td>
                            <span class="fw-bold">{{$leave_type->id }}</span>
                        </td>
                        <td>
                            <span class="fw-bolder">{{ucwords($leave_type->name)}}</span>
                        </td>
                        <td>
                            <span class="fw-normal">{{$leave_type->default_number_of_days}} {{__('leaves.days')}}</span>
                        </td>
                        <td>
                            <span class="fw-normal badge super-badge badge-lg bg-{{$leave_type->approvalStatusStyle('','boolean')}} rounded">{{$leave_type->approvalStatusText('','boolean')}}</span>
                        </td>
                        <td>
                            <span class="fw-normal">{{$leave_type->created_at->format('Y-m-d')}}</span>
                        </td>
                        @canany('leave_type-update','leave_type-delete')
                        <td>
                            @if($activeTab === 'active')
                                @can('leave_type-update')
                                <a href='#' wire:click.prevent="initData({{$leave_type->id}})" data-bs-toggle="modal" data-bs-target="#EditLeaveTypeModal">
                                    <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                @endcan
                                @can('leave_type-delete')
                                <a href='#' wire:click.prevent="initData({{$leave_type->id}})" data-bs-toggle="modal" data-bs-target="#DeleteModal">
                                    <svg class="icon icon-xs text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </a>
                                @endcan
                            @else
                                @can('leave_type-delete')
                                <a href="#" wire:click.prevent="$set('leave_type_id', {{ $leave_type->id }})" data-bs-toggle="modal" data-bs-target="#RestoreModal" class="text-success me-2" title="{{__('common.restore')}}">
                                    <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                </a>
                                <a href="#" wire:click.prevent="$set('selectedLeaveTypes', [{{ $leave_type->id }}])" data-bs-toggle="modal" data-bs-target="#ForceDeleteModal" class="text-danger" title="{{__('common.delete_forever')}}">
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
                        <td colspan="8">
                            <div class="text-center text-gray-800 mt-2">
                                <h4 class="fs-4 fw-bold">{{__('common.oops_nothing_here')}} &#128540;</h4>
                                <p>{{__('leaves.no_leave_type_found')}}</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class='pt-3 px-3 '>
                {{ $leave_types->links() }}
            </div>

        </div>
    </div>
</div>