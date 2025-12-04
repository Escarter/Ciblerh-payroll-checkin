<div wire:ignore.self  class="modal side-layout-modal fade" id="EditAbsenceModal" tabindex="-1" aria-labelledby="modal-form" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered " role="document">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0">
                        <h1 class="mb-0 h4">{{__('employees.update_an_absence')}}</h1>
                        <p>{{__('employees.edit_and_update_absence')}} &#128522;</p>
                    </div>
                    <x-form-items.form wire:submit.prevent="update" class="form-modal">
                        <div class="form-group mb-4">
                            <label for="absence_date">{{__('employees.absence_date')}}</label>
                            <input type="date" wire:model.defer="absence_date" class="form-control  @error('absence_date') is-invalid @enderror" required="">
                            @error('absence_date')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="absence_reason">{{__('leaves.absence_reason')}}</label>
                            <textarea wire:model.defer="absence_reason" class="form-control  @error('absence_reason') is-invalid @enderror" id='' cols='5' rows="5"></textarea>
                            @error('absence_reason')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="attachment">{{__('employees.supporting_file')}}</label>
                            <input type="file" wire:model.defer="attachment" class="form-control  @error('attachment') is-invalid @enderror" />
                            @error('attachment')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-gray-200 text-gray-600 ms-auto mx-3" data-bs-dismiss="modal">{{__('common.close')}}</button>
                            <button type="submit" wire:click.prevent="update" class="btn btn-primary" wire:loading.attr="disabled">{{__('common.update')}}</button>
                        </div>
                    </x-form-items.form>
                </div>
            </div>
        </div>
    </div>
</div>