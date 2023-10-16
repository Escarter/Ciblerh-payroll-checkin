<?php

namespace App\Livewire\Portal\Reports;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Company;
use Livewire\Component;
use App\Models\Department;
use App\Livewire\Traits\WithDataTable;
use App\Models\Payslip as ModelsPayslip;

class Payslip extends Component
{
    use WithDataTable;

    public $companies = [];
    public $selectedCompanyId = null;
    public $selectedDepartmentId = '';
    public $departments = [];
    public $employees = [];
    public $employee_id = 'all';
    public $start_date;
    public $end_date;
    public $email_status = 'all';
    public $sms_status = 'all';
    public $query_string = '';
    public $auth_role;

    public function mount()
    {
        $this->companies  = match (auth()->user()->getRoleNames()->first()) {
            "manager" => Company::manager()->orderBy('name', 'desc')->get(),
            "admin" => Company::orderBy('name', 'desc')->get(),
            "supervisor" => [],
            "deafult" => [],
        };
        $this->departments = match (auth()->user()->getRoleNames()->first()) {
            "supervisor" => Department::supervisor()->get(),
            "manager" => [],
            "admin" => [],
            "deafult" => [],
        };
        $this->auth_role = auth()->user()->getRoleNames()->first();

    }

    public function updatedSelectedCompanyId($company_id)
    {
        if (!is_null($company_id)) {
            $this->departments = match ($this->auth_role) {
                "supervisor" => Department::supervisor()->where('company_id', $company_id)->get(),
                "manager" => Department::manager()->where('company_id', $company_id)->get(),
                "admin" => Department::where('company_id', $company_id)->get(),
                "deafult" => [],
            };
        }
    }
    public function updatedSelectedDepartmentId($department_id)
    {
        if (!is_null($department_id)) {
            $this->employees  = match ($this->auth_role) {
                "supervisor" => User::role(['employee'])->supervisor()->where('department_id', $department_id)->get(),
                "manager" => User::role(['employee'])->manager()->where('department_id', $department_id)->get(),
                "admin" => User::role(['employee'])->where('department_id', $department_id)->get(),
                "deafult" => [],
            };
        }
    }

    public function render()
    {
        return view('livewire.portal.reports.payslip',[
            'payslips' => $this->buildQuery()->paginate($this->perPage),
            'payslips_count' => $this->buildQuery()->count()
        ])->layout('components.layouts.dashboard');
    }

    public function buildQuery()
    {
        return ModelsPayslip::query()
        ->when(!empty($this->query_string), function ($q) {
            return
                $q->where('first_name', 'like', '%' . $this->query_string . '%')
                ->orWhere('last_name', 'like', '%' . $this->query_string . '%')
                ->orWhere('email', 'like', '%' . $this->query_string . '%')
                ->orWhere('matricule', 'like', '%' . $this->query_string . '%')
                ->orWhere('phone', 'like', '%' . $this->query_string . '%')
                ->orWhere('month', 'like', '%' . $this->query_string . '%')
                ->orWhere('email_sent_status', 'like', '%' . $this->query_string . '%')
                ->orWhere('sms_sent_status', 'like', '%' . $this->query_string . '%');
        })
        ->when($this->selectedCompanyId != "all" && $this->auth_role !== 'supervisor', function ($query) {
            return $query->where('company_id',  $this->selectedCompanyId);
        })->when($this->selectedDepartmentId != "all", function ($query) {
            return $query->where('department_id',  $this->selectedDepartmentId);
        })->when($this->employee_id != "all", function ($query) {
            return $query->where('employee_id',  $this->employee_id);
        })->when(!empty($this->email_status) && $this->email_status === 'all', function ($query) {
                return $query->where('email_sent_status', ModelsPayslip::STATUS_SUCCESSFUL)
                        ->orWhere('email_sent_status', ModelsPayslip::STATUS_PENDING)
                        ->orWhere('email_sent_status', ModelsPayslip::STATUS_FAILED);
        })->when(!empty($this->sms_status) && $this->sms_status === 'all', function ($query) {
                return $query->where('sms_sent_status', ModelsPayslip::STATUS_SUCCESSFUL)
                    ->orWhere('sms_sent_status', ModelsPayslip::STATUS_PENDING)
                    ->orWhere('sms_sent_status', ModelsPayslip::STATUS_FAILED);
         })->when(!empty($this->email_status) && $this->email_status == "1", function ($query) {
            return $query->where('email_sent_status', ModelsPayslip::STATUS_SUCCESSFUL);
        })->when(!empty($this->email_status) && $this->email_status == "0", function ($query) {
            return $query->where('email_sent_status', ModelsPayslip::STATUS_PENDING);
        })->when(!empty($this->email_status) && $this->email_status == "2", function ($query) {
            return $query->where('email_sent_status',  ModelsPayslip::STATUS_FAILED);
        })->when(!empty($this->sms_status) && $this->sms_status == "1", function ($query) {
            return $query->where('sms_sent_status', ModelsPayslip::STATUS_SUCCESSFUL);
        })->when(!empty($this->sms_status) && $this->sms_status == "0", function ($query) {
            return $query->where('sms_sent_status', ModelsPayslip::STATUS_PENDING);
        })->when(!empty($this->sms_status) && $this->sms_status == "2", function ($query) {
            return $query->where('sms_sent_status',  ModelsPayslip::STATUS_FAILED);
        })->when(!empty($this->start_date) || !empty($this->end_date), function ($query) {
            return $query->whereBetween('created_at', [Carbon::parse($this->start_date)->toDateString(), Carbon::parse($this->end_date)->toDateString()]);
        });
    }
}
