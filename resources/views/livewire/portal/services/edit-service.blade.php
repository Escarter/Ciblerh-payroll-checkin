<div wire:ignore.self class="modal side-layout-modal fade" id="EditServiceModal" tabindex="-1" aria-labelledby="modal-form" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered " role="document">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0">
                        <h1 class="mb-0 h4">{{__('Edit Service')}}</h1>
                        <p>{{__('Edit Service details')}} &#128522;</p>
                    </div>
                    <x-form-items.form wire:submit="update">


                        <div class='form-group mb-4 row'>

                            <div class="col-md-6 col-sm-12">
                                <label for="company">{{__('companies.company')}}</label>
                                <input type="text" class="form-control  @error('company') is-invalid @enderror" value="{{!empty($department->company) ?$department->company->name : ''}}" disabled>
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

                        <div class='form-group mb-4'>
                            <label for="is_active">{{__('common.status')}}?</label>
                            <select wire:model="is_active" name="is_active" class="form-select  @error('is_active') is-invalid @enderror">
                                <option value="">{{__('common.select_status')}}</option>
                                <option value="1">{{__('common.active')}}</option>
                                <option value="0">{{__('Inactive')}}</option>
                            </select>
                            @error('is_active')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-gray-200 text-gray-600 ms-auto mx-3" data-bs-dismiss="modal">{{__('common.close')}}</button>
                            <button type="submit" wire:click.prevent="update" class="btn btn-primary" wire:loading.attr="disabled">{{__('common.update')}}</button>
                        </div>
                    </x-form-items.form>
                </div>
            </div>
        </div>
    </div>
</div>