<div>
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
                        <li class="breadcrumb-item"><a href="/" wire:navigate>{{__('dashboard.home')}}</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{__('reports.employees_checkins_reports')}}</li>
                    </ol>
                </nav>
                <h1 class="h4 mt-n2 d-flex justify-content-start align-items-end">
                    <svg class="icon me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    {{__('reports.employees_checkins_report')}}
                </h1>
                <p class="mt-n1 mx-2">{{__('reports.generate_monthly_employees_checkin_reports')}} &#x1F4DD; </p>
            </div>
            <div class="mb-2 mx-3">
                <div class="btn-toolbar " wire:loading.remove>
                    <a href="#" wire:click.prevent="generateReport()" class="btn btn-sm btn-gray-800 d-inline-flex align-items-center  {{count($checklogs) > 0 ? '' :'disabled'}}">
                        <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        {{__('common.generate')}}
                    </a>
                </div>
                <div class="text-center" wire:loading wire:target="generateReport">
                    <div class="text-center">
                        <div class="spinner-grow text-grey-300" style="width: 0.9rem; height: 0.9rem;" role="status"></div>
                        <div class="spinner-grow text-grey-300" style="width: 0.9rem; height: 0.9rem;" role="status"></div>
                        <div class="spinner-grow text-grey-300" style="width: 0.9rem; height: 0.9rem;" role="status"></div>
                        <div class="spinner-grow text-grey-300" style="width: 0.9rem; height: 0.9rem;" role="status"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row py-3">
        @hasanyrole('manager|admin')
        <div class="col">
            <label for="company">{{__('companies.company')}}: </label>
            <select wire:model.live="selectedCompanyId" class="form-select">
                <option value="all" selected>{{__('Select Company')}}</option>
                @foreach ($companies as $company)
                <option value="{{$company->id}}">{{$company->name}}</option>
                @endforeach
            </select>
        </div>
        @endhasanyrole
        <div class="col">
            <label for="selectedDepartmentId">{{__('departments.department')}}: </label>
            <select wire:model.live="selectedDepartmentId" class="form-select  @error('selectedDepartmentId') is-invalid @enderror">
                <option value="" selected>{{__('common.select')}} {{__('departments.departments')}}</option>
                @foreach ($departments as $department)
                <option value="{{$department->id}}">{{$department->name}}</option>
                @endforeach
            </select>
            @error('selectedDepartmentId')
            <div class="invalid-feedback">{{$message}}</div>
            @enderror
        </div>
        <div class="col">
            <div>
                <label for="orderBy">{{__('employees.employee')}}: </label>
                <select wire:model.live="employee_id" class="form-select">
                    <option value="all" selected>{{__('All Employees')}}</option>
                    @foreach ($employees as $employee)
                    <option value=" {{$employee->id}}">{{$employee->name}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col">
            <label for="status">{{__('common.status')}}: </label>
            <select wire:model.live="status" class="form-select">
                <option value="all" selected>{{__('Select Status')}}</option>
                <option value="approved">{{__('common.approved')}}</option>
                <option value="rejected">{{__('common.rejected')}}</option>
                <option value="pending">{{__('common.pending')}}</option>
            </select>
        </div>
        <div class="col">
            <label for="period">{{__('Period')}}: </label>
            <select wire:model.live="period" class="form-select  @error('period') is-invalid @enderror">
                <option value="" selected>{{__('Select Period')}}</option>
                @foreach ($periods as $key => $period)
                <option value="{{$period['year'].'-'.$period['month_number'] }}">{{$period['year'] .' - '. $period['month']}}</option>
                @endforeach
            </select>
            @error('period')
            <div class="invalid-feedback">{{$message}}</div>
            @enderror
        </div>

    </div>
    <div class="card">
        <div class="table-responsive pb-4 text-gray-700">
            <table class="table table-hover table-bordered align-items-center dataTable">
                <thead>
                    <tr>
                        <th class="border-bottom">{{__('employees.employee')}}</th>
                        <th class="border-bottom">{{__('CheckIn Time')}}</th>
                        <th class="border-bottom">{{__('CheckOut Time')}}</th>
                        <th class="border-bottom">{{__('overtime.hours_worked')}}</th>
                        <th class="border-bottom">{{__('common.sup_approval')}}</th>
                        <th class="border-bottom">{{__('common.mgr_approval')}}</th>
                        <th class="border-bottom">{{__('common.created_date')}}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($checklogs as $checklog)
                    <tr>
                        <td>
                            <a href="#" class="d-flex align-items-center">
                                <div class="avatar-md d-flex align-items-center justify-content-center fw-bold fs-5 rounded bg-primary me-3"><span class="text-white">{{$checklog->user->initials}}</span></div>
                                <div class="d-block"><span class="fw-bold fs-6">{{ucwords($checklog->user_full_name)}}</span>
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
                                <h4 class="fs-4 fw-bold">{{__('common.oops_nothing_here')}} &#128540;</h4>
                                <p>{{__('common.no_records_found')}}</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            <div class='pt-3 px-3 '>
                {{ $checklogs->links() }}
            </div>
        </div>
    </div>
</div>