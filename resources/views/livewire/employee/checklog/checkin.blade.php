<div wire:ignore.self class="modal side-layout-modal fade" id="CheckInModal" tabindex="-1" aria-labelledby="modal-form" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered " role="document">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0">
                        <h1 class="mb-0 h4">{{__('employees.single_checkin')}}</h1>
                        <p>{{__('employees.record_checkin_to_track_work')}} &#128522;</p>
                    </div>
                    <x-form-items.form wire:submit.prevent="store" enctype="multipart/form-data" class="form-modal">
                        <div class="form-group mb-4">
                            <label for="start_time">{{__('employees.checkin_time')}}</label>
                            <input wire:model.defer="start_time" type="datetime-local" class="form-control  @error('start_time') is-invalid @enderror" value="{{now()->format('Y-m-d\TH:i')}}" min="{{now()->subMonths(1)->format('Y-m-d\TH:i')}}" max="{{now()->format('Y-m-d\TH:i')}}" required="">
                            @error('start_time')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="end_time">{{__('employees.checkout_time')}}</label>
                            <input wire:model.defer="end_time" type="datetime-local" class="form-control  @error('end_time') is-invalid @enderror" value="{{now()->format('Y-m-d\TH:i')}}" min="{{now()->subMonths(1)->format('Y-m-d\TH:i')}}" max="{{now()->format('Y-m-d\TH:i')}}" required="">
                            @error('end_time')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="comments">{{__('employees.comments')}}</label>
                            <textarea wire:model.defer="comments" class="form-control  @error('comments') is-invalid @enderror" id='' cols='30' rows='10'></textarea>
                            @error('comments')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-gray-200 text-gray-600 ms-auto mx-3" data-bs-dismiss="modal">{{__('common.close')}}</button>
                            <button type="submit" wire:click.prevent="store" class="btn btn-secondary " wire:loading.attr="disabled">{{__('employees.checkin')}}</button>
                        </div>
                    </x-form-items.form>
                </div>
            </div>
        </div>
    </div>
</div>