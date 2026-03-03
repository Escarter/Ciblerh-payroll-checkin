<?php

namespace App\Imports\Adapters;

use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use App\Models\SupervisorDepartment;

class DepartmentImportAdapter extends BaseImportAdapter
{
    public function getEntityName(): string
    {
        return __('departments.departments');
    }

    public function getEntitySlug(): string
    {
        return 'departments';
    }

    public function getUniqueKeys(): array
    {
        return ['name', 'company_id'];
    }

    public function getFieldDefinitions(): array
    {
        return [
            [
                'field' => 'name',
                'label' => __('departments.name'),
                'required' => true,
                'type' => 'string',
                'aliases' => ['department_name', 'department', 'dept', 'departement', 'nom_departement'],
            ],
            [
                'field' => 'company',
                'label' => __('companies.company'),
                'required' => false,
                'type' => 'relationship',
                'lookup_fields' => ['code', 'name'],
                'lookup_model' => Company::class,
                'aliases' => ['company_name', 'company_code', 'societe', 'entreprise', 'nom_societe'],
            ],
            [
                'field' => 'supervisor_email',
                'label' => __('departments.supervisor_email'),
                'required' => false,
                'type' => 'email',
                'aliases' => ['supervisor', 'sup_email', 'email_superviseur', 'responsable'],
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
                'message' => __('validation.required', ['attribute' => __('departments.name')]),
            ];
        } elseif (strlen($name) > 255) {
            $errors[] = [
                'field' => 'name',
                'message' => __('validation.max.string', ['attribute' => __('departments.name'), 'max' => 255]),
            ];
        }

        // Validate supervisor email format if provided
        $supervisorEmail = $this->cleanValue($row['supervisor_email'] ?? null);
        if ($supervisorEmail && !filter_var($supervisorEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = [
                'field' => 'supervisor_email',
                'message' => __('validation.email', ['attribute' => __('departments.supervisor_email')]),
            ];
        }

        // Company is required if not in context
        $companyValue = $this->cleanValue($row['company'] ?? null);
        $contextCompanyId = $this->context['company_id'] ?? null;
        if (empty($companyValue) && empty($contextCompanyId)) {
            $errors[] = [
                'field' => 'company',
                'message' => __('validation.required', ['attribute' => __('companies.company')]),
            ];
        }

        return $errors;
    }

    public function validateRelationships(array $row, int $rowNumber): array
    {
        $errors = [];

        // Resolve company
        $companyValue = $this->cleanValue($row['company'] ?? null);
        $contextCompanyId = $this->context['company_id'] ?? null;

        $company = null;
        if ($companyValue) {
            $company = $this->resolveFK(Company::class, $companyValue, ['code', 'name']);
            if (!$company) {
                $errors[] = [
                    'field' => 'company',
                    'message' => __('import.company_not_found', ['value' => $companyValue]),
                ];
            }
        } elseif ($contextCompanyId) {
            $company = Company::find($contextCompanyId);
            if (!$company) {
                $errors[] = [
                    'field' => 'company',
                    'message' => __('import.context_company_invalid'),
                ];
            }
        }

        // Validate supervisor if provided
        $supervisorEmail = $this->cleanValue($row['supervisor_email'] ?? null);
        if ($supervisorEmail && $company) {
            $supervisor = User::where('email', $supervisorEmail)->first();
            if (!$supervisor) {
                $errors[] = [
                    'field' => 'supervisor_email',
                    'message' => __('import.supervisor_not_found', ['email' => $supervisorEmail]),
                ];
            } elseif ($supervisor->company_id != $company->id) {
                $errors[] = [
                    'field' => 'supervisor_email',
                    'message' => __('import.supervisor_wrong_company', ['email' => $supervisorEmail]),
                ];
            } elseif (!$supervisor->hasRole(['supervisor', 'manager', 'admin'])) {
                $errors[] = [
                    'field' => 'supervisor_email',
                    'message' => __('import.supervisor_wrong_role', ['email' => $supervisorEmail]),
                ];
            }
        }

        return $errors;
    }

    public function transformRow(array $row): array
    {
        // Resolve company
        $companyValue = $this->cleanValue($row['company'] ?? null);
        $contextCompanyId = $this->context['company_id'] ?? null;

        $company = null;
        if ($companyValue) {
            $company = $this->autoCreateEntities
                ? $this->resolveFKOrCreate(Company::class, $companyValue, ['code', 'name'])
                : $this->resolveFK(Company::class, $companyValue, ['code', 'name']);
        }
        if (!$company && $contextCompanyId) {
            $company = Company::find($contextCompanyId);
        }

        if (!$company) {
            return ['__error' => __('import.company_resolution_failed')];
        }

        $data = [
            'name' => $this->cleanValue($row['name']),
            'company_id' => $company->id,
            'is_active' => $this->parseBoolean($row['is_active'] ?? null) ?? true,
            'author_id' => $this->user?->id ?? auth()->id(),
            '__supervisor_email' => $this->cleanValue($row['supervisor_email'] ?? null),
        ];

        return $data;
    }

    public function createOrUpdateRecord(array $data)
    {
        $supervisorEmail = $data['__supervisor_email'] ?? null;
        unset($data['__supervisor_email']);

        $lookupBy = ['name' => $data['name'], 'company_id' => $data['company_id']];

        $department = null;

        if ($this->importMode === self::MODE_CREATE_ONLY) {
            $existing = Department::where($lookupBy)->first();
            if ($existing) {
                return null; // Skip
            }
            $department = Department::create($data);
            $department->wasRecentlyCreated = true;
        } elseif ($this->importMode === self::MODE_UPDATE_ONLY) {
            $department = Department::where($lookupBy)->first();
            if (!$department) {
                return null;
            }
            $department->update($data);
        } else {
            // Upsert
            $department = Department::updateOrCreate($lookupBy, $data);
        }

        // Handle supervisor assignment
        if ($department && $supervisorEmail) {
            $supervisor = User::where('email', $supervisorEmail)->first();
            if ($supervisor) {
                SupervisorDepartment::updateOrCreate(
                    ['department_id' => $department->id],
                    ['supervisor_id' => $supervisor->id]
                );
            }
        }

        return $department;
    }
}
