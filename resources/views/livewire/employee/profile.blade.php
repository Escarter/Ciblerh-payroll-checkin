<div>
    <x-alert />
    <div class='container pt-3 pt-lg-4 pb-4 pb-lg-3 text-white'>
        <div class='d-flex flex-wrap align-items-center  justify-content-between '>
            <a href="{{route('employee.dashboard')}}" wire:navigate class="">
                <svg class="icon me-1 text-gray-500 bg-gray-300 rounded-circle p-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <x-layouts.navigation.employee-nav />
            </div>
        </div>
        <div class='d-flex flex-wrap justify-content-between align-items-center pt-3'>
            <div class=''>
                <h1 class='fw-bold display-4 text-gray-600 d-inline-flex align-items-end'>
                    <svg class="icon icon-md me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>
                        {{__('employees.profile_settings')}}
                    </span>
                </h1>
                <p class="text-gray-800 pml-3">{{__('employees.update_personal_details')}} &#128523;</p>
            </div>
            <div class=''>

            </div>
        </div>

        <div class='row'>
            <div class='col-md-6 '>
                <div class='card card-body text-gray-700'>
                    <h5 class="pb-3">{{__('employees.personal_details')}}</h5>
                    <x-form-items.form wire:submit.prevent="updateProfile" nctype="multipart/form-data" class="form-modal">
                        <div class="form-group mb-2 row">
                            <div class='col-md-6 col-xs-12'>
                                <label for="first_name">{{__('employees.first_name')}}</label>
                                <input wire:model.defer="first_name" type="text" class="form-control  @error('first_name') is-invalid @enderror" value="{{auth()->user()->first_name}}" required="" name="first_name">
                                @error('first_name')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class='col-md-6 col-xs-12'>
                                <label for="last_name">{{__('employees.last_name')}}</label>
                                <input wire:model.defer="last_name" type="text" class="form-control  @error('last_name') is-invalid @enderror" value="{{auth()->user()->last_name}}" required="" name="last_name">
                                @error('last_name')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group mb-2 row">
                            <div class='col-md-6 col-xs-12'>
                                <label for="matricule">{{__('employees.matricule')}}</label>
                                <input wire:model.defer="matricule" type="text" class="form-control @error('matricule') is-invalid @enderror" value="{{auth()->user()->matricule}}" required="" name="matricule">
                                @error('matricule')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class='col-md-6 col-xs-12'>
                                <label for="email">{{__('employees.email')}}</label>
                                <input wire:model.defer="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{auth()->user()->email}}" required="" name="email">
                                @error('email')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror

                            </div>
                        </div>
                        <div class='form-group mb-2'>
                            <label for="pdf_password">{{__('employees.pdf_password')}}</label>
                            <input wire:model.defer="pdf_password" type="text" class="form-control  @error('pdf_password') is-invalid @enderror" disabled>
                            @error('pdf_password')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-2 row">
                            <div class='col-md-6 col-xs-12'>
                                <label for="professional_phone_number">{{__('employees.phone_number')}}</label>
                                <input wire:model.defer="professional_phone_number" type="text" class="form-control  @error('professional_phone_number') is-invalid @enderror" value="{{auth()->user()->phone}}" name="professional_phone_number">
                                @error('professional_phone_number')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class='col-md-6 col-xs-12'>
                                <label for="personal_phone_number">{{__('employees.personal_phone_number')}}</label>
                                <input wire:model.defer="personal_phone_number" type="text" class="form-control  @error('personal_phone_number') is-invalid @enderror" autofocus="" name="personal_phone_number">
                                @error('personal_phone_number')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                        </div>
                        <div class='form-group mb-2'>
                            <label for="date_of_birth">{{__('common.date_of_birth')}}</label>
                            <input wire:model="date_of_birth" type="date" class="form-control  @error('date_of_birth') is-invalid @enderror" >
                            @error('date_of_birth')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-2 row">
                            <div class='col-md-6 col-xs-12'>
                                <label for="work_time">{{__('employees.work_time')}}</label>
                                <input wire:model="work_time" type="text" class="form-control  @error('work_time') is-invalid @enderror" value="{{auth()->user()->work_time}}" name="work_time" disabled>
                                @error('work_time')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class='col-md-6 col-xs-12'>
                                <label for="position">{{__('common.position')}}</label>
                                <input wire:model="position" type="text" class="form-control  @error('position') is-invalid @enderror" autofocus="" name="position">
                                @error('position')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                        </div>
                        <div class='form-group mb-2'>
                            <label for="preferred_language">{{__('employees.select_preferred_language')}}</label>
                            <select wire:model.defer="preferred_language" name="preferred_language" class="form-select  @error('preferred_language') is-invalid @enderror" required="">
                                <option value="">{{__('employees.select_language')}}</option>
                                <option value="en">{{__('employees.english')}}</option>
                                <option value="fr">{{__('employees.french')}}</option>
                            </select>
                            @error('preferred_language')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" wire:click.prevent="updateProfile" class="btn btn-secondary btn-loading">{{__('common.update')}} </button>
                        </div>
                    </x-form-items.form>
                </div>
            </div>
            <div class='col-md-6'>
                <div class='card card-body text-gray-700'>
                    <h5 class="pb-3">{{__('employees.add_signature')}} <br>
                        <small class="text-muted fw-light fs-6 fst-italic">{{__('employees.upload_signature')}} <a href='https://www.signwell.com/online-signature/draw/' wire:navigate target="_blank">{{__("employees.use_this_tool")}}</a> </small>
                    </h5>

                    <x-form-items.form wire:submit.prevent="saveSignature" nctype="multipart/form-data" class="form-modal">
                        <div class="form-group mb-2">
                            <input type="file" wire:model.defer="signature" class="form-control  @error('signature') is-invalid @enderror" />
                            @error('signature')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="d-flex justify-content-between">
                            @if(!is_null(auth()->user()->signature_path))
                            <div class="w-25 h-25">
                                <img src='{{asset("storage/attachments/".auth()->user()->signature_path)}}' alt=''>
                            </div>
                            @endif
                            <div>
                                <button type="submit" wire:click.prevent="saveSignature" class="btn btn-gray-300 text-gray-500 btn-loading">{{__('employees.add_signature')}} </button>
                            </div>
                        </div>
                    </x-form-items.form>
                </div>
                <div class='card card-body text-gray-700 mt-3'>
                    <h5 class="pb-3">{{__('employees.password_reset')}}</h5>
                    <x-form-items.form wire:submit.prevent="passwordReset" nctype="multipart/form-data" class="form-modal">
                        <div class='form-group mb-2'>
                            <label for="current_password">{{__('employees.current_password')}}</label>
                            <input wire:model.defer="current_password" type="text" class="form-control  @error('current_password') is-invalid @enderror">
                            @error('current_password')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class='form-group row mb-2'>
                            <div class='col-md-6 col-xs-12'>
                                <label for="password">{{__('employees.new_password')}}</label>
                                <input wire:model.defer="password" type="text" class="form-control  @error('password') is-invalid @enderror">
                                @error('password')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class='col-md-6 col-xs-12'>
                                <label for="password_confirmation">{{__('employees.confirm_password')}}</label>
                                <input wire:model.defer="password_confirmation" type="text" class="form-control  @error('password_confirmation') is-invalid @enderror">
                                @error('password_confirmation')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" wire:click.prevent="passwordReset" class="btn btn-gray-300 text-gray-500 btn-loading">{{__('employees.reset')}} </button>
                        </div>
                    </x-form-items.form>
                </div>
            </div>
        </div>
    </div>
</div>