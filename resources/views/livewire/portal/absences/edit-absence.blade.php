<div wire:ignore.self class="modal side-layout-modal fade" id="EditAbsenceModal" tabindex="-1" aria-labelledby="modal-form" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered " role="document">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0">
                        <h1 class="mb-0 h4">{{__('Update or Approve Absence')}}</h1>
                        <p>{{__('Upate or Approve Employee absence record')}} &#128522;</p>
                    </div>
                    <x-form-items.form wire:submit="update">
                        <div class="form-group mb-4">
                            <label for="absence_date">{{__('Absence')}}</label>
                            <input wire:model="absence_date" type="date" class="form-control  @error('absence_date') is-invalid @enderror" value="{{now()->format('Y-m-d')}}" required="" name="absence_date">
                            @error('absence_date')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="absence_reason">{{__('Absence Reason')}}</label>
                            <textarea wire:model="absence_reason" name="absence_reason" class="form-control  @error('absence_reason') is-invalid @enderror" id='' cols='3' rows="3"></textarea>
                            @error('absence_reason')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class='form-group mb-4'>
                            <label for="approval_status">{{__('Approval Status')}}</label>
                            <select wire:model="approval_status" name="approval_status" class="form-select  @error('approval_status') is-invalid @enderror" required="">
                                <option value="">{{__('Select status')}}</option>
                                <option value="1">{{__('Approve')}}</option>
                                <option value="2">{{__('Reject')}}</option>
                            </select>
                            @error('approval_status')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="approval_reason">{{__('Approval/Rejection Reason')}}</label>
                            <textarea wire:model="approval_reason" name="approval_reason" class="form-control  @error('approval_reason') is-invalid @enderror" id='' cols='2' rows="2"></textarea>
                            @error('approval_reason')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-gray-200 text-gray-600 ms-auto mx-3" data-bs-dismiss="modal">{{__('Close')}}</button>
                            <button type="submit" wire:click.prevent="update" class="btn btn-secondary btn-loading">{{__('Confirm')}}</button>
                        </div>
                    </x-form-items.form>
                </div>
            </div>
        </div>
    </div>
</div>