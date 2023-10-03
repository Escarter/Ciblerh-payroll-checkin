<div wire:ignore.self  class="modal side-layout-modal fade" id="CreateAbsenceModal" tabindex="-1" aria-labelledby="modal-form" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered " role="document">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0">
                        <h1 class="mb-0 h4">{{__('Create an Absence')}}</h1>
                        <p>{{__('Record and submit an absence')}} &#128522;</p>
                    </div>
                    <x-form-items.form wire:submit.prevent="store" class="form-modal">
                        <div class="form-group mb-4">
                            <label for="absence_date">{{__('Absence Date')}}</label>
                            <input type="date" wire:model.defer="absence_date" class="form-control  @error('absence_date') is-invalid @enderror" required="">
                            @error('absence_date')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="absence_reason">{{__('Absence Reason')}}</label>
                            <textarea wire:model.defer="absence_reason" class="form-control  @error('absence_reason') is-invalid @enderror" id='' cols='5' rows="5"></textarea>
                            @error('absence_reason')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="attachment">{{__('Supporting File')}}</label>
                            <input type="file" wire:model.defer="attachment" class="form-control  @error('attachment') is-invalid @enderror" />
                            @error('attachment')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-gray-200 text-gray-600 ms-auto mx-3" data-bs-dismiss="modal">{{__('Close')}}</button>
                            <button type="submit" wire:click.prevent="store" class="btn btn-primary" wire:loading.attr="disabled">{{__('Create')}}</button>
                        </div>
                    </x-form-items.form>
                </div>
            </div>
        </div>
    </div>
</div>