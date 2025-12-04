<div>
    <div class='container pt-3 pt-lg-4 pb-7 pb-lg-9 text-white'>
        <div class="mb-4 d-flex justify-content-end">
            <x-layouts.navigation.employee-nav />
        </div>
        <div class='d-flex flex-wrap-reverse align-items-top justify-content-md-between '>
            <div class='d-flex flex-wrap align-items-center gap-3'>
                <div class='d-none d-md-block d-lg-block'>
                    <div class="avatar-xl d-flex align-items-center justify-content-center fw-bold rounded border-warn  mr-5">
                        <span class="p-2 display-2 text-secondary">
                            @if(!empty(auth()->user()->date_of_birth))
                            @if(auth()->user()->date_of_birth->isBirthday())
                            &#129395;
                            @else
                            {{auth()->user()->initials}}
                            @endif
                            @else
                            {{auth()->user()->initials}}
                            @endif
                        </span>
                    </div>
                </div>
                <div class=''>
                    <div class='fw-bold display-4 text-gray-600'>{{__('employees.hi')}}, {{auth()->user()->first_name}}</div>
                    <div class='d-flex align-items-center justify-content-start '>
                        <div class='leading text-gray-400 '>
                            @if(!empty(auth()->user()->date_of_birth))
                            @if(auth()->user()->date_of_birth->isBirthday())
                            {{__('employees.happy_birthday')}} 🎊 🎂 🥂
                            @else
                            {{ auth()->user()->company ? auth()->user()->company->name : __('employees.no_company')}} | {{ auth()->user()->department ? auth()->user()->department->name : __('employees.no_department')}}
                            @endif
                            @else
                            {{ auth()->user()->company ? auth()->user()->company->name : __('employees.no_company')}} | {{ auth()->user()->department ? auth()->user()->department->name : __('employees.no_department')}}
                            @endif
                        </div>
                    </div>
                    <div class='mt-4 d-flex flex-wrap   align-items-center gap-2'>
                        <a href="{{route('employee.checklogs')}}" wire:navigate class="btn btn-secondary mr-lg-2 ">
                            <svg class="icon icon-sm me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" strokelinejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                            </svg>
                            {{__('employees.checkins')}}
                        </a>

                        <a href='{{route("employee.overtimes")}}' wire:navigate class='btn btn-outline-primary mr-lg-2'>
                            <svg class="icon icon-sm me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{__('common.overtime')}}
                        </a>
                        <a href='{{route("employee.advance-salaries")}}' wire:navigate class='btn btn-outline-tertiary'>
                            <svg class="icon icon-sm me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            {{__('employees.advance_salary_short')}}
                        </a>
                        <a href='{{route("employee.absences")}}' wire:navigate class='btn btn-outline-info'>
                            <svg class="icon icon-sm me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            {{__('employees.absence')}}
                        </a>
                        <a href='{{route("employee.payslips")}}' wire:navigate class='btn btn-outline-success'>

                            <svg class="icon icon-sm me-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>

                            {{__('common.payslips')}}
                        </a>
                        <a href='{{route("employee.leaves")}}' wire:navigate class='btn btn-outline-gray-500'>
                            <svg class="icon icon-sm me-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                            </svg>

                            {{__('employees.leave')}}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class='my-5'>
            <div class=''>
                <x-alert />
                @include('flash::message')
            </div>
            <div class='border-warn p-4 rounded mb-4'>
                <div class="d-flex  flex-wrap justify-content-md-between align-items-md-center">
                    <div class="d-flex flex-wrap justify-content-md-center align-items-md-center gap-4">
                        <svg class="icon me-1 text-gray-500 bg-gray-300 shadow rounded-circle p-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                        </svg>
                        <div class="mb-3 mb-md-0">
                            <h5 class="text-gray-700 fw-bold"><span class="display-4">{{number_format($total_checklogs)}}</span> {{__('employees.checkins_checkout_recorded')}} <span class="mx-2">&#x23F0;</span> </h5>
                            <div class="text-gray-500 ">{{__('employees.view_past_checkins')}}<span class="mx-2"> &#x1F449;</span> </div>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap justify-content-center align-items-center ">
                        <a href="{{route('employee.checklogs')}}" wire:navigate class="btn btn-gray-300 text-gray-700 mx-3 "> {{__('employees.view_history')}}</a>
                    </div>
                </div>
            </div>
            <div class='row mb-4'>
                <div class='col-md-4 col-sm-12'>
                    <div class='border-prim bg-yellow-100 p-3 rounded'>
                        <a href="{{route('employee.overtimes')}}" wire:navigate class="d-flex  justify-content-between align-items-center gap-1">
                            <div class="d-flex justify-content-center align-items-center gap-2">
                                <svg class="icon me-1 text-gray-500 bg-gray-300 shadow rounded-circle p-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="mb-3 mb-md-0">
                                    <h5 class="text-gray-700 fw-bold"><span class="display-4">{{number_format($total_overtime)}}</span> {{__('employees.overtime_recorded')}} </h5>
                                    <div class=" text-gray-500 ">{{__('employees.view_all_overtime_records')}}</div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                <div class='col-md-4 col-sm-12 mt-3 mt-md-0'>
                    <div class='border-prim  p-3 rounded'>
                        <a href="{{route('employee.advance-salaries')}}" wire:navigate class="d-flex  justify-content-between align-items-center gap-1">
                            <div class="d-flex justify-content-center align-items-center gap-2">
                                <svg class="icon icon-md me-1 text-gray-500 bg-gray-300 shadow rounded-circle p-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                                <div class="mb-3 mb-md-0">
                                    <h5 class="text-gray-700 fw-bold"><span class="display-4">{{number_format($total_advance_salary)}}</span> {{__('employees.advance_salary')}} </h5>
                                    <div class="text-gray-500 ">{{__('employees.view_all_requests')}}</div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                <div class='col-md-4 col-sm-12 mt-3 mt-md-0'>
                    <div class='border-prim p-3 bg-gray-200 rounded'>
                        <a href="{{route('employee.absences')}}" wire:navigate class="d-flex  justify-content-between align-items-center gap-1">
                            <div class="d-flex justify-content-center align-items-center gap-2">
                                <svg class="icon me-1 text-gray-500 bg-gray-300 shadow rounded-circle p-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <div class="mb-3 mb-md-0">
                                    <h5 class="text-gray-700 fw-bold"><span class="display-4">{{number_format($total_absences)}}</span> {{__('employees.absences')}} </h5>
                                    <div class=" text-gray-500 ">{{__('employees.view_all_absences')}}</div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <div class='row'>
                <div class='col-md-6 col-sm-12'>
                    <div class='border-prim bg-yellow-400 p-3 rounded'>
                        <a href="{{route('employee.payslips')}}" wire:navigate class="d-flex  justify-content-between align-items-center gap-1">
                            <div class="d-flex justify-content-center align-items-center gap-2">
                                <svg class="icon me-1 text-gray-500 bg-gray-300 shadow rounded-circle p-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>

                                <div class="mb-3 mb-md-0">
                                    <h5 class="text-gray-700 fw-bold"><span class="display-4">{{number_format($total_payslips)}}</span> {{__('employees.available_payslips')}} </h5>
                                    <div class=" text-gray-500 ">{{__('employees.view_all_payslips_records')}}</div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
                <div class='col-md-6 col-sm-12 mt-3 mt-md-0'>
                    <div class='border-prim  p-3 rounded'>
                        <a href="{{route('employee.leaves')}}" wire:navigate class="d-flex  justify-content-between align-items-center gap-1">
                            <div class="d-flex justify-content-center align-items-center gap-2">
                                <svg class="icon me-1 text-gray-500 bg-gray-300 shadow rounded-circle p-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                </svg>
                                <div class="mb-3 mb-md-0">
                                    <h5 class="text-gray-700 fw-bold"><span class="display-4">{{number_format(auth()->user()->remaining_leave_days)}}</span> {{__('employees.remaining_leave_days')}} </h5>
                                    <div class="text-gray-500 ">{{__('employees.view_all_requests')}}</div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Enhanced Employee Metrics -->
        <!-- <div class='row mb-4'>
            <div class='col-md-3 col-sm-6'>
                <div class='border-prim bg-primary p-3 rounded'>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="text-white fw-bold">{{$attendance_rate}}%</h5>
                            <div class="text-white-50">{{__('Attendance Rate')}}</div>
                        </div>
                        <svg class="icon me-1 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div class='col-md-3 col-sm-6 mt-3 mt-md-0'>
                <div class='border-prim bg-success p-3 rounded'>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="text-white fw-bold">{{$performance_score}}%</h5>
                            <div class="text-white-50">{{__('Performance Score')}}</div>
                        </div>
                        <svg class="icon me-1 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div class='col-md-3 col-sm-6 mt-3 mt-md-0'>
                <div class='border-prim bg-info p-3 rounded'>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="text-white fw-bold">{{$monthly_overtime_hours}}h</h5>
                            <div class="text-white-50">{{__('Overtime Hours')}}</div>
                        </div>
                        <svg class="icon me-1 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            <div class='col-md-3 col-sm-6 mt-3 mt-md-0'>
                <div class='border-prim bg-warning p-3 rounded'>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="text-white fw-bold">{{$pending_requests['total']}}</h5>
                            <div class="text-white-50">{{__('Pending Requests')}}</div>
                        </div>
                        <svg class="icon me-1 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
         -->
        <div class='mt-5'>
            <div class='d-flex justify-content-between align-items-end mx-2'>
                <h5 class="h5 text-gray-600">{{__("employees.latest_audit_logs")}}</h5>
                <div>
                    <a href='{{route("employee.auditlogs")}}' wire:navigate class='btn btn-secondary'>{{__("employees.view_all")}}</a>
                </div>
            </div>
            <div class="card mt-2">
                <div class="table-responsive text-gray-700">
                    <table class="table employee-table table-hover align-items-center ">
                        <thead>
                            <tr>
                                <!-- <th class="border-bottom">{{__('employees.employee')}}</th> -->
                                <th class="border-bottom">{{__('employees.action_type')}}</th>
                                <th class="border-bottom">{{__('employees.action_performed')}}</th>
                                <th class="border-bottom">{{__('employees.date')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                            <tr>
                                <!-- <td>
                                    <a href="#" class="d-flex align-items-center">
                                        <div class="avatar d-flex align-items-center justify-content-center fw-bold rounded bg-secondary me-3"><span class="text-white">{{initials($log->user)}}</span></div>
                                        <div class="d-block"><span class="fw-bold">{{$log->user}}</span>
                                            <div class="small text-gray">{{$log->user}}</div>
                                        </div>
                                    </a>
                                </td> -->
                                <td>
                                    <span class="fw-normal badge super-badge badge-lg bg-{{$log->style}} rounded">{{$log->translated_action_type}}</span>
                                </td>
                                <td>
                                    <span class="fs-normal">{!! $log->translated_action_perform !!}</span>
                                </td>
                                <td>
                                    <span class="fw-normal">{{$log->created_at}}</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center">
                                    <div class="text-center text-gray-800 mt-2">
                                        <h4 class="fs-4 fw-bold">{{__('common.oops_nothing_here')}} &#128540;</h4>
                                        <p>{{__('common.no_records_found')}}</p>
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