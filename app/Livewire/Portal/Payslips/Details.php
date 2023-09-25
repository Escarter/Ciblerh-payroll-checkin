<?php

namespace App\Livewire\Portal\Payslips;

use Livewire\Component;
use App\Models\SendPayslipProcess;

class Details extends Component
{
    public $job;

    public function mount($id)
    {
        $this->job = SendPayslipProcess::findOrFail($id);
    }

    public function render()
    {
        $payslip_details = $this->job->payslips->sortByDesc('created_at');

        return view('livewire.portal.payslips.details', [
            'payslips' => $payslip_details,
            'job' => $this->job
        ])->layout('components.layouts.dashboard');
    }
}
