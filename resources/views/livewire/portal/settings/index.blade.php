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
                                    <label for="sms_provider_username">{{ __('SMS Provider') }}</label>
                                    <input type="text" class="form-control w-100" value="NEXAH" disabled>
                                </div>
                                <!-- Form Group (default email)-->
                                <div class="col">
                                    <label for="sms_provider_username">{{ __('Package Username') }}</label>
                                    <input wire:model="sms_provider_username" type="text" class="form-control w-100 @error('sms_provider_username') is-invalid @enderror" required autofocus>
                                </div>
                            </div>
                            <div class='form-group row mb-4'>
                                <div class="col">
                                    <label for="sms_provider_password">{{ __('Package password') }}</label>
                                    <input wire:model="sms_provider_password" type="text" class="form-control w-100 @error('sms_provider_password') is-invalid @enderror" required autofocus>
                                </div>
                                <div class="col">
                                    <label for="sms_provider_senderid">{{ __('Package sendername') }}</label>
                                    <input wire:model="sms_provider_senderid" type="text" class="form-control w-100 @error('sms_provider_senderid') is-invalid @enderror" required autofocus>
                                </div>
                            </div>
                            <div class="mt-4 mb-0 form-group d-flex justify-content-end">
                                <button type="submit" wire:click.prevent="saveSmsConfig" class="btn btn-primary">
                                    {{ __('Save SMS Config') }}
                                </button>
                            </div>
                        </x-form-items.form>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <!-- Notifications preferences card-->
                <div class="mb-5 card card-raised">
                    <div class="px-3 pt-3 card-body">
                        <div class='text-center'>
                            <p class='display-4'>
                                {{ $sms_balance }}
                            </p>
                            <div class="card-title">{{__('SMS Balance')}}</div>
                            <div class="mb-2 text-muted text-xs">{{__('You can check the detail of SMS sent on sms management section')}}.</div>
                        </div>
                        <x-form-items.form wire:submit="sendTestSms" class="px-4">
                            <div class="form-group mb-2">
                                <label for="test_phone_number">{{ __('Enter Phone number') }}</label>
                                <input wire:model="test_phone_number" type="text" class="form-control w-100 @error('test_phone_number') is-invalid @enderror" required autofocus>
                            </div>

                            <div class="mt-4 mb-0 form-group d-flex justify-content-end">
                                <button type="submit" wire:click.prevent="sendTestSms" class="btn btn-primary">
                                    {{ __('Send Test Sms') }}
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

                                <div class="mt-4 mb-0 form-group d-flex justify-content-end">
                                    <button type="submit" wire:click.prevent="saveSmtpConfig" class="btn btn-primary">
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

                                <div class="mt-3 mb-0 form-group d-flex justify-content-end">
                                    <button type="submit" wire:click.prevent="sendTestEmail" class="btn btn-primary">
                                        {{ __('Send Test Email') }}
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