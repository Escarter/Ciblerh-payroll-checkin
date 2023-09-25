<?php

namespace App\Livewire\Portal\Dashboard;

use App\Models\User;
use App\Models\Company;
use App\Models\Service;
use App\Models\Ticking;
use Livewire\Component;
use App\Models\AuditLog;
use App\Models\Department;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;
    public $companies = [];
    public $selectedCompanyId = null;
    public $selectedDepartmentId = 'all';
    public $departments = [];
    public $period = 'all_time';
    public $role;

    protected $paginationTheme = "bootstrap";

    public function mount()
    {
        $this->role = auth()->user()->getRoleNames()->first();
        $this->companies = match(auth()->user()->getRoleNames()->first()){
            'manager' => Company::manager()->orderBy('created_at', 'desc')->get(),
            'admin' => Company::orderBy('created_at', 'desc')->get(),
            'supervisor' => [],
            default => [],
        };

        $this->departments =  match (auth()->user()->getRoleNames()->first()) {
            'manager' => Department::manager()->orderBy('created_at', 'desc')->get(),
            'supervisor' => Department::whereIn('id', auth()->user()->supDepartments->pluck('department_id'))->get(),
            'admin' => [],
            default => [],
        };

    }

    public function updatedSelectedCompanyId($company_id)
    {
        if (!is_null($company_id)) {
            $this->departments = Department::where('company_id', $company_id)->get();
        }
    }

    public function render()
    {
        $checklogs = match ($this->role) {
            "supervisor" => Ticking::supervisor()->with('user')->orderBy('start_time', 'desc')->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                return $q->where('department_id', $this->selectedDepartmentId);
            })->dateFilter('created_at',$this->period)->get()->unique('user_id')->take(20),
            "manager" => Ticking::manager()->with('user')->orderBy('start_time', 'desc')->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                return $q->where('department_id', $this->selectedDepartmentId);
            })->dateFilter('created_at',$this->period)->get()->unique('user_id')->take(20),
            "admin" => Ticking::with('user')->orderBy('start_time', 'desc')->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                return $q->where('department_id', $this->selectedDepartmentId);
            })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
                return $q->where('company_id', $this->selectedCompanyId);
            })->dateFilter('created_at',$this->period)->get()->unique('user_id')->take(20),
            default => [],
        };


        $logs = match ($this->role) {
            "supervisor" => AuditLog::whereUserId(auth()->user()->id)->orderBy('created_at','desc')->dateFilter('created_at',$this->period)->get()->take(10),
            "manager" => AuditLog::manager()->orderBy('created_at', 'desc')->dateFilter('created_at', $this->period)->get()->take(10),
            "admin" => AuditLog::orderBy('created_at','desc')->dateFilter('created_at',$this->period)->get()->take(10),
            "default"=> [],
        };

            // dd($checklogs);
        return view('livewire.portal.dashboard.index',[
            'checklogs' => $checklogs,
            'logs' => $logs,

            'total_companies' => match ($this->role) {
                "admin" => Company::when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
                    return $q->where('id', $this->selectedCompanyId);
                })->dateFilter('created_at',$this->period)->count(),
                "manager" => Company::manager()->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
                    return $q->where('id', $this->selectedCompanyId);
                })->dateFilter('created_at',$this->period)->count(),
                'supervisor' => [],
               default => [],
            },

            'total_departments' => match ($this->role) {
                "supervisor" => Department::supervisor()->dateFilter('created_at',$this->period)->count(),
                "manager" => Department::manager()->when(!empty($this->selectedCompanyId), function($q) { 
                    return $q->where('company_id', $this->selectedCompanyId);
                })->dateFilter('created_at',$this->period)->count(),
                "admin" => Department::when(!empty($this->selectedCompanyId), function($q) { 
                    return $q->where('company_id', $this->selectedCompanyId);
                })->dateFilter('created_at',$this->period)->count(),
               default => [],
            },

            'total_services' => match ($this->role) {
                "supervisor" => Service::supervisor()->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function($q) { 
                        return $q->where('department_id', $this->selectedDepartmentId);
                })->dateFilter('created_at',$this->period)->count(),
                "manager" => Service::manager()->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function($q) { 
                        return $q->where('department_id', $this->selectedDepartmentId);
                })->dateFilter('created_at',$this->period)->count(),
                "admin" => Service::when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function($q) { 
                    return $q->where('department_id', $this->selectedDepartmentId);
                })->when(!empty($this->selectedCompanyId), function ($q) {
                    return $q->where('company_id', $this->selectedCompanyId);
                })->dateFilter('created_at', $this->period)->count(),
                "default"=> [],
            },

            'total_employees' => match ($this->role) {
                "supervisor" => User::supervisor()->with('role')->role(['employee'])->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                return $q->where('department_id', $this->selectedDepartmentId);
                })->dateFilter('created_at', $this->period)->count(),
                "manager" => User::manager()->with('role')->role(['employee'])->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                return $q->where('department_id', $this->selectedDepartmentId);
                })->when(!empty($this->selectedCompanyId), function ($q) {
                    return $q->where('company_id', $this->selectedCompanyId);
                })->dateFilter('created_at', $this->period)->count(),
                "admin" => User::with('role')->role(['employee'])->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                return $q->where('department_id', $this->selectedDepartmentId);
                })->when(!empty($this->selectedCompanyId), function ($q) {
                    return $q->where('company_id', $this->selectedCompanyId);
                })->dateFilter('created_at', $this->period)->count(),
                "default"=>[],
            },

            'checklogs_count' => match ($this->role) {
                "supervisor" => Ticking::supervisor()->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                return $q->where('department_id', $this->selectedDepartmentId);
                })->dateFilter('created_at', $this->period)->count(), 
                "manager" => Ticking::manager()->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function($q) { 
                    return $q->where('department_id', $this->selectedDepartmentId);
                })->when(!empty($this->selectedCompanyId), function ($q) {
                    return $q->where('company_id', $this->selectedCompanyId);
                })->dateFilter('created_at', $this->period)->count(),
                "admin" => Ticking::when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function($q) { 
                    return $q->where('department_id', $this->selectedDepartmentId);
                })->when(!empty($this->selectedCompanyId), function ($q) {
                    return $q->where('company_id', $this->selectedCompanyId);
                })->dateFilter('created_at', $this->period)->count(),
               default => []
            },

            'pending_checklogs_count' => match ($this->role) {
                "supervisor" => Ticking::supervisor()->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_PENDING)
                ->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function($q) { 
                    return $q->where('department_id', $this->selectedDepartmentId);
                })->dateFilter('created_at',$this->period)->count(),
                "manager" => Ticking::manager()->where('manager_approval_status', Ticking::MANAGER_APPROVAL_PENDING)
                ->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function($q) { 
                    return $q->where('department_id', $this->selectedDepartmentId);
                })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
                    return $q->where('company_id', $this->selectedCompanyId);
                })->dateFilter('created_at',$this->period)->count(),
                "admin" => Ticking::where('manager_approval_status', Ticking::MANAGER_APPROVAL_PENDING)
                ->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function($q) { 
                    return $q->where('department_id', $this->selectedDepartmentId);
                })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
                    return $q->where('company_id', $this->selectedCompanyId);
                })->dateFilter('created_at',$this->period)->count(),
               default => []
            },

            'approved_checklogs_count' => match ($this->role) {
                "supervisor" =>  Ticking::supervisor()->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_APPROVED)->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function($q) { 
                    return $q->where('department_id', $this->selectedDepartmentId);
                })->dateFilter('created_at',$this->period)->count(),
                "manager" => Ticking::manager()->where('manager_approval_status', Ticking::MANAGER_APPROVAL_APPROVED)->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function($q) { 
                    return $q->where('department_id', $this->selectedDepartmentId);
                })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
                    return $q->where('company_id', $this->selectedCompanyId);
                })->dateFilter('created_at',$this->period)->count(),
                "admin" => Ticking::where('manager_approval_status', Ticking::MANAGER_APPROVAL_APPROVED)->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function($q) { 
                    return $q->where('department_id', $this->selectedDepartmentId);
                })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
                    return $q->where('company_id', $this->selectedCompanyId);
                })->dateFilter('created_at',$this->period)->count(),
                "default"=> [],
            },

            'rejected_checklogs_count' => match ($this->role) {
                "supervisor" => Ticking::supervisor()->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_REJECTED)->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function($q) { 
                    return $q->where('department_id', $this->selectedDepartmentId);
                })->dateFilter('created_at',$this->period)->count(),
                "manager" => Ticking::where('manager_approval_status', Ticking::MANAGER_APPROVAL_REJECTED)->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function($q) { 
                    return $q->where('department_id', $this->selectedDepartmentId);
                })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
                    return $q->where('company_id', $this->selectedCompanyId);
                })->dateFilter('created_at',$this->period)->count(),
                "admin" => Ticking::where('manager_approval_status', Ticking::MANAGER_APPROVAL_REJECTED)->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function($q) { 
                    return $q->where('department_id', $this->selectedDepartmentId);
                })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
                    return $q->where('company_id', $this->selectedCompanyId);
                })->dateFilter('created_at',$this->period)->count(),
            },

        ])->layout('components.layouts.dashboard');
    }
}
