<div>
    <x-alert />
    @include('livewire.portal.departments.create-department')
    @include('livewire.portal.departments.edit-department')
    @include('livewire.portal.departments.import-departments')
    @include('livewire.portal.departments.assign-supervisor')
    @include('livewire.partials.delete-modal')
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
                        <li class="breadcrumb-item"><a href="{{route('portal.companies.index')}}" wire:navigate>{{ __('Companies') }}</a></li>
                        <li class="breadcrumb-item active">{{ __('Departments') }}</li>
                    </ol>
                </nav>
                <h1 class="h4 mt-n1 d-flex justify-content-start align-items-end">
                    <svg class="icon me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    {{$company->name}} - {{__('Departments Management')}}
                </h1>
                <p class="mt-n1">{{__('Manage departments and their related details')}} &#128524;</p>
            </div>
            <div class="d-flex justify-content-between mb-2">
                @can('department-create')
                <a href="#" data-bs-toggle="modal" data-bs-target="#CreateDepartmentModal" class="btn btn-sm btn-primary py-2 d-inline-flex align-items-center ">
                    <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg> {{__('New')}}
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
                    </svg> {{__('Import')}}
                </a>
                @endcan
                @can('department-export')
                <div class="mx-2" wire:loading.remove>
                    <a wire:click="export()" class="btn btn-sm btn-gray-500  py-2 d-inline-flex align-items-center {{count($departments) > 0 ? '' :'disabled'}}">
                        <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                        </svg>
                        {{__('Export')}}
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
        <div class='pb-4'>
            <div class="row ">
                <div class="col-md-4">
                    <label for="search">{{__('Search')}}: </label>
                    <input wire:model.live="query" id="search" type="text" placeholder="{{__('Search...')}}" class="form-control">
                    <p class="badge badge-info" wire:model.live="resultCount">{{$resultCount}}</p>
                </div>
                <div class="col-md-3">
                    <label for="orderBy">{{__('Order By')}}: </label>
                    <select wire:model.live="orderBy" id="orderBy" class="form-select">
                        <option value="name">{{__('Name')}}</option>
                        <option value="created_at">{{__('Created Date')}}</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="direction">{{__('Order')}}: </label>
                    <select wire:model.live="orderAsc" id="direction" class="form-select">
                        <option value="asc">{{__('Ascending')}}</option>
                        <option value="desc">{{__('Descending')}}</option>
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

        </div>
    </div>
    <div class='row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4'>
        @forelse ($departments as $department)
        <div class='col-md-3 '>
            <div class="card border-0 shadow p-3" draggable="false">
                <div class="card-header border-0 p-0 mb-2 d-flex justify-content-between align-items-start">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="avatar-md d-flex align-items-center justify-content-center fw-bold fs-5 rounded bg-primary  me-3">
                            <span class="text-gray-50">{{initials($department->name)}}</span>
                        </div>
                        <div>
                            <h3 class="h5 mb-0">{{$department->name}}</h3>
                            <div class='d-flex justify-content-start'>
                                <p class="small d-flex align-items-center mx-2">
                                    <span class='fw-bolder'> {{__('ID :')}} {{$department->id}}</span>
                                </p>
                                <p class="small d-flex align-items-center mx-2">
                                    <svg class="icon icon-xxs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>{{$department->created_at->ISOFormat('LLL')}}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="">
                        <span class="fw-normal badge super-badge badge-lg bg-{{$department->approvalStatusStyle('','boolean')}} rounded">{{$department->approvalStatusText('','boolean')}}</span>
                    </div>
                </div>
                <div class="card-body p-0 mx-2 my-1 d-flex flex-wrap justify-content-between align-items-center">
                    @if(!empty($department->depSupervisor->supervisor))
                    <a href="#" class="d-flex align-items-center">
                        <div class="avatar rounded-circle d-flex align-items-center justify-content-center fw-bold  rounded bg-gray-500  me-1">
                            <span class="text-gray-50">{{initials($department->depSupervisor->supervisor->name)}}</span>
                        </div>
                        <div class="d-none d-md-block">
                            <span class="small fw-bold text-gray-600">{{$department->depSupervisor->supervisor->first_name}}</span>
                            <div class="small fw-light text-gray-500 mt-n1">{{\Str::limit($department->depSupervisor->supervisor->email, 10)}}</div>
                        </div>
                    </a>
                    @else
                    <div class="small text-gray-500  fw-light ">
                        {{__('No Supervisor assigned !')}}
                    </div>
                    @endif
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <div class=" fw-normal d-flex align-items-center mx-2 ">
                            <svg class="icon icon-sm me-1 " fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <span class='me-1 fw-bold'>{{numberFormat(count($department->employees))}}</span> <span class='small me-1'> {{__('Employees')}}</span>
                        </div>
                        <div class=" fw-normal d-flex align-items-center ">
                            <svg class="icon icon-sm me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            <span class='me-1 fw-bold'>{{numberFormat(count($department->services))}}</span> <span class='small me-1'> {{__('Services')}}</span>
                        </div>
                    </div>
                </div>
                <hr>
                <div class='d-flex align-items-center justify-content-between'>
                    <a href="{{route('portal.services.index',['department_uuid'=>$department->uuid])}}" wire:navigate class="btn btn-sm btn-outline-gray-600 py-2 d-inline-flex align-items-center ">
                        <svg class="icon icon-xxs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <div class="d-none d-md-block">{{__('Services')}}</div>
                    </a>
                    @canany('department-update','department-delete')
                    <div>
                        @can('department-update')
                        <a href="#" wire:click.prevent="initData({{$department->id}})" data-bs-toggle="modal" data-bs-target="#EditDepartmentModal" draggable="false">
                            <svg class="icon icon-sm text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </a>
                        @endcan
                        @can('department-delete')
                        <a href="#" wire:click.prevent="initData({{$department->id}})" data-bs-toggle="modal" data-bs-target="#DeleteModal" href="#" draggable="false">
                            <svg class="icon icon-sm text-danger me-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </a>
                        @endcan
                    </div>
                    @endcanany
                </div>
            </div>
        </div>
        @empty
        <div class='col-md-12 '>
            <div class='border-prim rounded p-4 d-flex justify-content-center align-items-center flex-column mx-2'>
                @can('department-create')
                <a href="#" data-bs-toggle="modal" data-bs-target="#CreateDepartmentModal" class="btn btn-sm btn-primary py-2 mt-3 d-inline-flex align-items-center ">
                    <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg> {{__('Add Department')}}
                </a>
                @endcan
                <div class="text-center text-gray-800 mt-4">
                    <h4 class="fs-4 fw-bold">{{__('Opps nothing here')}} &#128540;</h4>
                    <p>{{__('No record found here!')}}</p>
                </div>
            </div>
        </div>
        @endforelse

    </div>
    <div class='pt-3 px-3 '>
        {{ $departments->links() }}
    </div>
</div>