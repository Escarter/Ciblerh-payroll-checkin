<?php

namespace App\Imports\Adapters;

use App\Models\Company;
use App\Models\Department;
use App\Models\Service;

class ServiceImportAdapter extends BaseImportAdapter
{
    public function getEntityName(): string
    {
        return __('services.services');
    }

    public function getEntitySlug(): string
    {
        return 'services';
    }

    public function getUniqueKeys(): array
    {
        return ['name', 'department_id'];
    }

    public function getFieldDefinitions(): array
    {
        return [
            [
                'field' => 'name',
                'label' => __('services.name'),
                'required' => true,
                'type' => 'string',
                'aliases' => ['service_name', 'service', 'nom_service'],
            ],
            [
                'field' => 'department',
                'label' => __('departments.department'),
                'required' => true,
                'type' => 'relationship',
                'lookup_fields' => ['name'],
                'lookup_model' => Department::class,
                'aliases' => ['department_name', 'department_code', 'dept', 'departement', 'nom_departement'],
            ],
            [
                'field' => 'company',
                'label' => __('companies.company'),
                'required' => false,
                'type' => 'relationship',
                'lookup_fields' => ['code', 'name'],
                'lookup_model' => Company::class,
                'aliases' => ['company_name', 'company_code', 'societe', 'entreprise'],
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
                'message' => __('validation.required', ['attribute' => __('services.name')]),
            ];
        }

        // Department required if not in context
        $deptValue = $this->cleanValue($row['department'] ?? null);
        $contextDeptId = $this->context['department_id'] ?? null;
        if (empty($deptValue) && empty($contextDeptId)) {
            $errors[] = [
                'field' => 'department',
                'message' => __('validation.required', ['attribute' => __('departments.department')]),
            ];
        }

        return $errors;
    }

    public function validateRelationships(array $row, int $rowNumber): array
    {
        $errors = [];

        // Resolve company first (needed to scope department lookup)
        $company = $this->resolveCompany($row);

        // Resolve department
        $deptValue = $this->cleanValue($row['department'] ?? null);
        $contextDeptId = $this->context['department_id'] ?? null;

        $department = null;
        if ($deptValue) {
            $scope = $company ? ['company_id' => $company->id] : [];
            $department = $this->resolveFK(Department::class, $deptValue, ['name'], $scope);
            if (!$department) {
                $errors[] = [
                    'field' => 'department',
                    'message' => __('import.department_not_found', ['value' => $deptValue]),
                ];
            }
        } elseif ($contextDeptId) {
            $department = Department::find($contextDeptId);
        }

        // Validate transitive integrity: Department → Company
        if ($department && $company) {
            $chainErrors = $this->validateRelationshipChain([
                ['model' => $department, 'parent_fk' => 'company_id', 'label' => __('departments.department')],
                ['model' => $company, 'parent_fk' => null, 'label' => __('companies.company')],
            ]);
            $errors = array_merge($errors, $chainErrors);
        }

        return $errors;
    }

    public function transformRow(array $row): array
    {
        // Resolve company
        $company = $this->resolveCompany($row);

        // Resolve department
        $deptValue = $this->cleanValue($row['department'] ?? null);
        $contextDeptId = $this->context['department_id'] ?? null;

        $department = null;
        if ($deptValue) {
            $scope = $company ? ['company_id' => $company->id] : [];
            $department = $this->autoCreateEntities
                ? $this->resolveFKOrCreate(Department::class, $deptValue, ['name'], $scope, ['company_id' => $company?->id])
                : $this->resolveFK(Department::class, $deptValue, ['name'], $scope);
        }
        if (!$department && $contextDeptId) {
            $department = Department::find($contextDeptId);
        }

        if (!$department) {
            return ['__error' => __('import.department_resolution_failed')];
        }

        // Derive company_id from department if not explicitly set
        $companyId = $company?->id ?? $department->company_id;

        return [
            'name' => $this->cleanValue($row['name']),
            'department_id' => $department->id,
            'company_id' => $companyId,
            'is_active' => $this->parseBoolean($row['is_active'] ?? null) ?? true,
            'author_id' => $this->user?->id ?? auth()->id(),
        ];
    }

    public function createOrUpdateRecord(array $data)
    {
        $lookupBy = ['name' => $data['name'], 'department_id' => $data['department_id']];

        if ($this->importMode === self::MODE_CREATE_ONLY) {
            $existing = Service::where($lookupBy)->first();
            if ($existing) {
                return null;
            }
            $service = Service::create($data);
            $service->wasRecentlyCreated = true;
            return $service;
        }

        if ($this->importMode === self::MODE_UPDATE_ONLY) {
            $existing = Service::where($lookupBy)->first();
            if (!$existing) {
                return null;
            }
            $existing->update($data);
            return $existing;
        }

        return Service::updateOrCreate($lookupBy, $data);
    }

    // ─── Private ───────────────────────────────────────────────────────

    protected function resolveCompany(array $row): ?Company
    {
        $companyValue = $this->cleanValue($row['company'] ?? null);
        $contextCompanyId = $this->context['company_id'] ?? null;

        if ($companyValue) {
            return $this->resolveFK(Company::class, $companyValue, ['code', 'name']);
        }
        if ($contextCompanyId) {
            return Company::find($contextCompanyId);
        }
        return null;
    }
}
