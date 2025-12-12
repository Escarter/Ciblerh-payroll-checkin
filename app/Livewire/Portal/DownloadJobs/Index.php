<?php

namespace App\Livewire\Portal\DownloadJobs;

use App\Models\DownloadJob;
use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use App\Services\ReportGenerationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    protected $paginationTheme = "bootstrap";

    // Filters and search
    public $jobTypeFilter = '';
    public $statusFilter = '';
    public $searchQuery = '';
    public $dateFrom = '';
    public $dateTo = '';

    // Tabs
    public $activeTab = 'active';

    // Bulk actions
    public $selectedJobs = [];
    public $selectAll = false;

    // Modal states
    public $showCreateModal = false;
    public $showDetailsModal = false;
    public $showDeleteModal = false;
    public $showBulkDeleteModal = false;
    public $selectedJob = null;
    public $jobToDelete = null;

    // Create report properties
    public $newReport = [
        'job_type' => '',
        'format' => 'xlsx',
        'filters' => [
            'selectedCompanyId' => 'all',
            'selectedDepartmentId' => 'all',
            'employee_id' => 'all',
            'status' => 'all',
            'start_date' => '',
            'end_date' => '',
            'query_string' => ''
        ]
    ];

    // Stats
    public $stats = [];

    public function mount()
    {
        $this->loadStats();
    }

    public function loadStats()
    {
        $this->stats = ReportGenerationService::getUserJobStats(auth()->id());
    }

    public function updatedActiveTab()
    {
        $this->resetPage();
        $this->reset(['selectedJobs', 'selectAll']);
    }

    public function updatedJobTypeFilter()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedSearchQuery()
    {
        $this->resetPage();
    }

    public function updatedDateFrom()
    {
        $this->resetPage();
    }

    public function updatedDateTo()
    {
        $this->resetPage();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $jobs = $this->getJobs();
            $this->selectedJobs = $jobs->items() ? collect($jobs->items())->pluck('id')->map(fn($id) => (string) $id)->toArray() : [];
        } else {
            $this->selectedJobs = [];
        }
    }

    public function getJobs()
    {
        $query = DownloadJob::forUser(auth()->id())
            ->when($this->jobTypeFilter, function ($q) {
                return $q->where('job_type', $this->jobTypeFilter);
            })
            ->when($this->statusFilter, function ($q) {
                return $q->where('status', $this->statusFilter);
            })
            ->when($this->searchQuery, function ($q) {
                return $q->where(function ($query) {
                    $query->where('uuid', 'like', '%' . $this->searchQuery . '%')
                        ->orWhere('file_name', 'like', '%' . $this->searchQuery . '%')
                        ->orWhere('job_type', 'like', '%' . $this->searchQuery . '%');
                });
            })
            ->when($this->dateFrom, function ($q) {
                return $q->whereDate('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                return $q->whereDate('created_at', '<=', $this->dateTo);
            })
            ->when($this->activeTab === 'active', function ($q) {
                return $q->active();
            })
            ->when($this->activeTab === 'completed', function ($q) {
                return $q->completed();
            })
            ->when($this->activeTab === 'failed', function ($q) {
                return $q->failed();
            })
            ->orderBy('created_at', 'desc');

        return $query->paginate(20);
    }

    public function getActiveJobsCountProperty()
    {
        return DownloadJob::forUser(auth()->id())
            ->whereIn('status', [DownloadJob::STATUS_PENDING, DownloadJob::STATUS_PROCESSING])
            ->count();
    }

    public function getCompletedJobsCountProperty()
    {
        return DownloadJob::forUser(auth()->id())
            ->where('status', DownloadJob::STATUS_COMPLETED)
            ->count();
    }

    public function getFailedJobsCountProperty()
    {
        return DownloadJob::forUser(auth()->id())
            ->where('status', DownloadJob::STATUS_FAILED)
            ->count();
    }

    public function getAllJobsCountProperty()
    {
        return DownloadJob::forUser(auth()->id())->count();
    }

    public function downloadFile($jobId)
    {
        $job = DownloadJob::findOrFail($jobId);

        if (!$job->canBeDownloaded()) {
            $this->dispatch("showToast", message: __('File is not available for download.'), type: "error");
            return;
        }

        // Check if file exists (file_path is relative to public disk)
        if (!Storage::disk('public')->exists($job->file_path)) {
            $this->dispatch("showToast", message: __('File not found. Please contact your administrator.'), type: "error");
            return;
        }

        try {
            return Storage::disk('public')->download($job->file_path, $job->file_name);
        } catch (\Exception $e) {
            $this->dispatch("showToast", message: __('Unable to download file. Please try again.'), type: "error");
            \Log::error('Download error: ' . $e->getMessage());
        }
    }

    public function cancelJob($jobId)
    {
        $job = DownloadJob::findOrFail($jobId);

        if (!$job->canBeCancelled()) {
            $this->dispatch("showToast", message: __('download_jobs.job_cannot_be_cancelled'), type: "error");
            return;
        }

        try {
            $job->update(['status' => DownloadJob::STATUS_CANCELLED]);
            $this->dispatch("showToast", message: __('download_jobs.job_cancelled_successfully'), type: "success");
            $this->loadStats();
        } catch (\Exception $e) {
            $this->dispatch("showToast", message: __('download_jobs.unable_to_cancel_job'), type: "error");
        }
    }

    public function confirmDeleteJob($jobId)
    {
        $job = DownloadJob::findOrFail($jobId);

        if (!$job->canBeDeleted()) {
            $this->dispatch("showToast", message: __('download_jobs.report_cannot_be_deleted'), type: "error");
            return;
        }

        $this->jobToDelete = $job;
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        if (!$this->jobToDelete) {
            $this->dispatch("showToast", message: __('download_jobs.report_not_found'), type: "error");
            return;
        }

        try {
            // Delete associated file
            if ($this->jobToDelete->file_path && Storage::disk('public')->exists($this->jobToDelete->file_path)) {
                Storage::disk('public')->delete($this->jobToDelete->file_path);
            }

            $this->jobToDelete->delete();
            $this->dispatch("showToast", message: __('download_jobs.report_deleted_successfully'), type: "success");
            $this->loadStats();
        } catch (\Exception $e) {
            $this->dispatch("showToast", message: __('download_jobs.unable_to_delete_report'), type: "error");
        } finally {
            $this->showDeleteModal = false;
            $this->jobToDelete = null;
        }
    }

    public function viewJobDetails($jobId)
    {
        $this->selectedJob = DownloadJob::findOrFail($jobId);
        $this->showDetailsModal = true;
        
        // Use JavaScript to show the modal
        $this->dispatch('show-details-modal');
    }

    public function bulkCancel()
    {
        if (empty($this->selectedJobs)) {
            $this->dispatch("showToast", message: __('download_jobs.please_select_jobs_to_cancel'), type: "error");
            return;
        }

        $jobs = DownloadJob::whereIn('id', $this->selectedJobs)
            ->where('user_id', auth()->id())
            ->whereIn('status', [DownloadJob::STATUS_PENDING, DownloadJob::STATUS_PROCESSING])
            ->get();

        $cancelled = 0;
        foreach ($jobs as $job) {
            try {
                $job->update(['status' => DownloadJob::STATUS_CANCELLED]);
                $cancelled++;
            } catch (\Exception $e) {
                // Continue with other jobs
            }
        }

        $this->dispatch("showToast", message: __('download_jobs.jobs_cancelled_successfully', ['count' => $cancelled]), type: "success");
        $this->reset(['selectedJobs', 'selectAll']);
        $this->loadStats();
    }

    public function confirmBulkDelete()
    {
        if (empty($this->selectedJobs)) {
            $this->dispatch("showToast", message: __('download_jobs.please_select_reports_to_delete'), type: "error");
            return;
        }

        $this->showBulkDeleteModal = true;
    }

    public function bulkDelete()
    {
        if (empty($this->selectedJobs)) {
            $this->dispatch("showToast", message: __('download_jobs.please_select_reports_to_delete'), type: "error");
            return;
        }

        $jobs = DownloadJob::whereIn('id', $this->selectedJobs)
            ->where('user_id', auth()->id())
            ->whereIn('status', [DownloadJob::STATUS_COMPLETED, DownloadJob::STATUS_FAILED, DownloadJob::STATUS_CANCELLED])
            ->get();

        $deleted = 0;
        foreach ($jobs as $job) {
            try {
                // Delete associated file
                if ($job->file_path && Storage::disk('public')->exists($job->file_path)) {
                    Storage::disk('public')->delete($job->file_path);
                }
                $job->delete();
                $deleted++;
            } catch (\Exception $e) {
                // Continue with other jobs
            }
        }

        $this->dispatch("showToast", message: __('download_jobs.reports_deleted_successfully', ['count' => $deleted]), type: "success");
        $this->reset(['selectedJobs', 'selectAll']);
        $this->showBulkDeleteModal = false;
        $this->loadStats();
    }

    public function clearFilters()
    {
        $this->reset([
            'jobTypeFilter', 'statusFilter', 'searchQuery', 
            'dateFrom', 'dateTo', 'selectedJobs', 'selectAll'
        ]);
        $this->resetPage();
    }

    public function refreshJobs()
    {
        $this->loadStats();
        $this->dispatch("showToast", message: __('download_jobs.jobs_refreshed_successfully'), type: "success");
    }

    public function getAvailableJobTypes()
    {
        return ReportGenerationService::getAvailableJobTypes();
    }

    public function getCompanies()
    {
        $query = Company::where('is_active', true);

        // Filter by managed companies if user is a manager
        if (auth()->user()->hasRole('manager')) {
            $query->whereIn('id', auth()->user()->managerCompanies->pluck('id'));
        }

        return $query->orderBy('name')->get();
    }

    public function getDepartments()
    {
        $query = Department::where('is_active', true)->with('company');

        // Filter by departments from managed companies if user is a manager
        if (auth()->user()->hasRole('manager')) {
            $query->whereIn('company_id', auth()->user()->managerCompanies->pluck('id'));
        }

        return $query->orderBy('name')->get();
    }

    public function getEmployees()
    {
        $query = User::where('status', true)
            ->whereHas('roles', function($query) {
                $query->where('name', 'employee');
            })
            ->whereDoesntHave('roles', function($query) {
                $query->where('name', 'admin');
            })
            ->with(['company', 'department']);

        // Filter by managed companies if user is a manager
        if (auth()->user()->hasRole('manager')) {
            $query->whereIn('company_id', auth()->user()->managerCompanies->pluck('id'));
        }

        // Filter by managed departments if user is a supervisor
        if (auth()->user()->hasRole('supervisor')) {
            $query->whereIn('department_id', auth()->user()->supDepartments->pluck('department_id'));
        }

        return $query->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    public function generateNewReport()
    {
        try {
            // Debug: Log the form data
            logger('Form data:', $this->newReport);
            
            // Validate the form
            $this->validate([
                'newReport.job_type' => 'required|string',
                'newReport.format' => 'required|string',
            ]);

            // Create the download job
            $job = ReportGenerationService::createJob(
                $this->newReport['job_type'],
                $this->newReport['filters'],
                ['format' => $this->newReport['format']]
            );

            // Reset the form
            $this->resetNewReport();

            // Close modal
            $this->showCreateModal = false;
            $this->dispatch('hide-create-modal');

            // Refresh stats
            $this->loadStats();

            $this->dispatch("showToast", message: __('download_jobs.report_generation_started'), type: "success");
        } catch (\Exception $e) {
            logger('Error in generateNewReport:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->dispatch("showToast", message: __('download_jobs.error_starting_report_generation') . $e->getMessage(), type: "error");
        }
    }

    public function openCreateModal()
    {
        $this->showCreateModal = true;
        $this->resetNewReport();
        
        // Use JavaScript to show the modal
        $this->dispatch('show-create-modal');
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        
        // Use JavaScript to hide the modal
        $this->dispatch('hide-create-modal');
    }

    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->selectedJob = null;
        
        // Use JavaScript to hide the modal
        $this->dispatch('hide-details-modal');
    }

    public function updatedNewReportJobType($value)
    {
        // Reset filters when report type changes
        $this->newReport['filters'] = [
            'selectedCompanyId' => 'all',
            'selectedDepartmentId' => 'all',
            'employee_id' => 'all',
            'status' => 'all',
            'start_date' => '',
            'end_date' => '',
            'query_string' => ''
        ];
    }

    public function resetNewReport()
    {
        $this->newReport = [
            'job_type' => '',
            'format' => 'xlsx',
            'filters' => [
                'selectedCompanyId' => 'all',
                'selectedDepartmentId' => 'all',
                'employee_id' => 'all',
                'status' => 'all',
                'start_date' => '',
                'end_date' => '',
                'query_string' => ''
            ]
        ];
    }


    // Computed properties for stats and counts
    public function getStatsProperty()
    {
        $jobs = DownloadJob::forUser(auth()->id());
        
        return [
            'total' => $jobs->count(),
            'pending' => $jobs->where('status', DownloadJob::STATUS_PENDING)->count(),
            'processing' => $jobs->where('status', DownloadJob::STATUS_PROCESSING)->count(),
            'completed' => $jobs->where('status', DownloadJob::STATUS_COMPLETED)->count(),
            'failed' => $jobs->where('status', DownloadJob::STATUS_FAILED)->count(),
            'cancelled' => $jobs->where('status', DownloadJob::STATUS_CANCELLED)->count(),
        ];
    }

    public function render()
    {
        return view('livewire.portal.download-jobs.index', [
            'jobs' => $this->getJobs(),
            'availableJobTypes' => $this->getAvailableJobTypes(),
            'companies' => $this->getCompanies(),
            'departments' => $this->getDepartments(),
            'employees' => $this->getEmployees(),
        ])->layout('components.layouts.dashboard');
    }
}