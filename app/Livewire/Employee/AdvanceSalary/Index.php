<?php

namespace App\Livewire\Employee\AdvanceSalary;

use App\Livewire\Traits\WithDataTable;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\AdvanceSalary;
use Illuminate\Support\Facades\Gate;

class Index extends Component
{
    use WithDataTable;

    public ?array $selected = [];

    //Create, Edit, Delete, View Post props
    public  $repayment_from_month;
    public  $repayment_to_month;
    public  $amount;
    public  $reason;
    public  $beneficiary_name;
    public  $beneficiary_id_card_number;
    public  $beneficiary_mobile_money_number;
    public  $advance_salary_id;
    public  $company_id;
    public ?AdvanceSalary $advance_salary = null;
    public $company;
    public $department;
    public $service;

    public function mount()
    {
        $this->company = auth()->user()->company;
        $this->department = auth()->user()->department;
        $this->service = auth()->user()->service;
    }

    protected $rules = [
        "amount" => "required|integer",
        "reason" => "required",
        "repayment_from_month" => "required|date|before:repayment_to_month",
        "repayment_to_month" => "required|date|after:repayment_from_month",
        "beneficiary_name" => "required",
        "beneficiary_mobile_money_number" => "required",
        "beneficiary_id_card_number" => "required",
    ];

    public function store()
    {
        if (!Gate::allows('advance_salary-create')) {
            return abort(401);
        }

        $this->validate();

        // Validate that user has required relationships
        if (empty($this->company)) {
            $this->addError('company', __('You are not associated with any company. Please contact your administrator.'));
            return;
        }

        if (empty($this->department)) {
            $this->addError('department', __('You are not associated with any department. Please contact your administrator.'));
            return;
        }

        auth()->user()->advanceSalaries()->create(
            [
                'company_id' => $this->company->id,
                'department_id' => $this->department->id,
                'author_id' => auth()->user()->author_id,
                'amount' => $this->amount,
                'reason' => $this->reason,
                'repayment_from_month' => $this->repayment_from_month,
                'repayment_to_month' => $this->repayment_to_month,
                'beneficiary_name' => $this->beneficiary_name,
                'beneficiary_mobile_money_number' => $this->beneficiary_mobile_money_number,
                'beneficiary_id_card_number' => $this->beneficiary_id_card_number,
            ]
        );
        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Advance salary recorded successfully!'), 'CreateAdvanceSalaryModal');
    }
    //Get & assign selected advance_salary props
    public function initData($advance_salary_id)
    {
        $advance_salary = AdvanceSalary::findOrFail($advance_salary_id);

        $this->advance_salary = $advance_salary;
        $this->amount = $advance_salary->amount;
        $this->reason = $advance_salary->reason;
        $this->repayment_from_month = $advance_salary->repayment_from_month->format('Y-m');
        $this->repayment_to_month = $advance_salary->repayment_to_month->format('Y-m');
        $this->beneficiary_name = $advance_salary->beneficiary_name;
        $this->beneficiary_mobile_money_number = $advance_salary->beneficiary_mobile_money_number;
        $this->beneficiary_id_card_number = $advance_salary->beneficiary_id_card_number;
        $this->advance_salary_id = $advance_salary->id;
        $this->company_id = $advance_salary->company_id;
        $this->department_id = $advance_salary->department_id;
    }


    public function update()
    {
        if (!Gate::allows('advance_salary--update')) {
            return abort(401);
        }
        $this->validate();
        $this->advance_salary->update([
            'amount' => $this->amount,
            'reason' => $this->reason,
            'repayment_from_month' => $this->repayment_from_month,
            'repayment_to_month' => $this->repayment_to_month,
            'beneficiary_name' => $this->beneficiary_name,
            'beneficiary_mobile_money_number' => $this->beneficiary_mobile_money_number,
            'beneficiary_id_card_number' => $this->beneficiary_id_card_number,
        ]);

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Advance Salary successfully updated!'), 'EditAdvanceSalaryModal');
    }
    public function delete()
    {
        if (!Gate::allows('advance_salary--delete')) {
            return abort(401);
        }

        if (!empty($this->advance_salary)) {

            $this->advance_salary->delete();
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Advance salary successfully deleted!'), 'DeleteModal');
    }

    public function clearFields()
    {
        $this->reset([
            'advance_salary',
            'advance_salary_id',
            'amount',
            'reason',
            'repayment_from_month',
            'repayment_to_month',
            'beneficiary_name',
            'beneficiary_mobile_money_number',
            'beneficiary_id_card_number',
        ]);
    }


    public function render()
    {
        if (!Gate::allows('advance_salary-read')) {
            return abort(401);
        }

        $advance_salaries = auth()->user()->advanceSalaries()->orderBy('created_at', 'desc')->paginate(10);

        // Get counts from all advance salaries, not just current page
        $allAdvanceSalaries = auth()->user()->advanceSalaries();
        $pending_advance_salary = $allAdvanceSalaries->where('approval_status', AdvanceSalary::APPROVAL_STATUS_PENDING)->count();
        $approved_advance_salary = $allAdvanceSalaries->where('approval_status', AdvanceSalary::APPROVAL_STATUS_APPROVED)->count();
        $rejected_advance_salary = $allAdvanceSalaries->where('approval_status', AdvanceSalary::APPROVAL_STATUS_REJECTED)->count();
        $advance_salaries_count = $advance_salaries->count();
   
        return view('livewire.employee.advance-salary.index', compact('advance_salaries', 'advance_salaries_count','pending_advance_salary', 'approved_advance_salary', 'rejected_advance_salary'))->layout('components.layouts.employee.master');
    }
}
