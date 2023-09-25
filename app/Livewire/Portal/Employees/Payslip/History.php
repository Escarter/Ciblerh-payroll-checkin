<?php

namespace App\Livewire\Portal\Employees\Payslip;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Gate;
use App\Livewire\Traits\WithDataTable;

class History extends Component
{
    use WithDataTable;

    public $employee;

    public function mount($employee_uuid)  
    {
        $this->employee = User::whereUuid($employee_uuid)->first();

    }
    public function render()
    {
        if (!Gate::allows('payslip-read')) {
            return abort(401);
        }

        // if (auth()->user()->hasRole('employee') && $this->employee->department->author_id !== auth()->user()->id) {
        //     return abort(401);
        // }
        
        $payslips = $this->employee->payslips->sortByDesc('created_at');

        return view('livewire.portal.employees.payslip.history', compact('payslips'))->layout('components.layouts.dashboard');
    }
}
