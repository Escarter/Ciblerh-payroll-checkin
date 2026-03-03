<?php

namespace App\Imports\Services;

use App\Imports\Adapters\BaseImportAdapter;
use App\Models\ImportJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportService
{
    protected AdapterRegistry $registry;
    protected FieldMappingService $fieldMapper;

    public function __construct(AdapterRegistry $registry, FieldMappingService $fieldMapper)
    {
        $this->registry = $registry;
        $this->fieldMapper = $fieldMapper;
    }

    // ─── 1. Parse CSV ──────────────────────────────────────────────────

    /**
     * Parse the uploaded file and return raw rows + headers.
     *
     * @param  string  $filePath  Path relative to storage disk
     * @param  string  $disk
     * @return array   ['headers' => [...], 'rows' => [[...], ...], 'total_rows' => int]
     */
    public function parseFile(string $filePath, string $disk = 'local'): array
    {
        $fullPath = Storage::disk($disk)->path($filePath);

        $collection = Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray {
            public function array(array $array): array
            {
                return $array;
            }
        }, $fullPath);

        // Excel returns nested arrays: sheets → rows
        $sheet = $collection[0] ?? [];

        if (empty($sheet)) {
            return ['headers' => [], 'rows' => [], 'total_rows' => 0];
        }

        $headers = array_map('trim', $sheet[0]);
        $rows = array_slice($sheet, 1);

        // Filter out completely empty rows
        $rows = array_filter($rows, function ($row) {
            return !empty(array_filter($row, fn($v) => $v !== null && $v !== ''));
        });

        return [
            'headers' => $headers,
            'rows' => array_values($rows), // Re-index
            'total_rows' => count($rows),
        ];
    }

    // ─── 2. Auto-Map Fields ────────────────────────────────────────────

    /**
     * Given CSV headers and an entity slug, produce auto-mapping suggestions.
     */
    public function autoMapFields(array $csvHeaders, string $entitySlug): array
    {
        $adapter = $this->registry->freshAdapter($entitySlug);
        if (!$adapter) {
            throw new \InvalidArgumentException("Unknown entity: {$entitySlug}");
        }

        return $this->fieldMapper->autoMap($csvHeaders, $adapter);
    }

    /**
     * Get unmapped required fields for user guidance.
     */
    public function getUnmappedRequired(array $mappings, string $entitySlug): array
    {
        $adapter = $this->registry->freshAdapter($entitySlug);
        if (!$adapter) {
            return [];
        }
        return $this->fieldMapper->getUnmappedRequiredFields($mappings, $adapter);
    }

    // ─── 3. Preview ────────────────────────────────────────────────────

    /**
     * Generate a preview of the first N rows with field mapping applied.
     *
     * @param  array   $rawRows   Indexed arrays from parseFile
     * @param  array   $mapping   csv_index => field_name (confirmed mapping)
     * @param  string  $entitySlug
     * @param  array   $context   Import context (company_id, department_id, etc.)
     * @param  int     $limit     Max rows to preview
     * @return array   ['rows' => [...], 'errors' => [...], 'warnings' => [...]]
     */
    public function preview(array $rawRows, array $mapping, string $entitySlug, array $context = [], int $limit = 20): array
    {
        $adapter = $this->registry->freshAdapter($entitySlug);
        if (!$adapter) {
            throw new \InvalidArgumentException("Unknown entity: {$entitySlug}");
        }

        $adapter->setContext($context);

        $previewRows = array_slice($rawRows, 0, $limit);
        $preview = [];
        $errors = [];
        $warnings = [];

        foreach ($previewRows as $index => $rawRow) {
            $rowNumber = $index + 2;
            $mappedRow = $this->fieldMapper->applyMapping($rawRow, $mapping);

            // Validate
            $rowErrors = $adapter->validateRow($mappedRow, $rowNumber);
            $relErrors = $adapter->validateRelationships($mappedRow, $rowNumber);
            $allErrors = array_merge($rowErrors, $relErrors);

            $preview[] = [
                'row_number' => $rowNumber,
                'data' => $mappedRow,
                'valid' => empty($allErrors),
                'errors' => $allErrors,
            ];

            foreach ($allErrors as $err) {
                $errors[] = [
                    'row' => $rowNumber,
                    'field' => $err['field'] ?? null,
                    'message' => $err['message'] ?? $err,
                ];
            }
        }

        return [
            'rows' => $preview,
            'errors' => $errors,
            'warnings' => $warnings,
            'total_preview' => count($preview),
            'total_valid' => count(array_filter($preview, fn($r) => $r['valid'])),
            'total_errors' => count(array_filter($preview, fn($r) => !$r['valid'])),
        ];
    }

    // ─── 4. Full Validation ────────────────────────────────────────────

    /**
     * Validate the entire file (batch + row + relationship).
     */
    public function validateAll(array $rawRows, array $mapping, string $entitySlug, array $context = []): array
    {
        $adapter = $this->registry->freshAdapter($entitySlug);
        if (!$adapter) {
            throw new \InvalidArgumentException("Unknown entity: {$entitySlug}");
        }

        $adapter->setContext($context);

        // Map all rows
        $mappedRows = [];
        foreach ($rawRows as $rawRow) {
            $mappedRows[] = $this->fieldMapper->applyMapping($rawRow, $mapping);
        }

        return $adapter->validateBatch($mappedRows);
    }

    // ─── 5. Execute Import ─────────────────────────────────────────────

    /**
     * Execute the full import.
     *
     * @param  array        $rawRows
     * @param  array        $mapping     csv_index => field_name
     * @param  string       $entitySlug
     * @param  array        $context
     * @param  string       $importMode  create_only|update_only|upsert
     * @param  bool         $autoCreate
     * @param  mixed        $user
     * @param  ImportJob|null $importJob  Optional: track progress on a model
     * @param  callable|null $onProgress
     * @return array        ['stats' => [...], 'errors' => [...], 'warnings' => [...]]
     */
    public function execute(
        array $rawRows,
        array $mapping,
        string $entitySlug,
        array $context = [],
        string $importMode = BaseImportAdapter::MODE_CREATE_ONLY,
        bool $autoCreate = false,
        $user = null,
        ?ImportJob $importJob = null,
        ?callable $onProgress = null
    ): array {
        $adapter = $this->registry->freshAdapter($entitySlug);
        if (!$adapter) {
            throw new \InvalidArgumentException("Unknown entity: {$entitySlug}");
        }

        $adapter->setImportMode($importMode)
                ->setAutoCreateEntities($autoCreate)
                ->setUser($user)
                ->setContext($context);

        // Map all rows
        $mappedRows = [];
        foreach ($rawRows as $rawRow) {
            $mappedRows[] = $this->fieldMapper->applyMapping($rawRow, $mapping);
        }

        // Update ImportJob status
        if ($importJob) {
            $importJob->update([
                'status' => ImportJob::STATUS_PROCESSING,
                'total_rows' => count($mappedRows),
                'started_at' => now(),
            ]);
        }

        // Process in batches within a transaction
        $batchSize = 100;
        $chunks = array_chunk($mappedRows, $batchSize, true);
        $allErrors = [];
        $allWarnings = [];
        $totalStats = [
            'total' => count($mappedRows),
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        $processedSoFar = 0;

        foreach ($chunks as $chunkIndex => $chunk) {
            try {
                DB::beginTransaction();

                $result = $adapter->processBatch($chunk, function ($processed, $chunkTotal) use (&$processedSoFar, $totalStats, $importJob, $onProgress) {
                    $current = $processedSoFar + $processed;
                    if ($importJob && $processed % 10 === 0) {
                        $importJob->update(['processed_rows' => $current]);
                    }
                    if ($onProgress) {
                        $onProgress($current, $totalStats['total']);
                    }
                });

                DB::commit();

                // Accumulate stats
                $totalStats['created'] += $result['stats']['created'];
                $totalStats['updated'] += $result['stats']['updated'];
                $totalStats['skipped'] += $result['stats']['skipped'];
                $totalStats['errors'] += $result['stats']['errors'];
                $allErrors = array_merge($allErrors, $result['errors']);
                $allWarnings = array_merge($allWarnings, $result['warnings']);

                $processedSoFar += count($chunk);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Import batch {$chunkIndex} failed", [
                    'entity' => $entitySlug,
                    'error' => $e->getMessage(),
                ]);

                // Count remaining chunk as errors
                $totalStats['errors'] += count($chunk);
                $allErrors[] = [
                    'row' => null,
                    'field' => null,
                    'message' => __('import.batch_failed', [
                        'batch' => $chunkIndex + 1,
                        'error' => $e->getMessage(),
                    ]),
                ];
                $processedSoFar += count($chunk);
            }
        }

        // Update ImportJob with final results
        if ($importJob) {
            $importJob->update([
                'status' => $totalStats['errors'] > 0 && $totalStats['created'] === 0 && $totalStats['updated'] === 0
                    ? ImportJob::STATUS_FAILED
                    : ImportJob::STATUS_COMPLETED,
                'processed_rows' => $processedSoFar,
                'successful_imports' => $totalStats['created'] + $totalStats['updated'],
                'failed_imports' => $totalStats['errors'],
                'error_details' => !empty($allErrors) ? $allErrors : null,
                'completed_at' => now(),
                'import_config' => array_merge($importJob->import_config ?? [], [
                    'import_mode' => $importMode,
                    'auto_create_entities' => $autoCreate,
                    'field_mapping' => $mapping,
                    'entity_type' => $entitySlug,
                ]),
            ]);
        }

        return [
            'stats' => $totalStats,
            'errors' => $allErrors,
            'warnings' => $allWarnings,
        ];
    }

    // ─── Template Download ─────────────────────────────────────────────

    /**
     * Generate a CSV template for a given entity.
     *
     * @param  string  $entitySlug
     * @return array   ['headers' => [...], 'sample' => [...], 'filename' => '...']
     */
    public function getTemplate(string $entitySlug): array
    {
        $adapter = $this->registry->freshAdapter($entitySlug);
        if (!$adapter) {
            throw new \InvalidArgumentException("Unknown entity: {$entitySlug}");
        }

        return [
            'headers' => $adapter->getTemplateHeaders(),
            'sample' => $adapter->getSampleRow(),
            'filename' => "import_{$entitySlug}_template.csv",
        ];
    }
}
