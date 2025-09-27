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
            session()->flash('error', __('File is not available for download.'));
            return;
        }

        // Check if file exists (file_path is relative to public disk)
        if (!Storage::disk('public')->exists($job->file_path)) {
            session()->flash('error', __('File not found. Please contact your administrator.'));
            return;
        }

        try {
            return Storage::disk('public')->download($job->file_path, $job->file_name);
        } catch (\Exception $e) {
            session()->flash('error', __('Unable to download file. Please try again.'));
            \Log::error('Download error: ' . $e->getMessage());
        }
    }

    public function cancelJob($jobId)
    {
        $job = DownloadJob::findOrFail($jobId);

        if (!$job->canBeCancelled()) {
            session()->flash('error', __('Job cannot be cancelled in its current status.'));
            return;
        }

        try {
            $job->update(['status' => DownloadJob::STATUS_CANCELLED]);
            session()->flash('message', __('Job cancelled successfully.'));
            $this->loadStats();
        } catch (\Exception $e) {
            session()->flash('error', __('Unable to cancel job. Please try again.'));
        }
    }

    public function confirmDeleteJob($jobId)
    {
        $job = DownloadJob::findOrFail($jobId);

        if (!$job->canBeDeleted()) {
            session()->flash('error', __('Report cannot be deleted in its current status.'));
            return;
        }

        $this->jobToDelete = $job;
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        if (!$this->jobToDelete) {
            session()->flash('error', __('Report not found.'));
            return;
        }

        try {
            // Delete associated file
            if ($this->jobToDelete->file_path && Storage::disk('public')->exists($this->jobToDelete->file_path)) {
                Storage::disk('public')->delete($this->jobToDelete->file_path);
            }

            $this->jobToDelete->delete();
            session()->flash('message', __('Report deleted successfully.'));
            $this->loadStats();
        } catch (\Exception $e) {
            session()->flash('error', __('Unable to delete report. Please try again.'));
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
            session()->flash('error', __('Please select jobs to cancel.'));
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

        session()->flash('message', __(':count jobs cancelled successfully.', ['count' => $cancelled]));
        $this->reset(['selectedJobs', 'selectAll']);
        $this->loadStats();
    }

    public function confirmBulkDelete()
    {
        if (empty($this->selectedJobs)) {
            session()->flash('error', __('Please select reports to delete.'));
            return;
        }

        $this->showBulkDeleteModal = true;
    }

    public function bulkDelete()
    {
        if (empty($this->selectedJobs)) {
            session()->flash('error', __('Please select reports to delete.'));
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

        session()->flash('message', __(':count reports deleted successfully.', ['count' => $deleted]));
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
        session()->flash('message', __('Jobs refreshed successfully.'));
    }

    public function getAvailableJobTypes()
    {
        return ReportGenerationService::getAvailableJobTypes();
    }

    public function getCompanies()
    {
        return Company::where('is_active', true)->orderBy('name')->get();
    }

    public function getDepartments()
    {
        return Department::where('is_active', true)->with('company')->orderBy('name')->get();
    }

    public function getEmployees()
    {
        return User::where('status', true)
            ->whereHas('roles', function($query) {
                $query->where('name', 'employee');
            })
            ->with(['company', 'department'])
            ->orderBy('first_name')
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

            session()->flash('message', __('Report generation started successfully! You will be notified when it\'s ready.'));
        } catch (\Exception $e) {
            logger('Error in generateNewReport:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            session()->flash('error', __('Error starting report generation: ') . $e->getMessage());
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