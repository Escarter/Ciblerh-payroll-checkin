<!-- Email Provider Guide Modal -->
<div class="modal side-layout-modal fade" id="emailProviderGuideModal" tabindex="-1" aria-labelledby="emailProviderGuideLabel" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 750px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emailProviderGuideLabel">
                    <i class="fas fa-envelope text-success me-2"></i>{{__('settings.email_providers_guide')}}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-4">
                    <h6 class="text-success mb-3">{{__('settings.available_email_providers')}}:</h6>

                    <!-- SMTP Provider -->
                    <div class="card border-secondary mb-3">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0 me-3">
                                    <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas fa-server fa-sm"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="card-title mb-1">{{__('SMTP')}}</h6>
                                    <p class="card-text small text-muted mb-2">{{__('settings.smtp_description')}}</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-secondary">{{__('settings.no')}} {{__('settings.webhook_support')}}</span>
                                        <small class="text-muted">{{__('settings.best_for')}}: {{__('settings.smtp_best_for')}}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Mailgun Provider -->
                    <div class="card border-success mb-3">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0 me-3">
                                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas fa-rocket fa-sm"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="card-title mb-1">{{__('Mailgun')}} <small class="text-success">{{__('settings.webhook_support')}} ✓</small></h6>
                                    <p class="card-text small text-muted mb-2">{{__('settings.mailgun_description')}}</p>
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <small class="text-muted d-block">{{__('settings.pricing')}}</small>
                                            <strong class="text-success">{{__('settings.mailgun_pricing')}}</strong>
                                        </div>
                                        <div class="col-6">
                                            <a href="https://documentation.mailgun.com/" target="_blank" class="btn btn-sm btn-outline-success">View Docs</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Amazon SES Provider -->
                    <div class="card border-success mb-3">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0 me-3">
                                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fab fa-aws fa-sm"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="card-title mb-1">{{__('Amazon SES')}} <small class="text-success">{{__('settings.webhook_support')}} ✓</small></h6>
                                    <p class="card-text small text-muted mb-2">{{__('settings.ses_description')}}</p>
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <small class="text-muted d-block">{{__('settings.pricing')}}</small>
                                            <strong class="text-success">{{__('settings.ses_pricing')}}</strong>
                                        </div>
                                        <div class="col-6">
                                            <a href="https://docs.aws.amazon.com/ses/" target="_blank" class="btn btn-sm btn-outline-success">View Docs</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Postmark Provider -->
                    <div class="card border-success mb-3">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0 me-3">
                                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas fa-shield-alt fa-sm"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="card-title mb-1">{{__('Postmark')}} <small class="text-success">{{__('settings.webhook_support')}} ✓</small></h6>
                                    <p class="card-text small text-muted mb-2">{{__('settings.postmark_description')}}</p>
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <small class="text-muted d-block">{{__('settings.pricing')}}</small>
                                            <strong class="text-success">{{__('settings.postmark_pricing')}}</strong>
                                        </div>
                                        <div class="col-6">
                                            <a href="https://postmarkapp.com/developer" target="_blank" class="btn btn-sm btn-outline-success">View Docs</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sendmail Provider -->
                    <div class="card border-warning mb-3">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start">
                                <div class="flex-shrink-0 me-3">
                                    <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fas fa-hdd fa-sm"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="card-title mb-1">{{__('Sendmail')}}</h6>
                                    <p class="card-text small text-muted mb-2">{{__('settings.sendmail_description')}}</p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-secondary">{{__('settings.no')}} {{__('settings.webhook_support')}}</span>
                                        <small class="text-muted">{{__('settings.best_for')}}: {{__('settings.sendmail_best_for')}}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Getting Credentials Section -->
                <div class="border-top pt-3">
                    <h6 class="text-success mb-3">{{__('settings.how_to_get_email_credentials')}}:</h6>
                    <div class="accordion" id="emailCredentialsAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#smtpCreds" aria-expanded="false">
                                    {{__('SMTP')}} {{__('settings.credentials')}}
                                </button>
                            </h2>
                            <div id="smtpCreds" class="accordion-collapse collapse" data-bs-parent="#emailCredentialsAccordion">
                                <div class="accordion-body">
                                    <ol class="small">
                                        <li>{{__('settings.smtp_step_1')}}</li>
                                        <li>{{__('settings.smtp_step_2')}}</li>
                                        <li>{{__('settings.smtp_step_3')}}</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#mailgunCreds" aria-expanded="false">
                                    {{__('Mailgun')}} {{__('settings.credentials')}}
                                </button>
                            </h2>
                            <div id="mailgunCreds" class="accordion-collapse collapse" data-bs-parent="#emailCredentialsAccordion">
                                <div class="accordion-body">
                                    <ol class="small">
                                        <li>{{__('settings.mailgun_step_1')}}</li>
                                        <li>{{__('settings.mailgun_step_2')}}</li>
                                        <li>{{__('settings.mailgun_step_3')}}</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sesCreds" aria-expanded="false">
                                    {{__('Amazon SES')}} {{__('settings.credentials')}}
                                </button>
                            </h2>
                            <div id="sesCreds" class="accordion-collapse collapse" data-bs-parent="#emailCredentialsAccordion">
                                <div class="accordion-body">
                                    <ol class="small">
                                        <li>{{__('settings.ses_step_1')}}</li>
                                        <li>{{__('settings.ses_step_2')}}</li>
                                        <li>{{__('settings.ses_step_3')}}</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#postmarkCreds" aria-expanded="false">
                                    {{__('Postmark')}} {{__('settings.credentials')}}
                                </button>
                            </h2>
                            <div id="postmarkCreds" class="accordion-collapse collapse" data-bs-parent="#emailCredentialsAccordion">
                                <div class="accordion-body">
                                    <ol class="small">
                                        <li>{{__('settings.postmark_step_1')}}</li>
                                        <li>{{__('settings.postmark_step_2')}}</li>
                                        <li>{{__('settings.postmark_step_3')}}</li>
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