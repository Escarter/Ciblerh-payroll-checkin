<?php

namespace App\Livewire\Portal\ImportJobs;

use App\Models\ImportJob;
use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use App\Models\Service;
use App\Services\ImportService;
use App\Livewire\Traits\WithImportPreview;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

class Index extends Component
{
    use WithPagination, WithFileUploads, WithImportPreview;

    protected $paginationTheme = "bootstrap";

    // Filters and search
    public $importTypeFilter = '';
    public $statusFilter = '';
    public $searchQuery = '';
    public $dateFrom = '';
    public $dateTo = '';

    // Tabs - active and trashed
    public $activeTab = 'active';

    // Bulk actions
    public $selectedJobs = [];
    public $selectAll = false;

    // Additional filters for completed jobs
    public $completedStatus = 'all'; // all, success, partial, failed
    public $completedQuery = '';
    public $completedDateFrom = '';
    public $completedDateTo = '';

    // Combined view properties
    public $showJobDetailsModal = false;
    public $selectedJobDetails = null;

    // Modal states
    public $showDetailsModal = false;
    public $showCreateModal = false;
    public $showRetryModal = false;
    public $selectedJob = null;
    public $jobToRetry = null;
    public $job_id = null;

    // Create import properties
    public $newImport = [
        'import_type' => '',
        'file' => null,
        'company_id' => null,
        'department_id' => null,
        'service_id' => null,
        'auto_create_entities' => false,
        'send_welcome_emails' => false,
    ];
    public $filePath = null; // Path to the stored import file
    public $previewSkipped = false; // Track if preview was skipped for large files

    // Import type specific properties (for preview functionality)
    public $currentImportType = null;
    protected $currentImportConfig = null;

    // Stats
    public $stats = [];

    // Toast notifications for import completion
    public $lastCheckedCompletedJobs = [];

    // Temporary storage for cross-field validation
    protected $parsedRowData = [];

    public function mount()
    {
        $this->initializePreview();
        $this->loadStats();
        // Initialize completed jobs tracking will be done lazily on first render
    }

    public function loadStats()
    {
        $this->stats = [
            'total' => ImportJob::forUser(auth()->id())->count(),
            'active' => ImportJob::forUser(auth()->id())->count(),
            'trashed' => ImportJob::forUser(auth()->id())->onlyTrashed()->count(),
            'pending' => ImportJob::forUser(auth()->id())->where('status', ImportJob::STATUS_PENDING)->count(),
            'processing' => ImportJob::forUser(auth()->id())->where('status', ImportJob::STATUS_PROCESSING)->count(),
            'completed' => ImportJob::forUser(auth()->id())->where('status', ImportJob::STATUS_COMPLETED)->count(),
            'failed' => ImportJob::forUser(auth()->id())->where('status', ImportJob::STATUS_FAILED)->count(),
            'cancelled' => ImportJob::forUser(auth()->id())->where('status', ImportJob::STATUS_CANCELLED)->count(),
        ];
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
        $this->reset(['selectedJobs', 'selectAll']);
    }

    public function updatedImportTypeFilter()
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
            $jobs = $this->jobs;
            $this->selectedJobs = $jobs->items() ? collect($jobs->items())->pluck('id')->map(fn($id) => (string) $id)->toArray() : [];
        } else {
            $this->selectedJobs = [];
        }
    }

    public function toggleSelectAll()
    {
        $this->selectAll = !$this->selectAll;
        $this->updatedSelectAll($this->selectAll);
    }

    public function getJobs()
    {
        $query = ImportJob::forUser(auth()->id())
            ->when($this->importTypeFilter, function ($q) {
                return $q->where('import_type', $this->importTypeFilter);
            })
            ->when($this->statusFilter, function ($q) {
                return $q->where('status', $this->statusFilter);
            })
            ->when($this->searchQuery, function ($q) {
                return $q->where(function ($query) {
                    $query->where('uuid', 'like', '%' . $this->searchQuery . '%')
                        ->orWhere('file_name', 'like', '%' . $this->searchQuery . '%')
                        ->orWhere('import_type', 'like', '%' . $this->searchQuery . '%');
                });
            })
            ->when($this->dateFrom, function ($q) {
                return $q->whereDate('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                return $q->whereDate('created_at', '<=', $this->dateTo);
            })
            ->orderBy('created_at', 'desc');

        return $query->paginate(20);
    }

    public function getTrashedJobs()
    {
        $query = ImportJob::forUser(auth()->id())
            ->onlyTrashed()
            ->when($this->importTypeFilter, function ($q) {
                return $q->where('import_type', $this->importTypeFilter);
            })
            ->when($this->statusFilter, function ($q) {
                return $q->where('status', $this->statusFilter);
            })
            ->when($this->searchQuery, function ($q) {
                return $q->where(function ($query) {
                    $query->where('uuid', 'like', '%' . $this->searchQuery . '%')
                        ->orWhere('file_name', 'like', '%' . $this->searchQuery . '%')
                        ->orWhere('import_type', 'like', '%' . $this->searchQuery . '%');
                });
            })
            ->when($this->dateFrom, function ($q) {
                return $q->whereDate('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($q) {
                return $q->whereDate('created_at', '<=', $this->dateTo);
            })
            ->orderBy('created_at', 'desc');

        return $query->paginate(20);
    }

    public function getCompaniesProperty()
    {
        return ImportService::getAvailableCompanies();
    }

    public function getDepartmentsProperty()
    {
        return ImportService::getAvailableDepartments($this->newImport['company_id']);
    }

    public function getServicesProperty()
    {
        return ImportService::getAvailableServices();
    }

    public function getActiveJobsCountProperty()
    {
        return ImportJob::forUser(auth()->id())->count();
    }

    // Completed Jobs Methods
    public function updatedCompletedStatus()
    {
        $this->resetPage();
    }

    public function updatedCompletedQuery()
    {
        $this->resetPage();
    }

    public function updatedCompletedDateFrom()
    {
        $this->resetPage();
    }

    public function updatedCompletedDateTo()
    {
        $this->resetPage();
    }

    public function getCompletedJobs()
    {
        $query = ImportJob::forUser(auth()->id())
            ->whereIn('status', [ImportJob::STATUS_COMPLETED, ImportJob::STATUS_FAILED, ImportJob::STATUS_CANCELLED])
            ->with(['user', 'company', 'department'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($this->completedStatus !== 'all') {
            switch ($this->completedStatus) {
                case 'success':
                    $query->where('status', ImportJob::STATUS_COMPLETED)->where('failed_imports', 0);
                    break;
                case 'partial':
                    $query->where('status', ImportJob::STATUS_COMPLETED)->where('failed_imports', '>', 0);
                    break;
                case 'failed':
                    $query->whereIn('status', [ImportJob::STATUS_FAILED, ImportJob::STATUS_CANCELLED]);
                    break;
            }
        }

        // Search functionality
        if (!empty($this->completedQuery)) {
            $query->where(function ($q) {
                $q->where('file_name', 'like', '%' . $this->completedQuery . '%')
                  ->orWhere('import_type', 'like', '%' . $this->completedQuery . '%')
                  ->orWhere('uuid', 'like', '%' . $this->completedQuery . '%')
                  ->orWhereHas('company', function ($companyQuery) {
                      $companyQuery->where('name', 'like', '%' . $this->completedQuery . '%');
                  });
            });
        }

        // Date filters
        if ($this->completedDateFrom) {
            $query->whereDate('created_at', '>=', $this->completedDateFrom);
        }

        if ($this->completedDateTo) {
            $query->whereDate('created_at', '<=', $this->completedDateTo);
        }

        return $query->paginate(20, ['*'], 'completedPage');
    }

    public function getFailedJobs()
    {
        return ImportJob::forUser(auth()->id())
            ->whereIn('status', [ImportJob::STATUS_FAILED, ImportJob::STATUS_CANCELLED])
            ->with(['user', 'company', 'department'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    public function showJobDetails($jobId)
    {
        $this->selectedJobDetails = ImportJob::with(['user', 'company', 'department'])
            ->findOrFail($jobId);
        $this->showJobDetailsModal = true;

        // Use JavaScript to show the modal
        $this->dispatch('show-job-details-modal');
    }

    public function closeJobDetailsModal()
    {
        $this->showJobDetailsModal = false;
        $this->selectedJobDetails = null;

        // Use JavaScript to hide the modal
        $this->dispatch('hide-job-details-modal');
    }

    public function clearCompletedFilters()
    {
        $this->reset([
            'completedStatus', 'completedQuery',
            'completedDateFrom', 'completedDateTo'
        ]);
        $this->resetPage();
    }

    public function getTrashedJobsCountProperty()
    {
        return ImportJob::forUser(auth()->id())->onlyTrashed()->count();
    }

    public function getJobsProperty()
    {
        switch ($this->activeTab) {
            case 'active':
                return $this->getJobs();
            case 'trashed':
                return $this->getTrashedJobs();
            default:
                return $this->getJobs();
        }
    }

    public function cancelJob($jobId)
    {
        $job = ImportJob::findOrFail($jobId);

        if (!$job->canBeCancelled()) {
            $this->dispatch("showToast", message: __('import_jobs.job_cannot_be_cancelled'), type: "danger");
            return;
        }

        try {
            $job->update(['status' => ImportJob::STATUS_CANCELLED]);
            $this->dispatch("showToast", message: __('import_jobs.job_cancelled_successfully'), type: "success");
            $this->loadStats();
        } catch (\Exception $e) {
            $this->dispatch("showToast", message: __('import_jobs.unable_to_cancel_job'), type: "danger");
        }
    }

    public function viewJobDetails($jobId)
    {
        $this->selectedJob = ImportJob::findOrFail($jobId);
        $this->showDetailsModal = true;

        // Use JavaScript to show the modal
        $this->dispatch('show-details-modal');
    }

    public function bulkCancel()
    {
        if (empty($this->selectedJobs)) {
            $this->dispatch("showToast", message: __('import_jobs.please_select_jobs_to_cancel'), type: "danger");
            return;
        }

        $jobs = ImportJob::whereIn('id', $this->selectedJobs)
            ->where('user_id', auth()->id())
            ->whereIn('status', [ImportJob::STATUS_PENDING, ImportJob::STATUS_PROCESSING])
            ->get();

        $cancelled = 0;
        foreach ($jobs as $job) {
            try {
                $job->update(['status' => ImportJob::STATUS_CANCELLED]);
                $cancelled++;
            } catch (\Exception $e) {
                // Continue with other jobs
            }
        }

        $this->dispatch("showToast", message: __('import_jobs.jobs_cancelled_successfully', ['count' => $cancelled]), type: "success");
        $this->reset(['selectedJobs', 'selectAll']);
        $this->loadStats();
    }

    public function retryModal($jobId = null)
    {
        // Validate job ID
        if (!$jobId) {
            $this->dispatch("showToast", message: 'Job ID is required', type: "danger");
            return;
        }

        $this->jobToRetry = ImportJob::findOrFail($jobId);

        // Check if user owns this job
        if ($this->jobToRetry->user_id !== auth()->id()) {
            $this->dispatch("showToast", message: __('import_jobs.job_not_found'), type: "danger");
            return;
        }

        // Check if job is failed (can only retry failed jobs)
        if (!$this->jobToRetry->isFailed()) {
            $this->dispatch("showToast", message: __('import_jobs.can_only_retry_failed_jobs'), type: "danger");
            return;
        }

        $this->showRetryModal = true;

        // Use JavaScript to show the modal
        $this->dispatch('show-retry-modal');
    }

    public function hideRetryModal()
    {
        $this->showRetryModal = false;
        $this->jobToRetry = null;

        // Use JavaScript to hide the modal
        $this->dispatch('hide-retry-modal');
    }

    public function confirmRetry()
    {
        if (!$this->jobToRetry) {
            $this->dispatch("showToast", message: __('import_jobs.job_not_found'), type: "danger");
            return;
        }

        try {
            // Check if the original file still exists
            if (!$this->jobToRetry->file_path || !\Storage::disk('local')->exists($this->jobToRetry->file_path)) {
                $this->dispatch("showToast", message: __('import_jobs.original_file_not_found'), type: "danger");
                return;
            }

            // Create new import job with same configuration
            $config = [
                'file_path' => $this->jobToRetry->file_path,
                'company_id' => $this->jobToRetry->company_id,
                'department_id' => $this->jobToRetry->department_id,
                'auto_create_entities' => $this->jobToRetry->import_config['auto_create_entities'] ?? false,
            ];

            $newJob = ImportService::createImportJob($this->jobToRetry->import_type, $config);

            $this->dispatch("showToast", message: __('import_jobs.job_retried_successfully'), type: "success");
            $this->loadStats();
            $this->hideRetryModal();

        } catch (\Exception $e) {
            $this->dispatch("showToast", message: __('import_jobs.error_retrying_job') . ': ' . $e->getMessage(), type: "danger");
        }
    }

    public function bulkRetry()
    {
        if (empty($this->selectedJobs)) {
            $this->dispatch("showToast", message: __('import_jobs.please_select_jobs_to_retry'), type: "danger");
            return;
        }

        $jobs = ImportJob::whereIn('id', $this->selectedJobs)
            ->where('user_id', auth()->id())
            ->where('status', ImportJob::STATUS_FAILED)
            ->get();

        $retried = 0;
        foreach ($jobs as $job) {
            try {
                // Check if the file still exists
                if ($job->file_path && \Storage::disk('local')->exists($job->file_path)) {
                    $config = [
                        'file_path' => $job->file_path,
                        'company_id' => $job->company_id,
                        'department_id' => $job->department_id,
                        'auto_create_entities' => $job->import_config['auto_create_entities'] ?? false,
                    ];

                    ImportService::createImportJob($job->import_type, $config);
                    $retried++;
                }
            } catch (\Exception $e) {
                // Continue with other jobs
            }
        }

        $this->dispatch("showToast", message: __('import_jobs.jobs_retried_successfully', ['count' => $retried]), type: "success");
        $this->reset(['selectedJobs', 'selectAll']);
        $this->loadStats();
    }

    public function bulkDelete()
    {
        if (empty($this->selectedJobs)) {
            $this->dispatch("showToast", message: __('import_jobs.please_select_jobs_to_delete'), type: "danger");
            return;
        }

        $jobs = ImportJob::whereIn('id', $this->selectedJobs)
            ->where('user_id', auth()->id())
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

        $this->dispatch("showToast", message: __('import_jobs.jobs_moved_to_trash_successfully', ['count' => $deleted]), type: "success");
        $this->reset(['selectedJobs', 'selectAll']);
        $this->loadStats();
    }

    public function bulkRestore()
    {
        if (empty($this->selectedJobs)) {
            $this->dispatch("showToast", message: __('import_jobs.please_select_jobs_to_restore'), type: "danger");
            return;
        }

        $jobs = ImportJob::withTrashed()
            ->whereIn('id', $this->selectedJobs)
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

        $this->dispatch("showToast", message: __('import_jobs.jobs_restored_successfully', ['count' => $restored]), type: "success");
        $this->reset(['selectedJobs', 'selectAll']);
        $this->loadStats();
    }

    public function bulkForceDelete()
    {
        if (empty($this->selectedJobs)) {
            $this->dispatch("showToast", message: __('import_jobs.please_select_jobs_to_delete'), type: "danger");
            return;
        }

        $jobs = ImportJob::withTrashed()
            ->whereIn('id', $this->selectedJobs)
            ->where('user_id', auth()->id())
            ->get();

        $deleted = 0;
        foreach ($jobs as $job) {
            try {
                // Delete the file if it exists
                if ($job->file_path && \Storage::exists($job->file_path)) {
                    \Storage::delete($job->file_path);
                }
                $job->forceDelete();
                $deleted++;
            } catch (\Exception $e) {
                // Continue with other jobs
            }
        }

        $this->dispatch("showToast", message: __('import_jobs.jobs_deleted_permanently', ['count' => $deleted]), type: "success");
        $this->reset(['selectedJobs', 'selectAll']);
        $this->loadStats();
    }

    public function restoreJob($jobId)
    {
        $job = ImportJob::withTrashed()->findOrFail($jobId);

        if ($job->user_id !== auth()->id()) {
            $this->dispatch("showToast", message: __('import_jobs.job_not_found'), type: "danger");
            return;
        }

        try {
            $job->restore();
            $this->dispatch("showToast", message: __('import_jobs.job_restored_successfully'), type: "success");
            $this->loadStats();
        } catch (\Exception $e) {
            $this->dispatch("showToast", message: __('import_jobs.unable_to_restore_job'), type: "danger");
        }
    }

    public function delete()
    {
        if (!$this->job_id) {
            $this->dispatch("showToast", message: __('import_jobs.job_not_found'), type: "danger");
            return;
        }

        $job = ImportJob::findOrFail($this->job_id);

        if ($job->user_id !== auth()->id()) {
            $this->dispatch("showToast", message: __('import_jobs.job_not_found'), type: "danger");
            return;
        }

        try {
            $job->delete(); // Soft delete
            $this->dispatch("showToast", message: __('import_jobs.job_moved_to_trash_successfully'), type: "success");
            $this->loadStats();
            $this->job_id = null;
            $this->dispatch('hide-delete-modal');
        } catch (\Exception $e) {
            $this->dispatch("showToast", message: __('import_jobs.unable_to_delete_job'), type: "danger");
        }
    }

    public function forceDelete()
    {
        $job = ImportJob::withTrashed()->findOrFail($this->job_id);

        if ($job->user_id !== auth()->id()) {
            $this->dispatch("showToast", message: __('import_jobs.job_not_found'), type: "danger");
            return;
        }

        try {
            // Delete the file if it exists
            if ($job->file_path && \Storage::exists($job->file_path)) {
                \Storage::delete($job->file_path);
            }
            $job->forceDelete();
            $this->dispatch("showToast", message: __('import_jobs.job_deleted_permanently'), type: "success");
            $this->loadStats();
            $this->job_id = null;
            $this->dispatch('hide-force-delete-modal');
        } catch (\Exception $e) {
            $this->dispatch("showToast", message: __('import_jobs.unable_to_delete_job'), type: "error");
        }
    }

    public function clearFilters()
    {
        $this->reset([
            'importTypeFilter', 'statusFilter', 'searchQuery',
            'dateFrom', 'dateTo', 'selectedJobs', 'selectAll'
        ]);
        $this->resetPage();
    }

    public function refreshJobs()
    {
        $this->loadStats();
        $this->dispatch("showToast", message: __('import_jobs.jobs_refreshed_successfully'), type: "success");
    }

    public function getAvailableImportTypes()
    {
        return ImportService::getAvailableImportTypes();
    }

    public function openCreateModal()
    {
        $this->showCreateModal = true;
        $this->currentStep = 'upload'; // Ensure we start with upload step
        $this->resetNewImport();

        // Use JavaScript to show the modal
        $this->dispatch('show-create-modal');
    }

    /**
     * Validate SMTP settings are configured when sending welcome emails
     * Returns true if validation passes, false if it fails
     */
    protected function validateSmtpSettingsForImport(): bool
    {
        $setting = \App\Models\Setting::first();

        if (!$setting) {
            $this->dispatch("showToast", message: __('common.smtp_not_configured'), type: "error");
            return false;
        }

        $requiredFields = ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'from_email', 'from_name'];
        $missingFields = [];

        foreach ($requiredFields as $field) {
            if (empty($setting->$field)) {
                $missingFields[] = $field;
            }
        }

        if (!empty($missingFields)) {
            $this->dispatch("showToast", message: __('common.smtp_missing_fields', ['fields' => implode(', ', $missingFields)]), type: "danger");
            return false;
        }

        return true;
    }

    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        $this->resetNewImport();

        // Use JavaScript to hide the modal
        $this->dispatch('hide-create-modal');
    }

    public function updatedNewImportImportType($value)
    {
        // Reset dependent fields when import type changes
        $this->newImport['company_id'] = null;
        $this->newImport['department_id'] = null;
        $this->newImport['auto_create_entities'] = false;
        $this->newImport['send_welcome_emails'] = false;
    }

    public function updatedNewImportCompanyId($value)
    {
        // Reset department when company changes
        $this->newImport['department_id'] = null;
    }

    public function createNewImport()
    {
        try {
            // Debug: Log file information
            $fileObject = $this->getSafeFileObject();
            if ($fileObject) {
                \Log::info('File upload debug', [
                    'original_name' => $fileObject->getClientOriginalName(),
                    'mime_type' => $fileObject->getMimeType(),
                    'extension' => $fileObject->getClientOriginalExtension(),
                    'size' => $fileObject->getSize(),
                ]);
            }

            // Validate the form
            $this->validate([
                'newImport.import_type' => 'required|string',
                'newImport.file' => 'required|file|max:' . ($this->maxFileSize * 1024),
            ]);

            // Additional custom validation for file type
            $fileObject = $this->getSafeFileObject();
            if (!$fileObject) {
                $this->addError('newImport.file', __('common.no_file_selected'));
                return;
            }

            $allowedExtensions = ['xlsx', 'xls', 'csv', 'txt'];
            $fileExtension = strtolower($fileObject->getClientOriginalExtension());

            if (!in_array($fileExtension, $allowedExtensions)) {
                $this->addError('newImport.file', __('common.file_must_be_of_type', ['types' => implode(', ', $allowedExtensions)]));
                return;
            }

            // Get import type config
            $this->currentImportConfig = ImportService::getImportTypeConfig($this->newImport['import_type']);
            $this->currentImportType = $this->newImport['import_type'];

            // Validate required fields based on import type
            $validationRules = [];
            foreach ($this->currentImportConfig['fields'] as $fieldName => $fieldConfig) {
                if ($fieldConfig['required']) {
                    if ($fieldName === 'company_id') {
                        $validationRules["newImport.{$fieldName}"] = 'required|integer|exists:companies,id';
                    } elseif ($fieldName === 'department_id') {
                        $validationRules["newImport.{$fieldName}"] = 'required|integer|exists:departments,id';
                    }
                }
            }

            if (!empty($validationRules)) {
                $this->validate($validationRules);
            }

            // Validate SMTP settings if sending welcome emails
            if ($this->newImport['send_welcome_emails'] ?? false) {
                if (!$this->validateSmtpSettingsForImport()) {
                    return; // Stop import if SMTP validation fails
                }
            }

            // Get file object for analysis (don't store yet)
            $fileObject = $this->getSafeFileObject();
            if (!$fileObject) {
                throw new \Exception(__('common.no_file_selected'));
            }

            // Store file path temporarily for analysis (will be stored permanently in confirmImport)
            $tempFileName = uniqid('temp_') . '_' . $fileObject->getClientOriginalName();
            $this->filePath = 'temp/' . $tempFileName;

            // Store temporarily for analysis only
            $fileObject->storeAs('temp', $tempFileName, 'local');

            // Perform quick file analysis to determine if we should skip preview
            $fileInfo = $this->quickFileAnalysis();

            // Clean up temp file after analysis
            if (\Storage::disk('local')->exists($this->filePath)) {
                \Storage::disk('local')->delete($this->filePath);
            }
            $this->filePath = null; // Clear the path since file was temporary

            // Initialize preview properties
            $this->initializePreview();

            if ($fileInfo['skip_preview'] ?? false) {
                // For large files, skip preview and go directly to confirmation
                $this->totalRows = $fileInfo['total_rows'];
                $this->hasLargeFile = $fileInfo['is_large'];
                $this->isRowCountEstimated = $fileInfo['estimated'] ?? false;
                $this->fileAnalysisWarning = $fileInfo['warning'] ?? $fileInfo['skip_reason'] ?? '';
                $this->previewSkipped = true; // Mark that preview was skipped
                $this->currentStep = 'confirm'; // Skip to confirmation step
            } else {
                // For small files, show preview step but process data on demand
                $this->currentStep = 'preview';
            }

        } catch (\Exception $e) {
            $this->dispatch("showToast", message: __('import_jobs.error_creating_import_job') . ': ' . $e->getMessage(), type: "danger");
        }
    }

    public function confirmImport()
    {
        \Log::info('ImportJobs/Index confirmImport called', [
            'import_type' => $this->newImport['import_type'] ?? 'unknown',
            'company_id' => $this->newImport['company_id'] ?? 'not set',
            'department_id' => $this->newImport['department_id'] ?? 'not set',
            'file_path' => $this->filePath
        ]);

        try {
            // Store the file permanently before creating the import job
            $fileObject = $this->getSafeFileObject();
            if (!$fileObject) {
                throw new \Exception(__('common.no_file_selected'));
            }

            $fileName = uniqid('import_') . '_' . $fileObject->getClientOriginalName();
            $filePath = $fileObject->storeAs('imports', $fileName, 'local');

            // Prepare config for the import
            $config = [
                'file_path' => $filePath,
                'company_id' => $this->newImport['company_id'],
                'department_id' => $this->newImport['department_id'],
                'auto_create_entities' => $this->newImport['auto_create_entities'] ?? false,
            ];

            // Create the import job
            $job = ImportService::createImportJob($this->newImport['import_type'], $config);

            // Reset the form and preview
            $this->resetNewImport();
            $this->clearPreview();
            $this->currentStep = 'upload';
            $this->currentImportType = null;
            $this->currentImportConfig = null;

            // Close modal
            $this->showCreateModal = false;
            $this->dispatch('hide-create-modal');

            // Refresh stats and show success message
            $this->loadStats();
            $this->dispatch("showToast", message: __('import_jobs.import_job_created_successfully'), type: "success");

        } catch (\Exception $e) {
            $this->dispatch("showToast", message: __('import_jobs.error_creating_import_job') . ': ' . $e->getMessage(), type: "danger");
        }
    }

    public function goBackToUpload()
    {
        $this->currentStep = 'upload';
        $this->clearPreview();
        $this->newImport['file'] = null;
    }

    public function resetNewImport()
    {
        // Clean up any stored import file if it exists
        if ($this->filePath && \Storage::disk('local')->exists($this->filePath)) {
            \Storage::disk('local')->delete($this->filePath);
        }

        $this->newImport = [
            'import_type' => '',
            'file' => null,
            'company_id' => null,
            'department_id' => null,
            'auto_create_entities' => false,
        ];
        $this->filePath = null;
        $this->previewSkipped = false;
    }

    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->selectedJob = null;

        // Use JavaScript to hide the modal
        $this->dispatch('hide-details-modal');
    }

    /**
     * Initialize tracking of completed jobs for notifications
     */
    public function initializeCompletedJobsTracking()
    {
        try {
            $this->lastCheckedCompletedJobs = collect($this->getJobs()->items())
                ->whereIn('status', [ImportJob::STATUS_COMPLETED, ImportJob::STATUS_FAILED])
                ->pluck('updated_at', 'id')
                ->toArray();
        } catch (\Exception $e) {
            // If initialization fails, start with empty array
            $this->lastCheckedCompletedJobs = [];
        }
    }

    /**
     * Check for newly completed imports and show toast notifications
     * This method is called by polling
     */
    public function checkForCompletedImports()
    {
        $currentCompletedJobs = collect($this->getJobs()->items())
            ->whereIn('status', [ImportJob::STATUS_COMPLETED, ImportJob::STATUS_FAILED])
            ->pluck('updated_at', 'id')
            ->toArray();

        $newlyCompletedJobs = [];
        foreach ($currentCompletedJobs as $jobId => $updatedAt) {
            if (!isset($this->lastCheckedCompletedJobs[$jobId]) ||
                $this->lastCheckedCompletedJobs[$jobId] != $updatedAt) {
                $newlyCompletedJobs[] = $jobId;
            }
        }

        // Show toast notifications for newly completed jobs
        foreach ($newlyCompletedJobs as $jobId) {
            $job = ImportJob::find($jobId);
            if ($job) {
                $this->showImportCompletionToast($job);
            }
        }

        // Update tracking
        $this->lastCheckedCompletedJobs = $currentCompletedJobs;

        // Refresh stats
        $this->loadStats();
    }

    /**
     * Show toast notification for import completion
     */
    protected function showImportCompletionToast(ImportJob $job)
    {
        $type = __('common.' . $job->import_type);

        if ($job->isCompleted()) {
            $message = __('import_jobs.import_completed_toast', [
                'type' => $type,
                'successful' => $job->successful_imports,
                'total' => $job->total_rows
            ]);

            if ($job->failed_imports > 0) {
                $message .= ' ' . __('import_jobs.import_with_errors_toast', [
                    'errors' => $job->failed_imports
                ]);
            }

            $this->showToast($message, 'success');
        } elseif ($job->isFailed()) {
            $message = __('import_jobs.import_failed_toast', [
                'type' => $type,
                'error' => $job->error_message ?? __('common.unknown_error')
            ]);

            $this->showToast($message, 'danger');
        }
    }

    /**
     * Show toast notification using the existing system
     */
    public function showToast($message, $type = 'success')
    {
        $this->dispatch("showToast", message: $message, type: $type);
    }

    public function render()
    {
        if (!Gate::allows('importjob-read')) {
            abort(403);
        }

        // Initialize completed jobs tracking if not already done
        if (!isset($this->lastCheckedCompletedJobs)) {
            $this->initializeCompletedJobsTracking();
        }

        return view('livewire.portal.import-jobs.index', [
            'jobs' => $this->jobs,
            'availableImportTypes' => $this->getAvailableImportTypes(),
            'companies' => $this->companies,
            'departments' => $this->departments,
            'services' => $this->services,
            'activeJobsCount' => $this->activeJobsCount,
            'trashedJobsCount' => $this->trashedJobsCount,
            'maxFileSize' => $this->maxFileSize,
        ])->layout('components.layouts.dashboard');
    }

    /**
     * Get import columns for the current import type (required by WithImportPreview)
     */
    protected function getImportColumns(): array
    {
        return $this->getColumnMapping();
    }

    /**
     * Validate preview row for the current import type (required by WithImportPreview)
     */
    protected function validatePreviewRow(array $rowData, int $rowNumber): array
    {
        if (!$this->currentImportType) {
            return ['valid' => false, 'errors' => [__('common.no_import_type_selected')], 'warnings' => []];
        }

        $errors = [];
        $warnings = [];

        // Get expected columns for this import type
        $expectedColumns = $this->getExpectedColumns();
        $requiredColumns = $this->getRequiredColumns();

        // If rowData is indexed array, map it to associative array using expected columns
        if (isset($rowData[0]) && !isset($rowData[$expectedColumns[0] ?? ''])) {
            $mappedData = [];
            foreach ($expectedColumns as $index => $column) {
                $mappedData[$column] = $rowData[$index] ?? '';
            }
            $rowData = $mappedData;
        }

        $parsedData = $rowData;

        // Check for missing required columns
        foreach ($requiredColumns as $column) {
            if (!isset($rowData[$column]) || trim($rowData[$column]) === '') {
                $errors[] = __('common.missing_required_field', ['field' => $column]);
            }
        }

        // Comprehensive validation based on import type
        switch ($this->currentImportType) {
            case ImportJob::TYPE_EMPLOYEES:
                $parsedData = $this->validateEmployeeRow($rowData, $errors, $warnings);
                break;

            case ImportJob::TYPE_COMPANIES:
                $parsedData = $this->validateCompanyRow($rowData, $errors, $warnings);
                break;

            case ImportJob::TYPE_DEPARTMENTS:
                $parsedData = $this->validateDepartmentRow($rowData, $errors, $warnings);
                break;

            case ImportJob::TYPE_SERVICES:
                $parsedData = $this->validateServiceRow($rowData, $errors, $warnings);
                break;

            case ImportJob::TYPE_LEAVE_TYPES:
                $parsedData = $this->validateLeaveTypeRow($rowData, $errors, $warnings);
                break;
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'parsed_data' => $parsedData
        ];
    }

    /**
     * Validate employee row data
     */
    protected function validateEmployeeRow(array $rowData, array &$errors, array &$warnings): array
    {
        $parsedData = $rowData;

        // Validate required fields
        $requiredFields = ['first_name', 'last_name', 'email', 'professional_phone_number',
                          'matricule', 'position', 'net_salary', 'salary_grade', 'department', 'service', 'role'];

        foreach ($requiredFields as $field) {
            if (!isset($rowData[$field]) || trim($rowData[$field]) === '') {
                $errors[] = __('common.missing_required_field', ['field' => __('common.' . $field)]);
            }
        }

        // Validate names
        if (isset($rowData['first_name']) && strlen(trim($rowData['first_name'])) < 2) {
            $errors[] = __('common.first_name_too_short');
        }
        if (isset($rowData['last_name']) && strlen(trim($rowData['last_name'])) < 2) {
            $errors[] = __('common.last_name_too_short');
        }

        // Validate email
        if (isset($rowData['email'])) {
            $email = trim($rowData['email']);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = __('common.invalid_email_format');
            } elseif (\App\Models\User::where('email', $email)->exists()) {
                $warnings[] = __('common.email_already_exists');
            }
        }

        // Validate phone number
        if (isset($rowData['professional_phone_number'])) {
            $phone = trim($rowData['professional_phone_number']);
            if (!$this->isValidPhoneNumber($phone)) {
                $errors[] = __('common.invalid_phone_format');
            } else {
                $parsedData['professional_phone_number'] = $this->formatPhoneNumber($phone);
            }
        }

        // Validate salary
        if (isset($rowData['net_salary'])) {
            $salary = $rowData['net_salary'];
            if (!is_numeric($salary) || $salary < 0) {
                $errors[] = __('common.invalid_salary_amount');
            } elseif ($salary > 10000000) { // Unreasonably high salary
                $warnings[] = __('common.salary_seems_high');
            }
        }

        // Validate role
        if (isset($rowData['role'])) {
            $validRoles = ['employee', 'supervisor', 'manager'];
            $role = strtolower(trim($rowData['role']));
            if (!in_array($role, $validRoles)) {
                $errors[] = __('common.invalid_role_must_be', ['roles' => implode(', ', $validRoles)]);
            }
            $parsedData['role'] = $role;
        }

        // Validate matricule
        if (isset($rowData['matricule'])) {
            $matricule = trim($rowData['matricule']);
            if (strlen($matricule) < 3) {
                $errors[] = __('common.matricule_too_short');
            } elseif (\App\Models\User::where('matricule', $matricule)->exists()) {
                $warnings[] = __('common.matricule_already_exists');
            }
        }

        // Validate position
        if (isset($rowData['position']) && strlen(trim($rowData['position'])) < 2) {
            $errors[] = __('common.position_too_short');
        }

        // Store parsed data temporarily for cross-field validation
        $this->parsedRowData = $parsedData;

        // Validate department and service references
        // Use form selections if provided, otherwise validate from file data
        if ($this->newImport['department_id'] ?? false) {
            // Use department selected in form
            $parsedData['department_id'] = $this->newImport['department_id'];
            $this->parsedRowData['department_id'] = $this->newImport['department_id'];
        } elseif (isset($rowData['department'])) {
            $deptResult = $this->validateEntityReference('department', $rowData['department']);
            if (!$deptResult['valid']) {
                $errors[] = $deptResult['error'];
            } else {
                $parsedData['department_id'] = $deptResult['id'];
                $this->parsedRowData['department_id'] = $deptResult['id'];
            }
        }

        if ($this->newImport['service_id'] ?? false) {
            // Use service selected in form
            $parsedData['service_id'] = $this->newImport['service_id'];
        } elseif (isset($rowData['service'])) {
            $serviceResult = $this->validateEntityReference('service', $rowData['service']);
            if (!$serviceResult['valid']) {
                $errors[] = $serviceResult['error'];
            } else {
                $parsedData['service_id'] = $serviceResult['id'];
            }
        }

        // Validate optional fields
        if (isset($rowData['personal_phone_number']) && !empty(trim($rowData['personal_phone_number']))) {
            $phone = trim($rowData['personal_phone_number']);
            if (!$this->isValidPhoneNumber($phone)) {
                $errors[] = __('common.invalid_personal_phone_format');
            } else {
                $parsedData['personal_phone_number'] = $this->formatPhoneNumber($phone);
            }
        }

        if (isset($rowData['alternative_email']) && !empty(trim($rowData['alternative_email']))) {
            if (!filter_var(trim($rowData['alternative_email']), FILTER_VALIDATE_EMAIL)) {
                $errors[] = __('common.invalid_alternative_email_format');
            }
        }

        // Validate dates
        if (isset($rowData['date_of_birth']) && !empty($rowData['date_of_birth'])) {
            if (!$this->isValidDate($rowData['date_of_birth'])) {
                $errors[] = __('common.invalid_date_of_birth');
            } else {
                $parsedData['date_of_birth'] = $this->parseDate($rowData['date_of_birth']);
            }
        }

        if (isset($rowData['contract_end']) && !empty($rowData['contract_end'])) {
            if (!$this->isValidDate($rowData['contract_end'])) {
                $errors[] = __('common.invalid_contract_end_date');
            } else {
                $parsedDate = $this->parseDate($rowData['contract_end']);
                $parsedData['contract_end'] = $parsedDate;

                if (strtotime($parsedDate) < time()) {
                    $warnings[] = __('common.contract_end_date_in_past');
                }
            }
        }

        return $parsedData;
    }

    /**
     * Validate company row data
     */
    protected function validateCompanyRow(array $rowData, array &$errors, array &$warnings): array
    {
        $parsedData = $rowData;

        // Validate required fields
        if (!isset($rowData['name']) || trim($rowData['name']) === '') {
            $errors[] = __('common.missing_required_field', ['field' => __('common.name')]);
        }

        // Validate company name
        if (isset($rowData['name'])) {
            $name = trim($rowData['name']);
            if (strlen($name) < 2) {
                $errors[] = __('common.company_name_too_short');
            } elseif (strlen($name) > 255) {
                $errors[] = __('common.company_name_too_long');
            } elseif (\App\Models\Company::where('name', $name)->exists()) {
                $warnings[] = __('common.company_already_exists');
            }
        }

        // Validate email if provided
        if (isset($rowData['email']) && !empty(trim($rowData['email']))) {
            $email = trim($rowData['email']);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = __('common.invalid_email_format');
            } elseif (\App\Models\Company::where('email', $email)->exists()) {
                $warnings[] = __('common.company_email_already_exists');
            }
        }

        // Validate phone if provided
        if (isset($rowData['phone']) && !empty(trim($rowData['phone']))) {
            $phone = trim($rowData['phone']);
            if (!$this->isValidPhoneNumber($phone)) {
                $errors[] = __('common.invalid_phone_format');
            } else {
                $parsedData['phone'] = $this->formatPhoneNumber($phone);
            }
        }

        // Validate address if provided
        if (isset($rowData['address']) && strlen(trim($rowData['address'])) > 500) {
            $errors[] = __('common.address_too_long');
        }

        return $parsedData;
    }

    /**
     * Validate department row data
     */
    protected function validateDepartmentRow(array $rowData, array &$errors, array &$warnings): array
    {
        $parsedData = $rowData;

        // Validate department name
        if (isset($rowData['name'])) {
            $name = trim($rowData['name']);
            if (strlen($name) < 2) {
                $errors[] = __('common.department_name_too_short');
            }
        }

        // Use company selected in form, or validate from file data if not provided
        if ($this->newImport['company_id'] ?? false) {
            // Use company selected in form
            $parsedData['company_id'] = $this->newImport['company_id'];

            // Check if department already exists for this company
            if (isset($rowData['name']) && \App\Models\Department::where('name', trim($rowData['name']))
                ->where('company_id', $this->newImport['company_id'])->exists()) {
                $warnings[] = __('common.department_already_exists_for_company');
            }
        } elseif (isset($rowData['company'])) {
            $companyValue = trim($rowData['company']);
            $company = null;

            // Try to find by ID first
            if (is_numeric($companyValue)) {
                $company = \App\Models\Company::find($companyValue);
            }

            // Try to find by name
            if (!$company) {
                $company = \App\Models\Company::where('name', $companyValue)->first();
            }

            if (!$company) {
                $errors[] = __('common.company_not_found');
            } else {
                $parsedData['company_id'] = $company->id;

                // Check if department already exists for this company
                if (isset($rowData['name']) && \App\Models\Department::where('name', trim($rowData['name']))
                    ->where('company_id', $company->id)->exists()) {
                    $warnings[] = __('common.department_already_exists_for_company');
                }
            }
        }

        return $parsedData;
    }

    /**
     * Validate service row data
     */
    protected function validateServiceRow(array $rowData, array &$errors, array &$warnings): array
    {
        $parsedData = $rowData;

        // Validate service name
        if (isset($rowData['name'])) {
            $name = trim($rowData['name']);
            if (strlen($name) < 2) {
                $errors[] = __('common.service_name_too_short');
            }
        }

        // Use department selected in form, or validate from file data if not provided
        if ($this->newImport['department_id'] ?? false) {
            // Use department selected in form
            $parsedData['department_id'] = $this->newImport['department_id'];

            // Check if service already exists for this department
            if (isset($rowData['name']) && \App\Models\Service::where('name', trim($rowData['name']))
                ->where('department_id', $this->newImport['department_id'])->exists()) {
                $warnings[] = __('common.service_already_exists_for_department');
            }
        } elseif (isset($rowData['department'])) {
            $deptValue = trim($rowData['department']);
            $department = null;

            // Try to find by ID first
            if (is_numeric($deptValue)) {
                $department = \App\Models\Department::find($deptValue);
            }

            // Try to find by name - but we need company context
            if (!$department && ($this->newImport['company_id'] ?? false)) {
                $department = \App\Models\Department::where('name', $deptValue)
                    ->where('company_id', $this->newImport['company_id'])
                    ->first();
            }

            if (!$department) {
                $errors[] = __('common.department_not_found');
            } else {
                $parsedData['department_id'] = $department->id;

                // Check if service already exists for this department
                if (isset($rowData['name']) && \App\Models\Service::where('name', trim($rowData['name']))
                    ->where('department_id', $department->id)->exists()) {
                    $warnings[] = __('common.service_already_exists_for_department');
                }
            }
        }

        return $parsedData;
    }

    /**
     * Validate leave type row data
     */
    protected function validateLeaveTypeRow(array $rowData, array &$errors, array &$warnings): array
    {
        $parsedData = $rowData;

        // Validate leave type name
        if (isset($rowData['name'])) {
            $name = trim($rowData['name']);
            if (strlen($name) < 2) {
                $errors[] = __('common.leave_type_name_too_short');
            } elseif (\App\Models\LeaveType::where('name', $name)->exists()) {
                $warnings[] = __('common.leave_type_already_exists');
            }
        }

        // Validate days
        if (isset($rowData['days'])) {
            $days = $rowData['days'];
            if (!is_numeric($days) || $days < 0 || $days > 365) {
                $errors[] = __('common.invalid_leave_days');
            }
        }

        return $parsedData;
    }

    /**
     * Validate phone number format
     */
    protected function isValidPhoneNumber(string $phone): bool
    {
        // Remove all non-digit characters
        $cleaned = preg_replace('/\D/', '', $phone);

        // Check if it's a valid length (international format)
        return strlen($cleaned) >= 7 && strlen($cleaned) <= 15;
    }

    /**
     * Format phone number
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Basic formatting - keep it simple for preview
        $cleaned = preg_replace('/\D/', '', $phone);

        // Add international prefix if missing
        if (!str_starts_with($cleaned, '+')) {
            $cleaned = '+' . $cleaned;
        }

        return $cleaned;
    }

    /**
     * Validate entity reference (department, service, etc.)
     */
    protected function validateEntityReference(string $entityType, string $value): array
    {
        if (empty(trim($value))) {
            return ['valid' => false, 'id' => null, 'error' => __('common.' . $entityType . '_is_required')];
        }

        $value = trim($value);

        // For departments and services, we need company context
        $companyId = $this->newImport['company_id'] ?? null;

        if (!$companyId) {
            return ['valid' => false, 'id' => null, 'error' => __('common.company_context_required')];
        }

        switch ($entityType) {
            case 'department':
                // Try to find by ID first
                if (is_numeric($value)) {
                    $entity = \App\Models\Department::where('id', $value)
                        ->where('company_id', $companyId)
                        ->first();
                    if ($entity) {
                        return ['valid' => true, 'id' => $entity->id, 'error' => null];
                    }
                }

                // Try to find by name
                $entity = \App\Models\Department::where('name', $value)
                    ->where('company_id', $companyId)
                    ->first();

                if ($entity) {
                    return ['valid' => true, 'id' => $entity->id, 'error' => null];
                }

                return ['valid' => false, 'id' => null, 'error' => __('common.department_not_found')];

            case 'service':
                // Get department ID from parsed data if available
                $departmentId = null;
                if (isset($this->parsedRowData['department_id'])) {
                    $departmentId = $this->parsedRowData['department_id'];
                }

                if (!$departmentId) {
                    return ['valid' => false, 'id' => null, 'error' => __('common.department_required_for_service')];
                }

                // Try to find by ID first
                if (is_numeric($value)) {
                    $entity = \App\Models\Service::where('id', $value)
                        ->where('department_id', $departmentId)
                        ->first();
                    if ($entity) {
                        return ['valid' => true, 'id' => $entity->id, 'error' => null];
                    }
                }

                // Try to find by name
                $entity = \App\Models\Service::where('name', $value)
                    ->where('department_id', $departmentId)
                    ->first();

                if ($entity) {
                    return ['valid' => true, 'id' => $entity->id, 'error' => null];
                }

                return ['valid' => false, 'id' => null, 'error' => __('common.service_not_found')];

            default:
                return ['valid' => false, 'id' => null, 'error' => __('common.invalid_entity_type')];
        }
    }

    /**
     * Validate date format
     */
    protected function isValidDate(string $date): bool
    {
        if (empty($date)) {
            return false;
        }

        // Try multiple date formats
        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y'];

        foreach ($formats as $format) {
            $dateTime = \DateTime::createFromFormat($format, $date);
            if ($dateTime && $dateTime->format($format) === $date) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse and format date
     */
    protected function parseDate(string $date): ?string
    {
        if (empty($date)) {
            return null;
        }

        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y'];

        foreach ($formats as $format) {
            $dateTime = \DateTime::createFromFormat($format, $date);
            if ($dateTime && $dateTime->format($format) === $date) {
                return $dateTime->format('Y-m-d');
            }
        }

        return null;
    }

    /**
     * Perform the actual import (required by WithImportPreview)
     */
    protected function performImport()
    {
        // This is handled by the ImportService and ImportDataJob
        // We don't perform synchronous import in the centralized system
        throw new \Exception('Import should be handled asynchronously via ImportService');
    }

    /**
     * Get file property for validation (required by WithImportPreview)
     */
    protected function getFileProperty()
    {
        // Prioritize the uploaded file object for processing
        $file = $this->getSafeFileObject();
        if ($file) {
            return $file;
        }

        // Fallback to stored file path if available
        if ($this->filePath && \Storage::exists($this->filePath)) {
            return \Storage::path($this->filePath);
        }

        return null;
    }

    /**
     * Safely get the file object, handling both file objects and arrays
     */
    protected function getSafeFileObject()
    {
        $fileInput = $this->newImport['file'] ?? null;

        if (!$fileInput) {
            return null;
        }

        // If it's already a file object, return it
        if (is_object($fileInput) && method_exists($fileInput, 'getClientOriginalName')) {
            return $fileInput;
        }

        // If it's an array (Livewire file upload), extract the file object
        if (is_array($fileInput)) {
            // Livewire sometimes stores files as arrays with temporary paths
            // Try to get the first file from the array
            $firstFile = reset($fileInput);
            if (is_object($firstFile) && method_exists($firstFile, 'getClientOriginalName')) {
                return $firstFile;
            }

            // If it's an array with path info, try to create a file object
            if (isset($fileInput['path']) && file_exists($fileInput['path'])) {
                return new \Illuminate\Http\UploadedFile(
                    $fileInput['path'],
                    $fileInput['name'] ?? 'uploaded_file',
                    $fileInput['type'] ?? 'application/octet-stream',
                    $fileInput['size'] ?? null,
                    $fileInput['error'] ?? UPLOAD_ERR_OK
                );
            }
        }

        return null;
    }

    /**
     * Get file property name (required by WithImportPreview)
     */
    protected function getFilePropertyName(): string
    {
        return 'newImport.file';
    }

    /**
     * Get expected columns for validation (required by WithImportPreview)
     */
    protected function getExpectedColumns(): array
    {
        if (!$this->currentImportType) {
            return [];
        }

        // Define expected columns based on import type
        switch ($this->currentImportType) {
            case ImportJob::TYPE_EMPLOYEES:
                return [
                    'first_name', 'last_name', 'email', 'professional_phone_number',
                    'matricule', 'position', 'net_salary', 'salary_grade',
                    'contract_end', 'department', 'service', 'role', 'status', 'password',
                    'remaining_leave_days', 'monthly_leave_allocation', 'receive_sms_notifications',
                    'personal_phone_number', 'work_start_time', 'work_end_time', 'receive_email_notifications',
                    'alternative_email', 'date_of_birth'
                ];
            case ImportJob::TYPE_COMPANIES:
                return ['code', 'name', 'description', 'sector'];
            case ImportJob::TYPE_DEPARTMENTS:
                return ['name', 'supervisor_email', 'company'];
            case ImportJob::TYPE_SERVICES:
                return ['name', 'department'];
            case ImportJob::TYPE_LEAVE_TYPES:
                return ['name', 'description', 'default_number_of_days', 'is_active'];
            default:
                return ['name'];
        }
    }

    protected function getRequiredColumns(): array
    {
        if (!$this->currentImportType) {
            return [];
        }

        // Define required columns based on import type (excludes optional columns)
        switch ($this->currentImportType) {
            case ImportJob::TYPE_EMPLOYEES:
                return [
                    'first_name', 'last_name', 'email', 'professional_phone_number',
                    'matricule', 'position', 'net_salary', 'salary_grade',
                    'department', 'service', 'role'
                ];
            case ImportJob::TYPE_COMPANIES:
                return ['name'];
            case ImportJob::TYPE_DEPARTMENTS:
                return ['name', 'company']; // supervisor_email is optional
            case ImportJob::TYPE_SERVICES:
                return ['name', 'department'];
            case ImportJob::TYPE_LEAVE_TYPES:
                return ['name'];
            default:
                return ['name'];
        }
    }

    /**
     * Get column mapping for display (required by WithImportPreview)
     */
    protected function getColumnMapping(): array
    {
        if (!$this->currentImportType) {
            return [];
        }

        switch ($this->currentImportType) {
            case ImportJob::TYPE_EMPLOYEES:
                return [
                    'first_name' => __('common.first_name'),
                    'last_name' => __('common.last_name'),
                    'email' => __('common.email'),
                    'professional_phone_number' => __('common.professional_phone'),
                    'matricule' => __('common.matricule'),
                    'position' => __('common.position'),
                    'net_salary' => __('common.net_salary'),
                    'salary_grade' => __('common.salary_grade'),
                    'contract_end' => __('common.contract_end'),
                    'department' => __('common.department'),
                    'service' => __('common.service'),
                    'role' => __('common.role'),
                    'status' => __('common.status'),
                    'password' => __('common.password'),
                    'remaining_leave_days' => __('common.remaining_leave_days'),
                    'monthly_leave_allocation' => __('common.monthly_leave_allocation'),
                    'receive_sms_notifications' => __('common.receive_sms_notifications'),
                    'personal_phone_number' => __('common.personal_phone_number'),
                    'work_start_time' => __('common.work_start_time'),
                    'work_end_time' => __('common.work_end_time'),
                    'receive_email_notifications' => __('common.receive_email_notifications'),
                    'alternative_email' => __('common.alternative_email'),
                    'date_of_birth' => __('common.date_of_birth'),
                ];
            case ImportJob::TYPE_COMPANIES:
                return [
                    'code' => __('common.code'),
                    'name' => __('common.name'),
                    'description' => __('common.description'),
                    'sector' => __('companies.sector'),
                ];
            case ImportJob::TYPE_DEPARTMENTS:
                return [
                    'name' => __('common.name'),
                    'supervisor_email' => __('common.supervisor_email'),
                    'company' => __('companies.company'),
                ];
            case ImportJob::TYPE_SERVICES:
                return [
                    'name' => __('common.name'),
                    'department' => __('common.department'),
                ];
            case ImportJob::TYPE_LEAVE_TYPES:
                return [
                    'name' => __('common.name'),
                    'description' => __('common.description'),
                    'default_number_of_days' => __('leaves.default_number_of_days'),
                    'is_active' => __('common.status'),
                ];
            default:
                return ['name' => __('common.name')];
        }
    }

    /**
     * Get preview columns for display
     */
    public function getPreviewColumns(): array
    {
        return $this->getColumnMapping();
    }

    /**
     * Perform quick file analysis to determine if preview should be skipped
     */
    protected function quickFileAnalysis(): array
    {
        $file = $this->getSafeFileObject();

        try {
            // Set a shorter timeout for this operation
            $originalTimeout = ini_get('max_execution_time');
            set_time_limit(10); // 10 seconds for quick analysis

            // Determine file size and decide on counting strategy
            $fileSizeMB = is_object($file) ? $file->getSize() / (1024 * 1024) : \Storage::size($file) / (1024 * 1024);

            if ($fileSizeMB > 50) {
                // For very large files, estimate based on file size and provide warning
                return [
                    'total_rows' => 10000, // Estimated
                    'is_large' => true,
                    'has_data' => true,
                    'estimated' => true,
                    'warning' => __('common.large_file_detected', ['size' => round($fileSizeMB, 1)]),
                    'skip_preview' => true
                ];
            }

            // For small files, read everything to get accurate count
            if ($fileSizeMB < 1) { // Less than 1MB
                $collection = \Maatwebsite\Excel\Facades\Excel::toCollection(new class implements \Maatwebsite\Excel\Concerns\ToCollection {
                    public function collection(\Illuminate\Support\Collection $rows) {
                        return $rows;
                    }
                }, $file)->first();

                $totalRows = $collection ? $collection->count() : 0;

                // For small files, allow preview even with more rows
                // Only skip preview for very large datasets
                if ($totalRows > 100) { // Only skip if more than 100 rows
                    return [
                        'total_rows' => $totalRows,
                        'is_large' => false,
                        'has_data' => $totalRows > 1,
                        'estimated' => false,
                        'skip_preview' => true,
                        'skip_reason' => __('common.large_record_count_for_preview', ['count' => $totalRows - 1])
                    ];
                }
            } else {
                // For medium files, sample first 100 rows to estimate size
                $sampleCheck = \Maatwebsite\Excel\Facades\Excel::toCollection(new class implements \Maatwebsite\Excel\Concerns\ToCollection, \Maatwebsite\Excel\Concerns\WithLimit {
                    public function limit(): int {
                        return 100; // Sample first 100 rows
                    }

                    public function collection(\Illuminate\Support\Collection $rows) {
                        return $rows;
                    }
                }, $file)->first();

                if ($sampleCheck && $sampleCheck->count() >= 100) {
                    // File likely has many more rows, estimate based on file size
                    $estimatedRows = $this->estimateRowCount($fileSizeMB);
                    return [
                        'total_rows' => $estimatedRows,
                        'is_large' => true,
                        'has_data' => true,
                        'estimated' => true,
                        'skip_preview' => true,
                        'skip_reason' => __('common.large_file_preview_skipped')
                    ];
                } elseif ($sampleCheck && $sampleCheck->count() > 12) {
                    // Medium file with 12-99 rows, skip preview for performance
                    $totalRows = $sampleCheck->count();
                    return [
                        'total_rows' => $totalRows,
                        'is_large' => false,
                        'has_data' => $totalRows > 1,
                        'estimated' => false,
                        'skip_preview' => true,
                        'skip_reason' => __('common.large_record_count_for_preview', ['count' => $totalRows - 1])
                    ];
                }
            }

            // Restore original timeout
            set_time_limit($originalTimeout);

            if (!$collection ?? null) {
                throw new \Exception(__('common.no_data_found_in_file'));
            }

            $totalRows = $collection->count();
            $isLarge = $totalRows > 1000; // Consider files with >1000 rows as large

            return [
                'total_rows' => $totalRows,
                'is_large' => $isLarge,
                'has_data' => $totalRows > 1, // At least header + 1 data row
                'estimated' => false,
                'skip_preview' => false
            ];
        } catch (\Exception $e) {
            // Restore original timeout even on error
            if (isset($originalTimeout)) {
                set_time_limit($originalTimeout);
            }

            throw new \Exception(__('common.file_structure_error', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Estimate row count based on file size (rough approximation)
     */
    protected function estimateRowCount(float $fileSizeMB): int
    {
        // Rough estimates based on typical Excel/CSV file sizes
        // CSV files: ~50-100 bytes per row
        // Excel files: ~200-500 bytes per row (compressed)

        $fileObject = $this->getSafeFileObject();
        $extension = $fileObject ? strtolower($fileObject->getClientOriginalExtension()) : 'csv';

        if ($extension === 'csv' || $extension === 'txt') {
            // CSV files are smaller per row
            $bytesPerRow = 75;
        } else {
            // Excel files (xlsx/xls) are larger
            $bytesPerRow = 300;
        }

        $estimatedRows = (int) (($fileSizeMB * 1024 * 1024) / $bytesPerRow);

        // Cap at reasonable maximum and ensure minimum
        return max(100, min($estimatedRows, 50000));
    }

    /**
     * Override goToPreview to prevent navigation back when preview was skipped for large files
     */
    public function goToPreview(): void
    {
        // If preview was skipped for large files, don't allow going back to preview
        if ($this->previewSkipped && $this->currentStep === 'confirm') {
            return; // Stay on confirmation step
        }

        // Otherwise, use the parent implementation
        parent::goToPreview();
    }

    /**
     * Override analyzeFileStructure to use our custom logic
     */
    protected function analyzeFileStructure(): array
    {
        $file = $this->getFileProperty();

        try {
            // Set a shorter timeout for this operation
            $originalTimeout = ini_get('max_execution_time');
            set_time_limit(15); // 15 seconds for file analysis

            // Use a more efficient approach for large files
            $fileSizeMB = is_object($file) ? $file->getSize() / (1024 * 1024) : \Storage::size($file) / (1024 * 1024);

            if ($fileSizeMB > 50) {
                // For very large files, estimate based on file size and provide warning
                return [
                    'total_rows' => 10000, // Estimated
                    'is_large' => true,
                    'has_data' => true,
                    'estimated' => true,
                    'warning' => __('common.large_file_detected', ['size' => round($fileSizeMB, 1)]),
                    'skip_preview' => true
                ];
            }

            // Determine file size and decide on counting strategy
            $fileSizeMB = is_object($file) ? $file->getSize() / (1024 * 1024) : \Storage::size($file) / (1024 * 1024);

            // For small files, read everything to get accurate count
            if ($fileSizeMB < 1) { // Less than 1MB
                $collection = \Maatwebsite\Excel\Facades\Excel::toCollection(new class implements \Maatwebsite\Excel\Concerns\ToCollection {
                    public function collection(\Illuminate\Support\Collection $rows) {
                        return $rows;
                    }
                }, $file)->first();

                $totalRows = $collection ? $collection->count() : 0;

                // For small files, allow preview even with more rows
                // Only skip preview for very large datasets
                if ($totalRows > 100) { // Only skip if more than 100 rows
                    return [
                        'total_rows' => $totalRows,
                        'is_large' => false,
                        'has_data' => $totalRows > 1,
                        'estimated' => false,
                        'skip_preview' => true,
                        'skip_reason' => __('common.large_record_count_for_preview', ['count' => $totalRows - 1])
                    ];
                }
            } else {
                // For medium files, sample first 100 rows to estimate size
                $sampleCheck = \Maatwebsite\Excel\Facades\Excel::toCollection(new class implements \Maatwebsite\Excel\Concerns\ToCollection, \Maatwebsite\Excel\Concerns\WithLimit {
                    public function limit(): int {
                        return 100; // Sample first 100 rows
                    }

                    public function collection(\Illuminate\Support\Collection $rows) {
                        return $rows;
                    }
                }, $file)->first();

                if ($sampleCheck && $sampleCheck->count() >= 100) {
                    // File likely has many more rows, estimate based on file size
                    $estimatedRows = $this->estimateRowCount($fileSizeMB);
                    return [
                        'total_rows' => $estimatedRows,
                        'is_large' => true,
                        'has_data' => true,
                        'estimated' => true,
                        'skip_preview' => true,
                        'skip_reason' => __('common.large_file_preview_skipped')
                    ];
                } elseif ($sampleCheck && $sampleCheck->count() > 12) {
                    // Medium file with 12-99 rows, allow preview
                    $totalRows = $sampleCheck->count();

                    // Allow preview for medium files
                    // Continue with normal processing
                } else {
                    // Small file, read fully
                    $collection = \Maatwebsite\Excel\Facades\Excel::toCollection(new class implements \Maatwebsite\Excel\Concerns\ToCollection {
                        public function collection(\Illuminate\Support\Collection $rows) {
                            return $rows;
                        }
                    }, $file)->first();
                }
            }

            // Restore original timeout
            set_time_limit($originalTimeout);

            if (!$collection ?? null) {
                throw new \Exception(__('common.no_data_found_in_file'));
            }

            $totalRows = $collection->count();
            $isLarge = $totalRows > 1000; // Consider files with >1000 rows as large

            return [
                'total_rows' => $totalRows,
                'is_large' => $isLarge,
                'has_data' => $totalRows > 1, // At least header + 1 data row
                'estimated' => false,
                'skip_preview' => false
            ];
        } catch (\Exception $e) {
            // Restore original timeout even on error
            if (isset($originalTimeout)) {
                set_time_limit($originalTimeout);
            }

            throw new \Exception(__('common.file_structure_error', ['error' => $e->getMessage()]));
        }
    }
}
