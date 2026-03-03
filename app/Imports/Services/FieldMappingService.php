<?php

namespace App\Imports\Services;

use App\Imports\Adapters\BaseImportAdapter;

class FieldMappingService
{
    /**
     * Levenshtein distance threshold as a fraction of string length.
     * A match is accepted when distance ≤ ceil(max_len * threshold).
     */
    protected float $levenshteinThreshold = 0.30;

    /**
     * Auto-map CSV headers to field definitions.
     *
     * Returns an array of suggested mappings:
     *   csv_index => ['csv_header' => '…', 'field' => '…', 'confidence' => 'exact'|'label'|'contains'|'fuzzy'|null]
     *
     * @param  array              $csvHeaders  Indexed array of header strings from row 1 of the CSV
     * @param  BaseImportAdapter  $adapter     The adapter whose getFieldDefinitions() to match against
     * @return array
     */
    public function autoMap(array $csvHeaders, BaseImportAdapter $adapter): array
    {
        $definitions = $adapter->getFieldDefinitions();
        $mappings = [];
        $usedFields = []; // Track which fields have been claimed

        // Build a lookup of all matchable terms per field definition
        $fieldIndex = $this->buildFieldIndex($definitions);

        foreach ($csvHeaders as $csvIdx => $header) {
            $normalizedHeader = $this->normalize($header);
            $bestMatch = null;
            $bestConfidence = null;

            // Level 1: Exact match on field name
            foreach ($fieldIndex as $fieldName => $terms) {
                if (isset($usedFields[$fieldName])) {
                    continue;
                }
                if ($normalizedHeader === $this->normalize($fieldName)) {
                    $bestMatch = $fieldName;
                    $bestConfidence = 'exact';
                    break;
                }
            }

            // Level 2: Exact match on label
            if (!$bestMatch) {
                foreach ($fieldIndex as $fieldName => $terms) {
                    if (isset($usedFields[$fieldName])) {
                        continue;
                    }
                    if ($normalizedHeader === $this->normalize($terms['label'])) {
                        $bestMatch = $fieldName;
                        $bestConfidence = 'label';
                        break;
                    }
                }
            }

            // Level 3: Exact match on aliases
            if (!$bestMatch) {
                foreach ($fieldIndex as $fieldName => $terms) {
                    if (isset($usedFields[$fieldName])) {
                        continue;
                    }
                    foreach ($terms['aliases'] as $alias) {
                        if ($normalizedHeader === $this->normalize($alias)) {
                            $bestMatch = $fieldName;
                            $bestConfidence = 'alias';
                            break 2;
                        }
                    }
                }
            }

            // Level 4: Contains match on field name or label
            if (!$bestMatch) {
                foreach ($fieldIndex as $fieldName => $terms) {
                    if (isset($usedFields[$fieldName])) {
                        continue;
                    }
                    $normField = $this->normalize($fieldName);
                    $normLabel = $this->normalize($terms['label']);

                    if (
                        (strlen($normField) >= 3 && str_contains($normalizedHeader, $normField)) ||
                        (strlen($normLabel) >= 3 && str_contains($normalizedHeader, $normLabel)) ||
                        (strlen($normalizedHeader) >= 3 && str_contains($normField, $normalizedHeader)) ||
                        (strlen($normalizedHeader) >= 3 && str_contains($normLabel, $normalizedHeader))
                    ) {
                        $bestMatch = $fieldName;
                        $bestConfidence = 'contains';
                        break;
                    }
                }
            }

            // Level 5: Levenshtein distance
            if (!$bestMatch) {
                $minDistance = PHP_INT_MAX;
                foreach ($fieldIndex as $fieldName => $terms) {
                    if (isset($usedFields[$fieldName])) {
                        continue;
                    }
                    $candidates = array_merge(
                        [$this->normalize($fieldName), $this->normalize($terms['label'])],
                        array_map([$this, 'normalize'], $terms['aliases'])
                    );

                    foreach ($candidates as $candidate) {
                        if (empty($candidate)) {
                            continue;
                        }
                        $maxLen = max(strlen($normalizedHeader), strlen($candidate));
                        if ($maxLen === 0) {
                            continue;
                        }
                        $distance = levenshtein($normalizedHeader, $candidate);
                        $threshold = ceil($maxLen * $this->levenshteinThreshold);

                        if ($distance <= $threshold && $distance < $minDistance) {
                            $minDistance = $distance;
                            $bestMatch = $fieldName;
                            $bestConfidence = 'fuzzy';
                        }
                    }
                }
            }

            $mappings[$csvIdx] = [
                'csv_header' => $header,
                'field' => $bestMatch,
                'confidence' => $bestConfidence,
            ];

            if ($bestMatch) {
                $usedFields[$bestMatch] = true;
            }
        }

        return $mappings;
    }

    /**
     * Apply a confirmed mapping to a row of raw CSV data.
     *
     * @param  array  $rawRow     Indexed array of CSV column values
     * @param  array  $mapping    csv_index => field_name (user-confirmed mapping)
     * @return array  Associative array keyed by field name
     */
    public function applyMapping(array $rawRow, array $mapping): array
    {
        $mapped = [];
        foreach ($mapping as $csvIdx => $fieldName) {
            if ($fieldName !== null && $fieldName !== '' && $fieldName !== '-') {
                $mapped[$fieldName] = $rawRow[$csvIdx] ?? null;
            }
        }
        return $mapped;
    }

    /**
     * Get unmapped required fields (fields that weren't matched).
     *
     * @param  array              $mappings     Result of autoMap()
     * @param  BaseImportAdapter  $adapter
     * @return array  Array of field definition arrays that are required but unmapped
     */
    public function getUnmappedRequiredFields(array $mappings, BaseImportAdapter $adapter): array
    {
        $mappedFields = collect($mappings)->pluck('field')->filter()->toArray();
        $unmapped = [];

        foreach ($adapter->getFieldDefinitions() as $def) {
            if (($def['required'] ?? false) && !in_array($def['field'], $mappedFields)) {
                $unmapped[] = $def;
            }
        }

        return $unmapped;
    }

    // ─── Private Helpers ───────────────────────────────────────────────

    protected function buildFieldIndex(array $definitions): array
    {
        $index = [];
        foreach ($definitions as $def) {
            $field = $def['field'];
            $index[$field] = [
                'label' => $def['label'] ?? $field,
                'aliases' => $def['aliases'] ?? [],
            ];
        }
        return $index;
    }

    /**
     * Normalize a string for comparison: lowercase, strip non-alphanumeric.
     */
    protected function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        // Replace underscores, hyphens, spaces with nothing
        $value = preg_replace('/[\s_\-]+/', '', $value);
        return $value;
    }
}
