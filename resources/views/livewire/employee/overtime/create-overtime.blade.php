<div wire:ignore.self class="modal side-layout-modal fade" id="CreateOvertimeModal" tabindex="-1" aria-labelledby="modal-form" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered " role="document">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0">
                        <h1 class="mb-0 h4">{{__('employees.record_overtime_worked')}}</h1>
                        <p>{{__('employees.record_and_submit_overtime')}} &#128555;</p>
                    </div>
                    <x-form-items.form wire:submit.prevent="store" enctype="multipart/form-data" class="form-modal">
                        <div class="form-group mb-4">
                            <div class="form-check form-switch">
                                <input wire:model.live="use_quick_add" class="form-check-input" type="checkbox" id="use_quick_add">
                                <label class="form-check-label" for="use_quick_add">{{ __('employees.quick_add_overtime') }}</label>
                            </div>
                        </div>
                        @if($use_quick_add)
                        <div class="form-group row mb-4">
                            <div class="col-md-6">
                                <label for="quick_date">{{ __('holidays.date') }}</label>
                                <input wire:model="quick_date" type="date" class="form-control @error('quick_date') is-invalid @enderror" id="quick_date">
                                @error('quick_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="quick_hours">{{ __('employees.overtime_hours') }}</label>
                                <input wire:model="quick_hours" type="number" step="0.5" min="0.5" max="24" class="form-control @error('quick_hours') is-invalid @enderror" placeholder="2" id="quick_hours">
                                @error('quick_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted">{{ __('employees.quick_hours_help') }}</small>
                            </div>
                        </div>
                        @else
                        <div class="form-group row mb-4">
                            <div class='col-md-6'>
                                <label for="start_time">{{__('common.start_time')}}</label>
                                <input wire:model.defer="start_time" type="datetime-local" class="form-control  @error('start_time') is-invalid @enderror" required="" name="start_time">
                                @error('start_time')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class='col-md-6'>
                                <label for="end_time">{{__('common.end_time')}}</label>
                                <input wire:model.defer="end_time" type="datetime-local" class="form-control  @error('end_time') is-invalid @enderror" required="" name="end_time">
                                @error('end_time')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                        </div>
                        @endif
                        <div class="form-group mb-4">
                            <label for="reason">{{__('common.reason')}}</label>
                            <textarea wire:model.defer="reason" name="reason" class="form-control  @error('reason') is-invalid @enderror" id='' cols='3' rows='3'></textarea>
                            @error('reason')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-gray-200 text-gray-600 ms-auto mx-3" data-bs-dismiss="modal">{{__('common.close')}}</button>
                            <button type="submit" wire:click.prevent="store" class="btn btn-secondary " wire:loading.attr="disabled">{{__('employees.submit_overtime')}}</button>
                        </div>
                    </x-form-items.form>
                </div>
            </div>
        </div>
    </div>
</div>