<div wire:ignore.self class="modal fade" id="ForceDeleteModal" tabindex="-1" role="dialog" aria-labelledby="forceDeleteModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0 text-center">
                        <svg class="icon icon-xxl text-danger mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        <h1 class="mb-0 h2 fw-bolder">{{__('common.permanent_deletion')}}</h1>
                        <p class="pt-2">{{__('You are about to permanently delete this')}} <strong>{{ $itemType ?? __('item') }}</strong> {{__('from the system.')}}</p>
                        <p class="text-danger fw-bold">{{__('common.this_action_cannot_be_undone')}}</p>
                        <div class="alert alert-warning d-flex align-items-center mt-3" role="alert">
                            <svg class="icon icon-sm me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <div>
                                <strong>{{__('Warning:')}}</strong> {{__('If this item has related records, the deletion will be prevented to maintain data integrity.')}}
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-center">
                        <button type="button" wire:click="bulkForceDelete" class="btn btn-danger mx-3" data-bs-dismiss="modal">{{__('common.delete_forever')}}</button>
                        <button type="button" class="btn btn-gray-300 text-white" data-bs-dismiss="modal">{{__('common.cancel')}}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
