<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use App\Models\SupervisorDepartment;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithValidation;

class DepartmentImport implements ToModel, WithStartRow, SkipsEmptyRows, WithValidation, SkipsOnError, SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures;

    public $company;
    public $autoCreateCompany = false; // Whether to auto-create missing company
    public $userId; // User ID for author_id field in background jobs

    /**
     * @return int
     */
    public function startRow(): int
    {
        return 2;
    }

    public function __construct($company = null, bool $autoCreateCompany = false, $userId = null)
    {
        // Handle different ways company can be passed
        if (is_numeric($company)) {
            $this->company = Company::find($company);
        } elseif ($company instanceof Company) {
            $this->company = $company;
        } else {
            $this->company = null;
        }

        $this->autoCreateCompany = $autoCreateCompany;
        $this->userId = $userId;

        // Log the initialization
        \Log::info('DepartmentImport initialized', [
            'company_id' => $this->company ? $this->company->id : null,
            'company_name' => $this->company ? $this->company->name : null,
            'company_valid' => $this->company && $this->company->id ? true : false,
            'auto_create_company' => $autoCreateCompany,
            'user_id' => $this->userId
        ]);
    }

    /**
     * Process a single row from the department import CSV
     *
     * Expected CSV columns (based on import_departments.csv template):
     * - Column 0: Department Name (required)
     * - Column 1: Supervisor Email (optional)
     * - Column 2: Company Name (required when no context company provided)
     *
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        try {
            // Debug logging with column mapping
            \Log::info('DepartmentImport processing row', [
                'row' => $row,
                'column_0_department_name' => $row[0] ?? 'EMPTY',
                'column_1_supervisor_email' => $row[1] ?? 'EMPTY',
                'column_2_company_name' => $row[2] ?? 'EMPTY',
                'has_company_context' => $this->company ? true : false,
                'company_id' => $this->company ? $this->company->id : null,
                'company_name' => $this->company ? $this->company->name : null,
                'auto_create_company' => $this->autoCreateCompany
            ]);

            // DECISION LOGIC: Always use context company if provided, otherwise look up from CSV
            if ($this->company && $this->company->id) {
                // Use the context company (passed in constructor)
                $company = $this->company;
                \Log::info('Using context company for department', [
                    'company_id' => $company->id,
                    'company_name' => $company->name,
                    'department_name' => $row[0] ?? 'N/A'
                ]);
            } else {
                // No context company provided, look up from CSV column 2
                \Log::info('No context company, looking up from CSV', [
                    'csv_company_name' => $row[2] ?? 'empty'
                ]);

                $companyResult = $this->findCompanyFromCSV($row[2] ?? '');

                \Log::info('Company lookup result from CSV', [
                    'company_result' => $companyResult,
                    'company_value_from_csv' => $row[2] ?? 'empty'
                ]);

                if (!$companyResult['found']) {
                    throw new \Exception('Company validation failed: ' . $companyResult['error']);
                }

                $company = $companyResult['company'];

                // Additional safety check
                if (!$company || !$company->id) {
                    throw new \Exception('Company validation failed: Invalid company object');
                }
            }

            // Check if department already exists for this company
            $existingDepartment = Department::where('company_id', $company->id)
                ->where('name', $row[0])
                ->first();

            if (!$existingDepartment) {
                // Debug logging for author_id
                $authorId = $this->userId ?? auth()->user()->id;
                \Log::info('DepartmentImport creating department', [
                    'department_name' => $row[0],
                    'company_id' => $company->id,
                    'this_userId' => $this->userId,
                    'auth_user_id' => auth()->user() ? auth()->user()->id : 'null',
                    'final_author_id' => $authorId
                ]);

                // Create new department
                $department = Department::create([
                    'name' => $row[0],
                    'company_id' => $company->id,
                    'author_id' => $authorId,
                ]);

                \Log::info('Department created successfully', [
                    'department_id' => $department->id,
                    'department_name' => $department->name,
                    'company_id' => $company->id
                ]);

                // Handle supervisor assignment if email provided in column 1 (Supervisor Email column)
                if (!empty($row[1])) {
                    \Log::info('Supervisor email provided in CSV, processing supervisor assignment', [
                        'department_name' => $department->name,
                        'supervisor_email' => $row[1]
                    ]);

                    $supervisorResult = $this->findSupervisor($row[1], $company->id);

                    // FIXED: Proper null checks before accessing ->id
                    $hasValidSupervisor = false;
                    $supervisorId = null;

                    if (
                        isset($supervisorResult['supervisor']) &&
                        $supervisorResult['supervisor'] instanceof \App\Models\User &&
                        $supervisorResult['supervisor']->exists &&
                        !empty($supervisorResult['supervisor']->id)
                    ) {

                        $supervisorId = $supervisorResult['supervisor']->id;
                        $hasValidSupervisor = true;
                    }

                    if ($hasValidSupervisor) {
                        SupervisorDepartment::updateOrCreate(
                            [
                                'department_id' => $department->id,
                            ],
                            [
                                'supervisor_id' => $supervisorId,
                                'department_id' => $department->id,
                            ]
                        );

                        \Log::info('Supervisor assigned to department', [
                            'department_id' => $department->id,
                            'supervisor_id' => $supervisorId,
                            'supervisor_email' => $row[1]
                        ]);
                    } else {
                        // FIXED: Check if supervisor exists and has valid ID
                        $supervisorHasId = false;
                        if (
                            isset($supervisorResult['supervisor']) &&
                            $supervisorResult['supervisor'] instanceof \App\Models\User &&
                            $supervisorResult['supervisor']->exists &&
                            !empty($supervisorResult['supervisor']->id)
                        ) {
                            $supervisorHasId = true;
                        }

                        \Log::warning('Supervisor not found or not assigned', [
                            'department_id' => $department->id,
                            'supervisor_email' => $row[1],
                            'supervisor_result_found' => $supervisorResult['found'] ?? false,
                            'has_supervisor' => isset($supervisorResult['supervisor']) ? 'yes' : 'no',
                            'supervisor_valid' => isset($supervisorResult['supervisor']) && $supervisorResult['supervisor'] ? 'yes' : 'no',
                            'supervisor_has_id' => $supervisorHasId ? 'yes' : 'no', // FIXED LINE
                            'error' => $supervisorResult['error'] ?? 'Unknown error'
                        ]);
                    }
                } else {
                    \Log::info('No supervisor email provided in CSV, skipping supervisor assignment', [
                        'department_name' => $department->name,
                        'supervisor_column_empty' => true
                    ]);
                }

                return $department;
            } else {
                \Log::info('Department already exists, skipping', [
                    'department_name' => $row[0],
                    'company_id' => $company->id,
                    'existing_department_id' => $existingDepartment->id
                ]);
            }

            return null;
        } catch (\Exception $e) {
            \Log::error('DepartmentImport model exception', [
                'row' => $row,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => substr($e->getTraceAsString(), 0, 500) // Shorter trace for readability
            ]);
            throw $e;
        }
    }

    /**
     * Find company from CSV value (only used when no context company is provided)
     */
    private function findCompanyFromCSV($companyValue): array
    {
        \Log::info('findCompanyFromCSV called', ['companyValue' => $companyValue]);

        // If no company name provided, this is an error
        if (empty(trim($companyValue))) {
            return [
                'found' => false,
                'company' => null,
                'error' => __('companies.name_is_required_in_csv_when_no_context_company_is_provided')
            ];
        }

        // Use the helper function to find or create company
        $result = findOrCreateCompany(trim($companyValue), $this->autoCreateCompany);

        \Log::info('findOrCreateCompany result', [
            'company_name' => trim($companyValue),
            'found' => $result['found'],
            'company_id' => $result['found'] ? ($result['company']->id ?? 'null') : 'null',
            'was_created' => $result['created'] ?? false
        ]);

        // Safety check: ensure company is valid if found
        if ($result['found'] && (!$result['company'] || !$result['company']->id)) {
            return [
                'found' => false,
                'company' => null,
                'error' => __('companies.validation_failed', ['company_name' => trim($companyValue)])
            ];
        }

        return $result;
    }

    /**
     * Find supervisor by email with better error messages
     */
    private function findSupervisor($email, $companyId): array
    {
        \Log::info('findSupervisor called', ['email' => $email, 'companyId' => $companyId]);

        if (empty(trim($email))) {
            return [
                'found' => false,
                'supervisor' => null,
                'error' => __('employees.email_is_empty')
            ];
        }

        $supervisor = User::where('email', trim($email))->first();

        if (!$supervisor) {
            return [
                'found' => false,
                'supervisor' => null,
                'error' => __('employees.not_found', ['email' => $email])
            ];
        }

        // DEBUG: Log supervisor object
        \Log::info('Supervisor found in database', [
            'supervisor_id' => $supervisor->id,
            'supervisor_email' => $supervisor->email,
            'supervisor_company_id' => $supervisor->company_id,
            'requested_company_id' => $companyId,
            'has_id_property' => isset($supervisor->id) ? 'yes' : 'no',
            'id_value' => $supervisor->id ?? 'null'
        ]);

        // Check if supervisor belongs to the same company
        if ($supervisor->company_id != $companyId) {
            \Log::warning('Supervisor company mismatch', [
                'supervisor_id' => $supervisor->id,
                'supervisor_company_id' => $supervisor->company_id,
                'expected_company_id' => $companyId
            ]);

            return [
                'found' => false,
                'supervisor' => null, // Set to null to prevent issues
                'error' => __(
                    'supervisors.belongs_to_different_company',
                    ['email' => $email, 'supervisor_company' => $supervisor->company_id, 'expected_company' => $companyId]
                )
            ];
        }

        // Check if user has supervisor or manager role
        if (!$supervisor->hasRole(['supervisor', 'manager'])) {
            \Log::warning('Supervisor missing required role', [
                'supervisor_id' => $supervisor->id,
                'email' => $email,
                'roles' => $supervisor->getRoleNames()
            ]);

            return [
                'found' => false,
                'supervisor' => null, // Set to null to prevent issues
                'error' => __('employees.does_not_have_role', ['email' => $email])
            ];
        }

        \Log::info('Supervisor found and validated', [
            'supervisor_id' => $supervisor->id,
            'email' => $email,
            'roles' => $supervisor->getRoleNames()
        ]);

        return [
            'found' => true,
            'supervisor' => $supervisor,
            'error' => null
        ];
    }

    /**
     * Validation rules for CSV columns
     *
     * Column mapping (matches import_departments.csv template):
     * - 0: Department Name (required, string, max 255 chars)
     * - 1: Supervisor Email (optional, valid email format, max 255 chars)
     * - 2: Company Name (validated separately based on context)
     */
    public function rules(): array
    {
        return [
            '0' => 'required|string|max:255', // department name
            '1' => 'nullable|email|max:255', // supervisor email (optional)
            // Note: Supervisor email validation removed - non-existent supervisors are ignored during import
            // Company validation depends on context
        ];
    }

    /**
     * Custom validation messages
     */
    public function customValidationMessages(): array
    {
        return [
            '0.required' => __('departments.name_is_required'),
            '0.string' => __('departments.name_must_be_text'),
            '0.max' => __('departments.name_cannot_exceed_255_characters'),
        ];
    }

    /**
     * Custom attribute names for validation error messages
     */
    public function customValidationAttributes(): array
    {
        return [
            '0' => __('departments.name'),
            '1' => __('employees.email'),
            '2' => __('companies.company'),
        ];
    }
}
