<?php

namespace App\Imports\Adapters;

use App\Models\Company;
use Illuminate\Support\Str;

class CompanyImportAdapter extends BaseImportAdapter
{
    public function getEntityName(): string
    {
        return __('companies.companies');
    }

    public function getEntitySlug(): string
    {
        return 'companies';
    }

    public function getUniqueKeys(): array
    {
        return ['code'];
    }

    public function getFieldDefinitions(): array
    {
        return [
            [
                'field' => 'name',
                'label' => __('companies.name'),
                'required' => true,
                'type' => 'string',
                'aliases' => ['company_name', 'company', 'nom', 'nom_societe', 'raison_sociale'],
            ],
            [
                'field' => 'code',
                'label' => __('companies.code'),
                'required' => false,
                'type' => 'string',
                'aliases' => ['company_code', 'code_societe', 'sigle'],
            ],
            [
                'field' => 'description',
                'label' => __('common.description'),
                'required' => false,
                'type' => 'string',
                'aliases' => ['desc', 'address', 'adresse'],
            ],
            [
                'field' => 'sector',
                'label' => __('companies.sector'),
                'required' => false,
                'type' => 'string',
                'aliases' => ['secteur', 'industry', 'industrie', 'country', 'pays'],
            ],
            [
                'field' => 'is_active',
                'label' => __('common.is_active'),
                'required' => false,
                'type' => 'boolean',
                'aliases' => ['active', 'actif', 'status', 'statut'],
                'default' => true,
            ],
        ];
    }

    public function validateRow(array $row, int $rowNumber): array
    {
        $errors = [];

        $name = $this->cleanValue($row['name'] ?? null);
        if (empty($name)) {
            $errors[] = [
                'field' => 'name',
                'message' => __('validation.required', ['attribute' => __('companies.name')]),
            ];
        } elseif (strlen($name) > 255) {
            $errors[] = [
                'field' => 'name',
                'message' => __('validation.max.string', ['attribute' => __('companies.name'), 'max' => 255]),
            ];
        }

        return $errors;
    }

    public function validateRelationships(array $row, int $rowNumber): array
    {
        // Company is the root entity — no FK relationships to validate
        return [];
    }

    public function transformRow(array $row): array
    {
        $name = $this->cleanValue($row['name'] ?? null);
        $code = $this->cleanValue($row['code'] ?? null);

        return [
            'name' => $name,
            'code' => $code ?: Str::upper(Str::random(12)),
            'description' => $this->cleanValue($row['description'] ?? null),
            'sector' => $this->cleanValue($row['sector'] ?? null),
            'is_active' => $this->parseBoolean($row['is_active'] ?? null) ?? true,
            'author_id' => $this->user?->id ?? auth()->id(),
        ];
    }

    public function createOrUpdateRecord(array $data)
    {
        // Determine unique lookup
        $lookupBy = !empty($data['code']) ? ['code' => $data['code']] : ['name' => $data['name']];

        if ($this->importMode === self::MODE_CREATE_ONLY) {
            $existing = Company::where($lookupBy)->first();
            if ($existing) {
                return null; // Skip
            }
            $company = Company::create($data);
            $company->wasRecentlyCreated = true;
            return $company;
        }

        if ($this->importMode === self::MODE_UPDATE_ONLY) {
            $existing = Company::where($lookupBy)->first();
            if (!$existing) {
                return null; // Skip — doesn't exist
            }
            $existing->update($data);
            return $existing;
        }

        // Upsert
        $company = Company::updateOrCreate($lookupBy, $data);
        return $company;
    }
}
