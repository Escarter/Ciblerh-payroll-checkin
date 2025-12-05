<div wire:ignore.self class="modal side-layout-modal fade" id="ServiceModal" tabindex="-1" aria-labelledby="modal-form" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0">
                        <h1 class="mb-0 h4">{{ $isEditMode ? __('services.edit_service') : __('services.create_service') }}</h1>
                        <p>{{ $isEditMode ? __('services.edit_service_details') : __('services.create_new_service_to_manage') }} &#128522;</p>
                    </div>
                    <x-form-items.form wire:submit="{{ $isEditMode ? 'update' : 'store' }}">
                        <div class='form-group mb-4 row'>
                            <div class="col-md-6 col-sm-12">
                                <label for="company">{{__('companies.company')}}</label>
                                <input type="text" class="form-control  @error('company') is-invalid @enderror" value="{{!empty($department->company) ? $department->company->name : ''}}" disabled>
                            </div>
                            <div class="col-md-6 col-sm-12">
                                <label for="department">{{__('departments.department')}}</label>
                                <input type="text" class="form-control  @error('department') is-invalid @enderror" value="{{$department->name}}" disabled>
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="name">{{__('common.name')}}</label>
                            <input wire:model="name" type="text" class="form-control  @error('name') is-invalid @enderror" required="" name="name">
                            @error('name')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        @if($isEditMode)
                        <div class='form-group mb-4'>
                            <label for="is_active">{{__('common.status')}}?</label>
                            <select wire:model="is_active" name="is_active" class="form-select  @error('is_active') is-invalid @enderror">
                                <option value="">{{__('common.select_status')}}</option>
                                <option value="1" {{ ($is_active == true || $is_active == 1) ? 'selected' : '' }}>{{__('common.active')}}</option>
                                <option value="0" {{ ($is_active == false || $is_active == 0) ? 'selected' : '' }}>{{__('services.inactive')}}</option>
                            </select>
                            @error('is_active')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        @endif
                        <div class="d-flex justify-content-end">
                            <button type="button" wire:click.prevent="{{ $isEditMode ? 'clearFields' : '' }}" class="btn btn-gray-200 text-gray-600 ms-auto mx-3" data-bs-dismiss="modal">{{__('common.close')}}</button>
                            <button type="submit" wire:click.prevent="{{ $isEditMode ? 'update' : 'store' }}" class="btn btn-primary" wire:loading.attr="disabled">{{ $isEditMode ? __('common.update') : __('common.create') }}</button>
                        </div>
                    </x-form-items.form>
                </div>
            </div>
        </div>
    </div>
</div>






