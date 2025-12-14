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
    public $showPayslipDetailsModal = false;
    public $selectedFailureType = 'all';

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

    public function openPayslipDetailsModal()
    {
        $this->showPayslipDetailsModal = true;
        $this->selectedFailureType = 'all';
    }

    public function closePayslipDetailsModal()
    {
        $this->showPayslipDetailsModal = false;
        $this->selectedFailureType = 'all';
    }

    public function getChartData()
    {
        return $this->cache('chart_data', function () {
            $stats = $this->getPayslipStats('week');
            $day_stats = $this->getPayslipStats('day');

            return [
                'approval_pie_chart' => $this->getApprovalStatusPieChart(),
                'payslip_status_pie_chart' => $this->getPayslipStatusPieChart(),
                'comprehensive_status_chart' => $this->getComprehensivePayslipStatusChart(),
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

        $connection = DB::getDriverName();

        if ($groupBy === 'week') {
            $periodFunction = $connection === 'sqlite'
                ? "strftime('%W', created_at)"
                : 'WEEK(created_at)';

            return $query->selectRaw("
                    {$periodFunction} as period,
                    email_sent_status,
                    sms_sent_status,
                    encryption_status,
                    COUNT(*) as data
                ")
                ->groupBy('period', 'email_sent_status', 'sms_sent_status', 'encryption_status')
                ->orderBy('period')
                ->get();
        } else {
            $periodFunction = $connection === 'sqlite'
                ? "strftime('%d', created_at)"
                : 'DAY(created_at)';

            return $query->selectRaw("
                    {$periodFunction} as period,
                    email_sent_status,
                    sms_sent_status,
                    encryption_status,
                    COUNT(*) as data
                ")
                ->groupBy('period', 'email_sent_status', 'sms_sent_status', 'encryption_status')
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
        $sms_disabled = [];
        $encryption_issues = [];

        // Group stats by period for processing
        $groupedStats = [];
        foreach ($stats as $stat) {
            $periodLabel = $periodFormatter($stat->period);
            if (!isset($groupedStats[$periodLabel])) {
                $groupedStats[$periodLabel] = [
                    'success' => 0,
                    'failed' => 0,
                    'pending' => 0,
                    'sms_disabled' => 0,
                    'encryption_failed' => 0
                ];
            }

            // Determine overall status for this payslip
            $emailStatus = $stat->email_sent_status;
            $smsStatus = $stat->sms_sent_status;
            $encryptionStatus = $stat->encryption_status;

            // Count issues
            $hasFailure = false;
            if ($emailStatus == Payslip::STATUS_FAILED) {
                $groupedStats[$periodLabel]['failed'] += $stat->data;
                $hasFailure = true;
            }
            if ($smsStatus == Payslip::STATUS_FAILED) {
                $groupedStats[$periodLabel]['failed'] += $stat->data;
                $hasFailure = true;
            }
            if ($encryptionStatus == Payslip::STATUS_FAILED) {
                $groupedStats[$periodLabel]['encryption_failed'] += $stat->data;
                $hasFailure = true;
            }
            if ($smsStatus == Payslip::STATUS_DISABLED) {
                $groupedStats[$periodLabel]['sms_disabled'] += $stat->data;
            }

            // If no failures but has pending statuses, count as pending
            if (!$hasFailure && ($emailStatus == Payslip::STATUS_PENDING || $smsStatus == Payslip::STATUS_PENDING)) {
                $groupedStats[$periodLabel]['pending'] += $stat->data;
            }
            // If all statuses are successful, count as success
            elseif (!$hasFailure &&
                    $emailStatus == Payslip::STATUS_SUCCESSFUL &&
                    ($smsStatus == Payslip::STATUS_SUCCESSFUL || $smsStatus == Payslip::STATUS_DISABLED) &&
                    ($encryptionStatus == Payslip::STATUS_SUCCESSFUL || is_null($encryptionStatus))) {
                $groupedStats[$periodLabel]['success'] += $stat->data;
            }
        }

        // Convert to arrays for Chartist
        foreach ($groupedStats as $period => $data) {
            $periods[] = $period;
            $success[] = $data['success'];
            $failed[] = $data['failed'];
            $pending[] = $data['pending'];
            $sms_disabled[] = $data['sms_disabled'];
            $encryption_issues[] = $data['encryption_failed'];
        }

        return [
            'periods' => $periods,
            'success' => $success,
            'failed' => $failed,
            'pending' => $pending,
            'sms_disabled' => $sms_disabled,
            'encryption_issues' => $encryption_issues
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

    public function getPayslipStatusPieChart()
    {
        return $this->cache('payslip_status_pie_chart', function () {
            $query = Payslip::query()
                ->when(
                    $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                    fn($q) => $q->where('company_id', $this->selectedCompanyId)
                )
                ->when(
                    $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                    fn($q) => $q->where('department_id', $this->selectedDepartmentId)
                );

            $emailSuccessful = $query->clone()->where('email_sent_status', Payslip::STATUS_SUCCESSFUL)->count();
            $emailFailed = $query->clone()->where('email_sent_status', Payslip::STATUS_FAILED)->count();
            $emailPending = $query->clone()->where('email_sent_status', Payslip::STATUS_PENDING)->count();

            $smsSuccessful = $query->clone()->where('sms_sent_status', Payslip::STATUS_SUCCESSFUL)->count();
            $smsFailed = $query->clone()->where('sms_sent_status', Payslip::STATUS_FAILED)->count();
            $smsPending = $query->clone()->where('sms_sent_status', Payslip::STATUS_PENDING)->count();
            $smsDisabled = $query->clone()->where('sms_sent_status', Payslip::STATUS_DISABLED)->count();

            $encryptionSuccessful = $query->clone()->where('encryption_status', Payslip::STATUS_SUCCESSFUL)->count();
            $encryptionFailed = $query->clone()->where('encryption_status', Payslip::STATUS_FAILED)->count();
            $encryptionNotRecorded = $query->clone()->whereNull('encryption_status')->orWhere('encryption_status', '')->count();

            return [
                'labels' => [
                    __('dashboard.email_successful'),
                    __('dashboard.email_failed'),
                    __('dashboard.email_pending'),
                    __('dashboard.sms_successful'),
                    __('dashboard.sms_failed'),
                    __('dashboard.sms_pending'),
                    __('dashboard.sms_disabled'),
                    __('dashboard.encryption_successful'),
                    __('dashboard.encryption_failed'),
                    __('dashboard.encryption_not_recorded')
                ],
                'data' => [
                    $emailSuccessful,
                    $emailFailed,
                    $emailPending,
                    $smsSuccessful,
                    $smsFailed,
                    $smsPending,
                    $smsDisabled,
                    $encryptionSuccessful,
                    $encryptionFailed,
                    $encryptionNotRecorded
                ],
                'colors' => [
                    '#28a745', // email successful - green
                    '#dc3545', // email failed - red
                    '#ffc107', // email pending - yellow
                    '#20c997', // sms successful - teal
                    '#e83e8c', // sms failed - pink
                    '#fd7e14', // sms pending - orange
                    '#6c757d', // sms disabled - gray
                    '#007bff', // encryption successful - blue
                    '#6f42c1', // encryption failed - purple
                    '#adb5bd'  // encryption not recorded - light gray
                ]
            ];
        });
    }

    public function getComprehensivePayslipStatusChart()
    {
        return $this->cache('comprehensive_status_chart', function () {
            $query = Payslip::query()
                ->when(
                    $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                    fn($q) => $q->where('company_id', $this->selectedCompanyId)
                )
                ->when(
                    $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                    fn($q) => $q->where('department_id', $this->selectedDepartmentId)
                );

            // Calculate overall success rates
            $totalPayslips = $query->clone()->count();

            $emailSuccessRate = $totalPayslips > 0 ?
                round(($query->clone()->where('email_sent_status', Payslip::STATUS_SUCCESSFUL)->count() / $totalPayslips) * 100, 1) : 0;

            $smsSuccessRate = $totalPayslips > 0 ?
                round(($query->clone()->where('sms_sent_status', Payslip::STATUS_SUCCESSFUL)->count() / $totalPayslips) * 100, 1) : 0;

            $encryptionSuccessRate = $totalPayslips > 0 ?
                round(($query->clone()->where('encryption_status', Payslip::STATUS_SUCCESSFUL)->count() / $totalPayslips) * 100, 1) : 0;

            // Get failure counts by type
            $emailFailures = $query->clone()->where('email_sent_status', Payslip::STATUS_FAILED)->count();
            $smsFailures = $query->clone()->where('sms_sent_status', Payslip::STATUS_FAILED)->count();
            $encryptionFailures = $query->clone()->where('encryption_status', Payslip::STATUS_FAILED)->count();

            // Get pending/disabled counts
            $emailPending = $query->clone()->where('email_sent_status', Payslip::STATUS_PENDING)->count();
            $smsPending = $query->clone()->where('sms_sent_status', Payslip::STATUS_PENDING)->count();
            $smsDisabled = $query->clone()->where('sms_sent_status', Payslip::STATUS_DISABLED)->count();

            return [
                'total_payslips' => $totalPayslips,
                'success_rates' => [
                    'email' => $emailSuccessRate,
                    'sms' => $smsSuccessRate,
                    'encryption' => $encryptionSuccessRate
                ],
                'failure_counts' => [
                    'email' => $emailFailures,
                    'sms' => $smsFailures,
                    'encryption' => $encryptionFailures
                ],
                'pending_counts' => [
                    'email' => $emailPending,
                    'sms' => $smsPending,
                    'sms_disabled' => $smsDisabled
                ],
                'health_score' => round(($emailSuccessRate + $smsSuccessRate + $encryptionSuccessRate) / 3, 1)
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

    public function getFailureDetails($failureType = 'all')
    {
        $query = Payslip::with('employee')
            ->when(
                $this->selectedCompanyId && $this->selectedCompanyId != 'all',
                fn($q) => $q->where('company_id', $this->selectedCompanyId)
            )
            ->when(
                $this->selectedDepartmentId && $this->selectedDepartmentId != 'all',
                fn($q) => $q->where('department_id', $this->selectedDepartmentId)
            )
            ->whereBetween('created_at', [now()->subDays(30), now()]); // Last 30 days

        $emailFailures = [];
        $smsFailures = [];
        $encryptionFailures = [];

        switch ($failureType) {
            case 'failed':
                // Get email failures
                $emailFailures = $query->clone()
                    ->where('email_sent_status', Payslip::STATUS_FAILED)
                    ->orderBy('created_at', 'desc')
                    ->take(10)
                    ->get()
                    ->map(function ($payslip) {
                        return [
                            'employee_name' => $payslip->name,
                            'email' => $payslip->email,
                            'error_message' => $payslip->email_status_note ?: __('dashboard.unknown_error'),
                            'created_at' => $payslip->created_at->format('M j, Y H:i')
                        ];
                    });

                // Get SMS failures
                $smsFailures = $query->clone()
                    ->where('sms_sent_status', Payslip::STATUS_FAILED)
                    ->orderBy('created_at', 'desc')
                    ->take(10)
                    ->get()
                    ->map(function ($payslip) {
                        return [
                            'employee_name' => $payslip->name,
                            'phone' => $payslip->phone,
                            'error_message' => $payslip->sms_status_note ?: __('dashboard.unknown_error'),
                            'created_at' => $payslip->created_at->format('M j, Y H:i')
                        ];
                    });
                break;

            case 'encryption_issues':
                $encryptionFailures = $query->clone()
                    ->where('encryption_status', Payslip::STATUS_FAILED)
                    ->orderBy('created_at', 'desc')
                    ->take(10)
                    ->get()
                    ->map(function ($payslip) {
                        return [
                            'employee_name' => $payslip->name,
                            'matricule' => $payslip->matricule,
                            'error_message' => $payslip->encryption_status_note ?: __('dashboard.encryption_failed'),
                            'created_at' => $payslip->created_at->format('M j, Y H:i')
                        ];
                    });
                break;

            default:
                // Get all types of failures
                $emailFailures = $query->clone()
                    ->where('email_sent_status', Payslip::STATUS_FAILED)
                    ->orderBy('created_at', 'desc')
                    ->take(5)
                    ->get()
                    ->map(function ($payslip) {
                        return [
                            'employee_name' => $payslip->name,
                            'email' => $payslip->email,
                            'error_message' => $payslip->email_status_note ?: __('dashboard.unknown_error'),
                            'created_at' => $payslip->created_at->format('M j, Y H:i')
                        ];
                    });

                $smsFailures = $query->clone()
                    ->where('sms_sent_status', Payslip::STATUS_FAILED)
                    ->orderBy('created_at', 'desc')
                    ->take(5)
                    ->get()
                    ->map(function ($payslip) {
                        return [
                            'employee_name' => $payslip->name,
                            'phone' => $payslip->phone,
                            'error_message' => $payslip->sms_status_note ?: __('dashboard.unknown_error'),
                            'created_at' => $payslip->created_at->format('M j, Y H:i')
                        ];
                    });

                $encryptionFailures = $query->clone()
                    ->where('encryption_status', Payslip::STATUS_FAILED)
                    ->orderBy('created_at', 'desc')
                    ->take(5)
                    ->get()
                    ->map(function ($payslip) {
                        return [
                            'employee_name' => $payslip->name,
                            'matricule' => $payslip->matricule,
                            'error_message' => $payslip->encryption_status_note ?: __('dashboard.encryption_failed'),
                            'created_at' => $payslip->created_at->format('M j, Y H:i')
                        ];
                    });
                break;
        }

        return [
            'email_failures' => $emailFailures,
            'sms_failures' => $smsFailures,
            'encryption_failures' => $encryptionFailures
        ];
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
            'payslip_status_pie_chart' => $this->getPayslipStatusPieChart(),
            'comprehensive_status_chart' => $this->getComprehensivePayslipStatusChart(),
            'department_comparison' => $this->getDepartmentComparisonChart(),
            'monthly_trends' => $this->getMonthlyTrendsChart(),
            'top_departments' => $this->getTopDepartmentsByPerformance(),
            'showDepartmentModal' => $this->showDepartmentModal,
            'showPayslipDetailsModal' => $this->showPayslipDetailsModal,
            'payslip_failure_details' => $this->getFailureDetails($this->selectedFailureType),

        ])->layout('components.layouts.dashboard');
    }
}
