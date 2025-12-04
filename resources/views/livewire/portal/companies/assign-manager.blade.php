<div wire:ignore.self class="modal  side-layout-modal fade" id="AssignManagerModal" tabindex="-1" aria-labelledby="modal-form" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0">
                        <h1 class="mb-0 h4">{{__('companies.assign_manager')}}</h1>
                        <p>{{__('companies.assign_manager_to_manage_company')}} &#128522;</p>
                    </div>
                    <form wire:submit.prevent="assignManager">
                        <div class='form-group mb-4'>
                            <label for="company_id">{{__('companies.company')}}</label>
                            <select wire:model="company_id" name="company_id" class="form-select @error('company_id') is-invalid @enderror">
                                <option value="">{{__('companies.select_company')}}</option>
                                @foreach ($companies as $company)
                                <option value="{{$company->id}}">{{$company->name}}</option>
                                @endforeach
                            </select>
                            @error('company_id')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class='form-group mb-4'>
                            <label for="manager_id">{{__('employees.manager')}}</label>
                            <select wire:model="manager_id" name="manager_id" class="form-select @error('manager_id') is-invalid @enderror">
                                <option value="">{{__('employees.select_manager')}}</option>
                                @foreach ($managers as $manager)
                                <option value="{{$manager->id}}">{{$manager->name}}</option>
                                @endforeach
                            </select>
                            @error('manager_id')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-gray-200 text-gray-600 ms-auto mx-3" data-bs-dismiss="modal">{{__('common.close')}}</button>
                            <button type="submit" class="btn btn-primary">{{__('companies.assign')}}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>