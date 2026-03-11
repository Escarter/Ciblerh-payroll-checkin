<?php

namespace App\Imports\Adapters;

use App\Models\User;
use App\Models\Company;
use App\Models\Department;
use App\Models\Service;
use App\Events\EmployeeCreated;
use Illuminate\Support\Str;

class EmployeeImportAdapter extends BaseImportAdapter
{
    protected bool $sendWelcomeEmails = false;

    public function setSendWelcomeEmails(bool $send): self
    {
        $this->sendWelcomeEmails = $send;
        return $this;
    }

    public function getEntityName(): string
    {
        return __('employees.employees');
    }

    public function getEntitySlug(): string
    {
        return 'employees';
    }

    public function getUniqueKeys(): array
    {
        return ['email'];
    }

    public function getFieldDefinitions(): array
    {
        return [
            [
                'field' => 'first_name',
                'label' => __('employees.first_name'),
                'required' => true,
                'type' => 'string',
                'aliases' => ['prenom', 'firstname', 'given_name', 'nom'],
            ],
            [
                'field' => 'last_name',
                'label' => __('employees.last_name'),
                'required' => true,
                'type' => 'string',
                'aliases' => ['nom_famille', 'lastname', 'surname', 'family_name'],
            ],
            [
                'field' => 'email',
                'label' => __('employees.email'),
                'required' => true,
                'type' => 'email',
                'aliases' => ['e-mail', 'courriel', 'email_address', 'adresse_email'],
            ],
            [
                'field' => 'professional_phone_number',
                'label' => __('common.prof_phone_number'),
                'required' => true,
                'type' => 'phone',
                'aliases' => ['phone', 'telephone', 'tel_pro', 'phone_number', 'numero_telephone'],
            ],
            [
                'field' => 'matricule',
                'label' => __('employees.matricule'),
                'required' => true,
                'type' => 'string',
                'aliases' => ['employee_id', 'staff_id', 'badge', 'numero_matricule', 'id_employe'],
            ],
            [
                'field' => 'position',
                'label' => __('common.position'),
                'required' => false,
                'type' => 'string',
                'aliases' => ['poste', 'job_title', 'titre', 'fonction', 'job'],
            ],
            [
                'field' => 'net_salary',
                'label' => __('employees.net_salary'),
                'required' => false,
                'type' => 'numeric',
                'aliases' => ['salary', 'salaire', 'salaire_net', 'remuneration', 'pay'],
            ],
            [
                'field' => 'salary_grade',
                'label' => __('employees.salary_grade'),
                'required' => false,
                'type' => 'string',
                'aliases' => ['grade', 'echelon', 'categorie', 'category', 'level'],
            ],
            [
                'field' => 'contract_end',
                'label' => __('employees.contract_end_date'),
                'required' => false,
                'type' => 'date',
                'aliases' => ['fin_contrat', 'end_date', 'date_fin', 'contract_end_date', 'expiry'],
            ],
            [
                'field' => 'department',
                'label' => __('departments.department'),
                'required' => false,
                'type' => 'relationship',
                'lookup_fields' => ['name'],
                'lookup_model' => Department::class,
                'aliases' => ['department_name', 'dept', 'departement', 'nom_departement', 'department_code'],
            ],
            [
                'field' => 'service',
                'label' => __('services.service'),
                'required' => false,
                'type' => 'relationship',
                'lookup_fields' => ['name'],
                'lookup_model' => Service::class,
                'aliases' => ['service_name', 'nom_service', 'unit', 'unite'],
            ],
            [
                'field' => 'role',
                'label' => __('employees.role'),
                'required' => false,
                'type' => 'enum',
                'enum_values' => ['employee', 'supervisor', 'manager'],
                'aliases' => ['user_role', 'profil', 'type', 'role_name'],
                'default' => 'employee',
            ],
            [
                'field' => 'status',
                'label' => __('common.status'),
                'required' => false,
                'type' => 'enum',
                'enum_values' => ['1', '0', 'active', 'inactive'],
                'aliases' => ['statut', 'is_active', 'actif', 'active'],
                'default' => '1',
            ],
            [
                'field' => 'password',
                'label' => __('common.password'),
                'required' => false,
                'type' => 'string',
                'aliases' => ['mot_de_passe', 'pwd', 'pass'],
            ],
            [
                'field' => 'remaining_leave_days',
                'label' => __('employees.remaining_leave_days'),
                'required' => false,
                'type' => 'numeric',
                'aliases' => ['leave_days', 'jours_conge', 'conges_restants', 'leave_balance'],
            ],
            [
                'field' => 'monthly_leave_allocation',
                'label' => __('common.monthly_leave_allocation'),
                'required' => false,
                'type' => 'numeric',
                'aliases' => ['leave_allocation', 'allocation_conge', 'monthly_leave'],
            ],
            [
                'field' => 'receive_sms_notifications',
                'label' => __('employees.receive_sms_notifications'),
                'required' => false,
                'type' => 'boolean',
                'aliases' => ['sms_notif', 'sms'],
                'default' => true,
            ],
            [
                'field' => 'personal_phone_number',
                'label' => __('common.personal_phone_number'),
                'required' => false,
                'type' => 'phone',
                'aliases' => ['personal_phone', 'tel_perso', 'mobile', 'portable'],
            ],
            [
                'field' => 'work_start_time',
                'label' => __('common.work_start_time'),
                'required' => false,
                'type' => 'time',
                'aliases' => ['start_time', 'heure_debut', 'debut_travail'],
                'default' => '08:00',
            ],
            [
                'field' => 'work_end_time',
                'label' => __('common.work_end_time'),
                'required' => false,
                'type' => 'time',
                'aliases' => ['end_time', 'heure_fin', 'fin_travail'],
                'default' => '17:30',
            ],
            [
                'field' => 'receive_email_notifications',
                'label' => __('employees.receive_email_notifications'),
                'required' => false,
                'type' => 'boolean',
                'aliases' => ['email_notif', 'email_notification'],
                'default' => true,
            ],
            [
                'field' => 'alternative_email',
                'label' => __('employees.alternative_email'),
                'required' => false,
                'type' => 'email',
                'aliases' => ['alt_email', 'email2', 'secondary_email', 'email_alternatif'],
            ],
            [
                'field' => 'date_of_birth',
                'label' => __('common.date_of_birth'),
                'required' => false,
                'type' => 'date',
                'aliases' => ['dob', 'birthday', 'birth_date', 'date_naissance', 'naissance'],
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
        ];
    }

    public function validateRow(array $row, int $rowNumber): array
    {
        $errors = [];

        // At least one name required
        $firstName = $this->cleanValue($row['first_name'] ?? null);
        $lastName = $this->cleanValue($row['last_name'] ?? null);
        if (empty($firstName) && empty($lastName)) {
            $errors[] = [
                'field' => 'first_name',
                'message' => __('employees.at_least_one_name_required'),
            ];
        }

        // Email required and valid format
        $email = $this->cleanValue($row['email'] ?? null);
        if (empty($email)) {
            $errors[] = [
                'field' => 'email',
                'message' => __('validation.required', ['attribute' => __('employees.email')]),
            ];
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = [
                'field' => 'email',
                'message' => __('validation.email', ['attribute' => __('employees.email')]),
            ];
        }

        // Phone required
        $phone = $this->cleanValue($row['professional_phone_number'] ?? null);
        if (empty($phone)) {
            $errors[] = [
                'field' => 'professional_phone_number',
                'message' => __('validation.required', ['attribute' => __('common.prof_phone_number')]),
            ];
        }

        // Matricule required
        if (empty($this->cleanValue($row['matricule'] ?? null))) {
            $errors[] = [
                'field' => 'matricule',
                'message' => __('validation.required', ['attribute' => __('employees.matricule')]),
            ];
        }

        // Net salary — optional, but must be numeric when provided
        $salary = $row['net_salary'] ?? null;
        if ($salary !== null && $salary !== '' && $this->parseNumeric($salary) === null) {
            $errors[] = [
                'field' => 'net_salary',
                'message' => __('validation.numeric', ['attribute' => __('employees.net_salary')]),
            ];
        }

        // Role validation
        $role = strtolower(trim($row['role'] ?? 'employee'));
        if (!in_array($role, ['employee', 'supervisor', 'manager'])) {
            $errors[] = [
                'field' => 'role',
                'message' => __('employees.role_invalid'),
            ];
        }

        // Alternative email format
        $altEmail = $this->cleanValue($row['alternative_email'] ?? null);
        if ($altEmail && !filter_var($altEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = [
                'field' => 'alternative_email',
                'message' => __('validation.email', ['attribute' => __('employees.alternative_email')]),
            ];
        }

        // Company required if not in context
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
        $company = $this->resolveCompanyFromRow($row);
        if (!$company) {
            $companyValue = $this->cleanValue($row['company'] ?? null);
            if ($companyValue) {
                $errors[] = [
                    'field' => 'company',
                    'message' => __('import.company_not_found', ['value' => $companyValue]),
                ];
            }
            // If no company from CSV or context, already covered in validateRow
            return $errors;
        }

        // Resolve department
        $deptValue = $this->cleanValue($row['department'] ?? null);
        $contextDeptId = $this->context['department_id'] ?? null;
        $department = null;

        if ($deptValue) {
            $department = $this->resolveFK(Department::class, $deptValue, ['name'], ['company_id' => $company->id]);
            if (!$department && !$this->autoCreateEntities) {
                $errors[] = [
                    'field' => 'department',
                    'message' => __('import.department_not_found', ['value' => $deptValue]),
                ];
            }
        } elseif ($contextDeptId) {
            $department = Department::find($contextDeptId);
        }

        // Resolve service
        $serviceValue = $this->cleanValue($row['service'] ?? null);
        $contextServiceId = $this->context['service_id'] ?? null;
        $service = null;

        if ($serviceValue && $department) {
            $service = $this->resolveFK(Service::class, $serviceValue, ['name'], ['department_id' => $department->id]);
            if (!$service && !$this->autoCreateEntities) {
                $errors[] = [
                    'field' => 'service',
                    'message' => __('import.service_not_found', ['value' => $serviceValue]),
                ];
            }
        } elseif ($contextServiceId) {
            $service = Service::find($contextServiceId);
        }

        // Validate transitive chain: Service → Department → Company
        if ($service && $department && $company) {
            $chainErrors = $this->validateRelationshipChain([
                ['model' => $service, 'parent_fk' => 'department_id', 'label' => __('services.service')],
                ['model' => $department, 'parent_fk' => 'company_id', 'label' => __('departments.department')],
                ['model' => $company, 'parent_fk' => null, 'label' => __('companies.company')],
            ]);
            $errors = array_merge($errors, $chainErrors);
        } elseif ($department && $company) {
            // Just check Department → Company
            if ($department->company_id != $company->id) {
                $errors[] = [
                    'field' => 'department',
                    'message' => __('import.department_wrong_company', [
                        'department' => $department->name,
                        'company' => $company->name,
                    ]),
                ];
            }
        }

        // Check email uniqueness in DB (only for create/upsert modes)
        if ($this->importMode !== self::MODE_UPDATE_ONLY) {
            $email = $this->cleanValue($row['email'] ?? null);
            if ($email && User::where('email', $email)->exists()) {
                if ($this->importMode === self::MODE_CREATE_ONLY) {
                    // Not an error — will be skipped, but note it
                    // (handled in createOrUpdateRecord)
                }
                // For upsert: this is expected, will update
            }
        }

        // Validate role permissions
        $csvRole = strtolower(trim($row['role'] ?? 'employee'));
        if ($this->user) {
            $currentUserRole = $this->user->getRoleNames()->first() ?? 'employee';
            $allowedRoles = match ($currentUserRole) {
                'admin' => ['admin', 'manager', 'supervisor', 'employee'],
                'manager' => ['employee', 'supervisor'],
                'supervisor' => ['employee'],
                default => ['employee'],
            };
            if (!in_array($csvRole, $allowedRoles)) {
                $errors[] = [
                    'field' => 'role',
                    'message' => __('import.role_not_permitted', ['role' => $csvRole]),
                ];
            }
        }

        return $errors;
    }

    public function transformRow(array $row): array
    {
        // Resolve company
        $company = $this->resolveCompanyFromRow($row);
        if (!$company) {
            return ['__error' => __('import.company_resolution_failed')];
        }

        // Resolve department
        $deptValue = $this->cleanValue($row['department'] ?? null);
        $contextDeptId = $this->context['department_id'] ?? null;
        $department = null;

        if ($deptValue) {
            $department = $this->autoCreateEntities
                ? $this->resolveFKOrCreate(Department::class, $deptValue, ['name'], ['company_id' => $company->id], ['company_id' => $company->id])
                : $this->resolveFK(Department::class, $deptValue, ['name'], ['company_id' => $company->id]);
        }
        if (!$department && $contextDeptId) {
            $department = Department::find($contextDeptId);
        }
        if (!$department) {
            return ['__error' => __('import.department_resolution_failed')];
        }

        // Resolve service (optional)
        $serviceValue = $this->cleanValue($row['service'] ?? null);
        $contextServiceId = $this->context['service_id'] ?? null;
        $service = null;

        if ($serviceValue) {
            $service = $this->autoCreateEntities
                ? $this->resolveFKOrCreate(Service::class, $serviceValue, ['name'], ['department_id' => $department->id], ['department_id' => $department->id, 'company_id' => $company->id])
                : $this->resolveFK(Service::class, $serviceValue, ['name'], ['department_id' => $department->id]);
        }
        if (!$service && $contextServiceId) {
            $service = Service::find($contextServiceId);
        }

        // Parse phone numbers
        $profPhone = $this->parsePhone($row['professional_phone_number'] ?? null);
        if (!$profPhone) {
            return ['__error' => __('import.invalid_phone', ['field' => __('common.prof_phone_number')])];
        }

        $firstName = $this->cleanValue($row['first_name'] ?? null);
        $lastName = $this->cleanValue($row['last_name'] ?? null);
        $firstName = !empty($firstName) ? $firstName : 'NA';
        $lastName = !empty($lastName) ? $lastName : 'NA';

        $password = $this->cleanValue($row['password'] ?? null) ?: Str::random(10);

        $status = $this->mapEnum($row['status'] ?? '1', [
            '1' => 1, '0' => 0,
            'active' => 1, 'inactive' => 0,
            'actif' => 1, 'inactif' => 0,
            'true' => 1, 'false' => 0,
        ], 1);

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => strtolower(trim($row['email'])),
            'professional_phone_number' => $profPhone,
            'personal_phone_number' => $this->parsePhone($row['personal_phone_number'] ?? null),
            'matricule' => (string) ($row['matricule'] ?? ''),
            'position' => $this->cleanValue($row['position'] ?? null),
            'net_salary' => $this->parseNumeric($row['net_salary'] ?? null),
            'salary_grade' => $this->cleanValue($row['salary_grade'] ?? null),
            'contract_end' => $this->parseDate($row['contract_end'] ?? null),
            'company_id' => $company->id,
            'department_id' => $department->id,
            'service_id' => $service?->id,
            'status' => $status,
            'password' => bcrypt($password),
            'remaining_leave_days' => $this->parseNumeric($row['remaining_leave_days'] ?? null),
            'monthly_leave_allocation' => $this->parseNumeric($row['monthly_leave_allocation'] ?? null),
            'receive_sms_notifications' => $this->parseBoolean($row['receive_sms_notifications'] ?? null) ?? true,
            'receive_email_notifications' => $this->parseBoolean($row['receive_email_notifications'] ?? null) ?? true,
            'alternative_email' => $this->cleanValue($row['alternative_email'] ?? null),
            'date_of_birth' => $this->parseDate($row['date_of_birth'] ?? null),
            'work_start_time' => $this->parseTime($row['work_start_time'] ?? null, '08:00'),
            'work_end_time' => $this->parseTime($row['work_end_time'] ?? null, '17:30'),
            'author_id' => $this->user?->id ?? auth()->id(),
            'pdf_password' => Str::random(10),
            // Meta fields for post-creation processing
            '__role' => strtolower(trim($row['role'] ?? 'employee')),
            '__raw_password' => $password,
        ];
    }

    public function createOrUpdateRecord(array $data)
    {
        $role = $data['__role'] ?? 'employee';
        $rawPassword = $data['__raw_password'] ?? null;
        unset($data['__role'], $data['__raw_password']);

        $email = $data['email'];

        if ($this->importMode === self::MODE_CREATE_ONLY) {
            $existing = User::where('email', $email)->first();
            if ($existing) {
                return null; // Skip
            }
            $user = User::create($data);
            $user->wasRecentlyCreated = true;
            $this->assignRoles($user, $role);
            $this->fireEvents($user, $rawPassword);
            return $user;
        }

        if ($this->importMode === self::MODE_UPDATE_ONLY) {
            $existing = User::where('email', $email)->first();
            if (!$existing) {
                return null; // Skip
            }
            // Don't overwrite password on update unless explicitly set
            unset($data['password'], $data['pdf_password']);
            $existing->update($data);
            $this->assignRoles($existing, $role);
            return $existing;
        }

        // Upsert
        $existing = User::where('email', $email)->first();
        if ($existing) {
            unset($data['password'], $data['pdf_password']);
            $existing->update($data);
            $this->assignRoles($existing, $role);
            return $existing;
        }

        $user = User::create($data);
        $user->wasRecentlyCreated = true;
        $this->assignRoles($user, $role);
        $this->fireEvents($user, $rawPassword);
        return $user;
    }

    // ─── Private Helpers ───────────────────────────────────────────────

    protected function resolveCompanyFromRow(array $row): ?Company
    {
        $companyValue = $this->cleanValue($row['company'] ?? null);
        $contextCompanyId = $this->context['company_id'] ?? null;

        if ($companyValue) {
            $company = $this->autoCreateEntities
                ? $this->resolveFKOrCreate(Company::class, $companyValue, ['code', 'name'])
                : $this->resolveFK(Company::class, $companyValue, ['code', 'name']);
            if ($company) {
                return $company;
            }
        }
        if ($contextCompanyId) {
            return Company::find($contextCompanyId);
        }
        return null;
    }

    protected function assignRoles(User $user, string $role): void
    {
        $user->syncRoles([]); // Clear existing
        if ($role === 'employee') {
            $user->assignRole('employee');
        } else {
            $user->assignRole(['employee', $role]);
        }
    }

    protected function fireEvents(User $user, ?string $rawPassword): void
    {
        if ($this->sendWelcomeEmails && $rawPassword) {
            event(new EmployeeCreated($user, $rawPassword));
        }
    }
}
