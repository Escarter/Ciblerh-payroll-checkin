<div>
    <div class='pb-3'>
        <div class="d-flex justify-content-between w-100 flex-wrap mb-0 align-items-center">
            <div class="mb-lg-0">

                <h1 class="h4 mt-n2 d-flex justify-content-start align-items-end">
                    {{__('Welcome')}}, {{auth()->user()->name}}
                </h1>
                <p class="mt-n2">{{__('Manage companies and their related details')}} &#128524;</p>
            </div>
            <div class="d-flex justify-content-between">

            </div>
        </div>
        <div style="">
            <div class='pb-4 d-flex justify-content-end'>
                <div class="row gap-1">
                    @hasanyrole('manager|admin')
                    <div class="col">
                        <label for="company">{{__('Company')}}: </label>
                        <select wire:model.live="selectedCompanyId" class="form-select">
                            <option value="all" selected>{{__('All Companies')}}</option>
                            @foreach ($companies as $company)
                            <option value="{{$company->id}}">{{$company->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    @endhasanyrole
                    <div class="col">
                        <label for="selectedDepartmentId">{{__('Department')}}: </label>
                        <select wire:model.live="selectedDepartmentId" class="form-select">
                            <option value="all" selected>{{__('All Departments')}}</option>
                            @foreach ($departments as $department)
                            <option value="{{$department->id}}">{{$department->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col">
                        <label for="period">{{__('Period')}}: </label>
                        <select wire:model.live="period" class="form-select  @error('period') is-invalid @enderror">
                            <option value="all_time" selected>{{__('All time')}}</option>
                            <option value="last_15_days">{{__('Last 15 Days')}}</option>
                            <option value="last_month">{{__('Last Month')}}</option>
                            <option value="last_3_months">{{__('Last 3 months')}}</option>
                            <option value="last_6_months">{{__('Last 6 months')}}</option>
                            <option value="last_9_months">{{__('Last 9 months')}}</option>
                            <option value="last_year">{{__('Last year')}}</option>

                        </select>
                        @error('period')
                        <div class="invalid-feedback">{{$message}}</div>
                        @enderror
                    </div>

                </div>

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
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                        </svg>
                                    </div>
                                    <div class="d-sm-none">
                                        <h2 class="fw-extrabold h5">{{ __('Total Checkins') }}</h2>
                                        <h3 class="mb-1">{{numberFormat($checklogs_count)}}</h3>
                                    </div>
                                </div>
                                <div class="col-12 col-xl-8 px-xl-0">
                                    <a href="#" class="d-none d-sm-block">
                                        <h2 class="h5">{{__('Total Checkins')}}</h2>
                                        <h3 class="fw-extrabold mb-1">{{numberFormat($checklogs_count)}}</h3>
                                    </a>
                                    <div class="d-flex mt-1" style="font-size:x-small;">
                                        <div>{{ __(\Str::plural(__('Total Checkin'), $checklogs_count)) }}</div>
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
                                        <h3 class="fw-extrabold h5">{{ __(\Str::plural('Approved Checkin', $approved_checklogs_count)) }}</h3>
                                        <h3 class="mb-1">{{numberFormat($approved_checklogs_count)}}</h3>
                                    </div>
                                </div>
                                <div class="col-12 col-xl-8 px-xl-0">
                                    <a href="#" class="d-none d-sm-block">
                                        <h2 class="h5">{{ __(\Str::plural('Approved Checkin', $approved_checklogs_count)) }}</h2>
                                        <h3 class="fw-extrabold mb-1">{{numberFormat($approved_checklogs_count)}}</h3>
                                    </a>
                                    <div class="d-flex mt-1" style="font-size:x-small;">
                                        <div>{{ __(\Str::plural(__('Checkin'), $approved_checklogs_count)) }} {{__('you approved!')}}</div>
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
                                        <h2 class="fw-extrabold h5">{{ __(\Str::plural('Pending Checkin', $pending_checklogs_count)) }}</h2>
                                        <h3 class="mb-1">{{numberFormat($pending_checklogs_count)}}</h3>
                                    </div>
                                </div>
                                <div class="col-12 col-xl-8 px-xl-0">
                                    <a href="#" class="d-none d-sm-block">
                                        <h2 class="h5">{{ __(\Str::plural('Pending Checkin', $pending_checklogs_count)) }}</h2>
                                        <h3 class="fw-extrabold mb-1">{{numberFormat($pending_checklogs_count)}}</h3>
                                    </a>
                                    <div class="d-flex mt-1" style="font-size:x-small;">
                                        <div>{{ __(\Str::plural('Checkin', $pending_checklogs_count)) }} {{__('pending your validation')}}</div>
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
                                        <h2 class="fw-extrabold h5">{{ __(\Str::plural('Rejected Checkin', $rejected_checklogs_count)) }}</h2>
                                        <h3 class="mb-1">{{numberFormat($rejected_checklogs_count)}} </h3>
                                    </div>
                                </div>
                                <div class="col-12 col-xl-8 px-xl-0">
                                    <a href="#" class="d-none d-sm-block">
                                        <h2 class="h5">{{ __(\Str::plural('Rejected Checkin', $rejected_checklogs_count)) }}</h2>
                                        <h3 class="fw-extrabold mb-1">{{numberFormat($rejected_checklogs_count)}} </h3>
                                    </a>
                                    <div class="d-flex mt-1" style="font-size:x-small;">
                                        <div>{{ __(\Str::plural('Checkin', $rejected_checklogs_count)) }} {{__('you rejected!')}}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class='mb-3 mt-0'>
            <div class='row'>
                @hasanyrole('manager|admin')
                <div class="col mb-2">
                    <div class="card border-0 shadow">
                        <div class="card-body">
                            <div class="row d-block d-xl-flex align-items-center">
                                <div class="col-12 col-xl-4 text-xl-center mb-2 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                                    <div class="icon-shape text-secondary rounded me-2 me-sm-0">
                                        <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"></path>
                                        </svg>
                                    </div>
                                    <div class="d-sm-none">
                                        <h2 class="fw-extrabold h5">{{__('Companies')}}</h2>
                                        <h3 class="mb-1">{{numberFormat($total_companies)}}</h3>
                                    </div>
                                </div>
                                <div class="col-12 col-xl-8 px-xl-0">
                                    <a href="#" class="d-none d-sm-block">
                                        <h2 class="h5">{{__('Companies')}}</h2>
                                        <h3 class="fw-extrabold mb-1">{{numberFormat($total_companies)}}</h3>
                                    </a>
                                    <div class="d-flex mt-1" style="font-size:x-small;">
                                        <div>{{ \Str::plural(__('Company'), $total_companies) }} {{__('you manage')}}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endhasanyrole
                <div class="col mb-2">
                    <div class="card border-0 shadow">
                        <div class="card-body">
                            <div class="row d-block d-xl-flex align-items-center">
                                <div class="col-12 col-xl-4 text-xl-center mb-2 mb-xl-0 d-flex align-items-center justify-content-xl-center ">
                                    <div class="icon-shape text-info rounded me-2 me-sm-0">
                                        <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                    </div>
                                    <div class="d-sm-none">
                                        <h2 class="fw-extrabold h5">{{ __(\Str::plural('Department', $total_departments)) }}</h2>
                                        <h3 class="mb-1">{{numberFormat($total_departments)}}</h3>
                                    </div>
                                </div>
                                <div class="col-12 col-xl-8 px-xl-0">
                                    <a href="#" class="d-none d-sm-block">
                                        <h2 class="h5">{{ __(\Str::plural('Department', $total_departments)) }}</h2>
                                        <h3 class="fw-extrabold mb-1">{{numberFormat($total_departments)}}</h3>
                                    </a>
                                    <div class="d-flex mt-1" style="font-size:x-small;">
                                        <div>{{ \Str::plural(__('Department'), $total_departments) }} {{__('for these companies!')}}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col mb-2">
                    <div class="card border-0 shadow">
                        <div class="card-body">
                            <div class="row d-block d-xl-flex align-items-center">
                                <div class="col-12 col-xl-4 text-xl-center mb-2 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                                    <div class="icon-shape text-purple rounded me-2 me-sm-0">
                                        <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path>
                                        </svg>
                                    </div>
                                    <div class="d-sm-none">
                                        <h2 class="fw-extrabold h5">{{ __(\Str::plural('Service', $total_services)) }}</h2>
                                        <h3 class="mb-1">{{numberFormat($total_services)}}</h3>
                                    </div>
                                </div>
                                <div class="col-12 col-xl-8 px-xl-0">
                                    <a href="#" class="d-none d-sm-block">
                                        <h2 class="h5">{{ __(\Str::plural('Service', $total_services)) }}</h2>
                                        <h3 class="fw-extrabold mb-1">{{numberFormat($total_services)}}</h3>
                                    </a>
                                    <div class="d-flex mt-1" style="font-size:x-small;">
                                        <div>{{ __(\Str::plural('Service', $total_services)) }} {{__('for these departments')}}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col mb-2">
                    <div class="card border-0 shadow">
                        <div class="card-body">
                            <div class="row d-block d-xl-flex align-items-center">
                                <div class="col-12 col-xl-4 text-xl-center mb-2 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                                    <div class="icon-shape icon-shape-gray rounded me-2 me-sm-0">
                                        <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="d-sm-none">
                                        <h2 class="fw-extrabold h5">{{ __(\Str::plural(__('Employee'), $total_employees)) }}</h2>
                                        <h3 class="mb-1">{{numberFormat($total_employees)}} </h3>
                                    </div>
                                </div>
                                <div class="col-12 col-xl-8 px-xl-0">
                                    <a href="#" class="d-none d-sm-block">
                                        <h2 class="h5">{{ __(\Str::plural(__('Employee'), $total_employees)) }}</h2>
                                        <h3 class="fw-extrabold mb-1">{{numberFormat($total_employees)}} </h3>
                                    </a>
                                    <div class="d-flex mt-1" style="font-size:x-small;">
                                        <div>{{ __(\Str::plural(__('Employee'), $total_employees)) }} {{__('you manage!')}}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class='col-12 col-md-9'>
                <div class="card bg-yellow-100 border-0 shadow pb-5">
                    <div class="card-header d-sm-flex flex-row align-items-center flex-0">
                        <div class="d-block mb-3 mb-sm-0">
                            <div class="fs-5 fw-normal mb-2">{{__('Total payslips sent')}}</div>
                            <h2 class="fs-3 fw-extrabold">{{number_format($payslips_failed + $payslips_success)}}</h2>
                            <div class="small mt-2">
                                <span class="fw-normal me-2"> {{now()->subMonth()->format('F') ." - ". now()->year}} {{__('success rate')}} - </span>
                                <span class="fas fa-angle-up text-success"></span> <span class="text-success fw-bold">{{ ceil(($payslips_last_month_success_count/($payslips_last_month_total_count == 0 ? 1 : $payslips_last_month_total_count))*100)}}%</span>
                            </div>
                        </div>
                        <div class="d-block ms-auto">
                            <div class="d-flex align-items-center text-end"><span class="dot rounded-circle bg-success me-2"></span> <span class="fw-normal small">{{__('Success')}}</span></div>
                            <div class="d-flex align-items-center text-end"><span class="dot rounded-circle bg-secondary me-2"></span> <span class="fw-normal small">{{__('Failed')}}</span></div>
                            <div class="d-flex align-items-center text-end mb-2"><span class="dot rounded-circle bg-gray-500 me-2"></span> <span class="fw-normal small">{{__('Pending')}}</span></div>
                        </div>
                    </div>
                    <div class="card-body p-2 ">
                        <div class='line-chart ct-double-octave flex-grow'></div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-3 ">
                <div class="card border-0 shadow mb-3">
                    <div class="card-body">
                        <a href="{{route('portal.payslips.index')}}" class="row d-block d-xl-flex align-items-center">
                            <div class="col-12 col-xl-3 text-xl-center mb-3 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                                <div class="icon-shape icon-shape-success rounded me-4 me-sm-0">
                                    <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" stroke-linejoin="round" stroke-width="2" d="M3 19v-8.93a2 2 0 01.89-1.664l7-4.666a2 2 0 012.22 0l7 4.666A2 2 0 0121 10.07V19M3 19a2 2 0 002 2h14a2 2 0 002-2M3 19l6.75-4.5M21 19l-6.75-4.5M3 10l6.75 4.5M21 10l-6.75 4.5m0 0l-1.14.76a2 2 0 01-2.22 0l-1.14-.76"></path>
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="fw-extrabold h5">{{__('Payslips Sent')}}</h2>
                                    <h3 class="mb-1">{{numberFormat($payslips_success)}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-9 px-xl-0">
                                <div class="d-none d-sm-block">
                                    <h2 class="h5">{{__('Payslips Sent')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat($payslips_success)}}</h3>
                                </div>
                                <small class="d-flex align-items-center">
                                    <svg class="icon icon-xxs text-gray-600 ms-2 me-1" stroke="currentColor" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>{{$payslips_success_week}} {{__('sent this week')}}, {{__('success rate')}} <span class="text-success fw-bolder mx-1">{{ceil(($payslips_success / (($payslips_failed + $payslips_success) == 0 ? 1 :($payslips_failed + $payslips_success)))*100)}}%</span>
                                </small>
                            </div>
                        </a>
                    </div>
                </div>
                <div class="card border-0 shadow mb-3">
                    <div class="card-body">
                        <a href="{{route('portal.payslips.index')}}" class="row d-block d-xl-flex align-items-center">
                            <div class="col-12 col-xl-4 text-xl-center mb-3 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                                <div class="icon-shape icon-shape-danger rounded me-4 me-sm-0">
                                    <svg class="icon icon-md text-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div class="d-sm-none">
                                    <h2 class="fw-extrabold h5">{{__('Payslips not sent')}}</h2>
                                    <h3 class="mb-1">{{numberFormat($payslips_failed)}}</h3>
                                </div>
                            </div>
                            <div class="col-12 col-xl-8 px-xl-0">
                                <div class="d-none d-sm-block">
                                    <h2 class="h5">{{__('Payslips not sent')}}</h2>
                                    <h3 class="fw-extrabold mb-1">{{numberFormat($payslips_failed)}}</h3>
                                </div>
                                <small>
                                    <svg class="icon icon-xxs text-red-500 ms-2 me-1" stroke="currentColor" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                    </svg>
                                    {{$payslips_failed_week}} {{__('failed this week')}}
                                </small>
                                <div class="small d-flex mt-1">
                                    <div><span class="text-success fw-bolder me-1">{{ceil($payslips_failed / (($payslips_failed + $payslips_success) == 0 ? 1 : $payslips_failed + $payslips_success)*100)}}%</span> {{__('failure rate')}}</div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="card border-0 shadow mb-3">
                    <div class="card-header d-flex flex-row align-items-center flex-0 border-bottom">
                        <div class="d-block">
                            <div class="h6 fw-normal text-gray mb-2">{{__('Total payslips sent this week')}}</div>
                            <h2 class="h3 fw-extrabold">{{$payslips_failed_week + $payslips_success_week}}</h2>
                            <div class="small mt-2"><span class="fas fa-angle-up text-success"></span> <span class="text-success fw-bold">{{ ceil(($payslips_success_week/(($payslips_success_week+$payslips_failed_week) == 0 ? 1 : ($payslips_success_week+$payslips_failed_week) ))*100)}}%</span></div>
                        </div>
                        <div class="d-block ms-auto">
                            <div class="d-flex align-items-center text-end"><span class="dot rounded-circle bg-success me-2"></span> <span class="fw-normal small">{{__('Success')}}</span></div>
                            <div class="d-flex align-items-center text-end"><span class="dot rounded-circle bg-secondary me-2"></span> <span class="fw-normal small">{{('Failed')}}</span></div>
                            <div class="d-flex align-items-center text-end mb-2"><span class="dot rounded-circle bg-gray-600 me-2"></span> <span class="fw-normal small">{{('Pending')}}</span></div>
                        </div>
                    </div>
                    <div class="card-body p-2">
                        <div class="bar-chart"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="mt-3 ">
        <div class='d-flex justify-content-between align-items-end mx-2'>
            <h5 class="h5">{{__("Last Checkin per employee")}}</h5>
            <div>
                <a href='{{route("portal.checklogs.index")}}' class='btn btn-secondary'>{{__("View all")}}</a>
            </div>
        </div>
        <div class="card mt-2">
            <div class="table-responsive text-gray-700">
                <table class="table table-hover align-items-center dataTable">
                    <thead>
                        <tr>
                            <th class="border-bottom">{{__('Employee')}}</th>
                            <th class="border-bottom">{{__('Checkin Time')}}</th>
                            <th class="border-bottom">{{__('Checkout Time')}}</th>
                            <th class="border-bottom">{{__('Hours Worked')}}</th>
                            <th class="border-bottom">{{__('Sup Approval')}}</th>
                            <th class="border-bottom">{{__('Mgr Approval')}}</th>
                            <th class="border-bottom">{{__('Date created')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($checklogs as $checklog)
                        <tr>
                            <td>
                                <a href="#" class="d-flex align-items-center">
                                    <div class="avatar-md d-flex align-items-center justify-content-center fw-bold fs-5 rounded bg-secondary me-3"><span class="text-white">{{!empty($checklog->user) ? $checklog->user->initials : ''}}</span></div>
                                    <div class="d-block"><span class="fw-bold fs-6">{{ucwords($checklog->userFull_name)}}</span>
                                        <div class="small text-gray">
                                            <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"></path>
                                            </svg> {{$checklog->email}}
                                        </div>
                                        <div class="small text-gray d-flex align-items-end">
                                            {{$checklog->company_name}} | {{$checklog->department_name}} | {{$checklog->service_name}}
                                        </div>

                                    </div>
                                </a>
                            </td>
                            <td>
                                <span class="fw-normal">{{$checklog->start_time}}</span>
                            </td>
                            <td>
                                <span class="fw-normal">{{$checklog->end_time}}</span>
                            </td>
                            <td>
                                <span class="fw-bold">{{$checklog->time_worked}}</span>
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
                        </tr>
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
            </div>
        </div>
    </div>
    <div class='mt-5'>
        <div class='d-flex justify-content-between align-items-end mx-2'>
            <h5 class="h5">{{__("Lastest Audit logs")}}</h5>
            <div>
                <a href='{{route("portal.auditlogs.index")}}' class='btn btn-secondary'>{{__("View all")}}</a>
            </div>
        </div>
        <div class="card mt-2">
            <div class="table-responsive text-gray-700">
                <table class="table employee-table table-hover align-items-center ">
                    <thead>
                        <tr>
                            <th class="border-bottom">{{__('Employee')}}</th>
                            <th class="border-bottom">{{__('Action Type')}}</th>
                            <th class="border-bottom">{{__('Action Performed')}}</th>
                            <th class="border-bottom">{{__('Date created')}}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr>
                            <td>
                                <a href="#" class="d-flex align-items-center">
                                    <div class="avatar d-flex align-items-center justify-content-center fw-bold rounded bg-secondary me-3"><span class="text-white">{{initials($log->user)}}</span></div>
                                    <div class="d-block"><span class="fw-bold">{{$log->user}}</span>
                                        <div class="small text-gray">{{$log->user}}</div>
                                    </div>
                                </a>
                            </td>
                            <td>
                                <span class="fw-normal badge super-badge badge-lg bg-{{$log->style}} rounded">{{$log->action_type}}</span>
                            </td>
                            <td>
                                <span class="fs-normal">{!! $log->action_perform !!}</span>
                            </td>
                            <td>
                                <span class="fw-normal">{{$log->created_at->format('Y-m-d')}}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">
                                <div class="text-center text-gray-800 mt-2">
                                    <h4 class="fs-4 fw-bold">{{__('Opps nothing here')}} &#128540;</h4>
                                    <p>{{__('No Record Found..!')}}</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>

            </div>
        </div>
    </div>

    @push('scripts')
    <script type="text/javascript">
        new Chartist.Bar('.bar-chart', {
            labels: {!!html_entity_decode($chart_daily[0]) !!},
            series: [{!!html_entity_decode($chart_daily[3]) !!},
                {!!html_entity_decode($chart_daily[2]) !!},
                {!!html_entity_decode($chart_daily[1]) !!},
            ]
        }, {
            low: 0,
            showArea: true,
            plugins: [
                Chartist.plugins.tooltip()
            ],
            axisX: {
                // On the x-axis start means top and end means bottom
                position: 'end'
            },
            axisY: {
                // On the y-axis start means left and end means right
                showGrid: false,
                showLabel: false,
                offset: 0
            }
        });
        new Chartist.Line('.line-chart', {
            labels: {!!html_entity_decode($chart_data[0]) !!},
            series: [{!!html_entity_decode($chart_data[3]) !!},
                {!!html_entity_decode($chart_data[2]) !!},
                {!!html_entity_decode($chart_data[1]) !!},
            ]
        }, {
            low: 0,
            scaleMinSpace: 10,
            showArea: true,
            fullWidth: true,
            plugins: [
                Chartist.plugins.tooltip()
            ],
            axisX: {
                // On the x-axis start means top and end means bottom
                position: 'end',
                showGrid: true
            },
            axisY: {
                // On the y-axis start means left and end means right
                showGrid: true,
                showLabel: true,
            }
        });
    </script>
    @endpush
</div>