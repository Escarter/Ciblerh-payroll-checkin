<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\EmployeeImport;
use App\Imports\DepartmentImport;
use App\Imports\CompanyImport;
use App\Imports\ServiceImport;
use App\Imports\LeaveTypeImport;
use App\Models\ImportJob;

class ImportDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out
     */
    public $timeout = 1200; // 20 minutes for large file processing

    /**
     * The number of times the job may be attempted
     */
    public $tries = 2;

    /**
     * Memory limit for large file processing
     */
    public $memory = 512;

    protected $importType;
    protected $filePath;
    protected $userId;
    protected $companyId;
    protected $departmentId;
    protected $serviceId;
    protected $autoCreateEntities;
    protected $sendWelcomeEmails;
    protected $importId;
    protected $importJobId;
    protected $importJob;
    protected $importResults;

    /**
     * Create a new job instance.
     */
    public function __construct(string $importType, string $filePath, int $userId, ?int $companyId = null, ?int $departmentId = null, ?int $serviceId = null, bool $autoCreateEntities = false, bool $sendWelcomeEmails = false, ?int $importJobId = null)
    {
        $this->importType = $importType;
        $this->filePath = $filePath;
        $this->userId = $userId;
        $this->companyId = $companyId;
        $this->departmentId = $departmentId;
        $this->serviceId = $serviceId;
        $this->autoCreateEntities = $autoCreateEntities;
        $this->sendWelcomeEmails = $sendWelcomeEmails;
        $this->importJobId = $importJobId;
        $this->importId = uniqid('import_', true);
        $this->queue = 'processing';

        // Note: importJob is loaded in handle() method to avoid deserialization issues
    }

    /**
     * Create initial import job record
     */
    protected function createImportJobRecord(): void
    {
        Log::info("Creating ImportJob record", [
            'import_id' => $this->importId,
            'import_type' => $this->importType,
            'user_id' => $this->userId
        ]);

        $fileName = basename($this->filePath);

        // Validate that user exists
        $user = \App\Models\User::find($this->userId);
        if (!$user) {
            throw new \Exception("User with ID {$this->userId} does not exist");
        }

        Log::info("User validation passed", ['user_id' => $this->userId]);

        // Validate company exists if provided
        if ($this->companyId && !\App\Models\Company::find($this->companyId)) {
            throw new \Exception("Company with ID {$this->companyId} does not exist");
        }

        // Validate department exists if provided
        if ($this->departmentId && !\App\Models\Department::find($this->departmentId)) {
            throw new \Exception("Department with ID {$this->departmentId} does not exist");
        }

        Log::info("Creating ImportJob model", [
            'import_type' => $this->importType,
            'user_id' => $this->userId,
            'file_name' => $fileName
        ]);

        $createData = [
            'import_type' => $this->importType,
            'user_id' => $this->userId,
            'company_id' => $this->companyId,
            'department_id' => $this->departmentId,
            'file_name' => $fileName,
            'file_path' => $this->filePath,
            'status' => ImportJob::STATUS_PENDING,
            'total_rows' => 0,
            'processed_rows' => 0,
            'successful_imports' => 0,
            'failed_imports' => 0,
            'import_config' => [
                'auto_create_entities' => $this->autoCreateEntities,
                'send_welcome_emails' => $this->sendWelcomeEmails,
            ],
            'started_at' => now(),
        ];

        Log::info("ImportJob create data", ['data' => $createData]);

        try {
            $this->importJob = ImportJob::create($createData);

            Log::info("ImportJob creation result", [
                'import_job_is_null' => is_null($this->importJob),
                'import_job_id' => $this->importJob ? $this->importJob->id : 'null',
                'import_job_exists' => $this->importJob ? $this->importJob->exists : 'null'
            ]);

            if (!$this->importJob) {
                throw new \Exception('Failed to create ImportJob record - creation returned null');
            }

            if (!$this->importJob->exists) {
                throw new \Exception('Failed to create ImportJob record - model does not exist in database');
            }

            Log::info("ImportJob record created successfully", [
                'import_id' => $this->importId,
                'job_id' => $this->importJob->id
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to create ImportJob record", [
                'import_id' => $this->importId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $createData
            ]);
            throw $e;
        }
    }


    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("=== IMPORT DATA JOB STARTED ===");

        Log::info("ImportDataJob handle started", [
            'import_type' => $this->importType,
            'file_path' => $this->filePath,
            'user_id' => $this->userId,
            'company_id' => $this->companyId,
            'department_id' => $this->departmentId,
            'auto_create_entities' => $this->autoCreateEntities,
            'import_job_id' => $this->importJobId
        ]);

        try {
            // Ensure import job record exists and is loaded
            if (!$this->importJob && $this->importJobId) {
                $this->importJob = ImportJob::find($this->importJobId);
            }

            // Create import job record if it doesn't exist (for jobs without importJobId)
            if (!$this->importJob) {
                $this->createImportJobRecord();
            }

            Log::info("Starting background import", [
                'import_id' => $this->importId,
                'type' => $this->importType,
                'user_id' => $this->userId,
                'job_id' => $this->importJob->id ?? 'null'
            ]);

            // Verify import job record exists
            Log::info("Verifying import job record", [
                'import_job_is_null' => is_null($this->importJob),
                'import_job_id' => $this->importJob ? $this->importJob->id : 'null',
                'import_job_exists' => $this->importJob ? $this->importJob->exists : 'null'
            ]);

            if (!$this->importJob) {
                throw new \Exception('ImportJob record was not created successfully');
            }

            if (!$this->importJob->exists) {
                throw new \Exception('ImportJob record exists but does not exist in database');
            }

            // Update status to processing (only if not already processing)
            if ($this->importJob->status !== ImportJob::STATUS_PROCESSING) {
                Log::info("About to update import job status", [
                    'job_id' => $this->importJob->id,
                    'current_status' => $this->importJob->status
                ]);

                $this->importJob->update([
                    'status' => ImportJob::STATUS_PROCESSING,
                    'started_at' => now()
                ]);
            }

            Log::info("Import job status updated successfully", [
                'job_id' => $this->importJob->id,
                'new_status' => $this->importJob->fresh()->status
            ]);

            // Get the file from storage
            $file = Storage::disk('local')->get($this->filePath);
            $tempPath = storage_path('app/temp/' . basename($this->filePath));

            // Ensure temp directory exists
            $tempDir = dirname($tempPath);
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Save to temp location for processing
            Storage::disk('local')->put('temp/' . basename($this->filePath), $file);

            Log::info("About to call performImport", ['temp_path' => $tempPath, 'file_path' => $this->filePath]);
            $this->importResults = $this->performImport($tempPath, $this->filePath);

            // Update database record with results
            $this->importJob->update([
                'status' => $this->importResults['success'] ? ImportJob::STATUS_COMPLETED : ImportJob::STATUS_FAILED,
                'total_rows' => ($this->importResults['imported_count'] ?? 0) + ($this->importResults['failed_count'] ?? 0),
                'processed_rows' => ($this->importResults['imported_count'] ?? 0) + ($this->importResults['failed_count'] ?? 0),
                'successful_imports' => $this->importResults['imported_count'] ?? 0,
                'failed_imports' => $this->importResults['failed_count'] ?? 0,
                'error_details' => $this->importResults['errors'] ?? null,
                'completed_at' => now(),
            ]);

            // Log import results
            $this->logImportResults();

            // Clean up temp file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }

            Log::info("Background import completed", [
                'import_id' => $this->importId,
                'results' => $this->importResults
            ]);

        } catch (\Exception $e) {
            Log::error("Background import failed", [
                'import_id' => $this->importId,
                'error' => $e->getMessage(),
                'user_id' => $this->userId
            ]);

            // Update record with failure
            $this->importJob->update([
                'status' => ImportJob::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            // Store failure results
            $this->importResults = [
                'success' => false,
                'error' => $e->getMessage(),
                'imported_count' => 0,
                'failed_count' => 0,
                'errors' => [$e->getMessage()]
            ];

            $this->logImportResults();
            throw $e;
        }
    }

    /**
     * Perform the actual import based on type
     */
    protected function performImport(string $tempPath, string $originalFilePath): array
    {
        \Log::info('performImport called', [
            'temp_path' => $tempPath,
            'original_file_path' => $originalFilePath
        ]);

        $import = $this->createImportInstance();

        \Log::info('Import instance created successfully', [
            'import_class' => get_class($import)
        ]);

        // Determine file type and import accordingly
        $fileExtension = strtolower(pathinfo($originalFilePath, PATHINFO_EXTENSION));

        try {
            \Log::info('About to call Excel::import', [
                'file_extension' => $fileExtension,
                'temp_path' => $tempPath
            ]);

            if ($fileExtension === 'csv') {
                // Import CSV file
                Excel::import($import, $tempPath, null, \Maatwebsite\Excel\Excel::CSV);
            } else {
                // Import Excel file (handle CSV files with .xlsx extension)
                $fileContent = file_get_contents($tempPath);
                if (strpos($fileContent, ',') !== false && strpos($fileContent, "\n") !== false) {
                    // File contains commas and newlines, likely CSV despite .xlsx extension
                    Excel::import($import, $tempPath, null, \Maatwebsite\Excel\Excel::CSV);
                } else {
                    Excel::import($import, $tempPath);
                }
            }

            // Get total rows from the file (excluding header)
            if ($fileExtension === 'csv') {
                // For CSV files, count lines
                $lines = file($tempPath);
                $totalRows = count($lines) - 1; // Subtract 1 for header row
            } else {
                // For Excel files, use PhpSpreadsheet
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tempPath);
                $worksheet = $spreadsheet->getActiveSheet();
                $totalRows = $worksheet->getHighestRow() - 1; // Subtract 1 for header row
            }
        } catch (\Exception $e) {
            Log::error("Failed to import file", [
                'import_id' => $this->importId,
                'file_path' => $originalFilePath,
                'temp_path' => $tempPath,
                'file_extension' => $fileExtension,
                'error' => $e->getMessage()
            ]);
            throw new \Exception("Failed to import file: " . $e->getMessage());
        }

        // Collect results from the import instance
        $results = [
            'success' => true,
            'imported_count' => 0, // Will be calculated below
            'failed_count' => 0,
            'errors' => [],
            'warnings' => []
        ];

        // Check for validation errors and failures (limit to prevent memory issues)
        $maxErrorsToStore = 100; // Limit error storage to prevent memory exhaustion
        $storedErrors = 0;

        if (method_exists($import, 'errors') && $import->errors()->count() > 0) {
            $results['failed_count'] = $import->errors()->count();
            $errors = $import->errors()->map(function($error) {
                return [
                    'row' => $error->row(),
                    'attribute' => $error->attribute(),
                    'errors' => $error->errors(),
                    'values' => $error->values()
                ];
            });

            // Limit the number of errors stored to prevent memory issues
            $results['errors'] = $errors->take($maxErrorsToStore)->toArray();
            $storedErrors = count($results['errors']);
        }

        if (method_exists($import, 'failures') && $import->failures()->count() > 0) {
            $results['failed_count'] += $import->failures()->count();
            $failureErrors = $import->failures()->map(function($failure) {
                return [
                    'row' => $failure->row(),
                    'attribute' => $failure->attribute(),
                    'errors' => $failure->errors(),
                    'values' => $failure->values()
                ];
            });

            // Add remaining failure errors up to the limit
            $remainingLimit = $maxErrorsToStore - $storedErrors;
            if ($remainingLimit > 0) {
                $additionalErrors = $failureErrors->take($remainingLimit)->toArray();
                $results['errors'] = array_merge($results['errors'], $additionalErrors);
                $storedErrors += count($additionalErrors);
            }
        }

        // If we hit the limit, add a summary message
        $totalErrors = ($import->errors()->count() ?? 0) + ($import->failures()->count() ?? 0);
        if ($totalErrors > $maxErrorsToStore) {
            $results['errors'][] = [
                'row' => null,
                'attribute' => 'summary',
                'errors' => ["Limited error display: showing {$maxErrorsToStore} of {$totalErrors} total errors. Check individual records for complete validation details."],
                'values' => []
            ];
        }

        // Calculate imported count as total rows minus failed rows
        $results['imported_count'] = max(0, $totalRows - $results['failed_count']);

        return $results;
    }

    /**
     * Create the appropriate import instance
     */
    protected function createImportInstance()
    {
        switch ($this->importType) {
            case 'employees':
                if ($this->companyId) {
                    $company = \App\Models\Company::find($this->companyId);
                    if (!$company) {
                        throw new \Exception('Company not found for employee import');
                    }

                    $department = null;
                    if ($this->departmentId) {
                        $department = \App\Models\Department::find($this->departmentId);
                        if (!$department) {
                            throw new \Exception('Department not found for employee import');
                        }
                    }

                    $service = null;
                    if ($this->serviceId) {
                        $service = \App\Models\Service::find($this->serviceId);
                        if (!$service) {
                            throw new \Exception('Service not found for employee import');
                        }
                    }

                    return new EmployeeImport($company, $department, $service, $this->autoCreateEntities, $this->userId, $this->sendWelcomeEmails);
                } else {
                    throw new \Exception('Company ID required for employee import');
                }

            case 'departments':
                \Log::error('*** DEPARTMENTS CASE EXECUTING ***');
                \Log::error('ImportDataJob creating DepartmentImport', [
                    'company_id' => $this->companyId,
                    'auto_create_entities' => $this->autoCreateEntities
                ]);

                $company = null;
                if ($this->companyId) {
                \Log::error('Looking up company', ['company_id' => $this->companyId, 'type' => gettype($this->companyId)]);
                $company = \App\Models\Company::find($this->companyId);
                \Log::error('Company::find result', ['company' => $company ? $company->toArray() : 'NULL']);
                if (!$company) {
                    \Log::error('Company not found for department import', [
                        'company_id' => $this->companyId,
                        'available_companies' => \App\Models\Company::pluck('id')->toArray()
                    ]);
                    throw new \Exception("Company with ID {$this->companyId} not found for department import");
                }
                if (!$company->id) {
                    \Log::error('Company found but has null ID', ['company' => $company->toArray()]);
                    throw new \Exception('Company found but has invalid ID');
                }
                \Log::error('Company found for DepartmentImport', [
                    'company_id' => $company->id,
                    'company_name' => $company->name
                ]);
                } else {
                    \Log::warning('No company ID provided for DepartmentImport - this may cause import failures');
                }

                \Log::info('Creating DepartmentImport instance', [
                    'company_provided' => $company ? true : false,
                    'company_id' => $company ? $company->id : null
                ]);

                return new DepartmentImport($company, $this->autoCreateEntities, $this->userId);

            case 'companies':
                return new CompanyImport($this->userId);

            case 'services':
                if ($this->departmentId) {
                    $department = \App\Models\Department::with('company')->find($this->departmentId);
                    if (!$department) {
                        throw new \Exception('Department not found for service import');
                    }
                    return new ServiceImport($department, $this->autoCreateEntities, $this->userId);
                } else {
                    throw new \Exception('Department ID required for service import');
                }

            case 'leave_types':
                return new LeaveTypeImport($this->userId);

            default:
                throw new \Exception("Unknown import type: {$this->importType}");
        }
    }

    /**
     * Log detailed import results
     */
    protected function logImportResults(): void
    {
        $user = \App\Models\User::find($this->userId);

        if ($this->importResults['success']) {
            $message = __($this->importType . '.background_import_completed', [
                'count' => $this->importResults['imported_count']
            ]);

            if ($this->importResults['failed_count'] > 0) {
                $message .= ' ' . __('common.import_partial_failures', [
                    'failed' => $this->importResults['failed_count']
                ]);
            }

            auditLog(
                $user, 
                $this->importType . '_imported', 
                'background', 
                $message,
                null, // No specific model for bulk imports
                [], // No old values
                [], // No new values
                [
                    'import_type' => $this->importType,
                    'import_id' => $this->importId ?? null,
                    'import_job_id' => $this->importJob->id ?? null,
                    'imported_count' => $this->importResults['imported_count'] ?? 0,
                    'failed_count' => $this->importResults['failed_count'] ?? 0,
                ] // Enhanced metadata
            );
        } else {
            auditLog(
                $user, 
                $this->importType . '_import_failed', 
                'background',
                __('common.import_failed_detailed', ['error' => $this->importResults['error']]),
                null, // No specific model
                [], // No old values
                [], // No new values
                [
                    'import_type' => $this->importType,
                    'import_id' => $this->importId ?? null,
                    'import_job_id' => $this->importJob->id ?? null,
                    'error' => $this->importResults['error'] ?? null,
                ] // Enhanced metadata with error details
            );
        }

        // Log detailed error information
        if (!empty($this->importResults['errors'])) {
            Log::info("Import detailed results", [
                'import_id' => $this->importId,
                'results' => $this->importResults
            ]);
        }
    }


    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception)
    {
        Log::error("Import job failed", [
            'import_id' => $this->importId,
            'error' => $exception->getMessage(),
            'user_id' => $this->userId
        ]);

        // Update results for failed job
        $this->importResults = [
            'success' => false,
            'error' => $exception->getMessage(),
            'imported_count' => 0,
            'failed_count' => 0,
            'errors' => [$exception->getMessage()]
        ];

    }
}
