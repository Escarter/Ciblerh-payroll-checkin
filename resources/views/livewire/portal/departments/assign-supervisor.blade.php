<div wire:ignore.self class="modal side-layout-modal fade" id="AssignSupModal" tabindex="-1" aria-labelledby="modal-form" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered " role="document">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0">
                        <h1 class="mb-0 h4">{{__('departments.assign_supervisor')}}</h1>
                        <p>{{__('departments.assign_supervisor_to_department')}} &#128522;</p>
                    </div>
                    <x-form-items.form wire:submit="assignSupervisor">

                        <div class='form-group mb-4'>
                            <label for="department_id">{{__('departments.department')}}</label>
                            <select wire:model="department_id" name="department_id" class="form-select  @error('department_id') is-invalid @enderror">
                                <option value="">{{__('common.select')}} {{__('departments.department')}}</option>
                                @foreach ($departments as $department)
                                <option value="{{$department->id}}">{{$department->name}}</option>
                                @endforeach
                            </select>
                            @error('department_id')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class='form-group mb-4'>
                            <label for="supervisor_id">{{__('common.supervisor')}}</label>
                            <select wire:model="supervisor_id" name="supervisor_id" class="form-select  @error('supervisor_id') is-invalid @enderror">
                                <option value="">{{__('departments.select_supervisor')}}</option>
                                @foreach ($supervisors as $supervisor)
                                <option value="{{$supervisor->id}}">{{$supervisor->name}}</option>
                                @endforeach
                            </select>
                            @error('supervisor_id')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-gray-200 text-gray-600 ms-auto mx-3" data-bs-dismiss="modal">{{__('common.close')}}</button>
                            <button type="submit" wire:click.prevent="assignSupervisor" class="btn btn-primary" wire:loading.attr="disabled">{{__('departments.assign')}}</button>
                        </div>
                    </x-form-items.form>
                </div>
            </div>
        </div>
    </div>
</div>