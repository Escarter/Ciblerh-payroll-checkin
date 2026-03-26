<?php

namespace App\Imports\Adapters;

use App\Models\Holiday;
use App\Models\Company;
use Carbon\Carbon;

class HolidayImportAdapter extends BaseImportAdapter
{
    public function getEntityName(): string
    {
        return __('holidays.holidays');
    }

    public function getEntitySlug(): string
    {
        return 'holidays';
    }

    public function getUniqueKeys(): array
    {
        return ['date', 'company_id'];
    }

    public function getFieldDefinitions(): array
    {
        return [
            [
                'field' => 'date',
                'label' => __('holidays.date'),
                'required' => true,
                'type' => 'date',
                'aliases' => ['holiday_date', 'date_holiday', 'jour', 'date_vacance', 'holiday_date_string'],
            ],
            [
                'field' => 'name',
                'label' => __('common.name'),
                'required' => true,
                'type' => 'string',
                'aliases' => ['holiday_name', 'holiday', 'nom', 'nom_vacance', 'libelle', 'title'],
            ],
            [
                'field' => 'description',
                'label' => __('common.description'),
                'required' => false,
                'type' => 'string',
                'aliases' => ['desc', 'details', 'notes', 'description_holiday', 'remarks'],
            ],
            [
                'field' => 'company_id',
                'label' => __('companies.company'),
                'required' => false,
                'type' => 'relationship',
                'lookup_model' => Company::class,
                'lookup_fields' => ['name', 'code'],
                'aliases' => ['company', 'societe', 'entreprise', 'company_name'],
            ],
        ];
    }

    public function validateRow(array $row, int $rowNumber): array
    {
        $errors = [];

        // Validate date
        $dateValue = $row['date'] ?? null;
        if (empty($dateValue)) {
            $errors[] = [
                'field' => 'date',
                'message' => __('validation.required', ['attribute' => __('holidays.date')]),
            ];
        } else {
            try {
                $parsed = Carbon::parse($dateValue);
                if ($parsed === false) {
                    throw new \Exception('Invalid date');
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'field' => 'date',
                    'message' => __('validation.date_format', [
                        'attribute' => __('holidays.date'),
                        'format' => 'Y-m-d',
                    ]),
                ];
            }
        }

        // Validate name
        $name = $this->cleanValue($row['name'] ?? null);
        if (empty($name)) {
            $errors[] = [
                'field' => 'name',
                'message' => __('validation.required', ['attribute' => __('common.name')]),
            ];
        }

        return $errors;
    }

    public function validateRelationships(array $row, int $rowNumber): array
    {
        $errors = [];

        // Validate company_id if provided
        if (!empty($row['company_id'])) {
            $companyId = $row['company_id'];
            
            if (!is_numeric($companyId) || !Company::where('id', $companyId)->exists()) {
                $errors[] = [
                    'field' => 'company_id',
                    'message' => __('validation.exists', ['attribute' => __('companies.company')]),
                ];
            }
        }

        return $errors;
    }

    public function transformRow(array $row): array
    {
        $parsed_date = null;

        // Parse date
        try {
            $parsed_date = Carbon::parse($row['date'])->format('Y-m-d');
        } catch (\Exception $e) {
            $this->errors[] = [
                'field' => 'date',
                'message' => __('validation.date_format', ['attribute' => __('holidays.date')]),
            ];
            return ['__error' => 'Invalid date format'];
        }

        return [
            'date' => $parsed_date,
            'name' => $this->cleanValue($row['name']),
            'description' => $this->cleanValue($row['description'] ?? null) ?? null,
            'company_id' => !empty($row['company_id']) ? (int) $row['company_id'] : null,
        ];
    }

    public function createOrUpdateRecord(array $data)
    {
        // Build lookup criteria
        $lookupBy = [
            'date' => $data['date'],
            'company_id' => $data['company_id'],
        ];

        if ($this->importMode === self::MODE_CREATE_ONLY) {
            $existing = Holiday::where($lookupBy)->first();
            if ($existing) {
                $this->stats['skipped']++;
                return null;
            }
            $holiday = Holiday::create($data);
            $holiday->wasRecentlyCreated = true;
            $this->stats['created']++;
            return $holiday;
        }

        if ($this->importMode === self::MODE_UPDATE_ONLY) {
            $existing = Holiday::where($lookupBy)->first();
            if (!$existing) {
                $this->stats['skipped']++;
                return null;
            }
            $existing->update($data);
            $this->stats['updated']++;
            return $existing;
        }

        // UPSERT mode
        $result = Holiday::updateOrCreate($lookupBy, $data);
        if ($result->wasRecentlyCreated) {
            $this->stats['created']++;
        } else {
            $this->stats['updated']++;
        }
        return $result;
    }
}
