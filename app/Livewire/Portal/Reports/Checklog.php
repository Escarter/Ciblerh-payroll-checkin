<?php

namespace App\Livewire\Portal\Reports;

use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Company;
use App\Models\Ticking;
use Livewire\Component;
use App\Models\Department;
use Illuminate\Support\Str;
use Livewire\WithPagination;

class Checklog extends Component
{
    use WithPagination;

    //DataTable props
    public ?string $query = null;
    public ?string $resultCount;
    public string $orderBy = 'created_at';
    public string $orderAsc = 'desc';
    public int $perPage = 15;

    protected $paginationTheme = "bootstrap";

    public $companies = [];
    public $selectedCompanyId = null;
    public $selectedDepartmentId = null;
    public $departments = [];
    public $employees = [];
    public $employee_id = 'all';
    public $periods = [];
    public $period = null;
    public $status = 'all';
    public $auth_role;


    public function mount() 
    {
        $this->companies  = match(auth()->user()->getRoleNames()->first()){
            "manager" => Company::manager()->orderBy('name', 'desc')->get(),
            "admin" => Company::orderBy('name','desc')->get(),
            "supervisor" => [],
            "deafult" => [],
        };
        $this->departments = match(auth()->user()->getRoleNames()->first()){
            "supervisor" => Department::supervisor()->get(),
            "manager" => [],
            "admin" => [],
            "deafult" => [],
        };
        $this->auth_role = auth()->user()->getRoleNames()->first();

        $this->periods = collect(range(5, 0))->map(function ($i) {
            $dt = today()->startOfMonth()->subMonths($i);
            return [
                'month' => $dt->shortMonthName,
                'month_number' => $dt->format('m'),
                'year' => $dt->format('Y')
            ];
        });
    }

    public function updatedSelectedCompanyId($company_id)
    {
        if (!is_null($company_id)) {
            $this->departments = match ($this->auth_role) {
                "supervisor" => Department::supervisor()->where('company_id', $company_id)->get(),
                "manager" => Department::manager()->where('company_id', $company_id)->get(),
                "admin" => Department::where('company_id',$company_id)->get(),
                "deafult" => [],
            };
        }
    }

    public function generateReport()
    {
        $this->validate([
            'period' => 'required',
            'selectedDepartmentId' =>'required'
        ]);

        $month = !empty($this->period) ? $this->period : '2022-04';
        $start = Carbon::parse($month)->startOfMonth();
        $end = Carbon::parse($month)->endOfMonth();

        $dates = [];
        while ($start->lte($end)) {
            $dates[] = $start->copy();
            $start->addDay();    
        }

        $users = User::whereHas('tickings')->with('tickings', function ($ticking) use ($month) {
           $ticking->when($this->selectedCompanyId != "all" && $this->auth_role !== 'supervisor', function ($query) {
                return $query->where('company_id',  $this->selectedCompanyId);
            })->when($this->selectedDepartmentId != "all", function ($query) {
                return $query->where('department_id',  $this->selectedDepartmentId);
            })->when($this->employee_id != "all", function ($query) {
                return $query->where('user_id',  $this->employee_id);
            })->when($this->status != "all" && $this->status === "approved", function ($query) {
                return $query->where('manager_approval_status',  Ticking::MANAGER_APPROVAL_APPROVED)->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_APPROVED);
            })->when($this->status != "all" && $this->status === "pending", function ($query) {
                return $query->where('manager_approval_status',  Ticking::MANAGER_APPROVAL_PENDING)
                    ->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_PENDING);
            })->when($this->status != "all" && $this->status === "rejected", function ($query) {
                return $query->where('manager_approval_status',  Ticking::MANAGER_APPROVAL_REJECTED)
                    ->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_REJECTED);
            })->when(!empty($this->period), function ($query) {
                return $query->whereYear('start_time', '=', explode('-', $this->period)[0])->whereMonth('start_time', '=', explode('-', $this->period)[1]);
            })->orderBy('start_time', 'asc');

        })->get();

        // dd(Company::findOrFail($this->selectedCompanyId)->supervisor);

        $department = Department::whereId($this->selectedDepartmentId)->first();
    
        set_time_limit(600);
        $data = [
            'date' => date('m/d/Y'),
            'month'=> !empty($this->period) ? explode('-', $this->period)[1] : "01",
            'dates' => $dates,
            'users' => $users,
            'supervisor' => !empty($department->depSupervisor) ? $department->depSupervisor->supervisor : '',
            'company'=> Company::whereId($this->selectedCompanyId)->first(),
            'department'=> Department::whereId($this->selectedDepartmentId)->first(),
        ];

        $pdf = PDF::loadView('livewire.portal.reports.partials.checkin-report-template', $data)->setPaper('letter', 'landscape');

    
        return response()->streamDownload(
            fn () => print($pdf->output()),
            __('Checkins-').$this->period."-". Str::random('10') . ".pdf"
        );
    }
    public function updatedSelectedDepartmentId($department_id)
    {
        if (!is_null($department_id)) {
            $this->employees  = match ($this->auth_role) {
                "supervisor" => User::supervisor()->where('department_id',$department_id)->get(),
                "manager" => User::manager()->where('department_id',$department_id)->get(),
                "admin" => User::where('department_id',$department_id)->get(),
                "deafult" => [],
            };
        }
    }
    
    public function render()
    {
        // foreach ($this->periods as $key => $value) {
        //     dd($value);
        // }
        return view('livewire.portal.reports.checklog',[
            'checklogs' => $this->buildQuery()->paginate($this->perPage),
        ])->layout('components.layouts.dashboard');
    }


    public function buildQuery()
    {
        // dd(explode('-', $this->period)[1]);
        return Ticking::query()->with(['department','user','company','service'])
        ->when($this->selectedCompanyId != "all" && $this->auth_role !== "supervisor", function ($query) {
            return $query->where('company_id',  $this->selectedCompanyId);
        })->when($this->selectedDepartmentId != "all", function ($query) {
            return $query->where('department_id',  $this->selectedDepartmentId);
        })->when($this->employee_id != "all", function ($query) {
            return $query->where('user_id',  $this->employee_id);
        })->when($this->status != "all" && $this->status === "approved", function ($query) {
            return $query->where('manager_approval_status',  Ticking::MANAGER_APPROVAL_APPROVED)->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_APPROVED);
        })->when($this->status != "all" && $this->status === "pending", function ($query) {
            return $query->where('manager_approval_status',  Ticking::MANAGER_APPROVAL_PENDING)
                ->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_PENDING);
        })->when($this->status != "all" && $this->status === "rejected", function ($query) {
            return $query->where('manager_approval_status',  Ticking::MANAGER_APPROVAL_REJECTED)
                ->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_REJECTED);
        })->when(!empty($this->period),function($query){
            return $query->whereYear('start_time','=',explode('-',$this->period)[0])->whereMonth('start_time','=',explode('-',$this->period)[1]);
        });
    }
}
