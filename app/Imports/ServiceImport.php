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

    /**
     * @return int
     */
    public function startRow(): int
    {
        return 2;
    }


    public function __construct(Department $department)
    {
        $this->department = $department;
    }
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Validate that department exists and has a valid company
        if (!$this->department || !$this->department->id) {
            throw new \Exception('Department is required for service import');
        }

        if (!$this->department->company_id) {
            throw new \Exception('Department must belong to a company for service import');
        }

        $code_exist = Service::where('department_id', $this->department->id)->where('name', $row[0])->first();
        if (!$code_exist ) {

            return new Service([
                'name' => $row[0],
                'company_id' => $this->department->company_id,
                'department_id' => $this->department->id,
                'author_id' => auth()->user()->id,
            ]);

        }
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
                if (!$this->department->company_id) {
                    $onFailure(__('Department must belong to a company for service import'));
                }
            }
        ];
    }
}
