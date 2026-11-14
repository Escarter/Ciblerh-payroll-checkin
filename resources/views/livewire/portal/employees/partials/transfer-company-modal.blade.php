<div wire:ignore.self class="modal side-layout-modal fade" id="TransferCompanyModal" tabindex="-1" aria-labelledby="TransferCompanyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="TransferCompanyModalLabel">
                    <i class="fas fa-exchange-alt text-primary me-2"></i>{{ __('employees.transfer_to_company_title') }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
            </div>

            <x-form-items.form wire:submit="transferEmployeesToCompany">
                <div class="modal-body">
                    <div class="alert alert-info d-flex align-items-start mb-3" role="alert">
                        <i class="fas fa-info-circle me-2 mt-1"></i>
                        <div>
                            <strong>{{ __('employees.transfer_to_company_info_title') }}</strong>
                            <div class="small mt-1">{{ __('employees.transfer_to_company_info_body') }}</div>
                        </div>
                    </div>

                    @if(!empty($transferEmployeeSummary))
                        <div class="mb-3">
                            <label class="form-label text-muted small mb-1">{{ __('employees.selected_employee') }}</label>
                            <div class="fw-semibold">{{ $transferEmployeeSummary }}</div>
                        </div>
                    @elseif(!empty($transferEmployeeIds))
                        <div class="mb-3">
                            <span class="badge bg-primary">
                                {{ __('employees.employees_selected_for_transfer', ['count' => count($transferEmployeeIds)]) }}
                            </span>
                        </div>
                    @endif

                    <div class="form-group mb-3">
                        <label for="transferCompanyId" class="form-label">
                            {{ __('employees.target_company') }} <span class="text-danger">*</span>
                        </label>
                        <select wire:model.live="transferCompanyId" id="transferCompanyId" class="form-select @error('transferCompanyId') is-invalid @enderror">
                            <option value="">{{ __('employees.select_target_company') }}</option>
                            @foreach(($transferableCompanies ?? []) as $companyOption)
                                <option value="{{ $companyOption->id }}">{{ $companyOption->name }}</option>
                            @endforeach
                        </select>
                        @error('transferCompanyId')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="transferDepartmentId" class="form-label">
                            {{ __('employees.department') }} <small class="text-muted">({{ __('common.optional') }})</small>
                        </label>
                        <select wire:model.live="transferDepartmentId" id="transferDepartmentId" class="form-select @error('transferDepartmentId') is-invalid @enderror" @if(empty($transferCompanyId)) disabled @endif>
                            <option value="">{{ __('employees.select_department') }}</option>
                            @foreach(($transferDepartments ?? []) as $departmentOption)
                                <option value="{{ $departmentOption->id }}">{{ $departmentOption->name }}</option>
                            @endforeach
                        </select>
                        @error('transferDepartmentId')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mb-3">
                        <label for="transferServiceId" class="form-label">
                            {{ __('employees.service') }} <small class="text-muted">({{ __('common.optional') }})</small>
                        </label>
                        <select wire:model.live="transferServiceId" id="transferServiceId" class="form-select @error('transferServiceId') is-invalid @enderror" @if(empty($transferDepartmentId)) disabled @endif>
                            <option value="">{{ __('employees.select_service') }}</option>
                            @foreach(($transferServices ?? []) as $serviceOption)
                                <option value="{{ $serviceOption->id }}">{{ $serviceOption->name }}</option>
                            @endforeach
                        </select>
                        @error('transferServiceId')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-gray-200 text-gray-600" data-bs-dismiss="modal">
                        {{ __('common.cancel') }}
                    </button>
                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                        <i class="fas fa-exchange-alt me-2"></i>{{ __('employees.confirm_transfer') }}
                    </button>
                </div>
            </x-form-items.form>
        </div>
    </div>
</div>
