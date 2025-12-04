<div wire:ignore.self class="modal side-layout-modal fade" id="EditOvertimeModal" tabindex="-1" aria-labelledby="modal-form" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered " role="document">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0">
                        <h1 class="mb-0 h4">{{__('Update or Approve Checkin')}}</h1>
                        <p>{{__('Upate or Approvel Employee chekin record')}} &#128522;</p>
                    </div>
                    <x-form-items.form wire:submit="update">
                        <div class="form-group mb-4">
                            <label for="user">{{__('employees.employee')}}</label>
                            <input wire:model="user" type="text" class="form-control  @error('user') is-invalid @enderror " required="" name="user" disabled>
                        </div>
                        <div class="form-group row mb-4">
                            <div class='col-md-6'>
                                <label for="start_time">{{__('common.start_time')}}</label>
                                <input wire:model="start_time" type="datetime-local" class="form-control  @error('start_time') is-invalid @enderror" required="" name="start_time">
                                @error('start_time')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class='col-md-6'>
                                <label for="end_time">{{__('common.end_time')}}</label>
                                <input wire:model="end_time" type="datetime-local" class="form-control  @error('end_time') is-invalid @enderror" required="" name="end_time">
                                @error('end_time')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="reason">{{__('Checkin Comment')}}</label>
                            <textarea wire:model="reason" name="reason" class="form-control  @error('reason') is-invalid @enderror" id='' cols='3' rows='3'></textarea>
                            @error('reason')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <h3 class="fw-bold fs-5">{{__('common.approval_section')}}</h3>
                        <hr>
                        <div class='form-group mb-4'>
                            <label for="approval_status">{{__('employees.approval_status')}}</label>
                            <select wire:model="approval_status" name="approval_status" class="form-select  @error('approval_status') is-invalid @enderror" required>
                                <option value="">{{__('common.select_status')}}</option>
                                <option value="1">{{__('common.approve')}}</option>
                                <option value="2">{{__('common.reject')}}</option>
                            </select>
                            @error('approval_status')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="approval_reason">{{__('common.approval_rejection_reason')}}</label>
                            <textarea wire:model="approval_reason" name="approval_reason" class="form-control  @error('approval_reason') is-invalid @enderror" id='' cols='3' rows="3"></textarea>
                            @error('approval_reason')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-gray-200 text-gray-600 ms-auto mx-3" data-bs-dismiss="modal">{{__('common.close')}}</button>
                            <button type="submit" wire:click.prevent="update" class="btn btn-secondary" wire:loading.attr="disabled">{{__('common.confirm')}}</button>
                        </div>
                    </x-form-items.form>
                </div>
            </div>
        </div>
    </div>
</div>