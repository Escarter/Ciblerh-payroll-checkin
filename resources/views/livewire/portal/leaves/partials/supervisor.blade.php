<div class="mb-4 mt-md-0">
    <h1 class="mb-0 h4">{{__('employees.bulk_leave')}} {{($bulk_approval_status == 1) ? __('common.approve') : __('common.reject')}} </h1>
    <p>{{__('employees.employee_leave_bulk')}} {{$bulk_approval_status ? __('common.approve') : __('common.reject')}} &#128522;</p>
</div>
<x-form-items.form wire:submit="bulkApproval">

    <div class="form-group mb-4">
        <label for="supervisor_approval_reason">{{$bulk_approval_status? __('common.approve') : __('common.reject')}} {{__('common.reason')}}</label>
        <textarea wire:model="supervisor_approval_reason" name="supervisor_approval_reason" class="form-control  @error('supervisor_approval_reason') is-invalid @enderror" id='' cols='3' rows="3"></textarea>
        @error('manager_approval_reason')
        <div class="invalid-feedback">{{$message}}</div>
        @enderror
    </div>

    <div class="d-flex justify-content-end">
        <button type="button" class="btn btn-gray-200 text-gray-600 ms-auto mx-3" data-bs-dismiss="modal">{{__('common.close')}}</button>
        <button type="submit" wire:click.prevent="bulkApproval" class="btn btn-{{ $bulk_approval_status ? 'success' : 'danger'}} text-white " wire:loading.attr="disabled">{{ $bulk_approval_status ? __('common.approve') : __('common.reject')}}</button>
    </div>
</x-form-items.form>