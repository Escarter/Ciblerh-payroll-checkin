@if($showCreateModal)
<div class="modal-backdrop fade show" wire:click="closeModals"></div>
<div class="modal side-layout-modal fade show" id="RoleModal" tabindex="-1" aria-labelledby="modal-form" data-bs-backdrop="static" data-bs-keyboard="false" style="display: block;" aria-hidden="false">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width:50%; max-height: 100vh;">
        <div class="modal-content" style="max-height: 100vh; display: flex; flex-direction: column;">
            <div class="modal-header border-0 pb-2 flex-shrink-0">
                <h1 class="mb-0 h4">{{ __('roles.create_role') }}</h1>
                <button type="button" class="btn-close" wire:click="closeModals" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 flex-grow-1" style="overflow-y: auto; min-height: 0;">
                <div class="p-2 p-lg-3">
                    <p class="mb-2 small">{{ __('roles.create_new_role_allocate_permissions') }} &#128522;</p>
                    <form wire:submit.prevent="createRole">
                        <div class="fv-row mb-3 fv-plugins-icon-container">
                            <label class="form-label mb-2 fw-semibold">
                                <span class="required">{{__('roles.role_name')}}</span>
                            </label>
                            <input class="form-control form-control-solid" 
                                   placeholder="{{__('roles.enter_role_name')}}" 
                                   wire:model="roleName">
                            @error('roleName')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="fv-row mb-3 p-3 bg-light rounded-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" wire:model="makeAdmin" id="makeAdmin">
                                <label class="form-check-label fw-semibold text-dark" for="makeAdmin">
                                    {{__('roles.admin_access')}}
                                </label>
                                <small class="d-block text-muted mt-1">{{__('roles.grant_all_permissions')}}</small>
                            </div>
                        </div>

                        <div class="fv-row mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label fw-semibold mb-0">{{__('roles.permissions')}}</label>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-primary btn-sm" wire:click="selectAllPermissions">
                                        {{__('common.select_all')}}
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="clearAllPermissions">
                                        {{__('common.clear_all')}}
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="position-relative">
                                    <input type="text"
                                        wire:model.live.debounce.300ms="permissionSearch"
                                        class="form-control ps-4"
                                        placeholder="{{__('roles.search_permissions') ?? 'Search permissions...'}}"
                                        style="border-radius: 10px; border: 2px solid #e5e7eb; padding: 0.6rem 1rem;">
                                </div>
                            </div>

                            <div class="permissions-container" style="max-height: 400px; overflow-y: auto; border: 2px solid #e5e7eb; border-radius: 12px; background: #f8fafc; padding: 1rem;">
                                @forelse($permissions as $category => $categoryPermissions)
                                <div class="permission-category mb-3" style="border-bottom: 1px solid #e5e7eb; padding-bottom: 1rem;">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-bold text-primary mb-0 d-flex align-items-center">
                                            @php
                                                $categoryLabels = [
                                                    'user' => __('dashboard.user'),
                                                    'company' => __('companies.company'),
                                                    'department' => __('departments.department'),
                                                    'service' => __('services.service'),
                                                    'employee' => __('employees.employee'),
                                                    'payslip' => __('payslips.payslip'),
                                                    'audit_log' => __('audit_logs.audit_log'),
                                                    'role' => __('roles.role'),
                                                    'advance_salary' => __('common.advance_salary'),
                                                    'absence' => __('common.absences'),
                                                    'overtime' => __('common.overtime'),
                                                    'ticking' => __('common.checkins'),
                                                    'leave' => __('common.leave'),
                                                    'leave_type' => __('common.leave_types'),
                                                    'importjob' => __('common.import_jobs'),
                                                    'setting' => __('common.settings'),
                                                    'report' => __('common.reports'),
                                                    'profile' => __('common.profile'),
                                                ];
                                                $categoryLabel = $categoryLabels[$category] ?? ucfirst(str_replace('_', ' ', $category));
                                            @endphp
                                            <div class="category-badge me-2 px-2 py-1 rounded text-white" style="background: #0057A4; color: white; font-size: 0.75rem;">
                                                {{ strtoupper(substr($category, 0, 1)) }}
                                            </div>
                                            {{ $categoryLabel }}
                                            <span class="badge bg-primary bg-opacity-20 text-white ms-2 px-2 py-1" style="font-size: 0.75rem; color: white !important;">{{ count($categoryPermissions) }}</span>
                                        </h6>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="selectCategoryPermissions('{{ $category }}')">
                                                ✓
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="clearCategoryPermissions('{{ $category }}')">
                                                ✕
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        @foreach($categoryPermissions as $permission)
                                        <div class="col-md-6 col-lg-4">
                                            <div class="permission-item p-2 rounded" style="background: white; border: 1px solid #e5e7eb; transition: all 0.2s;">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox"
                                                        wire:model="selectedPermissions"
                                                        value="{{ $permission->name }}"
                                                        id="{{ $permission->name }}_create"
                                                        style="border-radius: 6px; width: 1.1rem; height: 1.1rem;">
                                                    <label class="form-check-label fw-medium text-dark" for="{{ $permission->name }}_create" style="font-size: 0.85rem; margin-left: 0.5rem;">
                                                        {{ $this->getPermissionLabel($permission->name) }}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @empty
                                <div class="text-center py-4">
                                    <p class="text-muted mb-0">{{__('roles.no_permissions_found') ?? 'No permissions found'}}</p>
                                </div>
                                @endforelse
                            </div>

                            <div class="mt-3 p-3 bg-light rounded-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold text-dark">{{__('roles.selected_permissions') ?? 'Selected Permissions'}}</span>
                                    <span class="badge bg-primary px-3 py-2 text-white" style="font-size: 0.9rem;">{{ count($selectedPermissions ?? []) }}</span>
                                </div>
                                @if(count($selectedPermissions ?? []) > 0)
                                <div class="mt-2">
                                    @foreach($selectedPermissions as $permission)
                                    <span class="badge badge-lg bg-success bg-opacity-20 text-white me-1 mb-1 px-2 py-1" style="font-size: 0.75rem;">{{ $this->getPermissionLabel($permission) }}</span>
                                    @endforeach
                                </div>
                                @else
                                <small class="text-muted">{{__('roles.no_permissions_selected') ?? 'No permissions selected'}}</small>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer border-0 pt-2 pb-3 px-3 flex-shrink-0">
                <button type="button" wire:click="closeModals" class="btn btn-gray-200 text-gray-600 btn-sm">{{ __('common.close') }}</button>
                <button type="button" wire:click="createRole" class="btn btn-primary btn-sm" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="createRole">{{ __('common.save') }}</span>
                    <span wire:loading wire:target="createRole">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        {{ __('common.saving') }}...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@if($showEditModal)
<div class="modal-backdrop fade show" wire:click="closeModals"></div>
<div class="modal side-layout-modal fade show" id="RoleModal" tabindex="-1" aria-labelledby="modal-form" data-bs-backdrop="static" data-bs-keyboard="false" style="display: block;" aria-hidden="false">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width:50%; max-height: 100vh;">
        <div class="modal-content" style="max-height: 100vh; display: flex; flex-direction: column;">
            <div class="modal-header border-0 pb-2 flex-shrink-0">
                <h1 class="mb-0 h4">{{ __('roles.edit_role') }}</h1>
                <button type="button" class="btn-close" wire:click="closeModals" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 flex-grow-1" style="overflow-y: auto; min-height: 0;">
                <div class="p-2 p-lg-3">
                    <p class="mb-2 small">{{ __('roles.update_role_modify_permissions') }} &#128522;</p>
                    @if($editingRole)
                    <div class="fv-row mb-3 p-3 bg-info bg-opacity-10 border border-info border-opacity-25 rounded-3">
                        <div class="d-flex align-items-center">
                            <div class="me-3 p-2 bg-info bg-opacity-20 rounded-circle">
                                <svg width="16" height="16" fill="currentColor" class="text-white" viewBox="0 0 24 24">
                                    <path d="M13,9H11V7H13M13,17H11V11H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z" />
                                </svg>
                            </div>
                            <div>
                                <div class="fw-semibold text-white">{{ __('roles.currently_editing') }}: {{ $editingRole->name }}</div>
                                <small class="text-muted text-white">{{ $editingRole->permissions->count() }} {{ __('roles.permissions_currently_assigned') }}</small>
                            </div>
                        </div>
                    </div>
                    @endif
                    <form wire:submit.prevent="updateRole">
                        <div class="fv-row mb-3 fv-plugins-icon-container">
                            <label class="form-label mb-2 fw-semibold">
                                <span class="required">{{__('roles.role_name')}}</span>
                            </label>
                            <input class="form-control form-control-solid" 
                                   placeholder="{{__('roles.enter_role_name')}}" 
                                   wire:model="roleName">
                            @error('roleName')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="fv-row mb-3 p-3 bg-light rounded-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" wire:model="makeAdmin" id="makeAdminEdit">
                                <label class="form-check-label fw-semibold text-dark" for="makeAdminEdit">
                                    {{__('roles.admin_access')}}
                                </label>
                                <small class="d-block text-muted mt-1">{{__('roles.grant_all_permissions')}}</small>
                            </div>
                        </div>

                        <div class="fv-row mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label class="form-label fw-semibold mb-0">{{__('roles.permissions')}}</label>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-primary btn-sm" wire:click="selectAllPermissions">
                                        {{__('common.select_all')}}
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="clearAllPermissions">
                                        {{__('common.clear_all')}}
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="position-relative">
                                    <input type="text"
                                        wire:model.live.debounce.300ms="permissionSearch"
                                        class="form-control ps-4"
                                        placeholder="{{__('roles.search_permissions') ?? 'Search permissions...'}}"
                                        style="border-radius: 10px; border: 2px solid #e5e7eb; padding: 0.6rem 1rem;">
                                </div>
                            </div>

                            <div class="permissions-container" style="max-height: 400px; overflow-y: auto; border: 2px solid #e5e7eb; border-radius: 12px; background: #f8fafc; padding: 1rem;">
                                @forelse($permissions as $category => $categoryPermissions)
                                <div class="permission-category mb-3" style="border-bottom: 1px solid #e5e7eb; padding-bottom: 1rem;">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="fw-bold text-primary mb-0 d-flex align-items-center">
                                            @php
                                                $categoryLabels = [
                                                    'user' => __('dashboard.user'),
                                                    'company' => __('companies.company'),
                                                    'department' => __('departments.department'),
                                                    'service' => __('services.service'),
                                                    'employee' => __('employees.employee'),
                                                    'payslip' => __('payslips.payslip'),
                                                    'audit_log' => __('audit_logs.audit_log'),
                                                    'role' => __('roles.role'),
                                                    'advance_salary' => __('common.advance_salary'),
                                                    'absence' => __('common.absences'),
                                                    'overtime' => __('common.overtime'),
                                                    'ticking' => __('common.checkins'),
                                                    'leave' => __('common.leave'),
                                                    'leave_type' => __('common.leave_types'),
                                                    'importjob' => __('common.import_jobs'),
                                                    'setting' => __('common.settings'),
                                                    'report' => __('common.reports'),
                                                    'profile' => __('common.profile'),
                                                ];
                                                $categoryLabel = $categoryLabels[$category] ?? ucfirst(str_replace('_', ' ', $category));
                                            @endphp
                                            <div class="category-badge me-2 px-2 py-1 rounded text-white" style="background: #0057A4; color: white; font-size: 0.75rem;">
                                                {{ strtoupper(substr($category, 0, 1)) }}
                                            </div>
                                            {{ $categoryLabel }}
                                            <span class="badge bg-primary bg-opacity-20 text-white ms-2 px-2 py-1" style="font-size: 0.75rem; color: white !important;">{{ count($categoryPermissions) }}</span>
                                        </h6>
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="selectCategoryPermissions('{{ $category }}')">
                                                ✓
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="clearCategoryPermissions('{{ $category }}')">
                                                ✕
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        @foreach($categoryPermissions as $permission)
                                        <div class="col-md-6 col-lg-4">
                                            <div class="permission-item p-2 rounded" style="background: white; border: 1px solid #e5e7eb; transition: all 0.2s;">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox"
                                                        wire:model="selectedPermissions"
                                                        value="{{ $permission->name }}"
                                                        id="{{ $permission->name }}_edit"
                                                        style="border-radius: 6px; width: 1.1rem; height: 1.1rem;">
                                                    <label class="form-check-label fw-medium text-dark" for="{{ $permission->name }}_edit" style="font-size: 0.85rem; margin-left: 0.5rem;">
                                                        {{ $this->getPermissionLabel($permission->name) }}
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @empty
                                <div class="text-center py-4">
                                    <p class="text-muted mb-0">{{__('roles.no_permissions_found') ?? 'No permissions found'}}</p>
                                </div>
                                @endforelse
                            </div>

                            <div class="mt-3 p-3 bg-light rounded-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-semibold text-dark">{{__('roles.selected_permissions') ?? 'Selected Permissions'}}</span>
                                    <span class="badge bg-primary px-3 py-2 text-white" style="font-size: 0.9rem;">{{ count($selectedPermissions ?? []) }}</span>
                                </div>
                                @if(count($selectedPermissions ?? []) > 0)
                                <div class="mt-2">
                                    @foreach($selectedPermissions as $permission)
                                    <span class="badge badge-lg bg-success bg-opacity-20 text-white me-1 mb-1 px-2 py-1" style="font-size: 0.75rem;">{{ $this->getPermissionLabel($permission) }}</span>
                                    @endforeach
                                </div>
                                @else
                                <small class="text-muted">{{__('roles.no_permissions_selected') ?? 'No permissions selected'}}</small>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="modal-footer border-0 pt-2 pb-3 px-3 flex-shrink-0">
                <button type="button" wire:click="closeModals" class="btn btn-gray-200 text-gray-600 btn-sm">{{ __('common.close') }}</button>
                <button type="button" wire:click="updateRole" class="btn btn-primary btn-sm" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="updateRole">{{ __('common.update') }}</span>
                    <span wire:loading wire:target="updateRole">
                        <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                        {{ __('common.updating') }}...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
@endif
