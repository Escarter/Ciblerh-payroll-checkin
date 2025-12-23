<div>
    <div class='pb-3 pt-3'>
        <div class="d-flex justify-content-between w-100 flex-wrap mb-0 align-items-center">
            <div class="mb-lg-0 d-flex flex-column align-items-start">
                <h1 class="h4 mt-n2 d-flex justify-content-start align-items-end">
                    {{__('dashboard.welcome')}} {{auth()->user()->name}}
                </h1>
                <p class="mt-n2">{{__('companies.manage_companies_details')}} &#128524;</p>
            </div>
            <div class="d-flex justify-content-between align-items-start">
                <!-- Live Clock -->
                @include('components.live-clock')
            </div>
        </div>
        <div style="">
            <div class='mb-4'>
                <div class="row gap-1">
                    @hasanyrole('manager|admin')
                    <div class="col">
                        <label for="company">{{__('employees.company')}}: </label>
                        <select wire:model.live="selectedCompanyId" id="selectedCompanyId" class="form-select">
                            <option value="all" selected>{{__('companies.all_companies')}}</option>
                            @foreach ($companies as $company)
                            <option value="{{$company->id}}">{{$company->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    @endhasanyrole
                    <div class="col">
                        <label for="selectedDepartmentId">{{__('employees.department')}}: </label>
                        <select wire:model.live="selectedDepartmentId" id="selectedDepartmentId" class="form-select">
                            <option value="all" selected>{{__('dashboard.all_departments')}}</option>
                            @foreach ($departments as $department)
                            <option value="{{$department->id}}">{{$department->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col">
                        <label for="period">{{__('dashboard.period')}}: </label>
                        <select wire:model.live="period" id="period" class="form-select  @error('period') is-invalid @enderror">
                            <option value="all_time" selected>{{__('dashboard.all_time')}}</option>
                            <option value="last_15_days">{{__('dashboard.last_15_days')}}</option>
                            <option value="last_month">{{__('dashboard.last_month')}}</option>
                            <option value="last_3_months">{{__('dashboard.last_3_months')}}</option>
                            <option value="last_6_months">{{__('dashboard.last_6_months')}}</option>
                            <option value="last_9_months">{{__('dashboard.last_9_months')}}</option>
                            <option value="last_year">{{__('dashboard.last_year')}}</option>

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
                    <div class="card border-0 shadow h-100">
                        <div class="card-body">
                            <div class="row d-block d-xl-flex align-items-center">
                                <div class="col-12 col-xl-4 text-xl-center mb-2 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                                    <div class="icon-shape icon-shape-tertiary rounded me-2 me-sm-0">
                                        <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                        </svg>
                                    </div>
                                    <div class="d-sm-none">
                                        <h2 class="fw-extrabold h5">{{ __('dashboard.total_checkins') }}</h2>
                                        <h3 class="mb-1">{{numberFormat($checklogs_count)}}</h3>
                                    </div>
                                </div>
                                <div class="col-12 col-xl-8 px-xl-0">
                                    <a href="{{route('portal.checklogs.index')}}" class="d-none d-sm-block">
                                        <h2 class="h5">{{__('dashboard.total_checkins')}}</h2>
                                        <h3 class="fw-extrabold mb-1">{{numberFormat($checklogs_count)}}</h3>
                                    </a>
                                    <div class="d-flex mt-1" style="font-size:x-small;">
                                        <div>{{ __(\Str::plural('dashboard.total_checkin', $checklogs_count)) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3 mb-2">
                    <div class="card border-0 shadow h-100">
                        <div class="card-body">
                            <div class="row d-block d-xl-flex align-items-center">
                                <div class="col-12 col-xl-4 text-xl-center mb-2 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                                    <div class="icon-shape icon-shape-success rounded me-2 me-sm-0">
                                        <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                        </svg>
                                    </div>
                                    <div class="d-sm-none">
                                        <h3 class="fw-extrabold h5">{{ __(\Str::plural('dashboard.approved_checkin', $approved_checklogs_count)) }}</h3>
                                        <h3 class="mb-1">{{numberFormat($approved_checklogs_count)}}</h3>
                                    </div>
                                </div>
                                <div class="col-12 col-xl-8 px-xl-0">
                                    <a href="{{route('portal.checklogs.index')}}" class="d-none d-sm-block">
                                        <h2 class="h5">{{ __(\Str::plural('dashboard.approved_checkin', $approved_checklogs_count)) }}</h2>
                                        <h3 class="fw-extrabold mb-1">{{numberFormat($approved_checklogs_count)}}</h3>
                                    </a>
                                    <div class="d-flex mt-1" style="font-size:x-small;">
                                        <div>{{ __(\Str::plural('dashboard.checkin', $approved_checklogs_count)) }} {{__('dashboard.you_approved')}}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3 mb-2">
                    <div class="card border-0 shadow h-100">
                        <div class="card-body">
                            <div class="row d-block d-xl-flex align-items-center">
                                <div class="col-12 col-xl-4 text-xl-center mb-2 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                                    <div class="icon-shape icon-shape-warning rounded me-2 me-sm-0">
                                        <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="d-sm-none">
                                        <h2 class="fw-extrabold h5">{{ __(\Str::plural('dashboard.pending_checkin', $pending_checklogs_count)) }}</h2>
                                        <h3 class="mb-1">{{numberFormat($pending_checklogs_count)}}</h3>
                                    </div>
                                </div>
                                <div class="col-12 col-xl-8 px-xl-0">
                                    <a href="{{route('portal.checklogs.index')}}" class="d-none d-sm-block">
                                        <h2 class="h5">{{ __(\Str::plural('dashboard.pending_checkin', $pending_checklogs_count)) }}</h2>
                                        <h3 class="fw-extrabold mb-1">{{numberFormat($pending_checklogs_count)}}</h3>
                                    </a>
                                    <div class="d-flex mt-1" style="font-size:x-small;">
                                        <div>{{ __(\Str::plural('dashboard.checkin', $pending_checklogs_count)) }} {{__('dashboard.pending_your_validation')}}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3 mb-2">
                    <div class="card border-0 shadow h-100">
                        <div class="card-body">
                            <div class="row d-block d-xl-flex align-items-center">
                                <div class="col-12 col-xl-4 text-xl-center mb-2 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                                    <div class="icon-shape icon-shape-danger rounded me-2 me-sm-0">
                                        <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                    </div>
                                    <div class="d-sm-none">
                                        <h2 class="fw-extrabold h5">{{ __(\Str::plural('dashboard.rejected_checkin', $rejected_checklogs_count)) }}</h2>
                                        <h3 class="mb-1">{{numberFormat($rejected_checklogs_count)}} </h3>
                                    </div>
                                </div>
                                <div class="col-12 col-xl-8 px-xl-0">
                                    <a href="{{route('portal.checklogs.index')}}" class="d-none d-sm-block">
                                        <h2 class="h5">{{ __(\Str::plural('dashboard.rejected_checkin', $rejected_checklogs_count)) }}</h2>
                                        <h3 class="fw-extrabold mb-1">{{numberFormat($rejected_checklogs_count)}} </h3>
                                    </a>
                                    <div class="d-flex mt-1" style="font-size:x-small;">
                                        <div>{{ __(\Str::plural('dashboard.checkin', $rejected_checklogs_count)) }} {{__('dashboard.you_rejected')}}</div>
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
                                        <h2 class="fw-extrabold h5">{{__('companies.companies')}}</h2>
                                        <h3 class="mb-1">{{numberFormat($total_companies)}}</h3>
                                    </div>
                                </div>
                                <div class="col-12 col-xl-8 px-xl-0">
                                    <a href="{{route('portal.companies.index')}}" class="d-none d-sm-block">
                                        <h2 class="h5">{{__('companies.companies')}}</h2>
                                        <h3 class="fw-extrabold mb-1">{{numberFormat($total_companies)}}</h3>
                                    </a>
                                    <div class="d-flex mt-1" style="font-size:x-small;">
                                        <div>{{ \Str::plural(__('companies.company'), $total_companies) }} {{__('companies.you_manage')}}</div>
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
                                        <h2 class="fw-extrabold h5">{{ __(\Str::plural('departments.department', $total_departments)) }}</h2>
                                        <h3 class="mb-1">{{numberFormat($total_departments)}}</h3>
                                    </div>
                                </div>
                                <div class="col-12 col-xl-8 px-xl-0">
                                    <a href="{{route('portal.companies.index')}}" class="d-none d-sm-block">
                                        <h2 class="h5">{{ __(\Str::plural('departments.department', $total_departments)) }}</h2>
                                        <h3 class="fw-extrabold mb-1">{{numberFormat($total_departments)}}</h3>
                                    </a>
                                    <div class="d-flex mt-1" style="font-size:x-small;">
                                        <div>{{ __(\Str::plural('departments.department', $total_departments)) }} {{__('departments.for_these_companies')}}</div>
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
                                        <h2 class="fw-extrabold h5">{{ __(\Str::plural('services.service', $total_services)) }}</h2>
                                        <h3 class="mb-1">{{numberFormat($total_services)}}</h3>
                                    </div>
                                </div>
                                <div class="col-12 col-xl-8 px-xl-0">
                                    <a href="{{route('portal.companies.index')}}" class="d-none d-sm-block">
                                        <h2 class="h5">{{ __(\Str::plural('services.service', $total_services)) }}</h2>
                                        <h3 class="fw-extrabold mb-1">{{numberFormat($total_services)}}</h3>
                                    </a>
                                    <div class="d-flex mt-1" style="font-size:x-small;">
                                        <div>{{ __(\Str::plural('services.service', $total_services)) }} {{__('services.for_these_departments')}}</div>
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
                                        <h2 class="fw-extrabold h5">{{ __(\Str::plural('employees.employee', $total_employees)) }}</h2>
                                        <h3 class="mb-1">{{numberFormat($total_employees)}} </h3>
                                    </div>
                                </div>
                                <div class="col-12 col-xl-8 px-xl-0">
                                    <a href="{{route('portal.all-employees')}}" class="d-none d-sm-block">
                                        <h2 class="h5">{{ __(\Str::plural('employees.employee', $total_employees)) }}</h2>
                                        <h3 class="fw-extrabold mb-1">{{numberFormat($total_employees)}} </h3>
                                    </a>
                                    <div class="d-flex mt-1" style="font-size:x-small;">
                                        <div>{{ __(\Str::plural('employees.employee', $total_employees)) }} {{__('employees.you_manage')}}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Metrics Row -->
        <div class='mb-3 mt-0'>
            <div class='row'>
                <div class="col-12 col-sm-6 col-xl-3 mb-2">
                    <div class="card border-0 shadow h-100">
                        <div class="card-body">
                            <div class="row d-block d-xl-flex align-items-center">
                                <div class="col-12 col-xl-4 text-xl-center mb-2 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                                    <div class="icon-shape icon-shape-primary rounded me-2 me-sm-0">
                                        <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                    </div>
                                    <div class="d-sm-none">
                                        <h2 class="fw-extrabold h5">{{ __('dashboard.attendance_rate') }}</h2>
                                        <h3 class="mb-1">{{$attendance_rate}}%</h3>
                                    </div>
                                </div>
                                <div class="col-12 col-xl-8 px-xl-0">
                                    <div class="d-none d-sm-block">
                                        <h2 class="h5">{{__('dashboard.attendance_rate')}}</h2>
                                        <h3 class="fw-extrabold mb-1">{{$attendance_rate}}%</h3>
                                    </div>
                                    <div class="d-flex mt-1" style="font-size:x-small;">
                                        <div class="text-{{$attendance_rate >= 90 ? 'success' : ($attendance_rate >= 70 ? 'warning' : 'danger')}}">
                                            @if($attendance_rate > $attendance_rate_last_month)
                                            <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                                            </svg>
                                            @elseif($attendance_rate < $attendance_rate_last_month)
                                                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path>
                                                </svg>
                                                @endif
                                                {{abs($attendance_rate - $attendance_rate_last_month)}}% {{__('dashboard.vs_last_month')}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3 mb-2">
                    <div class="card border-0 shadow h-100">
                        <div class="card-body">
                            <div class="row d-block d-xl-flex align-items-center">
                                <div class="col-12 col-xl-4 text-xl-center mb-2 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                                    <div class="icon-shape icon-shape-info rounded me-2 me-sm-0">
                                        <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <div class="d-sm-none">
                                        <h2 class="fw-extrabold h5">{{ __('dashboard.leave_utilization') }}</h2>
                                        <h3 class="mb-1">{{$leave_utilization_rate}}%</h3>
                                    </div>
                                </div>
                                <div class="col-12 col-xl-8 px-xl-0">
                                    <div class="d-none d-sm-block">
                                        <h2 class="h5">{{__('dashboard.leave_utilization')}}</h2>
                                        <h3 class="fw-extrabold mb-1">{{$leave_utilization_rate}}%</h3>
                                    </div>
                                    <div class="d-flex mt-1" style="font-size:x-small;">
                                        <div class="text-{{$leave_utilization_rate >= 80 ? 'warning' : 'success'}}">
                                            {{$leave_utilization_rate >= 80 ? __('dashboard.high_utilization') : __('dashboard.healthy_utilization')}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-sm-6 col-xl-3 mb-2">
                    <div class="card border-0 shadow h-100">
                        <div class="card-body">
                            <div class="row d-block d-xl-flex align-items-center">
                                <div class="col-12 col-xl-4 text-xl-center mb-2 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                                    <div class="icon-shape icon-shape-warning rounded me-2 me-sm-0">
                                        <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                        </svg>
                                    </div>
                                    <div class="d-sm-none">
                                        <h2 class="fw-extrabold h5">{{ __('dashboard.pending_approvals') }}</h2>
                                        <h3 class="mb-1">{{$pending_approvals['total']}}</h3>
                                    </div>
                                </div>
                                <div class="col-12 col-xl-8 px-xl-0">
                                    <div class="d-none d-sm-block">
                                        <h2 class="h5">{{__('dashboard.pending_approvals')}}</h2>
                                        <h3 class="fw-extrabold mb-1">{{$pending_approvals['total']}}</h3>
                                    </div>
                                    <div class="d-flex mt-1" style="font-size:x-small;">
                                        <div class="text-{{$pending_approvals['total'] > 10 ? 'danger' : 'success'}}">
                                            {{$pending_approvals['checkins']}} checkins, {{$pending_approvals['leaves']}} leaves, {{$pending_approvals['overtimes']}} overtime
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3 mb-2">
                    <div class="card border-0 shadow h-100">
                        <div class="card-body">
                            <div class="row d-block d-xl-flex align-items-center">
                                <div class="col-12 col-xl-4 text-xl-center mb-2 mb-xl-0 d-flex align-items-center justify-content-xl-center">
                                    <div class="icon-shape icon-shape-success rounded me-2 me-sm-0">
                                        <svg class="icon icon-md" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                    </div>
                                    <div class="d-sm-none">
                                        <h2 class="fw-extrabold h5">{{ __('dashboard.top_performers') }}</h2>
                                        <h3 class="mb-1">{{count($top_performers)}}</h3>
                                    </div>
                                </div>
                                <div class="col-12 col-xl-8 px-xl-0">
                                    <div class="d-none d-sm-block">
                                        <h2 class="h5">{{__('dashboard.top_performers')}}</h2>
                                        <h3 class="fw-extrabold mb-1">{{count($top_performers)}}</h3>
                                    </div>
                                    <div class="d-flex mt-1" style="font-size:x-small;">
                                        <div class="text-success">
                                            {{ __('dashboard.best_attendance_this_month') }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Insights Summary -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-lg bg-gradient-info">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="mb-2">{{__('dashboard.key_insights')}}</h4>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="dot rounded-circle bg-success me-2"></div>
                                            <span class="small">{{__('dashboard.overall_attendance')}}: <strong>{{$attendance_rate}}%</strong></span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="dot rounded-circle bg-warning me-2"></div>
                                            <span class="small">{{__('dashboard.pending_approvals')}}: <strong>{{$pending_approvals['total']}}</strong></span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="dot rounded-circle bg-light me-2"></div>
                                            <span class="small">{{__('dashboard.leave_utilization')}}: <strong>{{$leave_utilization_rate}}%</strong></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="h2 mb-0">{{count($top_performers)}}</div>
                                <div class="small">{{__('dashboard.top_performers')}}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Charts Section -->
        <div class="row">
            <!-- Payslips Overview Chart -->
            <div class='col-12 col-lg-8'>
                <div class="card bg-gradient-primary border-0 shadow-lg h-100">
                    <div class="card-header d-sm-flex flex-row align-items-center flex-0 border-0">
                        <div class="d-block mb-3 mb-sm-0">
                            <div class="fs-5 fw-normal mb-2 ">{{__('dashboard.payslips_performance_overview')}}</div>
                            <h2 class="fs-3 fw-extrabold ">{{number_format($payslips_failed + $payslips_success)}}</h2>
                            <div class="small mt-2 -50">
                                <span class="fw-normal me-2">{{now()->subMonth()->locale(app()->getLocale())->isoFormat(__('dashboard.month_year_format'))}} {{__('dashboard.success_rate')}} - </span>
                                <span class="fas fa-angle-up text-success"></span>
                                <span class="text-success fw-bold">{{ ceil(($payslips_last_month_success_count/($payslips_last_month_total_count == 0 ? 1 : $payslips_last_month_total_count))*100)}}%</span>
                            </div>
                        </div>
                        <div class="d-block ms-auto">
                            <div class="d-flex align-items-center text-end "><span class="dot rounded-circle bg-success me-2"></span> <span class="fw-normal small">{{__('dashboard.success')}}</span></div>
                            <div class="d-flex align-items-center text-end "><span class="dot rounded-circle bg-danger me-2"></span> <span class="fw-normal small">{{__('dashboard.failed')}}</span></div>
                            <div class="d-flex align-items-center text-end mb-1"><span class="dot rounded-circle bg-warning me-2"></span> <span class="fw-normal small">{{__('dashboard.pending')}}</span></div>
                            <div class="d-flex align-items-center text-end mb-1"><span class="dot rounded-circle me-2" style="background-color: #6c757d;"></span> <span class="fw-normal small">{{__('dashboard.sms_disabled')}}</span></div>
                            <div class="d-flex align-items-center text-end mb-2"><span class="dot rounded-circle me-2" style="background-color: #6f42c1;"></span> <span class="fw-normal small">{{__('dashboard.encryption_failed')}}</span></div>
                        </div>
                    </div>
                    <div class=" card-body p-3">
                        <div class='line-chart ct-double-octave flex-grow' style="height: 300px;"></div>
                    </div>
                </div>
            </div>
            <!-- Comprehensive Payslip Status Overview -->
            <div class="col-12 col-lg-4">
                <div class="card border-0 shadow-lg h-100">
                    <div class="card-header d-flex flex-row align-items-center flex-0 border-bottom">
                        <div class="d-block">
                            <div class="h6 fw-normal text-gray mb-2">{{__('dashboard.payslip_status_overview')}}</div>
                            <div class="small text-gray">{{__('dashboard.comprehensive_status_breakdown')}}</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Health Score -->
                        <div class="text-center mb-3">
                            <div class="h5 fw-bold text-primary mb-1">{{ $comprehensive_status_chart['health_score'] }}%</div>
                            <div class="small text-gray">{{__('dashboard.overall_health_score')}}</div>
                        </div>

                        <!-- Success Rates -->
                        <div class="mb-3">
                            <div class="small fw-bold text-gray mb-2">{{__('dashboard.success_rates')}}</div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small">{{__('dashboard.email')}}:</span>
                                <span class="badge bg-{{ $comprehensive_status_chart['success_rates']['email'] >= 90 ? 'success' : ($comprehensive_status_chart['success_rates']['email'] >= 70 ? 'warning' : 'danger') }}">
                                    {{ $comprehensive_status_chart['success_rates']['email'] }}%
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small">{{__('dashboard.sms')}}:</span>
                                <span class="badge bg-{{ $comprehensive_status_chart['success_rates']['sms'] >= 90 ? 'success' : ($comprehensive_status_chart['success_rates']['sms'] >= 70 ? 'warning' : 'danger') }}">
                                    {{ $comprehensive_status_chart['success_rates']['sms'] }}%
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small">{{__('dashboard.encryption')}}:</span>
                                <span class="badge bg-{{ $comprehensive_status_chart['success_rates']['encryption'] >= 90 ? 'success' : ($comprehensive_status_chart['success_rates']['encryption'] >= 70 ? 'warning' : 'danger') }}">
                                    {{ $comprehensive_status_chart['success_rates']['encryption'] }}%
                                </span>
                            </div>
                        </div>

                        <!-- Failure Counts -->
                        <div class="mb-3">
                            <div class="small fw-bold text-gray mb-2">{{__('dashboard.failure_counts')}}</div>
                            @if($comprehensive_status_chart['failure_counts']['email'] > 0 || $comprehensive_status_chart['failure_counts']['sms'] > 0 || $comprehensive_status_chart['failure_counts']['encryption'] > 0)
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small text-danger">{{__('dashboard.email')}}:</span>
                                <span class="fw-bold text-danger">{{ $comprehensive_status_chart['failure_counts']['email'] }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small text-danger">{{__('dashboard.sms')}}:</span>
                                <span class="fw-bold text-danger">{{ $comprehensive_status_chart['failure_counts']['sms'] }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small text-danger">{{__('dashboard.encryption')}}:</span>
                                <span class="fw-bold text-danger">{{ $comprehensive_status_chart['failure_counts']['encryption'] }}</span>
                            </div>
                            @else
                            <div class="text-center text-success small">
                                <i class="fas fa-check-circle me-1"></i>{{__('dashboard.no_failures')}}
                            </div>
                            @endif
                        </div>

                        <!-- Pending/Disabled Counts -->
                        <div>
                            <div class="small fw-bold text-gray mb-2">{{__('dashboard.pending_status')}}</div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small">{{__('dashboard.email_pending')}}:</span>
                                <span class="fw-bold">{{ $comprehensive_status_chart['pending_counts']['email'] }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="small">{{__('dashboard.sms_pending')}}:</span>
                                <span class="fw-bold">{{ $comprehensive_status_chart['pending_counts']['sms'] }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="small">{{__('dashboard.sms_disabled')}}:</span>
                                <span class="fw-bold">{{ $comprehensive_status_chart['pending_counts']['sms_disabled'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Combined Payslip Charts Row -->
        <div class="row mt-4">
            <!-- Weekly Payslips Distribution -->
            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-lg h-100">
                    <div class="card-header d-flex flex-row align-items-center flex-0 border-bottom">
                        <div class="d-block">
                            <div class="h6 fw-normal text-gray mb-2">{{__('dashboard.weekly_payslips_distribution')}}</div>
                            <h2 class="h3 fw-extrabold">{{$payslips_failed_week + $payslips_success_week}}</h2>
                            <div class="small mt-2">
                                <span class="fas fa-angle-up text-success"></span>
                                <span class="text-success fw-bold">{{ ceil(($payslips_success_week/(($payslips_success_week+$payslips_failed_week) == 0 ? 1 : ($payslips_success_week+$payslips_failed_week) ))*100)}}%</span>
                                {{__('dashboard.success_rate_this_week')}}
                            </div>
                        </div>
                        <div class="d-block ms-auto">
                            <div class="d-flex align-items-center text-end"><span class="dot rounded-circle bg-success me-2"></span> <span class="fw-normal small">{{__('dashboard.success')}}</span></div>
                            <div class="d-flex align-items-center text-end"><span class="dot rounded-circle bg-danger me-2"></span> <span class="fw-normal small">{{__('dashboard.failed')}}</span></div>
                            <div class="d-flex align-items-center text-end mb-1"><span class="dot rounded-circle bg-warning me-2"></span> <span class="fw-normal small">{{__('dashboard.pending')}}</span></div>
                            <div class="d-flex align-items-center text-end mb-1"><span class="dot rounded-circle me-2" style="background-color: #6c757d;"></span> <span class="fw-normal small">{{__('dashboard.sms_disabled')}}</span></div>
                            <div class="d-flex align-items-center text-end mb-2"><span class="dot rounded-circle me-2" style="background-color: #6f42c1;"></span> <span class="fw-normal small">{{__('dashboard.encryption_failed')}}</span></div>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <div class="bar-chart" style="height: 300px;"></div>
                    </div>
                </div>
            </div>

            <!-- Detailed Payslip Status Breakdown -->
            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-lg h-100">
                    <div class="card-header d-flex flex-row align-items-center flex-0 border-bottom">
                        <div class="d-block">
                            <div class="h6 fw-normal text-gray mb-2">{{__('dashboard.detailed_payslip_status_breakdown')}}</div>
                            <div class="small text-gray">{{__('dashboard.status_distribution_by_type')}}</div>
                        </div>
                        <div class="ms-auto">
                            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="openPayslipDetailsModal">
                                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{__('dashboard.view_details')}}
                            </button>
                        </div>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <div style="position: relative; height: 250px; width: 100%; flex-shrink: 0;">
                            <canvas class="payslip-status-pie-chart" style="max-height: 250px; max-width: 100%;"></canvas>
                        </div>
                        <div class="row mt-3 flex-grow-1">
                            <!-- Email Status -->
                            <div class="col-md-4">
                                <div class="small fw-bold text-gray mb-2">{{__('dashboard.email_status')}}</div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="d-flex align-items-center">
                                        <div class="dot rounded-circle bg-success me-2" style="width: 8px; height: 8px;"></div>
                                        <span class="small">{{__('dashboard.successful')}}</span>
                                    </div>
                                    <span class="fw-bold">{{ $payslip_status_pie_chart['data'][0] }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="d-flex align-items-center">
                                        <div class="dot rounded-circle bg-danger me-2" style="width: 8px; height: 8px;"></div>
                                        <span class="small">{{__('dashboard.failed')}}</span>
                                    </div>
                                    <span class="fw-bold">{{ $payslip_status_pie_chart['data'][1] }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div class="dot rounded-circle bg-warning me-2" style="width: 8px; height: 8px;"></div>
                                        <span class="small">{{__('dashboard.pending')}}</span>
                                    </div>
                                    <span class="fw-bold">{{ $payslip_status_pie_chart['data'][2] }}</span>
                                </div>
                            </div>
                            <!-- SMS Status -->
                            <div class="col-md-4">
                                <div class="small fw-bold text-gray mb-2">{{__('dashboard.sms_status')}}</div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="d-flex align-items-center">
                                        <div class="dot rounded-circle me-2" style="width: 8px; height: 8px; background-color: #20c997;"></div>
                                        <span class="small">{{__('dashboard.successful')}}</span>
                                    </div>
                                    <span class="fw-bold">{{ $payslip_status_pie_chart['data'][3] }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="d-flex align-items-center">
                                        <div class="dot rounded-circle me-2" style="width: 8px; height: 8px; background-color: #e83e8c;"></div>
                                        <span class="small">{{__('dashboard.failed')}}</span>
                                    </div>
                                    <span class="fw-bold">{{ $payslip_status_pie_chart['data'][4] }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="d-flex align-items-center">
                                        <div class="dot rounded-circle me-2" style="width: 8px; height: 8px; background-color: #fd7e14;"></div>
                                        <span class="small">{{__('dashboard.pending')}}</span>
                                    </div>
                                    <span class="fw-bold">{{ $payslip_status_pie_chart['data'][5] }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div class="dot rounded-circle me-2" style="width: 8px; height: 8px; background-color: #6c757d;"></div>
                                        <span class="small">{{__('dashboard.disabled')}}</span>
                                    </div>
                                    <span class="fw-bold">{{ $payslip_status_pie_chart['data'][6] }}</span>
                                </div>
                            </div>
                            <!-- Encryption Status -->
                            <div class="col-md-4">
                                <div class="small fw-bold text-gray mb-2">{{__('dashboard.encryption_status')}}</div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="d-flex align-items-center">
                                        <div class="dot rounded-circle bg-primary me-2" style="width: 8px; height: 8px;"></div>
                                        <span class="small">{{__('dashboard.successful')}}</span>
                                    </div>
                                    <span class="fw-bold">{{ $payslip_status_pie_chart['data'][7] }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="d-flex align-items-center">
                                        <div class="dot rounded-circle me-2" style="width: 8px; height: 8px; background-color: #6f42c1;"></div>
                                        <span class="small">{{__('dashboard.failed')}}</span>
                                    </div>
                                    <span class="fw-bold">{{ $payslip_status_pie_chart['data'][8] }}</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <div class="dot rounded-circle bg-secondary me-2" style="width: 8px; height: 8px;"></div>
                                        <span class="small">{{__('dashboard.not_recorded')}}</span>
                                    </div>
                                    <span class="fw-bold">{{ $payslip_status_pie_chart['data'][9] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Department Comparison Charts -->
        <div class="row mt-4">
            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-lg h-100">
                    <div class="card-header d-flex flex-row align-items-center flex-0 border-bottom">
                        <div class="d-block">
                            <div class="h6 fw-normal text-gray mb-2">{{__('dashboard.department_performance_comparison')}}</div>
                            <div class="small text-gray">{{__('dashboard.attendance_rates_by_department')}}</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="position: relative; height: 300px; width: 100%;">
                            <canvas class="department-comparison-chart" style="max-height: 300px; max-width: 100%;"></canvas>
                        </div>
                        @if(config('app.debug'))
                        <div class="mt-2 small text-muted">
                            Debug: {{count($department_comparison['labels'])}} departments,
                            Attendance: {{implode(', ', $department_comparison['attendance'])}},
                            Overtime: {{implode(', ', $department_comparison['overtime'])}}
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow-lg h-100">
                    <div class="card-header d-flex flex-row align-items-center flex-0 border-bottom">
                        <div class="d-block">
                            <div class="h6 fw-normal text-gray mb-2">{{__('dashboard.monthly_trends')}}</div>
                            <div class="small text-gray">{{__('dashboard.6_month_trend_analysis')}}</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="position: relative; height: 300px; width: 100%;">
                            <canvas class="monthly-trends-chart" style="max-height: 300px; max-width: 100%;"></canvas>
                        </div>
                        @if(config('app.debug'))
                        <div class="mt-2 small text-muted">
                            Debug: {{count($monthly_trends['labels'])}} months,
                            Check-ins: {{implode(', ', $monthly_trends['attendance'])}},
                            Overtime: {{implode(', ', $monthly_trends['overtime'])}},
                            Leaves: {{implode(', ', $monthly_trends['leaves'])}}
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <!-- Failure Details Drill-down Modal -->
        <div class="modal fade" id="failureDetailsModal" tabindex="-1" aria-labelledby="failureDetailsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="failureDetailsModalLabel">{{__('dashboard.failure_details_breakdown')}}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="fw-bold text-danger mb-3">{{__('dashboard.email_failures')}}</h6>
                                <div class="small text-muted mb-2">{{__('dashboard.recent_email_failures')}}:</div>
                                <div id="email-failures-list" class="small">
                                    <!-- Email failures will be populated here -->
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold text-danger mb-3">{{__('dashboard.sms_failures')}}</h6>
                                <div class="small text-muted mb-2">{{__('dashboard.recent_sms_failures')}}:</div>
                                <div id="sms-failures-list" class="small">
                                    <!-- SMS failures will be populated here -->
                                </div>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-12">
                                <h6 class="fw-bold text-primary mb-3">{{__('dashboard.encryption_failures')}}</h6>
                                <div class="small text-muted mb-2">{{__('dashboard.recent_encryption_failures')}}:</div>
                                <div id="encryption-failures-list" class="small">
                                    <!-- Encryption failures will be populated here -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{__('dashboard.close')}}</button>
                        <a href="/portal/payslips" class="btn btn-primary">{{__('dashboard.view_all_payslips')}}</a>
                    </div>
                </div>
            </div>
        </div>


        <!-- Department Performance Comparison -->
        <div class="row mt-4">
            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow h-100">
                    <div class="card-header d-flex flex-row align-items-center flex-0 border-bottom">
                        <div class="d-block">
                            <div class="h6 fw-normal text-gray mb-2">{{__('dashboard.top_departments_performance')}}</div>
                            <div class="small text-gray">{{__('dashboard.top_5_departments_by_attendance')}} <span class="badge bg-primary ms-2">{{count($top_departments)}}/5</span></div>
                        </div>
                        <div class="ms-auto">
                            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="openDepartmentModal">
                                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                {{__('dashboard.view_details')}}
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="department-performance-chart">
                            @forelse($top_departments as $index => $dept)
                            <div class="d-flex justify-content-between align-items-center mb-3 department-row" style="cursor: pointer;" wire:click="selectDepartmentForModal({{$dept->id}})">
                                <div class="d-flex align-items-center">
                                    <div class="position-relative">
                                        <div class="avatar d-flex align-items-center justify-content-center fw-bold rounded bg-primary me-3">
                                            <span class="text-white">{{substr($dept->name, 0, 2)}}</span>
                                        </div>
                                        <div class="position-absolute top-0 start-0 translate-middle">
                                            <span class="badge rounded-pill bg-{{[$index+1][0] === 1 ? 'warning' : ($index+1 === 2 ? 'gray' : ($index+1 === 3 ? 'tertiary' : 'light'))}} text-dark fw-bold" style="font-size: 0.7rem; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center;">{{$index+1}}</span>
                                        </div>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{$dept->name}}</h6>
                                        <small class="text-gray">{{$dept->employees_count}} {{__('employees.employee')}}</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold text-{{$dept->performance_score >= 90 ? 'success' : ($dept->performance_score >= 70 ? 'warning' : 'danger')}}">
                                        {{$dept->performance_score}}%
                                    </div>
                                    <div class="progress" style="width: 100px; height: 6px;">
                                        <div class="progress-bar bg-{{$dept->performance_score >= 90 ? 'success' : ($dept->performance_score >= 70 ? 'warning' : 'danger')}}"
                                            style="width: {{$dept->performance_score}}%"></div>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="text-center text-gray-800 mt-4">
                                <h4 class="fs-4 fw-bold">{{__('common.no_data_available')}} &#128540;</h4>
                                <p>{{__('dashboard.no_department_performance_data')}}</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Attendance Heatmap -->
            <div class="col-12 col-lg-6">
                <div class="card border-0 shadow h-100">
                    <div class="card-header d-flex flex-row align-items-center flex-0 border-bottom">
                        <div class="d-block">
                            <div class="h6 fw-normal text-gray mb-2">{{__('dashboard.attendance_heatmap')}}</div>
                            <div class="small text-gray">{{__('dashboard.last_30_days_attendance_pattern')}}</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="attendance-heatmap">
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($attendance_heatmap as $day)
                                <div class="heatmap-day"
                                    style="width: 20px; height: 20px; background-color: {{$day['intensity'] == 'high' ? '#28a745' : ($day['intensity'] == 'medium' ? '#ffc107' : ($day['intensity'] == 'low' ? '#fd7e14' : '#dc3545'))}}; border-radius: 3px; cursor: pointer;"
                                    title="{{$day['date']}} - {{$day['attendance_rate']}}% ({{$day['checkins']}} {{__('dashboard.checkins')}})">
                                </div>
                                @endforeach
                            </div>
                            <div class="d-flex justify-content-between mt-3">
                                <small class="text-gray">{{__('dashboard.less')}}</small>
                                <div class="d-flex gap-1">
                                    <div style="width: 12px; height: 12px; background-color: #dc3545; border-radius: 2px;"></div>
                                    <div style="width: 12px; height: 12px; background-color: #fd7e14; border-radius: 2px;"></div>
                                    <div style="width: 12px; height: 12px; background-color: #ffc107; border-radius: 2px;"></div>
                                    <div style="width: 12px; height: 12px; background-color: #28a745; border-radius: 2px;"></div>
                                </div>
                                <small class="text-gray">{{__('dashboard.more')}}</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Performers Table -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-0 shadow">
                    <div class="card-header d-flex flex-row align-items-center flex-0 border-bottom">
                        <div class="d-block">
                            <div class="h6 fw-normal text-gray mb-2">{{__('dashboard.top_performers')}}</div>
                            <div class="small text-gray">{{__('dashboard.best_attendance_records_this_month')}}</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-items-center">
                                <thead class="">
                                    <tr>
                                        <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('employees.employee')}}</th>
                                        <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('departments.department')}}</th>
                                        <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('dashboard.checkins')}}</th>
                                        <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('dashboard.overtime_hours')}}</th>
                                        <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('dashboard.performance_score')}}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($top_performers as $performer)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar d-flex align-items-center justify-content-center fw-bold rounded bg-primary me-3">
                                                    <span class="text-white">{{$performer->initials}}</span>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0">{{$performer->first_name}} {{$performer->last_name}}</h6>
                                                    <small class="text-gray">{{$performer->email}}</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-normal">{{$performer->department->name ?? 'N/A'}}</span>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-success">{{$performer->monthly_checkins}}</span>
                                        </td>
                                        <td>
                                            <span class="fw-normal">{{$performer->monthly_overtimes}}</span>
                                        </td>
                                        <td>
                                            @php
                                            $score = min(100, ($performer->monthly_checkins / now()->daysInMonth) * 100);
                                            @endphp
                                            <span class="badge badge-lg bg-{{$score >= 90 ? 'success' : ($score >= 70 ? 'warning' : 'danger')}}">
                                                {{round($score)}}%
                                            </span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center">
                                            <div class="text-center text-gray-800 mt-2">
                                                <h4 class="fs-4 fw-bold">{{__('common.no_data_available')}} &#128540;</h4>
                                                <p>{{__('common.no_performance_data_found')}}</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <div class='d-flex justify-content-between align-items-end mx-2'>
                <h5 class="h5">{{__("dashboard.last_checkin_per_employee")}}</h5>
                <div>
                    <a href='{{route("portal.checklogs.index")}}' class='btn btn-primary'>{{__('dashboard.view_all')}}</a>
                </div>
            </div>
            <div class="card mt-2">
                <div class="table-responsive text-gray-700">
                    <table class="table table-hover align-items-center dataTable">
                        <thead class="">
                            <tr>
                                <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('employees.employee')}}</th>
                                <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('dashboard.checkin_time')}}</th>
                                <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('dashboard.checkout_time')}}</th>
                                <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('dashboard.hours_worked')}}</th>
                                <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('dashboard.sup_approval')}}</th>
                                <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('dashboard.mgr_approval')}}</th>
                                <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('dashboard.date_created')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($checklogs as $checklog)
                            <tr>
                                <td>
                                    <a href="#" class="d-flex align-items-center">
                                        <div class="avatar d-flex align-items-center justify-content-center fw-bold fs-5 rounded bg-primary me-3"><span class="text-white">{{!empty($checklog->user) ? $checklog->user->initials : ''}}</span></div>
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
                                    <span class="fw-normal">{{$checklog->created_at->locale(app()->getLocale())->isoFormat(__('dashboard.date_format_short'))}}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center">
                                    <div class="text-center text-gray-800 mt-2">
                                        <h4 class="fs-4 fw-bold">{{__('common.opps_nothing_here')}} &#128540;</h4>
                                        <p>{{__('common.no_employee_found')}}</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class='mt-4'>
            <div class='d-flex justify-content-between align-items-end mx-2'>
                <h5 class="h5">{{__("dashboard.lastest_audit_logs")}}</h5>
                <div>
                    <a href='{{route("portal.auditlogs.index")}}' class='btn btn-primary'>{{__('dashboard.view_all')}}</a>
                </div>
            </div>
            <div class="card mt-2">
                <div class="table-responsive text-gray-700">
                    <table class="table employee-table table-hover align-items-center ">
                        <thead class="">
                            <tr>
                                <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('employees.employee')}}</th>
                                <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('employees.action_type')}}</th>
                                <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('employees.action_performed')}}</th>
                                <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('dashboard.date_created')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                            @php
                                $userModel = $log->relationLoaded('user') ? $log->getRelation('user') : null;
                                $userName = $userModel ? $userModel->name : ($log->getAttribute('user') ?? __('audit_logs.system'));
                                $userEmail = $userModel ? $userModel->email : null;
                            @endphp
                            <tr>
                                <td>
                                    <a href="#" class="d-flex align-items-center">
                                        <div class="avatar d-flex align-items-center justify-content-center fw-bold rounded bg-primary me-3"><span class="text-white">{{initials($userName)}}</span></div>
                                        <div class="d-block"><span class="fw-bold">{{$userName}}</span>
                                            @if($userEmail)
                                            <div class="small text-gray">{{$userEmail}}</div>
                                            @endif
                                        </div>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge badge-lg bg-{{$log->action_color}} text-white">{{$log->translated_action_type}}</span>
                                </td>
                                <td>
                                    <span class="fs-normal">{!! \Illuminate\Support\Str::limit(strip_tags($log->translated_action_perform), 50) !!}</span>
                                </td>
                                <td>
                                    <span class="fw-normal">{{$log->created_at->locale(app()->getLocale())->isoFormat(__('dashboard.date_format_short'))}}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center">
                                    <div class="text-center text-gray-800 mt-2">
                                        <h4 class="fs-4 fw-bold">{{__('common.opps_nothing_here')}} &#128540;</h4>
                                        <p>{{__('common.no_record_found')}}</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>

                </div>
            </div>
        </div>


        <!-- Department Performance Details Modal -->
        <div class="department-modal-overlay"
            style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 1050; {{ $showDepartmentModal ? 'display: block;' : 'display: none;' }}"
            wire:click="closeDepartmentModal">

            <div class="department-modal-content"
                style="position: fixed; top: 0; right: 0; width: 950px; height: 100%; background-color: white; box-shadow: -2px 0 8px rgba(0, 0, 0, 0.15); overflow-y: auto; transform: translateX({{ $showDepartmentModal ? '0' : '100%' }}); transition: transform 300ms ease-in-out;">

                <div class="modal-header p-4 border-bottom">
                    <div class="d-flex justify-content-end align-items-center">
                        <div>
                            <h5 class="mb-1">{{__('dashboard.department_performance_details')}}</h5>
                            <small class="text-gray">{{__('dashboard.comprehensive_department_analysis')}}</small>
                        </div>
                        <button type="button" class="btn-close" wire:click="closeDepartmentModal" aria-label="Close"></button>
                    </div>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-4">
                        <h6 class="fw-bold mb-3">{{__('dashboard.all_departments_performance')}}</h6>
                        <div id="allDepartmentsList">
                            @foreach($department_performance as $index => $dept)
                            <div class="department-detail-card mb-3 p-3 border rounded" data-department-id="{{$dept->id}}">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar d-flex align-items-center justify-content-center fw-bold rounded bg-primary me-3" style="width: 40px; height: 40px;">
                                            <span class="text-white">{{substr($dept->name, 0, 2)}}</span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold">{{$dept->name}}</h6>
                                            <small class="text-gray">{{$dept->employees_count}} {{__('employees.employee')}}</small>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold fs-5 text-{{$dept->attendance_rate >= 90 ? 'success' : ($dept->attendance_rate >= 70 ? 'warning' : 'danger')}}">
                                            {{$dept->attendance_rate}}%
                                        </div>
                                        <small class="text-gray">{{__('dashboard.attendance_rate')}}</small>
                                    </div>
                                </div>

                                <div class="progress mb-3" style="height: 8px;">
                                    <div class="progress-bar bg-{{$dept->attendance_rate >= 90 ? 'success' : ($dept->attendance_rate >= 70 ? 'warning' : 'danger')}}"
                                        style="width: {{$dept->attendance_rate}}%"></div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-6">
                                        <div class="p-2 bg-light rounded text-center">
                                            <div class="fw-bold text-primary">{{$dept->total_checkins}}</div>
                                            <small class="text-gray">{{__('dashboard.total_checkins')}}</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-2 bg-light rounded text-center">
                                            <div class="fw-bold text-info">{{$dept->total_overtimes}}</div>
                                            <small class="text-gray">{{__('dashboard.overtime_hours')}}</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-2">
                                    <div class="p-2 bg-light rounded text-center">
                                        <div class="fw-bold text-warning">{{$dept->total_leaves}}</div>
                                        <small class="text-gray">{{__('dashboard.total_leaves')}}</small>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Performance Summary -->
                    <div class="border-top pt-3">
                        <h6 class="fw-bold mb-3">{{__('dashboard.performance_summary')}}</h6>
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="card bg-success bg-opacity-10 border-success">
                                    <div class="card-body p-3 text-center">
                                        <div class="fw-bold text-white fs-4">{{count($department_performance->where('attendance_rate', '>=', 90))}}</div>
                                        <small class="text-white">{{__('dashboard.excellent_performance')}}<br>(≥90%)</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card bg-warning bg-opacity-10 border-warning">
                                    <div class="card-body p-3 text-center">
                                        <div class="fw-bold text-white fs-4">{{count($department_performance->whereBetween('attendance_rate', [70, 89]))}}</div>
                                        <small class="text-white">{{__('dashboard.good_performance')}}<br>(70-89%)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 py-2">
                            <div class="col-6">
                                <div class="card bg-danger bg-opacity-10 border-danger">
                                    <div class="card-body p-3 text-center">
                                        <div class="fw-bold text-white fs-4">{{count($department_performance->where('attendance_rate', '<', 70))}}</div>
                                        <small class="text-white">{{__('dashboard.needs_improvement')}}<br>(<70%) </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="card bg-info bg-opacity-10 border-info">
                                    <div class="card-body p-3 text-center">
                                        <div class="fw-bold text-white fs-4">{{round($department_performance->avg('attendance_rate'), 1)}}%</div>
                                        <small class="text-white">{{__('dashboard.average_rate')}}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
</div>

<!-- Payslip Details Modal -->
@if($showPayslipDetailsModal)
<div class="modal fade show d-block" id="payslipDetailsModal" tabindex="-1" aria-labelledby="payslipDetailsModalLabel" aria-hidden="false" style="background-color: rgba(0,0,0,0.5);" wire:ignore.self>
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="payslipDetailsModalLabel">
                    <svg class="icon icon-sm me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    {{__('dashboard.payslip_processing_details')}}
                </h5>
                <button type="button" class="btn-close" wire:click="closePayslipDetailsModal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Summary Statistics -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <div class="fs-4 fw-bold">{{ $comprehensive_status_chart['total_payslips'] }}</div>
                                <div class="small">{{__('dashboard.total_payslips')}}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <div class="fs-4 fw-bold">{{ $comprehensive_status_chart['success_rates']['email'] }}%</div>
                                <div class="small">{{__('dashboard.email_success_rate')}}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <div class="fs-4 fw-bold">{{ $comprehensive_status_chart['success_rates']['sms'] }}%</div>
                                <div class="small">{{__('dashboard.sms_success_rate')}}</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <div class="fs-4 fw-bold">{{ $comprehensive_status_chart['success_rates']['encryption'] }}%</div>
                                <div class="small">{{__('dashboard.encryption_success_rate')}}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Failure Details Tabs -->
                <ul class="nav nav-tabs" id="payslipTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all-failures" type="button" role="tab" aria-controls="all-failures" aria-selected="true">
                            {{__('dashboard.all_failures')}}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email-failures" type="button" role="tab" aria-controls="email-failures" aria-selected="false">
                            {{__('dashboard.email_failures')}}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="sms-tab" data-bs-toggle="tab" data-bs-target="#sms-failures" type="button" role="tab" aria-controls="sms-failures" aria-selected="false">
                            {{__('dashboard.sms_failures')}}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="encryption-tab" data-bs-toggle="tab" data-bs-target="#encryption-failures" type="button" role="tab" aria-controls="encryption-failures" aria-selected="false">
                            {{__('dashboard.encryption_failures')}}
                        </button>
                    </li>
                </ul>

                <div class="tab-content mt-3" id="payslipTabsContent">
                    <!-- All Failures Tab -->
                    <div class="tab-pane fade show active" id="all-failures" role="tabpanel" aria-labelledby="all-tab">
                        <div class="row">
                            @if(count($payslip_failure_details['email_failures']) > 0 || count($payslip_failure_details['sms_failures']) > 0 || count($payslip_failure_details['encryption_failures']) > 0)
                                <!-- Email Failures -->
                                @if(count($payslip_failure_details['email_failures']) > 0)
                                <div class="col-md-4 mb-3">
                                    <div class="card border-danger">
                                        <div class="card-header bg-danger text-white">
                                            <h6 class="mb-0">
                                                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 7.89a2 2 0 002.83 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                </svg>
                                                {{__('dashboard.email_failures')}}
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            @foreach($payslip_failure_details['email_failures'] as $failure)
                                            <div class="mb-3 p-2 border-bottom">
                                                <div class="fw-bold small">{{ $failure['employee_name'] }}</div>
                                                <div class="small text-muted">{{ $failure['email'] }}</div>
                                                <div class="small text-danger">{{ $failure['error_message'] }}</div>
                                                <div class="small text-muted">{{ $failure['created_at'] }}</div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <!-- SMS Failures -->
                                @if(count($payslip_failure_details['sms_failures']) > 0)
                                <div class="col-md-4 mb-3">
                                    <div class="card border-warning">
                                        <div class="card-header bg-warning text-white">
                                            <h6 class="mb-0">
                                                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                                </svg>
                                                {{__('dashboard.sms_failures')}}
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            @foreach($payslip_failure_details['sms_failures'] as $failure)
                                            <div class="mb-3 p-2 border-bottom">
                                                <div class="fw-bold small">{{ $failure['employee_name'] }}</div>
                                                <div class="small text-muted">{{ $failure['phone'] }}</div>
                                                <div class="small text-danger">{{ $failure['error_message'] }}</div>
                                                <div class="small text-muted">{{ $failure['created_at'] }}</div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                @endif

                                <!-- Encryption Failures -->
                                @if(count($payslip_failure_details['encryption_failures']) > 0)
                                <div class="col-md-4 mb-3">
                                    <div class="card border-info">
                                        <div class="card-header bg-info text-white">
                                            <h6 class="mb-0">
                                                <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                                </svg>
                                                {{__('dashboard.encryption_failures')}}
                                            </h6>
                                        </div>
                                        <div class="card-body">
                                            @foreach($payslip_failure_details['encryption_failures'] as $failure)
                                            <div class="mb-3 p-2 border-bottom">
                                                <div class="fw-bold small">{{ $failure['employee_name'] }}</div>
                                                <div class="small text-muted">{{__('dashboard.matricule')}}: {{ $failure['matricule'] }}</div>
                                                <div class="small text-danger">{{ $failure['error_message'] }}</div>
                                                <div class="small text-muted">{{ $failure['created_at'] }}</div>
                                            </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                @endif
                            @else
                                <div class="col-12">
                                    <div class="text-center py-5">
                                        <svg class="icon icon-lg text-success mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <h5 class="text-success">{{__('dashboard.no_failures_found')}}</h5>
                                        <p class="text-muted">{{__('dashboard.all_payslips_processed_successfully')}}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Email Failures Tab -->
                    <div class="tab-pane fade" id="email-failures" role="tabpanel" aria-labelledby="email-tab">
                        @if(count($payslip_failure_details['email_failures']) > 0)
                            <div class="row">
                                @foreach($payslip_failure_details['email_failures'] as $failure)
                                <div class="col-md-6 mb-3">
                                    <div class="card border-danger">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start">
                                                <div class="avatar avatar-sm d-flex align-items-center justify-content-center fw-bold rounded bg-danger text-white me-3">
                                                    <span>{{ strtoupper(substr($failure['employee_name'], 0, 1)) }}</span>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="fw-bold">{{ $failure['employee_name'] }}</div>
                                                    <div class="small text-muted">{{ $failure['email'] }}</div>
                                                    <div class="small text-danger mt-1">{{ $failure['error_message'] }}</div>
                                                    <div class="small text-muted mt-1">{{ $failure['created_at'] }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-5">
                                <svg class="icon icon-lg text-success mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <h5 class="text-success">{{__('dashboard.no_email_failures')}}</h5>
                                <p class="text-muted">{{__('dashboard.all_emails_sent_successfully')}}</p>
                            </div>
                        @endif
                    </div>

                    <!-- SMS Failures Tab -->
                    <div class="tab-pane fade" id="sms-failures" role="tabpanel" aria-labelledby="sms-tab">
                        @if(count($payslip_failure_details['sms_failures']) > 0)
                            <div class="row">
                                @foreach($payslip_failure_details['sms_failures'] as $failure)
                                <div class="col-md-6 mb-3">
                                    <div class="card border-warning">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start">
                                                <div class="avatar avatar-sm d-flex align-items-center justify-content-center fw-bold rounded bg-warning text-white me-3">
                                                    <span>{{ strtoupper(substr($failure['employee_name'], 0, 1)) }}</span>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="fw-bold">{{ $failure['employee_name'] }}</div>
                                                    <div class="small text-muted">{{ $failure['phone'] }}</div>
                                                    <div class="small text-danger mt-1">{{ $failure['error_message'] }}</div>
                                                    <div class="small text-muted mt-1">{{ $failure['created_at'] }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-5">
                                <svg class="icon icon-lg text-success mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <h5 class="text-success">{{__('dashboard.no_sms_failures')}}</h5>
                                <p class="text-muted">{{__('dashboard.all_sms_sent_successfully')}}</p>
                            </div>
                        @endif
                    </div>

                    <!-- Encryption Failures Tab -->
                    <div class="tab-pane fade" id="encryption-failures" role="tabpanel" aria-labelledby="encryption-tab">
                        @if(count($payslip_failure_details['encryption_failures']) > 0)
                            <div class="row">
                                @foreach($payslip_failure_details['encryption_failures'] as $failure)
                                <div class="col-md-6 mb-3">
                                    <div class="card border-info">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start">
                                                <div class="avatar avatar-sm d-flex align-items-center justify-content-center fw-bold rounded bg-info text-white me-3">
                                                    <span>{{ strtoupper(substr($failure['employee_name'], 0, 1)) }}</span>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="fw-bold">{{ $failure['employee_name'] }}</div>
                                                    <div class="small text-muted">{{__('dashboard.matricule')}}: {{ $failure['matricule'] }}</div>
                                                    <div class="small text-danger mt-1">{{ $failure['error_message'] }}</div>
                                                    <div class="small text-muted mt-1">{{ $failure['created_at'] }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-5">
                                <svg class="icon icon-lg text-success mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <h5 class="text-success">{{__('dashboard.no_encryption_failures')}}</h5>
                                <p class="text-muted">{{__('dashboard.all_files_encrypted_successfully')}}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" wire:click="closePayslipDetailsModal">
                    {{__('common.close')}}
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@push('styles')
<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .card {
        transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }

    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
    }

    .metric-card {
        background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        color: white;
    }

    .metric-card-success {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        color: white;
    }

    .metric-card-warning {
        background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        color: white;
    }

    .metric-card-info {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        color: white;
    }

    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
    }

    .chart-container canvas {
        max-width: 100% !important;
        max-height: 100% !important;
    }

    /* Fix chart sizing issues */
    canvas {
        max-width: 100% !important;
        max-height: 100% !important;
    }

    .heatmap-day {
        transition: transform 0.2s ease-in-out;
    }

    .heatmap-day:hover {
        transform: scale(1.2);
        z-index: 10;
        position: relative;
    }

    .progress-bar {
        transition: width 0.6s ease;
    }

    .icon-shape {
        transition: transform 0.2s ease-in-out;
    }

    .icon-shape:hover {
        transform: scale(1.1);
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
    }

    @media (max-width: 768px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@push('scripts')
<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Chartist CDN -->
<script src="https://cdn.jsdelivr.net/npm/chartist@1.3.0/dist/chartist.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chartist@1.3.0/dist/chartist.min.css">

<style>
    /* Chartist Chart Colors - Match legend colors */
    .line-chart .ct-series-pending .ct-line,
    .line-chart .ct-series-pending .ct-point,
    .line-chart .ct-series-pending .ct-area {
        stroke: #ffc107 !important;
        /* pending - warning/yellow */
        fill: rgba(255, 193, 7, 0.3) !important;
    }

    .line-chart .ct-series-failed .ct-line,
    .line-chart .ct-series-failed .ct-point,
    .line-chart .ct-series-failed .ct-area {
        stroke: #dc3545 !important;
        /* failed - danger/red */
        fill: rgba(220, 53, 69, 0.3) !important;
    }

    .line-chart .ct-series-success .ct-line,
    .line-chart .ct-series-success .ct-point,
    .line-chart .ct-series-success .ct-area {
        stroke: #28a745 !important;
        /* success - success/green */
        fill: rgba(40, 167, 69, 0.3) !important;
    }

    .bar-chart .ct-series-pending .ct-bar {
        stroke: #ffc107 !important;
        /* pending - warning/yellow */
        fill: #ffc107 !important;
    }

    .bar-chart .ct-series-failed .ct-bar {
        stroke: #dc3545 !important;
        /* failed - danger/red */
        fill: #dc3545 !important;
    }

    .bar-chart .ct-series-success .ct-bar {
        stroke: #28a745 !important;
        /* success - success/green */
        fill: #28a745 !important;
    }

    .bar-chart .ct-series-sms-disabled .ct-bar {
        stroke: #6c757d !important;
        /* sms disabled - gray */
        fill: #6c757d !important;
    }

    .bar-chart .ct-series-encryption-issues .ct-bar {
        stroke: #6f42c1 !important;
        /* encryption issues - purple */
        fill: #6f42c1 !important;
    }

    .line-chart .ct-series-sms-disabled .ct-line,
    .line-chart .ct-series-sms-disabled .ct-point,
    .line-chart .ct-series-sms-disabled .ct-area {
        stroke: #6c757d !important;
        /* sms disabled - gray */
        fill: #6c757d !important;
    }

    .line-chart .ct-series-encryption-issues .ct-line,
    .line-chart .ct-series-encryption-issues .ct-point,
    .line-chart .ct-series-encryption-issues .ct-area {
        stroke: #6f42c1 !important;
        /* encryption issues - purple */
        fill: #6f42c1 !important;
    }
</style>

<script type="text/javascript">
    console.log('Dashboard chart script loaded');

    // New Chart.js charts
    // Initialize charts on DOM ready
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM content loaded, initializing charts');
        if (typeof Chart === 'undefined') {
            console.error('Chart.js not loaded!');
            return;
        }

        // Use the same dynamic approach for initial load
        fetchFreshChartData();
    });

    // Global variable to store the Livewire component instance
    let livewireComponent = null;

    // Chart instances storage
    let chartInstances = {
        payslipStatusPie: null,
        departmentComparison: null,
        monthlyTrends: null,
        payslipLine: null,
        payslipBar: null
    };

    // Function to destroy existing charts
    function destroyCharts() {
        // Destroy Chart.js charts
        if (chartInstances.payslipStatusPie) chartInstances.payslipStatusPie.destroy();
        if (chartInstances.departmentComparison) chartInstances.departmentComparison.destroy();
        if (chartInstances.monthlyTrends) chartInstances.monthlyTrends.destroy();

        // Clear Chartist charts (they don't have destroy method, just clear the containers)
        if (chartInstances.payslipLine) {
            document.querySelector('.line-chart').innerHTML = '';
            chartInstances.payslipLine = null;
        }
        if (chartInstances.payslipBar) {
            document.querySelector('.bar-chart').innerHTML = '';
            chartInstances.payslipBar = null;
        }
    }

    // Function to recreate charts with new data
    function recreateCharts() {
        console.log('Recreating charts...');
        destroyCharts();

        // Delay to ensure DOM is updated after Livewire request
        setTimeout(() => {
            console.log('Fetching fresh chart data...');
            // Fetch fresh chart data from Livewire component
            fetchFreshChartData();
        }, 500);
    }

    // Function to fetch fresh chart data
    function fetchFreshChartData() {
        // Wait for Livewire to be initialized
        if (typeof Livewire === 'undefined') {
            setTimeout(fetchFreshChartData, 100);
            return;
        }

        // Get the current Livewire component instance
        const livewireElement = document.querySelector('[wire\\:id]');
        if (livewireElement) {
            livewireComponent = Livewire.find(livewireElement.getAttribute('wire:id'));

            if (livewireComponent) {
                // Call the chart data methods on the Livewire component
                livewireComponent.call('getChartData').then((chartData) => {
                    createChartsWithData(chartData);
                });
            } else {
                // Fallback: use static data for initial load
                createChartsWithStaticData();
            }
        } else {
            // Fallback: use static data for initial load
            createChartsWithStaticData();
        }
    }

    // Function to create charts with static data (fallback)
    function createChartsWithStaticData() {
        // Set global Chart.js defaults for better visibility
        Chart.defaults.color = '#333';
        Chart.defaults.borderColor = '#333';
        Chart.defaults.backgroundColor = 'rgba(54, 162, 235, 0.2)';

        // Payslip Status Pie Chart
        const payslipStatusPieElement = document.querySelector('.payslip-status-pie-chart');
        if (payslipStatusPieElement) {
            const payslipStatusPieCtx = payslipStatusPieElement.getContext('2d');
            chartInstances.payslipStatusPie = new Chart(payslipStatusPieCtx, {
                type: 'doughnut',
                data: {
                    labels: @json($payslip_status_pie_chart['labels']),
                    datasets: [{
                        data: @json($payslip_status_pie_chart['data']),
                        backgroundColor: @json($payslip_status_pie_chart['colors']),
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false // We'll show custom legend below
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    },
                    cutout: '50%'
                }
            });
        }

        // Department Comparison Chart
        const deptComparisonElement = document.querySelector('.department-comparison-chart');
        if (deptComparisonElement) {
            const deptComparisonCtx = deptComparisonElement.getContext('2d');
            const labels = @json($department_comparison['labels']);
            const attendanceData = @json($department_comparison['attendance']);
            const overtimeData = @json($department_comparison['overtime']);

            if (!labels || labels.length === 0) {
                deptComparisonCtx.font = '16px Arial';
                deptComparisonCtx.fillStyle = '#333';
                deptComparisonCtx.textAlign = 'center';
                deptComparisonCtx.fillText('No department data available', deptComparisonElement.width / 2, deptComparisonElement.height / 2);
            } else {
                chartInstances.departmentComparison = new Chart(deptComparisonCtx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Attendance Rate (%)',
                            data: attendanceData,
                            backgroundColor: 'rgba(54, 162, 235, 0.8)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }, {
                            label: 'Overtime Count',
                            data: overtimeData,
                            backgroundColor: 'rgba(255, 99, 132, 0.8)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Attendance Rate (%)'
                                }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'Overtime Count'
                                },
                                grid: {
                                    drawOnChartArea: false
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top'
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false
                            }
                        }
                    }
                });
            }
        }

        // Monthly Trends Chart
        const monthlyTrendsElement = document.querySelector('.monthly-trends-chart');
        if (monthlyTrendsElement) {
            const monthlyTrendsCtx = monthlyTrendsElement.getContext('2d');
            const trendLabels = @json($monthly_trends['labels']);
            const attendanceTrends = @json($monthly_trends['attendance']);
            const overtimeTrends = @json($monthly_trends['overtime']);
            const leaveTrends = @json($monthly_trends['leaves']);

            if (!trendLabels || trendLabels.length === 0) {
                monthlyTrendsCtx.font = '16px Arial';
                monthlyTrendsCtx.fillStyle = '#333';
                monthlyTrendsCtx.textAlign = 'center';
                monthlyTrendsCtx.fillText('No trend data available', monthlyTrendsElement.width / 2, monthlyTrendsElement.height / 2);
            } else {
                chartInstances.monthlyTrends = new Chart(monthlyTrendsCtx, {
                    type: 'line',
                    data: {
                        labels: trendLabels,
                        datasets: [{
                            label: 'Check-ins',
                            data: attendanceTrends,
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            tension: 0.4,
                            fill: true
                        }, {
                            label: 'Overtime',
                            data: overtimeTrends,
                            borderColor: 'rgb(255, 159, 64)',
                            backgroundColor: 'rgba(255, 159, 64, 0.2)',
                            tension: 0.4,
                            fill: true
                        }, {
                            label: 'Leaves',
                            data: leaveTrends,
                            borderColor: 'rgb(153, 102, 255)',
                            backgroundColor: 'rgba(153, 102, 255, 0.2)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        scales: {
                            x: {
                                display: true,
                                title: {
                                    display: true,
                                    text: 'Month'
                                }
                            },
                            y: {
                                display: true,
                                title: {
                                    display: true,
                                    text: 'Count'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top'
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false
                            }
                        }
                    }
                });
            }
        }

        // Create Chartist charts with proper JSON data
        console.log('Creating Chartist charts...');
        console.log('Chartist available:', typeof Chartist !== 'undefined');

        // Payslip Performance Line Chart
        const lineChartElement = document.querySelector('.line-chart');
        console.log('Line chart element found:', lineChartElement);
        if (lineChartElement && typeof Chartist !== 'undefined') {
            try {
                // Use the chart data directly from Livewire
                const chartData = @json($chart_data);
                console.log('Line chart data:', chartData);

                chartInstances.payslipLine = new Chartist.Line('.line-chart', {
                    labels: chartData.periods || [],
                    series: [{
                            name: 'pending',
                            data: chartData.pending || [],
                            className: 'ct-series-pending'
                        },
                        {
                            name: 'failed',
                            data: chartData.failed || [],
                            className: 'ct-series-failed'
                        },
                        {
                            name: 'success',
                            data: chartData.success || [],
                            className: 'ct-series-success'
                        },
                        {
                            name: 'sms_disabled',
                            data: chartData.sms_disabled || [],
                            className: 'ct-series-sms-disabled'
                        },
                        {
                            name: 'encryption_issues',
                            data: chartData.encryption_issues || [],
                            className: 'ct-series-encryption-issues'
                        }
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
                        position: 'end'
                    },
                    axisY: {
                        showGrid: true,
                        showLabel: true,
                    }
                });
                console.log('Line chart created:', chartInstances.payslipLine);
            } catch (error) {
                console.error('Error creating line chart:', error);
            }
        }

        // Weekly Payslips Bar Chart
        const barChartElement = document.querySelector('.bar-chart');
        console.log('Bar chart element found:', barChartElement);
        if (barChartElement && typeof Chartist !== 'undefined') {
            try {
                // Use the chart data directly from Livewire
                const barChartData = @json($chart_daily);
                console.log('Bar chart data:', barChartData);

                chartInstances.payslipBar = new Chartist.Bar('.bar-chart', {
                    labels: barChartData.periods || [],
                    series: [{
                            name: 'pending',
                            data: barChartData.pending || [],
                            className: 'ct-series-pending'
                        },
                        {
                            name: 'failed',
                            data: barChartData.failed || [],
                            className: 'ct-series-failed'
                        },
                        {
                            name: 'success',
                            data: barChartData.success || [],
                            className: 'ct-series-success'
                        }
                    ]
                }, {
                    low: 0,
                    showArea: true,
                    plugins: [
                        Chartist.plugins.tooltip()
                    ],
                    axisX: {
                        position: 'end'
                    },
                    axisY: {
                        showGrid: false,
                        showLabel: false,
                        offset: 0
                    }
                });
                console.log('Bar chart created:', chartInstances.payslipBar);
            } catch (error) {
                console.error('Error creating bar chart:', error);
            }
        }
    }

    // Function to create charts with fresh data
    function createChartsWithData(chartData) {
        // Set global Chart.js defaults for better visibility
        Chart.defaults.color = '#333';
        Chart.defaults.borderColor = '#333';
        Chart.defaults.backgroundColor = 'rgba(54, 162, 235, 0.2)';

        // Payslip Status Pie Chart
        const payslipStatusPieElement = document.querySelector('.payslip-status-pie-chart');
        if (payslipStatusPieElement && chartData.payslip_status_pie_chart) {
            const payslipStatusPieCtx = payslipStatusPieElement.getContext('2d');
            chartInstances.payslipStatusPie = new Chart(payslipStatusPieCtx, {
                type: 'doughnut',
                data: {
                    labels: chartData.payslip_status_pie_chart.labels,
                    datasets: [{
                        data: chartData.payslip_status_pie_chart.data,
                        backgroundColor: chartData.payslip_status_pie_chart.colors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false // We'll show custom legend below
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    },
                    cutout: '50%'
                }
            });
        }

        // Department Comparison Chart
        const deptComparisonElement = document.querySelector('.department-comparison-chart');
        if (deptComparisonElement && chartData.department_comparison) {
            const deptComparisonCtx = deptComparisonElement.getContext('2d');
            const labels = chartData.department_comparison.labels;
            const attendanceData = chartData.department_comparison.attendance;
            const overtimeData = chartData.department_comparison.overtime;

            if (!labels || labels.length === 0) {
                deptComparisonCtx.font = '16px Arial';
                deptComparisonCtx.fillStyle = '#333';
                deptComparisonCtx.textAlign = 'center';
                deptComparisonCtx.fillText('No department data available', deptComparisonElement.width / 2, deptComparisonElement.height / 2);
            } else {
                chartInstances.departmentComparison = new Chart(deptComparisonCtx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Attendance Rate (%)',
                            data: attendanceData,
                            backgroundColor: 'rgba(54, 162, 235, 0.8)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        }, {
                            label: 'Overtime Count',
                            data: overtimeData,
                            backgroundColor: 'rgba(255, 99, 132, 0.8)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1,
                            yAxisID: 'y1'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Attendance Rate (%)'
                                }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'Overtime Count'
                                },
                                grid: {
                                    drawOnChartArea: false
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top'
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false
                            }
                        }
                    }
                });
            }
        }

        // Monthly Trends Chart
        const monthlyTrendsElement = document.querySelector('.monthly-trends-chart');
        if (monthlyTrendsElement && chartData.monthly_trends) {
            const monthlyTrendsCtx = monthlyTrendsElement.getContext('2d');
            const trendLabels = chartData.monthly_trends.labels;
            const attendanceTrends = chartData.monthly_trends.attendance;
            const overtimeTrends = chartData.monthly_trends.overtime;
            const leaveTrends = chartData.monthly_trends.leaves;

            if (!trendLabels || trendLabels.length === 0) {
                monthlyTrendsCtx.font = '16px Arial';
                monthlyTrendsCtx.fillStyle = '#333';
                monthlyTrendsCtx.textAlign = 'center';
                monthlyTrendsCtx.fillText('No trend data available', monthlyTrendsElement.width / 2, monthlyTrendsElement.height / 2);
            } else {
                chartInstances.monthlyTrends = new Chart(monthlyTrendsCtx, {
                    type: 'line',
                    data: {
                        labels: trendLabels,
                        datasets: [{
                            label: 'Check-ins',
                            data: attendanceTrends,
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            tension: 0.4,
                            fill: true
                        }, {
                            label: 'Overtime',
                            data: overtimeTrends,
                            borderColor: 'rgb(255, 159, 64)',
                            backgroundColor: 'rgba(255, 159, 64, 0.2)',
                            tension: 0.4,
                            fill: true
                        }, {
                            label: 'Leaves',
                            data: leaveTrends,
                            borderColor: 'rgb(153, 102, 255)',
                            backgroundColor: 'rgba(153, 102, 255, 0.2)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        scales: {
                            x: {
                                display: true,
                                title: {
                                    display: true,
                                    text: 'Month'
                                }
                            },
                            y: {
                                display: true,
                                title: {
                                    display: true,
                                    text: 'Count'
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'top'
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false
                            }
                        }
                    }
                });
            }
        }

        // Create Chartist charts
        if (chartData.chart_data && chartData.chart_daily) {
            console.log('Creating dynamic Chartist charts...');
            console.log('Chartist available:', typeof Chartist !== 'undefined');

            // Payslip Performance Line Chart
            const lineChartElement = document.querySelector('.line-chart');
            console.log('Dynamic line chart element found:', lineChartElement);
            if (lineChartElement && typeof Chartist !== 'undefined') {
                try {
                    console.log('Dynamic line chart data:', chartData.chart_data);

                    chartInstances.payslipLine = new Chartist.Line('.line-chart', {
                        labels: chartData.chart_data.periods || [],
                        series: [{
                                name: 'pending',
                                data: chartData.chart_data.pending || [],
                                className: 'ct-series-pending'
                            },
                            {
                                name: 'failed',
                                data: chartData.chart_data.failed || [],
                                className: 'ct-series-failed'
                            },
                            {
                                name: 'success',
                                data: chartData.chart_data.success || [],
                                className: 'ct-series-success'
                            }
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
                            position: 'end'
                        },
                        axisY: {
                            showGrid: true,
                            showLabel: true,
                        }
                    });
                    console.log('Dynamic line chart created:', chartInstances.payslipLine);
                } catch (error) {
                    console.error('Error creating dynamic line chart:', error);
                }
            }

            // Weekly Payslips Bar Chart
            const barChartElement = document.querySelector('.bar-chart');
            console.log('Dynamic bar chart element found:', barChartElement);
            if (barChartElement && typeof Chartist !== 'undefined') {
                try {
                    console.log('Dynamic bar chart data:', chartData.chart_daily);

                    chartInstances.payslipBar = new Chartist.Bar('.bar-chart', {
                        labels: chartData.chart_daily.periods || [],
                        series: [{
                                name: 'pending',
                                data: chartData.chart_daily.pending || [],
                                className: 'ct-series-pending'
                            },
                            {
                                name: 'failed',
                                data: chartData.chart_daily.failed || [],
                                className: 'ct-series-failed'
                            },
                            {
                                name: 'success',
                                data: chartData.chart_daily.success || [],
                                className: 'ct-series-success'
                            },
                            {
                                name: 'sms_disabled',
                                data: chartData.chart_daily.sms_disabled || [],
                                className: 'ct-series-sms-disabled'
                            },
                            {
                                name: 'encryption_issues',
                                data: chartData.chart_daily.encryption_issues || [],
                                className: 'ct-series-encryption-issues'
                            }
                        ]
                    }, {
                        low: 0,
                        showArea: true,
                        plugins: [
                            Chartist.plugins.tooltip()
                        ],
                        axisX: {
                            position: 'end'
                        },
                        axisY: {
                            showGrid: false,
                            showLabel: false,
                            offset: 0
                        }
                    });
                    console.log('Dynamic bar chart created:', chartInstances.payslipBar);
                } catch (error) {
                    console.error('Error creating dynamic bar chart:', error);
                }
            }
        }
    }

    // Function to add drill-down functionality to charts
    function addChartDrillDown(chartInstance, chartType) {
        if (!chartInstance) return;

        chartInstance.on('draw', function(data) {
            if (data.type === 'line' || data.type === 'bar' || data.type === 'point') {
                // Make chart elements clickable
                data.element._node.addEventListener('click', function() {
                    const seriesName = data.series.name;
                    if (seriesName === 'failed' || seriesName === 'encryption_issues') {
                        showFailureDetailsModal(seriesName);
                    }
                });

                // Add cursor pointer for clickable elements
                data.element._node.style.cursor = 'pointer';
            }
        });
    }

    // Function to show failure details modal
    function showFailureDetailsModal(failureType) {
        if (!livewireComponent) {
            console.error('Livewire component not available');
            return;
        }

        // Call Livewire method to get failure details
        livewireComponent.call('getFailureDetails', failureType).then((details) => {
            // Populate modal with failure details
            populateFailureModal(details);
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('failureDetailsModal'));
            modal.show();
        });
    }

    // Function to populate failure modal with data
    function populateFailureModal(details) {
        // Translation strings
        const translations = {
            no_email_failures_found: @json(__('dashboard.no_email_failures_found')),
            no_sms_failures_found: @json(__('dashboard.no_sms_failures_found')),
            no_encryption_failures_found: @json(__('dashboard.no_encryption_failures_found'))
        };

        // Populate email failures
        const emailList = document.getElementById('email-failures-list');
        if (details.email_failures && details.email_failures.length > 0) {
            emailList.innerHTML = details.email_failures.map(failure =>
                `<div class="mb-2 p-2 bg-light rounded small">
                    <strong>${failure.employee_name}</strong><br>
                    <span class="text-muted">${failure.email}</span><br>
                    <span class="text-danger">${failure.error_message}</span>
                </div>`
            ).join('');
        } else {
            emailList.innerHTML = '<div class="text-muted">' + translations.no_email_failures_found + '</div>';
        }

        // Populate SMS failures
        const smsList = document.getElementById('sms-failures-list');
        if (details.sms_failures && details.sms_failures.length > 0) {
            smsList.innerHTML = details.sms_failures.map(failure =>
                `<div class="mb-2 p-2 bg-light rounded small">
                    <strong>${failure.employee_name}</strong><br>
                    <span class="text-muted">${failure.phone}</span><br>
                    <span class="text-danger">${failure.error_message}</span>
                </div>`
            ).join('');
        } else {
            smsList.innerHTML = '<div class="text-muted">' + translations.no_sms_failures_found + '</div>';
        }

        // Populate encryption failures
        const encryptionList = document.getElementById('encryption-failures-list');
        if (details.encryption_failures && details.encryption_failures.length > 0) {
            encryptionList.innerHTML = details.encryption_failures.map(failure =>
                `<div class="mb-2 p-2 bg-light rounded small">
                    <strong>${failure.employee_name}</strong><br>
                    <span class="text-muted">${failure.matricule}</span><br>
                    <span class="text-danger">${failure.error_message}</span>
                </div>`
            ).join('');
        } else {
            encryptionList.innerHTML = '<div class="text-muted">' + translations.no_encryption_failures_found + '</div>';
        }
    }

    // Listen for select changes directly to update charts
    document.addEventListener('DOMContentLoaded', () => {
        console.log('Setting up direct select change listeners');

        const companySelect = document.getElementById('selectedCompanyId');
        const departmentSelect = document.getElementById('selectedDepartmentId');
        const periodSelect = document.getElementById('period');

        if (companySelect) {
            companySelect.addEventListener('change', () => {
                console.log('Company select changed directly');
                setTimeout(() => {
                    console.log('Recreating charts after company change');
                    recreateCharts();
                }, 1500); // Wait for Livewire to finish updating
            });
        }

        if (departmentSelect) {
            departmentSelect.addEventListener('change', () => {
                console.log('Department select changed directly');
                setTimeout(() => {
                    console.log('Recreating charts after department change');
                    recreateCharts();
                }, 1500); // Wait for Livewire to finish updating
            });
        }

        if (periodSelect) {
            periodSelect.addEventListener('change', () => {
                console.log('Period select changed directly');
                setTimeout(() => {
                    console.log('Recreating charts after period change');
                    recreateCharts();
                }, 1500); // Wait for Livewire to finish updating
            });
        }

        // Listen for modal events that might affect chart rendering
        const failureDetailsModal = document.getElementById('failureDetailsModal');
        const payslipDetailsModal = document.getElementById('payslipDetailsModal');

        if (failureDetailsModal) {
            failureDetailsModal.addEventListener('shown.bs.modal', () => {
                console.log('Failure details modal shown');
                // Small delay to ensure modal is fully rendered
                setTimeout(() => recreateCharts(), 300);
            });
            failureDetailsModal.addEventListener('hidden.bs.modal', () => {
                console.log('Failure details modal hidden');
                // Small delay to ensure modal is fully hidden
                setTimeout(() => recreateCharts(), 300);
            });
        }

        if (payslipDetailsModal) {
            // For payslip modal, listen for DOM changes since it uses wire:ignore
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                        const target = mutation.target;
                        if (target.id === 'payslipDetailsModal') {
                            const display = window.getComputedStyle(target).display;
                            console.log('Payslip modal display changed to:', display);
                            if (display === 'none') {
                                setTimeout(() => recreateCharts(), 300);
                            }
                        }
                    }
                });
            });

            observer.observe(payslipDetailsModal, {
                attributes: true,
                attributeFilter: ['style']
            });
        }

        // Listen for department modal overlay changes
        const departmentModalOverlay = document.querySelector('.department-modal-overlay');
        if (departmentModalOverlay) {
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                        const display = window.getComputedStyle(departmentModalOverlay).display;
                        console.log('Department modal overlay display changed to:', display);
                        // Recreate charts when modal is hidden or shown
                        setTimeout(() => recreateCharts(), 300);
                    }
                });
            });

            observer.observe(departmentModalOverlay, {
                attributes: true,
                attributeFilter: ['style']
            });
        }

        // General modal event listener for any Bootstrap modals
        document.addEventListener('show.bs.modal', (event) => {
            console.log('Modal shown:', event.target.id);
            // Small delay to ensure modal is fully rendered
            setTimeout(() => recreateCharts(), 300);
        });

        document.addEventListener('hide.bs.modal', (event) => {
            console.log('Modal hidden:', event.target.id);
            // Small delay to ensure modal is fully hidden
            setTimeout(() => recreateCharts(), 300);
        });
    });
</script>
@endpush