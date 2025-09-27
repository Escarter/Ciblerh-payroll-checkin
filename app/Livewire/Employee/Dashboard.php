<?php

namespace App\Livewire\Employee;

use Livewire\Component;
use App\Models\AuditLog;
use App\Models\Ticking;
use App\Models\Leave;
use App\Models\Overtime;
use App\Models\Absence;
use App\Models\AdvanceSalary;
use Carbon\Carbon;

class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();
        $total_overtime = $user->overtimes()->count();
        $total_advance_salary = $user->advanceSalaries()->count();
        $total_absences = $user->absences()->count();
        $total_checklogs = $user->tickings()->count();
        $total_payslips = $user->payslips()->count();
        $logs = AuditLog::where('user_id', $user->id)->orderBy('created_at', 'desc')->get()->take(10);
        
        // Enhanced metrics for employee dashboard
        $monthly_checkins = $user->tickings()->whereBetween('start_time', [now()->startOfMonth(), now()->endOfMonth()])->count();
        $monthly_overtime_hours = $user->overtimes()->whereBetween('start_time', [now()->startOfMonth(), now()->endOfMonth()])
            ->where('approval_status', Overtime::APPROVAL_STATUS_APPROVED)
            ->get()
            ->sum(function($overtime) {
                return Carbon::parse($overtime->start_time)->diffInHours(Carbon::parse($overtime->end_time));
            });
        
        $attendance_rate = $this->calculateEmployeeAttendanceRate($user);
        $leave_balance = $user->remaining_leave_days;
        $pending_requests = $this->getPendingRequestsCount($user);
        $performance_score = $this->calculatePerformanceScore($user);
        
        return view('livewire.employee.dashboard', compact(
            'user', 'total_overtime','total_payslips', 'total_advance_salary', 'total_absences', 'total_checklogs', 'logs',
            'monthly_checkins', 'monthly_overtime_hours', 'attendance_rate', 'leave_balance', 'pending_requests', 'performance_score'
        ))->layout('components.layouts.employee.master');
    }
    
    private function calculateEmployeeAttendanceRate($user)
    {
        $workingDays = now()->daysInMonth;
        $actualCheckins = $user->tickings()->whereBetween('start_time', [now()->startOfMonth(), now()->endOfMonth()])->count();
        return $workingDays > 0 ? round(($actualCheckins / $workingDays) * 100, 2) : 0;
    }
    
    private function getPendingRequestsCount($user)
    {
        $pendingLeaves = $user->leaves()->where('supervisor_approval_status', Leave::SUPERVISOR_APPROVAL_PENDING)
            ->orWhere('manager_approval_status', Leave::MANAGER_APPROVAL_PENDING)->count();
        $pendingOvertimes = $user->overtimes()->where('approval_status', Overtime::APPROVAL_STATUS_PENDING)->count();
        $pendingAdvances = $user->advanceSalaries()->where('approval_status', AdvanceSalary::APPROVAL_STATUS_PENDING)->count();
        
        return [
            'leaves' => $pendingLeaves,
            'overtimes' => $pendingOvertimes,
            'advances' => $pendingAdvances,
            'total' => $pendingLeaves + $pendingOvertimes + $pendingAdvances
        ];
    }
    
    private function calculatePerformanceScore($user)
    {
        $attendanceScore = $this->calculateEmployeeAttendanceRate($user);
        $overtimeScore = min(100, $user->overtimes()->whereBetween('start_time', [now()->startOfMonth(), now()->endOfMonth()])
            ->where('approval_status', Overtime::APPROVAL_STATUS_APPROVED)->count() * 10);
        
        return round(($attendanceScore * 0.7) + ($overtimeScore * 0.3), 2);
    }
}
