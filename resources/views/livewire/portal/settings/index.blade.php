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
                            <input type="text" class="form-control w-100" value="NEXAH" disabled>
                            <!-- Form Group (default email)-->
                            <div class="form-group mb-2">
                                <label for="sms_provider_username">{{ __('Package Username') }}</label>
                                <input wire:model="sms_provider_username" type="text" class="form-control w-100 @error('sms_provider_username') is-invalid @enderror" required autofocus>
                            </div>
                            <div class="form-group mb-2">
                                <label for="sms_provider_password">{{ __('Package password') }}</label>
                                <input wire:model="sms_provider_password" type="text" class="form-control w-100 @error('sms_provider_password') is-invalid @enderror" required autofocus>
                            </div>
                            <div class="form-group mb-2">
                                <label for="sms_provider_senderid">{{ __('Package sendername') }}</label>
                                <input wire:model="sms_provider_senderid" type="text" class="form-control w-100 @error('sms_provider_senderid') is-invalid @enderror" required autofocus>
                            </div>
                            <div class="mt-4 mb-0 form-group d-flex justify-content-end">
                                <button type="submit" wire:click.prevent="saveSmsConfig" class="btn btn-primary">
                                    {{ __('Save') }}
                                </button>
                            </div>
                        </x-form-items.form>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <!-- Notifications preferences card-->
                <div class="mb-5 card card-raised">
                    <div class="p-3 text-center card-body">
                        <p class='display-4 pt-3'>
                            {{ $sms_balance }}
                        </p>
                        <div class="card-title">{{__('SMS Balance')}}</div>
                        <div class="mb-4 text-muted text-xs">{{__('You can check the detail of SMS sent on sms management section')}}.</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-0 pt-0">
            <div class="mb-5 card card-raised">
                <div class="py-4 px-5 card-body">
                    <div class="card-title h5 ">{{__('SMS Package Configuration')}} </div>
                    <x-form-items.form wire:submit="saveSmsConfig">
                        <div class='form-group mb-2'>
                            <label for='smtp_type'>{{__('SMTP Provide')}}</label>
                            <select wire:model='' id=''></select>
                        </div>
                        <input type="text" class="form-control w-100" value="NEXAH" disabled>
                        <!-- Form Group (default email)-->
                        <div class="form-group mb-2">
                            <label for="sms_provider_username">{{ __('Package Username') }}</label>
                            <input wire:model="sms_provider_username" type="text" class="form-control w-100 @error('sms_provider_username') is-invalid @enderror" required autofocus>
                        </div>
                        <div class="form-group mb-2">
                            <label for="sms_provider_password">{{ __('Package password') }}</label>
                            <input wire:model="sms_provider_password" type="text" class="form-control w-100 @error('sms_provider_password') is-invalid @enderror" required autofocus>
                        </div>
                        <div class="form-group mb-2">
                            <label for="sms_provider_senderid">{{ __('Package sendername') }}</label>
                            <input wire:model="sms_provider_senderid" type="text" class="form-control w-100 @error('sms_provider_senderid') is-invalid @enderror" required autofocus>
                        </div>
                        <div class="mt-4 mb-0 form-group d-flex justify-content-end">
                            <button type="submit" wire:click.prevent="saveSmsConfig" class="btn btn-primary">
                                {{ __('Save') }}
                            </button>
                        </div>
                    </x-form-items.form>
                </div>
            </div>
        </div>
    </div>
</div>