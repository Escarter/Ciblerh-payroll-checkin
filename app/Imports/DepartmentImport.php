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

    /**
     * @return int
     */
    public function startRow(): int
    {
        return 2;
    }


    public function __construct(Company $company)
    {
        $this->company = $company;
    }
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Validate that company exists and has an ID
        if (!$this->company || !$this->company->id) {
            throw new \Exception('Company is required for department import');
        }

        $code_exist = Department::where('company_id',$this->company->id)->where('name', $row[0])->first();
        if (!$code_exist ) {

            $department = Department::create([
                'name' => $row[0],
                'company_id' => $this->company->id,
                'author_id' => auth()->user()->id,
            ]);

            if(!empty($row[1]) && !empty(User::where('email', $row[1])->first())){
                $supervisor_id = User::where('email', $row[1])->first()->id ;
    
                SupervisorDepartment::updateOrCreate(
                    [
                        'department_id' => $department->id,
                    ],
                    [
                        'supervisor_id' => $supervisor_id,
                        'department_id' => $department->id,
                    ]
                );
            }

            return $department;
        }
    }
    public function rules(): array
    {
        return [
            '0' => 'required|string',
            '1' => function ($attribute, $value, $onFailure) {
                if (!empty($value) && empty(User::where('email', $value)->first())) {
                    $onFailure(__('Supervisor email not found'));
                }
            },
            // Validate company context
            '*' => function ($attribute, $value, $onFailure) {
                if (!$this->company || !$this->company->id) {
                    $onFailure(__('Company context is required for department import'));
                }
            }
        ];
    }
}
