<div>
    <x-delete-modal />
    <x-alert />
    <div class='pb-0 mt-4'>
        <div class="row">
            <div class="col-lg-7">
                <div class="mb-5 card shadow-md card-raised">
                    <div class="py-4 px-5 card-body">
                        <div class="card-title h5 ">{{__('settings.sms_package_configuration')}} </div>
                        <x-form-items.form wire:submit="saveSmsConfig">
                            <div class='form-group row mb-4'>
                                <div class='col'>
                                    <label for="sms_provider">{{ __('settings.sms_provider') }}</label>
                                    <select wire:model.live='sms_provider' class="form-control w-100 @error('sms_provider') is-invalid @enderror">
                                        <option value='nexah' selected>{{__('NEXAH')}}</option>
                                        <option value='twilio'>{{__('Twilio')}}</option>
                                        <option value='aws_sns'>{{__('AWS SNS')}}</option>
                                    </select>
                                </div>
                                <!-- Form Group (default email)-->
                                <div class="col">
                                    <label for="sms_provider_username">{{ __('settings.username_or_token') }}</label>
                                    <input wire:model="sms_provider_username" type="text" class="form-control w-100 @error('sms_provider_username') is-invalid @enderror" required autofocus>
                                </div>
                            </div>
                            <div class='form-group row mb-4'>
                                <div class="col">
                                    <label for="sms_provider_password">{{ __('settings.password_or_secret') }}</label>
                                    <input wire:model="sms_provider_password" type="text" class="form-control w-100 @error('sms_provider_password') is-invalid @enderror" required autofocus>
                                </div>
                                <div class="col">
                                    <label for="sms_provider_senderid">{{ __('settings.senderid') }}</label>
                                    <input wire:model="sms_provider_senderid" type="text" class="form-control w-100 @error('sms_provider_senderid') is-invalid @enderror" required autofocus>
                                </div>
                            </div>

                            <div class="card-title h5 pt-3 ">{{__('settings.payslip_sms_message_config')}} </div>
                            <hr>
                            <div class="form-group mb-2">
                                <label for="sms_content_en">{{ __('settings.enter_sms_content_english') }}</label>
                                <textarea wire:model="sms_content_en" type="text" rows="3" class="form-control w-100 @error('sms_content_en') is-invalid @enderror" required autofocus></textarea>
                            </div>
                            <div class="form-group mb-2">
                                <label for="sms_content_fr">{{ __('settings.enter_sms_content_french') }}</label>
                                <textarea wire:model="sms_content_fr" type="text" rows="3" class="form-control w-100 @error('sms_content_fr') is-invalid @enderror" required autofocus></textarea>
                            </div>
                            <div class='text-xs text-danger ' style="font-size:small">
                                {{__('settings.do_not_remove_placeholders')}} <strong class="fw-bolder"> :name: , :month: , :year: , :pdf_password: </strong>,{{__('settings.placeholders_note')}}
                            </div>

                            <div class="card-title h5 pt-3">{{__('settings.birthday_sms_message_config')}} </div>
                            <hr>
                            <div class="form-group mb-2">
                                <label for="birthday_sms_message_en">{{ __('settings.enter_birthday_sms_english') }}</label>
                                <textarea wire:model="birthday_sms_message_en" type="text" rows="3" class="form-control w-100 @error('birthday_sms_message_en') is-invalid @enderror" required autofocus></textarea>
                            </div>
                            <div class="form-group mb-2">
                                <label for="birthday_sms_message_fr">{{ __('settings.enter_birthday_sms_french') }}</label>
                                <textarea wire:model="birthday_sms_message_fr" type="text" rows="3" class="form-control w-100 @error('birthday_sms_message_fr') is-invalid @enderror" required autofocus></textarea>
                            </div>
                            <div class='text-xs text-danger ' style="font-size:small">
                                {{__('settings.do_not_remove_placeholders')}} <strong class="fw-bolder"> :name: </strong>,{{__('settings.placeholders_note')}}
                            </div>

                            <div class="mt-4 mb-0 form-group d-flex justify-content-end">
                                <button type="submit" wire:click.prevent="saveSmsConfig" class="btn btn-primary" wire:loading.attr="disabled">
                                    {{ __('settings.save_sms_config') }}
                                </button>
                            </div>

                        </x-form-items.form>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <!-- Notifications preferences card-->
                <div class="mb-5 ">
                    <div class="px-3 mb-3 ">
                        <div class='card card-body card-raised text-center pt-5'>
                            <p class='display-3'>
                                {{ $sms_balance }}
                            </p>
                            <div class="card-title">{{__('settings.sms_balance')}}</div>
                            <div class="mb-2 text-muted text-xs">{{__('settings.you_can_check_sms_details')}}.</div>
                        </div>
                        <div class="card card-body mt-3">
                            <div class="card-title h5 ">{{__('settings.test_sms_configuration')}} </div>
                            <x-form-items.form wire:submit="sendTestSms">

                                <div class="form-group mb-2">
                                    <label for="test_phone_number">{{ __('settings.enter_phone_number') }}</label>
                                    <input wire:model="test_phone_number" type="text" class="form-control w-100 @error('test_phone_number') is-invalid @enderror" required autofocus>
                                </div>
                                <div class="form-group mb-2">
                                    <label for="test_sms_message">{{ __('settings.test_sms_message') }}</label>
                                    <textarea wire:model="test_sms_message" type="text" class="form-control w-100 @error('test_sms_message') is-invalid @enderror" required autofocus></textarea>
                                </div>
                                <div class="mt-4 mb-0 form-group d-flex justify-content-end">
                                    <button type="submit" wire:click.prevent="sendTestSms" class="btn btn-primary" wire:loading.attr="disabled">
                                        {{ __('settings.send_test_sms') }}
                                    </button>
                                </div>
                            </x-form-items.form>
                            </fieldset>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-n3 pt-0">
                <div class='row'>
                    <div class='col-md-7'>
                        <div class="mb-5 card card-raised">
                            <div class="py-4 px-5 card-body">
                                <div class="card-title h5 ">{{__('settings.smtp_configuration')}} </div>
                                <x-form-items.form wire:submit="saveSmtpConfig">
                                    <div class='form-group mb-2'>
                                        <label for='smtp_provider'>{{__('settings.smtp_provider')}}</label>
                                        <select wire:model='smtp_provider' id='' class="form-control" disabled>
                                            <option value='smtp' selected>{{__('settings.smtp')}}</option>
                                            <option value='mailgun'>{{__('settings.mailgun')}}</option>
                                        </select>
                                    </div>
                                    <!-- Form Group (default email)-->
                                    <div class='form-group row mb-2'>
                                        <div class="col">
                                            <label for="smtp_host">{{ __('settings.smtp_host') }}</label>
                                            <input wire:model="smtp_host" type="text" class="form-control w-100 @error('smtp_host') is-invalid @enderror" required autofocus>
                                        </div>
                                        <div class="col">
                                            <label for="smtp_port">{{ __('settings.smtp_port') }}</label>
                                            <input wire:model="smtp_port" type="text" class="form-control w-100 @error('smtp_port') is-invalid @enderror" required autofocus>
                                        </div>
                                    </div>
                                    <div class='form-group row mb-2'>
                                        <div class="col">
                                            <label for="smtp_username">{{ __('settings.smtp_username') }}</label>
                                            <input wire:model="smtp_username" type="text" class="form-control w-100 @error('smtp_username') is-invalid @enderror" required autofocus>
                                        </div>
                                        <div class="col">
                                            <label for="smtp_password">{{ __('settings.smtp_password') }}</label>
                                            <input wire:model="smtp_password" type="text" class="form-control w-100 @error('smtp_password') is-invalid @enderror" required autofocus>
                                        </div>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label for="smtp_encryption">{{ __('settings.smtp_encryption') }}</label>
                                        <input wire:model="smtp_encryption" type="text" class="form-control w-100 @error('smtp_encryption') is-invalid @enderror" required autofocus>
                                    </div>
                                    <div class='form-group row mb-2'>
                                        <div class="col">
                                            <label for="from_email">{{ __('settings.from_email') }}</label>
                                            <input wire:model="from_email" type="text" class="form-control w-100 @error('from_email') is-invalid @enderror" required autofocus>
                                        </div>
                                        <div class="col">
                                            <label for="from_name">{{ __('settings.smtp_from_name') }}</label>
                                            <input wire:model="from_name" type="text" class="form-control w-100 @error('from_name') is-invalid @enderror" required autofocus>
                                        </div>
                                    </div>
                                    <div class="mt-4 mb-0 form-group d-flex justify-content-end">
                                        <button type="submit" wire:click.prevent="saveSmtpConfig" class="btn btn-primary" wire:loading.attr="disabled">
                                            {{ __('settings.save_mail_config') }}
                                        </button>
                                    </div>
                                </x-form-items.form>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <!-- Notifications preferences card-->
                        <div class="mb-5 card card-raised">
                            <div class="p-3 card-body">
                                <div class="h5 text-center pt-4">{{__('settings.smtp_configuration')}}</div>
                                <div class="mb-4 text-muted text-xs text-center">{{__('settings.smtp_providers_only_supported')}}.</div>
                                <div class="mb-4 text-muted text-xs">
                                    <ol>
                                        <li>{{__('settings.login_to_provider_portal')}}</li>
                                        <li>{{__('settings.create_smtp_password')}}</li>
                                        <li>{{__('settings.copy_smtp_details')}}</li>
                                        <li>{{__('settings.configure_smtp_values')}}</li>
                                        <li>{{__('settings.use_test_form')}}</li>
                                    </ol>
                                </div>
                                <x-form-items.form wire:submit="sendTestEmail" class="px-4 ">
                                    <div class="form-group mb-2">
                                        <label for="test_email_address">{{ __('settings.enter_email_address') }}</label>
                                        <input wire:model="test_email_address" type="email" class="form-control w-100 @error('test_email_address') is-invalid @enderror" required autofocus>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label for="test_email_message">{{ __('settings.enter_email_message') }}</label>
                                        <textarea wire:model="test_email_message" type="text" class="form-control w-100 @error('test_email_message') is-invalid @enderror" required autofocus></textarea>
                                    </div>
                                    <div class="mt-3 mb-0 form-group d-flex justify-content-end">
                                        <button type="submit" wire:click.prevent="sendTestEmail" class="btn btn-primary" wire:loading.attr="disabled">
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
                                        <input wire:model="email_subject_en" type="text" class="form-control w-100 @error('email_subject_en') is-invalid @enderror" required autofocus>
                                    </div>

                                    <div class="form-group mb-2" wire:ignore>
                                        <label for="email_content_en">{{ __('settings.enter_email_content_english') }}</label>
                                        <textarea wire:model="email_content_en" class="email_content_en form-control w-100 @error('email_content_en') is-invalid @enderror">
                                                {!! $email_content_en !!}
                                            </textarea>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label for="welcome_email_subject_fr">{{ __('settings.enter_email_subject_french') }}</label>
                                        <input wire:model="email_subject_fr" type="text" class="form-control w-100 @error('email_subject_fr') is-invalid @enderror" required autofocus>
                                    </div>
                                    <div class=" form-group mb-2" wire:ignore>
                                        <label for="email_content_fr">{{ __('settings.enter_email_content_french') }}</label>
                                        <textarea wire:model="email_content_fr" class="email_content_fr form-control w-100 @error('email_content_fr') is-invalid @enderror">
                                                     {!! $email_content_fr !!}
                                            </textarea>
                                    </div>

                                    <div class='text-xs text-danger ' style="font-size:small">
                                        {{__('settings.do_not_remove_email_placeholders')}} <strong class="fw-bolder"> :name: </strong>,{{__('settings.email_placeholder_note')}}
                                    </div>
                                    <div class="mt-4 mb-0 form-group d-flex justify-content-end">
                                        <button type="submit" wire:click.prevent="saveSmtpConfig" class="btn btn-primary" wire:loading.attr="disabled">
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
                                        <input wire:model="welcome_email_subject_en" type="text" class="form-control w-100 @error('welcome_email_subject_en') is-invalid @enderror" required autofocus>
                                    </div>

                                    <div class="form-group mb-2" wire:ignore>
                                        <label for="welcome_email_content_en">{{ __('settings.enter_welcome_email_content_english') }}</label>
                                        <textarea wire:model="welcome_email_content_en" class="welcome_email_content_en form-control w-100 @error('welcome_email_content_en') is-invalid @enderror">
                                                {!! $welcome_email_content_en !!}
                                            </textarea>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label for="welcome_email_subject_fr">{{ __('settings.enter_welcome_email_subject_french') }}</label>
                                        <input wire:model="welcome_email_subject_fr" type="text" class="form-control w-100 @error('welcome_email_subject_fr') is-invalid @enderror" required autofocus>
                                    </div>
                                    <div class=" form-group mb-2" wire:ignore>
                                        <label for="welcome_email_content_fr">{{ __('settings.enter_welcome_email_content_french') }}</label>
                                        <textarea wire:model="welcome_email_content_fr" class="welcome_email_content_fr form-control w-100 @error('welcome_email_content_fr') is-invalid @enderror">
                                                     {!! $welcome_email_content_fr !!}
                                            </textarea>
                                    </div>

                                    <div class='text-xs text-danger ' style="font-size:small">
                                        {{__('settings.do_not_remove_welcome_placeholders')}} <strong class="fw-bolder"> :name: </strong>, <strong class="fw-bolder"> :username: </strong> , <strong class="fw-bolder"> :site_url: </strong> and <strong class="fw-bolder"> :password: </strong>,{{__('settings.welcome_placeholders_note')}}
                                    </div>
                                    <div class="mt-4 mb-0 form-group d-flex justify-content-end">
                                        <button type="submit" wire:click.prevent="saveSmtpConfig" class="btn btn-primary" wire:loading.attr="disabled">
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
    </script>
    @endpush
</div>