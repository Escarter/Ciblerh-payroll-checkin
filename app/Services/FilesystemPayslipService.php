<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Company;

class FilesystemPayslipService
{
    /**
     * Settings configuration
     */
    private array $config;

    public function __construct()
    {
        $setting = $this->resolveSftpSetting();
        $this->config = [
            'push_path' => $setting->sftp_push_path ?? 'storage/app/sftp-push',
            'sync_enabled' => $setting->sftp_sync_enabled ?? false,
        ];
    }

    private function resolveSftpSetting(): ?\App\Models\Setting
    {
        $primary = \App\Models\Setting::query()
            ->where('company_id', 1)
            ->latest('id')
            ->first();

        if ($primary) {
            return $primary;
        }

        $configured = \App\Models\Setting::query()
            ->where(function ($query) {
                $query->whereRaw("TRIM(COALESCE(sftp_push_path, '')) <> ''")
                    ->orWhereRaw("TRIM(COALESCE(sftp_push_username, '')) <> ''");
            })
            ->latest('id')
            ->first();

        if ($configured) {
            return $configured;
        }

        return \App\Models\Setting::query()->latest('id')->first();
    }

    /**
     * Fetch payslips from local push directory with advanced filtering
     *
     * @param array $options Options for filtering:
     *   - lastModifiedAfter: int (timestamp) - only files modified after this time
     *   - maxDepth: int - limit directory depth (default: unlimited)
     *   - limit: int - max files to return per batch (default: 100)
     *   - offset: int - pagination offset (default: 0)
     *   - extensions: array - file extensions to include (default: ['pdf'])
     *   - returnDirs: bool - include directories in response (default: false)
     * @return array
     */
    public function fetchPayslipsFromPath(array $options = []): array
    {
        try {
            // Set defaults
            $lastModifiedAfter = $options['lastModifiedAfter'] ?? 0;
            $maxDepth = $options['maxDepth'] ?? null;
            $limit = $options['limit'] ?? 100;
            $offset = $options['offset'] ?? 0;
            $extensions = $options['extensions'] ?? ['pdf'];
            $returnDirs = $options['returnDirs'] ?? false;

            // Verify push path exists and is accessible
            $pushPath = base_path($this->config['push_path']);
            
            if (!file_exists($pushPath)) {
                return [
                    'success' => false,
                    'error' => "Push path does not exist: {$pushPath}",
                    'files' => [],
                    'count' => 0,
                    'skipped' => 0,
                ];
            }

            if (!is_readable($pushPath)) {
                return [
                    'success' => false,
                    'error' => "Push path is not readable: {$pushPath}",
                    'files' => [],
                    'count' => 0,
                    'skipped' => 0,
                ];
            }

            $files = [];
            $skipped = 0;
            $processedCount = 0;

            // Recursively get all files
            $allFiles = $this->getAllFiles($pushPath, $maxDepth);

            foreach ($allFiles as $filePath) {
                // Filter by file extension
                $filename = strtolower(basename($filePath));
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (!in_array($ext, $extensions)) {
                    $skipped++;
                    continue;
                }

                // Filter by lastModified timestamp
                $fileTimestamp = filemtime($filePath);
                if ($fileTimestamp < $lastModifiedAfter) {
                    $skipped++;
                    continue;
                }

                // Skip if path contains archived folders
                if (strpos($filePath, '/processed/') !== false || strpos($filePath, '/failed/') !== false) {
                    $skipped++;
                    continue;
                }

                $processedCount++;

                // Apply pagination
                if ($processedCount <= $offset) {
                    continue;
                }

                if (count($files) >= $limit) {
                    break;
                }

                // Safely extract file metadata
                try {
                    $relativePath = str_replace($pushPath . '/', '', $filePath);
                    
                    $files[] = [
                        'type' => 'file',
                        'path' => $relativePath,
                        'full_path' => $filePath,
                        'basename' => basename($filePath),
                        'filename' => pathinfo($filename, PATHINFO_FILENAME),
                        'size' => filesize($filePath),
                        'timestamp' => $fileTimestamp,
                        'mimetype' => mime_content_type($filePath) ?? 'application/octet-stream',
                    ];
                } catch (\Exception $e) {
                    \Log::warning("Error extracting metadata for {$filePath}: {$e->getMessage()}");
                    continue;
                }
            }

            return [
                'success' => true,
                'files' => $files,
                'count' => count($files),
                'skipped' => $skipped,
                'processed' => $processedCount,
                'hasMore' => $processedCount > ($offset + $limit),
                'offset' => $offset,
                'limit' => $limit,
                'path' => $pushPath,
            ];
        } catch (\Exception $e) {
            \Log::error("Filesystem fetch error: {$e->getMessage()}", [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'files' => [],
                'count' => 0,
                'skipped' => 0,
            ];
        }
    }

    /**
     * Recursively get all files up to max depth
     */
    private function getAllFiles(string $directory, ?int $maxDepth = null, int $currentDepth = 0): array
    {
        $files = [];

        if ($maxDepth !== null && $currentDepth >= $maxDepth) {
            return $files;
        }

        try {
            $items = scandir($directory);
            
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }

                $path = $directory . '/' . $item;

                if (is_file($path)) {
                    $files[] = $path;
                } elseif (is_dir($path)) {
                    $subFiles = $this->getAllFiles($path, $maxDepth, $currentDepth + 1);
                    $files = array_merge($files, $subFiles);
                }
            }
        } catch (\Exception $e) {
            \Log::warning("Error scanning directory {$directory}: {$e->getMessage()}");
        }

        return $files;
    }

    /**
     * Parse filename and extract metadata using matching strategies
     *
     * @param string $filename
     * @param string $filepath
     * @param int $timestamp
     * @param array $strategies
     * @return array
     */
    public function parsePayslipMetadata(string $filename, string $filepath, int $timestamp, array $strategies): array
    {
        $metadata = [
            'filename' => $filename,
            'filepath' => $filepath,
            'timestamp' => $timestamp,
            'matches' => [],
        ];

        // Remove extension
        $name = pathinfo($filename, PATHINFO_FILENAME);

        // Try each enabled strategy
        if (in_array('employee_id', $strategies)) {
            $this->extractEmployeeId($name, $metadata);
        }

        if (in_array('department_code', $strategies)) {
            $this->extractDepartmentCode($name, $metadata);
        }

        if (in_array('company_code', $strategies)) {
            $this->extractCompanyCode($name, $metadata);
        }

        if (in_array('folder_structure', $strategies)) {
            $this->extractFromFolderStructure($filepath, $metadata);
        }

        if (in_array('fuzzy', $strategies)) {
            $this->extractFromTimestamp($timestamp, $metadata);
        }

        return $metadata;
    }

    /**
     * Match payslip to entities with confidence scores
     */
    public function matchPayslipToEntities(array $metadata): array
    {
        $candidates = [];

        // Search by employee_id if found
        if (isset($metadata['matches']['employee_id'])) {
            $employee = \App\Models\User::find($metadata['matches']['employee_id']);
            if ($employee) {
                $candidates[] = [
                    'type' => 'employee_id',
                    'strategy' => 'employee_id',
                    'confidence' => 0.95,
                    'employee_id' => $employee->id,
                    'department_id' => $employee->department_id,
                    'company_id' => $employee->company_id,
                ];
            }
        }

        // Search by department_code if found
        if (isset($metadata['matches']['department_code'])) {
            $department = Department::where('code', $metadata['matches']['department_code'])->first();
            if ($department) {
                $candidates[] = [
                    'type' => 'department',
                    'strategy' => 'department_code',
                    'confidence' => 0.9,
                    'department_id' => $department->id,
                    'company_id' => $department->company_id,
                ];
            }
        }

        // Search by company_code if found
        if (isset($metadata['matches']['company_code'])) {
            $company = Company::where('code', $metadata['matches']['company_code'])->first();
            if ($company) {
                $candidates[] = [
                    'type' => 'company',
                    'strategy' => 'company_code',
                    'confidence' => 0.85,
                    'company_id' => $company->id,
                ];
            }
        }

        return $candidates;
    }

    /**
     * Archive a processed file by moving it to processed subdirectory
     */
    public function archiveFile(string $filePath): bool
    {
        try {
            $pushPath = base_path($this->config['push_path']);
            $fullPath = $pushPath . '/' . $filePath;
            $processedDir = $pushPath . '/processed';

            // Create processed directory if it doesn't exist
            if (!file_exists($processedDir)) {
                mkdir($processedDir, 0755, true);
            }

            $archivePath = $processedDir . '/' . basename($filePath);
            
            // Add timestamp to avoid conflicts
            $archivePath = $processedDir . '/' . pathinfo($filePath, PATHINFO_FILENAME) 
                         . '_' . time() 
                         . '.' . pathinfo($filePath, PATHINFO_EXTENSION);

            if (rename($fullPath, $archivePath)) {
                \Log::info("Archived payslip file: {$filePath} -> {$archivePath}");
                return true;
            }

            return false;
        } catch (\Exception $e) {
            \Log::error("Error archiving file {$filePath}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Extract employee ID from filename
     */
    private function extractEmployeeId(string $name, array &$metadata): void
    {
        if (preg_match('/(?:emp|employee|e)[\s_-]?(\d+)/i', $name, $matches)) {
            $metadata['matches']['employee_id'] = (int)$matches[1];
        }
    }

    /**
     * Extract department code from filename
     */
    private function extractDepartmentCode(string $name, array &$metadata): void
    {
        if (preg_match('/(?:dept|department|dept_code)[\s_-]?([A-Z0-9_]+)/i', $name, $matches)) {
            $metadata['matches']['department_code'] = strtoupper($matches[1]);
        }
    }

    /**
     * Extract company code from filename
     */
    private function extractCompanyCode(string $name, array &$metadata): void
    {
        if (preg_match('/(?:company|comp|co)[\s_-]?([A-Z0-9_]+)/i', $name, $matches)) {
            $metadata['matches']['company_code'] = strtoupper($matches[1]);
        }
    }

    /**
     * Extract department from folder structure
     */
    private function extractFromFolderStructure(string $filepath, array &$metadata): void
    {
        $parts = explode('/', $filepath);
        if (count($parts) > 1) {
            $metadata['matches']['folder_structure'] = $parts[0];
        }
    }

    /**
     * Extract month/year from file timestamp
     */
    private function extractFromTimestamp(int $timestamp, array &$metadata): void
    {
        $metadata['matches']['timestamp'] = [
            'month' => (int) date('m', $timestamp),
            'year' => (int) date('Y', $timestamp),
        ];
    }

    /**
     * Test if push path is accessible and writable
     */
    public function testPushPath(): array
    {
        try {
            $pushPath = base_path($this->config['push_path']);
            $incomingPath = $pushPath . '/incoming';
            $processedPath = $pushPath . '/processed';
            $failedPath = $pushPath . '/failed';

            // Auto-create directory if it doesn't exist
            if (!file_exists($pushPath)) {
                @mkdir($pushPath, 0755, true);
                if (!file_exists($pushPath)) {
                    return [
                        'success' => false,
                        'message' => "Failed to create push path: {$pushPath}",
                    ];
                }
            }

            foreach ([$incomingPath, $processedPath, $failedPath] as $directory) {
                if (!file_exists($directory)) {
                    @mkdir($directory, 0755, true);
                }

                if (!file_exists($directory)) {
                    return [
                        'success' => false,
                        'message' => "Failed to create required directory: {$directory}",
                    ];
                }
            }

            if (!is_readable($pushPath)) {
                return [
                    'success' => false,
                    'message' => "Push path is not readable: {$pushPath}",
                ];
            }

            if (!is_writable($pushPath)) {
                return [
                    'success' => false,
                    'message' => "Push path is not writable: {$pushPath}",
                ];
            }

            if (!is_readable($incomingPath)) {
                return [
                    'success' => false,
                    'message' => "Incoming path is not readable: {$incomingPath}",
                ];
            }

            if (!is_writable($incomingPath)) {
                return [
                    'success' => false,
                    'message' => "Incoming path is not writable: {$incomingPath}",
                ];
            }

            // Try to count files
            $result = $this->fetchPayslipsFromPath(['limit' => 1]);
            
            if (!$result['success']) {
                return [
                    'success' => false,
                    'message' => "Failed to list files: {$result['error']}",
                ];
            }

            return [
                'success' => true,
                'message' => "Push path and incoming lifecycle folders are accessible and writable",
                'path' => $incomingPath,
                'file_count' => $result['processed'],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
}
