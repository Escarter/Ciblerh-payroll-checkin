<div wire:ignore.self class="modal side-layout-modal fade" id="EditAdvanceSalaryModal" tabindex="-1" aria-labelledby="modal-form" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered " role="document">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0">
                        <h1 class="mb-0 h4">{{__('Update or Approve Advance Salary')}}</h1>
                        <p>{{__('Upate or Approve Employee Advance salary request')}} &#128522;</p>
                    </div>
                    <x-form-items.form wire:submit="update">
                        <h5 class="pb-0 mb-n2">{{__('Request details')}}</h5>
                        <hr class="mb-3">
                        <div class="form-group row mb-4">
                            <div class="col-md-6">
                                <label for="employee">{{__('Employee')}}</label>
                                <input type="text" class="form-control  @error('employee') is-invalid @enderror" placeholder="{{__('25,000')}}" value="{{$user}}" required="" name="user" disabled>
                            </div>
                            <div class="col-md-6">
                                <label for="company">{{__('Company')}}</label>
                                <input type="text" class="form-control  @error('company') is-invalid @enderror" placeholder="{{__('25,000')}}" value="{{$company}}" name="company" disabled>
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="amount">{{__('Amount')}}</label>
                            <input wire:model="amount" type="money" class="form-control  @error('amount') is-invalid @enderror" placeholder="{{__('25,000')}}" value="" required="" name="amount">
                            @error('amount')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="reason">{{__('Reason')}}</label>
                            <textarea wire:model="reason" name="reason" class="form-control  @error('reason') is-invalid @enderror" id='' cols='3' rows="3" placeholder="{{__('To manage an urgent family matter')}}"></textarea>
                            @error('reason')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="repayment_from_month">{{__('Which month will start repayment?')}}</label>
                            <input wire:model="repayment_from_month" type="month" class="form-control  @error('repayment_from_month') is-invalid @enderror" min="{{now()->addMonth(1)->format('Y-m')}}" value="" required="" name="repayment_from_month">
                            @error('repayment_from_month')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="repayment_to_month">{{__('Which month will end repayment?')}}</label>
                            <input wire:model="repayment_to_month" type="month" class="form-control  @error('repayment_to_month') is-invalid @enderror" min="{{now()->addMonth(1)->format('Y-m')}}" value="" required="" name="repayment_to_month">
                            @error('end_repayment_month')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>

                        <h5 class="pb-0 mb-n2">{{__('Beneficiary details')}}</h5>
                        <hr class="mb-3">
                        <div class="form-group mb-4">
                            <label for="beneficiary_name">{{__('Beneficiary Name')}}</label>
                            <input wire:model="beneficiary_name" type="text" class="form-control  @error('beneficiary_name') is-invalid @enderror" placeholder="{{__('Janette Jaqueline')}}" value="" required="" name="beneficiary_name">
                            @error('beneficiary_name')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="beneficiary_mobile_money_number">{{__('Beneficiary Mobile Money Number')}}</label>
                            <input wire:model="beneficiary_mobile_money_number" type="text" class="form-control  @error('beneficiary_mobile_money_number') is-invalid @enderror" placeholder="{{__('6XXXXXXXX')}}" value="" required="" name="beneficiary_mobile_money_number">
                            @error('beneficiary_mobile_money_number')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="beneficiary_id_card_number">{{__('Beneficiary ID Card Number')}}</label>
                            <input wire:model="beneficiary_id_card_number" type="text" class="form-control  @error('beneficiary_id_card_number') is-invalid @enderror" placeholder="{{__('12xxxxxxxx')}}" value="" required="" name="beneficiary_id_card_number">
                            @error('beneficiary_id_card_number')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class='form-group mb-4'>
                            <label for="approval_status">{{__('Approval Status')}}</label>
                            <select wire:model="approval_status" name="approval_status" class="form-select  @error('approval_status') is-invalid @enderror">
                                <option value="">{{__('Select status')}}</option>
                                <option value="1">{{__('Approve')}}</option>
                                <option value="2">{{__('Reject')}}</option>
                            </select>
                            @error('approval_status')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="approval_reason">{{__('Approval/Rejection Reason')}}</label>
                            <textarea wire:model="approval_reason" name="approval_reason" class="form-control  @error('approval_reason') is-invalid @enderror" id='' cols='2' rows="2"></textarea>
                            @error('approval_reason')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-gray-200 text-gray-600 ms-auto mx-3" data-bs-dismiss="modal">{{__('Close')}}</button>
                            <button type="submit" wire:click.prevent="update" class="btn btn-secondary btn-loading">{{__('Confirm')}}</button>
                        </div>
                    </x-form-items.form>
                </div>
            </div>
        </div>
    </div>
</div>