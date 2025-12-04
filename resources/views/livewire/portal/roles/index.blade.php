<div>
    @include('livewire.portal.roles.create-role')
    @include('livewire.portal.roles.edit-role')
    @include('livewire.partials.delete-modal')
    @include('livewire.partials.bulk-delete-modal-generic', ['selectedItems' => $selectedRoles, 'itemType' => count($selectedRoles) === 1 ? __('roles.role') : __('roles.roles')])
    @include('livewire.partials.bulk-force-delete-modal-generic', ['selectedItems' => $selectedRoles, 'itemType' => count($selectedRoles) === 1 ? __('roles.role') : __('roles.roles')])
    @include('livewire.partials.force-delete-modal-generic', ['selectedItems' => $selectedRoles, 'itemType' => __('roles.role')])
    <x-alert />
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
                        <li class="breadcrumb-item"><a href="">{{ __('dashboard.home') }}</a></li>
                        <li class="breadcrumb-item active"><a href="">{{ __('roles.roles_management') }}</a></li>
                    </ol>
                </nav>
                <h1 class="h4 mt-n2 d-flex justify-content-start align-items-end">
                    <svg class="icon me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    {{__('roles.roles_management')}}
                </h1>
                <p class="mt-n2">{{__('roles.manage_all_permissions_and_roles')}}</p>
            </div>
            <div class="d-flex justify-content-between mb-2">
                @can('role-create')
                <a href="#" data-bs-toggle="modal" data-bs-target="#CreateRoleModal" class="btn btn-sm btn-primary py-2 d-inline-flex align-items-center mx-2">
                    <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg> {{__('common.new')}}
                </a>
                @endcan

                @can('role-export')
                <div class="mx-2" wire:loading.remove>
                    <a wire:click="export()" class="btn btn-sm btn-gray-500  py-2 d-inline-flex align-items-center {{count($roles) > 0 ? '' :'disabled'}}">
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
        <div class='pb-4'>
            <div class="row">
                <div class="col-md-3">
                    <label for="orderBy">{{__('common.order_by')}}: </label>
                    <select wire:model="orderBy" id="orderBy" class="form-select">
                        <option value="name">{{__('common.name')}}</option>
                        <option value="created_at">{{__('common.date_creation')}}</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="direction">{{__('Direction')}}: </label>
                    <select wire:model="orderAsc" id="direction" class="form-select">
                        <option value="asc">{{__('common.ascending')}}</option>
                        <option value="desc">{{__('common.descending')}}</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="perPage">{{__('Per Page')}}: </label>
                    <select wire:model="perPage" id="perPage" class="form-select">
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
                <span class="badge {{ $activeTab === 'active' ? 'bg-light text-white' : 'bg-primary text-white' }} ms-1">{{ $active_roles ?? 0 }}</span>
            </button>

            <button class="btn {{ $activeTab === 'deleted' ? 'btn-tertiary' : 'btn-outline-tertiary' }}"
                wire:click="switchTab('deleted')"
                type="button">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                {{__('common.deleted')}}
                <span class="badge {{ $activeTab === 'deleted' ? 'bg-light text-white' : 'bg-tertiary text-white' }} ms-1">{{ $deleted_roles ?? 0 }}</span>
            </button>
        </div>

        <!-- Bulk Actions (Left) -->
        <div>
            @if(count($roles) > 0)
            <div class="d-flex align-items-center gap-2">
                <!-- Select All Toggle -->
                <button wire:click="toggleSelectAll" 
                        class="btn btn-sm {{ $selectAll ? 'btn-primary' : 'btn-outline-primary' }} d-flex align-items-center">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ $selectAll ? __('Deselect All') : __('Select All') }}
                </button>
                
                @if(count($selectedRoles) > 0)
                @if($activeTab === 'active')
                @can('role-delete')
                <button type="button"
                    class="btn btn-sm btn-danger d-flex align-items-center"
                    data-bs-toggle="modal" 
                    data-bs-target="#BulkDeleteModal">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    {{__('common.move_to_trash')}}
                    <span class="badge bg-light text-white ms-1">{{ count($selectedRoles) }}</span>
                </button>
                @endcan
                @else
                @can('role-delete')
                <button wire:click="bulkRestore"
                    class="btn btn-sm btn-outline-success d-flex align-items-center me-2"
                    title="{{ __('Restore Selected Roles') }}">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    {{__('common.restore_selected')}}
                    <span class="badge bg-success text-white ms-1">{{ count($selectedRoles) }}</span>
                </button>

                <button type="button"
                    class="btn btn-sm btn-outline-danger d-flex align-items-center"
                    title="{{ __('Permanently Delete Selected Roles') }}"
                    data-bs-toggle="modal" 
                    data-bs-target="#BulkForceDeleteModal">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    {{__('common.delete_forever')}}
                    <span class="badge bg-danger text-white ms-1">{{ count($selectedRoles) }}</span>
                </button>
                @endcan
                @endif

                <button wire:click="$set('selectedRoles', [])"
                    class="btn btn-sm btn-outline-secondary d-flex align-items-center">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    {{__('common.clear')}}
                </button>
                @endif
            </div>
            @endif
        </div>
    </div>

    <div class='row row-cols-1 row-cols-md-2 @if(count($roles) >= 0) row-cols-xl-12 @else row-cols-xl-3 @endif  g-4'>
        @forelse ($roles as $role)

        <div class='col-md-3 h-50'>
            <div class="card card-flush h-md-100 shadow pb-4 pt-3 px-4 {{ in_array($role->id, $selectedRoles) ? 'border-primary border-3' : '' }}" 
                 draggable="false" 
                 wire:click="toggleRoleSelection({{ $role->id }})"
                 style="cursor: pointer; transition: all 0.3s ease;"
                 onmouseover="this.style.transform='translateY(-2px)'"
                 onmouseout="this.style.transform='translateY(0)'">
                <!-- Selection indicator -->
                @if(in_array($role->id, $selectedRoles))
                <div class="position-absolute top-0 end-0 p-2">
                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                        <svg class="icon icon-xs text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                </div>
                @endif
                <!-- <div class="small pt-0 d-flex align-items-end justify-content-end">
                    {{$role->created_at}}
                </div> -->
                <div class="card-header border-0 p-0 mb-2 d-flex justify-content-start align-items-start">
                    <div class="d-flex justify-content-start align-items-start">
                        <div class="avatar-md d-flex align-items-center justify-content-center fw-bold fs-5 rounded bg-primary me-3"><span class="text-gray-50">{{ initials($role->name) }}</span></div>
                        <div>
                            <h3 class="h5 mb-0">{{ucfirst($role->name)}}</h3>
                            <div class="fw-semi-bold text-gray-600 ">{{__('Number of users with this role:')}} {{$role->users_count}}</div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0 mx-3 my-1 d-flex flex-wrap justify-content-between align-items-center">
                    <ul>
                        @foreach($role->permissions->take(10) as $permission)
                        <li>{{$permission->name}}</li>
                        @endforeach
                    </ul>
                </div>
                <div class='d-flex align-items-center justify-content-between'>
                    <div>
                        <!-- <a href="" class="btn btn-sm btn-outline-primary py-2 d-inline-flex align-items-center ">
                            <svg class="icon icon-xxs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            <div class="d-none d-md-block">{{__('Voir les employés')}}</div>
                        </a> -->
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        @if($activeTab === 'active')
                        @can('role-update')
                        <a href="#" wire:click.prevent="editRole({{$role->id}})" data-bs-toggle="modal" data-bs-target="#EditRoleModal" draggable="false" onclick="event.stopPropagation();" title="{{ __('Edit Role') }}">
                            <svg class="icon icon-sm text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </a>
                        @endcan
                        @can('role-delete')
                        <a href="#" wire:click.prevent="initData({{$role->id}})" data-bs-toggle="modal" data-bs-target="#DeleteModal" draggable="false" onclick="event.stopPropagation();" title="{{ __('common.move_to_trash') }}">
                            <svg class="icon icon-sm text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </a>
                        @endcan
                        @else
                        @can('role-delete')
                        <a href="#" wire:click="restore({{$role->id}})" title="{{ __('Restore Role') }}" onclick="event.stopPropagation();">
                            <svg class="icon icon-sm text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </a>
                        <a href="#" wire:click.prevent="$set('selectedRoles', [{{$role->id}}])" data-bs-toggle="modal" data-bs-target="#ForceDeleteModal" title="{{ __('Permanently Delete') }}" onclick="event.stopPropagation();">
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
                @can('role-create')
                <a href="#" data-bs-toggle="modal" data-bs-target="#CreateCompanyModal" class="btn btn-sm btn-secondary py-2 mt-1 d-inline-flex align-items-center ">
                    <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg> {{__('Add Role ')}}
                </a>
                @endcan
            </div>
        </div>
        @endforelse

    </div>
    <div class='pt-3 px-3 '>
        {{ $roles->links() }}
    </div>
</div>