<div wire:ignore.self class="modal side-layout-modal fade" id="CreateLeaveTypeModal" tabindex="-1" aria-labelledby="modal-form" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered " role="document">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0">
                        <h1 class="mb-0 h4">{{__('leaves.create_leave_type')}}</h1>
                        <p>{{__('leaves.create_new_leave_type_to_manage')}} &#128522;</p>
                    </div>
                    <x-form-items.form wire:submit="store">
                        <div class='row form-group mb-2'>
                            <div class="col">
                                <label for="name">{{__('common.name')}}</label>
                                <input wire:model="name" type="text" class="form-control  @error('name') is-invalid @enderror" required="" name="name">
                                @error('name')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class="col">
                                <label for="default_number_of_days">{{__('leaves.default_number_of_days')}}</label>
                                <input wire:model="default_number_of_days" type="text" class="form-control  @error('default_number_of_days') is-invalid @enderror" required="" name="default_number_of_days">
                                @error('default_number_of_days')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group mb-4">
                            <label for="description">{{__('common.description')}}</label>
                            <textarea wire:model="description" name="description" class="form-control  @error('description') is-invalid @enderror" id='' cols='3' rows="3"></textarea>
                            @error('description')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-gray-200 text-gray-600 ms-auto mx-3" data-bs-dismiss="modal">{{__('common.close')}}</button>
                            <button type="submit" wire:click.prevent="store" class="btn btn-primary" wire:loading.attr="disabled">{{__('common.create')}}</button>
                        </div>
                    </x-form-items.form>
                </div>
            </div>
        </div>
    </div>
</div>