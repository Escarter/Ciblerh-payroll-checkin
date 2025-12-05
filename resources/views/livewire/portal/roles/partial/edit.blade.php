<div>
    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <i class="ki-duotone ki-cross-circle fs-2 text-danger me-3"></i>
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    <x-form-items.form wire:submit="update">
        <div class="d-flex flex-column scroll-y">
            <!--begin::Input group-->
            <div class="fv-row mb-3 fv-plugins-icon-container">
                <!--begin::Label-->
                <label class="form-label mb-2">
                    <span class="required">{{__('roles.role_name')}}</span>
                </label>
                <!--end::Label-->
                <!--begin::Input-->
                <input class="form-control form-control-solid" placeholder="{{__('roles.enter_role_name')}}" wire:model="name">
                <!--end::Input-->
                <div class="fv-plugins-message-container invalid-feedback"></div>
            </div>
            <!--end::Input group-->
            <!--begin::Permissions-->
            <div class="fv-row mb-3">
                <!--begin::Label-->
                <label class="form-label mb-2">{{__('roles.permissions_of_role')}}</label>
                <!--end::Label-->
                <!--begin::Table wrapper-->
                <div class="table-responsive">
                    <!--begin::Table-->
                    <div class="table align-middle table-row-dashed gy-3">
                        <!--begin::Table body-->
                        <div class="text-gray-600 ">
                            <!--begin::Table row-->
                            <div class="d-flex border-bottom border-1">
                                <div class="text-gray-800 w-25"> {{__('roles.admin_access')}}</div>
                                <div>
                                    <!--begin::Checkbox-->
                                    <label class="form-check form-check-custom form-check-solid me-9">
                                        <input class="form-check-input" type="checkbox" wire:model="makeAdmin">
                                        <span class="form-check-label" for="select_all_roles">{{__('roles.grant_all_permissions')}}</span>
                                    </label>
                                    <!--end::Checkbox-->
                                </div>
                            </div>


                            <div class="text-gray-800 w-100 mt-3 fs-0 fw-bold">{{__('companies.companies')}}, {{__('departments.departments')}}, {{__('services.services')}} and {{__('employees.employees')}} {{__('common.permissions')}}</div>
                            <div class="d-flex border-bottom border-1">
                                <!--begin::Label-->
                                <div class="text-gray-800 w-25">{{__('companies.companies')}}</div>
                                <div>
                                    <!--begin::Wrapper-->
                                    <div class="d-flex">
                                        <!--begin::Checkbox-->
                                        <label class="form-check form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" value="" wire:model="selectAllCompanyPermissions">
                                            <span class="form-check-label">{{__('common.all')}}</span>
                                        </label>
                                        @foreach($CompanyPermissions as $key => $value)
                                        <label class="form-check  form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" wire:model="selectedCompanyPermissions" value="{{$value}}">
                                            <span class="form-check-label">{{__($key)}}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                                <!--end::Input group-->
                            </div>
                            <div class="d-flex border-bottom border-1">
                                <!--begin::Label-->
                                <div class="text-gray-800 w-25">{{__('departments.departments')}}</div>
                                <div>
                                    <!--begin::Wrapper-->
                                    <div class="d-flex">
                                        <!--begin::Checkbox-->
                                        <label class="form-check form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" value="" wire:model="selectAllDepartmentPermissions">
                                            <span class="form-check-label">{{__('common.all')}}</span>
                                        </label>
                                        @foreach($DepartmentPermissions as $key => $value)
                                        <label class="form-check  form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" wire:model="selectedDepartmentPermissions" value="{{$value}}">
                                            <span class="form-check-label">{{__($key)}}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                                <!--end::Input group-->
                            </div>
                            <div class="d-flex border-bottom border-1">
                                <!--begin::Label-->
                                <div class="text-gray-800 w-25">{{__('services.services')}}</div>
                                <div>
                                    <!--begin::Wrapper-->
                                    <div class="d-flex">
                                        <!--begin::Checkbox-->
                                        <label class="form-check form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" value="" wire:model="selectAllServicePermissions">
                                            <span class="form-check-label">{{__('common.all')}}</span>
                                        </label>
                                        @foreach($ServicePermissions as $key => $value)
                                        <label class="form-check  form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" wire:model="selectedServicePermissions" value="{{$value}}">
                                            <span class="form-check-label">{{__($key)}}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                                <!--end::Input group-->
                            </div>
                            <div class="d-flex border-bottom border-1">
                                <!--begin::Label-->
                                <div class="text-gray-800 w-25">{{__('employees.employees')}}</div>
                                <div>
                                    <!--begin::Wrapper-->
                                    <div class="d-flex">
                                        <!--begin::Checkbox-->
                                        <label class="form-check form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" value="" wire:model="selectAllEmployeePermissions">
                                            <span class="form-check-label">{{__('common.all')}}</span>
                                        </label>
                                        @foreach($EmployeePermissions as $key => $value)
                                        <label class="form-check  form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" wire:model="selectedEmployeePermissions" value="{{$value}}">
                                            <span class="form-check-label">{{__($key)}}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                                <!--end::Input group-->
                            </div>

    

                            <div class="text-gray-800 w-100 mt-3 fs-0 fw-bold">{{__('roles.overtime_and_payments_permissions')}}</div>
                            <div class="d-flex border-bottom border-1">
                                <!--begin::Label-->
                                <div class="text-gray-800 w-25">{{__('common.checkins')}}</div>
                                <div>
                                    <!--begin::Wrapper-->
                                    <div class="d-flex">
                                        <!--begin::Checkbox-->
                                        <label class="form-check form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" value="" wire:model="selectAllTickingPermissions">
                                            <span class="form-check-label">{{__('common.all')}}</span>
                                        </label>
                                        @foreach($TickingPermissions as $key => $value)
                                        <label class="form-check  form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" wire:model="selectedTickingPermissions" value="{{$value}}">
                                            <span class="form-check-label">{{__($key)}}</span>
                                        </label>

                                        @endforeach
                                    </div>
                                </div>
                                <!--end::Input group-->
                            </div>
                            <div class="d-flex border-bottom border-1">
                                <!--begin::Label-->
                                <div class="text-gray-800 w-25">{{__('common.overtime')}}</div>
                                <div>
                                    <!--begin::Wrapper-->
                                    <div class="d-flex">
                                        <!--begin::Checkbox-->
                                        <label class="form-check form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" value="" wire:model="selectAllOvertimePermissions">
                                            <span class="form-check-label">{{__('common.all')}}</span>
                                        </label>
                                        @foreach($OvertimePermissions as $key => $value)
                                        <label class="form-check  form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" wire:model="selectedOvertimePermissions" value="{{$value}}">
                                            <span class="form-check-label">{{__($key)}}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                                <!--end::Input group-->
                            </div>
                            <div class="text-gray-800 w-100 mt-3 fs-0 fw-bold">{{__('roles.absences_and_advance_salary_permissions')}}</div>
                            <div class="d-flex border-bottom border-1">
                                <!--begin::Label-->
                                <div class="text-gray-800 w-25">{{__('common.advance_salary')}}</div>
                                <div>
                                    <!--begin::Wrapper-->
                                    <div class="d-flex">
                                        <!--begin::Checkbox-->
                                        <label class="form-check form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" value="" wire:model="selectAllAdvanceSalaryPermissions">
                                            <span class="form-check-label">{{__('common.all')}}</span>
                                        </label>
                                        @foreach($AdvanceSalaryPermissions as $key => $value)
                                        <label class="form-check  form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" wire:model="selectedAdvanceSalaryPermissions" value="{{$value}}">
                                            <span class="form-check-label">{{__($key)}}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                                <!--end::Input group-->
                            </div>
                            <div class="d-flex border-bottom border-1">
                                <!--begin::Label-->
                                <div class="text-gray-800 w-25">{{__('common.absences')}}</div>
                                <div>
                                    <!--begin::Wrapper-->
                                    <div class="d-flex">
                                        <!--begin::Checkbox-->
                                        <label class="form-check form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" value="" wire:model="selectAllAbsencePermissions">
                                            <span class="form-check-label">{{__('common.all')}}</span>
                                        </label>
                                        @foreach($AbsencePermissions as $key => $value)
                                        <label class="form-check  form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" wire:model="selectedAbsencePermissions" value="{{$value}}">
                                            <span class="form-check-label">{{__($key)}}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                                <!--end::Input group-->
                            </div>
                            <div class="text-gray-800 w-100 mt-3 fs-0 fw-bold">{{__('roles.leave_permissions')}}</div>
                            <div class="d-flex border-bottom">
                                <!--begin::Label-->
                                <div class="text-gray-800 w-25">{{__('common.leave')}}</div>
                                <div>
                                    <!--begin::Wrapper-->
                                    <div class="d-flex">
                                        <!--begin::Checkbox-->
                                        <label class="form-check form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" value="" wire:model="selectAllLeavePermissions">
                                            <span class="form-check-label">{{__('common.all')}}</span>
                                        </label>
                                        @foreach($LeavePermissions as $key => $value)
                                        <label class="form-check  form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" wire:model="selectedLeavePermissions" value="{{$value}}">
                                            <span class="form-check-label">{{__($key)}}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                                <!--end::Input group-->
                            </div>
                            <div class="d-flex border-top border-bottom border-1">
                                <!--begin::Label-->
                                <div class="text-gray-800 w-25">{{__('common.leave_types')}}</div>
                                <div>
                                    <!--begin::Wrapper-->
                                    <div class="d-flex">
                                        <!--begin::Checkbox-->
                                        <label class="form-check form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" value="" wire:model="selectAllLeaveTypePermissions">
                                            <span class="form-check-label">{{__('common.all')}}</span>
                                        </label>
                                        @foreach($LeaveTypePermissions as $key => $value)
                                        <label class="form-check  form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" wire:model="selectedLeaveTypePermissions" value="{{$value}}">
                                            <span class="form-check-label">{{__($key)}}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                                <!--end::Input group-->
                            </div>
                            <div class="text-gray-800 w-100 mt-3 fs-0 fw-bold">{{__('roles.payslips_processing_permissions')}}</div>
                            <div class="d-flex border-bottom border-1">
                                <!--begin::Label-->
                                <div class="text-gray-800 w-25">{{__('Payslip')}}</div>
                                <div>
                                    <!--begin::Wrapper-->
                                    <div class="d-flex">
                                        <!--begin::Checkbox-->
                                        <label class="form-check form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" value="" wire:model="selectAllPayslipPermissions">
                                            <span class="form-check-label">{{__('common.all')}}</span>
                                        </label>
                                        @foreach($PayslipPermissions as $key => $value)
                                        <label class="form-check  form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" wire:model="selectedPayslipPermissions" value="{{$value}}">
                                            <span class="form-check-label">{{__($key)}}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                                <!--end::Input group-->
                            </div>
                           
                                <div class="text-gray-800 w-100 mt-3 fs-0 fw-bold">{{__('roles.roles_and_audit_log_features_permissions')}}</div>
                            <div class="d-flex border-bottom border-1">
                                <!--begin::Label-->
                                <div class="text-gray-800 w-25">{{__('roles.roles')}}</div>
                                <div>
                                    <!--begin::Wrapper-->
                                    <div class="d-flex">
                                        <!--begin::Checkbox-->
                                        <label class="form-check form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" value="" wire:model="selectAllRolePermissions">
                                            <span class="form-check-label">{{__('common.all')}}</span>
                                        </label>
                                        @foreach($RolePermissions as $key => $value)
                                        <label class="form-check  form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" wire:model="selectedRolePermissions" value="{{$value}}">
                                            <span class="form-check-label">{{__($key)}}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                                <!--end::Input group-->
                            </div>
                            <div class="d-flex border-bottom border-1">
                                <!--begin::Label-->
                                <div class="text-gray-800 w-25">{{__('reports.journal_d_audit')}}</div>
                                <div>
                                    <!--begin::Wrapper-->
                                    <div class="d-flex">
                                        <!--begin::Checkbox-->
                                        <label class="form-check form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" value="" wire:model="selectAllAuditLogPermissions">
                                            <span class="form-check-label">{{__('common.all')}}</span>
                                        </label>
                                        @foreach($AuditLogPermissions as $key => $value)
                                        <label class="form-check  form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" wire:model="selectedAuditLogPermissions" value="{{$value}}">
                                            <span class="form-check-label">{{__($key)}}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                                <!--end::Input group-->
                            </div>
                            <div class="d-flex border-bottom border-1">
                                <!--begin::Label-->
                                    <div class="text-gray-800 w-25">{{__('roles.settings')}}</div>
                                <div>
                                    <!--begin::Wrapper-->
                                    <div class="d-flex">
                                        <!--begin::Checkbox-->
                                        <label class="form-check form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" value="" wire:model="selectAllSettingPermissions">
                                            <span class="form-check-label">{{__('common.all')}}</span>
                                        </label>
                                        @foreach($SettingPermissions as $key => $value)
                                        <label class="form-check  form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" wire:model="selectedSettingPermissions" value="{{$value}}">
                                            <span class="form-check-label">{{__($key)}}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                                <!--end::Input group-->
                            </div>
                            <div class="d-flex border-bottom border-1">
                                <!--begin::Label-->
                                <div class="text-gray-800 w-25">{{__('common.reports')}}</div>
                                <div>
                                    <!--begin::Wrapper-->
                                    <div class="d-flex">
                                        <!--begin::Checkbox-->
                                        <label class="form-check form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" value="" wire:model="selectAllReportPermissions">
                                            <span class="form-check-label">{{__('common.all')}}</span>
                                        </label>
                                        @foreach($ReportPermissions as $key => $value)
                                        <label class="form-check  form-check-custom form-check-solid me-3 me-lg-20">
                                            <input class="form-check-input" type="checkbox" wire:model="selectedReportPermissions" value="{{$value}}">
                                            <span class="form-check-label">{{__($key)}}</span>
                                        </label>
                                        @endforeach
                                    </div>
                                </div>
                                <!--end::Input group-->
                            </div>
                        </div>
                        <!--end::Table body-->
                    </div>
                    <!--end::Table-->
                </div>
                <!--end::Table wrapper-->
            </div>
            <!--end::Permissions-->
        </div>
        <div class="d-flex justify-content-end">
            <button type="button" wire:click.prevent="clearFields" class="btn btn-gray-200 text-gray-600  btn-sm ms-auto mx-3" data-bs-dismiss="modal">{{ __('common.close') }}</button>
            <button type="submit" wire:click.prevent="update" class="btn btn-primary btn-sm " wire:loading.attr="disabled">{{ __('common.save') }}</button>
        </div>
    </x-form-items.form>
</div>
