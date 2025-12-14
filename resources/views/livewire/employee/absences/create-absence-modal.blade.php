<div wire:ignore.self  class="modal side-layout-modal fade" id="CreateAbsenceModal" tabindex="-1" aria-labelledby="modal-form" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered " role="document">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0">
                        <h1 class="mb-0 h4">{{__('employees.create_an_absence')}}</h1>
                        <p>{{__('employees.record_and_submit_an_absence')}} &#128522;</p>
                    </div>
                    <x-form-items.form wire:submit.prevent="store" class="form-modal">
                        <div class='form-group row mb-4'>
                            <div class="col">
                                <label for="start_date">{{__('employees.absence_start_date')}}</label>
                                <input type="date" wire:model.live="start_date" class="form-control  @error('start_date') is-invalid @enderror" min="{{ date('Y-m-d') }}" required="">
                                @error('start_date')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class="col">
                                <label for="end_date">{{__('employees.absence_end_date')}}</label>
                                <input type="date" wire:model.live="end_date" class="form-control  @error('end_date') is-invalid @enderror" min="{{ date('Y-m-d') }}" required="">
                                @error('end_date')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
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
                            <div>
                                @if($interval) {!! $interval !!} @endif
                            </div>
                            <button type="button" class="btn btn-gray-200 text-gray-600 ms-auto mx-3" data-bs-dismiss="modal">{{__('common.close')}}</button>
                            <button type="submit" wire:click.prevent="store" class="btn btn-primary" wire:loading.attr="disabled">{{__('common.create')}}</button>
                        </div>
                    </x-form-items.form>
                </div>
            </div>
        </div>
    </div>
</div>