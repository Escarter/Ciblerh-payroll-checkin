<?php

namespace App\Livewire\Employee\Payslip;

use App\Models\Payslip;
use Livewire\Component;
use App\Livewire\Traits\WithDataTable;
use Illuminate\Support\Facades\Storage;

class Index extends Component
{
    use WithDataTable;

    public function generatePDF($payslip_id)
    {
        $payslip = Payslip::findOrFail($payslip_id);
        
        // Check if the file exists
        if (!Storage::disk('modified')->exists($payslip->file)) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => __('payslips.payslip_file_not_found')
            ]);
            return;
        }
        
        try {
            return response()->download(
                Storage::disk('modified')->path($payslip->file), 
                $payslip->matricule. "_" . $payslip->year.'_'.$payslip->month.'.pdf', 
                ['Content-Type'=> 'application/pdf']
            );
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => __('payslips.unable_to_download_payslip')
            ]);
        }
    }

    public function render()
    {
        $payslips = Payslip::search($this->query)->where('employee_id', auth()->user()->id)->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);
        $payslips_count = Payslip::where('employee_id', auth()->user()->id)->count();

        return view('livewire.employee.payslip.index', compact('payslips', 'payslips_count'))->layout('components.layouts.employee.master');
    }
}
