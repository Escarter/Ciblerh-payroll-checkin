<div>
    @include('livewire.portal.employees.manager.create-manager')
    @include('livewire.portal.employees.others.employee-form')
    @include('livewire.portal.employees.manager.edit-manager')
    @include('livewire.portal.employees.others.import-employees')
    @include('livewire.partials.delete-modal')
    @include('livewire.partials.restore-modal')
    @include('livewire.partials.bulk-restore-modal')
    @include('livewire.partials.bulk-delete-modal-generic', ['selectedItems' => $selectedEmployees, 'itemType' => (is_array($selectedEmployees) && count($selectedEmployees) === 1) ? __('employees.employee') : __('employees.employees')])
    @include('livewire.partials.bulk-force-delete-modal-generic', ['selectedItems' => $selectedEmployees, 'itemType' => (is_array($selectedEmployees) && count($selectedEmployees) === 1) ? __('employees.employee') : __('employees.employees')])
    @include('livewire.partials.force-delete-modal-generic', ['selectedItems' => $selectedEmployees, 'itemType' => __('employees.employee')])
    @livewire('portal.employees.partial.user-roles')
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
                        <li class="breadcrumb-item "><a href="{{route('portal.companies.index')}}" wire:navigate>{{__('companies.companies_management')}}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{__('employees.employees')}}</li>
                    </ol>
                </nav>
                <h1 class="h4 mt-n2 d-flex justify-content-start align-items-end">
                    <svg class="icon me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    {{__('employees.all_employees')}}
                </h1>
                <p class="mt-n1 mx-2">{{__('employees.all_employees_under_my_supervision')}} &#x23F0; </p>
            </div>

            <div class="d-flex justify-content-between mb-2">
                @can('employee-create')
                @hasrole('admin')
                <a href="#" data-bs-toggle="modal" data-bs-target="#CreateManagerModal" class="btn btn-sm btn-primary py-2 d-inline-flex align-items-center mx-2">
                    <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg> {{__('employees.create_manager')}}
                </a>
                @endhasrole
                @endcan
                @can('employee-import')
                <a href="{{ route('portal.import-jobs.index') }}" class="btn btn-sm btn-tertiary py-2 d-inline-flex align-items-center">
                    <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg> {{__('common.import')}}
                </a>
                @endcan
                @can('employee-export')
                <div class="mx-2" wire:loading.remove>
                    <a wire:click="export()" class="btn btn-sm btn-gray-500  py-2 d-inline-flex align-items-center  {{count($employees) > 0 ? '' :'disabled'}}">

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
                                    <h2 class="fw-extrabold h5">{{__('employees.total_employees')}}</h2>
                                    <h3 class="mb-1">{{numberFormat($employees_count)}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{__('employees.total_employees')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat($employees_count)}}</h3>
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
                                    <h2 class="fw-extrabold h5">{{ __('common.active') }}</h2>
                                    <h3 class="mb-1">{{numberFormat($active_employees)}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{ __('common.active') }}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat($active_employees)}}</h3>
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
                                    <h2 class="fw-extrabold h5">{{ __('common.inactive') }}</h2>
                                    <h3 class="mb-1">{{numberFormat($banned_employees)}} </h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{ __('common.inactive') }}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat($banned_employees)}} </h3>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <x-alert />

    <div class="row pt-2 pb-3">
        <div class="col-md-3">
            <label for="search">{{__('common.search')}}: </label>
            <input wire:model.live="query" id="search" type="text" placeholder="{{__('common.search_placeholder')}}" class="form-control">
            <p class="badge badge-info" wire:model.live="resultCount">{{$resultCount}}</p>
        </div>
        <div class="col-md-3">
            <label for="orderBy">{{__('common.order_by')}}: </label>
            <select wire:model.live="orderBy" id="orderBy" class="form-select">
                <option value="first_name">{{__('employees.first_name')}}</option>
                <option value="last_name">{{__('employees.last_name')}}</option>
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                {{__('common.active')}}
                <span class="badge {{ $activeTab === 'active' ? 'bg-light text-white' : 'bg-primary text-white' }} ms-1">{{ $active_employees ?? 0 }}</span>
            </button>

            <button class="btn {{ $activeTab === 'deleted' ? 'btn-danger' : 'btn-outline-danger' }}"
                wire:click="switchTab('deleted')"
                type="button">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                {{__('common.deleted')}}
                <span class="badge {{ $activeTab === 'deleted' ? 'bg-light text-white' : 'bg-danger text-white' }} ms-1">{{ $deleted_employees ?? 0 }}</span>
            </button>
        </div>

        <!-- Selection Controls and Bulk Actions (Left) -->
        <div class="d-flex align-items-center gap-2">
            @if(count($employees) > 0)
            @if($activeTab === 'active')
            <!-- Selection Controls -->
            <div class="dropdown me-2" style="position: relative;">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    {{__('common.select')}}
                    @if(is_array($selectedEmployees) && count($selectedEmployees) > 0)
                    <span class="badge bg-primary text-white ms-1">{{ count($selectedEmployees) }}</span>
                    @endif
                </button>
                <ul class="dropdown-menu shadow" style="z-index: 1050;">
                    <li><button class="dropdown-item" wire:click.prevent="selectAllVisible" type="button">{{__('absences.select_all_visible')}}</button></li>
                    <li><button class="dropdown-item" wire:click.prevent="selectAllEmployees" type="button">{{__('employees.select_all_employees')}}</button></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><button class="dropdown-item" wire:click.prevent="$set('selectedEmployees', [])" type="button">{{__('absences.deselect_all')}}</button></li>
                </ul>
            </div>
            @else
            <!-- Selection Controls for Deleted Tab -->
            <div class="dropdown me-2" style="position: relative;">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    {{__('common.select')}}
                    @if(is_array($selectedEmployeesForDelete) && count($selectedEmployeesForDelete) > 0)
                    <span class="badge bg-primary text-white ms-1">{{ count($selectedEmployeesForDelete) }}</span>
                    @endif
                </button>
                <ul class="dropdown-menu shadow" style="z-index: 1050;">
                    <li><button class="dropdown-item" wire:click.prevent="selectAllVisibleForDelete" type="button">{{__('absences.select_all_visible')}}</button></li>
                    <li><button class="dropdown-item" wire:click.prevent="selectAllDeletedEmployees" type="button">{{__('employees.select_all_deleted_employees')}}</button></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><button class="dropdown-item" wire:click.prevent="$set('selectedEmployeesForDelete', [])" type="button">{{__('absences.deselect_all')}}</button></li>
                </ul>
            </div>
            @endif

            @if((is_array($selectedEmployees) && count($selectedEmployees) > 0) || (is_array($selectedEmployeesForDelete) && count($selectedEmployeesForDelete) > 0))

            @if((is_array($selectedEmployees) && count($selectedEmployees) > 0) || (is_array($selectedEmployeesForDelete) && count($selectedEmployeesForDelete) > 0))
            <div class="d-flex align-items-center gap-2">

                @if($activeTab === 'active')
                @can('employee-delete')
                <button type="button"
                    class="btn btn-sm btn-danger d-flex align-items-center me-2"
                    data-bs-toggle="modal"
                    data-bs-target="#BulkDeleteModal">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    {{__('common.move_to_trash')}}
                    <span class="badge bg-light text-white ms-1">{{ is_array($selectedEmployees) ? count($selectedEmployees) : 0 }}</span>
                </button>
                @endcan
                @else
                @can('employee-delete')
                <button data-bs-toggle="modal" data-bs-target="#BulkRestoreModal"
                    class="btn btn-sm btn-outline-success d-flex align-items-center me-2"
                    title="{{ __('employees.restore_selected_employees') }}">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    {{__('common.restore_selected')}}
                    <span class="badge bg-success text-white ms-1">{{ is_array($selectedEmployeesForDelete) ? count($selectedEmployeesForDelete) : 0 }}</span>
                </button>

                <button type="button"
                    class="btn btn-sm btn-outline-danger d-flex align-items-center me-2"
                    title="{{ __('employees.permanently_delete_selected_employees') }}"
                    data-bs-toggle="modal"
                    data-bs-target="#BulkForceDeleteModal">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    {{__('common.delete_forever')}}
                    <span class="badge bg-danger text-white ms-1">{{ is_array($selectedEmployeesForDelete) ? count($selectedEmployeesForDelete) : 0 }}</span>
                </button>
                @endcan
                @endif

                <button wire:click="$set('selectedEmployees', []); $set('selectedEmployeesForDelete', [])"
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
    </div>

    <div class="card pb-3">
        <div class="table-responsive  text-gray-700">
            <table class="table employee-table table-hover  table-bordered align-items-center " id="">
                <thead>
                    <tr>
                        <th class="border-bottom">
                            <input type="checkbox"
                                wire:model="selectAll"
                                wire:change="toggleSelectAll"
                                class="form-check-input">
                        </th>
                        <th class="border-bottom">{{__('employees.employee')}}</th>
                        <th class="border-bottom">{{__('companies.company')}}</th>
                        <th class="border-bottom">{{__(key: 'Details')}}</th>
                        <th class="border-bottom">{{__('Roles & Status')}}</th>
                        @canany(['employee-delete','employee-update'])
                        <th class="border-bottom">{{__('common.action')}}</th>
                        @endcanany
                    </tr>
                </thead>
                <tbody>
                    @forelse($employees as $employee)
                    <tr>
                        <td>
                            <input type="checkbox"
                                wire:click="toggleEmployeeSelection({{ $employee->id }})"
                                {{ in_array($employee->id, $activeTab === 'deleted' ? $selectedEmployeesForDelete : $selectedEmployees) ? 'checked' : '' }}
                                class="form-check-input">
                        </td>
                        <td>
                            <a href="{{ $employee->getRoleNames()->first() === 'employee' ? route('portal.employee.payslips',['employee_uuid' => $employee->uuid]) : '#'}}" wire:navigate class="d-flex align-items-center">
                                <div class="avatar avatar-md d-flex align-items-center justify-content-center fw-bold fs-6 rounded bg-primary me-2"><span class="text-white">{{$employee->initials}}</span></div>
                                <div class="d-block"><span class="fw-bolder fs-6">{{ucwords($employee->name)}}</span>
                                    <div class="small text-gray">
                                        <svg class="icon icon-xxs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                        </svg> {{$employee->email}}
                                    </div>
                                    <div class="small text-gray d-flex align-items-end">
                                        <svg class="icon icon-xxs me-1 " fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg> {{$employee->pdf_password}} | {{$employee->matricule}}
                                    </div>
                                    <div class="small text-gray d-flex align-items-end">
                                        <svg class="icon icon-xxs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0V7a2 2 0 012-2h2a2 2 0 012 2v0M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V9a2 2 0 00-2-2h-2"></path>
                                        </svg> {{__('employees.created')}} : {{$employee->created_at->format('Y-m-d')}}
                                    </div>
                                </div>
                            </a>
                        </td>
                        <td>
                            <span class="fs-normal"><span class="fw-bolder">{{__('companies.company')}} </span>: {{is_null($employee->company) ? __('employees.na'): ucfirst($employee->company->name) }}</span> <br>
                            <span class="fs-normal"><span class="fw-bolder">{{__('departments.department')}}</span> : {{is_null($employee->department) ? __('employees.na'): ucfirst($employee->department->name) }}</span><br>
                            <span class="fs-normal"><span class="fw-bolder">{{__('employees.service')}}</span> : {{is_null($employee->service) ? __('employees.na'): ucfirst($employee->service->name) }}</span>
                        </td>
                        <td>
                            <span class="fs-normal"><span class="fw-bolder">{{__('employees.matricule')}} </span>: {{ $employee->matricule }}</span> <br>
                            <span class="fs-normal"><span class="fw-bolder">{{__('employees.pdf_password')}}</span> : {{ $employee->pdf_password }}</span><br>
                            <span class="fs-normal"><span class="fw-bolder">{{__('employees.professional_phone')}}</span> : {{ $employee->professional_phone_number }}</span><br>
                            <span class="fs-normal"><span class="fw-bolder">{{__('employees.personal_phone')}}</span> : {{ $employee->personal_phone_number }}</span>
                        </td>
                        <td>
                            <div class="mb-2">
                                <small class="text-muted fw-bold">{{__('common.roles')}}:</small>
                                <div class="d-flex flex-wrap align-items-center mt-1">
                                    @foreach($employee->roles as $role)
                                    <span class="fw-normal badge badge-lg bg-{{ match($role->name) {
                                        'supervisor' => 'info',
                                        'employee' => 'primary',
                                        'manager' => 'success',
                                        'admin' => 'warning',
                                        default => 'danger'
                                    } }} me-1 mb-1">{{$role->name}}</span>
                                    @endforeach
                                </div>
                            </div>
                            <hr class="my-2">
                            <div>
                                <small class="text-muted fw-bold">{{__('common.status')}}:</small>
                                <div class="mt-1">
                                    <span class="fw-normal badge super-badge badge-lg bg-{{$employee->status_style}} rounded">{{$employee->status_text}}</span>
                                </div>
                            </div>
                        </td>
                        @canany(['employee-delete','employee-update'])
                        <td>
                            @if($activeTab === 'active')
                            @can('employee-view')
                            <button wire:click="$dispatch('showUserRoles', [{{$employee->id}}])"
                                class="btn btn-sm btn-outline-info me-1"
                                title="{{ __('employees.manage_roles') }}">
                                <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"></path>
                                </svg>
                            </button>
                            @endcan

                            @can('employee-update')
                            @if($employee->hasAnyRole(['manager', 'admin', 'supervisor']) || $employee->roles->count() > 1)
                            <a href='#' wire:click.prevent="initDataManager({{$employee->id}})" data-bs-toggle="modal" data-bs-target="#EditManagerModal">
                                <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </a>
                            @elseif($employee->roles->count() === 1 && $employee->hasRole('employee'))
                            <a href='#' wire:click.prevent="initData({{$employee->id}})" data-bs-toggle="modal" data-bs-target="#EmployeeModal">
                                <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </a>
                            @endif
                            @endcan

                            @can('employee-delete')
                            @if($employee->hasAnyRole(['supervisor', 'manager']) || $employee->hasRole('admin'))
                            <a href='#' wire:click.prevent="initDataManager({{$employee->id}})" data-bs-toggle="modal" data-bs-target="#DeleteModal">
                                <svg class="icon icon-xs text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </a>
                            @else
                            <a href='#' wire:click.prevent="initData({{$employee->id}})" data-bs-toggle="modal" data-bs-target="#DeleteModal">
                                <svg class="icon icon-xs text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </a>
                            @endif
                            @endcan
                            @else
                            @can('employee-delete')
                            <a href='#' wire:click.prevent="$set('employee_id', {{$employee->id}})" data-bs-toggle="modal" data-bs-target="#RestoreModal"
                                title="{{ __('employees.restore_employee') }}">
                                <svg class="icon icon-xs text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </a>
                            <a href="#" wire:click.prevent="$set('selectedEmployees', [{{$employee->id}}])" data-bs-toggle="modal" data-bs-target="#ForceDeleteModal" draggable="false" onclick="event.stopPropagation();" title="{{__('Permanently Delete')}}">
                                <svg class="icon icon-xs text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                        <td colspan="9">
                            <div class="text-center text-gray-800 mt-2">
                                <h4 class="fs-4 fw-bold">{{__('common.oops_nothing_here')}} &#128540;</h4>
                                <p>{{__('common.no_employee_found')}}</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class='pt-3 px-3 '>
                {{ $employees->links() }}
            </div>
        </div>
    </div>
</div>