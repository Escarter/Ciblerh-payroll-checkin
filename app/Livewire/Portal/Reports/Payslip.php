<?php

namespace App\Livewire\Portal\Reports;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Company;
use Livewire\Component;
use App\Models\Department;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Exports\PayslipReportExport;
use App\Livewire\Traits\WithDataTable;
use App\Models\Payslip as ModelsPayslip;
use App\Services\ReportGenerationService;
use App\Models\DownloadJob;

class Payslip extends Component
{
    use WithDataTable;

    public $companies = [];
    public $selectedCompanyId = null;
    public $selectedDepartmentId = null;
    public $departments = [];
    public $employees = [];
    public $employee_id = null;
    public $start_date;
    public $end_date;
    public $email_status;
    public $sms_status;
    public $query_string = '';
    public $auth_role;

    public function mount()
    {
        $this->companies  = match (auth()->user()->getRoleNames()->first()) {
            "manager" => Company::whereIn('id', auth()->user()->managerCompanies->pluck('id'))->orderBy('name', 'desc')->get(),
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
                "supervisor" => $this->selectedCompanyId != "all" ? Department::supervisor()->where('company_id', $company_id)->get() : Department::supervisor()->get(),
                "manager" =>  $this->selectedCompanyId != "all" ?  Department::where('company_id', $company_id)->get() : Department::whereIn('company_id', auth()->user()->managerCompanies->pluck('id'))->get(),
                "admin" => $this->selectedCompanyId != "all" ? Department::where('company_id', $company_id)->get() : Department::all(),
                "deafult" => [],
            };
            $this->employee_id = '';
        }
    }
    public function updatedSelectedDepartmentId($department_id)
    {
        if (!is_null($department_id)) {
            $this->employees  = match ($this->auth_role) {
                "supervisor" => $this->selectedDepartmentId != "all" ? User::role(['employee'])->whereDoesntHave('roles', function($query) { $query->where('name', 'admin'); })->select('id', 'first_name', 'last_name', 'department_id')->supervisor()->where('department_id', $department_id)->get() :  (!empty($this->selectedCompanyId) ? User::role(['employee'])->whereDoesntHave('roles', function($query) { $query->where('name', 'admin'); })->supervisor()->where('company_id', $this->selectedCompanyId)->get() : []),
                "manager" => $this->selectedDepartmentId != "all" ? User::role(['employee'])->whereDoesntHave('roles', function($query) { $query->where('name', 'admin'); })->select('id', 'first_name', 'last_name', 'department_id')->where('department_id', $department_id)->whereIn('company_id', auth()->user()->managerCompanies->pluck('id'))->get() : (!empty($this->selectedCompanyId) ? User::role(['employee'])->whereDoesntHave('roles', function($query) { $query->where('name', 'admin'); })->where('company_id', $this->selectedCompanyId)->whereIn('company_id', auth()->user()->managerCompanies->pluck('id'))->get() : User::role(['employee'])->whereDoesntHave('roles', function($query) { $query->where('name', 'admin'); })->whereIn('company_id', auth()->user()->managerCompanies->pluck('id'))->get()),
                "admin" => $this->selectedDepartmentId != "all" ? User::role(['employee'])->select('id', 'first_name', 'last_name', 'department_id')->where('department_id', $department_id)->get() : (!empty($this->selectedCompanyId) ? User::role(['employee'])->where('company_id', $this->selectedCompanyId)->get() : []),
                "deafult" => [],
            };
           
            $this->employee_id = 'all';
        }
    }

    public function generateReport()
    {
        auditLog(
            auth()->user(),
            'payslip_report',
            'web',
            ucfirst(auth()->user()->name) . __('Report generate for payslips')
        );

        // Create job using the service
        $filters = [
            'selectedCompanyId' => $this->selectedCompanyId,
            'selectedDepartmentId' => $this->selectedDepartmentId,
            'employee_id' => $this->employee_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'email_status' => $this->email_status,
            'sms_status' => $this->sms_status,
            'query_string' => $this->query_string,
        ];

        $job = ReportGenerationService::createJob(
            DownloadJob::TYPE_PAYSLIP_REPORT,
            $filters,
            ['format' => 'xlsx']
        );

        session()->flash('message', __('Report generation started! You can track progress in the Generate page.'));
    }

    public function downloadBulkPayslips()
    {
        if (!$this->employee_id || $this->employee_id === 'all') {
            session()->flash('error', __('Please select a specific employee for bulk download.'));
            return;
        }

        $filters = [
            'selectedCompanyId' => $this->selectedCompanyId,
            'selectedDepartmentId' => $this->selectedDepartmentId,
            'employee_id' => $this->employee_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
        ];

        $job = ReportGenerationService::createJob(
            DownloadJob::TYPE_BULK_PAYSLIP_DOWNLOAD,
            $filters,
            ['format' => 'zip']
        );

        session()->flash('message', __('Bulk download started! You can track progress in the Generate page.'));
    }

    public function render()
    {
        return view('livewire.portal.reports.payslip', [
            'payslips' => $this->buildQuery()->paginate($this->perPage),
            'payslips_count' => $this->buildQuery()->count()
        ])->layout('components.layouts.dashboard');
    }

    public function buildQuery()
    {

        // dd(!empty($this->email_status) && ($this->email_status == ModelsPayslip::STATUS_SUCCESSFUL));
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
            })->when($this->selectedCompanyId != "all" && $this->auth_role !== 'supervisor', function ($query) {
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
                    ->orWhere('sms_sent_status', ModelsPayslip::STATUS_FAILED)
                    ->orWhere('sms_sent_status', ModelsPayslip::STATUS_DISABLED);
            })->when(!empty($this->email_status) && $this->email_status == ModelsPayslip::STATUS_SUCCESSFUL, function ($query) {
                return $query->where('email_sent_status', ModelsPayslip::STATUS_SUCCESSFUL);
            })->when(!empty($this->email_status) && $this->email_status == ModelsPayslip::STATUS_PENDING, function ($query) {
                return $query->where('email_sent_status', ModelsPayslip::STATUS_PENDING);
            })->when(!empty($this->email_status) && $this->email_status == ModelsPayslip::STATUS_FAILED, function ($query) {
                return $query->where('email_sent_status',  ModelsPayslip::STATUS_FAILED);
            })->when(!empty($this->sms_status) && $this->sms_status == ModelsPayslip::SMS_STATUS_SUCCESSFUL, function ($query) {
                return $query->where('sms_sent_status', ModelsPayslip::STATUS_SUCCESSFUL);
            })->when(!empty($this->sms_status) && $this->sms_status == ModelsPayslip::SMS_STATUS_PENDING, function ($query) {
                return $query->where('sms_sent_status', ModelsPayslip::STATUS_PENDING);
            })->when(!empty($this->sms_status) && $this->sms_status == ModelsPayslip::SMS_STATUS_FAILED, function ($query) {
                return $query->where('sms_sent_status',  ModelsPayslip::STATUS_FAILED);
            })->when(!empty($this->sms_status) && $this->sms_status == 6, function ($query) {
                return $query->where('sms_sent_status', ModelsPayslip::STATUS_DISABLED);
            })->when(!empty($this->start_date) || !empty($this->end_date), function ($query) {
                return $query->whereBetween(DB::raw('date(created_at)'), [Carbon::parse($this->start_date)->toDateString(), Carbon::parse($this->end_date)->toDateString()]);
            });
    }
}
