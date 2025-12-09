<?php

namespace App\Services;

use App\Models\ImportJob;
use App\Models\User;
use App\Models\Company;
use App\Models\Department;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use App\Jobs\ImportDataJob;

class ImportService
{
    /**
     * Create a new import job and dispatch it to the queue
     */
    public static function createImportJob(string $importType, array $config = []): ImportJob
    {
        $user = Auth::user();

        if (!$user) {
            throw new \Exception('User must be authenticated to create import jobs.');
        }

        // Validate import type and permissions
        self::validateImportType($importType, $user);

        // Validate configuration
        $validatedConfig = self::validateImportConfig($importType, $config);

        $job = ImportJob::create([
            'import_type' => $importType,
            'user_id' => $user->id,
            'company_id' => $validatedConfig['company_id'] ?? null,
            'department_id' => $validatedConfig['department_id'] ?? null,
            'file_name' => basename($validatedConfig['file_path']),
            'file_path' => $validatedConfig['file_path'],
            'status' => ImportJob::STATUS_PENDING,
            'import_config' => $validatedConfig,
        ]);

        // Dispatch the import job
        self::dispatchImportJob($job);

        return $job;
    }

    /**
     * Get all available import types with their display names and configurations
     */
    public static function getAvailableImportTypes(): array
    {
        return [
            ImportJob::TYPE_EMPLOYEES => [
                'label' => __('common.employees'),
                'description' => __('import_types.employees_description'),
                'template' => 'employee_import_template.csv',
                'excel_template' => 'import_employees.xlsx',
                'permissions' => ['employee-create'],
                'fields' => [
                    'company_id' => [
                        'type' => 'select',
                        'label' => __('companies.company'),
                        'required' => true,
                        'options' => 'companies'
                    ],
                    'department_id' => [
                        'type' => 'select',
                        'label' => __('common.department'),
                        'required' => false,
                        'options' => 'departments',
                        'depends_on' => 'company_id',
                        'description' => __('import_types.department_override_description')
                    ],
                    'service_id' => [
                        'type' => 'select',
                        'label' => __('common.service'),
                        'required' => false,
                        'options' => 'services',
                        'description' => __('import_types.service_override_description')
                    ],
                    'auto_create_entities' => [
                        'type' => 'checkbox',
                        'label' => __('import_types.auto_create_departments_services'),
                        'required' => false,
                        'default' => false
                    ]
                ]
            ],
            ImportJob::TYPE_DEPARTMENTS => [
                'label' => __('common.departments'),
                'description' => __('import_types.departments_description'),
                'template' => 'department_import_template.csv',
                'excel_template' => 'import_departments.xlsx',
                'permissions' => ['department-create'],
                'fields' => [
                    'company_id' => [
                        'type' => 'select',
                        'label' => __('companies.company'),
                        'required' => true,
                        'options' => 'companies'
                    ]
                ]
            ],
            ImportJob::TYPE_COMPANIES => [
                'label' => __('common.companies'),
                'description' => __('import_types.companies_description'),
                'template' => 'company_import_template.csv',
                'excel_template' => 'import_companies.xlsx',
                'permissions' => ['company-create'],
                'fields' => []
            ],
            ImportJob::TYPE_SERVICES => [
                'label' => __('common.services'),
                'description' => __('import_types.services_description'),
                'template' => 'service_import_template.csv',
                'excel_template' => 'import_services.xlsx',
                'permissions' => ['service-create'],
                'fields' => [
                    'company_id' => [
                        'type' => 'select',
                        'label' => __('companies.company'),
                        'required' => true,
                        'options' => 'companies'
                    ],
                    'department_id' => [
                        'type' => 'select',
                        'label' => __('departments.department'),
                        'required' => true,
                        'options' => 'departments',
                        'depends_on' => 'company_id'
                    ],
                    'auto_create_entities' => [
                        'type' => 'checkbox',
                        'label' => __('import_types.auto_create_missing_entities'),
                        'required' => false,
                        'default' => false
                    ]
                ]
            ],
            ImportJob::TYPE_LEAVE_TYPES => [
                'label' => __('common.leave_types'),
                'description' => __('import_types.leave_types_description'),
                'template' => 'leave_type_import_template.csv',
                'excel_template' => 'import_leave_types.xlsx',
                'permissions' => ['leave_type-create'],
                'fields' => []
            ]
        ];
    }

    /**
     * Get import type configuration
     */
    public static function getImportTypeConfig(string $importType): array
    {
        $types = self::getAvailableImportTypes();

        if (!isset($types[$importType])) {
            throw new \Exception("Unknown import type: {$importType}");
        }

        return $types[$importType];
    }

    /**
     * Validate that user has permission for the import type
     */
    public static function validateImportType(string $importType, User $user): void
    {
        $config = self::getImportTypeConfig($importType);

        // Check if user has any of the required permissions
        $hasPermission = false;
        foreach ($config['permissions'] as $permission) {
            if ($user->can($permission)) {
                $hasPermission = true;
                break;
            }
        }

        if (!$hasPermission) {
            throw new \Exception(__('import_types.permission_denied'));
        }
    }

    /**
     * Validate import configuration
     */
    public static function validateImportConfig(string $importType, array $config): array
    {
        $typeConfig = self::getImportTypeConfig($importType);
        $validated = [];

        // Validate required fields
        foreach ($typeConfig['fields'] as $fieldName => $fieldConfig) {
            if ($fieldConfig['required'] && (!isset($config[$fieldName]) || empty($config[$fieldName]))) {
                throw new \Exception(__('validation.required', ['attribute' => $fieldConfig['label']]));
            }

            if (isset($config[$fieldName])) {
                $validated[$fieldName] = $config[$fieldName];
            } elseif (isset($fieldConfig['default'])) {
                $validated[$fieldName] = $fieldConfig['default'];
            }
        }

        // Validate file exists
        if (!isset($config['file_path']) || !\Storage::disk('local')->exists($config['file_path'])) {
            throw new \Exception(__('import_types.file_not_found'));
        }

        $validated['file_path'] = $config['file_path'];

        return $validated;
    }

    /**
     * Dispatch the import job to the queue
     */
    public static function dispatchImportJob(ImportJob $job): void
    {
        // Update job status to processing
        $job->update(['status' => ImportJob::STATUS_PROCESSING, 'started_at' => now()]);

        // Dispatch the background job
        ImportDataJob::dispatch(
            $job->import_type,
            $job->file_path,
            $job->user_id,
            $job->company_id,
            $job->department_id,
            $job->import_config['auto_create_entities'] ?? false,
            $job->id
        );
    }

    /**
     * Get import job statistics for a user
     */
    public static function getUserImportJobStats(int $userId): array
    {
        $jobs = ImportJob::forUser($userId);

        return [
            'total' => $jobs->count(),
            'pending' => $jobs->byStatus(ImportJob::STATUS_PENDING)->count(),
            'processing' => $jobs->byStatus(ImportJob::STATUS_PROCESSING)->count(),
            'completed' => $jobs->byStatus(ImportJob::STATUS_COMPLETED)->count(),
            'failed' => $jobs->byStatus(ImportJob::STATUS_FAILED)->count(),
            'cancelled' => $jobs->byStatus(ImportJob::STATUS_CANCELLED)->count(),
        ];
    }

    /**
     * Get available companies for import (filtered by user permissions)
     */
    public static function getAvailableCompanies(): \Illuminate\Database\Eloquent\Collection
    {
        $query = Company::where('is_active', true);

        // Filter by managed companies if user is a manager
        if (auth()->user()->hasRole('manager')) {
            $query->whereIn('id', auth()->user()->managerCompanies->pluck('id'));
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Get available departments for import (filtered by company and user permissions)
     */
    public static function getAvailableDepartments(?int $companyId = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = Department::where('is_active', true)->with('company');

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        // Filter by managed companies if user is a manager
        if (auth()->user()->hasRole('manager')) {
            $query->whereIn('company_id', auth()->user()->managerCompanies->pluck('id'));
        }

        // Filter by managed departments if user is a supervisor
        if (auth()->user()->hasRole('supervisor')) {
            $query->whereIn('id', auth()->user()->supDepartments->pluck('department_id'));
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Get available services for import (filtered by user permissions)
     */
    public static function getAvailableServices(): \Illuminate\Database\Eloquent\Collection
    {
        $query = Service::where('is_active', true)->with(['company', 'department']);

        // Filter by managed companies if user is a manager
        if (auth()->user()->hasRole('manager')) {
            $query->whereIn('company_id', auth()->user()->managerCompanies->pluck('id'));
        }

        // Filter by managed departments if user is a supervisor
        if (auth()->user()->hasRole('supervisor')) {
            $query->whereIn('department_id', auth()->user()->supDepartments->pluck('department_id'));
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Clean up old import jobs and associated files
     */
    public static function cleanupOldImportJobs(int $daysOld = 30): int
    {
        $cutoffDate = now()->subDays($daysOld);
        $oldJobs = ImportJob::where('created_at', '<', $cutoffDate)->get();
        $deletedCount = 0;

        foreach ($oldJobs as $job) {
            // Delete associated files
            if ($job->file_path && \Storage::disk('local')->exists($job->file_path)) {
                \Storage::disk('local')->delete($job->file_path);
            }

            $job->delete();
            $deletedCount++;
        }

        return $deletedCount;
    }
}