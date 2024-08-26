<div wire:ignore.self class="modal fade" id="resendSMSModal" tabindex="-1" role="dialog" aria-labelledby="createPost" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0 text-center">
                        <svg class="icon icon-xxl text-primary mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                        </svg>

                        <h1 class="mb-0 h2 fw-bolder">{{__('Are you sure?')}}</h1>
                        <p class="pt-2">{{__('You are about to resend sms with pdf password to employee!')}} &#128522;</p>
                    </div>
                    <div class="d-flex justify-content-center">
                        <button type="button" wire:click="resendSMS" class="btn btn-primary mx-3" data-dismiss="modal">{{__('Confirm')}}</button>
                        <button type="button" class="btn btn-gray-300 text-white " data-bs-dismiss="modal">{{__('Cancel')}}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>