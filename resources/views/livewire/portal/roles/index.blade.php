<div>
    @include('livewire.portal.roles.create-role')
    @include('livewire.partials.delete-modal')
    @include('livewire.partials.bulk-delete-modal-generic', ['selectedItems' => $selectedRoles, 'itemType' => count($selectedRoles) === 1 ? __('roles.role') : __('roles.roles')])
    @include('livewire.partials.bulk-force-delete-modal-generic', ['selectedItems' => $selectedRoles, 'itemType' => count($selectedRoles) === 1 ? __('roles.role') : __('roles.roles')])
    @include('livewire.partials.force-delete-modal-generic', ['selectedItems' => $selectedRoles, 'itemType' => __('roles.role')])
    @include('livewire.partials.restore-modal')
    @include('livewire.partials.bulk-restore-modal')
    <x-alert />
    <div class='pb-0'>
        <div class="d-flex justify-content-between w-100 flex-wrap mb-0 align-items-center">
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
                <button type="button" wire:click="openCreateModal" id="create-role-btn" class="btn btn-sm btn-primary py-2 d-inline-flex align-items-center mx-2">
                    <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg> {{__('common.new')}}
                </button>
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
                    <label for="direction">{{__('common.direction')}}: </label>
                    <select wire:model="orderAsc" id="direction" class="form-select">
                        <option value="asc">{{__('common.ascending')}}</option>
                        <option value="desc">{{__('common.descending')}}</option>
                    </select>
                </div>

                <div class="col-md-2">
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

        </div>
    </div>

    <!-- Table Controls: Bulk Actions (Left) + Tab Buttons (Right) -->
    @if(auth()->user()->can('role-bulkdelete') && auth()->user()->can('role-bulkrestore'))
    <div class="d-flex justify-content-between align-items-center mb-3">
        <!-- Tab Buttons (Right) -->
        <div class="d-flex gap-2">
            <button class="btn {{ $activeTab === 'active' ? 'btn-primary' : 'btn-outline-primary' }}"
                wire:click="switchTab('active')"
                type="button"
                id="active-roles-tab">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                {{__('common.active')}}
                <span class="badge {{ $activeTab === 'active' ? 'bg-light text-white' : 'bg-primary text-white' }} ms-1">{{ $active_roles ?? 0 }}</span>
            </button>

            <button class="btn {{ $activeTab === 'deleted' ? 'btn-danger' : 'btn-outline-danger' }}"
                wire:click="switchTab('deleted')"
                type="button"
                id="deleted-roles-tab">
                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                {{__('common.deleted')}}
                <span class="badge {{ $activeTab === 'deleted' ? 'bg-light text-white' : 'bg-danger text-white' }} ms-1">{{ $deleted_roles ?? 0 }}</span>
            </button>
        </div>

        <!-- Bulk Actions (Left) -->
        <div>
            @if(count($roles) > 0)
            <div class="d-flex align-items-center gap-2">
                <!-- Select All Toggle -->
                <button wire:click="toggleSelectAll"
                        id="select-all-roles-checkbox"
                        class="btn btn-sm {{ $selectAll ? 'btn-primary' : 'btn-outline-primary' }} d-flex align-items-center">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ $selectAll ? __('common.deselect_all') : __('common.select_all') }}
                </button>
                
                @if(count($selectedRoles) > 0)
                @if($activeTab === 'active')
                @can('role-bulkdelete')
                <button type="button"
                    id="bulk-delete-roles-btn"
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
                @can('role-bulkrestore')
                <button data-bs-toggle="modal" data-bs-target="#BulkRestoreModal"
                    class="btn btn-sm btn-outline-success d-flex align-items-center me-2"
                    title="{{ __('common.restore_selected_roles') }}">
                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    {{__('common.restore_selected')}}
                    <span class="badge bg-success text-white ms-1">{{ count($selectedRoles) }}</span>
                </button>
                @endcan

                @can('role-delete')
                <button type="button"
                    class="btn btn-sm btn-outline-danger d-flex align-items-center"
                    title="{{ __('common.permanently_delete_selected_roles') }}"
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
    @endif

    <div class="card pb-3">
        <div class="table-responsive text-gray-700">
            <table class="table table-bordered table-hover align-items-center">
                <thead class="">
                    <tr>
                        <th class="border-0 px-4 py-2 text-muted fw-medium">
                            <input type="checkbox"
                                id="select-all-roles"
                                wire:model="selectAll"
                                wire:change="toggleSelectAll"
                                class="form-check-input">
                        </th>
                        <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('roles.role')}}</th>
                        <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('roles.users_count')}}</th>
                        <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('roles.permissions')}}</th>
                        <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('common.created_date')}}</th>
                        @canany(['role-update','role-delete'])
                        <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('common.action')}}</th>
                        @endcanany
                    </tr>
                </thead>
                <tbody>
                    @forelse($roles as $role)
                    <tr class="{{ in_array($role->id, $selectedRoles) ? 'table-primary' : '' }}">
                        <td>
                            <input type="checkbox"
                                id="role-checkbox-{{ $role->id }}"
                                wire:click="toggleRoleSelection({{ $role->id }})"
                                {{ in_array($role->id, $selectedRoles) ? 'checked' : '' }}
                                class="form-check-input">
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-md d-flex align-items-center justify-content-center fw-bold fs-5 rounded bg-primary me-3">
                                    <span class="text-white">{{ initials($role->name) }}</span>
                                </div>
                                <div class="d-block">
                                    <span class="fw-bold fs-6">{{ucfirst($role->name)}}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="fw-normal">{{$role->users_count}}</span>
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($role->permissions->take(8) as $permission)
                                <span class="badge badge-lg bg-gray-500 text-white mb-1">{{$this->getTranslatedPermissionName($permission->name)}}</span>
                                @endforeach
                                @if($role->permissions->count() > 8)
                                <span class="badge badge-lg bg-info text-white mb-1">+{{$role->permissions->count() - 8}} more</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            <span class="fw-normal">{{$role->created_at->format('Y-m-d')}}</span>
                        </td>
                        @canany(['role-update','role-delete'])
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if($activeTab === 'active')
                                @can('role-update')
                                <a href="#" wire:click.prevent="openEditModal({{$role->id}})" title="{{ __('roles.edit_role') }}" id="edit-role-{{$role->id}}">
                                    <svg class="icon icon-sm text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                @endcan
                                @can('role-delete')
                                <a href="#" wire:click.prevent="initData({{$role->id}})" data-bs-toggle="modal" data-bs-target="#DeleteModal" title="{{ __('common.move_to_trash') }}" id="delete-role-{{$role->id}}">
                                    <svg class="icon icon-sm text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </a>
                                @endcan
                                @else
                                @can('role-delete')
                                <a href="#" wire:click.prevent="$set('role_id', {{$role->id}})" data-bs-toggle="modal" data-bs-target="#RestoreModal" title="{{ __('common.restore') }}" id="restore-role-{{$role->id}}" class="text-success">
                                    <svg class="icon icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                </a>
                                <a href="#" wire:click.prevent="$set('selectedRoles', [{{$role->id}}])" data-bs-toggle="modal" data-bs-target="#ForceDeleteModal" title="{{ __('common.permanently_delete') }}" class="text-danger">
                                    <svg class="icon icon-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </a>
                                @endcan
                                @endif
                            </div>
                        </td>
                        @endcanany
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ auth()->user()->canany(['role-update','role-delete']) ? '6' : '5' }}" class="text-center">
                            <div class="text-center text-gray-800 mt-4">
                                <img src="{{ asset('/img/illustrations/404.svg') }}" class="w-25 ">
                                <h4 class="fs-4 fw-bold my-1">{{__('common.empty_set')}}</h4>
                                @can('role-create')
                                <a href="#" data-bs-toggle="modal" data-bs-target="#CreateRoleModal" class="btn btn-sm btn-secondary py-2 mt-1 d-inline-flex align-items-center">
                                    <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg> {{__('common.add_role')}}
                                </a>
                                @endcan
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class='pt-3 px-3 '>
                {{ $roles->links() }}
            </div>
        </div>
    </div>
</div>