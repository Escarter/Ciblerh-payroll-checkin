<?php

namespace App\Livewire\Portal\Payslips;

use Livewire\Component;
use App\Models\SendPayslipProcess;
use App\Models\Payslip;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use App\Livewire\Traits\WithDataTable;

class All extends Component
{
    use WithDataTable;

   public ?SendPayslipProcess $send_payslip_process;
   public ?SendPayslipProcess $selectedProcess = null;
   public ?int $job_id = null;

    // Cached statistics to prevent repeated calculations
    private $cachedTaskStatistics = null;
    private $cachedAdvancedStatistics = null;
    private $cachedPerformanceMetrics = null;

    public function mount()
    {
        // Initialize cache variables
        $this->cachedTaskStatistics = null;
        $this->cachedAdvancedStatistics = null;
        $this->cachedPerformanceMetrics = null;
    }

   // Soft delete properties
   public $activeTab = 'active';
   public $selectedJobs = [];
   public $selectAll = false;

    public function initData($job_id) {
        $process = SendPayslipProcess::findOrFail($job_id);
        
        // Validate supervisor access
        $role = auth()->user()->getRoleNames()->first();
        if ($role === 'supervisor') {
            $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
            if (!in_array($process->department_id, $validDepartmentIds)) {
                abort(403, __('common.unauthorized_department_access'));
            }
        }
        
        $this->send_payslip_process = $process;
    }

    public function downloadPayslip($payslip_id)
    {
        $payslip = Payslip::findOrFail($payslip_id);
        
        // Validate supervisor access
        $role = auth()->user()->getRoleNames()->first();
        if ($role === 'supervisor') {
            $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
            if (!in_array($payslip->department_id, $validDepartmentIds)) {
                abort(403, __('common.unauthorized_department_access'));
            }
        }
        
        // Check if the file exists
        if (!Storage::disk('modified')->exists($payslip->file)) {
            $this->showToast(__('payslips.payslip_file_not_found'), 'danger');
            return;
        }
        
        try {
            return response()->download(
                Storage::disk('modified')->path($payslip->file), 
                $payslip->matricule. "_" . $payslip->year.'_'.$payslip->month.'.pdf', 
                ['Content-Type'=> 'application/pdf']
            );
        } catch (\Exception $e) {
            $this->showToast(__('payslips.unable_to_download_payslip'), 'danger');
        }
    }

     public function delete($jobId = null)
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        $job = $jobId ? SendPayslipProcess::findOrFail($jobId) : $this->send_payslip_process;
        
        // Validate supervisor access
        if ($job) {
            $role = auth()->user()->getRoleNames()->first();
            if ($role === 'supervisor') {
                $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
                if (!in_array($job->department_id, $validDepartmentIds)) {
                    abort(403, __('common.unauthorized_department_access'));
                }
            }
        }

        if(!empty($job))
        {
            auditLog(
                auth()->user(),
                'delete_payslip_process',
                'web',
                __('payslips.delete_payslip_process_for', [
                    'month' => $job->month,
                    'year' => $job->year,
                    'datetime' => now()->format('Y-m-d H:i:s')
                ]),
                $job, // Pass model for enhanced tracking
                $job->getAttributes(), // Old values before deletion
                [], // No new values for deletes
                ['process_id' => $job->id, 'month' => $job->month, 'year' => $job->year] // Metadata
            );

            $job->delete(); // Soft delete
        }
        $this->reset(['send_payslip_process']);
        $this->closeModalAndFlashMessage(__('payslips.payslip_process_moved_to_trash'), 'DeleteModal');
    }

    public function restore()
    {
        if (!Gate::allows('payslip-restore')) {
            return abort(401);
        }

        $job = SendPayslipProcess::withTrashed()->findOrFail($this->job_id);
        
        // Validate supervisor access
        $role = auth()->user()->getRoleNames()->first();
        if ($role === 'supervisor') {
            $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
            if (!in_array($job->department_id, $validDepartmentIds)) {
                abort(403, __('common.unauthorized_department_access'));
            }
        }
        
        $job->restore();

        $this->closeModalAndFlashMessage(__('payslips.payslip_process_restored'), 'RestoreModal');
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
            __('payslips.permanently_delete_payslip_process_for', [
                'month' => $job->month,
                'year' => $job->year,
                'datetime' => now()->format('Y-m-d H:i:s')
            ]),
            $job, // Pass model for enhanced tracking
            $job->getAttributes(), // Old values before deletion
            [], // No new values for force deletes
            ['process_id' => $job->id, 'month' => $job->month, 'year' => $job->year] // Metadata
        );

        $job->payslips()->forceDelete(); // Permanently delete related payslips
        $job->forceDelete(); // Permanently delete the process

        $this->closeModalAndFlashMessage(__('payslips.payslip_process_permanently_deleted'), 'ForceDeleteModal');
    }

    public function bulkDelete()
    {
        if (!Gate::allows('payslip-bulkdelete')) {
            return abort(401);
        }

        $query = SendPayslipProcess::whereIn('id', $this->selectedJobs);
        
        // Validate supervisor access - only allow deleting processes from their departments
        $role = auth()->user()->getRoleNames()->first();
        if ($role === 'supervisor') {
            $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
            $query->whereIn('department_id', $validDepartmentIds);
        }
        
        $jobs = $query->get();
        $affectedRecords = $jobs->map(function ($job) {
            return [
                'id' => $job->id,
                'month' => $job->month,
                'year' => $job->year,
                'department_id' => $job->department_id,
                'status' => $job->status,
            ];
        })->toArray();
        
        foreach ($jobs as $job) {
            $job->delete(); // Soft delete
        }

        if ($jobs->count() > 0) {
            auditLog(
                auth()->user(),
                'payslip_process_bulk_deleted',
                'web',
                __('audit_logs.bulk_deleted_payslip_processes', ['count' => $jobs->count()]),
                null,
                [],
                [],
                [
                    'bulk_operation' => true,
                    'operation_type' => 'soft_delete',
                    'affected_count' => $jobs->count(),
                    'affected_ids' => $jobs->pluck('id')->toArray(),
                    'affected_records' => $affectedRecords,
                ]
            );
        }

        $this->selectedJobs = [];
        $this->selectAll = false;
        $this->closeModalAndFlashMessage(__('Selected payslip processes successfully moved to trash!'), 'BulkDeleteModal');
    }

    public function bulkRestore()
    {
        if (!Gate::allows('payslip-bulkrestore')) {
            return abort(401);
        }

        $query = SendPayslipProcess::withTrashed()->whereIn('id', $this->selectedJobs);
        
        // Validate supervisor access - only allow restoring processes from their departments
        $role = auth()->user()->getRoleNames()->first();
        if ($role === 'supervisor') {
            $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
            $query->whereIn('department_id', $validDepartmentIds);
        }
        
        $jobs = $query->get();
        $affectedRecords = $jobs->map(function ($job) {
            return [
                'id' => $job->id,
                'month' => $job->month,
                'year' => $job->year,
                'department_id' => $job->department_id,
                'status' => $job->status,
            ];
        })->toArray();
        
        foreach ($jobs as $job) {
            $job->restore();
        }

        if ($jobs->count() > 0) {
            auditLog(
                auth()->user(),
                'payslip_process_bulk_restored',
                'web',
                __('audit_logs.bulk_restored_payslip_processes', ['count' => $jobs->count()]),
                null,
                [],
                [],
                [
                    'bulk_operation' => true,
                    'operation_type' => 'bulk_restore',
                    'affected_count' => $jobs->count(),
                    'affected_ids' => $jobs->pluck('id')->toArray(),
                    'affected_records' => $affectedRecords,
                ]
            );
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

        $query = SendPayslipProcess::withTrashed()->whereIn('id', $this->selectedJobs);
        
        // Validate supervisor access - only allow force deleting processes from their departments
        $role = auth()->user()->getRoleNames()->first();
        if ($role === 'supervisor') {
            $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
            $query->whereIn('department_id', $validDepartmentIds);
        }
        
        $jobs = $query->get();
        $affectedRecords = $jobs->map(function ($job) {
            return [
                'id' => $job->id,
                'month' => $job->month,
                'year' => $job->year,
                'department_id' => $job->department_id,
                'status' => $job->status,
            ];
        })->toArray();
        
        foreach ($jobs as $job) {
            $job->payslips()->forceDelete(); // Permanently delete related payslips
            $job->forceDelete(); // Permanently delete the process
        }

        if ($jobs->count() > 0) {
            auditLog(
                auth()->user(),
                'payslip_process_bulk_force_deleted',
                'web',
                __('audit_logs.bulk_force_deleted_payslip_processes', ['count' => $jobs->count()]),
                null,
                [],
                [],
                [
                    'bulk_operation' => true,
                    'operation_type' => 'bulk_force_delete',
                    'affected_count' => $jobs->count(),
                    'affected_ids' => $jobs->pluck('id')->toArray(),
                    'affected_records' => $affectedRecords,
                ]
            );
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
            'supervisor' => SendPayslipProcess::supervisor(),
            'admin' => SendPayslipProcess::query(),
            default => SendPayslipProcess::query(),
        };

        // Add soft delete filtering based on active tab
        if ($this->activeTab === 'deleted') {
            $query->withTrashed()->whereNotNull('deleted_at');
        } else {
            $query->whereNull('deleted_at');
        }

        return $query->with('department')->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);
    }

    private function getAllJobs()
    {
        $query = match (auth()->user()->getRoleNames()->first()) {
            'manager' => SendPayslipProcess::manager(),
            'supervisor' => SendPayslipProcess::supervisor(),
            'admin' => SendPayslipProcess::query(),
            default => SendPayslipProcess::query(),
        };

        // Add soft delete filtering based on active tab
        if ($this->activeTab === 'deleted') {
            $query->withTrashed()->whereNotNull('deleted_at');
        } else {
            $query->whereNull('deleted_at');
        }

        return $query->with('department')->orderBy($this->orderBy, $this->orderAsc)->get();
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
            'supervisor' => SendPayslipProcess::supervisor()->whereNull('deleted_at')->count(),
            'admin' => SendPayslipProcess::whereNull('deleted_at')->count(),
            default => 0,
        };

        // Get counts for deleted jobs
        $deleted_jobs = match (auth()->user()->getRoleNames()->first()) {
            'manager' => SendPayslipProcess::manager()->withTrashed()->whereNotNull('deleted_at')->count(),
            'supervisor' => SendPayslipProcess::supervisor()->withTrashed()->whereNotNull('deleted_at')->count(),
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

    // Modal methods for task details
    public function showTaskDetails($processId)
    {
        $process = SendPayslipProcess::with(['department', 'owner', 'payslips'])->findOrFail($processId);
        
        // Validate supervisor access
        $role = auth()->user()->getRoleNames()->first();
        if ($role === 'supervisor') {
            $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
            if (!in_array($process->department_id, $validDepartmentIds)) {
                abort(403, __('common.unauthorized_department_access'));
            }
        }
        
        $this->selectedProcess = $process;

        // Clear cached statistics when loading a new process
        $this->cachedTaskStatistics = null;
        $this->cachedAdvancedStatistics = null;
        $this->cachedPerformanceMetrics = null;
    }

    public function closeTaskDetailsModal()
    {
        $this->selectedProcess = null;

        // Clear cached statistics when closing modal
        $this->cachedTaskStatistics = null;
        $this->cachedAdvancedStatistics = null;
        $this->cachedPerformanceMetrics = null;
    }

    public function refreshTaskData()
    {
        // Refresh the selected process data if modal is open
        if ($this->selectedProcess) {
            $process = SendPayslipProcess::with(['department', 'owner', 'payslips'])->find($this->selectedProcess->id);
            
            // Validate supervisor access
            if ($process) {
                $role = auth()->user()->getRoleNames()->first();
                if ($role === 'supervisor') {
                    $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
                    if (!in_array($process->department_id, $validDepartmentIds)) {
                        $this->selectedProcess = null;
                        return;
                    }
                }
            }
            
            $this->selectedProcess = $process;

            // Clear cached statistics to force recalculation
            $this->cachedTaskStatistics = null;
            $this->cachedAdvancedStatistics = null;
            $this->cachedPerformanceMetrics = null;
        }
    }

    public function getTaskProgressPercentage()
    {
        if (!$this->selectedProcess) {
            return 0;
        }

        $totalPayslips = $this->selectedProcess->payslips()->count();
        if ($totalPayslips === 0) {
            return 0;
        }

        // Consider a payslip as completed if it's successfully encrypted and sent via email or SMS
        $completedPayslips = $this->selectedProcess->payslips()
            ->where('encryption_status', 1)
            ->where(function ($query) {
                $query->where('email_sent_status', 1)
                      ->orWhere('sms_sent_status', 1);
            })
            ->count();

        return ($completedPayslips / $totalPayslips) * 100;
    }

     public function getTaskStatistics()
    {
        if (!$this->selectedProcess) {
            return [
                'total' => 0,
                'successful' => 0,
                'pending' => 0,
                'failed' => 0,
                'processing' => 0,
                'successful_percentage' => 0,
                'pending_percentage' => 0,
                'failed_percentage' => 0,
                'processing_percentage' => 0,
            ];
        }

        // Use cached statistics if available, otherwise calculate and cache
        if (!isset($this->cachedTaskStatistics) || $this->cachedTaskStatistics === null) {
            $this->cachedTaskStatistics = $this->calculateTaskStatistics();
        }

        return $this->cachedTaskStatistics;
    }

    private function calculateTaskStatistics()
    {
        // Eager load payslips to prevent N+1 queries
        $payslips = $this->selectedProcess->payslips()
            ->select(['id', 'encryption_status', 'email_sent_status', 'sms_sent_status'])
            ->get();

        $total = $payslips->count();

        if ($total === 0) {
            return [
                'total' => 0,
                'successful' => 0,
                'pending' => 0,
                'failed' => 0,
                'processing' => 0,
                'successful_percentage' => 0,
                'pending_percentage' => 0,
                'failed_percentage' => 0,
                'processing_percentage' => 0,
            ];
        }

        $successful = 0;
        $failed = 0;
        $processing = 0;
        $pending = 0;

        foreach ($payslips as $payslip) {
            $status = $this->getPayslipOverallStatus($payslip);

            switch ($status) {
                case 'success':
                    $successful++;
                    break;
                case 'failed':
                    $failed++;
                    break;
                case 'processing':
                    $processing++;
                    break;
                default:
                    $pending++;
                    break;
            }
        }

        return [
            'total' => $total,
            'successful' => $successful,
            'pending' => $pending,
            'failed' => $failed,
            'processing' => $processing,
            'successful_percentage' => $total > 0 ? ($successful / $total) * 100 : 0,
            'pending_percentage' => $total > 0 ? ($pending / $total) * 100 : 0,
            'failed_percentage' => $total > 0 ? ($failed / $total) * 100 : 0,
            'processing_percentage' => $total > 0 ? ($processing / $total) * 100 : 0,
        ];
    }

    public function getAdvancedTaskStatistics()
    {
        if (!$this->selectedProcess) {
            return [
                'estimated_completion_time' => 'N/A',
                'average_processing_time' => 'N/A',
                'first_error_timestamp' => 'N/A',
                'last_success_timestamp' => 'N/A',
            ];
        }

        // Use cached advanced statistics if available
        if (!isset($this->cachedAdvancedStatistics)) {
            $this->cachedAdvancedStatistics = $this->calculateAdvancedTaskStatistics();
        }

        return $this->cachedAdvancedStatistics;
    }

    private function calculateAdvancedTaskStatistics()
    {
        $payslips = $this->selectedProcess->payslips()
            ->select(['id', 'created_at', 'updated_at', 'encryption_status', 'email_sent_status', 'sms_sent_status'])
            ->get();

        $result = [
            'estimated_completion_time' => 'N/A',
            'average_processing_time' => 'N/A',
            'first_error_timestamp' => 'N/A',
            'last_success_timestamp' => 'N/A',
        ];

        if ($payslips->isEmpty()) {
            return $result;
        }

        // Calculate processing metrics
        $completedPayslips = $payslips->filter(function ($payslip) {
            return $this->getPayslipOverallStatus($payslip) === 'success';
        });

        $failedPayslips = $payslips->filter(function ($payslip) {
            return $this->getPayslipOverallStatus($payslip) === 'failed';
        });

        $processingPayslips = $payslips->filter(function ($payslip) {
            return $this->getPayslipOverallStatus($payslip) === 'processing';
        });

        // Average processing time for completed payslips
        if ($completedPayslips->count() > 0) {
            $totalProcessingTime = 0;
            foreach ($completedPayslips as $payslip) {
                $processingTime = $payslip->created_at->diffInSeconds($payslip->updated_at);
                $totalProcessingTime += $processingTime;
            }
            $avgProcessingTime = $totalProcessingTime / $completedPayslips->count();

            if ($avgProcessingTime < 60) {
                $result['average_processing_time'] = round($avgProcessingTime) . 's';
            } elseif ($avgProcessingTime < 3600) {
                $result['average_processing_time'] = round($avgProcessingTime / 60, 1) . 'm';
            } else {
                $result['average_processing_time'] = round($avgProcessingTime / 3600, 1) . 'h';
            }
        }

        // First error timestamp
        if ($failedPayslips->count() > 0) {
            $firstError = $failedPayslips->sortBy('updated_at')->first();
            $result['first_error_timestamp'] = $firstError->updated_at->format('M d, H:i');
        }

        // Last success timestamp
        if ($completedPayslips->count() > 0) {
            $lastSuccess = $completedPayslips->sortByDesc('updated_at')->first();
            $result['last_success_timestamp'] = $lastSuccess->updated_at->format('M d, H:i');
        }

        // Estimated completion time for remaining payslips
        $remainingPayslips = $processingPayslips->count() + $payslips->filter(function ($payslip) {
            return $this->getPayslipOverallStatus($payslip) === 'pending';
        })->count();

        if ($completedPayslips->count() > 0 && $remainingPayslips > 0) {
            $avgProcessingTime = $this->calculateAverageProcessingTime($completedPayslips);
            $estimatedSeconds = $avgProcessingTime * $remainingPayslips;
            $estimatedCompletion = now()->addSeconds($estimatedSeconds);

            if ($estimatedSeconds < 60) {
                $result['estimated_completion_time'] = round($estimatedSeconds) . 's';
            } elseif ($estimatedSeconds < 3600) {
                $result['estimated_completion_time'] = round($estimatedSeconds / 60) . 'm';
            } elseif ($estimatedSeconds < 86400) {
                $result['estimated_completion_time'] = round($estimatedSeconds / 3600) . 'h';
            } else {
                $result['estimated_completion_time'] = round($estimatedSeconds / 86400) . 'd';
            }
        }

        return $result;
    }

    public function getPerformanceMetrics()
    {
        if (!$this->selectedProcess) {
            return [
                'throughput_rate' => 'N/A',
                'success_rate' => '0%',
                'processing_efficiency' => '0%',
                'error_rate' => '0%',
                'avg_items_per_minute' => '0',
            ];
        }

        // Use cached performance metrics if available
        if (!isset($this->cachedPerformanceMetrics) || $this->cachedPerformanceMetrics === null) {
            $this->cachedPerformanceMetrics = $this->calculatePerformanceMetrics();
        }

        return $this->cachedPerformanceMetrics;
    }

    private function calculatePerformanceMetrics()
    {
        $payslips = $this->selectedProcess->payslips()
            ->select(['id', 'created_at', 'updated_at', 'encryption_status', 'email_sent_status', 'sms_sent_status'])
            ->get();

        $stats = $this->getTaskStatistics();
        $total = $stats['total'] ?? 0;
        $successful = $stats['successful'] ?? 0;
        $failed = $stats['failed'] ?? 0;
        $processing = $stats['processing'] ?? 0;

        $result = [
            'throughput_rate' => 'N/A',
            'success_rate' => $total > 0 ? number_format(($successful / $total) * 100, 1) . '%' : '0%',
            'processing_efficiency' => $total > 0 ? number_format((($successful + $processing) / $total) * 100, 1) . '%' : '0%',
            'error_rate' => $total > 0 ? number_format(($failed / $total) * 100, 1) . '%' : '0%',
            'avg_items_per_minute' => '0',
        ];

        // Calculate throughput rate (items processed per minute)
        if ($payslips->count() > 0) {
            $taskDuration = $this->selectedProcess->created_at->diffInMinutes(now());
            if ($taskDuration > 0) {
                $processedItems = $successful + $failed; // Items that have been processed (success or fail)
                $result['throughput_rate'] = number_format($processedItems / $taskDuration, 1) . ' ' . __('payslips.per_minute');
                $result['avg_items_per_minute'] = number_format($processedItems / $taskDuration, 1);
            }
        }

        return $result;
    }

    private function calculateAverageProcessingTime($completedPayslips)
    {
        if ($completedPayslips->count() === 0) {
            return 0;
        }

        $totalProcessingTime = 0;
        foreach ($completedPayslips as $payslip) {
            $processingTime = $payslip->created_at->diffInSeconds($payslip->updated_at);
            $totalProcessingTime += $processingTime;
        }

        return $totalProcessingTime / $completedPayslips->count();
    }

    public function getTaskTimeline()
    {
        if (!$this->selectedProcess) {
            return [];
        }

        $timeline = [];

        // Process creation
        $timeline[] = [
            'title' => __('payslips.task_created'),
            'description' => __('payslips.payslip_task_initiated', ['department' => $this->selectedProcess->department?->name ?? __('common.unknown')]),
            'time' => $this->selectedProcess->created_at->diffForHumans(),
            'type' => 'info'
        ];

        // If process is completed
        if ($this->selectedProcess->status === 'successful') {
            $timeline[] = [
                'title' => __('payslips.task_completed'),
                'description' => __('payslips.all_payslips_processed_successfully'),
                'time' => $this->selectedProcess->updated_at->diffForHumans(),
                'type' => 'success'
            ];
        } elseif ($this->selectedProcess->status === 'failed') {
            $timeline[] = [
                'title' => __('payslips.task_failed'),
                'description' => __('payslips.payslip_processing_encountered_errors'),
                'time' => $this->selectedProcess->updated_at->diffForHumans(),
                'type' => 'error'
            ];
        } elseif ($this->selectedProcess->status === 'cancelled') {
            $timeline[] = [
                'title' => __('common.cancelled'),
                'description' => __('payslips.task_cancelled_by_user'),
                'time' => $this->selectedProcess->updated_at->diffForHumans(),
                'type' => 'warning'
            ];
        }

        // Sort by time (most recent first)
        usort($timeline, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });

        return $timeline;
    }

    public function getRecentActivity()
    {
        if (!$this->selectedProcess) {
            return [];
        }

        $activities = [];

        // Get recent payslip activities
        $recentPayslips = $this->selectedProcess->payslips()
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        foreach ($recentPayslips as $payslip) {
            $activity = [
                'time' => $payslip->updated_at->diffForHumans(),
            ];

            // Determine activity type and description
            if ($payslip->encryption_status == 1) {
                if ($payslip->email_sent_status == 1 || $payslip->sms_sent_status == 1) {
                    $activity['type'] = 'success';
                    $activity['title'] = __('payslips.payslip_sent');
                    $activity['description'] = __('payslips.payslip_successfully_sent_to', ['name' => $payslip->name]);
                } elseif ($payslip->email_sent_status == 2 && $payslip->sms_sent_status == 2) {
                    $activity['type'] = 'error';
                    $activity['title'] = __('payslips.payslip_failed');
                    $activity['description'] = __('payslips.failed_to_send_payslip_to', ['name' => $payslip->name]);
                } else {
                    $activity['type'] = 'info';
                    $activity['title'] = __('payslips.payslip_encrypted');
                    $activity['description'] = __('payslips.payslip_encrypted_for', ['name' => $payslip->name]);
                }
            } elseif ($payslip->encryption_status == 2) {
                $activity['type'] = 'error';
                $activity['title'] = __('payslips.encryption_failed');
                $activity['description'] = __('payslips.failed_to_encrypt_payslip_for', ['name' => $payslip->name]);
            } else {
                $activity['type'] = 'info';
                $activity['title'] = __('payslips.payslip_processing');
                $activity['description'] = __('payslips.processing_payslip_for', ['name' => $payslip->name]);
            }

            $activities[] = $activity;
        }

        return $activities;
    }

    public function getFailedPayslips()
    {
        if (!$this->selectedProcess) {
            return collect();
        }

        return $this->selectedProcess->payslips()
            ->where(function ($query) {
                $query->where('encryption_status', 2) // Encryption failed
                      ->orWhere('email_sent_status', 2) // Email failed
                      ->orWhere('sms_sent_status', 2); // SMS failed
            })
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    public function getPayslipOverallStatus($payslip)
    {
        // If encryption failed, overall status is failed
        if ($payslip->encryption_status == 2) {
            return 'failed';
        }

        // If encryption is successful and either email or SMS is sent, it's success
        if ($payslip->encryption_status == 1 && ($payslip->email_sent_status == 1 || $payslip->sms_sent_status == 1)) {
            return 'success';
        }

        // If encryption is successful but both email and SMS failed, it's failed
        if ($payslip->encryption_status == 1 && $payslip->email_sent_status == 2 && $payslip->sms_sent_status == 2) {
            return 'failed';
        }

        // If encryption is successful but email and SMS are disabled, it's success
        if ($payslip->encryption_status == 1 && $payslip->email_sent_status == 3 && $payslip->sms_sent_status == 3) {
            return 'success';
        }

        // Otherwise, it's still processing
        return 'processing';
    }

    public function cancelTask($processId)
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        $process = SendPayslipProcess::findOrFail($processId);
        
        // Validate supervisor access
        $role = auth()->user()->getRoleNames()->first();
        if ($role === 'supervisor') {
            $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
            if (!in_array($process->department_id, $validDepartmentIds)) {
                abort(403, __('common.unauthorized_department_access'));
            }
        }

        if ($process->status === 'processing') {
            $process->update(['status' => 'cancelled']);

            auditLog(
                auth()->user(),
                'cancel_payslip_process',
                'web',
                __('audit_logs.cancel_payslip_process_for', [
                    'month' => $process->month,
                    'year' => $process->year,
                    'time' => now()->format('Y-m-d H:i:s')
                ])
            );

            session()->flash('message', __('payslips.task_cancelled_successfully'));
            $this->closeTaskDetailsModal();
            return $this->redirect(route('portal.payslips.index'), navigate: true);
        }
    }
}
