<div wire:ignore.self class="modal fade" id="BulkRestoreModal" tabindex="-1" role="dialog" aria-labelledby="bulkRestoreModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0 text-center">
                        <svg class="icon icon-xxl text-success mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <h1 class="mb-0 h2 fw-bolder">{{__('common.bulk_restore_items')}}</h1>
                        <p class="pt-2">{{__('common.bulk_restore_confirmation_message')}} <strong>{{ count($selectedItems ?? []) }} {{ $itemType ?? __(\Str::plural('common.item', count($selectedItems ?? []) )) }}</strong> {{__('common.from_trash')}} &#128522;</p>
                    </div>
                    <div class="d-flex justify-content-center">
                        <button type="button" wire:click="bulkRestore" class="btn btn-success mx-3" data-bs-dismiss="modal">{{__('common.confirm_bulk_restore')}}</button>
                        <button type="button" class="btn btn-gray-300 text-white" data-bs-dismiss="modal">{{__('common.cancel')}}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
