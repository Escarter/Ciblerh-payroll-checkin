<?php

namespace App\Livewire\Employee;

use Livewire\Component;
use App\Models\AuditLog;

class Dashboard extends Component
{
    public function render()
    {
        $user = auth()->user();
        $total_overtime = $user->overtimes()->count();
        $total_advance_salary = $user->advanceSalaries()->count();
        $total_absences = $user->absences()->count();
        $total_checklogs = $user->tickings()->count();
        $logs = AuditLog::where('user_id', $user->id)->orderBy('created_at', 'desc')->get()->take(10);
    
        return view('livewire.employee.dashboard', compact('user', 'total_overtime', 'total_advance_salary', 'total_absences', 'total_checklogs', 'logs'))->layout('components.layouts.employee.master');
    }
}
