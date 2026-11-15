<?php

namespace App\Livewire\Traits;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithLimit;
use Illuminate\Support\Collection;

trait WithImportPreview
{
    // Step management
    public $currentStep = 'upload'; // 'upload' | 'preview' | 'confirm'

    // Preview-related properties
    public $previewData = [];
    public $previewErrors = [];
    public $sampleValidationErrors = [];
    public $showPreview = false;
    public $totalRows = 0;
    public $processedRows = 0;
    public $hasLargeFile = false;
    public $fileAnalysisWarning = '';
    public $isRowCountEstimated = false;
    public $previewCacheKey;
    public $isProcessingPreview = false;
    public $processingStep = '';
    public $processingProgress = 0;

    // Quick validation settings
    protected $sampleSize = 2; // Only validate first 2 rows for quick feedback

    /**
     * Maximum rows to show in preview
     */
    protected $maxPreviewRows = 5;

    /**
     * File size threshold for large file warning (in MB)
     */
    protected $largeFileThreshold = 1;

    /**
     * Maximum file size for preview (in MB)
     * Keep reasonable to prevent timeouts
     */
    public $maxFileSize = 50;

    /**
     * Cache TTL for preview data (in minutes)
     */
    protected $previewCacheTtl = 15;

    /**
     * Initialize preview properties
     */
    public function initializePreview()
    {
        $this->previewCacheKey = "import_preview_" . $this->getImportContextId() . "_" . session()->getId();
        $this->loadCachedPreview();
    }

    /**
     * Get unique context ID for caching (should be overridden by implementing class)
     */
    protected function getImportContextId()
    {
        return auth()->id() . '_' . now()->timestamp;
    }

    /**
     * Process file for preview
     */
    public function processPreview()
    {
        $this->isProcessingPreview = true;
        $this->processingProgress = 0;
        $this->processingStep = __('common.validating_file');

        try {
            // Step 1: Quick file validation
            $this->quickValidateFile();
            $this->processingProgress = 25;
            $this->processingStep = __('common.analyzing_structure');

            // Step 2: Quick structure check and sampling
            $fileInfo = $this->analyzeFileStructure();
            $this->processingProgress = 50;

            // Check if we should skip preview for large files
            if (isset($fileInfo['skip_preview']) && $fileInfo['skip_preview']) {
                $this->processingProgress = 100;
                $this->processingStep = __('common.ready_for_import');

                // Store basic file info for background processing
                $this->totalRows = $fileInfo['total_rows'];
                $this->hasLargeFile = $fileInfo['is_large'];
                $this->isRowCountEstimated = $fileInfo['estimated'] ?? false;
                $this->previewData = []; // No preview data for large files
                $this->sampleValidationErrors = [];

                // Store skip reason if provided
                if (isset($fileInfo['skip_reason'])) {
                    $this->fileAnalysisWarning = $fileInfo['skip_reason'];
                } elseif (isset($fileInfo['warning'])) {
                    $this->fileAnalysisWarning = $fileInfo['warning'];
                }

                $this->showPreview = false; // Don't show preview modal
                $this->currentStep = 'confirm'; // Skip to confirmation step

                // Dispatch event to indicate we're ready for import
                $this->dispatch('previewSkipped', [
                    'reason' => $fileInfo['skip_reason'] ?? __('common.large_file_preview_skipped'),
                    'totalRows' => $fileInfo['total_rows']
                ]);

                return;
            }

            $this->processingStep = __('common.sampling_data');

            // Step 3: Sample validation (only a few rows)
            $sampleData = $this->extractSampleData();
            $this->processingProgress = 75;
            $this->processingStep = __('common.validating_sample');

            // Step 4: Validate sample data
            $this->validateSampleData($sampleData);
            $this->processingProgress = 100;
            $this->processingStep = __('common.ready_for_import');

            // Store basic file info for background processing
            $this->totalRows = $fileInfo['total_rows'];
            $this->hasLargeFile = $fileInfo['is_large'];
            $this->isRowCountEstimated = $fileInfo['estimated'] ?? false;
            $this->previewData = $sampleData;
            $this->sampleValidationErrors = $this->previewErrors ?? [];

            // Store file analysis warnings if any
            if (isset($fileInfo['warning'])) {
                $this->fileAnalysisWarning = $fileInfo['warning'];
            }

            $this->showPreview = true;
            $this->currentStep = 'preview';

            // Dispatch event to show modal
            $this->dispatch('showPreviewChanged', true);

        } catch (\Exception $e) {
            $dangerMessage = $e->getMessage();

            // Provide more user-friendly danger messages
            if (str_contains($dangerMessage, 'Maximum execution time')) {
                $dangerMessage = __('common.file_processing_timeout');
            } elseif (str_contains($dangerMessage, 'Allowed memory size')) {
                $dangerMessage = __('common.file_too_large_memory');
            }

            Log::danger('Import preview processing failed', [
                'danger' => $e->getMessage(),
                'user_id' => auth()->id(),
                'file' => $this->getFileProperty() ? $this->getFileProperty()->getClientOriginalName() : 'unknown'
            ]);

            $this->dispatch("showToast", message: __('common.preview_processing_failed', [
                'danger' => $dangerMessage
            ]), type: "danger");
        } finally {
            $this->isProcessingPreview = false;
            $this->processingStep = '';
            $this->processingProgress = 0;
        }
    }

    /**
     * Quick file validation (basic checks only)
     */
    protected function quickValidateFile()
    {
        $file = $this->getFileProperty();

        if (!$file) {
            throw new \Exception(__('common.no_file_selected'));
        }

        // Check file size with more granular limits
        $fileSizeMB = is_object($file) ? $file->getSize() / (1024 * 1024) : \Storage::size($file) / (1024 * 1024);

        if ($fileSizeMB > $this->maxFileSize) {
            throw new \Exception(__('common.file_too_large', [
                'max_size' => $this->maxFileSize
            ]));
        }

        // Provide guidance for different file sizes
        if ($fileSizeMB > 2) {
            // Log for monitoring large file processing
            \Log::info("Medium-large file upload detected", [
                'size_mb' => $fileSizeMB,
                'user_id' => auth()->id()
            ]);
        }

        // Check file type (more permissive validation)
        $allowedTypes = ['xlsx', 'xls', 'csv', 'txt'];
        $extension = strtolower($file->getClientOriginalExtension());

        // Debug logging
        \Log::info('File validation in trait', [
            'extension' => $extension,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'allowed_types' => $allowedTypes
        ]);

        if (!in_array($extension, $allowedTypes)) {
            // Try to detect CSV files that might have wrong extensions
            $mimeType = $file->getMimeType();
            $csvMimeTypes = ['text/csv', 'text/plain', 'application/csv', 'text/comma-separated-values'];

            if (in_array($mimeType, $csvMimeTypes)) {
                // Allow CSV-like files even if extension is wrong
                \Log::info('Allowing CSV-like file with non-standard extension', [
                    'extension' => $extension,
                    'mime_type' => $mimeType
                ]);
            } else {
                throw new \Exception(__('common.invalid_file_type', [
                    'allowed_types' => implode(', ', $allowedTypes)
                ]));
            }
        }
    }

    /**
     * Analyze file structure and get basic info
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
                $collection = Excel::toCollection(new class implements \Maatwebsite\Excel\Concerns\ToCollection {
                    public function collection(\Illuminate\Support\Collection $rows) {
                        return $rows;
                    }
                }, $file)->first();

                $totalRows = $collection ? $collection->count() : 0;

                // Skip preview if more than 10 data rows (accounting for header)
                if ($totalRows > 11) { // Headers + 10 data rows
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
                $sampleCheck = Excel::toCollection(new class implements \Maatwebsite\Excel\Concerns\ToCollection, \Maatwebsite\Excel\Concerns\WithLimit {
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
                    // Medium file with 12-99 rows, read fully for accuracy
                    $collection = Excel::toCollection(new class implements \Maatwebsite\Excel\Concerns\ToCollection {
                        public function collection(\Illuminate\Support\Collection $rows) {
                            return $rows;
                        }
                    }, $file)->first();

                    $totalRows = $collection ? $collection->count() : 0;

                    return [
                        'total_rows' => $totalRows,
                        'is_large' => false,
                        'has_data' => $totalRows > 1,
                        'estimated' => false,
                        'skip_preview' => true,
                        'skip_reason' => __('common.large_record_count_for_preview', ['count' => $totalRows - 1])
                    ];
                } else {
                    // Small file, read fully
                    $collection = Excel::toCollection(new class implements \Maatwebsite\Excel\Concerns\ToCollection {
                        public function collection(\Illuminate\Support\Collection $rows) {
                            return $rows;
                        }
                    }, $file)->first();
                }
            }

            // Restore original timeout
            set_time_limit($originalTimeout);

            if (!$collection) {
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
            // Restore original timeout even on danger
            if (isset($originalTimeout)) {
                set_time_limit($originalTimeout);
            }

            throw new \Exception(__('common.file_structure_danger', ['danger' => $e->getMessage()]));
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

        $extension = strtolower($this->getFileProperty()->getClientOriginalExtension());

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
     * Extract sample data for validation (only a few rows)
     */
    protected function extractSampleData(): array
    {
        $file = $this->getFileProperty();

        try {
            // Set a shorter timeout for sample extraction
            $originalTimeout = ini_get('max_execution_time');
            set_time_limit(10); // 10 seconds for sample extraction

            $collection = Excel::toCollection(new class($this->sampleSize + $this->getStartRow()) implements \Maatwebsite\Excel\Concerns\ToCollection, \Maatwebsite\Excel\Concerns\WithLimit {
                private $maxRows;

                public function __construct($maxRows) {
                    $this->maxRows = $maxRows;
                }

                public function limit(): int {
                    return $this->maxRows;
                }

                public function collection(\Illuminate\Support\Collection $rows) {
                    return $rows;
                }
            }, $file)->first();

            // Restore original timeout
            set_time_limit($originalTimeout);

            if (!$collection) {
                return [];
            }

            // Sample only the first few data rows (skip header)
            return $collection
                ->skip($this->getStartRow() - 1)
                ->take($this->sampleSize)
                ->map(function ($row, $index) {
                    return [
                        'row_number' => $index + $this->getStartRow(),
                        'data' => $row->toArray(),
                        'raw_data' => $row
                    ];
                })
                ->values()
                ->toArray();

        } catch (\Exception $e) {
            // Restore original timeout even on danger
            if (isset($originalTimeout)) {
                set_time_limit($originalTimeout);
            }

            \Log::warning('Sample data extraction failed, using empty sample', [
                'danger' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return []; // Return empty sample to allow processing to continue
        }
    }

    /**
     * Validate sample data (only a few rows for quick feedback)
     */
    protected function validateSampleData(array &$sampleData): void
    {
        $this->previewErrors = [];

        // Preload validation data (optimization)
        $this->preloadValidationData();

        foreach ($sampleData as &$row) {
            try {
                $validationResult = $this->validatePreviewRow($row['data'], $row['row_number']);
                $row['validation'] = $validationResult;

                if (!$validationResult['valid']) {
                    $this->previewErrors[] = [
                        'row' => $row['row_number'],
                        'dangers' => $validationResult['dangers'],
                        'warnings' => $validationResult['warnings'] ?? []
                    ];
                }
            } catch (\Exception $e) {
                $row['validation'] = [
                    'valid' => false,
                    'dangers' => [__('common.row_validation_danger', ['danger' => $e->getMessage()])],
                    'warnings' => []
                ];

                $this->previewErrors[] = [
                    'row' => $row['row_number'],
                    'dangers' => [__('common.row_validation_danger', ['danger' => $e->getMessage()])],
                    'warnings' => []
                ];
            }
        }

        $this->processedRows = count($sampleData);
    }

    /**
     * Extract data for preview (first N rows)
     */
    protected function extractPreviewData(): array
    {
        $file = $this->getFileProperty();

        $collection = Excel::toCollection(null, $file)->first();

        if (!$collection) {
            return [];
        }

        return $collection
            ->skip($this->getStartRow() - 1) // Skip header row
            ->take($this->maxPreviewRows)
            ->map(function ($row, $index) {
                return [
                    'row_number' => $index + $this->getStartRow(),
                    'data' => $row->toArray(),
                    'raw_data' => $row
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * Validate preview data rows
     */
    protected function validatePreviewData(array &$previewData): void
    {
        $this->previewErrors = [];

        foreach ($previewData as &$row) {
            try {
                $validationResult = $this->validatePreviewRow($row['data'], $row['row_number']);
                $row['validation'] = $validationResult;

                if (!$validationResult['valid']) {
                    $this->previewErrors[] = [
                        'row' => $row['row_number'],
                        'dangers' => $validationResult['dangers'],
                        'warnings' => $validationResult['warnings'] ?? []
                    ];
                }
            } catch (\Exception $e) {
                $row['validation'] = [
                    'valid' => false,
                    'dangers' => [__('common.row_validation_danger', ['danger' => $e->getMessage()])],
                    'warnings' => []
                ];

                $this->previewErrors[] = [
                    'row' => $row['row_number'],
                    'dangers' => [__('common.row_validation_danger', ['danger' => $e->getMessage()])],
                    'warnings' => []
                ];
            }
        }

        $this->processedRows = count($previewData);
    }

    /**
     * Get total row count in file
     */
    protected function getTotalRowCount(): int
    {
        $file = $this->getFileProperty();

        // Use a more efficient method to get row count
        $collection = Excel::toCollection(null, $file)->first();
        return $collection ? $collection->count() : 0;
    }

    /**
     * Preload any data needed for validation (optimization hook)
     */
    protected function preloadValidationData(): void
    {
        // Default implementation - child classes can override to preload data
        // This helps avoid N+1 queries during validation
    }

    /**
     * Validate a single preview row (should be overridden by implementing class)
     */
    protected function validatePreviewRow(array $rowData, int $rowNumber): array
    {
        // Default implementation - should be overridden
        return [
            'valid' => true,
            'dangers' => [],
            'warnings' => [],
            'parsed_data' => $rowData
        ];
    }

    /**
     * Get the start row for data (should be overridden by implementing class)
     */
    protected function getStartRow(): int
    {
        return 2; // Default: skip header row
    }

    /**
     * Get the uploaded file property (generic implementation)
     */
    protected function getFileProperty()
    {
        // Try to find file property dynamically based on common naming patterns
        $fileProperties = ['employee_file', 'department_file', 'service_file', 'company_file'];

        foreach ($fileProperties as $property) {
            if (property_exists($this, $property) && $this->{$property}) {
                return $this->{$property};
            }
        }

        return null;
    }

    /**
     * Cache preview data
     */
    protected function cachePreviewData(): void
    {
        Cache::put($this->previewCacheKey, [
            'previewData' => $this->previewData,
            'previewErrors' => $this->previewErrors,
            'totalRows' => $this->totalRows,
            'processedRows' => $this->processedRows,
            'hasLargeFile' => $this->hasLargeFile,
            'file_info' => [
                'name' => $this->getFileProperty()?->getClientOriginalName(),
                'size' => $this->getFileProperty()?->getSize(),
            ],
            'timestamp' => now()
        ], now()->addMinutes($this->previewCacheTtl));
    }

    /**
     * Load cached preview data
     */
    protected function loadCachedPreview(): void
    {
        $cached = Cache::get($this->previewCacheKey);

        if ($cached && $this->isValidCache($cached)) {
            $this->previewData = $cached['previewData'] ?? [];
            $this->previewErrors = $cached['previewErrors'] ?? [];
            $this->totalRows = $cached['totalRows'] ?? 0;
            $this->processedRows = $cached['processedRows'] ?? 0;
            $this->hasLargeFile = $cached['hasLargeFile'] ?? false;
        }
    }

    /**
     * Check if cached data is still valid
     */
    protected function isValidCache(array $cached): bool
    {
        // Check if cache is not too old (within last 10 minutes for better UX)
        return isset($cached['timestamp']) &&
               now()->diffInMinutes($cached['timestamp']) < 10;
    }

    /**
     * Clear preview data
     */
    public function clearPreview(): void
    {
        $this->previewData = [];
        $this->previewErrors = [];
        $this->showPreview = false;
        $this->totalRows = 0;
        $this->processedRows = 0;
        $this->hasLargeFile = false;
        $this->fileAnalysisWarning = '';
        $this->isProcessingPreview = false;
        $this->processingStep = '';
        $this->processingProgress = 0;

        Cache::forget($this->previewCacheKey);
    }

    /**
     * Get preview summary stats
     */
    public function getPreviewStats(): array
    {
        $validRows = count(array_filter($this->previewData, fn($row) => $row['validation']['valid'] ?? false));
        $dangerRows = count($this->previewErrors);

        return [
            'total_preview_rows' => $this->processedRows,
            'valid_rows' => $validRows,
            'danger_rows' => $dangerRows,
            'total_file_rows' => $this->totalRows,
            'has_large_file' => $this->hasLargeFile
        ];
    }

    /**
     * Check if preview can proceed with import
     */
    public function canProceedWithImport(): bool
    {
        return !empty($this->previewData) && count($this->previewErrors) === 0;
    }

    /**
     * Close preview modal
     */
    public function closePreview(): void
    {
        $this->showPreview = false;

        // Dispatch event to hide modal
        $this->dispatch('showPreviewChanged', false);

        // Dispatch event to reopen the import modal
        $this->dispatch('reopen-import-modal');
    }

    /**
     * Navigate to preview step
     */
    public function goToPreview(): void
    {
        // If we already have preview data, just change the step without reprocessing
        if (!empty($this->previewData) || $this->currentStep === 'confirm') {
            $this->currentStep = 'preview';
            $this->showPreview = true;
            return;
        }

        try {
            // Validate file headers before processing
            $this->validateFileHeaders();

            // Process preview if headers are valid
            $this->processPreview();
            $this->currentStep = 'preview';
        } catch (\Exception $e) {
            Log::danger('Import field validation failed', [
                'danger' => $e->getMessage(),
                'user_id' => auth()->id(),
                'file' => $this->getFileProperty()
            ]);

            $this->dispatch("showToast", message: __('common.field_validation_danger', [
                'danger' => $e->getMessage()
            ]), type: "danger");
        }
    }

    /**
     * Validate file headers/columns before allowing preview
     */
    protected function validateFileHeaders(): void
    {
        $file = $this->getFileProperty();

        if (!$file) {
            throw new \Exception(__('common.no_file_selected'));
        }

        try {
            // Read the first row (headers) from the file
            $headers = Excel::toCollection(null, $file)->first()->first();

            if (!$headers) {
                throw new \Exception(__('common.no_headers_found'));
            }

            // Convert headers to array and clean them
            $fileHeaders = array_map('trim', array_map('strtolower', $headers->toArray()));

            // Get expected columns from implementing class
            $expectedColumns = $this->getExpectedColumns();

            // Check for missing required columns
            $missingColumns = array_diff($expectedColumns, $fileHeaders);

            if (!empty($missingColumns)) {
                throw new \Exception(__('common.missing_required_columns', [
                    'columns' => implode(', ', array_map('ucfirst', $missingColumns))
                ]));
            }

            // Check for extra columns (optional - could be a warning)
            $extraColumns = array_diff($fileHeaders, $expectedColumns);
            if (!empty($extraColumns)) {
                // Log as warning but don't fail
                Log::warning('Extra columns found in import file', [
                    'extra_columns' => $extraColumns,
                    'user_id' => auth()->id()
                ]);
            }

        } catch (\Exception $e) {
            if ($e instanceof \Exception && str_contains($e->getMessage(), 'missing_required_columns')) {
                throw $e; // Re-throw our custom validation dangers
            }

            // Handle Excel reading dangers
            throw new \Exception(__('common.invalid_file_format_or_corrupted'));
        }
    }

    /**
     * Get expected columns for validation (should be overridden by implementing class)
     */
    protected function getExpectedColumns(): array
    {
        return []; // Implementing classes should override this
    }

    /**
     * Navigate back to upload step
     */
    public function goToUpload(): void
    {
        $this->currentStep = 'upload';
    }

    /**
     * Navigate to confirm step
     */
    public function goToConfirm(): void
    {
        $this->currentStep = 'confirm';
    }

    /**
     * Reset to upload step (used after successful import)
     */
    public function resetToUpload(): void
    {
        $this->currentStep = 'upload';
        $this->clearPreview();
    }

    /**
     * Get validation danger summary
     */
    public function getValidationSummary(): string
    {
        $stats = $this->getPreviewStats();

        if ($stats['danger_rows'] === 0) {
            return __('common.preview_all_valid');
        }

        return __('common.preview_validation_summary', [
            'valid' => $stats['valid_rows'],
            'dangers' => $stats['danger_rows'],
            'total' => $stats['total_preview_rows']
        ]);
    }
}