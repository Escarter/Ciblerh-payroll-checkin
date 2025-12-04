<?php

namespace App\Livewire\Portal\Dashboard;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Company;
use App\Models\Payslip;
use App\Models\Service;
use App\Models\Ticking;
use App\Models\Leave;
use App\Models\Overtime;
use App\Models\AdvanceSalary;
use Livewire\Component;
use App\Models\AuditLog;
use App\Models\Department;
use Illuminate\Support\Facades\DB;

class Index extends Component
{
    public $companies = [];
    public $selectedCompanyId = null;
    public $selectedDepartmentId = 'all';
    public $departments = [];
    public $period = 'all_time';
    public $role;
    public $currentTime;

    // Modal state management
    public $showDepartmentModal = false;
    public $selectedDepartmentForModal = null;

    // Cache frequently used data
    protected $cachedData = [];

    public function mount()
    {
        $this->role = auth()->user()->getRoleNames()->first();
        $this->loadInitialData();
        $this->updateCurrentTime();
    }

    protected function loadInitialData()
    {
        switch ($this->role) {
            case 'manager':
                $this->companies = Company::manager()->orderBy('created_at', 'desc')->get();
                $this->departments = Department::manager()->orderBy('created_at', 'desc')->get();
                break;
            case 'admin':
                $this->companies = Company::orderBy('created_at', 'desc')->get();
                $this->departments = [];
                break;
            case 'supervisor':
                $this->companies = [];
                $this->departments = Department::whereIn('id', auth()->user()->supDepartments->pluck('department_id'))->get();
                break;
            default:
                $this->companies = [];
                $this->departments = [];
        }
    }

    public function updateCurrentTime()
    {
        $this->currentTime = now()->format('H:i:s');
    }

    public function updatedSelectedCompanyId($company_id)
    {
        if (!is_null($company_id)) {
            $this->departments = Department::where('company_id', $company_id)->get();
        }
        $this->dispatch('charts-updated');
        $this->clearCache();
    }

    public function updatedSelectedDepartmentId($department_id)
    {
        $this->dispatch('charts-updated');
        $this->clearCache();
    }

    public function updatedPeriod($period)
    {
        $this->dispatch('charts-updated');
        $this->clearCache();
    }

    protected function clearCache()
    {
        $this->cachedData = [];
    }

    protected function cache($key, $callback)
    {
        if (!isset($this->cachedData[$key])) {
            $this->cachedData[$key] = $callback();
        }
        return $this->cachedData[$key];
    }

    // Modal management methods
    public function openDepartmentModal()
    {
        $this->showDepartmentModal = true;
        $this->selectedDepartmentForModal = null;
    }

    public function closeDepartmentModal()
    {
        $this->showDepartmentModal = false;
        $this->selectedDepartmentForModal = null;
    }

    public function selectDepartmentForModal($departmentId)
    {
        $this->selectedDepartmentForModal = $departmentId;
        $this->showDepartmentModal = true;
    }

    public function getChartData()
    {
        return $this->cache('chart_data', function () {
            $stats = $this->getPayslipStats('week');
            $day_stats = $this->getPayslipStats('day');

            return [
                'approval_pie_chart' => $this->getApprovalStatusPieChart(),
                'department_comparison' => $this->getDepartmentComparisonChart(),
                'monthly_trends' => $this->getMonthlyTrendsChart(),
                'chart_data' => $this->prepareWeeklyChart($stats),
                'chart_daily' => $this->prepareDailyChart($day_stats),
            ];
        });
    }

    protected function getPayslipStats($groupBy = 'week')
    {
        $query = Payslip::query()
            ->when(
                $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                fn($q) => $q->where('company_id', $this->selectedCompanyId)
            )
            ->when(
                $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                fn($q) => $q->where('department_id', $this->selectedDepartmentId)
            );

        if ($groupBy === 'week') {
            return $query->selectRaw('WEEK(created_at) as period, email_sent_status, COUNT(*) as data')
                ->groupBy('period', 'email_sent_status')
                ->orderBy('period')
                ->get();
        } else {
            return $query->selectRaw('DAY(created_at) as period, email_sent_status, COUNT(*) as data')
                ->groupBy('period', 'email_sent_status')
                ->orderBy('period')
                ->get();
        }
    }

    public function prepareWeeklyChart($stats)
    {
        return $this->prepareChart($stats, fn($period) => 'Wk - ' . $period);
    }

    public function prepareDailyChart($stats)
    {
        return $this->prepareChart($stats, fn($period) => Carbon::parse($period)->format('D'));
    }

    protected function prepareChart($stats, $periodFormatter)
    {
        $periods = [];
        $success = [];
        $failed = [];
        $pending = [];

        foreach ($stats as $stat) {
            $periodLabel = $periodFormatter($stat->period);
            if (!in_array($periodLabel, $periods, true)) {
                $periods[] = $periodLabel;
            }

            switch ($stat->email_sent_status) {
                case Payslip::STATUS_SUCCESSFUL:
                    $success[] = $stat->data;
                    break;
                case Payslip::STATUS_FAILED:
                    $failed[] = $stat->data;
                    break;
                default:
                    $pending[] = $stat->data;
            }
        }

        return [
            'periods' => $periods,
            'success' => $success,
            'failed' => $failed,
            'pending' => $pending
        ];
    }

    // New methods for richer dashboard metrics
    public function calculateAttendanceRate($period = 'this_month')
    {
        return $this->cache("attendance_rate_{$period}", function () use ($period) {
            $dateFilter = $this->getDateRangeForPeriod($period);
            [$startDate, $endDate] = $dateFilter;

            $totalEmployees = User::when(
                $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                fn($q) => $q->where('department_id', $this->selectedDepartmentId)
            )
                ->when(
                    $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                    fn($q) => $q->where('company_id', $this->selectedCompanyId)
                )
                ->count();

            $totalWorkingDays = $this->getWorkingDaysInPeriod($startDate, $endDate);
            $expectedCheckins = $totalEmployees * $totalWorkingDays;

            if ($expectedCheckins <= 0) return 0;

            $actualCheckins = Ticking::when(
                $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                fn($q) => $q->where('department_id', $this->selectedDepartmentId)
            )
                ->when(
                    $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                    fn($q) => $q->where('company_id', $this->selectedCompanyId)
                )
                ->whereBetween('start_time', $dateFilter)
                ->count();

            return round(($actualCheckins / $expectedCheckins) * 100, 2);
        });
    }

    protected function getDateRangeForPeriod($period)
    {
        return match ($period) {
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'last_week' => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
            'this_week' => [now()->startOfWeek(), now()->endOfWeek()],
            default => [now()->startOfMonth(), now()->endOfMonth()]
        };
    }

    public function calculateLeaveUtilizationRate()
    {
        return $this->cache('leave_utilization', function () {
            $totalAllocatedDays = User::when(
                $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                fn($q) => $q->where('department_id', $this->selectedDepartmentId)
            )
                ->when(
                    $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                    fn($q) => $q->where('company_id', $this->selectedCompanyId)
                )
                ->sum('monthly_leave_allocation');

            if ($totalAllocatedDays <= 0) return 0;

            $totalUsedDays = Leave::when(
                $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                fn($q) => $q->where('department_id', $this->selectedDepartmentId)
            )
                ->when(
                    $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                    fn($q) => $q->where('company_id', $this->selectedCompanyId)
                )
                ->where('supervisor_approval_status', Leave::SUPERVISOR_APPROVAL_APPROVED)
                ->where('manager_approval_status', Leave::MANAGER_APPROVAL_APPROVED)
                ->sum(DB::raw('DATEDIFF(end_date, start_date) + 1'));

            return round(($totalUsedDays / $totalAllocatedDays) * 100, 2);
        });
    }

    public function getDepartmentPerformanceMetrics()
    {
        return $this->cache('department_performance', function () {
            return Department::when(
                $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                fn($q) => $q->where('company_id', $this->selectedCompanyId)
            )
                ->withCount('employees')
                ->get()
                ->map(function ($dept) {
                    $totalCheckins = Ticking::where('department_id', $dept->id)
                        ->whereBetween('start_time', [now()->startOfMonth(), now()->endOfMonth()])
                        ->count();

                    $dept->total_checkins = $totalCheckins;
                    $dept->total_leaves = Leave::where('department_id', $dept->id)
                        ->whereBetween('start_date', [now()->startOfMonth(), now()->endOfMonth()])
                        ->count();
                    $dept->total_overtimes = Overtime::where('department_id', $dept->id)
                        ->whereBetween('start_time', [now()->startOfMonth(), now()->endOfMonth()])
                        ->count();

                    $dept->attendance_rate = $dept->employees_count > 0
                        ? round(($totalCheckins / ($dept->employees_count * now()->daysInMonth)) * 100, 2)
                        : 0;

                    return $dept;
                });
        });
    }

    public function getPendingApprovalsCount()
    {
        return $this->cache('pending_approvals', function () {
            $pendingCheckins = Ticking::when(
                $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                fn($q) => $q->where('department_id', $this->selectedDepartmentId)
            )
                ->when(
                    $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                    fn($q) => $q->where('company_id', $this->selectedCompanyId)
                )
                ->where(function ($q) {
                    $q->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_PENDING)
                        ->orWhere('manager_approval_status', Ticking::MANAGER_APPROVAL_PENDING);
                })->count();

            $pendingLeaves = Leave::when(
                $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                fn($q) => $q->where('department_id', $this->selectedDepartmentId)
            )
                ->when(
                    $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                    fn($q) => $q->where('company_id', $this->selectedCompanyId)
                )
                ->where(function ($q) {
                    $q->where('supervisor_approval_status', Leave::SUPERVISOR_APPROVAL_PENDING)
                        ->orWhere('manager_approval_status', Leave::MANAGER_APPROVAL_PENDING);
                })->count();

            $pendingOvertimes = Overtime::when(
                $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                fn($q) => $q->where('department_id', $this->selectedDepartmentId)
            )
                ->when(
                    $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                    fn($q) => $q->where('company_id', $this->selectedCompanyId)
                )
                ->where('approval_status', Overtime::APPROVAL_STATUS_PENDING)
                ->count();

            $pendingAdvances = AdvanceSalary::when(
                $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                fn($q) => $q->where('department_id', $this->selectedDepartmentId)
            )
                ->when(
                    $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                    fn($q) => $q->where('company_id', $this->selectedCompanyId)
                )
                ->where('approval_status', AdvanceSalary::APPROVAL_STATUS_PENDING)
                ->count();

            return [
                'checkins' => $pendingCheckins,
                'leaves' => $pendingLeaves,
                'overtimes' => $pendingOvertimes,
                'advances' => $pendingAdvances,
                'total' => $pendingCheckins + $pendingLeaves + $pendingOvertimes + $pendingAdvances
            ];
        });
    }

    public function getTopPerformers()
    {
        return $this->cache('top_performers', function () {
            return User::when(
                $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                fn($q) => $q->where('department_id', $this->selectedDepartmentId)
            )
                ->when(
                    $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                    fn($q) => $q->where('company_id', $this->selectedCompanyId)
                )
                ->withCount([
                    'tickings as monthly_checkins' => function ($query) {
                        $query->whereBetween('start_time', [now()->startOfMonth(), now()->endOfMonth()]);
                    },
                    'overtimes as monthly_overtimes' => function ($query) {
                        $query->whereBetween('start_time', [now()->startOfMonth(), now()->endOfMonth()])
                            ->where('approval_status', Overtime::APPROVAL_STATUS_APPROVED);
                    }
                ])->orderBy('monthly_checkins', 'desc')->limit(5)->get();
        });
    }

    public function getAttendanceHeatmapData()
    {
        return $this->cache('attendance_heatmap', function () {
            $heatmapData = [];
            $startDate = now()->subDays(30);

            for ($i = 0; $i < 30; $i++) {
                $date = $startDate->copy()->addDays($i);
                $checkins = Ticking::when(
                    $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                    fn($q) => $q->where('department_id', $this->selectedDepartmentId)
                )
                    ->when(
                        $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                        fn($q) => $q->where('company_id', $this->selectedCompanyId)
                    )
                    ->whereDate('start_time', $date)->count();

                $totalEmployees = User::when(
                    $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                    fn($q) => $q->where('department_id', $this->selectedDepartmentId)
                )
                    ->when(
                        $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                        fn($q) => $q->where('company_id', $this->selectedCompanyId)
                    )
                    ->count();

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
        });
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
        return $this->cache('approval_pie_chart', function () {
            $pendingCheckins = Ticking::when(
                $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                fn($q) => $q->where('department_id', $this->selectedDepartmentId)
            )
                ->when(
                    $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                    fn($q) => $q->where('company_id', $this->selectedCompanyId)
                )
                ->where(function ($q) {
                    $q->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_PENDING)
                        ->orWhere('manager_approval_status', Ticking::MANAGER_APPROVAL_PENDING);
                })->count();

            $approvedCheckins = Ticking::when(
                $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                fn($q) => $q->where('department_id', $this->selectedDepartmentId)
            )
                ->when(
                    $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                    fn($q) => $q->where('company_id', $this->selectedCompanyId)
                )
                ->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_APPROVED)
                ->where('manager_approval_status', Ticking::MANAGER_APPROVAL_APPROVED)
                ->count();

            $rejectedCheckins = Ticking::when(
                $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                fn($q) => $q->where('department_id', $this->selectedDepartmentId)
            )
                ->when(
                    $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                    fn($q) => $q->where('company_id', $this->selectedCompanyId)
                )
                ->where(function ($q) {
                    $q->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_REJECTED)
                        ->orWhere('manager_approval_status', Ticking::MANAGER_APPROVAL_REJECTED);
                })->count();

            return [
                'labels' => ['Pending', 'Approved', 'Rejected'],
                'data' => [$pendingCheckins, $approvedCheckins, $rejectedCheckins],
                'colors' => ['#ffc107', '#28a745', '#dc3545']
            ];
        });
    }

    public function getDepartmentComparisonChart()
    {
        return $this->cache('department_comparison', function () {
            $departments = Department::when(
                $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                fn($q) => $q->where('company_id', $this->selectedCompanyId)
            )
                ->withCount('employees')->get();

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
        });
    }

    public function getMonthlyTrendsChart()
    {
        return $this->cache('monthly_trends', function () {
            $months = [];
            $attendanceTrends = [];
            $overtimeTrends = [];
            $leaveTrends = [];

            for ($i = 5; $i >= 0; $i--) {
                $month = now()->subMonths($i);
                $months[] = $month->format('M Y');

                $startOfMonth = $month->copy()->startOfMonth();
                $endOfMonth = $month->copy()->endOfMonth();

                $checkins = Ticking::when(
                    $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                    fn($q) => $q->where('department_id', $this->selectedDepartmentId)
                )
                    ->when(
                        $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                        fn($q) => $q->where('company_id', $this->selectedCompanyId)
                    )
                    ->whereBetween('start_time', [$startOfMonth, $endOfMonth])->count();

                $overtimes = Overtime::when(
                    $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                    fn($q) => $q->where('department_id', $this->selectedDepartmentId)
                )
                    ->when(
                        $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                        fn($q) => $q->where('company_id', $this->selectedCompanyId)
                    )
                    ->whereBetween('start_time', [$startOfMonth, $endOfMonth])->count();

                $leaves = Leave::when(
                    $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                    fn($q) => $q->where('department_id', $this->selectedDepartmentId)
                )
                    ->when(
                        $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                        fn($q) => $q->where('company_id', $this->selectedCompanyId)
                    )
                    ->whereBetween('start_date', [$startOfMonth, $endOfMonth])->count();

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
        });
    }

    public function getTopDepartmentsByPerformance()
    {
        return $this->cache('top_departments', function () {
            return Department::when(
                $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                fn($q) => $q->where('company_id', $this->selectedCompanyId)
            )
                ->withCount('employees')
                ->get()
                ->map(function ($dept) {
                    $checkins = Ticking::where('department_id', $dept->id)
                        ->whereBetween('start_time', [now()->startOfMonth(), now()->endOfMonth()])
                        ->count();

                    $dept->performance_score = $dept->employees_count > 0 ?
                        round(($checkins / ($dept->employees_count * now()->daysInMonth)) * 100, 2) : 0;

                    return $dept;
                })
                ->sortByDesc('performance_score')
                ->take(5);
        });
    }

    public function render()
    {
        // Get checklogs based on role
        $checklogs = match ($this->role) {
            "supervisor" => Ticking::supervisor()->with('user')
                ->orderBy('start_time', 'desc')
                ->when(
                    $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                    fn($q) => $q->where('department_id', $this->selectedDepartmentId)
                )
                ->dateFilter('created_at', $this->period)
                ->get()->unique('user_id')->take(20),

            "manager" => Ticking::manager()->with('user')
                ->orderBy('start_time', 'desc')
                ->when(
                    $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                    fn($q) => $q->where('department_id', $this->selectedDepartmentId)
                )
                ->dateFilter('created_at', $this->period)
                ->get()->unique('user_id')->take(20),

            "admin" => Ticking::with('user')
                ->orderBy('start_time', 'desc')
                ->when(
                    $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                    fn($q) => $q->where('department_id', $this->selectedDepartmentId)
                )
                ->when(
                    $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                    fn($q) => $q->where('company_id', $this->selectedCompanyId)
                )
                ->dateFilter('created_at', $this->period)
                ->get()->unique('user_id')->take(20),

            default => [],
        };

        // Get audit logs based on role
        $logs = match ($this->role) {
            "supervisor" => AuditLog::whereUserId(auth()->user()->id)
                ->orderBy('created_at', 'desc')
                ->dateFilter('created_at', $this->period)
                ->get()->take(10),

            "manager" => AuditLog::manager()
                ->orderBy('created_at', 'desc')
                ->dateFilter('created_at', $this->period)
                ->get()->take(10),

            "admin" => AuditLog::orderBy('created_at', 'desc')
                ->dateFilter('created_at', $this->period)
                ->get()->take(10),

            default => [],
        };

        // Get payslips
        $payslips = Payslip::select('id', 'email_sent_status', 'department_id', 'created_at')
            ->when(
                $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                fn($q) => $q->where('department_id', $this->selectedDepartmentId)
            )
            ->dateFilter('created_at', $this->period)
            ->get();

        // Calculate payslip stats
        $payslips_last_month_count = Payslip::select('id', 'email_sent_status', 'created_at')
            ->whereIn('email_sent_status', [Payslip::STATUS_FAILED, Payslip::STATUS_SUCCESSFUL])
            ->whereBetween('created_at', [now()->startOfMonth()->subMonthNoOverflow(), now()->endOfMonth()])
            ->count();

        $payslips_last_month_success_count = Payslip::select('id', 'email_sent_status', 'created_at')
            ->where('email_sent_status', Payslip::STATUS_SUCCESSFUL)
            ->whereBetween('created_at', [now()->startOfMonth()->subMonthNoOverflow(), now()->endOfMonth()])
            ->count();

        // Get chart data
        $stats = $this->getPayslipStats('week');
        $day_stats = $this->getPayslipStats('day');

        // Calculate pie chart data
        $pie_chart_data = [
            array_sum($this->prepareDailyChart($day_stats)['pending']),
            array_sum($this->prepareDailyChart($day_stats)['failed']),
            array_sum($this->prepareDailyChart($day_stats)['success'])
        ];

        return view('livewire.portal.dashboard.index', [
            'checklogs' => $checklogs,
            'logs' => $logs,

            'total_companies' => match ($this->role) {
                "admin" => Company::when(
                    $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                    fn($q) => $q->where('id', $this->selectedCompanyId)
                )
                    ->dateFilter('created_at', $this->period)->count(),

                "manager" => Company::manager()
                    ->when(
                        $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                        fn($q) => $q->where('id', $this->selectedCompanyId)
                    )
                    ->dateFilter('created_at', $this->period)->count(),

                default => 0,
            },

            'total_departments' => match ($this->role) {
                "supervisor" => Department::supervisor()->dateFilter('created_at', $this->period)->count(),
                "manager" => Department::manager()
                    ->when(
                        $this->selectedCompanyId,
                        fn($q) => $q->where('company_id', $this->selectedCompanyId)
                    )
                    ->dateFilter('created_at', $this->period)->count(),
                "admin" => Department::when(
                    $this->selectedCompanyId,
                    fn($q) => $q->where('company_id', $this->selectedCompanyId)
                )
                    ->dateFilter('created_at', $this->period)->count(),
                default => 0,
            },

            'total_services' => match ($this->role) {
                "supervisor" => Service::supervisor()
                    ->when(
                        $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                        fn($q) => $q->where('department_id', $this->selectedDepartmentId)
                    )
                    ->dateFilter('created_at', $this->period)->count(),
                "manager" => Service::manager()
                    ->when(
                        $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                        fn($q) => $q->where('department_id', $this->selectedDepartmentId)
                    )
                    ->dateFilter('created_at', $this->period)->count(),
                "admin" => Service::when(
                    $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                    fn($q) => $q->where('department_id', $this->selectedDepartmentId)
                )
                    ->when(
                        $this->selectedCompanyId,
                        fn($q) => $q->where('company_id', $this->selectedCompanyId)
                    )
                    ->dateFilter('created_at', $this->period)->count(),
                default => 0,
            },

            'total_employees' => match ($this->role) {
                "supervisor" => User::supervisor()->with('role')->role(['employee'])
                    ->when(
                        $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                        fn($q) => $q->where('department_id', $this->selectedDepartmentId)
                    )
                    ->dateFilter('created_at', $this->period)->count(),
                "manager" => User::manager()->with('role')->role(['employee'])
                    ->when(
                        $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                        fn($q) => $q->where('department_id', $this->selectedDepartmentId)
                    )
                    ->when(
                        $this->selectedCompanyId,
                        fn($q) => $q->where('company_id', $this->selectedCompanyId)
                    )
                    ->dateFilter('created_at', $this->period)->count(),
                "admin" => User::with('role')->role(['employee'])
                    ->when(
                        $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                        fn($q) => $q->where('department_id', $this->selectedDepartmentId)
                    )
                    ->when(
                        $this->selectedCompanyId,
                        fn($q) => $q->where('company_id', $this->selectedCompanyId)
                    )
                    ->dateFilter('created_at', $this->period)->count(),
                default => 0,
            },

            'checklogs_count' => match ($this->role) {
                "supervisor" => Ticking::supervisor()
                    ->when(
                        $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                        fn($q) => $q->where('department_id', $this->selectedDepartmentId)
                    )
                    ->dateFilter('created_at', $this->period)->count(),
                "manager" => Ticking::manager()
                    ->when(
                        $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                        fn($q) => $q->where('department_id', $this->selectedDepartmentId)
                    )
                    ->when(
                        $this->selectedCompanyId,
                        fn($q) => $q->where('company_id', $this->selectedCompanyId)
                    )
                    ->dateFilter('created_at', $this->period)->count(),
                "admin" => Ticking::when(
                    $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                    fn($q) => $q->where('department_id', $this->selectedDepartmentId)
                )
                    ->when(
                        $this->selectedCompanyId,
                        fn($q) => $q->where('company_id', $this->selectedCompanyId)
                    )
                    ->dateFilter('created_at', $this->period)->count(),
                default => 0
            },

            'pending_checklogs_count' => match ($this->role) {
                "supervisor" => Ticking::supervisor()
                    ->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_PENDING)
                    ->when(
                        $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                        fn($q) => $q->where('department_id', $this->selectedDepartmentId)
                    )
                    ->dateFilter('created_at', $this->period)->count(),
                "manager" => Ticking::manager()
                    ->where('manager_approval_status', Ticking::MANAGER_APPROVAL_PENDING)
                    ->when(
                        $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                        fn($q) => $q->where('department_id', $this->selectedDepartmentId)
                    )
                    ->when(
                        $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                        fn($q) => $q->where('company_id', $this->selectedCompanyId)
                    )
                    ->dateFilter('created_at', $this->period)->count(),
                "admin" => Ticking::where('manager_approval_status', Ticking::MANAGER_APPROVAL_PENDING)
                    ->when(
                        $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                        fn($q) => $q->where('department_id', $this->selectedDepartmentId)
                    )
                    ->when(
                        $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                        fn($q) => $q->where('company_id', $this->selectedCompanyId)
                    )
                    ->dateFilter('created_at', $this->period)->count(),
                default => 0
            },

            'approved_checklogs_count' => match ($this->role) {
                "supervisor" =>  Ticking::supervisor()
                    ->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_APPROVED)
                    ->when(
                        $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                        fn($q) => $q->where('department_id', $this->selectedDepartmentId)
                    )
                    ->dateFilter('created_at', $this->period)->count(),
                "manager" => Ticking::manager()
                    ->where('manager_approval_status', Ticking::MANAGER_APPROVAL_APPROVED)
                    ->when(
                        $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                        fn($q) => $q->where('department_id', $this->selectedDepartmentId)
                    )
                    ->when(
                        $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                        fn($q) => $q->where('company_id', $this->selectedCompanyId)
                    )
                    ->dateFilter('created_at', $this->period)->count(),
                "admin" => Ticking::where('manager_approval_status', Ticking::MANAGER_APPROVAL_APPROVED)
                    ->when(
                        $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                        fn($q) => $q->where('department_id', $this->selectedDepartmentId)
                    )
                    ->when(
                        $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                        fn($q) => $q->where('company_id', $this->selectedCompanyId)
                    )
                    ->dateFilter('created_at', $this->period)->count(),
                default => 0,
            },

            'rejected_checklogs_count' => match ($this->role) {
                "supervisor" => Ticking::supervisor()
                    ->where('supervisor_approval_status', Ticking::SUPERVISOR_APPROVAL_REJECTED)
                    ->when(
                        $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                        fn($q) => $q->where('department_id', $this->selectedDepartmentId)
                    )
                    ->dateFilter('created_at', $this->period)->count(),
                "manager" => Ticking::where('manager_approval_status', Ticking::MANAGER_APPROVAL_REJECTED)
                    ->when(
                        $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                        fn($q) => $q->where('department_id', $this->selectedDepartmentId)
                    )
                    ->when(
                        $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                        fn($q) => $q->where('company_id', $this->selectedCompanyId)
                    )
                    ->dateFilter('created_at', $this->period)->count(),
                "admin" => Ticking::where('manager_approval_status', Ticking::MANAGER_APPROVAL_REJECTED)
                    ->when(
                        $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                        fn($q) => $q->where('department_id', $this->selectedDepartmentId)
                    )
                    ->when(
                        $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                        fn($q) => $q->where('company_id', $this->selectedCompanyId)
                    )
                    ->dateFilter('created_at', $this->period)->count(),
                default => 0,
            },

            'payslips_success' => count($payslips->where('email_sent_status', Payslip::STATUS_SUCCESSFUL)),
            'payslips_success_week' => count($payslips->where('email_sent_status', Payslip::STATUS_SUCCESSFUL)
                ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])),
            'payslips_failed' => count($payslips->where('email_sent_status', Payslip::STATUS_FAILED)),
            'payslips_failed_week' => count($payslips->where('email_sent_status', Payslip::STATUS_FAILED)
                ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])),
            'payslips_last_month_total_count' => $payslips_last_month_count,
            'payslips_last_month_success_count' => $payslips_last_month_success_count,
            'chart_data' => $this->prepareWeeklyChart($stats),
            'chart_daily' => $this->prepareDailyChart($day_stats),
            'pie_chart_data' => $pie_chart_data,

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
            'showDepartmentModal' => $this->showDepartmentModal,

        ])->layout('components.layouts.dashboard');
    }
}
