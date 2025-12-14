<!-- SMS Provider Guide Modal -->
<div class="modal side-layout-modal fade" id="smsProviderGuideModal" tabindex="-1" aria-labelledby="smsProviderGuideLabel" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 750px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="smsProviderGuideLabel">
                    <i class="fas fa-mobile-alt text-primary me-2"></i>{{__('settings.sms_providers_guide')}}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" >
                <div class="mb-4">
                    <h6 class="text-primary mb-3">{{__('settings.available_sms_providers')}}:</h6>

                    <!-- NEXAH Provider -->
                    <div class="card border-primary mb-3">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0 me-3">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas fa-star fa-sm"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="card-title mb-1">{{__('NEXAH')}} <small class="text-primary">{{__('settings.recommended')}}</small></h6>
                                    <p class="card-text small text-muted mb-2">{{__('settings.nexah_description')}}</p>
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <small class="text-muted d-block">{{__('settings.pricing')}}</small>
                                            <strong class="text-success">{{__('settings.nexah_pricing')}}</strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">{{__('settings.support')}}</small>
                                            <strong class="text-info">{{__('settings.nexah_support')}}</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Twilio Provider -->
                    <div class="card border-info mb-3">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0 me-3">
                                    <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas fa-globe fa-sm"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="card-title mb-1">{{__('Twilio')}}</h6>
                                    <p class="card-text small text-muted mb-2">{{__('settings.twilio_description')}}</p>
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <small class="text-muted d-block">{{__('settings.pricing')}}</small>
                                            <strong class="text-warning">{{__('settings.twilio_pricing')}}</strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">{{__('settings.documentation')}}</small>
                                            <a href="https://www.twilio.com/docs/sms" target="_blank" class="btn btn-sm btn-outline-primary">View Docs</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- AWS SNS Provider -->
                    <div class="card border-warning mb-3">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0 me-3">
                                    <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas fa-cloud fa-sm"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="card-title mb-1">{{__('AWS SNS')}}</h6>
                                    <p class="card-text small text-muted mb-2">{{__('settings.aws_sns_description')}}</p>
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <small class="text-muted d-block">{{__('settings.pricing')}}</small>
                                            <strong class="text-warning">{{__('settings.aws_sns_pricing')}}</strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">{{__('settings.documentation')}}</small>
                                            <a href="https://docs.aws.amazon.com/sns/" target="_blank" class="btn btn-sm btn-outline-primary">View Docs</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Getting Credentials Section -->
                <div class="border-top pt-3">
                    <h6 class="text-primary mb-3">{{__('settings.how_to_get_sms_credentials')}}:</h6>
                    <div class="accordion" id="smsCredentialsAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#nexahCreds" aria-expanded="false">
                                    {{__('NEXAH')}} {{__('settings.credentials')}}
                                </button>
                            </h2>
                            <div id="nexahCreds" class="accordion-collapse collapse" data-bs-parent="#smsCredentialsAccordion">
                                <div class="accordion-body">
                                    <ol class="small">
                                        <li>{{__('settings.nexah_step_1')}}</li>
                                        <li>{{__('settings.nexah_step_2')}}</li>
                                        <li>{{__('settings.nexah_step_3')}}</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#twilioCreds" aria-expanded="false">
                                    {{__('Twilio')}} {{__('settings.credentials')}}
                                </button>
                            </h2>
                            <div id="twilioCreds" class="accordion-collapse collapse" data-bs-parent="#smsCredentialsAccordion">
                                <div class="accordion-body">
                                    <ol class="small">
                                        <li>{{__('settings.twilio_step_1')}}</li>
                                        <li>{{__('settings.twilio_step_2')}}</li>
                                        <li>{{__('settings.twilio_step_3')}}</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#awsCreds" aria-expanded="false">
                                    {{__('AWS SNS')}} {{__('settings.credentials')}}
                                </button>
                            </h2>
                            <div id="awsCreds" class="accordion-collapse collapse" data-bs-parent="#smsCredentialsAccordion">
                                <div class="accordion-body">
                                    <ol class="small">
                                        <li>{{__('settings.aws_sns_step_1')}}</li>
                                        <li>{{__('settings.aws_sns_step_2')}}</li>
                                        <li>{{__('settings.aws_sns_step_3')}}</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{__('common.close')}}</button>
            </div>
        </div>
    </div>
</div>