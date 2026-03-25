<div wire:ignore.self class="modal side-layout-modal fade" id="ServiceSmsToggleModal" tabindex="-1" role="dialog" aria-labelledby="serviceSmsToggleModal" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="p-3 p-lg-4">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h1 class="mb-1 h3 fw-bolder">{{ __('services.manage_notifications_title') }}</h1>
                            <p class="mb-0 text-muted">{{ __('services.manage_notifications_help') }}</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                    </div>

                    <div class="row g-3 align-items-end">
                        <div class="col-12">
                            <label class="form-label">{{ __('services.bulk_sms_select_service') }}</label>
                            <select wire:model.live="smsServiceActionId" class="form-select">
                                <option value="">{{ __('services.bulk_sms_select_service') }}</option>
                                @foreach($services as $service)
                                    <option value="{{ $service->id }}">{{ $service->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h2 class="h6 mb-1">{{ __('common.sms') }}</h2>
                                        <p class="small text-muted mb-0">{{ __('services.bulk_sms_service_help') }}</p>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <button wire:click="bulkToggleServiceSmsNotifications(false)" type="button" class="btn btn-sm btn-danger {{ $smsServiceActionId ? '' : 'disabled' }}">
                                        {{ __('services.bulk_sms_disable_action') }}
                                    </button>
                                    <button wire:click="bulkToggleServiceSmsNotifications(true)" type="button" class="btn btn-sm btn-success {{ $smsServiceActionId ? '' : 'disabled' }}">
                                        {{ __('services.bulk_sms_enable_action') }}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-lg-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h2 class="h6 mb-1">{{ __('common.email') }}</h2>
                                        <p class="small text-muted mb-0">{{ __('services.bulk_email_service_help') }}</p>
                                    </div>
                                </div>
                                <div class="d-flex gap-2 flex-wrap">
                                    <button wire:click="bulkToggleServiceEmailNotifications(false)" type="button" class="btn btn-sm btn-danger {{ $smsServiceActionId ? '' : 'disabled' }}">
                                        {{ __('services.bulk_email_disable_action') }}
                                    </button>
                                    <button wire:click="bulkToggleServiceEmailNotifications(true)" type="button" class="btn btn-sm btn-success {{ $smsServiceActionId ? '' : 'disabled' }}">
                                        {{ __('services.bulk_email_enable_action') }}
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