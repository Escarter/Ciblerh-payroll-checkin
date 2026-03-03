<?php

namespace App\Imports\Adapters;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

abstract class BaseImportAdapter
{
    /**
     * Import mode constants
     */
    const MODE_CREATE_ONLY = 'create_only';
    const MODE_UPDATE_ONLY = 'update_only';
    const MODE_UPSERT = 'upsert';

    /**
     * Current import mode
     */
    protected string $importMode = self::MODE_CREATE_ONLY;

    /**
     * Whether to auto-create missing related entities
     */
    protected bool $autoCreateEntities = false;

    /**
     * The authenticated user performing the import
     */
    protected $user = null;

    /**
     * Context scope (company, department, etc.) passed from the UI
     */
    protected array $context = [];

    /**
     * Collected errors during processing
     */
    protected array $errors = [];

    /**
     * Collected warnings during processing
     */
    protected array $warnings = [];

    /**
     * Import statistics
     */
    protected array $stats = [
        'total' => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
    ];

    // ─── Abstract Methods ──────────────────────────────────────────────

    /**
     * Return the human-readable entity name (e.g., 'Employee', 'Department')
     */
    abstract public function getEntityName(): string;

    /**
     * Return the entity slug used in import_type column (e.g., 'employees', 'departments')
     */
    abstract public function getEntitySlug(): string;

    /**
     * Return field definitions for this entity.
     *
     * Each definition is an array:
     *   [
     *     'field'        => 'department_id',        // DB column name
     *     'label'        => 'Department',            // Human-readable label
     *     'required'     => true,
     *     'type'         => 'relationship',          // string|numeric|date|time|boolean|email|phone|relationship|enum
     *     'lookup_fields'=> ['code','name'],         // For relationships: columns to search on the related model
     *     'lookup_model' => \App\Models\Department::class,
     *     'lookup_scope' => ['company_id'],          // Scope columns (resolved from context/row)
     *     'aliases'      => ['dept','department_name','dept_name'], // Alternative header names for auto-mapping
     *     'enum_values'  => [],                      // For enum types
     *     'default'      => null,
     *   ]
     */
    abstract public function getFieldDefinitions(): array;

    /**
     * Transform a mapped row into data ready for DB insertion.
     * Resolve all FK relationships here.
     *
     * @param  array  $row  Associative array keyed by DB field name
     * @return array  Transformed data or ['__error' => 'message'] on failure
     */
    abstract public function transformRow(array $row): array;

    /**
     * Validate a single row (field-level validation).
     *
     * @param  array  $row  Associative array keyed by DB field name
     * @param  int    $rowNumber
     * @return array  Array of error strings (empty = valid)
     */
    abstract public function validateRow(array $row, int $rowNumber): array;

    /**
     * Validate relationships for a single row.
     * Check that all FK references exist and are consistent (transitive integrity).
     *
     * @param  array  $row  Associative array keyed by DB field name
     * @param  int    $rowNumber
     * @return array  Array of error strings (empty = valid)
     */
    abstract public function validateRelationships(array $row, int $rowNumber): array;

    /**
     * Create or update a record based on import mode.
     *
     * @param  array  $data  Validated & transformed data
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    abstract public function createOrUpdateRecord(array $data);

    // ─── Template Methods ──────────────────────────────────────────────

    /**
     * Get the unique key field(s) used for duplicate detection.
     * Return an array of field names. For composite keys, return multiple.
     *
     * @return array e.g. ['email'] or ['name', 'company_id']
     */
    abstract public function getUniqueKeys(): array;

    /**
     * Return CSV template headers for download.
     */
    public function getTemplateHeaders(): array
    {
        return collect($this->getFieldDefinitions())
            ->pluck('label')
            ->toArray();
    }

    /**
     * Return a sample row for the CSV template.
     */
    public function getSampleRow(): array
    {
        $sample = [];
        foreach ($this->getFieldDefinitions() as $def) {
            $sample[] = $this->getSampleValue($def);
        }
        return $sample;
    }

    // ─── Configuration ─────────────────────────────────────────────────

    public function setImportMode(string $mode): self
    {
        if (!in_array($mode, [self::MODE_CREATE_ONLY, self::MODE_UPDATE_ONLY, self::MODE_UPSERT])) {
            throw new \InvalidArgumentException("Invalid import mode: {$mode}");
        }
        $this->importMode = $mode;
        return $this;
    }

    public function getImportMode(): string
    {
        return $this->importMode;
    }

    public function setAutoCreateEntities(bool $autoCreate): self
    {
        $this->autoCreateEntities = $autoCreate;
        return $this;
    }

    public function getAutoCreateEntities(): bool
    {
        return $this->autoCreateEntities;
    }

    public function setUser($user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    // ─── Batch Validation ──────────────────────────────────────────────

    /**
     * Validate the entire batch before any DB writes.
     * Checks for within-file duplicates and required fields.
     *
     * @param  array  $rows  Array of mapped rows (associative arrays)
     * @return array  ['valid' => bool, 'errors' => [...]]
     */
    public function validateBatch(array $rows): array
    {
        $errors = [];
        $uniqueKeys = $this->getUniqueKeys();
        $seenValues = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 because row 1 is header

            // Check within-file duplicates
            $keyValue = $this->extractUniqueKeyValue($row, $uniqueKeys);
            if ($keyValue !== null) {
                $keyString = is_array($keyValue) ? implode('|', $keyValue) : (string) $keyValue;
                if (isset($seenValues[$keyString])) {
                    $errors[] = [
                        'row' => $rowNumber,
                        'field' => implode(', ', $uniqueKeys),
                        'message' => __('import.duplicate_in_file', [
                            'key' => $keyString,
                            'first_row' => $seenValues[$keyString],
                        ]),
                    ];
                } else {
                    $seenValues[$keyString] = $rowNumber;
                }
            }

            // Field-level validation
            $rowErrors = $this->validateRow($row, $rowNumber);
            foreach ($rowErrors as $error) {
                $errors[] = [
                    'row' => $rowNumber,
                    'field' => $error['field'] ?? null,
                    'message' => $error['message'] ?? $error,
                ];
            }

            // Relationship validation
            $relErrors = $this->validateRelationships($row, $rowNumber);
            foreach ($relErrors as $error) {
                $errors[] = [
                    'row' => $rowNumber,
                    'field' => $error['field'] ?? null,
                    'message' => $error['message'] ?? $error,
                ];
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    // ─── Import Execution ──────────────────────────────────────────────

    /**
     * Process a batch of rows: validate → transform → create/update.
     *
     * @param  array     $rows       Array of mapped rows
     * @param  callable  $onProgress Optional callback: fn(int $processed, int $total)
     * @return array     Stats + errors
     */
    public function processBatch(array $rows, ?callable $onProgress = null): array
    {
        $this->resetStats();
        $this->errors = [];
        $this->warnings = [];
        $total = count($rows);
        $this->stats['total'] = $total;

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            try {
                // 1. Validate
                $rowErrors = $this->validateRow($row, $rowNumber);
                $relErrors = $this->validateRelationships($row, $rowNumber);
                $allErrors = array_merge($rowErrors, $relErrors);

                if (!empty($allErrors)) {
                    $this->stats['errors']++;
                    foreach ($allErrors as $err) {
                        $this->errors[] = [
                            'row' => $rowNumber,
                            'field' => $err['field'] ?? null,
                            'message' => $err['message'] ?? $err,
                        ];
                    }
                    continue;
                }

                // 2. Transform
                $transformed = $this->transformRow($row);
                if (isset($transformed['__error'])) {
                    $this->stats['errors']++;
                    $this->errors[] = [
                        'row' => $rowNumber,
                        'field' => null,
                        'message' => $transformed['__error'],
                    ];
                    continue;
                }

                if (isset($transformed['__warning'])) {
                    $this->warnings[] = [
                        'row' => $rowNumber,
                        'message' => $transformed['__warning'],
                    ];
                    unset($transformed['__warning']);
                }

                // 3. Create or update
                $result = $this->createOrUpdateRecord($transformed);

                if ($result === null) {
                    $this->stats['skipped']++;
                } elseif ($result->wasRecentlyCreated ?? false) {
                    $this->stats['created']++;
                } else {
                    $this->stats['updated']++;
                }
            } catch (\Exception $e) {
                $this->stats['errors']++;
                $this->errors[] = [
                    'row' => $rowNumber,
                    'field' => null,
                    'message' => $e->getMessage(),
                ];
                Log::error("Import row {$rowNumber} failed", [
                    'entity' => $this->getEntitySlug(),
                    'error' => $e->getMessage(),
                ]);
            }

            if ($onProgress) {
                $onProgress($index + 1, $total);
            }
        }

        return [
            'stats' => $this->stats,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }

    // ─── Utility: FK Resolution ────────────────────────────────────────

    /**
     * Cascading FK resolution: try code → name → ID on a model.
     *
     * @param  string  $modelClass   e.g. \App\Models\Department::class
     * @param  mixed   $value        The value from the CSV cell
     * @param  array   $lookupFields Ordered fields to search: ['code','name']
     * @param  array   $scope        Additional where clauses: ['company_id' => 5]
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveFK(string $modelClass, $value, array $lookupFields = ['code', 'name'], array $scope = [])
    {
        if (empty($value) && $value !== 0 && $value !== '0') {
            return null;
        }

        $value = is_string($value) ? trim($value) : $value;

        $query = $modelClass::query();
        foreach ($scope as $col => $val) {
            $query->where($col, $val);
        }

        // Try by ID first (if numeric)
        if (is_numeric($value)) {
            $record = (clone $query)->where('id', $value)->first();
            if ($record) {
                return $record;
            }
        }

        // Try each lookup field (code, name, etc.)
        foreach ($lookupFields as $field) {
            if (!\Illuminate\Support\Facades\Schema::hasColumn((new $modelClass)->getTable(), $field)) {
                continue;
            }
            $record = (clone $query)->where($field, $value)->first();
            if ($record) {
                return $record;
            }
            // Case-insensitive fallback
            $record = (clone $query)->whereRaw("LOWER({$field}) = ?", [strtolower($value)])->first();
            if ($record) {
                return $record;
            }
        }

        return null;
    }

    /**
     * Resolve FK with auto-create fallback.
     *
     * @param  string  $modelClass
     * @param  mixed   $value
     * @param  array   $lookupFields
     * @param  array   $scope
     * @param  array   $createData  Extra data for auto-creation
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function resolveFKOrCreate(string $modelClass, $value, array $lookupFields = ['code', 'name'], array $scope = [], array $createData = [])
    {
        $record = $this->resolveFK($modelClass, $value, $lookupFields, $scope);
        if ($record) {
            return $record;
        }

        if (!$this->autoCreateEntities || empty($value)) {
            return null;
        }

        try {
            // Determine the best field for creation (prefer 'name')
            $nameField = in_array('name', $lookupFields) ? 'name' : $lookupFields[0];
            $data = array_merge(
                $scope,
                [$nameField => trim($value)],
                $createData,
                ['author_id' => $this->user?->id ?? auth()->id()]
            );

            $record = $modelClass::create($data);
            Log::info("Auto-created {$modelClass}", ['data' => $data, 'id' => $record->id]);
            return $record;
        } catch (\Exception $e) {
            Log::warning("Failed to auto-create {$modelClass}", ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Validate transitive relationship integrity.
     *
     * Ensures that a chain like Employee → Service → Department → Company
     * is consistent: the service's department_id matches the resolved department,
     * and the department's company_id matches the resolved company.
     *
     * @param  array  $chain  Ordered chain of ['model' => Model|null, 'parent_fk' => 'company_id', 'label' => 'Department']
     * @return array  Array of error messages (empty = consistent)
     */
    public function validateRelationshipChain(array $chain): array
    {
        $errors = [];

        for ($i = 0; $i < count($chain) - 1; $i++) {
            $child = $chain[$i];
            $parent = $chain[$i + 1];

            if (!$child['model'] || !$parent['model']) {
                continue; // Skip if either is null (optional relationship)
            }

            $parentFK = $child['parent_fk'];
            $childParentId = $child['model']->{$parentFK} ?? null;
            $parentId = $parent['model']->id ?? null;

            if ($childParentId && $parentId && $childParentId != $parentId) {
                $errors[] = [
                    'field' => $parentFK,
                    'message' => __('import.relationship_chain_mismatch', [
                        'child' => $child['label'],
                        'child_name' => $child['model']->name ?? $child['model']->id,
                        'parent' => $parent['label'],
                        'parent_name' => $parent['model']->name ?? $parent['model']->id,
                        'expected_parent' => $parentFK . '=' . $parentId,
                        'actual_parent' => $parentFK . '=' . $childParentId,
                    ]),
                ];
            }
        }

        return $errors;
    }

    // ─── Utility: Value Parsing ────────────────────────────────────────

    /**
     * Clean a raw CSV value: trim whitespace, nullify empty.
     */
    public function cleanValue($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $cleaned = trim((string) $value);
        return $cleaned === '' ? null : $cleaned;
    }

    /**
     * Parse a boolean value from CSV (handles '1', 'yes', 'true', 'oui', etc.)
     */
    public function parseBoolean($value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }
        $val = strtolower(trim((string) $value));
        if (in_array($val, ['1', 'true', 'yes', 'oui', 'y', 'o'])) {
            return true;
        }
        if (in_array($val, ['0', 'false', 'no', 'non', 'n'])) {
            return false;
        }
        return null;
    }

    /**
     * Parse a date from various formats (Excel numeric, d/m/Y, Y-m-d, etc.)
     */
    public function parseDate($value, string $format = 'Y-m-d'): ?string
    {
        if (empty($value)) {
            return null;
        }

        // Already in expected format
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            try {
                return Carbon::createFromFormat('Y-m-d', $value)->format($format);
            } catch (\Exception $e) {
                // fall through
            }
        }

        // Excel numeric date
        if (is_numeric($value)) {
            try {
                return Carbon::instance(
                    \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)
                )->format($format);
            } catch (\Exception $e) {
                // fall through
            }
        }

        // Common date formats
        $formats = ['d/m/Y', 'm/d/Y', 'd-m-Y', 'Y/m/d', 'd.m.Y'];
        foreach ($formats as $fmt) {
            try {
                return Carbon::createFromFormat($fmt, $value)->format($format);
            } catch (\Exception $e) {
                continue;
            }
        }

        // Last resort: Carbon parse
        try {
            return Carbon::parse($value)->format($format);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Parse a time value (handles HH:MM, Excel numeric time).
     */
    public function parseTime($value, ?string $default = null): ?string
    {
        if (empty($value)) {
            return $default;
        }

        if (preg_match('/^\d{1,2}:\d{2}$/', $value)) {
            try {
                return Carbon::createFromFormat('H:i', $value)->format('H:i');
            } catch (\Exception $e) {
                return $default;
            }
        }

        if (is_numeric($value)) {
            try {
                $dateTime = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
                return $dateTime->format('H:i');
            } catch (\Exception $e) {
                return $default;
            }
        }

        return $default;
    }

    /**
     * Parse a numeric value, stripping currency symbols and thousand separators.
     */
    public function parseNumeric($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        // Remove currency symbols, spaces, thousand separators
        $cleaned = preg_replace('/[^\d.,-]/', '', (string) $value);
        // Handle comma as decimal separator (European format)
        if (preg_match('/^\d{1,3}(\.\d{3})*(,\d+)?$/', $cleaned)) {
            $cleaned = str_replace('.', '', $cleaned);
            $cleaned = str_replace(',', '.', $cleaned);
        } else {
            $cleaned = str_replace(',', '', $cleaned);
        }
        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    /**
     * Map a human-readable enum value to its DB constant.
     *
     * @param  mixed  $value
     * @param  array  $mapping  ['active' => 1, 'inactive' => 0, ...]
     * @param  mixed  $default
     * @return mixed
     */
    public function mapEnum($value, array $mapping, $default = null)
    {
        if ($value === null || $value === '') {
            return $default;
        }
        $val = strtolower(trim((string) $value));
        return $mapping[$val] ?? $default;
    }

    /**
     * Validate and format a phone number using the project's helper.
     */
    public function parsePhone($value): ?string
    {
        if (empty($value)) {
            return null;
        }
        $phone = preg_replace('/\s+/', '', (string) $value);
        if (function_exists('validatePhoneNumber')) {
            $result = validatePhoneNumber($phone);
            return $result['valid'] ? $result['formatted'] : null;
        }
        return $phone;
    }

    // ─── Private Helpers ───────────────────────────────────────────────

    protected function extractUniqueKeyValue(array $row, array $keys): mixed
    {
        if (count($keys) === 1) {
            return $row[$keys[0]] ?? null;
        }
        $vals = [];
        foreach ($keys as $k) {
            $vals[] = $row[$k] ?? '';
        }
        return $vals;
    }

    protected function resetStats(): void
    {
        $this->stats = [
            'total' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];
    }

    protected function getSampleValue(array $def): string
    {
        return match ($def['type'] ?? 'string') {
            'email' => 'john.doe@example.com',
            'phone' => '+237612345678',
            'date' => '2025-01-15',
            'time' => '08:00',
            'numeric' => '150000',
            'boolean' => '1',
            'relationship' => 'Department A',
            'enum' => $def['enum_values'][0] ?? '',
            default => 'Sample ' . ($def['label'] ?? $def['field']),
        };
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function getStats(): array
    {
        return $this->stats;
    }
}
