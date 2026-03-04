<div wire:ignore.self class="modal side-layout-modal fade" id="EditAdvanceSalaryModal" tabindex="-1" aria-labelledby="modal-form" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered " role="document">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="p-3 p-lg-4">
                    <div class="mb-4 mt-md-0">
                        <h1 class="mb-0 h4">{{__('employees.update_or_approve_advance_salary')}}</h1>
                        <p>{{__('employees.update_or_approve_employee_advance_salary_request')}} &#128522;</p>
                    </div>
                    <x-form-items.form wire:submit="update">
                        <h5 class="pb-0 mb-n2">{{__('common.request_details')}}</h5>
                        <hr class="mb-3">
                        <div class="form-group row mb-4">
                            <div class="col-md-6">
                                <label for="employee">{{__('employees.employee')}}</label>
                                <input type="text" class="form-control  @error('employee') is-invalid @enderror" placeholder="{{__('employees.amount_placeholder')}}" value="{{$user}}" required="" name="user" disabled>
                            </div>
                            <div class="col-md-6">
                                <label for="company">{{__('companies.company')}}</label>
                                <input type="text" class="form-control  @error('company') is-invalid @enderror" placeholder="{{__('employees.amount_placeholder')}}" value="{{$company}}" name="company" disabled>
                            </div>
                        </div>
                        <div class="form-group mb-4">
                            <label for="amount">{{__('common.amount')}}</label>
                            <input wire:model="amount" type="money" class="form-control  @error('amount') is-invalid @enderror" placeholder="{{__('employees.amount_placeholder')}}" value="" name="amount" @if($advance_salary && !$advance_salary->canSupervisorEditAmount() && $role === 'supervisor') readonly @endif>
                            @error('amount')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="reason">{{__('common.reason')}}</label>
                            <textarea wire:model="reason" name="reason" class="form-control  @error('reason') is-invalid @enderror" id='' cols='3' rows="3" placeholder="{{__('employees.reason_placeholder')}}"></textarea>
                            @error('reason')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="repayment_from_month">{{__('employees.repayment_from_month')}}</label>
                            <input wire:model="repayment_from_month" type="month" class="form-control  @error('repayment_from_month') is-invalid @enderror" min="{{now()->addMonth(1)->format('Y-m')}}" value="" required="" name="repayment_from_month">
                            @error('repayment_from_month')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="repayment_to_month">{{__('employees.repayment_to_month')}}</label>
                            <input wire:model="repayment_to_month" type="month" class="form-control  @error('repayment_to_month') is-invalid @enderror" min="{{now()->addMonth(1)->format('Y-m')}}" value="" required="" name="repayment_to_month">
                            @error('end_repayment_month')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>

                        <h5 class="pb-0 mb-n2">{{__('common.beneficiary_details')}}</h5>
                        <hr class="mb-3">
                        <div class="form-group mb-4">
                            <label for="beneficiary_name">{{__('employees.beneficiary_name')}}</label>
                            <input wire:model="beneficiary_name" type="text" class="form-control  @error('beneficiary_name') is-invalid @enderror" placeholder="{{__('employees.beneficiary_name_placeholder')}}" value="" required="" name="beneficiary_name">
                            @error('beneficiary_name')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="beneficiary_mobile_money_number">{{__('employees.beneficiary_mobile_money_number')}}</label>
                            <input wire:model="beneficiary_mobile_money_number" type="text" class="form-control  @error('beneficiary_mobile_money_number') is-invalid @enderror" placeholder="{{__('employees.mobile_money_placeholder')}}" value="" required="" name="beneficiary_mobile_money_number">
                            @error('beneficiary_mobile_money_number')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        <div class="form-group mb-4">
                            <label for="beneficiary_id_card_number">{{__('employees.beneficiary_id_card_number')}}</label>
                            <input wire:model="beneficiary_id_card_number" type="text" class="form-control  @error('beneficiary_id_card_number') is-invalid @enderror" placeholder="{{__('employees.id_card_placeholder')}}" value="" required="" name="beneficiary_id_card_number">
                            @error('beneficiary_id_card_number')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        @if($role === 'supervisor')
                        <div class='form-group mb-4'>
                            <label for="supervisor_approval_status">{{__('employees.supervisor_approval')}}</label>
                            <select wire:model="supervisor_approval_status" id="supervisor_approval_status" name="supervisor_approval_status" class="form-select @error('supervisor_approval_status') is-invalid @enderror">
                                <option value="">{{__('common.select_status')}}</option>
                                <option value="1">{{__('common.approve')}}</option>
                                <option value="2">{{__('common.reject')}}</option>
                            </select>
                            @error('supervisor_approval_status')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        @else
                        <div class='form-group mb-4'>
                            <label for="manager_approval_status">{{__('employees.manager_approval')}}</label>
                            <select wire:model="manager_approval_status" id="manager_approval_status" name="manager_approval_status" class="form-select @error('manager_approval_status') is-invalid @enderror">
                                <option value="">{{__('common.select_status')}}</option>
                                <option value="1">{{__('common.approve')}}</option>
                                <option value="2">{{__('common.reject')}}</option>
                            </select>
                            @error('manager_approval_status')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        @endif
                        <div class="form-group mb-4">
                            <label for="approval_reason">{{__('common.approval_rejection_reason')}}</label>
                            <textarea wire:model="approval_reason" name="approval_reason" class="form-control  @error('approval_reason') is-invalid @enderror" id="approval_reason" cols='2' rows="2"></textarea>
                            @error('approval_reason')
                            <div class="invalid-feedback">{{$message}}</div>
                            @enderror
                        </div>
                        @if($advance_salary && $advance_salary->type === \App\Models\AdvanceSalary::TYPE_LOAN && in_array($role, ['manager', 'admin']))
                        <h5 class="pb-0 mb-n2">{{__('employees.loan_repayment')}}</h5>
                        <hr class="mb-3">
                        <div class="form-group mb-4">
                            <p class="small text-muted mb-1">
                                {{__('employees.amount_repaid')}}: <strong>{{ number_format($advance_salary->amount_repaid ?? 0) }} XAF</strong> |
                                {{__('employees.remaining_to_repay')}}: <strong>{{ number_format(max(0, ($advance_salary->amount ?? 0) - ($advance_salary->amount_repaid ?? 0))) }} XAF</strong>
                                @if($advance_salary->is_fully_repaid)
                                    <span class="badge bg-success">{{__('employees.fully_repaid')}}</span>
                                @endif
                            </p>
                            @if(!$advance_salary->is_fully_repaid)
                            <div class="input-group">
                                <input wire:model="repayment_amount" type="text" class="form-control @error('repayment_amount') is-invalid @enderror" placeholder="{{__('employees.repayment_amount_placeholder')}}">
                                <button type="button" wire:click="recordRepayment" wire:loading.attr="disabled" class="btn btn-outline-primary">{{__('employees.record_repayment')}}</button>
                            </div>
                            @error('repayment_amount')
                            <div class="invalid-feedback d-block">{{$message}}</div>
                            @enderror
                            @endif
                        </div>
                        @endif
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-gray-200 text-gray-600 ms-auto mx-3" data-bs-dismiss="modal">{{__('common.close')}}</button>
                            <button type="submit" wire:click.prevent="update" id="confirm-advance-salary-btn" class="btn btn-secondary " wire:loading.attr="disabled">{{__('common.confirm')}}</button>
                        </div>
                    </x-form-items.form>
                </div>
            </div>
        </div>
    </div>
</div>