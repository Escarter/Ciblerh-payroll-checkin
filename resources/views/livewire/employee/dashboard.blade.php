<div>
    <div class="container emp-page">
        <x-alert />
        @include('flash::message')

        <section class="emp-hero">
            <div class="emp-hero-text">
                <p class="emp-hero-kicker">
                    @if(!empty(auth()->user()->date_of_birth) && auth()->user()->date_of_birth->isBirthday())
                    {{__('employees.happy_birthday')}} 🎂
                    @else
                    {{__('employees.hi')}} 👋
                    @endif
                </p>
                <h1 class="emp-hero-name">{{auth()->user()->first_name}}</h1>
                <p class="emp-hero-meta">{{ auth()->user()->company ? auth()->user()->company->name : __('employees.no_company')}} · {{ auth()->user()->department ? auth()->user()->department->name : __('employees.no_department')}}</p>
            </div>
            <a href="{{route('employee.checklogs')}}" wire:navigate class="emp-hero-cta">
                <svg class="emp-ico" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><span>{{__('employees.checkins')}}</span>
            </a>
        </section>

        <section class="emp-quick" aria-label="Shortcuts">
            <a href="{{route('employee.overtimes')}}" wire:navigate class="emp-quick-item"><span class="tone-indigo"><svg class="emp-ico" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>{{__('common.overtime')}}</a>
            <a href="{{route('employee.advance-salaries')}}" wire:navigate class="emp-quick-item"><span class="tone-amber"><svg class="emp-ico" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg></span>{{__('employees.advance_salary_short')}}</a>
            <a href="{{route('employee.absences')}}" wire:navigate class="emp-quick-item"><span class="tone-rose"><svg class="emp-ico" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></span>{{__('employees.absence')}}</a>
            <a href="{{route('employee.leaves')}}" wire:navigate class="emp-quick-item"><span class="tone-sky"><svg class="emp-ico" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></span>{{__('employees.leave')}}</a>
            <a href="{{route('employee.payslips')}}" wire:navigate class="emp-quick-item"><span class="tone-green"><svg class="emp-ico" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>{{__('common.payslips')}}</a>
            <a href="{{route('employee.annual-declarations')}}" wire:navigate class="emp-quick-item"><span class="tone-violet"><svg class="emp-ico" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>{{__('annual_declarations.annual_declarations')}}</a>
        </section>

        <section class="emp-stats" aria-label="Overview">
            <a href="{{route('employee.checklogs')}}" wire:navigate class="emp-stat"><b>{{number_format($total_checklogs)}}</b><span>{{__('employees.checkins_checkout_recorded')}}</span></a>
            <a href="{{route('employee.payslips')}}" wire:navigate class="emp-stat"><b>{{number_format($total_payslips)}}</b><span>{{__('employees.available_payslips')}}</span></a>
            <a href="{{route('employee.overtimes')}}" wire:navigate class="emp-stat"><b>{{number_format($total_overtime)}}</b><span>{{__('employees.overtime_recorded')}}</span></a>
            <a href="{{route('employee.advance-salaries')}}" wire:navigate class="emp-stat"><b>{{number_format($total_advance_salary)}}</b><span>{{__('employees.advance_salary')}}</span></a>
            <a href="{{route('employee.absences')}}" wire:navigate class="emp-stat"><b>{{number_format($total_absences)}}</b><span>{{__('employees.absences')}}</span></a>
            <a href="{{route('employee.leaves')}}" wire:navigate class="emp-stat"><b>{{number_format(auth()->user()->remaining_leave_days)}}</b><span>{{__('employees.remaining_leave_days')}}</span></a>
        </section>

        <!-- Enhanced Employee Metrics -->
        <!-- <div class='row mb-4'>
            <div class='col-md-3 col-sm-6'>
                <div class='border-prim bg-primary p-3 rounded'>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="text-white fw-bold">{{$attendance_rate}}%</h5>
                            <div class="text-white-50">{{__('dashboard.attendance_rate')}}</div>
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
                            <div class="text-white-50">{{__('dashboard.performance_score')}}</div>
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
                            <div class="text-white-50">{{__('dashboard.overtime_hours')}}</div>
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
                            <div class="text-white-50">{{__('dashboard.pending_requests')}}</div>
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
                        <thead class="">
                            <tr>
                                <!-- <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('employees.employee')}}</th> -->
                                <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('employees.action_type')}}</th>
                                <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('employees.action_performed')}}</th>
                                <th class="border-0 px-4 py-2 text-muted fw-medium">{{__('employees.date')}}</th>
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
                                    <span class="badge badge-lg bg-{{$log->action_color}} text-white">{{$log->translated_action_type}}</span>
                                </td>
                                <td>
                                    <span class="fs-normal">{!! \Illuminate\Support\Str::limit(strip_tags($log->translated_action_perform), 50) !!}</span>
                                </td>
                                <td>
                                    <span class="fw-normal">{{$log->created_at->format('M d, Y H:i')}}</span>
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