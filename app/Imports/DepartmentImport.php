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

    /**
     * @return int
     */
    public function startRow(): int
    {
        return 2;
    }


    public function __construct(Company $company = null, bool $autoCreateCompany = false)
    {
        $this->company = $company;
        $this->autoCreateCompany = $autoCreateCompany;
    }
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Handle company lookup (by existing company context or name)
        $companyResult = $this->findCompany($row[2] ?? ''); // Assuming company name is in column 2
        if (!$companyResult['found']) {
            throw new \Exception('Company validation failed: ' . $companyResult['error']);
        }

        $company = $companyResult['company'];

        $code_exist = Department::where('company_id', $company->id)->where('name', $row[0])->first();
        if (!$code_exist) {

            $department = Department::create([
                'name' => $row[0],
                'company_id' => $company->id,
                'author_id' => auth()->user()->id,
            ]);

            // Handle supervisor assignment
            if (!empty($row[1])) {
                $supervisorResult = $this->findSupervisor($row[1], $company->id);
                if ($supervisorResult['found']) {
                SupervisorDepartment::updateOrCreate(
                    [
                        'department_id' => $department->id,
                    ],
                    [
                            'supervisor_id' => $supervisorResult['supervisor']->id,
                        'department_id' => $department->id,
                    ]
                );
                }
            }

            return $department;
        }

        return null;
    }
    /**
     * Find company by existing context or name
     */
    private function findCompany($companyValue): array
    {
        // If we have a company context, use it
        if ($this->company && $this->company->id) {
            return [
                'found' => true,
                'company' => $this->company,
                'error' => null
            ];
        }

        // If no company name provided, this is an error
        if (empty($companyValue)) {
            return [
                'found' => false,
                'company' => null,
                'error' => __('Company is required for department import')
            ];
        }

        // Try to find by name
        $result = findOrCreateCompany($companyValue, $this->autoCreateCompany);

        return $result;
    }

    /**
     * Find supervisor by email with better error messages
     */
    private function findSupervisor($email, $companyId): array
    {
        if (empty($email)) {
            return [
                'found' => false,
                'supervisor' => null,
                'error' => __('Supervisor email is empty')
            ];
        }

        $supervisor = User::where('email', $email)->first();

        if (!$supervisor) {
            return [
                'found' => false,
                'supervisor' => null,
                'error' => __('Supervisor with email ":email" not found', ['email' => $email])
            ];
        }

        // Check if supervisor belongs to the same company
        if ($supervisor->company_id !== $companyId) {
            return [
                'found' => false,
                'supervisor' => null,
                'error' => __('Supervisor ":email" belongs to a different company', ['email' => $email])
            ];
        }

        // Check if user has supervisor or manager role
        if (!$supervisor->hasRole(['supervisor', 'manager'])) {
            return [
                'found' => false,
                'supervisor' => null,
                'error' => __('User ":email" does not have supervisor or manager role', ['email' => $email])
            ];
        }

        return [
            'found' => true,
            'supervisor' => $supervisor,
            'error' => null
        ];
    }

    public function rules(): array
    {
        return [
            '0' => 'required|string', // department name
            '1' => function ($attribute, $value, $onFailure) { // supervisor email (optional)
                if (!empty($value)) {
                    $supervisorResult = $this->findSupervisor($value, $this->company ? $this->company->id : null);
                    if (!$supervisorResult['found']) {
                        $onFailure($supervisorResult['error']);
                    }
                }
            },
            // Validate company context (only if not auto-creating)
            '*' => function ($attribute, $value, $onFailure) {
                if (!$this->company && !$this->autoCreateCompany) {
                    $onFailure(__('Company context is required for department import'));
                }
            }
        ];
    }
}
