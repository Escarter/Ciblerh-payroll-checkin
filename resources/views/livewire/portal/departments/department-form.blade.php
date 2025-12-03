<div wire:ignore.self class="modal side-layout-modal fade" id="DepartmentModal" tabindex="-1" aria-labelledby="modal-form" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0">
                        <h1 class="mb-0 h4">{{ $isEditMode ? __('Edit Department') : __('Create Department') }}</h1>
                        <p>{{ $isEditMode ? __('Edit and update department details') : __('Create a new Department to manage') }} &#128522;</p>
                    </div>
                    <x-form-items.form wire:submit="{{ $isEditMode ? 'update' : 'store' }}">
                        <div class="form-group mb-4">
                            <label for="company">{{__('Company')}}</label>
                            @if(auth()->user()->hasRole('supervisor'))
                                <input type="text" class="form-control" value="{{ $isEditMode ? ($department->company->name ?? __('Unknown Company')) : __('Multiple Companies') }}" disabled>
                                @if(!$isEditMode)
                                <small class="text-muted">{{__('Departments will be created in your assigned companies')}}</small>
                                @endif
                            @else
                                <input type="text" class="form-control  @error('company') is-invalid @enderror" value="{{$company->name}}" disabled>
                            @endif
                        </div>
                        <div class='form-group mb-4'>
                            <label for="supervisor_id">{{__('Supervisor')}}</label>
                            <select wire:model="supervisor_id" name="supervisor_id" class="form-select  @error('supervisor_id') is-invalid @enderror">
                                <option value="">{{__("Select supervisor")}}</option>
                                @foreach ($supervisors as $supervisor)
                                <option value="{{$supervisor->id}}" {{ $isEditMode && $supervisor_id == $supervisor->id ? 'selected' : '' }}>{{$supervisor->name}}</option>
                                @endforeach
                            </select>
                            @error('supervisor_id')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="name">{{__('Name')}}</label>
                            <input wire:model="name" type="text" class="form-control  @error('name') is-invalid @enderror" required="" name="name">
                            @error('name')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        @if($isEditMode)
                        <div class='form-group mb-4'>
                            <label for="is_active">{{__('Is Active')}}?</label>
                            <select wire:model="is_active" name="is_active" class="form-select  @error('is_active') is-invalid @enderror">
                                <option value="">{{__('Select status')}}</option>
                                <option value="1" {{ $is_active == true || $is_active == 1 ? 'selected' : '' }}>{{__('Active')}}</option>
                                <option value="0" {{ $is_active == false || $is_active == 0 ? 'selected' : '' }}>{{__('Inactive')}}</option>
                            </select>
                            @error('is_active')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        @endif
                        <div class="d-flex justify-content-end">
                            <button type="button" wire:click.prevent="{{ $isEditMode ? 'clearFields' : '' }}" class="btn btn-gray-200 text-gray-600 ms-auto mx-3" data-bs-dismiss="modal">{{__('Close')}}</button>
                            <button type="submit" wire:click.prevent="{{ $isEditMode ? 'update' : 'store' }}" class="btn btn-primary" wire:loading.attr="disabled">{{ $isEditMode ? __('Update') : __('Create') }}</button>
                        </div>
                    </x-form-items.form>
                </div>
            </div>
        </div>
    </div>
</div>




