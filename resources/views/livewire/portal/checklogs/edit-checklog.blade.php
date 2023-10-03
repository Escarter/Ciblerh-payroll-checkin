<div wire:ignore.self class="modal side-layout-modal fade" id="EditChecklogModal" tabindex="-1" aria-labelledby="modal-form" style="display: none;" aria-hidden="true">
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
                            <label for="user">{{__('Employee')}}</label>
                            <input wire:model="user" type="text" class="form-control  @error('user') is-invalid @enderror " required="" name="user" disabled>
                            @error('user')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group row mb-4">
                            <div class='col-md-6'>
                                <label for="start_time">{{__('Start Time')}}</label>
                                <input wire:model="start_time" type="datetime-local" class="form-control  @error('start_time') is-invalid @enderror" required="" name="start_time">
                                @error('start_time')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class='col-md-6'>
                                <label for="end_time">{{__('End Time')}}</label>
                                <input wire:model="end_time" type="datetime-local" class="form-control  @error('end_time') is-invalid @enderror" required="" name="end_time">
                                @error('end_time')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="checkin_comments">{{__('Checkin Comment')}}</label>
                            <textarea wire:model="checkin_comments" name="checkin_comments" class="form-control  @error('checkin_comments') is-invalid @enderror" id='' cols='3' rows='3'></textarea>
                            @error('checkin_comments')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <h3 class="fw-bold fs-5">{{__('Approval section')}}</h3>
                        <hr>
                        @if($role === "supervisor")
                        <div class='form-group mb-4'>
                            <label for="supervisor_approval_status">{{__('Approval Status')}}</label>
                            <select wire:model="supervisor_approval_status" name="supervisor_approval_status" class="form-select  @error('supervisor_approval_status') is-invalid @enderror" required="required">
                                <option value="">{{__('Select status')}}</option>
                                <option value="1">{{__('Approve')}}</option>
                                <option value="2">{{__('Reject')}}</option>
                            </select>
                            @error('supervisor_approval_status')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="supervisor_approval_reason">{{__('Approval/Rejection Reason')}}</label>
                            <textarea wire:model="supervisor_approval_reason" name="supervisor_approval_reason" class="form-control  @error('supervisor_approval_reason') is-invalid @enderror" id='' cols='3' rows="3"></textarea>
                            @error('supervisor_approval_reason')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        @else
                        <div class='form-group mb-4'>
                            <label for="manager_approval_status">{{__('Approval Status')}}</label>
                            <select wire:model="manager_approval_status" name="manager_approval_status" class="form-select  @error('manager_approval_status') is-invalid @enderror">
                                <option value="">{{__('Select status')}}</option>
                                <option value="1">{{__('Approve')}}</option>
                                <option value="2">{{__('Reject')}}</option>
                            </select>
                            @error('manager_approval_status')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="manager_approval_reason">{{__('Approval/Rejection Reason')}}</label>
                            <textarea wire:model="manager_approval_reason" name="manager_approval_reason" class="form-control  @error('manager_approval_reason') is-invalid @enderror" id='' cols='3' rows="3"></textarea>
                            @error('manager_approval_reason')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        @endif
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-gray-200 text-gray-600 ms-auto mx-3" data-bs-dismiss="modal">{{__('Close')}}</button>
                            <button type="submit" wire:click.prevent="update" class="btn btn-secondary " wire:loading.attr="disabled">{{__('Confirm')}}</button>
                        </div>
                    </x-form-items.form>
                </div>
            </div>
        </div>
    </div>
</div>