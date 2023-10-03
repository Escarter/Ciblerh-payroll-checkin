<div wire:ignore.self class="modal side-layout-modal fade" id="EditBulkOvertimeModal" tabindex="-1" aria-labelledby="modal-form" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered " role="document">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0">
                        <h1 class="mb-0 h4">{{__('Bulk Overtime')}} {{$bulk_approval_status ? __('Approval') : __('Rejection')}} </h1>
                        <p>{{__('Employee Overtime Bulk')}} {{$bulk_approval_status ? __('Approval') : __('Rejection')}} &#128522;</p>
                    </div>
                    <x-form-items.form wire:submit="bulkApproval">

                        <div class="form-group mb-4">
                            <label for="approval_reason">{{$bulk_approval_status ? __('Approval') : __('Rejection')}} {{__('Reason')}}</label>
                            <textarea wire:model="approval_reason" name="approval_reason" class="form-control  @error('approval_reason') is-invalid @enderror" id='' cols='3' rows="3"></textarea>
                            @error('approval_reason')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-gray-200 text-gray-600 ms-auto mx-3" data-bs-dismiss="modal">{{__('Close')}}</button>
                            <button type="submit" wire:click.prevent="bulkApproval" class="btn btn-{{$bulk_approval_status ? 'success' : 'danger'}} text-white " wire:loading.attr="disabled">{{$bulk_approval_status ? __('Approve') : __('Reject')}}</button>
                        </div>
                    </x-form-items.form>
                </div>
            </div>
        </div>
    </div>
</div>