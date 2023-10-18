<?php

namespace App\Livewire\Portal\Dashboard;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Company;
use App\Models\Payslip;
use App\Models\Service;
use App\Models\Ticking;
use Livewire\Component;
use App\Models\AuditLog;
use App\Models\Department;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class Index extends Component
{
    use WithPagination;
    public $companies = [];
    public $selectedCompanyId = null;
    public $selectedDepartmentId = 'all';
    public $departments = [];
    public $period = 'all_time';
    public $role;

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

    public function prepareWeeklyChart($stats)
    {
        $weeks = [];
        $failed = [];
        $success = [];
        $pending = [];

        foreach ($stats as $stat) {
            if (!in_array('Wk - ' . $stat->week, $weeks, true)) {
                array_push($weeks, 'Wk - ' . $stat->week);
            }
            if ($stat->email_sent_status === Payslip::STATUS_SUCCESSFUL) {
                array_push($success, $stat->data);
            } elseif ($stat->email_sent_status === Payslip::STATUS_FAILED) {
                array_push($failed, $stat->data);
            } else {
                array_push($pending, $stat->data);
            }
        }
        // dd(json_encode(array_reverse($weeks)));

        return $chart_data = [json_encode($weeks), json_encode($success), json_encode($failed), json_encode($pending)];
    }
    public function prepareDailyChart($stats)
    {
        $days = [];
        $failed = [];
        $success = [];
        $pending = [];
        foreach ($stats as $stat) {
            if (!in_array(Carbon::parse($stat->day)->format('D'), $days, true)) {
                array_push($days, Carbon::parse($stat->day)->format('D'));
            }
            if ($stat->email_sent_status === Payslip::STATUS_SUCCESSFUL) {
                array_push($success, $stat->data);
            } elseif ($stat->email_sent_status === Payslip::STATUS_FAILED) {
                array_push($failed, $stat->data);
            } else {
                array_push($pending, $stat->data);
            }
        }
        // dd(json_encode(array_reverse($days)));

        return $chart_data = [json_encode($days), json_encode($success), json_encode($failed), json_encode($pending)];
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

        $payslips = Payslip::select('id', 'email_sent_status','department_id','created_at')->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
            return $q->where('department_id', $this->selectedDepartmentId);
        })->dateFilter('created_at', $this->period)->get();
        $payslips_last_month_count = Payslip::select('id', 'email_sent_status', 'created_at')->where('email_sent_status', 'failed')->orWhere('email_sent_status', 'successful')->whereBetween('created_at', [now()->startOfMonth()->subMonthNoOverflow(), now()->endOfMonth()])->count();

        $payslips_last_month_success_count = Payslip::select('id', 'email_sent_status', 'created_at')->where('email_sent_status', 'successful')->whereBetween('created_at', [now()->startOfMonth()->subMonthNoOverflow(), now()->endOfMonth()])->count();


        if (auth()->user()->hasRole('admin')) {
         
            $stats = Payslip::dateFilter('created_at',$this->period)
                ->select('email_sent_status', DB::raw('count(id) as `data`'), DB::raw('month(created_at) month'), DB::raw('week(created_at) week'))
                ->groupBy('email_sent_status', DB::raw('week(created_at)'), DB::raw('month(created_at)'))
                ->orderBy(DB::raw('week(created_at)'), 'asc')
                ->get();
            $day_stats = Payslip::dateFilter('created_at', $this->period)
                ->select('email_sent_status', DB::raw('count(id) as `data`'), DB::raw('date(created_at) day'), DB::raw('week(created_at) week'))
                ->groupBy('email_sent_status', DB::raw('date(created_at)'), DB::raw('week(created_at)'))
                ->orderBy(DB::raw('date(created_at)'), 'asc')
                ->get();

            
        
        } else {
         

            $stats = Payslip::where('user_id', auth()->user()->id)->whereBetween(DB::raw('month(created_at)'), [now()->startOfMonth()->subMonth(10)->month, now()->endOfMonth()->month])
                ->select('email_sent_status', DB::raw('count(id) as `data`'), DB::raw('month(created_at) month'), DB::raw('week(created_at) week'))
                ->groupBy('email_sent_status', DB::raw('week(created_at)'), DB::raw('month(created_at)'))
                ->orderBy(DB::raw('week(created_at)'), 'asc')
                ->get();
            $day_stats = Payslip::where('user_id', auth()->user()->id)->whereBetween(DB::raw('date(created_at)'), [now()->startOfWeek()->subDay(7), now()->endOfWeek()])
                ->select('email_sent_status', DB::raw('count(id) as `data`'), DB::raw('date(created_at) day'), DB::raw('week(created_at) week'))
                ->groupBy('email_sent_status', DB::raw('date(created_at)'), DB::raw('week(created_at)'))
                ->orderBy(DB::raw('date(created_at)'), 'asc')
                ->get();

            $payslips = Payslip::select('id', 'email_sent_status', 'created_at')->where('user_id', auth()->user()->id)->get();
            $payslips_last_month_count = Payslip::select('id', 'email_sent_status', 'created_at')->where('user_id', auth()->user()->id)->whereBetween('created_at', [now()->startOfMonth()->subMonthNoOverflow(), now()->endOfMonth()])->count();
            $payslips_last_month_success_count = Payslip::select('id', 'email_sent_status', 'created_at')->where('user_id', auth()->user()->id)->where('email_sent_status', 'successful')->whereBetween('created_at', [now()->startOfMonth()->subMonthNoOverflow(), now()->endOfMonth()])->count();

    
        }

        // dd(now()->startOfMonth()->subMonth(10)->month);

        $pie_chart = json_encode([
            array_sum(json_decode($this->prepareDailyChart($day_stats)[3])),
            array_sum(json_decode($this->prepareDailyChart($day_stats)[2])),
            array_sum(json_decode($this->prepareDailyChart($day_stats)[1]))
        ]);

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

            'payslips_success' => count($payslips->where('email_sent_status', 'successful')),
            'payslips_success_week' => count($payslips->where('email_sent_status', 'successful')->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])),
            'payslips_failed' => count($payslips->where('email_sent_status', 'failed')),
            'payslips_failed_week' => count($payslips->where('email_sent_status', 'failed')->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])),
            'payslips_last_month_total_count' => $payslips_last_month_count,
            'payslips_last_month_success_count' => $payslips_last_month_success_count,
            'chart_data' => $this->prepareWeeklyChart($stats),
            'chart_daily' => $this->prepareDailyChart($day_stats),
            'chart_pie_daily' => $pie_chart,

        ])->layout('components.layouts.dashboard');
    }
}
