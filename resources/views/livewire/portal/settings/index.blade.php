<div>
    <x-delete-modal />
    <x-alert />
    <div class='pb-0 mt-4'>
        <div class="row">
            <div class="col-lg-7">
                <div class="mb-5 card shadow-md card-raised">
                    <div class="py-4 px-5 card-body">
                        <div class="card-title h5 ">{{__('SMS Package Configuration')}} </div>
                        <x-form-items.form wire:submit="saveSmsConfig">
                            <div class='form-group row mb-4'>
                                <div class='col'>
                                    <label for="sms_provider">{{ __('SMS Provider') }}</label>
                                    <select wire:model.live='sms_provider' class="form-control w-100 @error('sms_provider') is-invalid @enderror">
                                        <option value='nexah' selected>{{__('NEXAH')}}</option>
                                        <option value='twilio'>{{__('Twilio')}}</option>
                                    </select>
                                </div>
                                <!-- Form Group (default email)-->
                                <div class="col">
                                    <label for="sms_provider_username">{{ __('Username or Token') }}</label>
                                    <input wire:model="sms_provider_username" type="text" class="form-control w-100 @error('sms_provider_username') is-invalid @enderror" required autofocus>
                                </div>
                            </div>
                            <div class='form-group row mb-4'>
                                <div class="col">
                                    <label for="sms_provider_password">{{ __('Password or secret') }}</label>
                                    <input wire:model="sms_provider_password" type="text" class="form-control w-100 @error('sms_provider_password') is-invalid @enderror" required autofocus>
                                </div>
                                <div class="col">
                                    <label for="sms_provider_senderid">{{ __('SenderId') }}</label>
                                    <input wire:model="sms_provider_senderid" type="text" class="form-control w-100 @error('sms_provider_senderid') is-invalid @enderror" required autofocus>
                                </div>
                            </div>
                            <div class="form-group mb-2">
                                <label for="sms_content_en">{{ __('Enter sms Content English') }}</label>
                                <textarea wire:model="sms_content_en" type="text" rows="3" class="form-control w-100 @error('sms_content_en') is-invalid @enderror" required autofocus></textarea>
                            </div>
                            <div class="form-group mb-2">
                                <label for="sms_content_fr">{{ __('Enter sms Content French') }}</label>
                                <textarea wire:model="sms_content_fr" type="text" rows="3" class="form-control w-100 @error('sms_content_fr') is-invalid @enderror" required autofocus></textarea>
                            </div>
                            <div class='text-xs text-danger ' style="font-size:small">
                                {{__('Don not remove or change the values of')}} <strong class="fw-bolder"> :name: , :month: , :year: , :pdf_password: </strong>,{{__(' as these are used as placeholders')}}
                            </div>
                            <div class="mt-4 mb-0 form-group d-flex justify-content-end">
                                <button type="submit" wire:click.prevent="saveSmsConfig" class="btn btn-primary" wire:loading.attr="disabled">
                                    {{ __('Save SMS Config') }}
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
                            <div class="card-title">{{__('SMS Balance')}}</div>
                            <div class="mb-2 text-muted text-xs">{{__('You can check the detail of SMS sent on sms management section')}}.</div>
                        </div>
                        <div class="card card-body mt-3">
                            <div class="card-title h5 ">{{__('Test SMS configuration')}} </div>
                            <x-form-items.form wire:submit="sendTestSms">

                                <div class="form-group mb-2">
                                    <label for="test_phone_number">{{ __('Enter Phone number') }}</label>
                                    <input wire:model="test_phone_number" type="text" class="form-control w-100 @error('test_phone_number') is-invalid @enderror" required autofocus>
                                </div>
                                <div class="form-group mb-2">
                                    <label for="test_sms_message">{{ __('Test SMS Message') }}</label>
                                    <textarea wire:model="test_sms_message" type="text" class="form-control w-100 @error('test_sms_message') is-invalid @enderror" required autofocus></textarea>
                                </div>
                                <div class="mt-4 mb-0 form-group d-flex justify-content-end">
                                    <button type="submit" wire:click.prevent="sendTestSms" class="btn btn-primary" wire:loading.attr="disabled">
                                        {{ __('Send Test Sms') }}
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
                                <div class="card-title h5 ">{{__('SMTP Configuration')}} </div>
                                <x-form-items.form wire:submit="saveSmtpConfig">
                                    <div class='form-group mb-2'>
                                        <label for='smtp_provider'>{{__('SMTP Provider')}}</label>
                                        <select wire:model='smtp_provider' id='' class="form-control" disabled>
                                            <option value='smtp' selected>{{__('SMTP')}}</option>
                                            <option value='mailgun'>{{__('MailGun')}}</option>
                                        </select>
                                    </div>
                                    <!-- Form Group (default email)-->
                                    <div class='form-group row mb-2'>
                                        <div class="col">
                                            <label for="smtp_host">{{ __('SMTP Host') }}</label>
                                            <input wire:model="smtp_host" type="text" class="form-control w-100 @error('smtp_host') is-invalid @enderror" required autofocus>
                                        </div>
                                        <div class="col">
                                            <label for="smtp_port">{{ __('SMTP Port') }}</label>
                                            <input wire:model="smtp_port" type="text" class="form-control w-100 @error('smtp_port') is-invalid @enderror" required autofocus>
                                        </div>
                                    </div>
                                    <div class='form-group row mb-2'>
                                        <div class="col">
                                            <label for="smtp_username">{{ __('SMTP Username') }}</label>
                                            <input wire:model="smtp_username" type="text" class="form-control w-100 @error('smtp_username') is-invalid @enderror" required autofocus>
                                        </div>
                                        <div class="col">
                                            <label for="smtp_password">{{ __('SMTP Password') }}</label>
                                            <input wire:model="smtp_password" type="text" class="form-control w-100 @error('smtp_password') is-invalid @enderror" required autofocus>
                                        </div>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label for="smtp_encryption">{{ __('SMTP Encryption') }}</label>
                                        <input wire:model="smtp_encryption" type="text" class="form-control w-100 @error('smtp_encryption') is-invalid @enderror" required autofocus>
                                    </div>
                                    <div class='form-group row mb-2'>
                                        <div class="col">
                                            <label for="from_email">{{ __('Fom Email') }}</label>
                                            <input wire:model="from_email" type="text" class="form-control w-100 @error('from_email') is-invalid @enderror" required autofocus>
                                        </div>
                                        <div class="col">
                                            <label for="from_name">{{ __('From Name') }}</label>
                                            <input wire:model="from_name" type="text" class="form-control w-100 @error('from_name') is-invalid @enderror" required autofocus>
                                        </div>
                                    </div>

                                    <div class="form-group mb-2">
                                        <label for="email_subject_en">{{ __('Enter Email subject English') }}</label>
                                        <input wire:model="email_subject_en" type="text" class="form-control w-100 @error('email_subject_en') is-invalid @enderror" required autofocus>
                                    </div>

                                    <div class="form-group mb-2" wire:ignore>
                                        <label for="email_content_en">{{ __('Enter Email Content English') }}</label>
                                        <textarea wire:model="email_content_en" class="email_content_en form-control w-100 @error('email_content_en') is-invalid @enderror">
                                            {!! $email_content_en !!}
                                        </textarea>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label for="welcome_email_subject_fr">{{ __('Enter Email subject French') }}</label>
                                        <input wire:model="email_subject_fr" type="text" class="form-control w-100 @error('email_subject_fr') is-invalid @enderror" required autofocus>
                                    </div>
                                    <div class=" form-group mb-2" wire:ignore>
                                        <label for="email_content_fr">{{ __('Enter Email Content French') }}</label>
                                        <textarea wire:model="email_content_fr" class="email_content_fr form-control w-100 @error('email_content_fr') is-invalid @enderror">
                                                 {!! $email_content_fr !!}
                                        </textarea>
                                    </div>

                                    <div class='text-xs text-danger ' style="font-size:small">
                                        {{__('Don not remove or change the values of')}} <strong class="fw-bolder"> :name: </strong>,{{__(' as this is used as placeholders')}}
                                    </div>
                                    <div class="mt-4 mb-0 form-group d-flex justify-content-end">
                                        <button type="submit" wire:click.prevent="saveSmtpConfig" class="btn btn-primary" wire:loading.attr="disabled">
                                            {{ __('Save Mail Config') }}
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
                                <div class="h5 text-center pt-4">{{__('SMTP configuration')}}</div>
                                <div class="mb-4 text-muted text-xs text-center">{{__('As of now only SMTP providers are supported')}}.</div>
                                <div class="mb-4 text-muted text-xs">
                                    <ol>
                                        <li>{{__('Login to your providers portal and create an smtp user')}}</li>
                                        <li>{{__('Create also password for the given user')}}</li>
                                        <li>{{__('Copy the smtp host and port provider by your provider')}}</li>
                                        <li>{{__('Now put these values in the fields configuration and save')}}</li>
                                        <li>{{__('Use below form to test email configurations.')}}</li>
                                    </ol>
                                </div>
                                <x-form-items.form wire:submit="sendTestEmail" class="px-4 ">
                                    <div class="form-group mb-2">
                                        <label for="test_email_address">{{ __('Enter Email Address') }}</label>
                                        <input wire:model="test_email_address" type="email" class="form-control w-100 @error('test_email_address') is-invalid @enderror" required autofocus>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label for="test_email_message">{{ __('Enter Email Message') }}</label>
                                        <textarea wire:model="test_email_message" type="text" class="form-control w-100 @error('test_email_message') is-invalid @enderror" required autofocus></textarea>
                                    </div>
                                    <div class="mt-3 mb-0 form-group d-flex justify-content-end">
                                        <button type="submit" wire:click.prevent="sendTestEmail" class="btn btn-primary" wire:loading.attr="disabled">
                                            {{ __('Send Test Email') }}
                                        </button>
                                    </div>
                                </x-form-items.form>
                            </div>
                        </div>
                        <div class="mb-5 card card-raised">
                            <div class="py-4 px-5 card-body">
                                <div class="card-title h5 ">{{__('Welcome Email configuration')}} </div>
                                <x-form-items.form wire:submit="saveSmtpConfig">
                                    <div class="form-group mb-2">
                                        <label for="welcome_email_subject_en">{{ __('Enter welcome email subject English') }}</label>
                                        <input wire:model="welcome_email_subject_en" type="text" class="form-control w-100 @error('welcome_email_subject_en') is-invalid @enderror" required autofocus>
                                    </div>

                                    <div class="form-group mb-2" wire:ignore>
                                        <label for="welcome_email_content_en">{{ __('Enter welcome email content English') }}</label>
                                        <textarea wire:model="welcome_email_content_en" class="welcome_email_content_en form-control w-100 @error('welcome_email_content_en') is-invalid @enderror">
                                            {!! $welcome_email_content_en !!}
                                        </textarea>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label for="welcome_email_subject_fr">{{ __('Enter welcome email subject French') }}</label>
                                        <input wire:model="welcome_email_subject_fr" type="text" class="form-control w-100 @error('welcome_email_subject_fr') is-invalid @enderror" required autofocus>
                                    </div>
                                    <div class=" form-group mb-2" wire:ignore>
                                        <label for="welcome_email_content_fr">{{ __('Enter welcome email content French') }}</label>
                                        <textarea wire:model="welcome_email_content_fr" class="welcome_email_content_fr form-control w-100 @error('welcome_email_content_fr') is-invalid @enderror">
                                                 {!! $welcome_email_content_fr !!}
                                        </textarea>
                                    </div>

                                    <div class='text-xs text-danger ' style="font-size:small">
                                        {{__('Don not remove or change the values of')}} <strong class="fw-bolder"> :name: </strong>, <strong class="fw-bolder"> :username: </strong> , <strong class="fw-bolder"> :site_url: </strong> and <strong class="fw-bolder"> :password: </strong>,{{__(' as these are used as placeholders')}}
                                    </div>
                                    <div class="mt-4 mb-0 form-group d-flex justify-content-end">
                                        <button type="submit" wire:click.prevent="saveSmtpConfig" class="btn btn-primary" wire:loading.attr="disabled">
                                            {{ __('Save Welcome Email Config') }}
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