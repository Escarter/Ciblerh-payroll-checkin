<?php

declare(strict_types=1);

namespace App\Jobs\DownloadJobs;

use App\Models\DownloadJob;
use App\Models\Payslip;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class BulkPayslipDownloadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The queue connection name
     */

    protected $downloadJob;

    /**
     * Create a new job instance.
     */
    public function __construct(DownloadJob $downloadJob)
    {
        $this->queue = 'processing';
        $this->downloadJob = $downloadJob;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Refresh the model to ensure we have the latest data from the database
            $this->downloadJob->refresh();
            
            // Log filters at the start to debug
            \Log::info('BulkPayslipDownloadJob started', [
                'job_id' => $this->downloadJob->id,
                'filters' => $this->downloadJob->filters,
                'filters_raw' => json_encode($this->downloadJob->filters),
                'employee_id' => $this->downloadJob->filters['employee_id'] ?? 'not_set',
                'employee_id_type' => gettype($this->downloadJob->filters['employee_id'] ?? null),
                'employee_id_is_array' => is_array($this->downloadJob->filters['employee_id'] ?? null),
                'employee_id_count' => is_array($this->downloadJob->filters['employee_id'] ?? null) ? count($this->downloadJob->filters['employee_id']) : 'not_array',
                'employee_id_empty_check' => empty($this->downloadJob->filters['employee_id'] ?? null),
                'employee_id_json' => isset($this->downloadJob->filters['employee_id']) ? json_encode($this->downloadJob->filters['employee_id']) : 'not_set',
            ]);
            
            // Update job status to processing
            $this->downloadJob->update([
                'status' => DownloadJob::STATUS_PROCESSING,
                'started_at' => now()
            ]);

            // Get total count first (without loading into memory)
            $query = $this->getFilteredPayslipsQuery();
            $totalCount = $query->count();
            
            // Log query details for debugging
            \Log::info('Payslip query executed', [
                'job_id' => $this->downloadJob->id,
                'total_count' => $totalCount,
                'sql' => $query->toSql(),
                'bindings' => $query->getBindings(),
            ]);
            
            $this->downloadJob->update(['total_records' => $totalCount]);

            if ($totalCount === 0) {
                $this->downloadJob->update([
                    'status' => DownloadJob::STATUS_FAILED,
                    'error_message' => __('download_jobs.no_payslips_found_matching_criteria'),
                    'completed_at' => now()
                ]);
                return;
            }

            // Create zip file (using cursor to process in chunks without loading all into memory)
            // Note: Both ZIP and PDF formats create a ZIP archive with organized PDFs
            // PDF format means the payslips are PDFs organized in folders (same structure as ZIP)
            $zipPath = $this->createZipFile();
            
            // Determine file extension and mime type based on format
            $reportFormat = $this->downloadJob->report_format ?? DownloadJob::FORMAT_ZIP;
            $fileExtension = $reportFormat === DownloadJob::FORMAT_PDF ? 'zip' : 'zip'; // Both create ZIP archives
            $mimeType = 'application/zip'; // Both create ZIP archives

            // Update job completion
            $this->downloadJob->update([
                'status' => DownloadJob::STATUS_COMPLETED,
                'completed_at' => now(),
                'file_path' => $zipPath,
                'file_name' => basename($zipPath),
                'file_size' => Storage::disk('public')->size($zipPath),
                'mime_type' => $mimeType,
                'processed_records' => $this->downloadJob->processed_records,
                'failed_records' => $this->downloadJob->failed_records
            ]);

            \Log::info("Bulk payslip download completed successfully", [
                'job_id' => $this->downloadJob->id,
                'filename' => basename($zipPath),
                'processed' => $this->downloadJob->processed_records,
                'failed' => $this->downloadJob->failed_records
            ]);

        } catch (\Exception $e) {
            // Update job with error
            $this->downloadJob->update([
                'status' => DownloadJob::STATUS_FAILED,
                'error_message' => $e->getMessage(),
                'error_details' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ],
                'completed_at' => now()
            ]);

            \Log::error("Bulk payslip download failed", [
                'job_id' => $this->downloadJob->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get filtered payslips query builder (reusable for count and cursor)
     * Only includes payslips that have been successfully encrypted and have a valid file path
     */
    private function getFilteredPayslipsQuery()
    {
        return Payslip::query()
            // Explicitly select the file column to ensure it's loaded
            ->select('payslips.*')
            // Only include payslips that were successfully encrypted
            ->where('encryption_status', Payslip::STATUS_SUCCESSFUL)
            // Ensure file path exists and is not null/empty (multiple checks for safety)
            ->whereNotNull('file')
            ->where('file', '!=', '')
            ->where('file', '<>', '')
            ->whereRaw("file IS NOT NULL AND file != '' AND TRIM(file) != ''")
            ->when($this->downloadJob->filters['selectedCompanyId'] != "all", function ($q) {
                return $q->where('company_id', $this->downloadJob->filters['selectedCompanyId']);
            })->when($this->downloadJob->filters['selectedDepartmentId'] != "all", function ($q) {
                return $q->where('department_id', $this->downloadJob->filters['selectedDepartmentId']);
            })->when(isset($this->downloadJob->filters['employee_id']) && 
                     $this->downloadJob->filters['employee_id'] !== null && 
                     $this->downloadJob->filters['employee_id'] !== 'all' && 
                     $this->downloadJob->filters['employee_id'] !== '' &&
                     !(is_array($this->downloadJob->filters['employee_id']) && empty($this->downloadJob->filters['employee_id'])), function ($q) {
                $employeeId = $this->downloadJob->filters['employee_id'];
                
                // Log for debugging
                \Log::info('Filtering by employee_id', [
                    'employee_id_raw' => $employeeId,
                    'employee_id_type' => gettype($employeeId),
                    'is_array' => is_array($employeeId),
                    'employee_id_count' => is_array($employeeId) ? count($employeeId) : 'not_array',
                    'json_encode' => json_encode($employeeId)
                ]);
                
                // Handle both array (multiple employees) and single value (backward compatibility)
                if (is_array($employeeId)) {
                    // Multiple employees selected - filter out empty values and ensure integers
                    $employeeIds = array_values(array_filter(array_map(function($id) {
                        // Convert to int, filtering out any invalid values
                        return is_numeric($id) ? (int)$id : null;
                    }, $employeeId), fn($id) => $id !== null && $id > 0));
                    
                    if (!empty($employeeIds)) {
                        \Log::info('Using whereIn with employee IDs', [
                            'employee_ids' => $employeeIds,
                            'count' => count($employeeIds),
                            'query' => 'whereIn("employee_id", ' . json_encode($employeeIds) . ')'
                        ]);
                        return $q->whereIn('employee_id', $employeeIds);
                    } else {
                        \Log::warning('employee_id array is empty after filtering', [
                            'original' => $employeeId
                        ]);
                        // Still return query even if empty to maintain chain
                        return $q;
                    }
                } elseif ($employeeId !== 'all' && $employeeId !== null && $employeeId !== '' && $employeeId !== []) {
                    // Single employee (backward compatibility) - convert to array
                    $singleId = is_numeric($employeeId) ? (int)$employeeId : null;
                    if ($singleId) {
                        \Log::info('Using where with single employee ID', ['employee_id' => $singleId]);
                        return $q->where('employee_id', $singleId);
                    }
                } else {
                    \Log::warning('employee_id filter not applied - invalid value', [
                        'employee_id' => $employeeId,
                        'type' => gettype($employeeId)
                    ]);
                    // Return query to maintain chain
                    return $q;
                }
            })->when(!empty($this->downloadJob->filters['start_date']) && !empty($this->downloadJob->filters['end_date']), function ($q) {
                return $q->whereBetween('created_at', [
                    \Carbon\Carbon::parse($this->downloadJob->filters['start_date'])->startOfDay(),
                    \Carbon\Carbon::parse($this->downloadJob->filters['end_date'])->endOfDay()
                ]);
            });
    }

    /**
     * Create zip file with payslip PDFs
     * Uses cursor() to process payslips in chunks without loading all into memory
     * Supports both ZIP and PDF formats - both create ZIP archives with organized PDFs and password files
     */
    private function createZipFile(): string
    {
        // Both ZIP and PDF formats create a ZIP archive (since we have multiple PDFs)
        // The format just indicates the contents are PDFs
        $reportFormat = $this->downloadJob->report_format ?? DownloadJob::FORMAT_ZIP;
        $fileExtension = 'zip'; // Always create ZIP for bulk downloads (contains multiple PDFs)
        $zipFileName = 'payslips_' . Str::slug($this->getFilterDescription()) . '_' . now()->format('Y_m_d_H_i_s') . '.' . $fileExtension;
        $zipPath = 'downloads/' . $this->downloadJob->user_id . '/' . $zipFileName;

        // Ensure directory exists
        Storage::disk('public')->makeDirectory(dirname($zipPath));

        $zip = new ZipArchive();
        $fullZipPath = Storage::disk('public')->path($zipPath);
        
        if ($zip->open($fullZipPath, ZipArchive::CREATE) !== TRUE) {
            throw new \Exception(__('download_jobs.cannot_create_zip_file'));
        }

        $processed = 0;
        $failed = 0;
        $lastUpdateTime = now();
        $updateInterval = 5; // Update progress every 5 seconds

        // Use cursor() to process payslips one at a time without loading all into memory
        // Also load employee relationship as we iterate
        $query = $this->getFilteredPayslipsQuery();
        
        foreach ($query->cursor() as $payslip) {
            try {
                // CRITICAL: Extract and validate file path FIRST, before any other operations
                // This prevents any operations (like loading relationships) from affecting the file path
                $attributes = $payslip->getAttributes();
                $filePath = null;
                
                // Get file path from raw attributes first (most reliable)
                if (isset($attributes['file']) && $attributes['file'] !== null) {
                    $filePath = $attributes['file'];
                } else {
                    // Fallback to accessor
                    $filePath = $payslip->file ?? null;
                }
                
                // Validate file path immediately - if invalid, skip before doing anything else
                if ($filePath === null || !is_string($filePath) || trim($filePath) === '') {
                    $failed++;
                    \Log::warning("Payslip file path is invalid before processing", [
                        'payslip_id' => $payslip->id,
                        'file_path' => $filePath,
                        'file_path_type' => gettype($filePath),
                        'raw_attribute' => $attributes['file'] ?? 'not_set'
                    ]);
                    continue;
                }
                
                // Store validated file path in a safe variable - explicitly convert to string
                $validatedFilePath = (string) trim((string) $filePath);
                
                // Validate the trimmed result
                if ($validatedFilePath === '' || !is_string($validatedFilePath)) {
                    $failed++;
                    \Log::error("File path became invalid after trimming", [
                        'payslip_id' => $payslip->id,
                        'original_file_path' => $filePath,
                        'validated_file_path' => $validatedFilePath,
                        'type' => gettype($validatedFilePath)
                    ]);
                    continue;
                }
                
                // Additional safety checks: Verify encryption status
                if ($payslip->encryption_status !== Payslip::STATUS_SUCCESSFUL) {
                    $failed++;
                    \Log::warning("Payslip encryption not successful, skipping", [
                        'payslip_id' => $payslip->id,
                        'encryption_status' => $payslip->encryption_status
                    ]);
                    continue;
                }

                // Load relationships needed for folder organization (lazy loading)
                // NOTE: We do this AFTER extracting the file path to prevent any interference
                $payslip->load(['company', 'department', 'employee']);
                
                // CRITICAL: Re-validate validatedFilePath after loading relationships
                // This ensures nothing changed it
                if (!isset($validatedFilePath) || !is_string($validatedFilePath) || trim($validatedFilePath) === '') {
                    $failed++;
                    \Log::error("Validated file path became invalid after loading relationships", [
                        'payslip_id' => $payslip->id,
                        'validated_file_path' => $validatedFilePath ?? 'null'
                    ]);
                    continue;
                }
                
                // Check if file actually exists on disk
                // NOTE: We're using $validatedFilePath which was set BEFORE loading relationships
                try {
                    // Create a final safe copy with explicit string casting
                    $finalFilePath = (string) trim((string) $validatedFilePath);
                    
                    // Final validation - ensure it's a valid non-empty string
                    if (!is_string($finalFilePath) || $finalFilePath === '' || strlen($finalFilePath) === 0) {
                        throw new \RuntimeException("Final file path validation failed: " . var_export($finalFilePath, true));
                    }
                    
                    // CRITICAL: Log the exact value before calling exists() to help debug
                    \Log::info("About to check file existence", [
                        'payslip_id' => $payslip->id,
                        'final_file_path' => $finalFilePath,
                        'final_file_path_type' => gettype($finalFilePath),
                        'final_file_path_length' => strlen($finalFilePath),
                        'is_string' => is_string($finalFilePath),
                        'is_empty' => empty($finalFilePath),
                        'md5' => md5($finalFilePath) // To verify it's not just whitespace
                    ]);
                    
                    // Now safely call exists() - finalFilePath is guaranteed to be a non-empty string
                    // Use our safe wrapper function which has explicit string type declaration
                    // This ensures PHP will enforce the type before the function is even called
                    $fileExists = $this->safeFileExists($finalFilePath);
                    
                    // Update filePath for use later
                    $filePath = $finalFilePath;
                    
                    if (!$fileExists) {
                        $failed++;
                        \Log::warning("Payslip file not found on disk", [
                            'payslip_id' => $payslip->id,
                            'file_path' => $finalFilePath
                        ]);
                        continue;
                    }
                    
                    // Update filePath to use the final validated version
                    $filePath = $finalFilePath;
                } catch (\TypeError $e) {
                    // Catch any type errors from Storage::exists() receiving null
                    $failed++;
                    \Log::error("Type error checking payslip file existence", [
                        'payslip_id' => $payslip->id,
                        'validated_file_path' => $validatedFilePath ?? 'null',
                        'final_file_path' => $finalFilePath ?? 'null',
                        'file_path_type' => isset($finalFilePath) ? gettype($finalFilePath) : 'null',
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    continue;
                } catch (\InvalidArgumentException | \RuntimeException $e) {
                    $failed++;
                    \Log::error("Invalid file path argument", [
                        'payslip_id' => $payslip->id,
                        'validated_file_path' => $validatedFilePath ?? 'null',
                        'final_file_path' => $finalFilePath ?? 'null',
                        'file_path_type' => isset($finalFilePath) ? gettype($finalFilePath) : 'null',
                        'error' => $e->getMessage()
                    ]);
                    continue;
                } catch (\Exception $e) {
                    $failed++;
                    \Log::error("Unexpected error checking file existence", [
                        'payslip_id' => $payslip->id,
                        'validated_file_path' => $validatedFilePath ?? 'null',
                        'final_file_path' => $finalFilePath ?? 'null',
                        'error' => $e->getMessage(),
                        'error_class' => get_class($e)
                    ]);
                    continue;
                }

                // Validate matricule, year, and month are not null before building paths
                if (empty($payslip->matricule) || empty($payslip->year) || empty($payslip->month)) {
                    $failed++;
                    \Log::warning("Payslip missing required fields (matricule, year, or month)", [
                        'payslip_id' => $payslip->id,
                        'matricule' => $payslip->matricule ?? 'null',
                        'year' => $payslip->year ?? 'null',
                        'month' => $payslip->month ?? 'null'
                    ]);
                    continue;
                }

                // Double-check file path is still valid before using it
                if (empty($filePath) || !is_string($filePath)) {
                    $failed++;
                    \Log::error("File path became invalid after validation", [
                        'payslip_id' => $payslip->id,
                        'file_path' => $filePath
                    ]);
                    continue;
                }

                // Build folder structure: Company/Department/Employee
                $folderPath = $this->buildFolderPath($payslip);
                
                // Log folder path for debugging
                \Log::debug('Building folder path for payslip', [
                    'payslip_id' => $payslip->id,
                    'employee_id' => $payslip->employee_id,
                    'folder_path' => $folderPath,
                    'selectedCompanyId' => $this->downloadJob->filters['selectedCompanyId'] ?? 'not_set',
                    'selectedDepartmentId' => $this->downloadJob->filters['selectedDepartmentId'] ?? 'not_set',
                    'filter_employee_id' => $this->downloadJob->filters['employee_id'] ?? 'not_set',
                ]);
                
                // All checks passed - add PDF to zip with folder structure
                $fileName = $payslip->matricule . '_' . $payslip->year . '_' . $payslip->month . '.pdf';
                $pdfPathInZip = trim($folderPath . '/' . $fileName, '/');
                
                try {
                    $sourcePath = Storage::disk('modified')->path($filePath);
                    $zip->addFile($sourcePath, $pdfPathInZip);
                } catch (\Exception $e) {
                    $failed++;
                    \Log::error("Error adding PDF file to zip", [
                        'payslip_id' => $payslip->id,
                        'file_path' => $filePath,
                        'source_path' => $sourcePath ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                    continue;
                }

                // Create and add TXT file with PDF password in same folder
                // Always try to include the password file if available
                $pdfPassword = null;
                
                // First try: get from already loaded employee relationship
                if ($payslip->employee && !empty($payslip->employee->pdf_password)) {
                    $pdfPassword = $payslip->employee->pdf_password;
                } elseif ($payslip->employee_id) {
                    // Second try: load employee directly if not already loaded
                    try {
                        $employee = \App\Models\User::find($payslip->employee_id);
                        if ($employee && !empty($employee->pdf_password)) {
                            $pdfPassword = $employee->pdf_password;
                        }
                    } catch (\Exception $e) {
                        \Log::warning("Could not load employee for password", [
                            'payslip_id' => $payslip->id,
                            'employee_id' => $payslip->employee_id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
                
                // Add password file if we found a password
                if (!empty($pdfPassword)) {
                    $passwordFileName = $payslip->matricule . '_' . $payslip->year . '_' . $payslip->month . '_password.txt';
                    $passwordPathInZip = trim($folderPath . '/' . $passwordFileName, '/');
                    
                    // Add password file directly to zip using addFromString (no temporary file needed)
                    $zip->addFromString($passwordPathInZip, $pdfPassword);
                } else {
                    \Log::warning("PDF password not found for payslip", [
                        'payslip_id' => $payslip->id,
                        'employee_id' => $payslip->employee_id,
                        'matricule' => $payslip->matricule
                    ]);
                }

                $processed++;
            } catch (\Exception $e) {
                $failed++;
                \Log::error("Error adding payslip to zip", [
                    'payslip_id' => $payslip->id,
                    'encryption_status' => $payslip->encryption_status ?? 'unknown',
                    'file_path' => $payslip->file ?? 'null',
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }

            // Update progress periodically (every N seconds or every 100 records) to reduce database load
            if (now()->diffInSeconds($lastUpdateTime) >= $updateInterval || ($processed + $failed) % 100 === 0) {
                $this->downloadJob->update([
                    'processed_records' => $processed,
                    'failed_records' => $failed
                ]);
                $lastUpdateTime = now();
            }
        }

        // Final progress update
        $this->downloadJob->update([
            'processed_records' => $processed,
            'failed_records' => $failed
        ]);

        $zip->close();

        return $zipPath;
    }

    /**
     * Generate filter description for filename
     */
    private function getFilterDescription(): string
    {
        $parts = [];
        
        if (isset($this->downloadJob->filters['employee_name'])) {
            $parts[] = $this->downloadJob->filters['employee_name'];
        }
        
        if (isset($this->downloadJob->filters['company_name'])) {
            $parts[] = $this->downloadJob->filters['company_name'];
        }
        
        if (isset($this->downloadJob->filters['start_date']) && isset($this->downloadJob->filters['end_date'])) {
            $parts[] = $this->downloadJob->filters['start_date'] . '_to_' . $this->downloadJob->filters['end_date'];
        }

        return implode('_', $parts) ?: 'all_payslips';
    }

    /**
     * Build folder path structure
     * If company/department are not selected but multiple employees are: Employee Name (Matricule)/
     * Otherwise: Company/Department/Employee/
     * Sanitizes folder names to be filesystem-safe
     */
    private function buildFolderPath($payslip): string
    {
        $selectedCompanyId = $this->downloadJob->filters['selectedCompanyId'] ?? 'all';
        $selectedDepartmentId = $this->downloadJob->filters['selectedDepartmentId'] ?? 'all';
        $employeeId = $this->downloadJob->filters['employee_id'] ?? [];
        
        // Log filter values for debugging
        \Log::debug('buildFolderPath called', [
            'selectedCompanyId' => $selectedCompanyId,
            'selectedDepartmentId' => $selectedDepartmentId,
            'employeeId' => $employeeId,
            'employeeId_type' => gettype($employeeId),
            'employeeId_is_array' => is_array($employeeId),
            'employeeId_empty' => empty($employeeId),
            'employeeId_count' => is_array($employeeId) ? count($employeeId) : 'not_array',
        ]);
        
        // Check if we're in "multiple employees only" mode (no company/department selected)
        $isMultipleEmployeesOnly = false;
        if (($selectedCompanyId === 'all' || empty($selectedCompanyId)) && 
            ($selectedDepartmentId === 'all' || empty($selectedDepartmentId)) &&
            is_array($employeeId) && !empty($employeeId)) {
            $isMultipleEmployeesOnly = true;
            \Log::debug('Using multiple employees only mode', ['employee_count' => count($employeeId)]);
        } else {
            \Log::debug('Using full hierarchical structure mode');
        }
        
        $folders = [];
        
        if ($isMultipleEmployeesOnly) {
            // Direct employee folder structure: Employee Name (Matricule)/
            if ($payslip->employee) {
                $employeeName = trim($payslip->employee->first_name . ' ' . $payslip->employee->last_name);
                if (!empty($employeeName)) {
                    $folders[] = $this->sanitizeFolderName($employeeName . ' (' . $payslip->matricule . ')');
                } else {
                    $folders[] = $this->sanitizeFolderName('Employee_' . $payslip->matricule);
                }
            } else {
                // Fallback to payslip name or matricule
                $employeeName = trim($payslip->first_name . ' ' . $payslip->last_name);
                if (!empty($employeeName)) {
                    $folders[] = $this->sanitizeFolderName($employeeName . ' (' . $payslip->matricule . ')');
                } else {
                    $folders[] = $this->sanitizeFolderName('Employee_' . $payslip->matricule);
                }
            }
        } else {
            // Full hierarchical structure: Company/Department/Employee/
            // Company folder
            if ($payslip->company && $payslip->company->name) {
                $folders[] = $this->sanitizeFolderName($payslip->company->name);
            } else {
                $folders[] = 'Unknown_Company';
            }
            
            // Department folder
            if ($payslip->department && $payslip->department->name) {
                $folders[] = $this->sanitizeFolderName($payslip->department->name);
            } else {
                $folders[] = 'Unknown_Department';
            }
            
            // Employee folder (use name if available, otherwise matricule)
            if ($payslip->employee) {
                $employeeName = trim($payslip->employee->first_name . ' ' . $payslip->employee->last_name);
                if (!empty($employeeName)) {
                    $folders[] = $this->sanitizeFolderName($employeeName . ' (' . $payslip->matricule . ')');
                } else {
                    $folders[] = $this->sanitizeFolderName('Employee_' . $payslip->matricule);
                }
            } else {
                // Fallback to payslip name or matricule
                $employeeName = trim($payslip->first_name . ' ' . $payslip->last_name);
                if (!empty($employeeName)) {
                    $folders[] = $this->sanitizeFolderName($employeeName . ' (' . $payslip->matricule . ')');
                } else {
                    $folders[] = $this->sanitizeFolderName('Employee_' . $payslip->matricule);
                }
            }
        }
        
        return implode('/', $folders);
    }

    /**
     * Safely check if a file exists on the modified disk
     * This wrapper ensures we never pass null to Storage::exists()
     * 
     * @param string $filePath The file path to check (must be a non-empty string)
     * @return bool True if file exists, false otherwise
     * @throws \InvalidArgumentException If file path is invalid
     */
    private function safeFileExists(string $filePath): bool
    {
        // Explicit type declaration in function signature ensures $filePath is a string
        // With strict_types=1, PHP will throw TypeError if null is passed
        // This prevents null from ever reaching Storage::exists()
        
        // Additional runtime validation as a safety net
        if ($filePath === null) {
            \Log::error("safeFileExists received null despite type declaration", [
                'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
            ]);
            throw new \InvalidArgumentException("File path cannot be null");
        }
        
        if (!is_string($filePath)) {
            \Log::error("safeFileExists received non-string despite type declaration", [
                'type' => gettype($filePath),
                'value' => var_export($filePath, true),
                'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
            ]);
            throw new \InvalidArgumentException("File path must be a string, got: " . gettype($filePath));
        }
        
        $trimmed = trim($filePath);
        if ($trimmed === '') {
            \Log::error("safeFileExists received empty string", [
                'original_length' => strlen($filePath),
                'backtrace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5)
            ]);
            throw new \InvalidArgumentException("File path cannot be empty");
        }
        
        // Log the call for debugging
        \Log::debug("safeFileExists called", [
            'file_path' => $trimmed,
            'file_path_length' => strlen($trimmed),
            'file_path_type' => gettype($trimmed)
        ]);
        
        // Now safely call Storage::exists() - we've verified it's a non-empty string
        return Storage::disk('modified')->exists($trimmed);
    }

    /**
     * Sanitize folder/file name to be filesystem-safe
     * Removes or replaces invalid characters for folder names
     */
    private function sanitizeFolderName($name): string
    {
        // Remove or replace invalid filesystem characters
        $name = preg_replace('/[<>:"|?*\x00-\x1f]/', '', $name);
        
        // Replace slashes and backslashes
        $name = str_replace(['/', '\\'], '-', $name);
        
        // Trim and replace multiple spaces/hyphens with single
        $name = preg_replace('/[\s-]+/', '-', trim($name));
        
        // Remove leading/trailing dots and spaces (Windows issue)
        $name = trim($name, '. ');
        
        // Ensure it's not empty
        if (empty($name)) {
            $name = 'Unnamed';
        }
        
        // Limit length (avoid filesystem issues)
        if (strlen($name) > 200) {
            $name = substr($name, 0, 200);
        }
        
        return $name;
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        $this->downloadJob->update([
            'status' => DownloadJob::STATUS_FAILED,
            'error_message' => $exception->getMessage(),
            'error_details' => [
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ],
            'completed_at' => now()
        ]);
    }
}