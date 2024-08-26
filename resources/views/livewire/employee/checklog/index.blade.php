<div>
    @include('livewire.employee.checklog.checkin')
    @include('livewire.employee.checklog.bulk-checkin')
    @include('livewire.employee.checklog.edit-checklog')
    @include('livewire.partials.delete-modal')
    <div class='container pt-3 pt-lg-4 pb-4 pb-lg-3 text-white'>
        <div class='d-flex flex-wrap align-items-center  justify-content-between '>
            <a href="{{route('employee.dashboard')}}" wire:navigate class="">
                <svg class="icon me-1 text-gray-500 bg-gray-300 rounded-circle p-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <x-navigation.employee-nav />
            </div>
        </div>
        <div class='d-flex flex-wrap justify-content-between align-items-center pt-3'>
            <div class=''>
                <h1 class='fw-bold display-4 text-gray-600 d-inline-flex align-items-end'>
                    <svg class="icon icon-md me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                    </svg>
                    <span>
                        {{__('Checkin Recorded')}}
                    </span>
                </h1>
                <p class="text-gray-800">{{__('View all your checkin recorded so far')}} &#128523;</p>
            </div>
            <div class=''>
                <a href="#" class="btn btn-secondary mx-2 my-3 " data-bs-toggle="modal" data-bs-target="#CheckInModal">
                    <svg class="icon icon-sm me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                    </svg>
                    {{__('Single CheckIn')}}
                </a>
                <a href="#" class="btn btn-primary  my-3 " data-bs-toggle="modal" data-bs-target="#BulkCheckInModal">
                    <svg class="icon icon-sm me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                    </svg>
                    {{__('Bulk CheckIn')}}
                </a>
            </div>
        </div>
        <div class=' mt-3'>
            <div class='row'>
                <div class='col-md-4 col-sm-12'>
                    <div class='border-prim p-3 rounded'>
                        <a href="#" class="d-flex  justify-content-between align-items-center gap-1">
                            <div class="d-flex justify-content-center align-items-center gap-2">
                                <svg class="icon me-1 text-gray-50 bg-success shadow rounded-circle p-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                </svg>
                                <div class="mb-3 mb-md-0">
                                    <h5 class="text-gray-700 fw-bold mb-0">{{numberFormat($approved_checklogs_count)}} {{ __(\Str::plural('CheckIn', $approved_checklogs_count))}} </h5>
                                    <div class=" text-gray-500 ">{{__('Approved')}} &#128516;</div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                <div class='col-md-4 col-sm-12 mt-3 mt-md-0'>
                    <div class='border-prim p-3 rounded'>
                        <a href="#" class="d-flex  justify-content-between align-items-center gap-1">
                            <div class="d-flex justify-content-center align-items-center gap-2">
                                <svg class="icon icon-md me-1 text-gray-50 bg-warning shadow rounded-circle p-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="mb-3 mb-md-0">
                                    <h5 class="text-gray-700 fw-bold mb-0">{{numberFormat($pending_checklogs_count)}} {{ __(\Str::plural('CheckIn', $pending_checklogs_count))}} </h5>
                                    <div class="text-gray-500 ">{{__('pending approval!')}} &#128530;</div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                <div class='col-md-4 col-sm-12 mt-3 mt-md-0'>
                    <div class='border-prim p-3 rounded'>
                        <a href="#" class="d-flex  justify-content-between align-items-center gap-1">
                            <div class="d-flex justify-content-center align-items-center gap-2">
                                <svg class="icon icon-md me-1 text-gray-50 bg-danger shadow rounded-circle p-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <div class="mb-3 mb-md-0">
                                    <h5 class="text-gray-700 fw-bold mb-0">{{numberFormat($rejected_checklogs_count)}} {{ __(\Str::plural('CheckIn', $rejected_checklogs_count))}} </h5>
                                    <div class="text-gray-500 ">{{__('Rejected')}} &#128560;</div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <x-alert />
        <div class="row py-2 text-gray-500 mt-3">
            <div class="col-md-3 mb-2">
                <label for="search">{{__('Search')}}: </label>
                <input wire:model="query" id="search" type="text" placeholder="{{__('Search...')}}" class="form-control">
                <p class="badge badge-info" wire:model="resultCount">{{$resultCount}}</p>
            </div>
            <div class="col-md-3 mb-2">
                <label for="orderBy">{{__('Order By')}}: </label>
                <select wire:model="orderBy" id="orderBy" class="form-select">
                    <option value="start_time">{{__('Checkin Time')}}</option>
                    <option value="end_time">{{__('Checkout Time')}}</option>
                    <option value="supervisor_approval_status">{{__('Sup Approval status')}}</option>
                    <option value="manager_approval_status">{{__('Mgr Approval status')}}</option>
                    <option value="created_at">{{__('Created Date')}}</option>
                </select>
            </div>

            <div class="col-md-3 mb-2">
                <label for="direction">{{__('Order direction')}}: </label>
                <select wire:model="orderAsc" id="direction" class="form-select">
                    <option value="asc">{{__('Ascending')}}</option>
                    <option value="desc">{{__('Descending')}}</option>
                </select>
            </div>

            <div class="col-md-3 mb-2">
                <label for="perPage">{{__('Items Per Page')}}: </label>
                <select wire:model="perPage" id="perPage" class="form-select">
                    <option value="5">5</option>
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="20">20</option>
                    <option value="25">25</option>
                </select>
            </div>
        </div>
        @if(count($checklogs) > 0)
        <div class="card">
            <div class="table-responsive pb-3 text-gray-700">
                <table class="table employee-table table-hover align-items-center " id="">
                    <thead>
                        <tr>
                            <th class="border-bottom">{{__('Hours Worked')}}</th>
                            <th class="border-bottom">{{__('Checkin Time')}}</th>
                            <th class="border-bottom">{{__('Expected Checkout Time')}}</th>
                            <th class="border-bottom">{{__('Checkout Time')}}</th>
                            <th class="border-bottom">{{__('Sup Approval')}}</th>
                            <th class="border-bottom">{{__('Mgr Approval')}}</th>
                            <th class="border-bottom">{{__('Date created')}}</th>
                            <th class="border-bottom">{{__('Action')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($checklogs as $checklog)
                        <tr>
                            <td>
                                <span class="fw-bold">{{$checklog->time_worked}}</span>
                            </td>
                            <td>
                                <span class="fw-normal">{{$checklog->start_time->format('Y-m-d H:i')}}</span>
                            </td>
                            <td>
                                <span class="fw-normal">{{\Carbon\Carbon::parse($checklog->end_time->format('Y-m-d') ."". $checklog->user->work_end_time)->format('Y-m-d H:i')}}</span>
                            </td>
                            <td>
                                <span class="fw-normal">{{$checklog->end_time->format('Y-m-d H:i')}}</span>
                            </td>
                            <td>
                                <span class="fw-normal badge super-badge badge-lg bg-{{$checklog->approvalStatusStyle('supervisor')}} rounded">{{$checklog->approvalStatusText('supervisor')}}</span>
                            </td>
                            <td>
                                <span class="fw-normal badge super-badge badge-lg bg-{{$checklog->approvalStatusStyle('manager')}} rounded">{{$checklog->approvalStatusText('manager')}}</span>
                            </td>
                            <td>
                                <span class="fw-normal">{{$checklog->created_at->format('Y-m-d')}}</span>
                            </td>
                            <td>
                                <a href='#' wire:click="initData({{$checklog->id}})" data-bs-toggle="modal" data-bs-target="#EditChecklogModal">
                                    <svg class="icon icon-xs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                <a href='#' wire:click="initData({{$checklog->id}})" data-bs-toggle="modal" data-bs-target="#DeleteModal">
                                    <svg class="icon icon-xs text-danger" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class='py-3 px-3 '>
                {{ $checklogs->links() }}
            </div>
        </div>
        @else
        <div class='border-prim rounded p-4 d-flex justify-content-center align-items-center flex-column'>
            <img src="{{asset('/img/empty.svg')}}" alt='{{__("Empty")}}' class="text-center  w-25 h-25">
            <div class="text-center text-gray-800 mt-2">
                <h4 class="fs-4 fw-bold">{{__('Opps nothing here')}} &#128540;</h4>
                <p>{{__('Record a checkin to see it here!')}}</p>
            </div>
        </div>
        @endif
    </div>
</div>