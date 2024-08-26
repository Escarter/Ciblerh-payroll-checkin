<?php

namespace App\Livewire\Portal\Payslips;

use Livewire\Component;
use App\Models\SendPayslipProcess;
use Illuminate\Support\Facades\Gate;
use App\Livewire\Traits\WithDataTable;

class All extends Component
{
    use WithDataTable;

   public ?SendPayslipProcess $send_payslip_process;

    public function initData($job_id) {
        $this->send_payslip_process = SendPayslipProcess::findOrFail($job_id);
    }

     public function delete()
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        if(!empty($this->send_payslip_process))
        {
            $this->send_payslip_process->delete();
        }
        $this->reset(['send_payslip_process']);
        $this->closeModalAndFlashMessage(__('Payslip Process successfully deleted!'), 'DeleteModal');
    }



    public function render()
    {
        if (!Gate::allows('payslip-read')) {
            return abort(401);
        }

        $jobs =  match (auth()->user()->getRoleNames()->first()) {
            'manager' => SendPayslipProcess::manager()->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage),
            'supervisor' => SendPayslipProcess::whereIn('author_id', auth()->user()->supDepartments->pluck('id'))->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage),
            'admin' => SendPayslipProcess::orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage),
            default => [],
        };
        $jobs_count =  match (auth()->user()->getRoleNames()->first()) {
            'manager' => SendPayslipProcess::manager()->count(),
            'supervisor' => SendPayslipProcess::whereIn('author_id', auth()->user()->supDepartments->pluck('id'))->count(),
            'admin' => SendPayslipProcess::count(),
            default => [],
        };

        return view('livewire.portal.payslips.all', compact('jobs','jobs_count'))->layout('components.layouts.dashboard');
    }
}
