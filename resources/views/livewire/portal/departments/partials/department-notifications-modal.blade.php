<div wire:ignore.self class="modal fade" id="DepartmentSmsToggleModal" tabindex="-1" role="dialog" aria-labelledby="departmentSmsToggleModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="p-3 p-lg-4">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h1 class="mb-1 h3 fw-bolder">{{ __('departments.manage_notifications_title') }}</h1>
                            <p class="mb-0 text-muted">{{ __('departments.manage_notifications_help') }}</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                    </div>

                    <div class="row g-3 align-items-end">
                        <div class="col-12">
                            <label class="form-label">{{ __('departments.bulk_sms_select_department') }}</label>
                            <select wire:model.live="smsDepartmentActionId" class="form-select">
                                <option value="">{{ __('departments.bulk_sms_select_department') }}</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}">{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h2 class="h6 mb-1">{{ __('common.sms') }}</h2>
                                        <p class="small text-muted mb-0">{{ __('departments.bulk_sms_department_help') }}</p>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <button wire:click="bulkToggleDepartmentSmsNotifications(false)" type="button" class="btn btn-sm btn-danger {{ $smsDepartmentActionId ? '' : 'disabled' }}">
                                        {{ __('departments.bulk_sms_disable_action') }}
                                    </button>
                                    <button wire:click="bulkToggleDepartmentSmsNotifications(true)" type="button" class="btn btn-sm btn-success {{ $smsDepartmentActionId ? '' : 'disabled' }}">
                                        {{ __('departments.bulk_sms_enable_action') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h2 class="h6 mb-1">{{ __('common.email') }}</h2>
                                        <p class="small text-muted mb-0">{{ __('departments.bulk_email_department_help') }}</p>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <button wire:click="bulkToggleDepartmentEmailNotifications(false)" type="button" class="btn btn-sm btn-danger {{ $smsDepartmentActionId ? '' : 'disabled' }}">
                                        {{ __('departments.bulk_email_disable_action') }}
                                    </button>
                                    <button wire:click="bulkToggleDepartmentEmailNotifications(true)" type="button" class="btn btn-sm btn-success {{ $smsDepartmentActionId ? '' : 'disabled' }}">
                                        {{ __('departments.bulk_email_enable_action') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="button" class="btn btn-gray-300 text-white" data-bs-dismiss="modal">{{ __('common.close') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>