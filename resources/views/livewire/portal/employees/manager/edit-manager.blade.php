<div wire:ignore.self class="modal side-layout-modal fade" id="EditManagerModal" tabindex="-1" aria-labelledby="modal-form" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered " role="document">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0">

                        <h1 class="mb-0 h4">{{__('employees.update_manager_details')}}</h1>
                        <p>{{__('employees.edit_and_update_manager_details')}} &#128522;</p>
                    </div>
                    <x-form-items.form wire:submit="updateManager" class="form-modal">
                        <div class='form-group mb-4'>
                            <x-choices-multi-select
                                id="edit_manager_roles"
                                wireModel="selected_roles"
                                :options="$roles->pluck('name', 'name')->map(fn($name) => ucfirst($name))->toArray()"
                                :selected="$selected_roles"
                                label="{{__('common.roles')}}"
                                help="{{__('common.maximum_2_roles_allowed')}}"
                                class="form-select" />
                            @error('selected_roles')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4 row">
                            <div class='col-md-6 col-xs-12'>
                                <label for="first_name">{{__('employees.first_name')}}</label>
                                <input wire:model="first_name" type="text" class="form-control  @error('first_name') is-invalid @enderror" placeholder="{{__('employees.first_name_placeholder')}}" required="" name="first_name">
                                @error('first_name')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class='col-md-6 col-xs-12'>
                                <label for="last_name">{{__('employees.last_name')}}</label>
                                <input wire:model="last_name" type="text" class="form-control  @error('last_name') is-invalid @enderror" placeholder="{{__('employees.last_name_placeholder')}}" required="" name="last_name">
                                @error('last_name')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group mb-4 row">
                            <div class='col-md-6 col-xs-12'>
                                <label for="matricule">{{__('employees.matricule')}}</label>
                                <input wire:model="matricule" type="text" class="form-control @error('matricule') is-invalid @enderror" placeholder="{{__('employees.matricule_placeholder')}}" required="" name="matricule">
                                @error('matricule')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class='col-md-6 col-xs-12'>
                                <label for="email">{{__('employees.email')}}</label>
                                <input wire:model="email" type="email" class="form-control @error('email') is-invalid @enderror" placeholder="{{__('employees.email_placeholder')}}" required="" name="email">
                                @error('email')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror

                            </div>
                        </div>
                        <div class="form-group mb-4 row">
                            <div class='col-md-6 col-xs-12'>
                                <label for="professional_phone_number">{{__('common.prof_phone_number')}}</label>
                                <input wire:model="professional_phone_number" type="text" class="form-control  @error('professional_phone_number') is-invalid @enderror" placeholder="{{__('employees.phone_placeholder')}}" name="professional_phone_number">
                                @error('professional_phone_number')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class='col-md-6 col-xs-12'>
                                <label for="personal_phone_number">{{__('common.personal_phone_number')}}</label>
                                <input wire:model="personal_phone_number" type="text" class="form-control  @error('personal_phone_number') is-invalid @enderror" placeholder="{{__('employees.phone_placeholder')}}" name="personal_phone_number">
                                @error('personal_phone_number')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group mb-4 row">
                            <div class='col-md-12 col-xs-12'>
                                <label for="date_of_birth">{{__('common.date_of_birth')}}</label>
                                <input wire:model="date_of_birth" type="date" class="form-control  @error('date_of_birth') is-invalid @enderror" name="date_of_birth">
                                @error('date_of_birth')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group mb-4 row">
                            <div class='col-md-6 col-xs-12'>
                                <label for="status">{{__('common.status')}}</label>
                                <select wire:model="status" name="status" class="form-select  @error('status') is-invalid @enderror">
                                    <option value="">{{__("Select status")}}</option>
                                    <option value="true" @if($status === true || $status === "true") selected @endif>{{__('common.active')}}</option>
                                    <option value="false" @if($status === false || $status === "false") selected @endif>{{__('Banned')}}</option>
                                </select>
                                @error('status')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class='col-md-6 col-xs-12'>
                                <label for="password">{{__("Update Manager's Password")}}</label>
                                <input wire:model="password" type="text" class="form-control  @error('password') is-invalid @enderror" autofocus="" name="password">
                                @error('password')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" wire:click.prevent="clearFields" class="btn btn-gray-200 text-gray-600 ms-auto mx-3" data-bs-dismiss="modal">{{__('common.close')}}</button>
                            <button type="submit" wire:click.prevent="updateManager" class="btn btn-primary " wire:loading.attr="disabled">{{__('common.update')}}</button>
                        </div>
                    </x-form-items.form>
                </div>
            </div>
        </div>
    </div>
</div>