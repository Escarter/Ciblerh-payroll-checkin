<?php

namespace App\Livewire\Portal\Dashboard;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Company;
use App\Models\Payslip;
use App\Models\Service;
use App\Models\Ticking;
use App\Models\Absence;
use App\Models\Leave;
use App\Models\Overtime;
use App\Models\AdvanceSalary;
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
        $this->companies = match (auth()->user()->getRoleNames()->first()) {
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
        $this->dispatch('charts-updated');
    }

    public function updatedSelectedDepartmentId($department_id)
    {
        $this->dispatch('charts-updated');
    }

    public function updatedPeriod($period)
    {
        $this->dispatch('charts-updated');
    }

    public function getChartData()
    {
        // Get payslip data for charts
        $stats = Payslip::when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
            return $q->where('company_id', $this->selectedCompanyId);
        })->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
            return $q->where('department_id', $this->selectedDepartmentId);
        })->selectRaw('WEEK(created_at) as week, email_sent_status, COUNT(*) as data')
            ->groupBy('week', 'email_sent_status')
            ->orderBy('week')
            ->get();

        $day_stats = Payslip::when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
            return $q->where('company_id', $this->selectedCompanyId);
        })->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
            return $q->where('department_id', $this->selectedDepartmentId);
        })->selectRaw('DAY(created_at) as day, email_sent_status, COUNT(*) as data')
            ->groupBy('day', 'email_sent_status')
            ->orderBy('day')
            ->get();

        return [
            'approval_pie_chart' => $this->getApprovalStatusPieChart(),
            'department_comparison' => $this->getDepartmentComparisonChart(),
            'monthly_trends' => $this->getMonthlyTrendsChart(),
            'chart_data' => $this->prepareWeeklyChart($stats),
            'chart_daily' => $this->prepareDailyChart($day_stats),
        ];
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

    // New methods for richer dashboard metrics
    public function calculateAttendanceRate($period = 'last_month')
    {
        $dateFilter = match ($period) {
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_week' => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
            'this_week' => [now()->startOfWeek(), now()->endOfWeek()],
            default => [now()->startOfMonth(), now()->endOfMonth()]
        };

        $totalEmployees = User::when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
            return $q->where('department_id', $this->selectedDepartmentId);
        })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
            return $q->where('company_id', $this->selectedCompanyId);
        })->count();

        $totalWorkingDays = $this->getWorkingDaysInPeriod($dateFilter[0], $dateFilter[1]);
        $expectedCheckins = $totalEmployees * $totalWorkingDays;

        $actualCheckins = Ticking::when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
            return $q->where('department_id', $this->selectedDepartmentId);
        })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
            return $q->where('company_id', $this->selectedCompanyId);
        })->whereBetween('start_time', $dateFilter)->count();

        return $expectedCheckins > 0 ? round(($actualCheckins / $expectedCheckins) * 100, 2) : 0;
    }

    public function calculateLeaveUtilizationRate()
    {
        $totalAllocatedDays = User::when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
            return $q->where('department_id', $this->selectedDepartmentId);
        })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
            return $q->where('company_id', $this->selectedCompanyId);
        })->sum('monthly_leave_allocation');

        $totalUsedDays = Leave::when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
            return $q->where('department_id', $this->selectedDepartmentId);
        })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
            return $q->where('company_id', $this->selectedCompanyId);
        })->where('supervisor_approval_status', Leave::SUPERVISOR_APPROVAL_APPROVED)
            ->where('manager_approval_status', Leave::MANAGER_APPROVAL_APPROVED)
            ->sum(DB::raw('DATEDIFF(end_date, start_date) + 1'));

        return $totalAllocatedDays > 0 ? round(($totalUsedDays / $totalAllocatedDays) * 100, 2) : 0;
    }

    public function getDepartmentPerformanceMetrics()
    {
        $departments = Department::when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
            return $q->where('company_id', $this->selectedCompanyId);
        })->withCount('employees')->get();

        return $departments->map(function ($dept) {
            // Get actual counts for this department
            $totalCheckins = Ticking::where('department_id', $dept->id)
                ->whereBetween('start_time', [now()->startOfMonth(), now()->endOfMonth()])
                ->count();

            $totalLeaves = Leave::where('department_id', $dept->id)
                ->whereBetween('start_date', [now()->startOfMonth(), now()->endOfMonth()])
                ->count();

            $totalOvertimes = Overtime::where('department_id', $dept->id)
                ->whereBetween('start_time', [now()->startOfMonth(), now()->endOfMonth()])
                ->count();

            $dept->total_checkins = $totalCheckins;
            $dept->total_leaves = $totalLeaves;
            $dept->total_overtimes = $totalOvertimes;

            $dept->attendance_rate = $dept->employees_count > 0 ?
                round(($totalCheckins / ($dept->employees_count * now()->daysInMonth)) * 100, 2) : 0;
            $dept->overtime_rate = $dept->employees_count > 0 ?
                round(($totalOvertimes / $dept->employees_count) * 100, 2) : 0;

            return $dept;
        });
    }

    public function getPendingApprovalsCount()
    {
        $pendingCheckins = Ticking::when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
            return $q->where('department_id', $this->selectedDepartmentId);
        })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
            return $q->where('company_id', $this->selectedCompanyId);
        })->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_PENDING)
            ->orWhere('manager_approval_status', Ticking::MANAGER_APPROVAL_PENDING)->count();

        $pendingLeaves = Leave::when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
            return $q->where('department_id', $this->selectedDepartmentId);
        })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
            return $q->where('company_id', $this->selectedCompanyId);
        })->where('supervisor_approval_status', Leave::SUPERVISOR_APPROVAL_PENDING)
            ->orWhere('manager_approval_status', Leave::MANAGER_APPROVAL_PENDING)->count();

        $pendingOvertimes = Overtime::when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
            return $q->where('department_id', $this->selectedDepartmentId);
        })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
            return $q->where('company_id', $this->selectedCompanyId);
        })->where('approval_status', Overtime::APPROVAL_STATUS_PENDING)->count();

        $pendingAdvances = AdvanceSalary::when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
            return $q->where('department_id', $this->selectedDepartmentId);
        })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
            return $q->where('company_id', $this->selectedCompanyId);
        })->where('approval_status', AdvanceSalary::APPROVAL_STATUS_PENDING)->count();

        return [
            'checkins' => $pendingCheckins,
            'leaves' => $pendingLeaves,
            'overtimes' => $pendingOvertimes,
            'advances' => $pendingAdvances,
            'total' => $pendingCheckins + $pendingLeaves + $pendingOvertimes + $pendingAdvances
        ];
    }

    public function getTopPerformers()
    {
        return User::when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
            return $q->where('department_id', $this->selectedDepartmentId);
        })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
            return $q->where('company_id', $this->selectedCompanyId);
        })->withCount([
            'tickings as monthly_checkins' => function ($query) {
                $query->whereBetween('start_time', [now()->startOfMonth(), now()->endOfMonth()]);
            },
            'overtimes as monthly_overtimes' => function ($query) {
                $query->whereBetween('start_time', [now()->startOfMonth(), now()->endOfMonth()])
                    ->where('approval_status', Overtime::APPROVAL_STATUS_APPROVED);
            }
        ])->orderBy('monthly_checkins', 'desc')->limit(5)->get();
    }

    public function getAttendanceHeatmapData()
    {
        $heatmapData = [];
        $startDate = now()->subDays(30);

        for ($i = 0; $i < 30; $i++) {
            $date = $startDate->copy()->addDays($i);
            $checkins = Ticking::when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                return $q->where('department_id', $this->selectedDepartmentId);
            })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
                return $q->where('company_id', $this->selectedCompanyId);
            })->whereDate('start_time', $date)->count();

            $totalEmployees = User::when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                return $q->where('department_id', $this->selectedDepartmentId);
            })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
                return $q->where('company_id', $this->selectedCompanyId);
            })->count();

            $attendanceRate = $totalEmployees > 0 ? ($checkins / $totalEmployees) * 100 : 0;

            $heatmapData[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('D'),
                'checkins' => $checkins,
                'attendance_rate' => round($attendanceRate, 2),
                'intensity' => $this->getIntensityLevel($attendanceRate)
            ];
        }

        return $heatmapData;
    }

    private function getIntensityLevel($rate)
    {
        if ($rate >= 90) return 'high';
        if ($rate >= 70) return 'medium';
        if ($rate >= 50) return 'low';
        return 'very-low';
    }

    private function getWorkingDaysInPeriod($startDate, $endDate)
    {
        $workingDays = 0;
        $current = $startDate->copy();

        while ($current->lte($endDate)) {
            if ($current->isWeekday()) {
                $workingDays++;
            }
            $current->addDay();
        }

        return $workingDays;
    }

    // Additional chart methods for richer visualizations
    public function getApprovalStatusPieChart()
    {
        $pendingCheckins = Ticking::when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
            return $q->where('department_id', $this->selectedDepartmentId);
        })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
            return $q->where('company_id', $this->selectedCompanyId);
        })->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_PENDING)
            ->orWhere('manager_approval_status', Ticking::MANAGER_APPROVAL_PENDING)->count();

        $approvedCheckins = Ticking::when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
            return $q->where('department_id', $this->selectedDepartmentId);
        })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
            return $q->where('company_id', $this->selectedCompanyId);
        })->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_APPROVED)
            ->where('manager_approval_status', Ticking::MANAGER_APPROVAL_APPROVED)->count();

        $rejectedCheckins = Ticking::when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
            return $q->where('department_id', $this->selectedDepartmentId);
        })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
            return $q->where('company_id', $this->selectedCompanyId);
        })->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_REJECTED)
            ->orWhere('manager_approval_status', Ticking::MANAGER_APPROVAL_REJECTED)->count();

        return [
            'labels' => ['Pending', 'Approved', 'Rejected'],
            'data' => [$pendingCheckins, $approvedCheckins, $rejectedCheckins],
            'colors' => ['#ffc107', '#28a745', '#dc3545']
        ];
    }

    public function getDepartmentComparisonChart()
    {
        $departments = Department::when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
            return $q->where('company_id', $this->selectedCompanyId);
        })->withCount('employees')->get();

        $labels = [];
        $attendanceData = [];
        $overtimeData = [];
        $employeeData = [];

        foreach ($departments as $dept) {
            $labels[] = substr($dept->name, 0, 10); // Truncate long names

            $checkins = Ticking::where('department_id', $dept->id)
                ->whereBetween('start_time', [now()->startOfMonth(), now()->endOfMonth()])
                ->count();

            $overtimes = Overtime::where('department_id', $dept->id)
                ->whereBetween('start_time', [now()->startOfMonth(), now()->endOfMonth()])
                ->count();

            $attendanceRate = $dept->employees_count > 0 ?
                round(($checkins / ($dept->employees_count * now()->daysInMonth)) * 100, 2) : 0;

            $attendanceData[] = $attendanceRate;
            $overtimeData[] = $overtimes;
            $employeeData[] = $dept->employees_count;
        }

        return [
            'labels' => $labels,
            'attendance' => $attendanceData,
            'overtime' => $overtimeData,
            'employees' => $employeeData
        ];
    }

    public function getMonthlyTrendsChart()
    {
        $months = [];
        $attendanceTrends = [];
        $overtimeTrends = [];
        $leaveTrends = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $months[] = $month->format('M Y');

            $startOfMonth = $month->startOfMonth();
            $endOfMonth = $month->endOfMonth();

            $checkins = Ticking::when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                return $q->where('department_id', $this->selectedDepartmentId);
            })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
                return $q->where('company_id', $this->selectedCompanyId);
            })->whereBetween('start_time', [$startOfMonth, $endOfMonth])->count();

            $overtimes = Overtime::when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                return $q->where('department_id', $this->selectedDepartmentId);
            })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
                return $q->where('company_id', $this->selectedCompanyId);
            })->whereBetween('start_time', [$startOfMonth, $endOfMonth])->count();

            $leaves = Leave::when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                return $q->where('department_id', $this->selectedDepartmentId);
            })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
                return $q->where('company_id', $this->selectedCompanyId);
            })->whereBetween('start_date', [$startOfMonth, $endOfMonth])->count();

            $attendanceTrends[] = $checkins;
            $overtimeTrends[] = $overtimes;
            $leaveTrends[] = $leaves;
        }

        return [
            'labels' => $months,
            'attendance' => $attendanceTrends,
            'overtime' => $overtimeTrends,
            'leaves' => $leaveTrends
        ];
    }

    public function getTopDepartmentsByPerformance()
    {
        return Department::when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
            return $q->where('company_id', $this->selectedCompanyId);
        })->withCount('employees')->get()->map(function ($dept) {
            $checkins = Ticking::where('department_id', $dept->id)
                ->whereBetween('start_time', [now()->startOfMonth(), now()->endOfMonth()])
                ->count();

            $dept->performance_score = $dept->employees_count > 0 ?
                round(($checkins / ($dept->employees_count * now()->daysInMonth)) * 100, 2) : 0;

            return $dept;
        })->sortByDesc('performance_score')->take(5);
    }

    public function render()
    {
        $checklogs = match ($this->role) {
            "supervisor" => Ticking::supervisor()->with('user')->orderBy('start_time', 'desc')->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                return $q->where('department_id', $this->selectedDepartmentId);
            })->dateFilter('created_at', $this->period)->get()->unique('user_id')->take(20),
            "manager" => Ticking::manager()->with('user')->orderBy('start_time', 'desc')->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                return $q->where('department_id', $this->selectedDepartmentId);
            })->dateFilter('created_at', $this->period)->get()->unique('user_id')->take(20),
            "admin" => Ticking::with('user')->orderBy('start_time', 'desc')->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                return $q->where('department_id', $this->selectedDepartmentId);
            })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
                return $q->where('company_id', $this->selectedCompanyId);
            })->dateFilter('created_at', $this->period)->get()->unique('user_id')->take(20),
            default => [],
        };


        $logs = match ($this->role) {
            "supervisor" => AuditLog::whereUserId(auth()->user()->id)->orderBy('created_at', 'desc')->dateFilter('created_at', $this->period)->get()->take(10),
            "manager" => AuditLog::manager()->orderBy('created_at', 'desc')->dateFilter('created_at', $this->period)->get()->take(10),
            "admin" => AuditLog::orderBy('created_at', 'desc')->dateFilter('created_at', $this->period)->get()->take(10),
            "default" => [],
        };

        $payslips = Payslip::select('id', 'email_sent_status', 'department_id', 'created_at')->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
            return $q->where('department_id', $this->selectedDepartmentId);
        })->dateFilter('created_at', $this->period)->get();

        $payslips_last_month_count = Payslip::select('id', 'email_sent_status', 'created_at')->where('email_sent_status', Payslip::STATUS_FAILED)->orWhere('email_sent_status', Payslip::STATUS_SUCCESSFUL)->whereBetween('created_at', [now()->startOfMonth()->subMonthNoOverflow(), now()->endOfMonth()])->count();

        $payslips_last_month_success_count = Payslip::select('id', 'email_sent_status', 'created_at')->where('email_sent_status', Payslip::STATUS_SUCCESSFUL)->whereBetween('created_at', [now()->startOfMonth()->subMonthNoOverflow(), now()->endOfMonth()])->count();


        if (auth()->user()->hasRole('admin')) {

            $stats = Payslip::when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                return $q->where('department_id', $this->selectedDepartmentId);
            })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
                return $q->where('company_id', $this->selectedCompanyId);
            })->dateFilter('created_at', $this->period)->dateFilter('created_at', $this->period)
                ->select('email_sent_status', DB::raw('count(id) as `data`'), DB::raw('month(created_at) month'), DB::raw('week(created_at) week'))
                ->groupBy('email_sent_status', DB::raw('week(created_at)'), DB::raw('month(created_at)'))
                ->orderBy(DB::raw('week(created_at)'), 'asc')
                ->get();
            $day_stats = Payslip::when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                return $q->where('department_id', $this->selectedDepartmentId);
            })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
                return $q->where('company_id', $this->selectedCompanyId);
            })->dateFilter('created_at', $this->period)
                ->select('email_sent_status', DB::raw('count(id) as `data`'), DB::raw('date(created_at) day'), DB::raw('week(created_at) week'))
                ->groupBy('email_sent_status', DB::raw('date(created_at)'), DB::raw('week(created_at)'))
                ->orderBy(DB::raw('date(created_at)'), 'asc')
                ->get();
        } else {

            $stats = Payslip::where('user_id', auth()->user()->id)->whereBetween(DB::raw('month(created_at)'), [now()->startOfMonth()->subMonths(10)->month, now()->endOfMonth()->month])
                ->select('email_sent_status', DB::raw('count(id) as `data`'), DB::raw('month(created_at) month'), DB::raw('week(created_at) week'))
                ->groupBy('email_sent_status', DB::raw('week(created_at)'), DB::raw('month(created_at)'))
                ->orderBy(DB::raw('week(created_at)'), 'asc')
                ->get();
            $day_stats = Payslip::where('user_id', auth()->user()->id)->whereBetween(DB::raw('date(created_at)'), [now()->startOfWeek()->subDays(7), now()->endOfWeek()])
                ->select('email_sent_status', DB::raw('count(id) as `data`'), DB::raw('date(created_at) day'), DB::raw('week(created_at) week'))
                ->groupBy('email_sent_status', DB::raw('date(created_at)'), DB::raw('week(created_at)'))
                ->orderBy(DB::raw('date(created_at)'), 'asc')
                ->get();

            $payslips = Payslip::select('id', 'email_sent_status', 'created_at')->where('user_id', auth()->user()->id)->get();
            $payslips_last_month_count = Payslip::select('id', 'email_sent_status', 'created_at')->where('user_id', auth()->user()->id)->whereBetween('created_at', [now()->startOfMonth()->subMonthNoOverflow(), now()->endOfMonth()])->count();
            $payslips_last_month_success_count = Payslip::select('id', 'email_sent_status', 'created_at')->where('user_id', auth()->user()->id)->where('email_sent_status', Payslip::STATUS_SUCCESSFUL)->whereBetween('created_at', [now()->startOfMonth()->subMonthNoOverflow(), now()->endOfMonth()])->count();
        }

        // dd(now()->startOfMonth()->subMonth(10)->month);

        $pie_chart = json_encode([
            array_sum(json_decode($this->prepareDailyChart($day_stats)[3])),
            array_sum(json_decode($this->prepareDailyChart($day_stats)[2])),
            array_sum(json_decode($this->prepareDailyChart($day_stats)[1]))
        ]);

        // dd($checklogs);
        return view('livewire.portal.dashboard.index', [
            'checklogs' => $checklogs,
            'logs' => $logs,

            'total_companies' => match ($this->role) {
                "admin" => Company::when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
                    return $q->where('id', $this->selectedCompanyId);
                })->dateFilter('created_at', $this->period)->count(),
                "manager" => Company::manager()->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
                    return $q->where('id', $this->selectedCompanyId);
                })->dateFilter('created_at', $this->period)->count(),
                'supervisor' => [],
                default => [],
            },

            'total_departments' => match ($this->role) {
                "supervisor" => Department::supervisor()->dateFilter('created_at', $this->period)->count(),
                "manager" => Department::manager()->when(!empty($this->selectedCompanyId), function ($q) {
                    return $q->where('company_id', $this->selectedCompanyId);
                })->dateFilter('created_at', $this->period)->count(),
                "admin" => Department::when(!empty($this->selectedCompanyId), function ($q) {
                    return $q->where('company_id', $this->selectedCompanyId);
                })->dateFilter('created_at', $this->period)->count(),
                default => [],
            },

            'total_services' => match ($this->role) {
                "supervisor" => Service::supervisor()->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                    return $q->where('department_id', $this->selectedDepartmentId);
                })->dateFilter('created_at', $this->period)->count(),
                "manager" => Service::manager()->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                    return $q->where('department_id', $this->selectedDepartmentId);
                })->dateFilter('created_at', $this->period)->count(),
                "admin" => Service::when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                    return $q->where('department_id', $this->selectedDepartmentId);
                })->when(!empty($this->selectedCompanyId), function ($q) {
                    return $q->where('company_id', $this->selectedCompanyId);
                })->dateFilter('created_at', $this->period)->count(),
                "default" => [],
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
                "default" => [],
            },

            'checklogs_count' => match ($this->role) {
                "supervisor" => Ticking::supervisor()->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                    return $q->where('department_id', $this->selectedDepartmentId);
                })->dateFilter('created_at', $this->period)->count(),
                "manager" => Ticking::manager()->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                    return $q->where('department_id', $this->selectedDepartmentId);
                })->when(!empty($this->selectedCompanyId), function ($q) {
                    return $q->where('company_id', $this->selectedCompanyId);
                })->dateFilter('created_at', $this->period)->count(),
                "admin" => Ticking::when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                    return $q->where('department_id', $this->selectedDepartmentId);
                })->when(!empty($this->selectedCompanyId), function ($q) {
                    return $q->where('company_id', $this->selectedCompanyId);
                })->dateFilter('created_at', $this->period)->count(),
                default => []
            },

            'pending_checklogs_count' => match ($this->role) {
                "supervisor" => Ticking::supervisor()->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_PENDING)
                    ->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                        return $q->where('department_id', $this->selectedDepartmentId);
                    })->dateFilter('created_at', $this->period)->count(),
                "manager" => Ticking::manager()->where('manager_approval_status', Ticking::MANAGER_APPROVAL_PENDING)
                    ->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                        return $q->where('department_id', $this->selectedDepartmentId);
                    })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
                        return $q->where('company_id', $this->selectedCompanyId);
                    })->dateFilter('created_at', $this->period)->count(),
                "admin" => Ticking::where('manager_approval_status', Ticking::MANAGER_APPROVAL_PENDING)
                    ->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                        return $q->where('department_id', $this->selectedDepartmentId);
                    })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
                        return $q->where('company_id', $this->selectedCompanyId);
                    })->dateFilter('created_at', $this->period)->count(),
                default => []
            },

            'approved_checklogs_count' => match ($this->role) {
                "supervisor" =>  Ticking::supervisor()->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_APPROVED)->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                    return $q->where('department_id', $this->selectedDepartmentId);
                })->dateFilter('created_at', $this->period)->count(),
                "manager" => Ticking::manager()->where('manager_approval_status', Ticking::MANAGER_APPROVAL_APPROVED)->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                    return $q->where('department_id', $this->selectedDepartmentId);
                })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
                    return $q->where('company_id', $this->selectedCompanyId);
                })->dateFilter('created_at', $this->period)->count(),
                "admin" => Ticking::where('manager_approval_status', Ticking::MANAGER_APPROVAL_APPROVED)->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                    return $q->where('department_id', $this->selectedDepartmentId);
                })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
                    return $q->where('company_id', $this->selectedCompanyId);
                })->dateFilter('created_at', $this->period)->count(),
                "default" => [],
            },

            'rejected_checklogs_count' => match ($this->role) {
                "supervisor" => Ticking::supervisor()->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_REJECTED)->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                    return $q->where('department_id', $this->selectedDepartmentId);
                })->dateFilter('created_at', $this->period)->count(),
                "manager" => Ticking::where('manager_approval_status', Ticking::MANAGER_APPROVAL_REJECTED)->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                    return $q->where('department_id', $this->selectedDepartmentId);
                })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
                    return $q->where('company_id', $this->selectedCompanyId);
                })->dateFilter('created_at', $this->period)->count(),
                "admin" => Ticking::where('manager_approval_status', Ticking::MANAGER_APPROVAL_REJECTED)->when(!empty($this->selectedDepartmentId) && $this->selectedDepartmentId != 'all', function ($q) {
                    return $q->where('department_id', $this->selectedDepartmentId);
                })->when(!empty($this->selectedCompanyId) && $this->selectedCompanyId != 'all', function ($q) {
                    return $q->where('company_id', $this->selectedCompanyId);
                })->dateFilter('created_at', $this->period)->count(),
            },

            'payslips_success' => count($payslips->where('email_sent_status', Payslip::STATUS_SUCCESSFUL)),
            'payslips_success_week' => count($payslips->where('email_sent_status', Payslip::STATUS_SUCCESSFUL)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])),
            'payslips_failed' => count($payslips->where('email_sent_status', Payslip::STATUS_FAILED)),
            'payslips_failed_week' => count($payslips->where('email_sent_status', Payslip::STATUS_FAILED)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])),
            'payslips_last_month_total_count' => $payslips_last_month_count,
            'payslips_last_month_success_count' => $payslips_last_month_success_count,
            'chart_data' => $this->prepareWeeklyChart($stats),
            'chart_daily' => $this->prepareDailyChart($day_stats),
            'chart_pie_daily' => $pie_chart,

            // New enhanced metrics
            'attendance_rate' => $this->calculateAttendanceRate('this_month'),
            'attendance_rate_last_month' => $this->calculateAttendanceRate('last_month'),
            'leave_utilization_rate' => $this->calculateLeaveUtilizationRate(),
            'department_performance' => $this->getDepartmentPerformanceMetrics(),
            'pending_approvals' => $this->getPendingApprovalsCount(),
            'top_performers' => $this->getTopPerformers(),
            'attendance_heatmap' => $this->getAttendanceHeatmapData(),

            // Additional chart data
            'approval_pie_chart' => $this->getApprovalStatusPieChart(),
            'department_comparison' => $this->getDepartmentComparisonChart(),
            'monthly_trends' => $this->getMonthlyTrendsChart(),
            'top_departments' => $this->getTopDepartmentsByPerformance(),

        ])->layout('components.layouts.dashboard');
    }
}
