<div wire:ignore.self class="modal side-layout-modal fade" id="CreateCompanyModal" tabindex="-1" aria-labelledby="modal-form" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered " role="document">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0">
                        <h1 class="mb-0 h4">{{__('Create Company')}}</h1>
                        <p>{{__('Create a new company to manage')}} &#128522;</p>
                    </div>
                    <x-form-items.form wire:submit="store">
                        <div class="form-group row mb-4">
                            <div class='col-md-6'>
                                <label for="name">{{__('Name')}}</label>
                                <input wire:model="name" type="text" class="form-control  @error('name') is-invalid @enderror" required="" name="name">
                                @error('name')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class='col-md-6'>
                                <label for="code">{{__('Code')}}</label>
                                <input wire:model="code" type="text" class="form-control  @error('code') is-invalid @enderror" required="" value="" name="code">
                                @error('code')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                        </div>
                        @hasrole('admin')
                        <div class="form-group mb-4">
                            <label for="assign_manager">{{__('Assign Manager')}}</label>
                            <select wire:model="manager_id" name="manager_id" class="form-select">
                                <option value="">{{__('Select Manager')}}</option>
                                @foreach($managers as $manager)
                                    <option value="{{$manager->id}}">{{$manager->first_name}} {{$manager->last_name}}</option>
                                @endforeach
                            </select>
                        </div>
                        @endhasrole
                        <div class='form-group mb-4'>
                                <label for="sector">{{__('Sector')}}</label>
                                <input wire:model="sector" type="text" class="form-control  @error('sector') is-invalid @enderror" required="" name="sector">
                                @error('sector')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="description">{{__('Description')}}</label>
                            <textarea wire:model="description" name="description" class="form-control  @error('description') is-invalid @enderror" id='' cols='3' rows='3'></textarea>
                            @error('description')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
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