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
                        <li class="breadcrumb-item active" aria-current="page">{{__('reports.payslips_reports')}}</li>
                    </ol>
                </nav>
                <h1 class="h4 mt-n2 d-flex justify-content-start align-items-end">
                    <svg class="icon me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    {{__('reports.payslips_report')}}
                </h1>
                <p class="mt-n1 mx-2">{{__('reports.generate_payslips_reports')}} &#x1F4DD; </p>
            </div>
            <div class="mb-2 mx-3">
                <div class="btn-toolbar " wire:loading.remove>
                    <a href="#" id="generate-payslip-report-btn" wire:click.prevent="generateReport()" class="btn btn-sm btn-gray-800 d-inline-flex align-items-center  {{count($payslips) > 0 ? '' :'disabled'}}">
                        <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        {{__('common.generate_report')}}
                    </a>
                    <a href="#" wire:click.prevent="downloadBulkPayslips()" class="btn btn-sm btn-primary d-inline-flex align-items-center ms-2 {{$employee_id && $employee_id !== 'all' ? '' : 'disabled'}}">
                        <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        {{__('payslips.download_payslips')}}
                    </a>
                    <a href="/portal/download-jobs" wire:navigate class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center ms-2">
                        <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        {{__('common.view_jobs')}}
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
        <div class="col">
            <label for="search">{{__('common.search')}}: </label>
            <input wire:model.live="query_string" id="search" type="text" placeholder="{{__('common.search_placeholder')}}" class="form-control">
        </div>
        @hasanyrole('manager|admin')
        <div class="col">
            <label for="company">{{__('companies.company')}}: </label>
            <select wire:model.live="selectedCompanyId" id="selectedCompanyId" class="form-select">
                <option value="" selected>{{__('common.select_company')}}</option>
                <option value="all">{{__('common.all_companies')}}</option>
                @foreach ($companies as $company)
                <option value="{{$company->id}}">{{$company->name}}</option>
                @endforeach
            </select>
        </div>
        @endhasanyrole
        <div class="col">
            <label for="selectedDepartmentId">{{__('departments.department')}}: </label>
            <select wire:model.live="selectedDepartmentId" class="form-select @error('selectedDepartmentId') is-invalid @enderror">
                <option value="" selected>{{__('common.select_department')}}</option>
                <option value="all">{{__('common.all_name',['name'=>__('departments.departments')])}}</option>
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
                    <option value="" selected>{{__('common.select_employees')}}</option>
                    <option value="all">{{__('employees.all_employees')}}</option>
                    @foreach ($employees as $employee)
                    <option value=" {{$employee->id}}">{{$employee->name}}</option>
                    @endforeach
                </select>
            </div>
        </div>

    </div>
    <div class='row pb-3'>
        <div class="col">
            <label for="sms_status">{{__('common.sms_status')}}: </label>
            <select wire:model.live="sms_status" class="form-select">
                <option value="">{{__('common.select_status')}}</option>
                <option value="all" selected>{{__('common.select_all')}}</option>
                <option value="3">{{__('common.pending')}}</option>
                <option value="4">{{__('common.successful')}}</option>
                <option value="5">{{__('common.failed')}}</option>
                <option value="6">{{__('common.disabled')}}</option>
            </select>
        </div>
        <div class="col">
            <label for="email_status">{{__('common.email_status')}}: </label>
            <select wire:model.live="email_status" class="form-select">
                <option value="">{{__('common.select_status')}}</option>
                <option value="all" selected>{{__('common.select_all')}}</option>
                <option value="0">{{__('common.pending')}}</option>
                <option value="1">{{__('common.successful')}}</option>
                <option value="2">{{__('common.failed')}}</option>
            </select>
        </div>

        <div class="col">
            <label for="Start Date">{{__('common.start_date')}}: </label>
            <input wire:model.live="start_date" id="StartDate" type="date" class="form-control">
        </div>
        <div class="col">
            <label for="End Date">{{__('common.end_date')}}: </label>
            <input wire:model.live="end_date" id="EndDate" type="date" class="form-control">
        </div>
    </div>
    <div class="card pb-3 ">
        <div class="table-responsive">
            <table class="table employee-table table-bordered table-hover align-items-center dataTable" id="datatable">
                <thead>
                    <tr>
                        <th class="border-bottom">{{__('common.name')}}</th>
                        <th class="border-bottom">{{__('employees.matricule')}}</th>
                        <th class="border-bottom">{{__('common.contacts')}}</th>
                        <th class="border-bottom">{{__('common.period')}}</th>
                        <th class="border-bottom">{{__('common.when')}}</th>
                        <th class="border-bottom">{{__('reports.email_status')}}</th>
                        <th class="border-bottom">{{__('common.sms_status')}}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payslips as $payslip)
                    <tr>
                        <td>
                            <a href="#" class="d-flex align-items-center">
                                <div class="avatar d-flex align-items-center justify-content-center fw-bold rounded bg-primary text-white me-3"><span>{{$payslip->initials}}</span></div>
                                <div class="d-block"><span class="fw-bold">{{$payslip->name}}</span>
                                    <div class="small text-gray">{{$payslip->email}}</div>
                                </div>
                            </a>
                        </td>
                        <td>
                            <span class="fw-normal">{{$payslip->matricule}}</span>
                        </td>
                        <td>
                            @if(!is_null($payslip->phone))
                            <a href='tel:{{$payslip->phone}}' class="small text-gray">
                                <svg class="icon icon-xxs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                </svg><span class="fw-normal"> {{$payslip->phone}}</span><br>
                            </a>
                            @endif
                        </td>
                        <td>
                            <span class="fw-normal">{{$payslip->month}} - {{$payslip->year}}</span>
                        </td>
                        <td>
                            <span class="fw-normal">{{$payslip->created_at}}</span>
                        </td>
                        <td>
                            @if($payslip->email_sent_status == 1)
                            <span class="badge badge-lg text-md bg-success">{{__('common.successful')}}</span>
                            @elseif($payslip->email_sent_status == 2 )
                            <span class="badge badge-lg text-md bg-danger">{{__('common.failed')}}</span>
                            @else
                            <span class="badge badge-lg text-md text-gray bg-warning">{{__('common.pending')}}</span>
                            @endif
                        </td>
                        <td>
                            @if($payslip->sms_sent_status == 1)
                            <span class="badge badge-lg text-md bg-success">{{__('common.successful')}}</span>
                            @elseif($payslip->sms_sent_status == 2)
                            <span class="badge badge-lg text-md bg-danger">{{__('common.failed')}}</span>
                            @elseif($payslip->sms_sent_status == 3)
                            <span class="badge badge-lg text-md bg-info">{{__('Disabled')}}</span>
                            @else
                            <span class="badge badge-lg text-md text-dark bg-warning">{{__('common.pending')}}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10">
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
                {{ $payslips->links() }}
            </div>
        </div>
    </div>
</div>