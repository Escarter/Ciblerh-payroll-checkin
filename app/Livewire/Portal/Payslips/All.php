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

   // Soft delete properties
   public $activeTab = 'active';
   public $selectedJobs = [];
   public $selectAll = false;

    public function initData($job_id) {
        $this->send_payslip_process = SendPayslipProcess::findOrFail($job_id);
    }

     public function delete($jobId = null)
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        $job = $jobId ? SendPayslipProcess::findOrFail($jobId) : $this->send_payslip_process;

        if(!empty($job))
        {
            auditLog(
                auth()->user(),
                'delete_payslip_process',
                'web',
                __('Delete Payslip process for ') . $job->month . "-" . $job->year . " @ " . now()
            );

            $job->delete(); // Soft delete
        }
        $this->reset(['send_payslip_process']);
        $this->closeModalAndFlashMessage(__('Payslip Process successfully moved to trash!'), 'DeleteModal');
    }

    public function restore($jobId)
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        $job = SendPayslipProcess::withTrashed()->findOrFail($jobId);
        $job->restore();

        $this->closeModalAndFlashMessage(__('Payslip Process successfully restored!'), 'RestoreModal');
    }

    public function forceDelete($jobId)
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        $job = SendPayslipProcess::withTrashed()->findOrFail($jobId);
        
        auditLog(
            auth()->user(),
            'force_delete_payslip_process',
            'web',
            __('Permanently delete Payslip process for ') . $job->month . "-" . $job->year . " @ " . now()
        );

        $job->payslips()->forceDelete(); // Permanently delete related payslips
        $job->forceDelete(); // Permanently delete the process

        $this->closeModalAndFlashMessage(__('Payslip Process permanently deleted!'), 'ForceDeleteModal');
    }

    public function bulkDelete()
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        $jobs = SendPayslipProcess::whereIn('id', $this->selectedJobs)->get();
        
        foreach ($jobs as $job) {
            auditLog(
                auth()->user(),
                'bulk_delete_payslip_process',
                'web',
                __('Bulk delete Payslip process for ') . $job->month . "-" . $job->year . " @ " . now()
            );
            $job->delete(); // Soft delete
        }

        $this->selectedJobs = [];
        $this->selectAll = false;
        $this->closeModalAndFlashMessage(__('Selected payslip processes successfully moved to trash!'), 'BulkDeleteModal');
    }

    public function bulkRestore()
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        $jobs = SendPayslipProcess::withTrashed()->whereIn('id', $this->selectedJobs)->get();
        
        foreach ($jobs as $job) {
            $job->restore();
        }

        $this->selectedJobs = [];
        $this->selectAll = false;
        $this->closeModalAndFlashMessage(__('Selected payslip processes successfully restored!'), 'BulkRestoreModal');
    }

    public function bulkForceDelete()
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        $jobs = SendPayslipProcess::withTrashed()->whereIn('id', $this->selectedJobs)->get();
        
        foreach ($jobs as $job) {
            auditLog(
                auth()->user(),
                'bulk_force_delete_payslip_process',
                'web',
                __('Bulk permanently delete Payslip process for ') . $job->month . "-" . $job->year . " @ " . now()
            );
            $job->payslips()->forceDelete(); // Permanently delete related payslips
            $job->forceDelete(); // Permanently delete the process
        }

        $this->selectedJobs = [];
        $this->selectAll = false;
        $this->closeModalAndFlashMessage(__('Selected payslip processes permanently deleted!'), 'BulkForceDeleteModal');
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->selectedJobs = [];
        $this->selectAll = false;
    }

    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedJobs = $this->getAllJobs()->pluck('id')->toArray();
        } else {
            $this->selectedJobs = [];
        }
    }

    public function toggleJobSelection($jobId)
    {
        if (in_array($jobId, $this->selectedJobs)) {
            $this->selectedJobs = array_diff($this->selectedJobs, [$jobId]);
        } else {
            $this->selectedJobs[] = $jobId;
        }
        
        // Update selectAll state
        $allJobs = $this->getAllJobs();
        $this->selectAll = count($this->selectedJobs) === $allJobs->count() && $allJobs->count() > 0;
    }

    private function getJobs()
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

        return $query->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);
    }

    private function getAllJobs()
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

        return $query->orderBy($this->orderBy, $this->orderAsc)->get();
    }

    public function render()
    {
        if (!Gate::allows('payslip-read')) {
            return abort(401);
        }

        $jobs = $this->getJobs();

        // Get counts for active jobs (non-deleted)
        $active_jobs = match (auth()->user()->getRoleNames()->first()) {
            'manager' => SendPayslipProcess::manager()->whereNull('deleted_at')->count(),
            'supervisor' => SendPayslipProcess::whereIn('author_id', auth()->user()->supDepartments->pluck('id'))->whereNull('deleted_at')->count(),
            'admin' => SendPayslipProcess::whereNull('deleted_at')->count(),
            default => 0,
        };

        // Get counts for deleted jobs
        $deleted_jobs = match (auth()->user()->getRoleNames()->first()) {
            'manager' => SendPayslipProcess::manager()->withTrashed()->whereNotNull('deleted_at')->count(),
            'supervisor' => SendPayslipProcess::whereIn('author_id', auth()->user()->supDepartments->pluck('id'))->withTrashed()->whereNotNull('deleted_at')->count(),
            'admin' => SendPayslipProcess::withTrashed()->whereNotNull('deleted_at')->count(),
            default => 0,
        };

        return view('livewire.portal.payslips.all', [
            'jobs' => $jobs,
            'jobs_count' => $active_jobs, // Legacy for backward compatibility
            'active_jobs' => $active_jobs,
            'deleted_jobs' => $deleted_jobs,
        ])->layout('components.layouts.dashboard');
    }
}
