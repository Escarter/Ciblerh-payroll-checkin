<div>
    <x-alert />
    <div class='container pt-3 pt-lg-4 pb-4 pb-lg-3 text-white'>
        <div class='d-flex flex-wrap align-items-center  justify-content-between '>
            <a href="{{route('employee.dashboard')}}" wire:navigate class="">
                <svg class="icon me-1 text-gray-500 bg-gray-300 rounded-circle p-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <x-layouts.navigation.employee-nav />
            </div>
        </div>
        <div class='d-flex flex-wrap justify-content-between align-items-center pt-3'>
            <div class=''>
                <h1 class='fw-bold display-4 text-gray-600 d-inline-flex align-items-end'>
                    <svg class="icon icon-md me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span>
                        {{__('Payslips')}}
                    </span>
                </h1>
                <p class="text-gray-800">{{__('View all your payslips')}} &#129297; </p>
            </div>
            <div class=''>
            </div>
        </div>

        <div class="row py-2 text-gray-600  mb-2">
            <div class="col-md-3">
                <label for="search">{{__('Search')}}: </label>
                <input wire:model.live="query" id="search" type="text" placeholder="{{__('Search...')}}" class="form-control">
                <p class="badge badge-info" wire:model.live="resultCount">{{$resultCount}}</p>
            </div>
            <div class="col-md-3">
                <label for="orderBy">{{__('Order By')}}: </label>
                <select wire:model.live="orderBy" id="orderBy" class="form-select">
                    <option value="first_name">{{__('First Name')}}</option>
                    <option value="last_name">{{__('Last Name')}}</option>
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
        @if(count($payslips) > 0)
        <div class="card">
            <div class="table-responsive text-gray-700">
                <table class="table table-hover align-items-center dataTable">
                    <thead>
                        <tr>
                            <th class="border-bottom">{{__('Year')}}</th>
                            <th class="border-bottom">{{__('Month')}}</th>
                            <th class="border-bottom">{{__('Sent Date')}}</th>
                            <th class="border-bottom d-flex justify-content-center">{{__('Download')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payslips as $payslip)
                        <tr>
                            <td>
                                <span class="fw-bold">{{$payslip->year}}</span>
                            </td>
                            <td>
                                <span class="fs-normal">{{$payslip->month}}</span>
                            </td>
                            <td>
                                <span class="fw-normal">{{$payslip->created_at}}</span>
                            </td>
                            <td>
                                @can('payslip-read')
                                <div class='d-flex justify-content-center'>
                                    <a href='#' class="text-info " wire:click="generatePDF({{ $payslip->id }})" wire:loading.remove>
                                        <svg class="icon icon-xs" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 7.5h-.75A2.25 2.25 0 004.5 9.75v7.5a2.25 2.25 0 002.25 2.25h7.5a2.25 2.25 0 002.25-2.25v-7.5a2.25 2.25 0 00-2.25-2.25h-.75m-6 3.75l3 3m0 0l3-3m-3 3V1.5m6 9h.75a2.25 2.25 0 012.25 2.25v7.5a2.25 2.25 0 01-2.25 2.25h-7.5a2.25 2.25 0 01-2.25-2.25v-.75" />
                                        </svg>
                                    </a>
                                    <div class="text-center" wire:loading wire:target="generatePDF({{ $payslip->id }})">
                                        <div class="text-center">
                                            <div class="spinner-grow text-grey-300" style="width: 0.5rem; height: 0.5rem;" role="status"></div>
                                            <div class="spinner-grow text-grey-300" style="width: 0.5rem; height: 0.5rem;" role="status"></div>
                                            <div class="spinner-grow text-grey-300" style="width: 0.5rem; height: 0.5rem;" role="status"></div>
                                        </div>
                                    </div>
                                </div>
                                @endcan
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class=' py-3 px-3'>
                
                    {{ $payslips->links() }}
                </div>
            </div>
        </div>
        @else
        <div class='border-prim rounded p-4 d-flex justify-content-center align-items-center flex-column'>
            <img src="{{asset('/img/empty.svg')}}" alt='{{__("Empty")}}' class="text-center  w-25 h-25">
            <div class="text-center text-gray-800 mt-2">
                <h4 class="fs-4 fw-bold">{{__('Opps nothing here')}} &#128540;</h4>
                <p>{{__('Record overtime worked to see them here!')}}</p>
            </div>
        </div>
        @endif
    </div>
</div>