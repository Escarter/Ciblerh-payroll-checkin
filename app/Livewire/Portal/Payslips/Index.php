<?php

namespace App\Livewire\Portal\Payslips;

use App\Models\Company;
use App\Models\Setting;
use App\Services\Nexah;
use Livewire\Component;
use App\Models\Department;
use App\Services\TwilioSMS;
use App\Services\AwsSnsSMS;
use Illuminate\Support\Str;
use App\Models\SendPayslipProcess;
use Illuminate\Support\Facades\Gate;
use App\Jobs\Plan\PayslipSendingPlan;
use App\Livewire\Traits\WithDataTable;
use Illuminate\Support\Facades\Storage;

class Index extends Component
{
    use WithDataTable;
    
    public $companies = [];
    public $departments = [];
    public $company_id, $department_id, $month, $payslip_file;
    public ?int $job_id = null;

    public ?SendPayslipProcess $send_payslip_process;
    
    // Soft delete properties
    public $activeTab = 'active';
    public $selectedSendPayslipProcesses = [];
    public $selectAll = false;

    public function initData($job_id)
    {
        $this->send_payslip_process = SendPayslipProcess::findOrFail($job_id);
    }

    public function delete($processId = null)
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        $process = $processId ? SendPayslipProcess::findOrFail($processId) : $this->send_payslip_process;
        
        if (!empty($process)) {
            auditLog(
                auth()->user(),
                'delete_payslip_process',
                'web',
                __('payslips.delete_payslip_process') . $process->month . "-" . $process->year . " @ " . now()
            );
            $process->payslips()->delete(); // Soft delete related payslips
            $process->delete(); // Soft delete the process
        }
        $this->reset(['send_payslip_process']);
        $this->closeModalAndFlashMessage(__('payslips.payslip_process_moved_to_trash'), 'DeleteModal');
    }

    public function restore()
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        $process = SendPayslipProcess::withTrashed()->findOrFail($this->job_id);
        $process->restore();

        $this->closeModalAndFlashMessage(__('payslips.payslip_process_restored'), 'RestoreModal');
    }

    public function forceDelete($processId)
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        $process = SendPayslipProcess::withTrashed()->findOrFail($processId);
        $process->payslips()->forceDelete(); // Force delete related payslips
        $process->forceDelete(); // Force delete the process

        $this->closeModalAndFlashMessage(__('payslips.payslip_process_permanently_deleted'), 'ForceDeleteModal');
    }

    public function bulkDelete()
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedSendPayslipProcesses)) {
            $processes = SendPayslipProcess::whereIn('id', $this->selectedSendPayslipProcesses);
            foreach ($processes->get() as $process) {
                $process->payslips()->delete(); // Soft delete related payslips
            }
            $processes->delete(); // Soft delete the processes
            $this->selectedSendPayslipProcesses = [];
        }

        $this->closeModalAndFlashMessage(__('payslips.selected_payslip_processes_moved_to_trash'), 'BulkDeleteModal');
    }

    public function bulkRestore()
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedSendPayslipProcesses)) {
            SendPayslipProcess::withTrashed()->whereIn('id', $this->selectedSendPayslipProcesses)->restore();
            $this->selectedSendPayslipProcesses = [];
        }

        $this->closeModalAndFlashMessage(__('payslips.selected_payslip_processes_restored'), 'BulkRestoreModal');
    }

    public function bulkForceDelete()
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedSendPayslipProcesses)) {
            $processes = SendPayslipProcess::withTrashed()->whereIn('id', $this->selectedSendPayslipProcesses);
            foreach ($processes->get() as $process) {
                $process->payslips()->forceDelete(); // Force delete related payslips
            }
            $processes->forceDelete(); // Force delete the processes
            $this->selectedSendPayslipProcesses = [];
        }

        $this->closeModalAndFlashMessage(__('payslips.selected_payslip_processes_permanently_deleted'), 'BulkForceDeleteModal');
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->selectedSendPayslipProcesses = [];
        $this->selectAll = false;
    }

    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedSendPayslipProcesses = $this->getSendPayslipProcesses()->pluck('id')->toArray();
        } else {
            $this->selectedSendPayslipProcesses = [];
        }
    }

    public function toggleSendPayslipProcessSelection($processId)
    {
        if (in_array($processId, $this->selectedSendPayslipProcesses)) {
            $this->selectedSendPayslipProcesses = array_diff($this->selectedSendPayslipProcesses, [$processId]);
        } else {
            $this->selectedSendPayslipProcesses[] = $processId;
        }
        
        $this->selectAll = count($this->selectedSendPayslipProcesses) === $this->getSendPayslipProcesses()->count();
    }

    private function getSendPayslipProcesses()
    {
        $query = match (auth()->user()->getRoleNames()->first()) {
            'manager' => SendPayslipProcess::manager(),
            'supervisor' => SendPayslipProcess::whereIn('author_id', auth()->user()->supDepartments->pluck('id')),
            'admin' => SendPayslipProcess::query(),
            default => SendPayslipProcess::query(),
        };

        // Add soft delete filtering based on active tab
        if ($this->activeTab === 'deleted') {
            $query->withTrashed()->whereNotNull('deleted_at');
        } else {
            $query->whereNull('deleted_at');
        }

        return $query->orderBy('created_at', 'desc')->take(20)->get();
    }


    public function mount()
    {
        $this->role = auth()->user()->getRoleNames()->first();
        $this->companies = match (auth()->user()->getRoleNames()->first()) {
            'manager' => Company::manager()->orderBy('created_at', 'desc')->get(),
            'admin' => Company::orderBy('created_at', 'desc')->get(),
            'supervisor' => [],
            default => [],
        };

        $this->departments =  match (auth()->user()->getRoleNames()->first()) {
            'manager' => [],
            'supervisor' => Department::whereIn('id', auth()->user()->supDepartments->pluck('department_id'))->get(),
            'admin' => [],
            default => [],
        };
        
    }

    public function updatedCompanyId($company_id)
    {
        if (!is_null($company_id)) {
            $this->departments = Department::where('company_id', $company_id)->get();
            $this->department_id = null; // Reset department selection
            $this->dispatch('departments-updated');
        } else {
            $this->departments = [];
            $this->department_id = null;
            $this->dispatch('departments-updated');
        }
    }

    public function send()
    {
        if (!Gate::allows('payslip-create')) {
            return abort(401);
        }

        $this->validate([
            'department_id' => 'required',
            'month' => 'required',
            'payslip_file' => 'required|mimes:pdf'
        ]);

        $setting = Setting::first();

        if(!empty($setting)){
            // Check SMTP settings
            if (empty($setting->smtp_host) && empty($setting->smtp_port)) {
                $this->showToast(__('payslips.smtp_setting_required'), 'danger');
                return;
            }

            // Check SMS settings
            if (empty($setting->sms_provider)) {
                $this->showToast(__('payslips.sms_provider_required'), 'danger');
                return;
            }
        }else{
            $this->showToast(__('payslips.smtp_sms_settings_required'), 'danger');
            return;
        }


        $raw_file_path = $this->payslip_file->store(auth()->user()->id, 'raw');

        $choosen_department = Department::findOrFail($this->department_id);

        $raw_file = Storage::disk('raw')->path($raw_file_path);

        $splitted_disk = Storage::disk('splitted');
        $modified_disk = Storage::disk('modified');

        $destination_directory = Str::random(20);


        if (countPages(Storage::disk('raw')->path($raw_file_path)) > config('ciblerh.max_payslip_pages')) {
            $this->showToast(__('payslips.file_upload_max_pages', ['max' => config('ciblerh.max_payslip_pages')]), 'danger');
            return $this->redirect(route('portal.payslips.index'), navigate: true);
        }

        $existing = SendPayslipProcess::where('department_id', $this->department_id)->where('month', $this->month)->where('year', now()->year)->first();

        if (empty($existing)) {
            $payslip_process =
                SendPayslipProcess::create([
                    'user_id' => auth()->user()->id,
                    'company_id' => !empty($this->company_id) ? $this->company_id : auth()->user()->company_id,
                    'department_id' => $this->department_id,
                    'author_id' => auth()->user()->id,
                    'raw_file' => $raw_file,
                    'destination_directory' => $destination_directory,
                    'month' => $this->month,
                    'year' => now()->year,
                    'batch_id' => ''
                ]);
        } else {
            $existing->update(['status' => 'processing', 'batch_id' => '']);
            $payslip_process = $existing;
        }

        PayslipSendingPlan::start($payslip_process);

        auditLog(
            auth()->user(),
            'payslip_sending',
            'web',
            __('payslips.user_initiated_payslip_sending', [
                'user_id' => auth()->user()->id,
                'user_name' => auth()->user()->name,
                'department_name' => $choosen_department->name,
                'month' => $this->month,
                'year' => now()->year
            ])
        );

        $this->showToast(__('payslips.job_processing_status'), 'success');
        return $this->redirect(route('portal.payslips.index'), navigate: true);
    }

    public function render()
    {
        if (!Gate::allows('payslip-read')) {
            return abort(401);
        }

        $jobs = $this->getSendPayslipProcesses();

        // Get counts for active processes (non-deleted)
        $active_processes = match (auth()->user()->getRoleNames()->first()) {
            'manager' => SendPayslipProcess::manager()->whereNull('deleted_at')->count(),
            'supervisor' => SendPayslipProcess::whereIn('author_id', auth()->user()->supDepartments->pluck('id'))->whereNull('deleted_at')->count(),
            'admin' => SendPayslipProcess::whereNull('deleted_at')->count(),
            default => 0,
        };

        $deleted_processes = match (auth()->user()->getRoleNames()->first()) {
            'manager' => SendPayslipProcess::manager()->withTrashed()->whereNotNull('deleted_at')->count(),
            'supervisor' => SendPayslipProcess::whereIn('author_id', auth()->user()->supDepartments->pluck('id'))->withTrashed()->whereNotNull('deleted_at')->count(),
            'admin' => SendPayslipProcess::withTrashed()->whereNotNull('deleted_at')->count(),
            default => 0,
        };

        return view('livewire.portal.payslips.index', [
            'jobs' => $jobs,
            'active_processes' => $active_processes,
            'deleted_processes' => $deleted_processes,
        ])->layout('components.layouts.dashboard');
    }
}
