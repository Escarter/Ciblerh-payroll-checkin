<div wire:ignore.self class="modal side-layout-modal fade" id="CreateManagerModal" tabindex="-1" aria-labelledby="modal-form" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-xs modal-dialog-centered " role="document">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0">

                        <h1 class="mb-0 h4">{{__('Create a new Manager')}}</h1>
                        <p>{{__('Create a new Manager')}} &#128522;</p>
                    </div>
                    <x-form-items.form wire:submit="store"  class="form-modal">
                        <div class='form-group mb-4'>
                            <label for="role_name">{{__('Role')}}</label>
                            <select wire:model="role_name" name="role_name" class="form-select  @error('role_name') is-invalid @enderror">
                                <option value="">{{__("Select role")}}</option>
                                @foreach ($roles->whereIn('name',['admin','manager']) as $role)
                                <option value="{{$role->name}}">{{$role->name}}</option>
                                @endforeach
                            </select>
                            @error('role_name')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4 row">
                            <div class='col-md-6 col-xs-12'>
                                <label for="first_name">{{__('First Name')}}</label>
                                <input wire:model="first_name" type="text" class="form-control  @error('first_name') is-invalid @enderror" placeholder="John" required="" name="first_name">
                                @error('first_name')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class='col-md-6 col-xs-12'>
                                <label for="last_name">{{__('Last Name')}}</label>
                                <input wire:model="last_name" type="text" class="form-control  @error('last_name') is-invalid @enderror" placeholder="Doe" required="" name="last_name">
                                @error('last_name')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group mb-4 row">
                            <div class='col-md-6 col-xs-12'>
                                <label for="matricule">{{__('Matricule')}}</label>
                                <input wire:model="matricule" type="text" class="form-control @error('matricule') is-invalid @enderror" placeholder="1134578" required="" name="matricule">
                                @error('matricule')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class='col-md-6 col-xs-12'>
                                <label for="email">{{__('Email')}}</label>
                                <input wire:model="email" type="email" class="form-control @error('email') is-invalid @enderror" placeholder="example@company.com" required="" name="email">
                                @error('email')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror

                            </div>
                        </div>
                        <div class="form-group mb-4 row">
                            <div class='col-md-6 col-xs-12'>
                                <label for="professional_phone_number">{{__('Prof Phone Number')}}</label>
                                <input wire:model="professional_phone_number" type="text" class="form-control  @error('professional_phone_number') is-invalid @enderror" placeholder="2376xxxxxxxxx" name="professional_phone_number">
                                @error('professional_phone_number')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class='col-md-6 col-xs-12'>
                                <label for="personal_phone_number">{{__('Personal Phone Number')}}</label>
                                <input wire:model="personal_phone_number" type="text" class="form-control  @error('personal_phone_number') is-invalid @enderror" placeholder="2376xxxxxxxxx" name="personal_phone_number">
                                @error('personal_phone_number')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group mb-4 row">
                            <div class='col-md-12 col-xs-12'>
                                <label for="date_of_birth">{{__('Date of Birth')}}</label>
                                <input wire:model="date_of_birth" type="date" class="form-control  @error('date_of_birth') is-invalid @enderror" name="date_of_birth">
                                @error('date_of_birth')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-4">
                            <div class='col-md-6 col-xs-12'>
                                <label for="status">{{__('Status')}}</label>
                                <select wire:model="status" name="status" class="form-select  @error('status') is-invalid @enderror">
                                    <option value="">{{__("Select status")}}</option>
                                    <option value="true">{{__('Active')}}</option>
                                    <option value="false">{{__('Banned')}}</option>
                                </select>
                                @error('status')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class='col-md-6 col-xs-12'>
                                <label for="password">{{__('Create Manager\'s Password')}}</label>
                                <input wire:model="password" type="text" class="form-control  @error('password') is-invalid @enderror" autofocus="" name="password">
                                @error('password')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-gray-200 text-gray-600 ms-auto mx-3" data-bs-dismiss="modal">{{__('Close')}}</button>
                            <button type="submit" wire:click.prevent="store" class="btn btn-primary" wire:loading.attr="disabled">{{__('Create')}}</button>
                        </div>
                    </x-form-items.form>
                </div>
            </div>
        </div>
    </div>
</div>