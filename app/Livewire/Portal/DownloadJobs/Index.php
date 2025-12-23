<?php

namespace App\Livewire\Portal\DownloadJobs;

use App\Models\DownloadJob;
use App\Models\ScheduledReport;
use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use App\Services\ReportGenerationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

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
    
    // Soft delete properties
    public $selectedJobsForDelete = [];
    public $selectAllForDelete = false;

    // Modal states
    public $showCreateModal = false;
    public $showDetailsModal = false;
    public $showDeleteModal = false;
    public $showBulkDeleteModal = false;
    public $selectedJob = null;
    public $jobToDelete = null;
    public $scheduledReportToDelete = null;
    public $scheduledReportToRestore = null;
    public $scheduledReportToForceDelete = null;

    // Create report properties
    public $newReport = [
        'job_type' => '',
        'format' => 'xlsx',
        'filters' => [
            'selectedCompanyId' => 'all',
            'selectedDepartmentId' => 'all',
            'employee_id' => [], // Changed to array for multiple select
            'status' => 'all',
            'start_date' => '',
            'end_date' => '',
            'query_string' => ''
        ],
        // Schedule options
        'is_scheduled' => false,
        'schedule_name' => '',
        'recipients' => [],
        'recipient_email' => '',
        'frequency' => 'monthly',
        'day_of_month' => 1,
        'time' => '09:00',
        'timezone' => 'Africa/Douala',
        'is_active' => true,
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
        $this->reset(['selectedJobs', 'selectAll', 'selectedJobsForDelete', 'selectAllForDelete']);
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

    public function updatedSelectedJobs()
    {
        $jobs = $this->getJobs();
        $currentPageIds = $jobs->items() ? collect($jobs->items())->pluck('id')->map(fn($id) => (string) $id)->toArray() : [];
        $this->selectAll = !empty($currentPageIds) && count($currentPageIds) === count(array_intersect($currentPageIds, $this->selectedJobs));
    }

    public function updatedSelectAllForDelete($value)
    {
        if ($value) {
            $jobs = $this->getJobs();
            $this->selectedJobsForDelete = $jobs->items() ? collect($jobs->items())->pluck('id')->map(fn($id) => (string) $id)->toArray() : [];
        } else {
            $this->selectedJobsForDelete = [];
        }
    }

    public function toggleSelectAllForDelete()
    {
        $this->selectAllForDelete = !$this->selectAllForDelete;
        if ($this->selectAllForDelete) {
            $jobs = $this->getJobs();
            $this->selectedJobsForDelete = $jobs->items() ? collect($jobs->items())->pluck('id')->map(fn($id) => (string) $id)->toArray() : [];
        } else {
            $this->selectedJobsForDelete = [];
        }
    }

    public function updatedSelectedJobsForDelete()
    {
        $jobs = $this->getJobs();
        $currentPageIds = $jobs->items() ? collect($jobs->items())->pluck('id')->map(fn($id) => (string) $id)->toArray() : [];
        $this->selectAllForDelete = !empty($currentPageIds) && count($currentPageIds) === count(array_intersect($currentPageIds, $this->selectedJobsForDelete));
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
                // Show all non-deleted reports regardless of status
                // No status filter needed - show pending, processing, completed, failed, cancelled
            })
            ->when($this->activeTab === 'deleted', function ($q) {
                return $q->onlyTrashed();
            })
            ->when($this->activeTab === 'scheduled', function ($q) {
                // For scheduled tab, return empty collection as we'll show scheduled reports separately
                return $q->whereRaw('1 = 0'); // Return empty result
            })
            ->orderBy('created_at', 'desc');

        return $query->paginate(20);
    }

    public function getActiveJobsCountProperty()
    {
        // Count all non-deleted reports regardless of status
        return DownloadJob::forUser(auth()->id())->count();
    }

    public function getDeletedJobsCountProperty()
    {
        // Count both deleted DownloadJobs and deleted ScheduledReports
        $deletedJobs = DownloadJob::forUser(auth()->id())->onlyTrashed()->count();
        $deletedScheduled = ScheduledReport::where('user_id', auth()->id())->onlyTrashed()->count();
        return $deletedJobs + $deletedScheduled;
    }

    public function getScheduledReportsCountProperty()
    {
        // Count only non-deleted scheduled reports
        return ScheduledReport::where('user_id', auth()->id())->count();
    }

    public function downloadFile($jobId)
    {
        if (!Gate::allows('downloadjob-read')) {
            return abort(401);
        }

        $job = DownloadJob::findOrFail($jobId);

        if (!$job->canBeDownloaded()) {
            $this->dispatch("showToast", message: __('reports.file_not_available'), type: "danger");
            return;
        }

        // Check if file exists (file_path is relative to public disk)
        if (!Storage::disk('public')->exists($job->file_path)) {
            $this->dispatch("showToast", message: __('reports.file_not_found'), type: "danger");
            return;
        }

        try {
            return Storage::disk('public')->download($job->file_path, $job->file_name);
        } catch (\Exception $e) {
            $this->dispatch("showToast", message: __('reports.unable_to_download_file'), type: "danger");
            \Log::danger('Download danger: ' . $e->getMessage());
        }
    }

    public function cancelJob($jobId)
    {
        if (!Gate::allows('downloadjob-cancel')) {
            return abort(401);
        }

        $job = DownloadJob::findOrFail($jobId);

        if (!$job->canBeCancelled()) {
            $this->dispatch("showToast", message: __('download_jobs.job_cannot_be_cancelled'), type: "danger");
            return;
        }

        try {
            $job->update(['status' => DownloadJob::STATUS_CANCELLED]);
            $this->dispatch("showToast", message: __('download_jobs.job_cancelled_successfully'), type: "success");
            $this->loadStats();
        } catch (\Exception $e) {
            $this->dispatch("showToast", message: __('download_jobs.unable_to_cancel_job'), type: "danger");
        }
    }

    public function confirmDeleteJob($jobId)
    {
        if (!Gate::allows('downloadjob-delete')) {
            return abort(401);
        }

        $job = DownloadJob::findOrFail($jobId);

        if (!$job->canBeDeleted()) {
            $this->dispatch("showToast", message: __('download_jobs.report_cannot_be_deleted'), type: "danger");
            return;
        }

        $this->jobToDelete = $job;
        $this->showDeleteModal = true;
    }

    public function delete()
    {
        if (!Gate::allows('downloadjob-delete')) {
            return abort(401);
        }

        // Handle both DownloadJob and ScheduledReport deletion
        if ($this->jobToDelete) {
            try {
                $this->jobToDelete->delete(); // Soft delete
                $this->dispatch("showToast", message: __('download_jobs.report_deleted_successfully'), type: "success");
                $this->loadStats();
            } catch (\Exception $e) {
                $this->dispatch("showToast", message: __('download_jobs.unable_to_delete_report'), type: "danger");
            } finally {
                $this->jobToDelete = null;
            }
        } elseif ($this->scheduledReportToDelete) {
            try {
                $this->scheduledReportToDelete->delete();
                $this->dispatch("showToast", message: __('scheduled_reports.scheduled_report_deleted'), type: "success");
            } catch (\Exception $e) {
                $this->dispatch("showToast", message: __('scheduled_reports.error_deleting_scheduled_report'), type: "danger");
            } finally {
                $this->scheduledReportToDelete = null;
            }
        } else {
            $this->dispatch("showToast", message: __('download_jobs.report_not_found'), type: "danger");
        }

        $this->showDeleteModal = false;
    }

    public $jobToRestore = null;
    public $job_id = null;

    public function confirmRestore($jobId)
    {
        $this->job_id = $jobId;
        $this->jobToRestore = DownloadJob::withTrashed()
            ->where('user_id', auth()->id())
            ->findOrFail($jobId);
    }

    public function restore($jobId = null)
    {
        if (!Gate::allows('downloadjob-restore')) {
            return abort(401);
        }

        // Use parameter if provided, otherwise use properties
        $idToRestore = $jobId ?? $this->job_id;
        
        // Handle both DownloadJob and ScheduledReport restoration
        if ($this->jobToRestore) {
            try {
                $this->jobToRestore->restore();
                $this->dispatch("showToast", message: __('download_jobs.report_restored_successfully'), type: "success");
                $this->loadStats();
            } catch (\Exception $e) {
                $this->dispatch("showToast", message: __('download_jobs.unable_to_restore_report'), type: "danger");
            } finally {
                $this->jobToRestore = null;
                $this->job_id = null;
            }
        } elseif ($this->scheduledReportToRestore) {
            try {
                $this->scheduledReportToRestore->restore();
                $this->dispatch("showToast", message: __('scheduled_reports.scheduled_report_restored'), type: "success");
            } catch (\Exception $e) {
                $this->dispatch("showToast", message: __('scheduled_reports.error_restoring_scheduled_report'), type: "danger");
            } finally {
                $this->scheduledReportToRestore = null;
                $this->job_id = null;
            }
        } elseif ($idToRestore) {
            // Try to restore as DownloadJob first
            try {
                $job = DownloadJob::withTrashed()
                    ->where('user_id', auth()->id())
                    ->findOrFail($idToRestore);
                $job->restore();
                $this->dispatch("showToast", message: __('download_jobs.report_restored_successfully'), type: "success");
                $this->loadStats();
            } catch (\Exception $e) {
                // If not a DownloadJob, try ScheduledReport
                try {
                    $scheduledReport = ScheduledReport::withTrashed()
                        ->where('user_id', auth()->id())
                        ->findOrFail($idToRestore);
                    $scheduledReport->restore();
                    $this->dispatch("showToast", message: __('scheduled_reports.scheduled_report_restored'), type: "success");
                } catch (\Exception $e2) {
                    $this->dispatch("showToast", message: __('download_jobs.report_not_found'), type: "danger");
                }
            } finally {
                $this->job_id = null;
                $this->jobToRestore = null;
            }
        } else {
            $this->dispatch("showToast", message: __('download_jobs.report_not_found'), type: "danger");
        }
    }

    public $jobToForceDelete = null;

    public function confirmForceDelete($jobId)
    {
        $this->jobToForceDelete = DownloadJob::withTrashed()
            ->where('user_id', auth()->id())
            ->findOrFail($jobId);
    }

    public function forceDelete()
    {
        if (!Gate::allows('downloadjob-delete')) {
            return abort(401);
        }

        // Handle both DownloadJob and ScheduledReport force deletion
        if ($this->jobToForceDelete) {
            try {
                // Delete associated file
                if ($this->jobToForceDelete->file_path && Storage::disk('public')->exists($this->jobToForceDelete->file_path)) {
                    Storage::disk('public')->delete($this->jobToForceDelete->file_path);
                }
                $this->jobToForceDelete->forceDelete();
                $this->dispatch("showToast", message: __('download_jobs.report_permanently_deleted'), type: "success");
                $this->loadStats();
            } catch (\Exception $e) {
                $this->dispatch("showToast", message: __('download_jobs.unable_to_permanently_delete_report'), type: "danger");
            } finally {
                $this->jobToForceDelete = null;
            }
        } elseif ($this->scheduledReportToForceDelete) {
            try {
                $this->scheduledReportToForceDelete->forceDelete();
                $this->dispatch("showToast", message: __('scheduled_reports.scheduled_report_permanently_deleted'), type: "success");
            } catch (\Exception $e) {
                $this->dispatch("showToast", message: __('scheduled_reports.error_deleting_scheduled_report'), type: "danger");
            } finally {
                $this->scheduledReportToForceDelete = null;
            }
        } else {
            $this->dispatch("showToast", message: __('download_jobs.report_not_found'), type: "danger");
        }
    }

    public function forceDeleteOld($jobId)
    {
        try {
            $job = DownloadJob::withTrashed()
                ->where('user_id', auth()->id())
                ->findOrFail($jobId);
            
            // Delete associated file
            if ($job->file_path && Storage::disk('public')->exists($job->file_path)) {
                Storage::disk('public')->delete($job->file_path);
            }
            
            $job->forceDelete();
            $this->dispatch("showToast", message: __('download_jobs.report_permanently_deleted'), type: "success");
            $this->loadStats();
        } catch (\Exception $e) {
            $this->dispatch("showToast", message: __('download_jobs.unable_to_permanently_delete_report'), type: "danger");
        }
    }

    public function viewJobDetails($jobId)
    {
        if (!Gate::allows('downloadjob-read')) {
            return abort(401);
        }

        $this->selectedJob = DownloadJob::findOrFail($jobId);
        $this->showDetailsModal = true;
        
        // Use JavaScript to show the modal
        $this->dispatch('show-details-modal');
    }

    public function bulkCancel()
    {
        if (!Gate::allows('downloadjob-cancel')) {
            return abort(401);
        }

        if (empty($this->selectedJobs)) {
            $this->dispatch("showToast", message: __('download_jobs.please_select_jobs_to_cancel'), type: "danger");
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

    public function bulkDelete()
    {
        if (!Gate::allows('downloadjob-bulkdelete')) {
            return abort(401);
        }

        if (empty($this->selectedJobs)) {
            $this->dispatch("showToast", message: __('download_jobs.please_select_reports_to_delete'), type: "danger");
            return;
        }

        // Active tab - soft delete selected items
        $jobs = DownloadJob::whereIn('id', $this->selectedJobs)
            ->where('user_id', auth()->id())
            ->whereIn('status', [DownloadJob::STATUS_COMPLETED, DownloadJob::STATUS_FAILED, DownloadJob::STATUS_CANCELLED])
            ->get();

        $deleted = 0;
        foreach ($jobs as $job) {
            try {
                $job->delete(); // Soft delete
                $deleted++;
            } catch (\Exception $e) {
                // Continue with other jobs
            }
        }

        $this->selectedJobs = [];
        $this->selectAll = false;

        $this->dispatch("showToast", message: __('download_jobs.reports_deleted_successfully', ['count' => $deleted]), type: "success");
        $this->loadStats();
    }

    public function bulkRestore()
    {
        if (!Gate::allows('downloadjob-bulkrestore')) {
            return abort(401);
        }

        if (empty($this->selectedJobsForDelete)) {
            $this->dispatch("showToast", message: __('download_jobs.please_select_reports_to_restore'), type: "danger");
            return;
        }

        $jobs = DownloadJob::withTrashed()
            ->whereIn('id', $this->selectedJobsForDelete)
            ->where('user_id', auth()->id())
            ->get();

        $restored = 0;
        foreach ($jobs as $job) {
            try {
                $job->restore();
                $restored++;
            } catch (\Exception $e) {
                // Continue with other jobs
            }
        }

        $this->dispatch("showToast", message: __('download_jobs.reports_restored_successfully', ['count' => $restored]), type: "success");
        $this->reset(['selectedJobsForDelete', 'selectAllForDelete']);
        $this->loadStats();
    }

    public function bulkForceDelete()
    {
        if (!Gate::allows('downloadjob-delete')) {
            return abort(401);
        }

        if (empty($this->selectedJobsForDelete)) {
            $this->dispatch("showToast", message: __('download_jobs.please_select_reports_to_permanently_delete'), type: "danger");
            return;
        }

        $jobs = DownloadJob::withTrashed()
            ->whereIn('id', $this->selectedJobsForDelete)
            ->where('user_id', auth()->id())
            ->get();

        $deleted = 0;
        foreach ($jobs as $job) {
            try {
                // Delete associated file
                if ($job->file_path && Storage::disk('public')->exists($job->file_path)) {
                    Storage::disk('public')->delete($job->file_path);
                }
                $job->forceDelete();
                $deleted++;
            } catch (\Exception $e) {
                // Continue with other jobs
            }
        }

        $this->dispatch("showToast", message: __('download_jobs.reports_permanently_deleted', ['count' => $deleted]), type: "success");
        $this->reset(['selectedJobsForDelete', 'selectAllForDelete']);
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
        if (!Gate::allows('downloadjob-read')) {
            return abort(401);
        }

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

        // Filter by selected company if not "all"
        if (!empty($this->newReport['filters']['selectedCompanyId']) && $this->newReport['filters']['selectedCompanyId'] !== 'all') {
            $query->where('company_id', $this->newReport['filters']['selectedCompanyId']);
        }

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

        // Filter by selected company if not "all"
        if (!empty($this->newReport['filters']['selectedCompanyId']) && $this->newReport['filters']['selectedCompanyId'] !== 'all') {
            $query->where('company_id', $this->newReport['filters']['selectedCompanyId']);
        }

        // Filter by selected department if not "all"
        if (!empty($this->newReport['filters']['selectedDepartmentId']) && $this->newReport['filters']['selectedDepartmentId'] !== 'all') {
            $query->where('department_id', $this->newReport['filters']['selectedDepartmentId']);
        }

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
        if (!Gate::allows('downloadjob-create')) {
            return abort(401);
        }

        try {
            // Ensure employee_id is properly formatted as array of integers
            if (isset($this->newReport['filters']['employee_id'])) {
                $employeeId = $this->newReport['filters']['employee_id'];
                // Convert to array if it's a string or single value
                if (!is_array($employeeId)) {
                    if ($employeeId === 'all' || empty($employeeId) || $employeeId === null) {
                        $this->newReport['filters']['employee_id'] = [];
                    } else {
                        // Convert to integer array
                        $id = is_numeric($employeeId) ? (int)$employeeId : null;
                        $this->newReport['filters']['employee_id'] = $id ? [$id] : [];
                    }
                } else {
                    // Ensure all values are integers and filter out invalid values
                    $this->newReport['filters']['employee_id'] = array_values(
                        array_filter(
                            array_map(function($val) {
                                return is_numeric($val) ? (int)$val : null;
                            }, $employeeId),
                            fn($val) => $val !== null && $val > 0
                        )
                    );
                }
            }
            
            // Validate the form
            $validationRules = [
                'newReport.job_type' => 'required|string',
                'newReport.format' => 'required|string',
            ];

            // If scheduling, add schedule validation
            if ($this->newReport['is_scheduled']) {
                $validationRules['newReport.schedule_name'] = 'required|string|max:255';
                $validationRules['newReport.recipients'] = 'required|array|min:1';
                $validationRules['newReport.recipients.*'] = 'email';
                $validationRules['newReport.frequency'] = 'required|in:monthly,weekly,daily';
                $validationRules['newReport.day_of_month'] = 'required|integer|min:1|max:28';
                $validationRules['newReport.time'] = 'required|date_format:H:i';
            }

            $this->validate($validationRules);

            if ($this->newReport['is_scheduled']) {
                // Create scheduled report
                $scheduledReport = ScheduledReport::create([
                    'user_id' => auth()->id(),
                    'name' => $this->newReport['schedule_name'],
                    'job_type' => $this->newReport['job_type'],
                    'report_format' => $this->newReport['format'],
                    'filters' => $this->newReport['filters'],
                    'report_config' => [],
                    'recipients' => $this->newReport['recipients'],
                    'frequency' => $this->newReport['frequency'],
                    'day_of_month' => $this->newReport['day_of_month'],
                    'time' => $this->newReport['time'],
                    'timezone' => $this->newReport['timezone'],
                    'is_active' => $this->newReport['is_active'] ?? true,
                    'next_run_at' => (new ScheduledReport())->calculateNextRun(),
                ]);

                $this->dispatch("showToast", message: __('scheduled_reports.scheduled_report_created'), type: "success");
            } else {
                // Log filters for debugging (can be removed later)
                \Log::debug('Creating download job with filters', [
                    'job_type' => $this->newReport['job_type'],
                    'employee_id' => $this->newReport['filters']['employee_id'] ?? 'not_set',
                    'employee_id_type' => gettype($this->newReport['filters']['employee_id'] ?? null),
                    'employee_id_count' => is_array($this->newReport['filters']['employee_id'] ?? null) ? count($this->newReport['filters']['employee_id']) : 'not_array'
                ]);
                
                // Create immediate download job
                $job = ReportGenerationService::createJob(
                    $this->newReport['job_type'],
                    $this->newReport['filters'],
                    ['format' => $this->newReport['format']]
                );

                $this->dispatch("showToast", message: __('download_jobs.report_generation_started'), type: "success");
            }

            // Reset the form
            $this->resetNewReport();

            // Close modal
            $this->showCreateModal = false;
            $this->dispatch('hide-create-modal');

            // Refresh stats
            $this->loadStats();
        } catch (\Exception $e) {
            \Log::error('Error in generateNewReport:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->dispatch("showToast", message: __('download_jobs.error_starting_report_generation') . $e->getMessage(), type: "danger");
        }
    }

    public function generateReportName()
    {
        if (empty($this->newReport['job_type'])) {
            return '';
        }

        $reportType = $this->getAvailableJobTypes()[$this->newReport['job_type']] ?? '';
        $parts = [$reportType];

        // Add company name if selected
        if (!empty($this->newReport['filters']['selectedCompanyId']) && $this->newReport['filters']['selectedCompanyId'] !== 'all') {
            $company = Company::find($this->newReport['filters']['selectedCompanyId']);
            if ($company) {
                $parts[] = $company->name;
            }
        }

        // Add department name if selected
        if (!empty($this->newReport['filters']['selectedDepartmentId']) && $this->newReport['filters']['selectedDepartmentId'] !== 'all') {
            $department = Department::find($this->newReport['filters']['selectedDepartmentId']);
            if ($department) {
                $parts[] = $department->name;
            }
        }

        // Add date range if specified
        if (!empty($this->newReport['filters']['start_date']) && !empty($this->newReport['filters']['end_date'])) {
            $startDate = \Carbon\Carbon::parse($this->newReport['filters']['start_date'])->format('M Y');
            $endDate = \Carbon\Carbon::parse($this->newReport['filters']['end_date'])->format('M Y');
            if ($startDate === $endDate) {
                $parts[] = $startDate;
            } else {
                $parts[] = $startDate . ' - ' . $endDate;
            }
        } elseif (!empty($this->newReport['filters']['start_date'])) {
            $parts[] = \Carbon\Carbon::parse($this->newReport['filters']['start_date'])->format('M Y');
        }

        // Add frequency if scheduled
        if ($this->newReport['is_scheduled']) {
            $frequencyLabels = [
                'monthly' => __('scheduled_reports.monthly'),
                'weekly' => __('scheduled_reports.weekly'),
                'daily' => __('scheduled_reports.daily'),
            ];
            $parts[] = $frequencyLabels[$this->newReport['frequency']] ?? '';
        }

        return implode(' - ', array_filter($parts));
    }

    public function updatedNewReportJobType($value)
    {
        // Reset filters when report type changes
        $this->newReport['filters'] = [
            'selectedCompanyId' => 'all',
            'selectedDepartmentId' => 'all',
            'employee_id' => [],
            'status' => 'all',
            'start_date' => '',
            'end_date' => '',
            'query_string' => ''
        ];
        
        // Auto-generate schedule name if scheduling is enabled
        if ($this->newReport['is_scheduled']) {
            $this->newReport['schedule_name'] = $this->generateReportName();
        }
    }

    public function updatedNewReportFiltersSelectedCompanyId($value)
    {
        // When company changes, reset department and employee filters
        $this->newReport['filters']['selectedDepartmentId'] = 'all';
        $this->newReport['filters']['employee_id'] = [];
        
        // Auto-generate schedule name if scheduling is enabled
        if ($this->newReport['is_scheduled']) {
            $this->newReport['schedule_name'] = $this->generateReportName();
        }
    }

    public function updatedNewReportFiltersSelectedDepartmentId($value)
    {
        // When department changes, reset employee filter
        $this->newReport['filters']['employee_id'] = [];
        
        // Auto-generate schedule name if scheduling is enabled
        if ($this->newReport['is_scheduled']) {
            $this->newReport['schedule_name'] = $this->generateReportName();
        }
    }

    public function updatedNewReport($value, $key)
    {
        // Handle employee_id array updates specifically
        if ($key === 'filters.employee_id') {
            // Ensure it's always an array of integers
            if (!is_array($value)) {
                if ($value === 'all' || empty($value) || $value === null) {
                    $this->newReport['filters']['employee_id'] = [];
                } else {
                    // Convert single value to array of integers
                    $id = is_numeric($value) ? (int)$value : null;
                    $this->newReport['filters']['employee_id'] = $id ? [$id] : [];
                }
            } else {
                // Clean and validate array - convert to integers
                $this->newReport['filters']['employee_id'] = array_values(
                    array_filter(
                        array_map(function($val) {
                            return is_numeric($val) ? (int)$val : null;
                        }, $value),
                        fn($val) => $val !== null && $val > 0
                    )
                );
            }
        }
        
        // Auto-generate schedule name when relevant fields change
        if (strpos($key, 'filters.') === 0 || $key === 'is_scheduled' || $key === 'frequency') {
            if ($this->newReport['is_scheduled'] && !empty($this->newReport['job_type'])) {
                $this->newReport['schedule_name'] = $this->generateReportName();
            }
        }
    }
    
    /**
     * Method to update employee_id from the multi-select component
     * This allows the JavaScript to call a dedicated method
     */
    public function updateEmployeeIds($employeeIds)
    {
        // Log incoming data for debugging
        \Log::debug('updateEmployeeIds called', [
            'incoming' => $employeeIds,
            'type' => gettype($employeeIds),
            'is_array' => is_array($employeeIds)
        ]);
        
        // Ensure it's always an array
        if (!is_array($employeeIds)) {
            if ($employeeIds === 'all' || empty($employeeIds) || $employeeIds === null) {
                $this->newReport['filters']['employee_id'] = [];
            } else {
                // Convert single value to array
                $id = is_numeric($employeeIds) ? (int)$employeeIds : null;
                $this->newReport['filters']['employee_id'] = $id ? [$id] : [];
            }
        } else {
            // Clean and validate array - convert to integers and remove invalid values
            $validIds = array_values(
                array_filter(
                    array_map(function($val) {
                        // Convert to int if numeric, otherwise return null
                        if (is_numeric($val)) {
                            return (int)$val;
                        }
                        return null;
                    }, $employeeIds),
                    fn($val) => $val !== null && $val > 0
                )
            );
            $this->newReport['filters']['employee_id'] = $validIds;
        }
        
        // Log the result
        \Log::debug('updateEmployeeIds result', [
            'stored' => $this->newReport['filters']['employee_id'],
            'count' => count($this->newReport['filters']['employee_id'] ?? [])
        ]);
        
        // Auto-generate schedule name if scheduling is enabled
        if ($this->newReport['is_scheduled'] && !empty($this->newReport['job_type'])) {
            $this->newReport['schedule_name'] = $this->generateReportName();
        }
    }

    public function addRecipient()
    {
        if (!empty($this->newReport['recipient_email']) && filter_var($this->newReport['recipient_email'], FILTER_VALIDATE_EMAIL)) {
            if (!in_array($this->newReport['recipient_email'], $this->newReport['recipients'])) {
                $this->newReport['recipients'][] = $this->newReport['recipient_email'];
            }
            $this->newReport['recipient_email'] = '';
        }
    }

    public function removeRecipient($index)
    {
        unset($this->newReport['recipients'][$index]);
        $this->newReport['recipients'] = array_values($this->newReport['recipients']);
        
        // Regenerate name if scheduled
        if ($this->newReport['is_scheduled']) {
            $this->newReport['schedule_name'] = $this->generateReportName();
        }
    }

    public function getScheduledReports()
    {
        $query = ScheduledReport::where('user_id', auth()->id());
        
        // If we're on the deleted tab, show deleted scheduled reports
        if ($this->activeTab === 'deleted') {
            $query->onlyTrashed();
        }
        
        return $query->orderBy('created_at', 'desc')->get();
    }

    public function toggleScheduledReport($id)
    {
        if (!Gate::allows('downloadjob-update')) {
            return abort(401);
        }

        $scheduledReport = ScheduledReport::where('user_id', auth()->id())->findOrFail($id);
        $scheduledReport->update([
            'is_active' => !$scheduledReport->is_active,
        ]);
        $this->dispatch("showToast", message: __('scheduled_reports.scheduled_report_updated'), type: "success");
    }

    public function confirmDeleteScheduledReport($id)
    {
        if (!Gate::allows('downloadjob-delete')) {
            return abort(401);
        }

        $this->scheduledReportToDelete = ScheduledReport::where('user_id', auth()->id())->findOrFail($id);
        $this->showDeleteModal = true;
    }

    public function deleteScheduledReport()
    {
        if (!$this->scheduledReportToDelete) {
            $this->dispatch("showToast", message: __('scheduled_reports.error_deleting_scheduled_report'), type: "danger");
            return;
        }

        try {
            $this->scheduledReportToDelete->delete();
            $this->dispatch("showToast", message: __('scheduled_reports.scheduled_report_deleted'), type: "success");
        } catch (\Exception $e) {
            $this->dispatch("showToast", message: __('scheduled_reports.error_deleting_scheduled_report'), type: "danger");
        } finally {
            $this->scheduledReportToDelete = null;
            $this->showDeleteModal = false;
        }
    }

    public function confirmRestoreScheduledReport($id)
    {
        if (!Gate::allows('downloadjob-restore')) {
            return abort(401);
        }

        $this->scheduledReportToRestore = ScheduledReport::where('user_id', auth()->id())->onlyTrashed()->findOrFail($id);
        $this->job_id = $id; // Set job_id for restore modal compatibility
    }

    public function confirmForceDeleteScheduledReport($id)
    {
        if (!Gate::allows('downloadjob-delete')) {
            return abort(401);
        }

        $this->scheduledReportToForceDelete = ScheduledReport::where('user_id', auth()->id())->onlyTrashed()->findOrFail($id);
    }

    public function openCreateModal()
    {
        if (!Gate::allows('downloadjob-create')) {
            return abort(401);
        }

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


    public function resetNewReport()
    {
        $this->newReport = [
            'job_type' => '',
            'format' => 'xlsx',
            'filters' => [
                'selectedCompanyId' => 'all',
                'selectedDepartmentId' => 'all',
                'employee_id' => [],
                'status' => 'all',
                'start_date' => '',
                'end_date' => '',
                'query_string' => ''
            ],
            'is_scheduled' => false,
            'schedule_name' => '',
            'recipients' => [],
            'recipient_email' => '',
            'frequency' => 'monthly',
            'day_of_month' => 1,
            'time' => '09:00',
            'timezone' => 'Africa/Douala',
            'is_active' => true,
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
        if (!Gate::allows('downloadjob-read')) {
            return abort(401);
        }

        return view('livewire.portal.download-jobs.index', [
            'jobs' => $this->getJobs(),
            'availableJobTypes' => $this->getAvailableJobTypes(),
            'companies' => $this->getCompanies(),
            'departments' => $this->getDepartments(),
            'employees' => $this->getEmployees(),
            'scheduledReports' => $this->getScheduledReports(),
        ])->layout('components.layouts.dashboard');
    }
}