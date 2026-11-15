<div wire:ignore.self class="modal side-layout-modal fade" id="TestSmsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-body">
                <div class="p-3 p-lg-4">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h1 class="mb-1 h3 fw-bolder">{{ __('employees.test_sms_title') }}</h1>
                            <p class="mb-0 text-muted">{{ $testSmsEmployeeName }}</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                    </div>

                    @if(empty($testSmsPhones))
                        <div class="alert alert-warning mb-0">{{ __('employees.test_sms_no_phone') }}</div>
                    @else
                        <div class="mb-3">
                            <label class="form-label">{{ __('employees.test_sms_phone') }}</label>
                            <select wire:model="testSmsPhone" class="form-select @error('testSmsPhone') is-invalid @enderror">
                                @foreach($testSmsPhones as $type => $number)
                                    <option value="{{ $number }}">{{ $number }} ({{ __('employees.test_sms_' . $type) }})</option>
                                @endforeach
                            </select>
                            @error('testSmsPhone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-4">
                            <label class="form-label">{{ __('employees.test_sms_message') }}</label>
                            <textarea wire:model="testSmsMessage" rows="3" maxlength="160" class="form-control @error('testSmsMessage') is-invalid @enderror"></textarea>
                            @error('testSmsMessage') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-gray-200" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                            <button type="button" wire:click="sendTestSms" wire:loading.attr="disabled" wire:target="sendTestSms" class="btn btn-gray-800">
                                <span wire:loading.remove wire:target="sendTestSms">{{ __('employees.test_sms_send') }}</span>
                                <span wire:loading wire:target="sendTestSms">{{ __('employees.test_sms_sending') }}</span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
