<div wire:ignore.self class="modal side-layout-modal fade" id="CreateLeaveModal" tabindex="-1" aria-labelledby="modal-form" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered " role="document">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0">
                        <h1 class="mb-0 h4">{{__('Create a Leave')}}</h1>
                        <p>{{__('Record and submit a Leave')}} &#128522;</p>
                    </div>
                    <x-form-items.form wire:submit.prevent="store" class="form-modal">
                        <div class="form-group mb-4">
                            <label for="leave_type_id">{{__('Leave type')}}</label>
                            <select wire:model.defer="leave_type_id" class="form-control  @error('leave_type_id') is-invalid @enderror" required="">
                                <option value=''>{{__('--Select--')}}</option>
                                @foreach($types as $type)
                                <option value='{{$type->id}}'>{{$type->name}}</option>
                                @endforeach
                            </select>
                            @error('leave_type_id')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class='form-group row mb-4'>
                            <div class="col ">
                                <label for="start_date">{{__('Leave Date')}}</label>
                                <input type="date" wire:model.live="start_date" class="form-control  @error('start_date') is-invalid @enderror" required="">
                                @error('start_date')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                            <div class="col">
                                <label for="end_date">{{__('Leave Date')}}</label>
                                <input type="date" wire:model.live="end_date" class="form-control  @error('end_date') is-invalid @enderror" required="">
                                @error('end_date')
                                <div class="invalid-feedback">{{$message}}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="leave_reason">{{__('leave Reason')}}</label>
                            <textarea wire:model.defer="leave_reason" class="form-control  @error('leave_reason') is-invalid @enderror" id='' cols='5' rows="5"></textarea>
                            @error('leave_reason')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="d-flex justify-content-end">
                            <div>
                                @if($interval) {!! $interval !!} @endif
                            </div>
                            <button type="button" class="btn btn-gray-200 text-gray-600 ms-auto mx-3" data-bs-dismiss="modal">{{__('Close')}}</button>
                            <button type="submit" wire:click.prevent="store" class="btn btn-primary" wire:loading.attr="disabled">{{__('Create')}}</button>
                        </div>
                    </x-form-items.form>
                </div>
            </div>
        </div>
    </div>
</div>