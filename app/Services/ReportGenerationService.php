<?php

namespace App\Services;

use App\Models\DownloadJob;
use App\Models\User;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class ReportGenerationService
{
    /**
     * Create a new download job and dispatch it to the queue
     */
    public static function createJob(string $jobType, array $filters, array $config = []): DownloadJob
    {
        $user = Auth::user();
        
        if (!$user) {
            throw new \Exception('User must be authenticated to create download jobs.');
        }
        
        // Add additional metadata to filters
        $enhancedFilters = self::enhanceFilters($filters, $user);
        
        $job = DownloadJob::create([
            'uuid' => Str::uuid(),
            'user_id' => $user->id,
            'job_type' => $jobType,
            'report_format' => $config['format'] ?? 'xlsx',
            'filters' => $enhancedFilters,
            'report_config' => $config,
            'status' => DownloadJob::STATUS_PENDING,
            'expires_at' => now()->addDays(7), // Auto-cleanup after 7 days
            'metadata' => [
                'created_by' => $user->name,
                'created_by_email' => $user->email,
                'user_role' => $user->getRoleNames()->first(),
            ]
        ]);

        // Dispatch appropriate job to queue
        self::dispatchJob($job);

        return $job;
    }

    /**
     * Get all available job types with their display names
     */
    public static function getAvailableJobTypes(): array
    {
        return [
            DownloadJob::TYPE_PAYSLIP_REPORT => __('reports.payslip_report'),
            DownloadJob::TYPE_OVERTIME_REPORT => __('reports.overtime_report'),
            DownloadJob::TYPE_CHECKLOG_REPORT => __('reports.checklog_report'),
            DownloadJob::TYPE_EMPLOYEE_EXPORT => __('reports.employee_export'),
            DownloadJob::TYPE_SERVICE_EXPORT => __('reports.service_export'),
            DownloadJob::TYPE_COMPANY_EXPORT => __('reports.company_export'),
            DownloadJob::TYPE_DEPARTMENT_EXPORT => __('reports.department_export'),
            DownloadJob::TYPE_ADVANCE_SALARY_EXPORT => __('reports.advance_salary_export'),
            DownloadJob::TYPE_ABSENCES_EXPORT => __('reports.absences_export'),
            DownloadJob::TYPE_BULK_PAYSLIP_DOWNLOAD => __('reports.bulk_payslip_download'),
        ];
    }

    /**
     * Get available report formats
     */
    public static function getAvailableFormats(): array
    {
        return [
            DownloadJob::FORMAT_XLSX => __('download_jobs.excel_xlsx'),
            DownloadJob::FORMAT_PDF => __('download_jobs.pdf'),
            DownloadJob::FORMAT_ZIP => __('download_jobs.zip_archive'),
        ];
    }

    /**
     * Validate job type and format combination
     */
    public static function validateJobConfiguration(string $jobType, string $format): bool
    {
        $validCombinations = [
            DownloadJob::TYPE_BULK_PAYSLIP_DOWNLOAD => [DownloadJob::FORMAT_ZIP, DownloadJob::FORMAT_PDF],
            DownloadJob::TYPE_PAYSLIP_REPORT => [DownloadJob::FORMAT_XLSX],
            DownloadJob::TYPE_OVERTIME_REPORT => [DownloadJob::FORMAT_XLSX],
            DownloadJob::TYPE_CHECKLOG_REPORT => [DownloadJob::FORMAT_XLSX],
            DownloadJob::TYPE_EMPLOYEE_EXPORT => [DownloadJob::FORMAT_XLSX],
            DownloadJob::TYPE_SERVICE_EXPORT => [DownloadJob::FORMAT_XLSX],
            DownloadJob::TYPE_COMPANY_EXPORT => [DownloadJob::FORMAT_XLSX],
            DownloadJob::TYPE_DEPARTMENT_EXPORT => [DownloadJob::FORMAT_XLSX],
            DownloadJob::TYPE_ADVANCE_SALARY_EXPORT => [DownloadJob::FORMAT_XLSX],
            DownloadJob::TYPE_ABSENCES_EXPORT => [DownloadJob::FORMAT_XLSX],
        ];

        return isset($validCombinations[$jobType]) && 
               in_array($format, $validCombinations[$jobType]);
    }

    /**
     * Get job statistics for a user
     */
    public static function getUserJobStats(int $userId): array
    {
        $jobs = DownloadJob::forUser($userId);

        return [
            'total' => $jobs->count(),
            'pending' => $jobs->byStatus(DownloadJob::STATUS_PENDING)->count(),
            'processing' => $jobs->byStatus(DownloadJob::STATUS_PROCESSING)->count(),
            'completed' => $jobs->byStatus(DownloadJob::STATUS_COMPLETED)->count(),
            'failed' => $jobs->byStatus(DownloadJob::STATUS_FAILED)->count(),
            'cancelled' => $jobs->byStatus(DownloadJob::STATUS_CANCELLED)->count(),
        ];
    }

    /**
     * Clean up expired jobs
     */
    public static function cleanupExpiredJobs(): int
    {
        $expiredJobs = DownloadJob::expired()->get();
        $deletedCount = 0;

        foreach ($expiredJobs as $job) {
            // Delete associated files
            if ($job->file_path && \Storage::exists($job->file_path)) {
                \Storage::delete($job->file_path);
            }
            
            $job->delete();
            $deletedCount++;
        }

        return $deletedCount;
    }

    /**
     * Enhance filters with additional metadata
     */
    private static function enhanceFilters(array $filters, User $user): array
    {
        $enhanced = $filters;

        // Add company name if company_id is present
        if (isset($filters['selectedCompanyId']) && $filters['selectedCompanyId'] !== 'all') {
            $company = Company::find($filters['selectedCompanyId']);
            if ($company) {
                $enhanced['company_name'] = $company->name;
            }
        }

        // Add department name if department_id is present
        if (isset($filters['selectedDepartmentId']) && $filters['selectedDepartmentId'] !== 'all') {
            $department = Department::find($filters['selectedDepartmentId']);
            if ($department) {
                $enhanced['department_name'] = $department->name;
            }
        }

        // Add employee name if employee_id is present
        // Handle both single ID (string/int) and array of IDs
        if (isset($filters['employee_id'])) {
            if (is_array($filters['employee_id']) && !empty($filters['employee_id'])) {
                // Multiple employees selected
                $employeeIds = array_filter($filters['employee_id']);
                if (!empty($employeeIds)) {
                    $employees = User::whereIn('id', $employeeIds)->get();
                    if ($employees->isNotEmpty()) {
                        // Use map() instead of pluck() to access the name accessor
                        $enhanced['employee_names'] = $employees->map(function($employee) {
                            return $employee->name;
                        })->toArray();
                        $enhanced['employee_count'] = $employees->count();
                    }
                }
            } elseif (!is_array($filters['employee_id']) && 
                     $filters['employee_id'] !== 'all' && 
                     $filters['employee_id'] !== null && 
                     $filters['employee_id'] !== '') {
                // Single employee (backward compatibility) - ensure it's not an array
                $employeeId = is_numeric($filters['employee_id']) ? (int)$filters['employee_id'] : null;
                if ($employeeId) {
                    $employee = User::find($employeeId);
                    if ($employee) {
                        $enhanced['employee_name'] = $employee->name;
                        $enhanced['employee_email'] = $employee->email;
                    }
                }
            }
        }

        return $enhanced;
    }

    /**
     * Dispatch the appropriate job to the queue
     */
    public static function dispatchJob(DownloadJob $job): void
    {
        $jobClass = match($job->job_type) {
            DownloadJob::TYPE_BULK_PAYSLIP_DOWNLOAD => \App\Jobs\DownloadJobs\BulkPayslipDownloadJob::class,
            DownloadJob::TYPE_PAYSLIP_REPORT => \App\Jobs\DownloadJobs\PayslipReportJob::class,
            DownloadJob::TYPE_OVERTIME_REPORT => \App\Jobs\DownloadJobs\OvertimeReportJob::class,
            DownloadJob::TYPE_CHECKLOG_REPORT => \App\Jobs\DownloadJobs\ChecklogReportJob::class,
            DownloadJob::TYPE_EMPLOYEE_EXPORT => \App\Jobs\DownloadJobs\EmployeeExportJob::class,
            DownloadJob::TYPE_SERVICE_EXPORT => \App\Jobs\DownloadJobs\ServiceExportJob::class,
            DownloadJob::TYPE_COMPANY_EXPORT => \App\Jobs\DownloadJobs\CompanyExportJob::class,
            DownloadJob::TYPE_DEPARTMENT_EXPORT => \App\Jobs\DownloadJobs\DepartmentExportJob::class,
            DownloadJob::TYPE_ADVANCE_SALARY_EXPORT => \App\Jobs\DownloadJobs\AdvanceSalaryExportJob::class,
            DownloadJob::TYPE_ABSENCES_EXPORT => \App\Jobs\DownloadJobs\AbsencesExportJob::class,
            default => throw new \InvalidArgumentException("Unknown job type: {$job->job_type}")
        };

        $jobClass::dispatch($job);
    }

    /**
     * Create a job from existing report component data
     */
    public static function createFromReportComponent(string $reportType, object $component): DownloadJob
    {
        $filters = [];
        $config = ['format' => 'xlsx'];

        // Extract common filters from component
        $commonFilters = [
            'selectedCompanyId', 'selectedDepartmentId', 'employee_id', 
            'start_date', 'end_date', 'period', 'status', 'query_string'
        ];

        foreach ($commonFilters as $filter) {
            if (property_exists($component, $filter)) {
                $filters[$filter] = $component->$filter;
            }
        }

        // Extract report-specific filters
        if ($reportType === DownloadJob::TYPE_PAYSLIP_REPORT) {
            $specificFilters = ['email_status', 'sms_status'];
            foreach ($specificFilters as $filter) {
                if (property_exists($component, $filter)) {
                    $filters[$filter] = $component->$filter;
                }
            }
        }

        return self::createJob($reportType, $filters, $config);
    }
}
