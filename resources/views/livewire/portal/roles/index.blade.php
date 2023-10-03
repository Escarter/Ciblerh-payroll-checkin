<div>
    @include('livewire.portal.roles.create-role')
    @include('livewire.portal.roles.edit-role')
    <x-delete-modal />
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
                        <li class="breadcrumb-item"><a href="">{{ __('Home') }}</a></li>
                        <li class="breadcrumb-item active"><a href="">{{ __('Roles Management') }}</a></li>
                    </ol>
                </nav>
                <h1 class="h4 mt-n2 d-flex justify-content-start align-items-end">
                    <svg class="icon me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    {{__('Roles Management')}}
                </h1>
                <p class="mt-n2">{{__('Manage all permissions and roles')}}</p>
            </div>
            <div class="d-flex justify-content-between mb-2">
                @can('role-create')
                <a href="#" data-bs-toggle="modal" data-bs-target="#CreateRoleModal" class="btn btn-sm btn-primary py-2 d-inline-flex align-items-center mx-2">
                    <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg> {{__('New')}}
                </a>
                @endcan

                @can('role-export')
                <div class="mx-2" wire:loading.remove>
                    <a wire:click="export()" class="btn btn-sm btn-gray-500  py-2 d-inline-flex align-items-center {{count($roles) > 0 ? '' :'disabled'}}">
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
            <div class="row">
                <div class="col-md-3">
                    <label for="orderBy">{{__('Trier par')}}: </label>
                    <select wire:model="orderBy" id="orderBy" class="form-select">
                        <option value="name">{{__('Name')}}</option>
                        <option value="created_at">{{__('Date Creation')}}</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="direction">{{__('Direction')}}: </label>
                    <select wire:model="orderAsc" id="direction" class="form-select">
                        <option value="asc">{{__('Ascending')}}</option>
                        <option value="desc">{{__('Descending')}}</option>
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
    <div class='row row-cols-1 row-cols-md-2 @if(count($roles) >= 0) row-cols-xl-12 @else row-cols-xl-3 @endif  g-4'>
        @forelse ($roles as $role)

        <div class='col-md-3 h-50'>
            <div class="card card-flush h-md-100 shadow pb-4 pt-3 px-4 " draggable="false">
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
                    <div>
                        <!-- @can('rol-update')
                        <a href="#" wire:click.prevent="initData({{$role->id}})" data-bs-toggle="modal" data-bs-target="#EditRoleModal" draggable="false">
                            <svg class="icon icon-sm text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </a>
                        @endcan -->
                        @can('role-delete')
                        <a href="#" wire:click.prevent="initData({{$role->id}})" data-bs-toggle="modal" data-bs-target="#DeleteModal" href="#" draggable="false">
                            <svg class="icon icon-sm text-danger me-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class='col-md-12 '>
            <div class='border-prim rounded p-4 d-flex justify-content-center align-items-center flex-column mx-2'>

                <div class="text-center text-gray-800 mt-4">
                    <img src="{{ asset('/img/illustrations/not_found.svg') }}" class="w-25 ">
                    <h4 class="fs-4 fw-bold my-1">{{__('Empty set.')}}</h4>
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
    <div class='d-flex justify-content-between align-items-center pt-3 px-3 '>
        <div>
            {{__('Showing')}} {{$perPage > $roles_count ? $roles_count : $perPage  }} {{__(' items of ')}} {{$roles_count}}
        </div>
        {{ $roles->links() }}
    </div>
</div>