<div wire:ignore.self class="modal side-layout-modal fade" id="EditOvertimeModal" tabindex="-1" aria-labelledby="modal-form" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered " role="document">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0">
                        <h1 class="mb-0 h4">{{__('employees.update_overtime')}}</h1>
                        <p>{{__('employees.edit_and_update_overtime_request')}} &#128530;</p>
                    </div>
                    <x-form-items.form wire:submit.prevent="update">
                        <div class="form-group row mb-4">
                            <div class='col-md-6'>
                                <label for="start_time">{{__('common.start_time')}}</label>
                                <input wire:model.defer="start_time" type="datetime-local" class="form-control  @error('start_time') is-invalid @enderror" required="" {{\Str::contains($reason, 'System generated') ? 'disabled':''}}>
                                @error('start_time')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class='col-md-6'>
                                <label for="end_time">{{__('common.end_time')}}</label>
                                <input wire:model.defer="end_time" type="datetime-local" class="form-control  @error('end_time') is-invalid @enderror" required="">
                                @error('end_time')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="reason">{{__('common.reason')}}</label>
                            <textarea wire:model.defer="reason" name="reason" class="form-control  @error('reason') is-invalid @enderror" id='' cols='3' rows='3'></textarea>
                            @error('reason')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-gray-200 text-gray-600 ms-auto mx-3" data-bs-dismiss="modal">{{__('common.close')}}</button>
                            <button type="submit" wire:click.prevent="update" class="btn btn-secondary " wire:loading.attr="disabled">{{__('common.confirm')}}</button>
                        </div>
                    </x-form-items.form>
                </div>
            </div>
        </div>
    </div>
</div>