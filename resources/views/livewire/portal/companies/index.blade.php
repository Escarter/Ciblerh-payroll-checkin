<div>
    <x-alert />
    @if (session()->has('message'))
        <div class="alert alert-success alert-dismissible fade show mt-2" role="alert">
            {{ session('message') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
<script>
    window.addEventListener('close-assign-manager-modal', function () {
        var modal = document.getElementById('AssignManagerModal');
        if (modal) {
            var bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
        }
    });
</script>
    @include('livewire.portal.companies.create-company')
    @include('livewire.portal.companies.edit-company')
    @include('livewire.portal.companies.import-companies')
    @livewire('portal.companies.assign-manager')
    @include('livewire.partials.delete-modal')
    @include('livewire.partials.bulk-delete-modal-generic', ['selectedItems' => $selectedCompanies, 'itemType' => count($selectedCompanies) === 1 ? __('company') : __('companies')])
    @include('livewire.partials.bulk-force-delete-modal-generic', ['selectedItems' => $selectedCompanies, 'itemType' => count($selectedCompanies) === 1 ? __('company') : __('companies')])
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
                    </ol>
                </nav>
                <h1 class="h4 mt-n2 d-flex justify-content-start align-items-end">
                    <svg class="icon me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path>
                    </svg>
                    {{__('Companies Management')}}
                </h1>
                <p class="mt-n2">{{__('Manage companies and their related details')}} &#128524;</p>
            </div>
            <div class="d-flex justify-content-between mb-2">
                @can('company-create')
                <a href="#" data-bs-toggle="modal" data-bs-target="#CreateCompanyModal" class="btn btn-sm btn-primary py-2 d-inline-flex align-items-center mx-2">
                    <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg> {{__('New')}}
                </a>
                @endcan
                @hasrole('admin')
                <a href="#" data-bs-toggle="modal" data-bs-target="#AssignManagerModal" class="btn btn-sm btn-info py-2 d-inline-flex align-items-center mx-2">
                    <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 01-8 0m8 0a4 4 0 00-8 0m8 0V5a4 4 0 00-8 0v2m8 0v2a4 4 0 01-8 0V7m8 0V5a4 4 0 00-8 0v2" />
                    </svg> {{__('Assign Manager')}}
                </a>
                @endhasrole
                @can('company-import')
                <a href="#" data-bs-toggle="modal" data-bs-target="#importCompaniesModal" class="btn btn-sm btn-tertiary py-2 d-inline-flex align-items-center">
                    <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>{{__('Import')}}
                </a>
                @endcan
                @can('company-export')
                <div class="mx-2" wire:loading.remove>
                    <a wire:click="export()" class="btn btn-sm btn-gray-500  py-2 d-inline-flex align-items-center {{count($companies) > 0 ? '' :'disabled'}}">
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path>
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="fw-extrabold h5">{{__('Total Companies')}}</h2>
                                    <h3 class="mb-1">{{numberFormat($companies_count ?? 0)}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{__('Total Companies')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat($companies_count ?? 0)}}</h3>
                                </a>
                                <div class="small d-flex mt-1">
                                    <div>{{ \Str::plural(__('Company'), $companies_count ?? 0) }} {{__('in the system')}}</div>
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
                                    <h2 class="fw-extrabold h5">{{ __(\Str::plural('Company', $active_companies ?? 0)) }}</h2>
                                    <h3 class="mb-1">{{numberFormat($active_companies ?? 0)}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{ __(\Str::plural('Company', $active_companies ?? 0)) }}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat($active_companies ?? 0)}}</h3>
                                </a>
                                <div class="small d-flex mt-1">
                                    <div>{{ \Str::plural(__('Company'), $active_companies ?? 0) }} {{__('that are active!')}}</div>
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
                                    <h2 class="fw-extrabold h5">{{ \Str::plural(__('Company'), $deleted_companies ?? 0) }}</h2>
                                    <h3 class="mb-1">{{numberFormat($deleted_companies ?? 0)}} </h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{ \Str::plural(__('Company'), $deleted_companies ?? 0) }}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat($deleted_companies ?? 0)}} </h3>
                                </a>
                                <div class="small d-flex mt-1">
                                    <div>{{ \Str::plural(__('Company'), $deleted_companies ?? 0) }} {{__('that are deleted!')}}</div>
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
        <div class="col-md-3">
            <label for="search">{{__('Search')}}: </label>
            <input wire:model.live="query" id="search" type="text" placeholder="{{__('Search...')}}" class="form-control">
            <p class="badge badge-info" wire:model.live="resultCount">{{$resultCount}}</p>
        </div>
        <div class="col-md-3">
            <label for="orderBy">{{__('Order By')}}: </label>
            <select wire:model.live="orderBy" id="orderBy" class="form-select">
                <option value="name">{{__('Name')}}</option>
                <option value="code">{{__('Code')}}</option>
                <option value="sector">{{__('Sector')}}</option>
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path>
                </svg>
                {{__('Active')}}
                <span class="badge {{ $activeTab === 'active' ? 'bg-light text-white' : 'bg-primary text-white' }} ms-1">{{ $active_companies ?? 0 }}</span>
            </button>

            <button class="btn {{ $activeTab === 'deleted' ? 'btn-tertiary' : 'btn-outline-tertiary' }}"
                wire:click="switchTab('deleted')"
                type="button">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                {{__('Deleted')}}
                <span class="badge {{ $activeTab === 'deleted' ? 'bg-light text-white' : 'bg-tertiary text-white' }} ms-1">{{ $deleted_companies ?? 0 }}</span>
            </button>
        </div>

        <!-- Bulk Actions (Left) -->
                    <div>
            @if(count($selectedCompanies) > 0)
            <div class="d-flex align-items-center gap-2">

                @if($activeTab === 'active')
                @can('company-delete')
                <button type="button"
                    class="btn btn-sm btn-danger d-flex align-items-center"
                    data-bs-toggle="modal" 
                    data-bs-target="#BulkDeleteModal">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    {{__('Move to Trash')}}
                    <span class="badge bg-light text-white ms-1">{{ count($selectedCompanies) }}</span>
                </button>
                @endcan
                @else
                @can('company-delete')
                <button wire:click="bulkRestore"
                    class="btn btn-sm btn-outline-success d-flex align-items-center me-2"
                    title="{{ __('Restore Selected Companies') }}">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    {{__('Restore Selected')}}
                    <span class="badge bg-success text-white ms-1">{{ count($selectedCompanies) }}</span>
                </button>

                <button type="button"
                    class="btn btn-sm btn-outline-danger d-flex align-items-center"
                    title="{{ __('Permanently Delete Selected Companies') }}"
                    data-bs-toggle="modal" 
                    data-bs-target="#BulkForceDeleteModal">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    {{__('Delete Forever')}}
                    <span class="badge bg-danger text-white ms-1">{{ count($selectedCompanies) }}</span>
                </button>
                @endcan
                @endif

                <button wire:click="$set('selectedCompanies', [])"
                    class="btn btn-sm btn-outline-secondary d-flex align-items-center">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    {{__('Clear')}}
                </button>
            </div>
            @endif
        </div>
    </div>

    <div class="card pb-3">
        <div class="table-responsive text-gray-700">
            <table class="table employee-table table-bordered table-hover align-items-center">
                <thead>
                    <tr>
                        <th class="border-bottom">
                            <input type="checkbox"
                                wire:model="selectAll"
                                wire:change="toggleSelectAll"
                                class="form-check-input">
                        </th>
                        <th class="border-bottom">{{__('Company')}}</th>
                        <th class="border-bottom">{{__('Details')}}</th>
                        <th class="border-bottom">{{__('Managers')}}</th>
                        <th class="border-bottom">{{__('Status')}}</th>
                        <th class="border-bottom">{{__('Navigation')}}</th>
                        @canany('company-delete','company-update')
                        <th class="border-bottom">{{__('Action')}}</th>
                        @endcanany
                    </tr>
                </thead>
                <tbody>
                    @forelse ($companies as $company)
                    <tr>
                        <td>
                            <input type="checkbox"
                                wire:click="toggleCompanySelection({{ $company->id }})"
                                {{ in_array($company->id, $selectedCompanies) ? 'checked' : '' }}
                                class="form-check-input">
                        </td>
                        <td>
                            <a href="{{route('portal.departments.index',['company_uuid'=>$company->uuid])}}" class="d-flex align-items-center">
                                <div class="avatar avatar-md d-flex align-items-center justify-content-center fw-bold fs-6 rounded bg-primary me-2"><span class="text-white">{{$company->initials}}</span></div>
                                <div class="d-block"><span class="fw-bolder fs-6">{{ucwords($company->name)}}</span>
                                    <div class="small text-gray">
                            <svg class="icon icon-xxs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg> {{\Str::limit($company->description, 30)}}
                                    </div>
                                    <div class="small text-gray d-flex align-items-end">
                                        <svg class="icon icon-xxs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg> {{__('Created')}}: {{$company->created_at->format('Y-m-d')}}
                                    </div>
                                </div>
                            </a>
                        </td>
                        <td>
                            <span class="fs-normal"><span class="fw-bolder">{{__('Departments')}}</span>: {{numberFormat(count($company->departments)) }}</span><br>
                            <span class="fs-normal"><span class="fw-bolder">{{__('Employees')}}</span>: {{numberFormat(count($company->employees)) }}</span><br>
                            <span class="fs-normal"><span class="fw-bolder">{{__('Services')}}</span>: {{numberFormat(count($company->services)) }}</span>
                        </td>
                        <td>
                            @if($company->managers->count() > 0)
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($company->managers->take(2) as $manager)
                                    <div class="d-flex align-items-center small">
                                        <div class="avatar rounded-circle d-flex align-items-center justify-content-center fw-bold rounded bg-gray-500 me-2" style="width: 24px; height: 24px;">
                                            <span class="text-gray-50" style="font-size: 10px;">{{initials($manager->first_name . ' ' . $manager->last_name)}}</span>
                    </div>
                    <div>
                                            <div class="small fw-bold text-gray-600">{{ $manager->first_name }} {{ $manager->last_name }}</div>
                                            <div class="small fw-light text-gray-500">{{\Str::limit($manager->email, 15)}}</div>
                                        </div>
                                    </div>
                                @endforeach
                                @if($company->managers->count() > 2)
                                    <div class="small text-gray-500">
                                        +{{ $company->managers->count() - 2 }} {{__('more')}}
                                    </div>
                                @endif
                            </div>
                            @else
                            <div class="small text-gray-500 fw-light">
                                {{__('No manager assigned')}}
                            </div>
                            @endif
                        </td>
                        <td>
                            <div class="mb-2">
                                <small class="text-muted fw-bold">{{__('Status')}}:</small>
                                <div class="mt-1">
                                    <span class="fw-normal badge badge-lg bg-{{$company->is_active ? 'success' : 'danger'}} rounded">{{$company->is_active ? __('Active') : __('Inactive')}}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex flex-column gap-1">
                                <a href="{{route('portal.departments.index',['company_uuid'=>$company->uuid])}}" wire:navigate class="btn btn-xs btn-outline-primary py-1 px-1 d-inline-flex align-items-center" title="{{__('View Departments')}}">
                                    <svg class="icon icon-xxs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    <span class="d-none d-lg-inline">{{__('Departments')}}</span>
                                </a>
                                <a href="{{route('portal.employees.index',['company_uuid'=>$company->id])}}" wire:navigate class="btn btn-xs btn-outline-tertiary py-1 px-1 d-inline-flex align-items-center" title="{{__('View Employees')}}">
                                    <svg class="icon icon-xxs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    </svg>
                                    <span class="d-none d-lg-inline">{{__('Employees')}}</span>
                                </a>
                            </div>
                        </td>
                        @canany('company-delete','company-update')
                        <td>
                            @if($activeTab === 'active')
                        @can('company-update')
                            <a href="#" wire:click.prevent="initData({{$company->id}})" data-bs-toggle="modal" data-bs-target="#EditCompanyModal" draggable="false" title="{{__('Edit Company')}}">
                            <svg class="icon icon-sm text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </a>
                        @endcan
                        @can('company-delete')
                            <a href="#" wire:click.prevent="initData({{$company->id}})" data-bs-toggle="modal" data-bs-target="#DeleteModal" draggable="false" title="{{__('Delete Company')}}">
                            <svg class="icon icon-sm text-danger me-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </a>
                        @endcan
                            @else
                            @can('company-delete')
                            <a href='#' wire:click.prevent="restore({{$company->id}})" title="{{__('Restore Company')}}">
                                <svg class="icon icon-sm text-success me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </a>
                            <a href='#' wire:click.prevent="forceDelete({{$company->id}})" title="{{__('Permanently Delete')}}" data-confirm="{{__('Are you sure you want to permanently delete this company? This action cannot be undone.')}}">
                                <svg class="icon icon-sm text-danger" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                            </a>
                            @endcan
                            @endif
                        </td>
                        @endcanany
                    </tr>
        @empty
                    <tr>
                        <td colspan="7" class="text-center">
                            <div class="text-center text-gray-800 mt-2">
                @can('company-create')
                                <a href="#" data-bs-toggle="modal" data-bs-target="#CreateCompanyModal" class="btn btn-sm btn-primary py-2 mt-3 d-inline-flex align-items-center">
                    <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg> {{__('Add Company')}}
                </a>
                @endcan
                                <h4 class="fs-4 fw-bold mt-3">{{__('Opps nothing here')}} &#128540;</h4>
                    <p>{{__('No record found here!')}}</p>
                </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class='pt-3 px-3 '>
        {{ $companies->links() }}
    </div>

</div>