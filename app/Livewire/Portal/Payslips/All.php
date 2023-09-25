<?php

namespace App\Livewire\Portal\Payslips;

use Livewire\Component;
use App\Models\SendPayslipProcess;
use Illuminate\Support\Facades\Gate;
use App\Livewire\Traits\WithDataTable;

class All extends Component
{
    use WithDataTable;

    public function render()
    {
        if (!Gate::allows('payslip-read')) {
            return abort(401);
        }

        $jobs =  match (auth()->user()->getRoleNames()->first()) {
            'manager' => SendPayslipProcess::manager()->orderBy('created_at', 'desc')->get(),
            'supervisor' => SendPayslipProcess::whereIn('author_id', auth()->user()->supDepartments->pluck('id'))->get(),
            'admin' => SendPayslipProcess::orderBy('created_at', 'desc')->get(),
            default => [],
        };

        return view('livewire.portal.payslips.all', compact('jobs'))->layout('components.layouts.dashboard');
    }
}
