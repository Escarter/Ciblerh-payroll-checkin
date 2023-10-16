<?php

namespace App\Livewire\Portal\Reports;

use App\Livewire\Traits\WithDataTable;
use PDF;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Company;
use Livewire\Component;
use App\Models\Department;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Overtime as EmployeeOvertime;

class Overtime extends Component
{
    use WithDataTable;

    public $companies = [];
    public $selectedCompanyId = null;
    public $selectedDepartmentId = '';
    public $departments = [];
    public $employees = [];
    public $employee_id = 'all';
    public $periods = [];
    public $period = null;
    public $status = 'all';
    public $auth_role ;

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
        
        $this->periods = collect(range(5, 0))->map(function ($i) {
            $dt = today()->startOfWeek()->subWeeks($i);
            return [
                'week' => $dt->weekOfYear,
                'month' => $dt->shortMonthName,
                'month_number' => $dt->format('m'),
                'year' => $dt->format('Y'),
                'week_number' => $dt->weekNumberInMonth,
                'month_number' => $dt->weekOfYear,
                'start_of_week' => $dt->startOfWeek(),
            ];
        });
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

    public function generateReport()
    {
        $this->validate([
            'period' => 'required',
            'selectedDepartmentId' => 'required'
        ]);

        $users = User::withAndWhereHas('overtimes', function ($overtime) {
            $overtime->when(!empty($this->selectedCompanyId) && $this->auth_role !== 'supervisor', function ($query) {
                return $query->where('company_id',  $this->selectedCompanyId);
            })->when(!empty($this->selectedDepartmentId), function ($query) {
                return $query->where('department_id',  $this->selectedDepartmentId);
            })->when($this->employee_id != "all", function ($query) {
                return $query->where('user_id',  $this->employee_id);
            })->when(!empty($this->status) && $this->status === "approved", function ($query) {
                return $query->where('approval_status',  EmployeeOvertime::APPROVAL_STATUS_APPROVED);
            })->when(!empty($this->status) && $this->status === "pending", function ($query) {
                return $query->where('approval_status',  EmployeeOvertime::APPROVAL_STATUS_PENDING);
            })->when(!empty($this->status) && $this->status === "rejected", function ($query) {
                return $query->where('approval_status',  EmployeeOvertime::APPROVAL_STATUS_REJECTED);
            })->when(!empty($this->period), function ($query) {
                return $query->whereBetween('start_time', [now()->isoWeek($this->period)->startOfWeek(), now()->isoWeek($this->period)->endOfWeek()]);
            })->orderBy('start_time', 'asc');
        })->get();

        // dd($this->period);


        // $users->each(function($user){
        //     dd($user);
        // });

        $department = Department::whereId($this->selectedDepartmentId)->first();

        set_time_limit(600);
        $data = [
            'date' => date('m/d/Y'),
            'users' => $users,
            'week' => $this->period,
            'supervisor' => !empty($department->depSupervisor) ? $department->depSupervisor->supervisor : '',
            'company' => Company::whereId($this->selectedCompanyId)->first(),
            'department' => Department::whereId($this->selectedDepartmentId)->first(),
        ];

        $pdf = PDF::loadView('livewire.portal.reports.partials.overtime-report-template', $data)->setPaper('letter', 'portrait')->setOptions(['dpi'=> '105']);


        return response()->streamDownload(
            fn () => print($pdf->output()),
            __('Overtimes-') . $this->period . "-" . Str::random('10') . ".pdf"
        );
    }

    public function render()
    {
        return view('livewire.portal.reports.overtime', [
            'overtimes' => $this->buildQuery()->paginate($this->perPage),
        ])->layout('components.layouts.dashboard');
    }


    public function buildQuery()
    {
        // dd($this->period);
        // dd(now()->isoWeek($this->period)->startOfWeek());÷
        return EmployeeOvertime::query()->with(['department', 'user', 'company'])
            ->when($this->selectedCompanyId != "all" && $this->auth_role !== 'supervisor', function ($query) {
                return $query->where('company_id',  $this->selectedCompanyId);
            })->when($this->selectedDepartmentId != "all", function ($query) {
                return $query->where('department_id',  $this->selectedDepartmentId);
            })->when($this->employee_id != "all", function ($query) {
                return $query->where('user_id',  $this->employee_id);
            })->when(!empty($this->status) && $this->status === "approved", function ($query) {
                return $query->where('approval_status',  EmployeeOvertime::APPROVAL_STATUS_APPROVED);
            })->when(!empty($this->status) && $this->status === "pending", function ($query) {
                return $query->where('approval_status',  EmployeeOvertime::APPROVAL_STATUS_PENDING);
            })->when(!empty($this->status) && $this->status === "rejected", function ($query) {
                return $query->where('approval_status',  EmployeeOvertime::APPROVAL_STATUS_REJECTED);
            })->when(!empty($this->period), function ($query) {
                 return $query->whereBetween('start_time', [now()->isoWeek($this->period)->startOfWeek(), now()->isoWeek($this->period)->endOfWeek()]);
            });
    }
}
