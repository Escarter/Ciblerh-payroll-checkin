<?php

namespace App\Imports;

use App\Models\Company;
use App\Models\Service;
use App\Models\Department;
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

class ServiceImport implements ToModel, WithStartRow, SkipsEmptyRows, WithValidation, SkipsOnError, SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures;

    public $department;
    public $company;
    public $autoCreateEntities = false; // Whether to auto-create missing departments/companies
    public $userId; // User ID for author_id field in background jobs

    /**
     * @return int
     */
    public function startRow(): int
    {
        return 2;
    }


    public function __construct(Department $department = null, bool $autoCreateEntities = false, $userId = null)
    {
        $this->department = $department;
        $this->company = $department ? $department->company : null;
        $this->autoCreateEntities = $autoCreateEntities;
        $this->userId = $userId;
    }
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Handle department lookup (by existing context or name)
        $departmentResult = $this->findDepartment($row[1] ?? ''); // Assuming department name is in column 1
        if (!$departmentResult['found']) {
            throw new \Exception('Department validation failed: ' . $departmentResult['error']);
        }

        $department = $departmentResult['department'];

        $code_exist = Service::where('department_id', $department->id)->where('name', $row[0])->first();
        if (!$code_exist) {

            return new Service([
                'name' => $row[0],
                'company_id' => $department->company_id,
                'department_id' => $department->id,
                'author_id' => $this->userId ?? auth()->user()->id,
            ]);

        }

        return null;
    }

    /**
     * Find department by ID or name
     */
    private function findDepartment($departmentValue): array
    {
        if (empty($departmentValue)) {
            return [
                'found' => false,
                'department' => null,
                'error' => __('Department is required')
            ];
        }

        // If we have a department context from constructor, use that
        if ($this->department && $this->department->id) {
            return [
                'found' => true,
                'department' => $this->department,
                'error' => null
            ];
        }

        // Try to find by ID first (if it's numeric)
        if (is_numeric($departmentValue)) {
            $department = Department::where('id', $departmentValue)
                ->where('company_id', $this->company ? $this->company->id : null)
                ->first();

            if ($department) {
                return [
                    'found' => true,
                    'department' => $department,
                    'error' => null
                ];
            }
        }

        // Try to find by name (using the helper function)
        if (!$this->company || !$this->company->id) {
            return [
                'found' => false,
                'department' => null,
                'error' => __('Company context is required for department lookup')
            ];
        }
        $result = findOrCreateDepartment($departmentValue, $this->company->id, $this->autoCreateEntities);

        return $result;
    }

    public function rules(): array
    {
        return [
            '0' => 'required|string',
            // Validate department and company context
            '*' => function ($attribute, $value, $onFailure) {
                if (!$this->department || !$this->department->id) {
                    $onFailure(__('Department context is required for service import'));
                }
                if (!$this->department || !$this->department->company_id) {
                    $onFailure(__('Department must belong to a company for service import'));
                }
            }
        ];
    }
}
