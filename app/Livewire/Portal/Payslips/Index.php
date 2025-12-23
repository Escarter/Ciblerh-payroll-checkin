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
use App\Jobs\Single\ResendFailedPayslipJob;

class Index extends Component
{
    use WithDataTable;
    
    public $companies = [];
    public $departments = [];
    public $company_id, $department_id, $month, $payslip_file;
    public $job_id = null;

    public ?SendPayslipProcess $send_payslip_process;

    // Soft delete properties
    public $activeTab = 'active';
    public $selectedSendPayslipProcesses = [];
    public $selectAll = false;

    // Task details modal properties
    public ?SendPayslipProcess $selectedProcess = null;

    // Individual payslip details modal properties
    public $selectedPayslip = null;

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
        
        // Validate supervisor access
        if ($this->role === 'supervisor' && $process) {
            $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
            if (!in_array($process->department_id, $validDepartmentIds)) {
                abort(403, __('common.unauthorized_department_access'));
            }
        }
        
        if (!empty($process)) {
            auditLog(
                auth()->user(),
                'delete_payslip_process',
                'web',
                'payslip_process_deleted',
                $process, // Pass model for enhanced tracking
                $process->getAttributes(), // Old values before deletion
                [], // No new values for deletes
                ['process_id' => $process->id, 'month' => $process->month, 'year' => $process->year] // Metadata
            );
            $process->payslips()->delete(); // Soft delete related payslips
            $process->delete(); // Soft delete the process
        }
        $this->reset(['send_payslip_process']);
        $this->closeModalAndFlashMessage(__('payslips.payslip_process_moved_to_trash'), 'DeleteModal');
    }

    public function restore($processId)
    {
        if (!Gate::allows('payslip-restore')) {
            return abort(401);
        }

        $process = SendPayslipProcess::withTrashed()->findOrFail($processId);
        
        // Validate supervisor access
        if ($this->role === 'supervisor') {
            $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
            if (!in_array($process->department_id, $validDepartmentIds)) {
                abort(403, __('common.unauthorized_department_access'));
            }
        }
        $process->restore();

        $this->closeModalAndFlashMessage(__('payslips.payslip_process_restored'), 'RestoreModal');
    }

    public function forceDelete($processId)
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        $process = SendPayslipProcess::withTrashed()->findOrFail($processId);
        
        // Validate supervisor access
        if ($this->role === 'supervisor') {
            $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
            if (!in_array($process->department_id, $validDepartmentIds)) {
                abort(403, __('common.unauthorized_department_access'));
            }
        }
        $process->payslips()->forceDelete(); // Force delete related payslips
        $process->forceDelete(); // Force delete the process

        $this->closeModalAndFlashMessage(__('payslips.payslip_process_permanently_deleted'), 'ForceDeleteModal');
    }

    public function bulkDelete()
    {
        if (!Gate::allows('payslip-bulkdelete')) {
            return abort(401);
        }

        if (!empty($this->selectedSendPayslipProcesses)) {
            $query = SendPayslipProcess::whereIn('id', $this->selectedSendPayslipProcesses);
            
            // Validate supervisor access - only allow deleting processes from their departments
            if ($this->role === 'supervisor') {
                $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
                $query->whereIn('department_id', $validDepartmentIds);
            }
            
            $processes = $query->get();
            $affectedRecords = $processes->map(function ($process) {
                return [
                    'id' => $process->id,
                    'month' => $process->month,
                    'year' => $process->year,
                    'department_id' => $process->department_id,
                    'status' => $process->status,
                ];
            })->toArray();

            foreach ($processes as $process) {
                $process->payslips()->delete(); // Soft delete related payslips
            }
            $query->delete(); // Soft delete the processes

            if ($processes->count() > 0) {
                auditLog(
                    auth()->user(),
                    'payslip_process_bulk_deleted',
                    'web',
                    'bulk_deleted_payslip_processes',
                    null,
                    [],
                    [],
                    [
                        'translation_key' => 'bulk_deleted_payslip_processes',
                        'translation_params' => ['count' => $processes->count()],
                        'bulk_operation' => true,
                        'operation_type' => 'soft_delete',
                        'affected_count' => $processes->count(),
                        'affected_ids' => $processes->pluck('id')->toArray(),
                        'affected_records' => $affectedRecords,
                    ]
                );
            }

            $this->selectedSendPayslipProcesses = [];
        }

        $this->closeModalAndFlashMessage(__('payslips.selected_payslip_processes_moved_to_trash'), 'BulkDeleteModal');
    }

    public function bulkRestore()
    {
        if (!Gate::allows('payslip-bulkrestore')) {
            return abort(401);
        }

        if (!empty($this->selectedSendPayslipProcesses)) {
            $query = SendPayslipProcess::withTrashed()->whereIn('id', $this->selectedSendPayslipProcesses);
            
            // Validate supervisor access - only allow restoring processes from their departments
            if ($this->role === 'supervisor') {
                $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
                $query->whereIn('department_id', $validDepartmentIds);
            }
            
            $processes = $query->get();
            $affectedRecords = $processes->map(function ($process) {
                return [
                    'id' => $process->id,
                    'month' => $process->month,
                    'year' => $process->year,
                    'department_id' => $process->department_id,
                    'status' => $process->status,
                ];
            })->toArray();

            $query->restore();

            if ($processes->count() > 0) {
                auditLog(
                    auth()->user(),
                    'payslip_process_bulk_restored',
                    'web',
                    'bulk_restored_payslip_processes',
                    null,
                    [],
                    [],
                    [
                        'translation_key' => 'bulk_restored_payslip_processes',
                        'translation_params' => ['count' => $processes->count()],
                        'bulk_operation' => true,
                        'operation_type' => 'bulk_restore',
                        'affected_count' => $processes->count(),
                        'affected_ids' => $processes->pluck('id')->toArray(),
                        'affected_records' => $affectedRecords,
                    ]
                );
            }

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
            $query = SendPayslipProcess::withTrashed()->whereIn('id', $this->selectedSendPayslipProcesses);
            
            // Validate supervisor access - only allow force deleting processes from their departments
            if ($this->role === 'supervisor') {
                $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
                $query->whereIn('department_id', $validDepartmentIds);
            }
            
            $processes = $query->get();
            $affectedRecords = $processes->map(function ($process) {
                return [
                    'id' => $process->id,
                    'month' => $process->month,
                    'year' => $process->year,
                    'department_id' => $process->department_id,
                    'status' => $process->status,
                ];
            })->toArray();

            foreach ($processes as $process) {
                $process->payslips()->forceDelete(); // Force delete related payslips
            }
            $query->forceDelete(); // Force delete the processes

            if ($processes->count() > 0) {
                auditLog(
                    auth()->user(),
                    'payslip_process_bulk_force_deleted',
                    'web',
                    'bulk_force_deleted_payslip_processes',
                    null,
                    [],
                    [],
                    [
                        'translation_key' => 'bulk_force_deleted_payslip_processes',
                        'translation_params' => ['count' => $processes->count()],
                        'bulk_operation' => true,
                        'operation_type' => 'bulk_force_delete',
                        'affected_count' => $processes->count(),
                        'affected_ids' => $processes->pluck('id')->toArray(),
                        'affected_records' => $affectedRecords,
                    ]
                );
            }

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

        return $query->orderBy('created_at', 'desc')->take(20)->get();
    }

    public function showTaskDetails($processId)
    {
        $process = SendPayslipProcess::with(['department', 'owner', 'payslips'])->findOrFail($processId);
        
        // Validate supervisor access
        if ($this->role === 'supervisor') {
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

    public function showPayslipDetails($payslipId)
    {
        $payslip = \App\Models\Payslip::with(['sendPayslipProcess.department', 'sendPayslipProcess.owner'])->findOrFail($payslipId);
        
        // Validate supervisor access
        if ($this->role === 'supervisor') {
            $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
            if (!in_array($payslip->department_id, $validDepartmentIds)) {
                abort(403, __('common.unauthorized_department_access'));
            }
        }
        
        $this->selectedPayslip = $payslip;
    }

    public function closePayslipDetailsModal()
    {
        $this->selectedPayslip = null;
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

    public function resendPayslip($payslipId)
    {
        $payslip = \App\Models\Payslip::findOrFail($payslipId);
        
        // Validate supervisor access
        if ($this->role === 'supervisor') {
            $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
            if (!in_array($payslip->department_id, $validDepartmentIds)) {
                abort(403, __('common.unauthorized_department_access'));
            }
        }

        // Reset statuses to allow reprocessing
        $payslip->update([
            'encryption_status' => 0,
            'email_sent_status' => 0,
            'sms_sent_status' => 0,
            'encryption_status_note' => null,
            'email_status_note' => null,
            'sms_status_note' => null,
        ]);

        // Trigger reprocessing
        \App\Jobs\Single\ResendFailedPayslipJob::dispatch($payslip);

        session()->flash('message', __('payslips.payslip_queued_for_resend'));
        $this->closePayslipDetailsModal();
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

    // Cached statistics to prevent repeated calculations
    private $cachedTaskStatistics = null;
    private $cachedAdvancedStatistics = null;
    private $cachedPerformanceMetrics = null;

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
        if (!isset($this->cachedAdvancedStatistics) || $this->cachedAdvancedStatistics === null) {
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

    public function refreshTaskData()
    {
        // Refresh the selected process data if modal is open
        if ($this->selectedProcess) {
            $process = SendPayslipProcess::with(['department', 'owner', 'payslips'])->find($this->selectedProcess->id);
            
            // Validate supervisor access
            if ($this->role === 'supervisor' && $process) {
                $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
                if (!in_array($process->department_id, $validDepartmentIds)) {
                    $this->selectedProcess = null;
                    return;
                }
            }
            
            $this->selectedProcess = $process;

            // Clear cached statistics to force recalculation
            $this->cachedTaskStatistics = null;
            $this->cachedAdvancedStatistics = null;
            $this->cachedPerformanceMetrics = null;
        }
    }

    public function cancelTask($processId)
    {
        if (!Gate::allows('payslip-delete')) {
            return abort(401);
        }

        $process = SendPayslipProcess::findOrFail($processId);
        
        // Validate supervisor access
        if ($this->role === 'supervisor') {
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
                'cancel_payslip_process_for',
                null,
                [],
                [],
                [
                    'translation_key' => 'cancel_payslip_process_for',
                    'translation_params' => [
                        'month' => $process->month,
                        'year' => $process->year,
                        'time' => now()->format('Y-m-d H:i:s')
                    ],
                ]
            );

            session()->flash('message', __('payslips.task_cancelled_successfully'));
            $this->closeTaskDetailsModal();
            return $this->redirect(route('portal.payslips.index'), navigate: true);
        }
    }

    public function bulkResendEmails($processId)
    {
        if (!Gate::allows('payslip-bulkresend-email')) {
            return abort(401);
        }

        $process = SendPayslipProcess::findOrFail($processId);
        
        // Validate supervisor access
        if ($this->role === 'supervisor') {
            $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
            if (!in_array($process->department_id, $validDepartmentIds)) {
                abort(403, __('common.unauthorized_department_access'));
            }
        }

        // Get all payslips that failed email sending but succeeded in encryption
        $failedEmailPayslips = $process->payslips()
            ->where('email_sent_status', 2) // Failed
            ->where('encryption_status', 1) // Encryption successful
            ->whereNotNull('file')
            ->get();

        if ($failedEmailPayslips->isEmpty()) {
            session()->flash('error', __('payslips.no_failed_emails_to_resend'));
            return;
        }

        $resendCount = 0;
        $errorCount = 0;

        foreach ($failedEmailPayslips as $payslip) {
            try {
                $employee = \App\Models\User::find($payslip->employee_id);

                if (empty($employee) || empty($employee->email)) {
                    $errorCount++;
                    continue;
                }

                if (!Storage::disk('modified')->exists($payslip->file)) {
                    $errorCount++;
                    continue;
                }

                setSavedSmtpCredentials();

                Mail::to(cleanString($employee->email))->send(new \App\Mail\SendPayslip($employee, $payslip->file, $payslip->month));

                // Reset email status to allow reprocessing
                $payslip->update([
                    'email_sent_status' => 0,
                    'email_delivery_status' => null,
                    'email_sent_at' => null,
                    'email_retry_count' => 0,
                    'last_email_retry_at' => null,
                    'failure_reason' => null,
                ]);

                $resendCount++;
            } catch (\Exception $e) {
                Log::error('Bulk email resend failed for payslip', [
                    'payslip_id' => $payslip->id,
                    'error' => $e->getMessage()
                ]);
                $errorCount++;
            }
        }

        // Trigger reprocessing of the updated payslips
        foreach ($failedEmailPayslips->where('email_sent_status', 0) as $payslip) {
            \App\Jobs\Single\ResendFailedPayslipJob::dispatch($payslip);
        }

        $message = __('payslips.bulk_email_resend_completed', [
            'resend' => $resendCount,
            'errors' => $errorCount
        ]);

        session()->flash('message', $message);
        $this->refreshTaskData();
    }

    public function bulkResendSms($processId)
    {
        if (!Gate::allows('payslip-bulkresend-sms')) {
            return abort(401);
        }

        $process = SendPayslipProcess::findOrFail($processId);

        // Get all payslips that failed SMS sending but succeeded in encryption
        $failedSmsPayslips = $process->payslips()
            ->where('sms_sent_status', 2) // Failed
            ->where('encryption_status', 1) // Encryption successful
            ->whereNotNull('file')
            ->get();

        if ($failedSmsPayslips->isEmpty()) {
            session()->flash('error', __('payslips.no_failed_sms_to_resend'));
            return;
        }

        $resendCount = 0;
        $errorCount = 0;

        foreach ($failedSmsPayslips as $payslip) {
            try {
                $employee = \App\Models\User::find($payslip->employee_id);

                if (empty($employee) || empty($employee->phone)) {
                    $errorCount++;
                    continue;
                }

                if (!Storage::disk('modified')->exists($payslip->file)) {
                    $errorCount++;
                    continue;
                }

                // Check SMS balance
                $sms_balance = null;
                $setting = \App\Models\Setting::first();
                if (!empty($setting->sms_provider)) {
                    $sms_client = match ($setting->sms_provider) {
                        'twilio' => new TwilioSMS($setting),
                        'nexah' => new Nexah($setting),
                        'aws_sns' => new AwsSnsSMS($setting),
                        default => new Nexah($setting)
                    };

                    try {
                        $sms_balance = $sms_client->getBalance();
                    } catch (\Exception $e) {
                        Log::warning('Failed to check SMS balance in bulk SMS resend: ' . $e->getMessage());
                    }
                }

                sendSmsAndUpdateRecord($employee, $payslip->month, $payslip, $sms_balance);

                $resendCount++;
            } catch (\Exception $e) {
                Log::error('Bulk SMS resend failed for payslip', [
                    'payslip_id' => $payslip->id,
                    'error' => $e->getMessage()
                ]);
                $errorCount++;
            }
        }

        $message = __('payslips.bulk_sms_resend_completed', [
            'resend' => $resendCount,
            'errors' => $errorCount
        ]);

        session()->flash('message', $message);
        $this->refreshTaskData();
    }

    public function mount()
    {
        // Initialize cache variables
        $this->cachedTaskStatistics = null;
        $this->cachedAdvancedStatistics = null;
        $this->cachedPerformanceMetrics = null;

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
          

            if (empty($setting->smtp_host) && empty($setting->smtp_port)) {
                session()->flash('error', __('payslips.smtp_setting_required'));
                return;
            }

        }else{
            session()->flash('error', __('payslips.sms_smtp_settings_required'));
            return;
        }



        $raw_file_path = $this->payslip_file->store(auth()->user()->id, 'raw');

        $choosen_department = Department::findOrFail($this->department_id);
        
        // Validate supervisor can only send to their managed departments
        if ($this->role === 'supervisor') {
            $validDepartmentIds = auth()->user()->supDepartments->pluck('department_id')->toArray();
            if (!in_array($this->department_id, $validDepartmentIds)) {
                session()->flash('error', __('common.unauthorized_department_access'));
                return $this->redirect(route('portal.payslips.index'), navigate: true);
            }
        }

        $raw_file = Storage::disk('raw')->path($raw_file_path);

        $splitted_disk = Storage::disk('splitted');
        $modified_disk = Storage::disk('modified');

        $destination_directory = Str::random(20);


        if (countPages(Storage::disk('raw')->path($raw_file_path)) > config('ciblerh.max_payslip_pages')) {
            session()->flash('error', __('payslips.file_upload_max_pages', ['max' => config('ciblerh.max_payslip_pages')]));
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
            // Only restart if the process failed or was cancelled, not if it's successful or already processing
            if (in_array($existing->status, ['failed', 'cancelled'])) {
                $existing->update(['status' => 'processing', 'batch_id' => '']);
                $payslip_process = $existing;
            } else {
                // Process is already successful or processing - don't restart
                session()->flash('error', __('payslips.process_already_running_or_completed'));
                return $this->redirect(route('portal.payslips.index'), navigate: true);
            }
        }

        PayslipSendingPlan::start($payslip_process);

        auditLog(
            auth()->user(),
            'payslip_sending',
            'web',
            'payslip_sending_initiated',
            null,
            [],
            [],
            [
                'translation_key' => 'payslip_sending_initiated',
                'translation_params' => [
                    'user' => '<a href="/portal/users?user_id=' . auth()->user()->id . '">' . auth()->user()->name . '</a>',
                    'department' => '<strong>' . $choosen_department->name . '</strong>',
                    'month' => $this->month,
                    'year' => now()->year,
                    'history_link' => '<a href="/portal/payslips/history"> Go to Payslips details</a>'
                ],
            ]
        );

        session()->flash('message', __('payslips.job_processing_status'));
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
            'supervisor' => SendPayslipProcess::supervisor()->whereNull('deleted_at')->count(),
            'admin' => SendPayslipProcess::whereNull('deleted_at')->count(),
            default => 0,
        };

        $deleted_processes = match (auth()->user()->getRoleNames()->first()) {
            'manager' => SendPayslipProcess::manager()->withTrashed()->whereNotNull('deleted_at')->count(),
            'supervisor' => SendPayslipProcess::supervisor()->withTrashed()->whereNotNull('deleted_at')->count(),
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