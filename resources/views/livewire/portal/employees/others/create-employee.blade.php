<div wire:ignore.self class="modal side-layout-modal fade" id="CreateEmployeeModal" tabindex="-1" aria-labelledby="modal-form" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered " role="document">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="p-3 p-lg-5">
                    <div class="mb-4 mt-md-0">
                        <h1 class="mb-0 h4">{{__('employees.create_new_employee')}}</h1>
                        <p>
                            @if(auth()->user()->hasRole('supervisor'))
                                {{__('employees.create_new_employee_in_department')}} &#128522;
                            @else
                                {{__('employees.create_new_employee_for')}} - {{$company->name ?? '' }} &#128522;
                            @endif
                        </p>
                    </div>
                    <x-form-items.form wire:submit="store" class="form-modal">
                        <input type='hidden' name='employee_id' value="" id="EmployeeId">
                        <div class="form-group mb-2 row">
                            <div class='col-md-6 col-xs-12'>
                                <label for="company">{{__('companies.company')}}</label>
                                @if(auth()->user()->hasRole('supervisor'))
                                    <input type="text" class="form-control" value="{{__('employees.department_company')}}" disabled>
                                @else
                                    <input type="text" class="form-control  @error('company') is-invalid @enderror" name="company" value="{{$company->name ?? ''}}" disabled>
                                @endif
                            </div>
                            <div class='col-md-6 col-xs-12'>
                                <label for="department">{{__('departments.department')}}</label>
                                <select wire:model.live="department_id" class="form-select  @error('department_id') is-invalid @enderror">
                                    <option value="">{{__("employees.select_department")}}</option>
                                    @foreach ($departments as $department)
                                    <option value="{{$department->id}}">{{$department->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class='form-group mb-2'>
                            <label for="service_id">{{__('employees.service')}}</label>
                            <select wire:model="service_id" name="service_id" class="form-select  @error('service_id') is-invalid @enderror">
                                <option value="">{{__("employees.select_service")}}</option>
                                @foreach ($services as $service)
                                <option value="{{$service->id}}">{{$service->name}}</option>
                                @endforeach
                            </select>
                            @error('service_id')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class='form-group mb-2'>
                            <x-choices-multi-select
                                id="create_selected_roles"
                                wireModel="selected_roles"
                                :options="$roles->pluck('name', 'name')->map(fn($name) => ucfirst($name))->toArray()"
                                :selected="$selected_roles"
                                label="{{__('common.roles')}}"
                                help="{{__('common.maximum_2_roles_allowed')}}"
                                class="form-select" />
                        </div>
                        <div class="form-group mb-2">
                            <div class='col-md-6 col-xs-12'>
                                <label for="first_name">{{__('employees.first_name')}}</label>
                                <input wire:model="first_name" type="text" class="form-control  @error('first_name') is-invalid @enderror" placeholder="John" required="" name="first_name">
                                @error('first_name')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class='col-md-6 col-xs-12'>
                                <label for="last_name">{{__('employees.last_name')}}</label>
                                <input wire:model="last_name" type="text" class="form-control  @error('last_name') is-invalid @enderror" placeholder="Doe" required="" name="last_name">
                                @error('last_name')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                        </div>
                        <div class='form-group mb-2'>
                            <label for="date_of_birth">{{__('common.date_of_birth')}}</label>
                            <input wire:model="date_of_birth" type="date" class="form-control @error('date_of_birth') is-invalid @enderror" required="" name="date_of_birth">
                            @error('date_of_birth')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-2 row">
                            <div class='col-md-6 col-xs-12'>
                                <label for="matricule">{{__('employees.matricule')}}</label>
                                <input wire:model="matricule" type="text" class="form-control @error('matricule') is-invalid @enderror" placeholder="1134578" required="" name="matricule">
                                @error('matricule')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class='col-md-6 col-xs-12'>
                                <label for="email">{{__('employees.email')}}</label>
                                <input wire:model="email" type="email" class="form-control @error('email') is-invalid @enderror" placeholder="example@company.com" required="" name="email">
                                @error('email')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror

                            </div>
                        </div>
                        <div class="form-group mb-2 row">
                            <div class='col-md-6 col-xs-12'>
                                <label for="professional_phone_number">{{__('common.prof_phone_number')}}</label>
                                <input wire:model="professional_phone_number" type="text" class="form-control  @error('professional_phone_number') is-invalid @enderror" placeholder="2376xxxxxxxxx" name="professional_phone_number">
                                @error('professional_phone_number')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class='col-md-6 col-xs-12'>
                                <label for="personal_phone_number">{{__('common.personal_phone_number')}}</label>
                                <input wire:model="personal_phone_number" type="text" class="form-control  @error('personal_phone_number') is-invalid @enderror" placeholder="2376xxxxxxxxx" name="personal_phone_number">
                                @error('personal_phone_number')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <div class="form-check form-switch">
                                <input wire:model="receive_sms_notifications" class="form-check-input" type="checkbox" id="receive_sms_notifications_create">
                                <label class="form-check-label" for="receive_sms_notifications_create">
                                    {{__('employees.receive_sms_notifications')}}
                                </label>
                            </div>
                            <small class="text-muted">{{__('employees.enable_sms_notifications')}}</small>
                        </div>
                        <div class="form-group mb-2 row">
                            <div class='col-md-6 col-xs-12'>
                                <label for="remaining_leave_days">{{__('employees.total_leave_days')}}</label>
                                <input wire:model="remaining_leave_days" type="number" class="form-control  @error('remaining_leave_days') is-invalid @enderror" placeholder="18" name="remaining_leave_days">
                                @error('remaining_leave_days')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class='col-md-6 col-xs-12'>
                                <label for="monthly_leave_allocation">{{__('employees.monthly_allocations')}}</label>
                                <input wire:model="monthly_leave_allocation" type="number" class="form-control  @error('monthly_leave_allocation') is-invalid @enderror" placeholder="1.5" name="monthly_leave_allocation">
                                @error('monthly_leave_allocation')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group mb-2">
                            <label for="net_salary">{{__('employees.net_salary')}}</label>
                            <input wire:model="net_salary" type="text" class="form-control  @error('net_salary') is-invalid @enderror" name="net_salary">
                            @error('net_salary')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-2 row">
                            <div class='col-md-6 col-xs-12'>
                                <label for="salary_grade">{{__('employees.salary_grade')}}</label>
                                <input wire:model="salary_grade" type="text" class="form-control  @error('salary_grade') is-invalid @enderror" autofocus="" name="salary_grade">
                                @error('salary_grade')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class='col-md-6 col-xs-12'>
                                <label for="position">{{__('common.position')}}</label>
                                <input wire:model="position" type="text" class="form-control  @error('position') is-invalid @enderror" autofocus="" name="position">
                                @error('position')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group row mb-2">
                            <div class='col'>
                                <label for="work_start_time">{{__('overtime.work_start_time')}}</label>
                                <input wire:model="work_start_time" type="time" class="form-control  @error('work_start_time') is-invalid @enderror" value="{{now()->format('H:i')}}" required="">
                                @error('work_start_time')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class='col'>
                                <label for="work_end_time">{{__('overtime.work_end_time')}}</label>
                                <input wire:model="work_end_time" type="time" class="form-control  @error('work_end_time') is-invalid @enderror" value="{{now()->format('H:i')}}" required="">
                                @error('work_end_time')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group mb-2 row">
                            <div class='col-md-6 col-xs-12'>
                                <label for="status">{{__('common.status')}}</label>
                                <select wire:model="status" name="status" class="form-select  @error('status') is-invalid @enderror">
                                    <option value="">{{__("Select status")}}</option>
                                    <option value="true">{{__('common.active')}}</option>
                                    <option value="false">{{__('employees.banned')}}</option>
                                </select>
                                @error('status')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class='col-md-6 col-xs-12'>
                                <label for="contract_end">{{__('employees.contract_end_date')}}</label>
                                <input wire:model="contract_end" type="date" class="form-control  @error('contract_end') is-invalid @enderror" autofocus="" name="contract_end">
                                @error('contract_end')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="password">{{__('employees.create_employee_password')}}</label>
                            <input wire:model="password" type="text" class="form-control  @error('password') is-invalid @enderror" autofocus="" name="password">
                            @error('password')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-gray-200 text-gray-600 ms-auto mx-3" data-bs-dismiss="modal">{{__('common.close')}}</button>
                            <button type="submit" wire:click.prevent="store" class="btn btn-primary " wire:loading.attr="disabled">
                                @if(auth()->user()->hasRole('supervisor'))
                                    {{__('employees.add_employee')}}
                                @else
                                    {{__('employees.add_to')}} {{$company->name ?? ''}}
                                @endif
                            </button>
                        </div>
                    </x-form-items.form>
                </div>
            </div>
        </div>
    </div>
</div>