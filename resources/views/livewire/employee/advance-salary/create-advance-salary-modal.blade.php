<div wire:ignore.self class="modal side-layout-modal fade" id="CreateAdvanceSalaryModal" tabindex="-1" aria-labelledby="modal-form" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered " role="document">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0">
                        <h1 class="mb-0 h4">{{__('employees.request_advance_salary')}}</h1>
                        <p>{{__('employees.create_and_submit_advance_salary_request')}} &#128530;</p>
                    </div>
                    <x-form-items.form wire:submit.prevent="store" enctype="multipart/form-data">
                        <h5 class="pb-0 mb-n2">{{__('common.request_details')}}</h5>
                        <hr class="mb-3">
                        <div class="form-group mb-4">
                            <label for="amount">{{__('common.amount')}}</label>
                            <input wire:model.defer="amount" type="money" class="form-control  @error('amount') is-invalid @enderror" placeholder="{{__('25,000')}}" value="" required="">
                            @error('amount')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="reason">{{__('common.reason')}}</label>
                            <textarea wire:model.defer="reason" class="form-control  @error('reason') is-invalid @enderror" id='' cols='3' rows="3" placeholder="{{__('To manage an urgent family matter')}}"></textarea>
                            @error('reason')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="repayment_from_month">{{__('employees.repayment_from_month')}}</label>
                            <input wire:model.defer="repayment_from_month" type="month" class="form-control  @error('repayment_from_month') is-invalid @enderror" min="{{now()->startOfMonth()->format('Y-m')}}" value="" required="">
                            @error('repayment_from_month')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="repayment_to_month">{{__('employees.repayment_to_month')}}</label>
                            <input wire:model.defer="repayment_to_month" type="month" class="form-control  @error('repayment_to_month') is-invalid @enderror" min="{{now()->startOfMonth()->format('Y-m')}}" value="" required="">
                            @error('end_repayment_month')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>

                        <h5 class="pb-0 mb-n2">{{__('common.beneficiary_details')}}</h5>
                        <hr class="mb-3">
                        <div class="form-group mb-4">
                            <label for="beneficiary_name">{{__('employees.beneficiary_name')}}</label>
                            <input wire:model.defer="beneficiary_name" type="text" class="form-control  @error('beneficiary_name') is-invalid @enderror" placeholder="{{__('Janette Jaqueline')}}" value="" required="">
                            @error('beneficiary_name')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="beneficiary_mobile_money_number">{{__('employees.beneficiary_mobile_money_number')}}</label>
                            <input wire:model.defer="beneficiary_mobile_money_number" type="text" class="form-control  @error('beneficiary_mobile_money_number') is-invalid @enderror" placeholder="{{__('6XXXXXXXX')}}" value="" required="">
                            @error('beneficiary_mobile_money_number')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="beneficiary_id_card_number">{{__('employees.beneficiary_id_card_number')}}</label>
                            <input wire:model.defer="beneficiary_id_card_number" type="text" class="form-control  @error('beneficiary_id_card_number') is-invalid @enderror" placeholder="{{__('12xxxxxxxx')}}" value="" required="">
                            @error('beneficiary_id_card_number')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-gray-200 text-gray-600 ms-auto mx-3" data-bs-dismiss="modal">{{__('common.close')}}</button>
                            <button type="submit" wire:click.prevent="store" class="btn btn-secondary " wire:loading.attr="disabled">{{__('employees.submit_request')}}</button>
                        </div>
                    </x-form-items.form>
                </div>
            </div>
        </div>
    </div>
</div>