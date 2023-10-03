<div>
    @include('livewire.portal.leaves.partials.edit-leave')
    @include('livewire.portal.leaves.partials.bulk-approval')
    @include('livewire.partials.delete-modal')
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
                        <li class="breadcrumb-item active" aria-current="page">{{__('Leaves')}}</li>
                    </ol>
                </nav>
                <h1 class="h4 mt-n2 d-flex justify-content-start align-items-end">
                    <svg class="icon me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    {{__('Leaves Management')}}
                </h1>
                <p class="mt-n1 mx-2">{{__('Manage Employees Leaves request!')}} &#x23F0; </p>
            </div>
            @can('leave-export')
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
                                    <h2 class="fw-extrabold h5">{{__('Total Leaves')}}</h2>
                                    <h3 class="mb-1">{{numberFormat(count($leaves))}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{__('Total Leaves')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat(count($leaves))}}</h3>
                                </a>
                                <div class="d-flex mt-1" style="font-size:x-small;">
                                    <div>{{ \Str::plural(__('Leave'), count($leaves)) }} {{__('recorded by employees')}}</div>
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
                                    <h2 class="fw-extrabold h5">{{ __(\Str::plural('Leave', $approved_leaves_count)) }} {{__("Approved")}}</h2>
                                    <h3 class="mb-1">{{numberFormat($approved_leaves_count)}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{ __(\Str::plural('Leave', $approved_leaves_count)) }} {{__("Approved")}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat($approved_leaves_count)}}</h3>
                                </a>
                                <div class="d-flex mt-1" style="font-size:x-small;">
                                    <div>{{ __(\Str::plural(__('Leave'), $approved_leaves_count)) }} {{__('you approved!')}}</div>
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
                                    <h2 class="fw-extrabold h5">{{ __(\Str::plural(__('Leave'), $pending_leaves_count)) }} {{__("pending")}}</h2>
                                    <h3 class="mb-1">{{numberFormat($pending_leaves_count)}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{ __(\Str::plural(__('Leave'), $pending_leaves_count)) }} {{__("pending")}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat($pending_leaves_count)}}</h3>
                                </a>
                                <div class="d-flex mt-1" style="font-size:x-small;">
                                    <div>{{ __(\Str::plural(__('Leave'), $pending_leaves_count)) }} {{__('pending your validation')}}</div>
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
                                    <h2 class="fw-extrabold h5">{{ __(\Str::plural(__('Leave'), $rejected_leaves_count)) }} {{__('Rejected')}}</h2>
                                    <h3 class="mb-1">{{numberFormat($rejected_leaves_count)}} </h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <a href="#" class="d-none d-sm-block">
                                    <h2 class="h5">{{ __(\Str::plural(__('Leave'), $rejected_leaves_count)) }} {{__('Rejected')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat($rejected_leaves_count)}} </h3>
                                </a>
                                <div class="d-flex mt-1" style="font-size:x-small;">
                                    <div>{{ __(\Str::plural(__('Leave'), $rejected_leaves_count)) }} {{__('you rejected!')}}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <x-alert />
    @can('leave-update')
    <div class=' pt-1'>
        <button wire:click.prevent="initDataBulk('reject')" data-bs-toggle="modal" data-bs-target="#EditBulkLeaveModal" class="btn btn-danger btn-md mb-2" {{ $bulkDisabled ? 'disabled' : null }}>{{__('Bulk Reject')}}
        </button>

        <button wire:click.prevent="initDataBulk('approve')" data-bs-toggle="modal" data-bs-target="#EditBulkLeaveModal" class="btn btn-success text-white btn-md mb-2" {{ $bulkDisabled ? 'disabled' : null }}>{{__('Bulk Approve')}}
        </button>
    </div>
    @endcan
    <div class="row py-3">
        <div class="col-md-3">
            <label for="search">{{__('Search')}}: </label>
            <input wire:model.live="query" id="search" type="text" placeholder="{{__('Search...')}}" class="form-control">
            <p class="badge badge-info" wire:model.live="resultCount">{{$resultCount}}</p>
        </div>
        <div class="col-md-3">
            <label for="orderBy">{{__('Order By')}}: </label>
            <select wire:model.live="orderBy" id="orderBy" class="form-select">
                <option value="start_date">{{__('Start date')}}</option>
                <option value="end_date">{{__('End date')}}</option>
                <option value="leave_reason">{{__('Reason')}}</option>
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
    <div class="card">
        <div class="table-responsive pb-4 text-gray-700">
            <table class="table table-hover align-items-center dataTable">
                <thead>
                    <tr>
                        <th class="border-bottom">
                            <div class="form-check d-flex justify-content-center align-items-center">
                                <input class="form-check-input p-2" wire:model.live="selectAll" type="checkbox">
                            </div>
                        </th>
                        <th class="border-bottom">{{__('Employee')}}</th>
                        <th class="border-bottom">{{__('Leave Type')}}</th>
                        <th class="border-bottom">{{__('Period')}}</th>
                        <th class="border-bottom">{{__('Leave Reason')}}</th>
                        <th class="border-bottom">{{__('Sup Approval')}}</th>
                        <th class="border-bottom">{{__('Mgr Approval')}}</th>
                        <th class="border-bottom">{{__('Date created')}}</th>
                        @canany('leave-update','leave-delete')
                        <th class="border-bottom">{{__('Action')}}</th>
                        @endcanany
                    </tr>
                </thead>
                <tbody>
                    @forelse($leaves as $leave)
                    @if(!empty($leave->user))
                    <tr>
                        <td>
                            <div class="form-check d-flex justify-content-center align-items-center">
                                <input class="form-check-input" wire:model.live="selectedLeaves" value="{{$leave->id}}" type="checkbox">
                            </div>
                        </td>
                        <td>
                            <a href="#" class="d-flex align-items-center">
                                <div class="avatar-md d-flex align-items-center justify-content-center fw-bold fs-5 rounded bg-secondary me-3"><span class="text-white">{{$leave->user->initials}}</span></div>
                                <div class="d-block"><span class="fw-bold fs-6">{{ucwords($leave->user->name)}}</span>
                                    <div class="text-gray" style="font-size:x-small;">
                                        <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                        </svg> {{$leave->email}}
                                    </div>
                                    <div class="text-gray d-flex align-items-end" style="font-size:x-small;">
                                        {{$leave->company->name}} | {{$leave->department->name}}
                                    </div>

                                </div>
                            </a>
                        </td>
                        <td>
                            <span class="fw-normal">{{!empty($leave->leaveType) ? $leave->leaveType->name : ''}}</span>
                        </td>
                        <td>
                            <span class="fw-bold">{{__('Start Date')}}</span>: <span class="fw-normal">{{$leave->start_date->ISOFormat('LL')}}</span><br>
                            <span class="fw-bold">{{__('End Date')}}</span>: <span class="fw-normal">{{$leave->end_date->ISOFormat('LL')}}</span><br>
                            <span class="fw-bold">{{__('Number of Days')}}</span>: <span class="fw-normal">{{$leave->period}}</span><br>
                        </td>
                        <td>
                            <span class="fw-bold">{{$leave->leave_reason}}</span>
                        </td>
                        <td>
                            <span class="fw-normal badge super-badge badge-lg bg-{{$leave->approvalStatusStyle('supervisor')}} rounded">{{$leave->approvalStatusText('supervisor')}}</span>
                        </td>
                        <td>
                            <span class="fw-normal badge super-badge badge-lg bg-{{$leave->approvalStatusStyle('manager')}} rounded">{{$leave->approvalStatusText('manager')}}</span>
                        </td>
                        <td>
                            <span class="fw-normal">{{$leave->created_at->format('Y-m-d')}}</span>
                        </td>
                        @canany('leave-update','leave-delete')
                        <td>
                            @can('leave-update')
                            <a href="#" wire:click="initData({{ $leave->id }})" data-bs-toggle="modal" data-bs-target="#EditLeaveModal">
                                <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </a>
                            @endcan
                            @can('leave-delete')
                            <a hre="#" wire:click="initData({{ $leave->id }})" data-bs-toggle="modal" data-bs-target="#DeleteModal">
                                <svg class="icon icon-xs text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </a>
                            @endcan
                        </td>
                        @endcanany
                    </tr>
                    @endif
                    @empty
                    <tr>
                        <td colspan="9" class="text-center">
                            <div class="text-center text-gray-800 mt-2">
                                <h4 class="fs-4 fw-bold">{{__('Opps nothing here')}} &#128540;</h4>
                                <p>{{__('No Employee Found..!')}}</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class='d-flex justify-content-between align-items-center pt-3 px-3 '>
                <div>
                    {{__('Showing')}} {{$perPage > $leaves_count ? $leaves_count : $perPage  }} {{__(' items of ')}} {{$leaves_count}}
                </div>
                {{ $leaves->links() }}
            </div>
        </div>
    </div>
</div>