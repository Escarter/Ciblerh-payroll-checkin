<?php

namespace App\Livewire\Portal\Employees\Payslip;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Gate;
use App\Livewire\Traits\WithDataTable;
use App\Models\Payslip;

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
        
        $payslips = Payslip::search($this->query)->where('employee_id', $this->employee->id)->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);
        $payslips_count = Payslip::where('employee_id', $this->employee->id)->count();

        return view('livewire.portal.employees.payslip.history', compact('payslips', 'payslips_count'))->layout('components.layouts.dashboard');
    }
}
