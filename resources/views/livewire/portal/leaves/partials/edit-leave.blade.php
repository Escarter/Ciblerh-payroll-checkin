<div wire:ignore.self class="modal side-layout-modal fade" id="EditLeaveModal" tabindex="-1" aria-labelledby="modal-form" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered " role="document">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0">
                        <h1 class="mb-0 h4">{{__('leaves.update_or_approve_leave')}}</h1>
                        <p>{{__('leaves.update_or_approve_employee_leave_record')}} &#128522;</p>
                    </div>
                    <x-form-items.form wire:submit="update">
                        <div class='row form-group mb-4'>
                            <div class="col">
                                <label for="user">{{__('employees.employee')}}</label>
                                <input wire:model="user" type="text" class="form-control  @error('user') is-invalid @enderror " required="" name="user" disabled>
                                @error('user')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class="col">
                                <label for="user">{{__('leaves.leave_type')}}</label>
                                <input wire:model="leave_type" type="text" class="form-control  @error('leave_type') is-invalid @enderror " required="" name="leave_type" disabled>
                                @error('leave_type')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group row mb-4">
                            <div class='col-md-6'>
                                <label for="start_date">{{__('employees.start_date')}}</label>
                                <input wire:model="start_date" id="start_date" type="date" class="form-control  @error('start_date') is-invalid @enderror" required="" name="start_date">
                                @error('start_date')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class='col-md-6'>
                                <label for="end_date">{{__('employees.end_date')}}</label>
                                <input wire:model="end_date" id="end_date" type="date" class="form-control  @error('end_date') is-invalid @enderror" required="" name="end_date">
                                @error('end_date')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="leave_reason">{{__('employees.leave_reason')}}</label>
                            <textarea wire:model="leave_reason" id="leave_reason" name="leave_reason" class="form-control  @error('leave_reason') is-invalid @enderror" cols='3' rows='3'></textarea>
                            @error('leave_reason')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        @if($leave && $leave->attachment_path)
                        <div class="form-group mb-4">
                            <label>{{__('common.attachment_link')}}</label>
                            <p class="mb-0">
                                <a href="{{ asset('storage/attachments/'.$leave->attachment_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <svg class="icon icon-xs me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    {{__('employees.view_attachment')}}
                                </a>
                            </p>
                        </div>
                        @endif
                        <h3 class="fw-bold fs-5">{{__('common.approval_section')}}</h3>
                        <hr>
                        @if($role === "supervisor")
                        <div class='form-group mb-4'>
                            <label for="supervisor_approval_status">{{__('employees.approval_status')}}</label>
                            <select wire:model="supervisor_approval_status" id="supervisor_approval_status" name="supervisor_approval_status" class="form-select  @error('supervisor_approval_status') is-invalid @enderror" required="required">
                                <option value="">{{__('common.select_status')}}</option>
                                <option value="1">{{__('common.approve')}}</option>
                                <option value="2">{{__('common.reject')}}</option>
                            </select>
                            @error('supervisor_approval_status')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="supervisor_approval_reason">{{__('common.approval_rejection_reason')}}</label>
                            <textarea wire:model="supervisor_approval_reason" id="supervisor_approval_reason" name="supervisor_approval_reason" class="form-control  @error('supervisor_approval_reason') is-invalid @enderror" cols='3' rows="3"></textarea>
                            @error('supervisor_approval_reason')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        @else
                        <div class='form-group mb-4'>
                            <label for="manager_approval_status">{{__('employees.approval_status')}}</label>
                            <select wire:model="manager_approval_status" id="manager_approval_status" name="manager_approval_status" class="form-select  @error('manager_approval_status') is-invalid @enderror">
                                <option value="">{{__('common.select_status')}}</option>
                                <option value="1">{{__('common.approve')}}</option>
                                <option value="2">{{__('common.reject')}}</option>
                            </select>
                            @error('manager_approval_status')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="manager_approval_reason">{{__('common.approval_rejection_reason')}}</label>
                            <textarea wire:model="manager_approval_reason" id="manager_approval_reason" name="manager_approval_reason" class="form-control  @error('manager_approval_reason') is-invalid @enderror" cols='3' rows="3"></textarea>
                            @error('manager_approval_reason')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        @endif
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-gray-200 text-gray-600 ms-auto mx-3" data-bs-dismiss="modal">{{__('common.close')}}</button>
                            <button type="submit" wire:click.prevent="update" class="btn btn-primary " wire:loading.attr="disabled">{{__('common.confirm')}}</button>
                        </div>
                    </x-form-items.form>
                </div>
            </div>
        </div>
    </div>
</div>