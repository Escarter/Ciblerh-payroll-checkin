<div wire:ignore.self class="modal fade" id="resendEmailModal" tabindex="-1" role="dialog" aria-labelledby="createPost" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0 text-center">
                        <svg class="icon icon-xxl text-primary mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                        </svg>
                        <h1 class="mb-0 h2 fw-bolder">{{__('common.are_you_sure')}}</h1>
                        <p class="pt-2">{{__('You are about to resend email with employee payslip!')}} &#128522;</p>
                    </div>
                    <div class="d-flex justify-content-center">
                        <button type="button" wire:click="resendEmail" class="btn btn-primary mx-3" data-dismiss="modal">{{__('common.confirm')}}</button>
                        <button type="button" class="btn btn-gray-300 text-white " data-bs-dismiss="modal">{{__('common.cancel')}}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>