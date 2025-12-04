<div>
    <x-alert />
    @include('livewire.portal.departments.department-form')
    @include('livewire.portal.departments.import-departments')
    @include('livewire.portal.departments.assign-supervisor')
    @include('livewire.partials.delete-modal')
    @include('livewire.partials.bulk-delete-modal-generic', ['selectedItems' => $selectedDepartments, 'itemType' => count($selectedDepartments) === 1 ? __('department') : __('departments')])
    @include('livewire.partials.bulk-force-delete-modal-generic', ['selectedItems' => $selectedDepartments, 'itemType' => count($selectedDepartments) === 1 ? __('department') : __('departments')])
    @include('livewire.partials.force-delete-modal-generic', ['selectedItems' => $selectedDepartments, 'itemType' => __('department')])
    <div class='pb-0'>
        <div class="d-flex justify-content-between w-100 flex-wrap mb-0 align-items-center">
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
                        <li class="breadcrumb-item"><a href="{{route('portal.dashboard')}}" wire:navigate>{{ __('Dashboard') }}</a></li>
                        @if(auth()->user()->hasRole('supervisor'))
                            <li class="breadcrumb-item active" aria-current="page">{{ __('My Departments') }}</li>
                        @else
                            <li class="breadcrumb-item"><a href="{{route('portal.companies.index')}}" wire:navigate>{{ __('Companies') }}</a></li>
                            <li class="breadcrumb-item active">{{ __('Departments') }}</li>
                        @endif
                    </ol>
                </nav>
                <h1 class="h4 mt-n1 d-flex justify-content-start align-items-end">
                    <svg class="icon me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    @if(auth()->user()->hasRole('supervisor'))
                        {{__('My Departments')}}
                    @else
                        {{$company->name}} - {{__('Departments Management')}}
                    @endif
                </h1>
                <p class="mt-n1">{{__('Manage departments and their related details')}} &#128524;</p>
            </div>
            <div class="d-flex justify-content-between mb-2">
                @can('department-create')
                <a href="#" wire:click.prevent="openCreateModal" data-bs-toggle="modal" data-bs-target="#DepartmentModal" class="btn btn-sm btn-primary py-2 d-inline-flex align-items-center ">
                    <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg> {{__('common.new')}}
                </a>
                @endcan
                @can('department-update')
                <a href="#" data-bs-toggle="modal" data-bs-target="#AssignSupModal" class="btn btn-sm btn-tertiary py-2 d-inline-flex align-items-center mx-2">
                    <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg> {{__('Assign Supervisor')}}
                </a>
                @endcan
                @can('department-import')
                <a href="#" data-bs-toggle="modal" data-bs-target="#importDepartmentsModal" class="btn btn-sm btn-secondary py-2 d-inline-flex align-items-center">
                    <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg> {{__('common.import')}}
                </a>
                @endcan
                @can('department-export')
                <div class="mx-2" wire:loading.remove>
                    <a wire:click="export()" class="btn btn-sm btn-gray-500  py-2 d-inline-flex align-items-center {{count($departments) > 0 ? '' :'disabled'}}">
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
                                <div class="icon-shape icon-shape-primary rounded me-2 me-sm-0">
                                    <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="fw-extrabold h5">{{__('Total Departments')}}</h2>
                                    <h3 class="mb-1">{{numberFormat($departments_count ?? 0)}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{__('Total Departments')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat($departments_count ?? 0)}}</h3>
                                </a>
                                <div class="small d-flex mt-1">
                                    <div>{{ \Str::plural(__('departments.department'), $departments_count ?? 0) }} {{__('in this company')}}</div>
                                </div>
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
                                    <h2 class="fw-extrabold h5">{{ __(\Str::plural('Department', $active_departments ?? 0)) }}</h2>
                                    <h3 class="mb-1">{{numberFormat($active_departments ?? 0)}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{ __(\Str::plural('Department', $active_departments ?? 0)) }}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat($active_departments ?? 0)}}</h3>
                                </a>
                                <div class="small d-flex mt-1">
                                    <div>{{ \Str::plural(__('departments.department'), $active_departments ?? 0) }} {{__('that are active!')}}</div>
                                </div>
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="fw-extrabold h5">{{ \Str::plural(__('departments.department'), $deleted_departments ?? 0) }}</h2>
                                    <h3 class="mb-1">{{numberFormat($deleted_departments ?? 0)}} </h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{ \Str::plural(__('departments.department'), $deleted_departments ?? 0) }}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat($deleted_departments ?? 0)}} </h3>
                                </a>
                                <div class="small d-flex mt-1">
                                    <div>{{ \Str::plural(__('departments.department'), $deleted_departments ?? 0) }} {{__('that are deleted!')}}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <x-alert />

    <div class="row pt-2 pb-3">
        <div class="col-md-4">
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
            <label for="direction">{{__('Order')}}: </label>
            <select wire:model.live="orderAsc" id="direction" class="form-select">
                <option value="asc">{{__('common.ascending')}}</option>
                <option value="desc">{{__('common.descending')}}</option>
            </select>
        </div>

        <div class="col-md-2">
            <label for="perPage">{{__('Per Page')}}: </label>
            <select wire:model.live="perPage" id="perPage" class="form-select">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="20">20</option>
                <option value="25">25</option>
            </select>
        </div>
    </div>

    <!-- Table Controls: Tab Buttons + Bulk Actions + Select All -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <!-- Tab Buttons (Left) -->
        <div class="d-flex gap-2">
            <button class="btn {{ $activeTab === 'active' ? 'btn-primary' : 'btn-outline-primary' }}"
                wire:click="switchTab('active')"
                type="button">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                {{__('common.active')}}
                <span class="badge {{ $activeTab === 'active' ? 'bg-light text-white' : 'bg-primary text-white' }} ms-1">{{ $active_departments ?? 0 }}</span>
            </button>

            <button class="btn {{ $activeTab === 'deleted' ? 'btn-tertiary' : 'btn-outline-tertiary' }}"
                wire:click="switchTab('deleted')"
                type="button">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                {{__('common.deleted')}}
                <span class="badge {{ $activeTab === 'deleted' ? 'bg-light text-white' : 'bg-tertiary text-white' }} ms-1">{{ $deleted_departments ?? 0 }}</span>
            </button>
        </div>

        <!-- Right Side: Select All + Bulk Actions -->
        <div class="d-flex align-items-center gap-2">
            <!-- Select All Button -->
            @if(count($departments) > 0)
            <button wire:click="toggleSelectAll" 
                    class="btn btn-sm {{ $selectAll ? 'btn-primary' : 'btn-outline-primary' }} d-flex align-items-center">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ $selectAll ? __('Deselect All') : __('Select All') }}
            </button>
            @endif

            <!-- Bulk Actions -->
            @if(count($selectedDepartments) > 0)
            @if($activeTab === 'active')
            @can('department-delete')
            <button type="button"
                class="btn btn-sm btn-danger d-flex align-items-center"
                data-bs-toggle="modal" 
                data-bs-target="#BulkDeleteModal">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                {{__('common.move_to_trash')}}
                <span class="badge bg-light text-white ms-1">{{ count($selectedDepartments) }}</span>
            </button>
            @endcan
            @else
            @can('department-delete')
            <button wire:click="bulkRestore"
                class="btn btn-sm btn-outline-success d-flex align-items-center"
                title="{{ __('Restore Selected Departments') }}">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                {{__('common.restore_selected')}}
                <span class="badge bg-success text-white ms-1">{{ count($selectedDepartments) }}</span>
            </button>

            <button type="button"
                class="btn btn-sm btn-outline-danger d-flex align-items-center"
                title="{{ __('Permanently Delete Selected Departments') }}"
                data-bs-toggle="modal" 
                data-bs-target="#BulkForceDeleteModal">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                {{__('common.delete_forever')}}
                <span class="badge bg-danger text-white ms-1">{{ count($selectedDepartments) }}</span>
            </button>
            @endcan
            @endif

            <button wire:click="$set('selectedDepartments', [])"
                class="btn btn-sm btn-outline-secondary d-flex align-items-center">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                {{__('common.clear')}}
            </button>
            @endif
        </div>
    </div>

    <div class='row row-cols-1 @if(count($departments) >= 0) row-cols-xl-4 @else row-cols-xl-3 @endif  g-4'>
        @forelse ($departments as $department)
        <div class='col-md-6 col-xl-4'>
            <div class="card card-flush h-100 shadow-sm pb-4 pt-4 px-4 {{ in_array($department->id, $selectedDepartments) ? 'border-primary border-3' : 'border-0' }}" 
                 draggable="false" 
                 wire:click="toggleDepartmentSelection({{ $department->id }})"
                 style="cursor: pointer; transition: all 0.3s ease; min-height: 200px; border-radius: 12px;"
                 onmouseover="this.style.transform='translateY(-4px)'; this.style.boxShadow='0 8px 25px rgba(0,0,0,0.15)'"
                 onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 2px 10px rgba(0,0,0,0.1)'">
                
                <!-- Selection indicator -->
                @if(in_array($department->id, $selectedDepartments))
                <div class="position-absolute top-0 end-0 p-2">
                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 28px; height: 28px;">
                        <svg class="icon icon-xs text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </div>
                @endif
                
                <div class="card-header border-0 p-0 mb-3 d-flex justify-content-start align-items-start">
                    <div class="d-flex justify-content-start align-items-start w-100">
                        <div class="avatar avatar-lg d-flex align-items-center justify-content-center fw-bold fs-5 rounded-3 bg-gray-500 me-3 shadow-sm">
                            <span class="text-white">{{initials($department->name)}}</span>
                        </div>
                        <div class="d-block flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h5 class="fw-bold text-gray-800 mb-0">{{ucwords($department->name)}}</h5>
                                <span class="badge {{$department->is_active ? 'bg-success' : 'bg-danger'}} px-2 py-1 rounded-pill small">
                                    {{$department->is_active ? __('common.active') : __('Inactive')}}
                                </span>
                            </div>
                            @if(auth()->user()->hasRole('supervisor'))
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-info text-white px-2 py-1 rounded-pill small">
                                    <svg class="icon icon-xxs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    {{$department->company->name}}
                                </span>
                            </div>
                            @endif
                            @if(!empty($department->depSupervisor->supervisor))
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-light text-primary px-2 py-1 rounded-pill small">
                                    <svg class="icon icon-xxs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    {{$department->depSupervisor->supervisor->first_name}}
                                </span>
                            </div>
                            @endif
                            <div class="small text-gray-500 d-flex align-items-center">
                                <svg class="icon icon-xxs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                {{$department->created_at->format('M d, Y')}}
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-body p-0">
                    <div class="mb-4">
                        <div class="row g-2">
                            <div class="col-4">
                                <div class="text-center p-2 bg-light rounded-3">
                                    <div class="fw-bold fs-5 text-primary">{{$department->id}}</div>
                                    <div class="small text-gray-600">{{__('ID')}}</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-center p-2 bg-light rounded-3">
                                    <div class="fw-bold fs-5 text-primary">{{numberFormat(count($department->employees)) }}</div>
                                    <div class="small text-gray-600">{{__('Staff')}}</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-center p-2 bg-light rounded-3">
                                    <div class="fw-bold fs-5 text-success">{{numberFormat(count($department->services)) }}</div>
                                    <div class="small text-gray-600">{{__('Services')}}</div>
                                </div>
                            </div>
                           
                        </div>
                    </div>
                </div>
                
                <div class='d-flex align-items-center justify-content-between pt-3 border-top'>
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{route('portal.services.index',['department_uuid'=>$department->uuid])}}" wire:navigate class="btn btn-sm btn-outline-primary d-flex align-items-center" title="{{__('View Services')}}" onclick="event.stopPropagation();">
                            <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            {{__('Services')}}
                        </a>
                        @if(auth()->user()->hasRole('supervisor'))
                        <a href="{{route('portal.department.employees', $department->uuid)}}" wire:navigate class="btn btn-sm btn-outline-success d-flex align-items-center" title="{{__('Manage Employees')}}" onclick="event.stopPropagation();">
                            <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            {{__('Employees')}}
                        </a>
                        @endif
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        @if($activeTab === 'active')
                        @can('department-update')
                        <a href="#" wire:click.prevent="initData({{$department->id}})" data-bs-toggle="modal" data-bs-target="#DepartmentModal" draggable="false" onclick="event.stopPropagation();" title="{{__('Edit Department')}}">
                            <svg class="icon icon-sm text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </a>
                        @endcan
                        @can('department-delete')
                        <a href="#" wire:click.prevent="initData({{$department->id}})" data-bs-toggle="modal" data-bs-target="#DeleteModal" draggable="false" onclick="event.stopPropagation();" title="{{__('common.move_to_trash')}}">
                            <svg class="icon icon-sm text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </a>
                        @endcan
                        @else
                        @can('department-delete')
                        <a href="#" wire:click="restore({{$department->id}})" title="{{__('Restore Department')}}" onclick="event.stopPropagation();">
                            <svg class="icon icon-sm text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </a>
                        <a href="#" wire:click.prevent="$set('selectedDepartments', [{{$department->id}}])" data-bs-toggle="modal" data-bs-target="#ForceDeleteModal" draggable="false" onclick="event.stopPropagation();" title="{{__('Permanently Delete')}}">
                            <svg class="icon icon-sm text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </a>
                        @endcan
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class='col-md-12 '>
            <div class='border-prim rounded p-4 d-flex justify-content-center align-items-center flex-column mx-2'>
                <div class="text-center text-gray-800 mt-4">
                    <img src="{{ asset('/img/illustrations/not_found.svg') }}" class="w-25 ">
                    <h4 class="fs-4 fw-bold my-1">{{__('common.empty_set')}}</h4>
                </div>
                @can('department-create')
                <a href="#" wire:click.prevent="openCreateModal" data-bs-toggle="modal" data-bs-target="#DepartmentModal" class="btn btn-sm btn-secondary py-2 mt-1 d-inline-flex align-items-center ">
                    <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg> {{__('Add Department ')}}
                </a>
                @endcan
            </div>
        </div>
        @endforelse
    </div>
    <div class='pt-3 px-3 '>
        {{ $departments->links() }}
    </div>
</div>