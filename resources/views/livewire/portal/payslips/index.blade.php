<div>
    <x-alert />
    <div class='py-2 pb-2'>
        <div class="d-flex justify-content-between w-100 flex-wrap mb-4 align-items-center">
            <div class="mb-lg-0">
                <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
                    <ol class="breadcrumb breadcrumb-dark breadcrumb-transparent">
                        <li class="breadcrumb-item"><a href="#"><svg class="icon icon-xxs" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                                </svg></a></li>
                        <li class="breadcrumb-item"><a href="{{route('portal.dashboard')}}">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{__('Payslips Management')}}</li>
                    </ol>
                </nav>
                <h1 class="h4">
                    <svg class="icon me-1" fill="none" stroke="currentColor" viewBox="0 0 25 25" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    {{__('Payslips Management')}}
                </h1>
                <p class="mb-0">{{__('Create new, update and delete any groups on the platform')}}</p>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4">
            <div class="card p-3 p-lg-4">
                <div class="text-center text-md-center mb-4 mt-md-0">
                    <h1 class="mb-0 h4">{{__('Send Payslips')}}</h1>
                </div>
                <x-form-items.form wire:submit="send">
                    @hasanyrole('manager|admin')
                    <div class="form-group mb-4">
                        <label for="company">{{__('Company')}}</label>
                        <select wire:model.live="company_id" class="form-select @error('company_id') is-invalid @enderror" id="company">
                            <option value=''>{{__('--Select Company--')}}</option>
                            @foreach ($companies as $company)

                            <option value='{{$company->id}}' wire:key="company-{{ $company->id }}">{{$company->name ." - with ". count($company->departments) ." departments"}}</option>
                            @endforeach
                        </select>
                        <div>
                            @error('company_id')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                    </div>
                    @endhasanyrole

                    <div class="form-group mb-4">
                        <label for="department">{{__('Department')}}</label>
                        <select wire:model.live="department_id" class="form-select @error('department_id') is-invalid @enderror" id="department">
                            <option value=''>{{__('--Select Department--')}}</option>
                            @foreach ($departments as $department)
                            <option value='{{$department->id}}' wire:key="department-{{ $department->id }}">{{$department->name ." - with ". count($department->employees) ." employees"}}</option>
                            @endforeach
                        </select>
                        <div>
                            @error('department_id')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                    </div>
                    <div class='form-group mb-4'>
                        <label for="month">{{__('Month')}}</label>
                        <select wire:model.live="month" class="form-select @error('month') is-invalid @enderror">
                            <option value='January'>{{__('January')}}</option>
                            <option value='February'>{{__('February')}}</option>
                            <option value='March'>{{__('March')}}</option>
                            <option value='April'>{{__('April')}}</option>
                            <option value='May'>{{__('May')}}</option>
                            <option value='June'>{{__('June')}}</option>
                            <option value='July'>{{__('July')}}</option>
                            <option value='August'>{{__('August')}}</option>
                            <option value='September'>{{__('September')}}</option>
                            <option value='October'>{{__('October')}}</option>
                            <option value='November'>{{__('November')}}</option>
                            <option value='December'>{{__('December')}}</option>
                        </select>
                        <div>
                            @error('month')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="form-group mb-4">
                        <label for="payslip_file" class="form-label">{{__('Select Payslip')}}</label>
                        <input class="form-control  @error('payslip_file') is-invalid @enderror" type="file" wire:model.live="payslip_file" id="payslip_file">
                        <div>
                            @error('payslip_file')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" wire:click.prevent="send" wire:loading.attr="disabled" class="btn btn-gray-800">{{__('Start Processing')}}</button>
                    </div>
                </x-form-items.form>
            </div>
        </div>

        <div class='col-md-8'>
            <div class="card" wire:poll.visible.55s>
                @if(count($jobs)>0)
                <div class="table-responsive py-4">
                    <table class="table user-table table-hover align-items-center dataTable">
                        <thead>
                            <tr>
                                <th class="border-bottom">{{__('Department')}}</th>
                                <th class="border-bottom">{{__('By')}}</th>
                                <th class="border-bottom">{{__('Target')}}</th>
                                <th class="border-bottom">{{__('Period')}}</th>
                                <th class="border-bottom">{{__('Date Created')}}</th>
                                <th class="border-bottom">{{__('Status')}}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($jobs as $job)
                            <tr>
                                <td>
                                    @if (!is_null($job->department))
                                    <a href="/admin/payslips/{{$job->id}}/details" class="d-flex align-items-center">
                                        <div class="avatar d-flex align-items-center justify-content-center fw-bold rounded bg-primary me-3 text-white"><span>{{initials($job->department->name)}}</span></div>
                                        <div class="d-block"><span class="fw-bold">{{$job->department->name}}</span>
                                            @hasanyrole('admin')
                                            <div class="small text-gray">
                                                <span class="d-flex align-items-baseline">
                                                    <svg class="icon icon-xxs small me-1" fill="currentColor" stroke="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                        <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                    </svg>
                                                    {{!is_null($job->owner) ? $job->owner->first_name : ''}}
                                                </span>
                                            </div>
                                            @endhasanyrole
                                        </div>
                                    </a>
                                    @else
                                    <p>{{__('Department deleted!')}}</p>
                                    @endif
                                </td>
                                <td>
                                    <div class="small text-gray">
                                        <span class="d-flex align-items-baseline">
                                            <svg class="icon icon-xxs small me-1" fill="currentColor" stroke="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            {{!is_null($job->owner) ? $job->owner->first_name : ''}}
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span>{{!is_null($job->department) ? count($job->department->employees) : 0 }} - {{__('Employees')}}</span>
                                </td>
                                <td>
                                    <span>{{$job->month}} - {{$job->year}} </span>
                                </td>
                                <td>
                                    <span>{{$job->created_at->diffForHumans()}}</span>
                                </td>
                                <td>
                                    @if($job->status == 'successful')
                                    <span class="badge badge-lg text-md bg-success">{{__('Succesful')}}</span>
                                    @elseif($job->status == 'failed')
                                    <span class="badge badge-lg text-md bg-danger">{{__('Failed')}}</span>
                                    @else
                                    <span class="badge badge-lg text-md bg-warning">{{__('Processing...')}}</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class='mb-4 text-center'>
                    <a href="{{route('portal.payslips.history')}}" class="btn btn-secondary text-dark">{{__('View past history')}}</a>
                </div>
                @else
                <div class='p-5 text-center '>
                    <img src="{{asset('img/empty.svg')}}" alt='' class="w-25 h-25">
                    <p class="py-4 h5 text-muted">{{__('Start processing payslip to see the outcome here')}}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>