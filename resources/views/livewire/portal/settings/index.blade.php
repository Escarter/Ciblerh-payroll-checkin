<div>
    <x-delete-modal />
    <x-alert />
    <div class='pb-0 mt-4'>
        <div class="row">
            <div class="col-lg-7">
                <div class="mb-5 card shadow-md card-raised" style="min-height: 600px;">
                    <div class="py-4 px-5 card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="card-title h5 mb-0">{{__('settings.sms_package_configuration')}}</div>
                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#smsProviderGuideModal" title="{{__('settings.sms_providers_guide')}}">
                                <i class="fas fa-mobile-alt"></i> <span class="d-none d-md-inline">{{__('settings.guide')}}</span>
                            </button>
                        </div>

                        <x-form-items.form wire:submit="saveSmsConfig">
                            <div class='form-group row mb-4'>
                                <div class='col'>
                                    <label for="sms_provider">{{ __('settings.sms_provider') }}</label>
                                    <select wire:model.live='sms_provider' id="sms_provider" class="form-control w-100 @error('sms_provider') is-invalid @enderror">
                                        <option value='nexah' selected>{{__('NEXAH')}}</option>
                                        <option value='twilio'>{{__('Twilio')}}</option>
                                        <option value='aws_sns'>{{__('AWS SNS')}}</option>
                                    </select>
                                </div>
                                <!-- Form Group (default email)-->
                                <div class="col">
                                    <label for="sms_provider_username">{{ __('settings.username_or_token') }}</label>
                                    <input wire:model="sms_provider_username" id="sms_provider_username" type="text" class="form-control w-100 @error('sms_provider_username') is-invalid @enderror" required autofocus>
                                </div>
                            </div>
                            <div class='form-group row mb-4'>
                                <div class="col">
                                    <label for="sms_provider_password">{{ __('settings.password_or_secret') }}</label>
                                    <input wire:model="sms_provider_password" id="sms_provider_password" type="text" class="form-control w-100 @error('sms_provider_password') is-invalid @enderror" required autofocus>
                                </div>
                                <div class="col">
                                    <label for="sms_provider_senderid">{{ __('settings.senderid') }}</label>
                                    <input wire:model="sms_provider_senderid" id="sms_provider_senderid" type="text" class="form-control w-100 @error('sms_provider_senderid') is-invalid @enderror" required autofocus>
                                </div>
                            </div>

                            <div class="card-title h5 pt-3 ">{{__('settings.payslip_sms_message_config')}} </div>
                            <hr>
                            <div class="form-group mb-2">
                                <label for="sms_content_en">{{ __('settings.enter_sms_content_english') }}</label>
                                <textarea wire:model="sms_content_en" id="sms_content_en" type="text" rows="3" class="form-control w-100 @error('sms_content_en') is-invalid @enderror" required autofocus></textarea>
                            </div>
                            <div class="form-group mb-2">
                                <label for="sms_content_fr">{{ __('settings.enter_sms_content_french') }}</label>
                                <textarea wire:model="sms_content_fr" id="sms_content_fr" type="text" rows="3" class="form-control w-100 @error('sms_content_fr') is-invalid @enderror" required autofocus></textarea>
                            </div>
                            <div class='text-xs text-danger ' style="font-size:small">
                                {{__('settings.do_not_remove_placeholders')}} <strong class="fw-bolder"> :name: , :month: , :year: , :pdf_password: </strong>,{{__('settings.placeholders_note')}}
                            </div>

                            <div class="card-title h5 pt-3">{{__('settings.birthday_sms_message_config')}} </div>
                            <hr>
                            <div class="form-group mb-2">
                                <label for="birthday_sms_message_en">{{ __('settings.enter_birthday_sms_english') }}</label>
                                <textarea wire:model="birthday_sms_message_en" id="birthday_sms_message_en" type="text" rows="3" class="form-control w-100 @error('birthday_sms_message_en') is-invalid @enderror" required autofocus></textarea>
                            </div>
                            <div class="form-group mb-2">
                                <label for="birthday_sms_message_fr">{{ __('settings.enter_birthday_sms_french') }}</label>
                                <textarea wire:model="birthday_sms_message_fr" id="birthday_sms_message_fr" type="text" rows="3" class="form-control w-100 @error('birthday_sms_message_fr') is-invalid @enderror" required autofocus></textarea>
                            </div>
                            <div class='text-xs text-danger ' style="font-size:small">
                                {{__('settings.do_not_remove_placeholders')}} <strong class="fw-bolder"> :name: </strong>,{{__('settings.placeholders_note')}}
                            </div>

                            <div class="mt-4 mb-0 form-group d-flex justify-content-end">
                                <button type="submit" id="save-sms-config-btn" wire:click.prevent="saveSmsConfig" class="btn btn-primary" wire:loading.attr="disabled">
                                    {{ __('settings.save_sms_config') }}
                                </button>
                            </div>

                        </x-form-items.form>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <!-- Notifications preferences card-->
                <div class="mb-5 d-flex flex-column" style="min-height: 600px;">
                    <div class="px-3 mb-3 flex-fill d-flex">
                        <div class='card card-body card-raised text-center pt-5 w-100'>
                            <p class='display-3'>
                                {{ $sms_balance }}
                            </p>
                            <div class="card-title">{{__('settings.sms_balance')}}</div>
                            <div class="mb-2 text-muted text-xs">{{__('settings.you_can_check_sms_details')}}.</div>
                        </div>
                    </div>
                    <div class="px-3 flex-fill d-flex">
                        <div class="card card-body mt-3 w-100">
                            <div class="h5 text-center pt-4">{{__('settings.test_sms_configuration')}}</div>
                            <div class="mb-4 text-muted text-xs text-center">{{__('settings.verify_sms_setup')}}.</div>
                            <x-form-items.form wire:submit="sendTestSms">

                                <div class='form-group row mb-3'>
                                    <div class='col'>
                                        <label for="test_phone_number">{{ __('settings.enter_phone_number') }}</label>
                                        <input wire:model="test_phone_number" id="test_phone_number" type="text" class="form-control w-100 @error('test_phone_number') is-invalid @enderror" required autofocus>
                                    </div>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="test_sms_message">{{ __('settings.test_sms_message') }}</label>
                                    <textarea wire:model="test_sms_message" id="test_sms_message" type="text" rows="4" class="form-control w-100 @error('test_sms_message') is-invalid @enderror" required autofocus></textarea>
                                </div>
                                <div class="mt-4 mb-0 form-group d-flex justify-content-end">
                                    <button type="submit" id="send-test-sms-btn" wire:click.prevent="sendTestSms" class="btn btn-primary" wire:loading.attr="disabled">
                                        {{ __('settings.send_test_sms') }}
                                    </button>
                                </div>
                            </x-form-items.form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-n3 pt-0">
                <div class='row'>
                    <div class='col-md-7'>
                        <div class="mb-5 card card-raised" style="min-height: 600px;">
                            <div class="py-4 px-5 card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="card-title h5 mb-0">{{__('settings.email_configuration')}}</div>
                                    <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#emailProviderGuideModal" title="{{__('settings.email_providers_guide')}}">
                                        <i class="fas fa-envelope"></i> <span class="d-none d-md-inline">{{__('settings.guide')}}</span>
                                    </button>
                                </div>
                                <x-form-items.form wire:submit="saveSmtpConfig">
                                    <div class='form-group mb-2'>
                                        <label for='smtp_provider'>{{__('settings.email_provider')}}</label>
                                        <select wire:model.live='smtp_provider' id='smtp_provider' class="form-control @error('smtp_provider') is-invalid @enderror">
                                            <option value='smtp'>{{__('SMTP')}}</option>
                                            <option value='mailgun'>{{__('Mailgun')}}</option>
                                            <option value='ses'>{{__('Amazon SES')}}</option>
                                            <option value='postmark'>{{__('Postmark')}}</option>
                                            <option value='sendmail'>{{__('Sendmail')}}</option>
                                            <option value='mailpit'>{{__('Mailpit (Development)')}}</option>
                                            <option value='log'>{{__('Log (Development)')}}</option>
                                            <option value='array'>{{__('Array (Testing)')}}</option>
                                        </select>
                                    </div>

                                    <!-- SMTP Configuration (only for SMTP provider) -->
                                    <div x-show="$wire.smtp_provider === 'smtp'" class="provider-config">
                                        <hr>
                                        <h6 class="text-primary mb-3">{{__('settings.smtp_configuration')}}</h6>
                                        <div class='form-group row mb-2'>
                                            <div class="col">
                                                <label for="smtp_host">{{ __('settings.smtp_host') }}</label>
                                                <input wire:model="smtp_host" id="smtp_host" type="text" class="form-control w-100 @error('smtp_host') is-invalid @enderror" x-bind:required="$wire.smtp_provider === 'smtp'">
                                            </div>
                                            <div class="col">
                                                <label for="smtp_port">{{ __('settings.smtp_port') }}</label>
                                                <input wire:model="smtp_port" id="smtp_port" type="text" class="form-control w-100 @error('smtp_port') is-invalid @enderror" x-bind:required="$wire.smtp_provider === 'smtp'">
                                            </div>
                                        </div>
                                        <div class='form-group row mb-2'>
                                            <div class="col">
                                                <label for="smtp_username">{{ __('settings.smtp_username') }}</label>
                                                <input wire:model="smtp_username" id="smtp_username" type="text" class="form-control w-100 @error('smtp_username') is-invalid @enderror" x-bind:required="$wire.smtp_provider === 'smtp'">
                                            </div>
                                            <div class="col">
                                                <label for="smtp_password">{{ __('settings.smtp_password') }}</label>
                                                <input wire:model="smtp_password" id="smtp_password" type="password" class="form-control w-100 @error('smtp_password') is-invalid @enderror" x-bind:required="$wire.smtp_provider === 'smtp'">
                                            </div>
                                        </div>
                                        <div class="form-group mb-2">
                                            <label for="smtp_encryption">{{ __('settings.smtp_encryption') }}</label>
                                            <input wire:model="smtp_encryption" id="smtp_encryption" type="text" class="form-control w-100 @error('smtp_encryption') is-invalid @enderror" x-bind:required="$wire.smtp_provider === 'smtp'" placeholder="tls/ssl">
                                        </div>
                                    </div>

                                    <!-- Mailgun Configuration -->
                                    <div x-show="$wire.smtp_provider === 'mailgun'" class="provider-config">
                                        <hr>
                                        <h6 class="text-primary mb-3">{{__('Mailgun Configuration')}}</h6>
                                        <div class='form-group row mb-2'>
                                            <div class="col">
                                                <label for="mailgun_domain">{{ __('Domain') }}</label>
                                                <input wire:model="mailgun_domain" id="mailgun_domain" type="text" class="form-control w-100 @error('mailgun_domain') is-invalid @enderror" placeholder="yourdomain.com">
                                            </div>
                                            <div class="col">
                                                <label for="mailgun_secret">{{ __('API Secret') }}</label>
                                                <input wire:model="mailgun_secret" id="mailgun_secret" type="password" class="form-control w-100 @error('mailgun_secret') is-invalid @enderror" placeholder="key-...">
                                            </div>
                                        </div>
                                        <div class='form-group row mb-2'>
                                            <div class="col">
                                                <label for="mailgun_endpoint">{{ __('Endpoint (Optional)') }}</label>
                                                <input wire:model="mailgun_endpoint" id="mailgun_endpoint" type="text" class="form-control w-100 @error('mailgun_endpoint') is-invalid @enderror" placeholder="api.mailgun.net">
                                            </div>
                                            <div class="col">
                                                <label for="mailgun_scheme">{{ __('Scheme (Optional)') }}</label>
                                                <input wire:model="mailgun_scheme" id="mailgun_scheme" type="text" class="form-control w-100 @error('mailgun_scheme') is-invalid @enderror" placeholder="https">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- AWS SES Configuration -->
                                    <div x-show="$wire.smtp_provider === 'ses'" class="provider-config">
                                        <hr>
                                        <h6 class="text-primary mb-3">{{__('Amazon SES Configuration')}}</h6>
                                        <div class='form-group row mb-2'>
                                            <div class="col">
                                                <label for="ses_key">{{ __('Access Key ID') }}</label>
                                                <input wire:model="ses_key" id="ses_key" type="text" class="form-control w-100 @error('ses_key') is-invalid @enderror" placeholder="AKIA...">
                                            </div>
                                            <div class="col">
                                                <label for="ses_secret">{{ __('Secret Access Key') }}</label>
                                                <input wire:model="ses_secret" id="ses_secret" type="password" class="form-control w-100 @error('ses_secret') is-invalid @enderror" placeholder="...">
                                            </div>
                                        </div>
                                        <div class="form-group mb-2">
                                            <label for="ses_region">{{ __('Region') }}</label>
                                            <select wire:model="ses_region" id="ses_region" class="form-control w-100 @error('ses_region') is-invalid @enderror">
                                                <option value="us-east-1">US East (N. Virginia)</option>
                                                <option value="us-east-2">US East (Ohio)</option>
                                                <option value="us-west-1">US West (N. California)</option>
                                                <option value="us-west-2">US West (Oregon)</option>
                                                <option value="eu-west-1">EU (Ireland)</option>
                                                <option value="eu-west-2">EU (London)</option>
                                                <option value="eu-central-1">EU (Frankfurt)</option>
                                                <option value="ap-southeast-1">Asia Pacific (Singapore)</option>
                                                <option value="ap-southeast-2">Asia Pacific (Sydney)</option>
                                                <option value="ap-northeast-1">Asia Pacific (Tokyo)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Postmark Configuration -->
                                    <div x-show="$wire.smtp_provider === 'postmark'" class="provider-config">
                                        <hr>
                                        <h6 class="text-primary mb-3">{{__('Postmark Configuration')}}</h6>
                                        <div class="form-group mb-2">
                                            <label for="postmark_token">{{ __('Server Token') }}</label>
                                            <input wire:model="postmark_token" id="postmark_token" type="password" class="form-control w-100 @error('postmark_token') is-invalid @enderror" placeholder="server-token-...">
                                        </div>
                                    </div>

                                    <!-- Sendmail Configuration -->
                                    <div x-show="$wire.smtp_provider === 'sendmail'" class="provider-config">
                                        <hr>
                                        <h6 class="text-primary mb-3">{{__('Sendmail Configuration')}}</h6>
                                        <div class="form-group mb-2">
                                            <label for="sendmail_path">{{ __('Sendmail Path (Optional)') }}</label>
                                            <input wire:model="sendmail_path" id="sendmail_path" type="text" class="form-control w-100 @error('sendmail_path') is-invalid @enderror" placeholder="/usr/sbin/sendmail -bs -i">
                                        </div>
                                    </div>

                                    <!-- Mailpit Configuration -->
                                    <div x-show="$wire.smtp_provider === 'mailpit'" class="provider-config">
                                        <hr>
                                        <h6 class="text-primary mb-3">{{__('Mailpit Configuration (Development)')}}</h6>
                                        <div class='form-group row mb-2'>
                                            <div class="col">
                                                <label for="mailpit_host">{{ __('Host') }}</label>
                                                <input wire:model="mailpit_host" id="mailpit_host" type="text" class="form-control w-100 @error('mailpit_host') is-invalid @enderror" placeholder="localhost">
                                            </div>
                                            <div class="col">
                                                <label for="mailpit_port">{{ __('Port') }}</label>
                                                <input wire:model="mailpit_port" id="mailpit_port" type="text" class="form-control w-100 @error('mailpit_port') is-invalid @enderror" placeholder="1025">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Log Configuration -->
                                    <div x-show="$wire.smtp_provider === 'log'" class="provider-config">
                                        <hr>
                                        <h6 class="text-primary mb-3">{{__('Log Configuration (Development)')}}</h6>
                                        <div class="form-group mb-2">
                                            <label for="log_channel">{{ __('Log Channel (Optional)') }}</label>
                                            <input wire:model="log_channel" id="log_channel" type="text" class="form-control w-100 @error('log_channel') is-invalid @enderror" placeholder="daily">
                                        </div>
                                    </div>
                                    <div class='form-group row mb-2'>
                                        <div class="col">
                                            <label for="from_email">{{ __('settings.from_email') }}</label>
                                            <input wire:model="from_email" id="from_email" type="text" class="form-control w-100 @error('from_email') is-invalid @enderror" required autofocus>
                                        </div>
                                        <div class="col">
                                            <label for="from_name">{{ __('settings.smtp_from_name') }}</label>
                                            <input wire:model="from_name" id="from_name" type="text" class="form-control w-100 @error('from_name') is-invalid @enderror" required autofocus>
                                        </div>
                                    </div>

                                    <!-- Provider-specific alerts -->
                                    <div x-show="$wire.smtp_provider === 'smtp'" class="mt-3">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i>
                                            <strong>{{__('settings.smtp_limitations')}}:</strong>
                                            {{__('settings.smtp_webhook_note')}}
                                        </div>
                                    </div>

                                    <div x-show="['mailgun', 'ses', 'postmark'].includes($wire.smtp_provider)" class="mt-3">
                                        <div class="alert alert-success">
                                            <i class="fas fa-check-circle"></i>
                                            {{__('settings.transactional_webhook_note')}}
                                        </div>
                                    </div>

                                    <div x-show="['mailpit', 'log', 'array'].includes($wire.smtp_provider)" class="mt-3">
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            {{__('settings.development_driver_note')}}
                                        </div>
                                    </div>

                                    <!-- Webhook Configuration Section -->
                                    <div x-show="['mailgun', 'ses', 'postmark'].includes($wire.smtp_provider)" class="mt-4">
                                        <div class="card-title h5 pt-3">{{__('settings.webhook_configuration')}} </div>
                                        <hr>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i>
                                            <strong>{{__('settings.webhook_setup_required')}}:</strong>
                                            {{__('settings.webhook_setup_instructions')}}
                                        </div>

                                        <div class="form-group mb-3">
                                            <label class="form-label">{{__('settings.webhook_url')}}:</label>
                                            <div class="input-group">
                                                <input type="text"
                                                    class="form-control"
                                                    readonly
                                                    x-bind:value="getWebhookUrl($wire.smtp_provider)">
                                                <button class="btn btn-outline-secondary"
                                                    type="button"
                                                    onclick="navigator.clipboard.writeText(getWebhookUrl(document.querySelector('select[id=&quot;smtp_provider&quot;]').value))">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted">{{__('settings.webhook_url_help')}}</small>
                                        </div>

                                        <div x-show="$wire.smtp_provider === 'mailgun'" class="mb-3">
                                            <h6 class="text-primary">{{__('settings.mailgun_webhook_setup')}}</h6>
                                            <ol class="text-sm">
                                                <li>{{__('settings.mailgun_webhook_step_1')}}</li>
                                                <li>{{__('settings.mailgun_webhook_step_2')}}</li>
                                                <li>{{__('settings.mailgun_webhook_step_3')}}</li>
                                            </ol>
                                        </div>

                                        <div x-show="$wire.smtp_provider === 'ses'" class="mb-3">
                                            <h6 class="text-primary">{{__('settings.ses_webhook_setup')}}</h6>
                                            <ol class="text-sm">
                                                <li>{{__('settings.ses_webhook_step_1')}}</li>
                                                <li>{{__('settings.ses_webhook_step_2')}}</li>
                                                <li>{{__('settings.ses_webhook_step_3')}}</li>
                                            </ol>
                                        </div>

                                        <div x-show="$wire.smtp_provider === 'postmark'" class="mb-3">
                                            <h6 class="text-primary">{{__('settings.postmark_webhook_setup')}}</h6>
                                            <ol class="text-sm">
                                                <li>{{__('settings.postmark_webhook_step_1')}}</li>
                                                <li>{{__('settings.postmark_webhook_step_2')}}</li>
                                                <li>{{__('settings.postmark_webhook_step_3')}}</li>
                                            </ol>
                                        </div>
                                    </div>

                                    <div class="mt-4 mb-0 form-group d-flex justify-content-end">
                                        <button type="submit" id="save-mail-config-btn" wire:click.prevent="saveSmtpConfig" class="btn btn-primary" wire:loading.attr="disabled">
                                            {{ __('settings.save_mail_config') }}
                                        </button>
                                    </div>
                                </x-form-items.form>
                            </div>
                        </div>
                    </div>
            <div class="col-lg-5">
                <!-- Notifications preferences card-->
                <div class="mb-5 card card-raised" style="min-height: 600px;">
                    <div class="p-3 card-body">
                        <div class="h5 text-center pt-4">{{__('settings.test_email_configuration')}}</div>
                        <div class="mb-4 text-muted text-xs text-center">{{__('settings.test_email_setup_instructions')}}.</div>
                        <x-form-items.form wire:submit="sendTestEmail" class="px-4 ">
                                    <div class="form-group mb-2">
                                        <label for="test_email_address">{{ __('settings.enter_email_address') }}</label>
                                        <input wire:model="test_email_address" id="test_email_address" type="email" class="form-control w-100 @error('test_email_address') is-invalid @enderror" required autofocus>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label for="test_email_message">{{ __('settings.enter_email_message') }}</label>
                                        <textarea wire:model="test_email_message" id="test_email_message" type="text" class="form-control w-100 @error('test_email_message') is-invalid @enderror" required autofocus></textarea>
                                    </div>
                                    <div class="mt-3 mb-0 form-group d-flex justify-content-end">
                                        <button type="submit" id="send-test-email-btn" wire:click.prevent="sendTestEmail" class="btn btn-primary" wire:loading.attr="disabled">
                                            {{ __('settings.send_test_email') }}
                                        </button>
                                    </div>
                                </x-form-items.form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class='mt-n3 pt-0'>
                <div class='row'>
                    <div class='col-md-6'>
                        <div class="mb-5 card card-raised">
                            <div class="py-4 px-5 card-body">
                                <div class="card-title h5 ">{{__('settings.payslips_mail_configuration')}} </div>
                                <x-form-items.form wire:submit="saveSmtpConfig">

                                    <div class="form-group mb-2">
                                        <label for="email_subject_en">{{ __('settings.enter_email_subject_english') }}</label>
                                        <input wire:model="email_subject_en" id="email_subject_en" type="text" class="form-control w-100 @error('email_subject_en') is-invalid @enderror" required autofocus>
                                    </div>

                                    <div class="form-group mb-2" wire:ignore>
                                        <label for="email_content_en">{{ __('settings.enter_email_content_english') }}</label>
                                        <textarea wire:model="email_content_en" id="email_content_en" class="email_content_en form-control w-100 @error('email_content_en') is-invalid @enderror">
                                                {!! $email_content_en !!}
                                            </textarea>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label for="welcome_email_subject_fr">{{ __('settings.enter_email_subject_french') }}</label>
                                        <input wire:model="email_subject_fr" id="email_subject_fr" type="text" class="form-control w-100 @error('email_subject_fr') is-invalid @enderror" required autofocus>
                                    </div>
                                    <div class=" form-group mb-2" wire:ignore>
                                        <label for="email_content_fr">{{ __('settings.enter_email_content_french') }}</label>
                                        <textarea wire:model="email_content_fr" id="email_content_fr" class="email_content_fr form-control w-100 @error('email_content_fr') is-invalid @enderror">
                                                     {!! $email_content_fr !!}
                                            </textarea>
                                    </div>

                                    <div class='text-xs text-danger ' style="font-size:small">
                                        {{__('settings.do_not_remove_email_placeholders')}} <strong class="fw-bolder"> :name: </strong>,{{__('settings.email_placeholder_note')}}
                                    </div>
                                    <div class="mt-4 mb-0 form-group d-flex justify-content-end">
                                        <button type="submit" id="save-birthday-email-config-btn" wire:click.prevent="saveSmtpConfig" class="btn btn-primary" wire:loading.attr="disabled">
                                            {{ __('settings.save_mail_config') }}
                                        </button>
                                    </div>

                                </x-form-items.form>
                            </div>
                        </div>
                    </div>
                    <div class='row col-md-6'>
                        <div class="mb-5 card card-raised">
                            <div class="py-4 px-5 card-body">
                                <div class="card-title h5 ">{{__('settings.welcome_email_configuration')}} </div>
                                <x-form-items.form wire:submit="saveSmtpConfig">
                                    <div class="form-group mb-2">
                                        <label for="welcome_email_subject_en">{{ __('settings.enter_welcome_email_subject_english') }}</label>
                                        <input wire:model="welcome_email_subject_en" id="welcome_email_subject_en" type="text" class="form-control w-100 @error('welcome_email_subject_en') is-invalid @enderror" required autofocus>
                                    </div>

                                    <div class="form-group mb-2" wire:ignore>
                                        <label for="welcome_email_content_en">{{ __('settings.enter_welcome_email_content_english') }}</label>
                                        <textarea wire:model="welcome_email_content_en" id="welcome_email_content_en" class="welcome_email_content_en form-control w-100 @error('welcome_email_content_en') is-invalid @enderror">
                                                {!! $welcome_email_content_en !!}
                                            </textarea>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label for="welcome_email_subject_fr">{{ __('settings.enter_welcome_email_subject_french') }}</label>
                                        <input wire:model="welcome_email_subject_fr" id="welcome_email_subject_fr" type="text" class="form-control w-100 @error('welcome_email_subject_fr') is-invalid @enderror" required autofocus>
                                    </div>
                                    <div class=" form-group mb-2" wire:ignore>
                                        <label for="welcome_email_content_fr">{{ __('settings.enter_welcome_email_content_french') }}</label>
                                        <textarea wire:model="welcome_email_content_fr" id="welcome_email_content_fr" class="welcome_email_content_fr form-control w-100 @error('welcome_email_content_fr') is-invalid @enderror">
                                                     {!! $welcome_email_content_fr !!}
                                            </textarea>
                                    </div>

                                    <div class='text-xs text-danger ' style="font-size:small">
                                        {{__('settings.do_not_remove_welcome_placeholders')}} <strong class="fw-bolder"> :name: </strong>, <strong class="fw-bolder"> :username: </strong> , <strong class="fw-bolder"> :site_url: </strong> and <strong class="fw-bolder"> :password: </strong>,{{__('settings.welcome_placeholders_note')}}
                                    </div>
                                    <div class="mt-4 mb-0 form-group d-flex justify-content-end">
                                        <button type="submit" id="save-welcome-email-config-btn" wire:click.prevent="saveSmtpConfig" class="btn btn-primary" wire:loading.attr="disabled">
                                            {{ __('settings.save_welcome_email_config') }}
                                        </button>
                                    </div>
                                </x-form-items.form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('livewire.portal.settings.partials.sms-provider-guide-modal')

    @include('livewire.portal.settings.partials.email-provider-guide-modal')

    @push('scripts')
    <script>
        var editor1 = new MediumEditor('.email_content_en');
        editor1.subscribe('blur', function(event, editable) {
            @this.set('email_content_en', editor1.getContent())
        });
        var editor2 = new MediumEditor('.email_content_fr');
        editor2.subscribe('blur', function(event, editable) {
            @this.set('email_content_fr', editor2.getContent())
        });
        var editor3 = new MediumEditor('.welcome_email_content_fr');
        editor2.subscribe('blur', function(event, editable) {
            @this.set('welcome_email_content_fr', editor3.getContent())
        });
        var editor4 = new MediumEditor('.welcome_email_content_en');
        editor2.subscribe('blur', function(event, editable) {
            @this.set('welcome_email_content_en', editor4.getContent())
        });

        function getWebhookUrl(provider) {
            const baseUrl = window.location.origin;
            const providerParam = provider || document.querySelector('select[id="smtp_provider"]').value;
            return `${baseUrl}/webhooks/email/${providerParam}`;
        }

        // Make function available globally for onclick handler
        window.getWebhookUrl = getWebhookUrl;
    </script>

</div>
@endpush
</div>