<?php

namespace App\Livewire\Portal\Payslips;

use App\Livewire\Traits\WithDataTable;
use App\Models\Payslip;
use Livewire\Component;
use App\Models\SendPayslipProcess;

class Details extends Component
{
    use WithDataTable;

    public $job;

    public function mount($id)
    {
        $this->job = SendPayslipProcess::findOrFail($id);
    }

    public function render()
    {
        $payslip_details = Payslip::search($this->query)->where('send_payslip_process_id',$this->job->id)->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);

        return view('livewire.portal.payslips.details', [
            'payslips' => $payslip_details,
            'payslips_count' => count($this->job->payslips),
            'job' => $this->job
        ])->layout('components.layouts.dashboard');
    }
}
