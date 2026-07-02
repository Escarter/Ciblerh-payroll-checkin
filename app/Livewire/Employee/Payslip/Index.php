<?php

namespace App\Livewire\Employee\Payslip;

use App\Models\Payslip;
use App\Services\PayslipEncryptionService;
use Livewire\Component;
use App\Livewire\Traits\WithDataTable;
use Illuminate\Support\Facades\Storage;

class Index extends Component
{
    use WithDataTable;

    public function mount(): void
    {
        $this->orderBy = 'year';
        $this->orderAsc = 'desc';
    }

    public function generatePDF($payslip_id)
    {
        $payslip = Payslip::findOrFail($payslip_id);

        if (!$payslip->isVisibleToEmployee(auth()->user())) {
            abort(403, __('common.unauthorized_access'));
        }
        
        // Check if the file path is valid
        if (empty($payslip->file)) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => __('payslips.payslip_file_not_found')
            ]);
            return;
        }
        
        // Check if the file exists
        if (!Storage::disk('modified')->exists($payslip->file)) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => __('payslips.payslip_file_not_found')
            ]);
            return;
        }

        if ((int) $payslip->encryption_status !== Payslip::STATUS_SUCCESSFUL) {
            if (!app(PayslipEncryptionService::class)->ensureEncrypted($payslip->fresh(), auth()->user())) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => __('payslips.encryption_not_successful')
                ]);
                return;
            }
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

    public function viewPdf($payslip_id)
    {
        $payslip = Payslip::findOrFail($payslip_id);
        
        if (!$payslip->isVisibleToEmployee(auth()->user())) {
            abort(403, __('common.unauthorized_access'));
        }
        
        // Check if the file exists
        if (!Storage::disk('modified')->exists($payslip->file)) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => __('payslips.payslip_file_not_found')
            ]);
            return;
        }
        
        try {
            $filePath = Storage::disk('modified')->path($payslip->file);
            return response()->file($filePath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"'
            ]);
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => __('payslips.unable_to_view_payslip')
            ]);
        }
    }

    public function render()
    {
        $user = auth()->user();
        $payslips = Payslip::search($this->query)
            ->visibleToEmployee($user)
            ->orderBy($this->orderBy, $this->orderAsc)
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
        $payslips_count = Payslip::visibleToEmployee($user)->count();

        return view('livewire.employee.payslip.index', compact('payslips', 'payslips_count'))->layout('components.layouts.employee.master');
    }
}
