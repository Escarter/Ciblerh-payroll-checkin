<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Events\EmployeeCreated;
use App\Models\Department;
use App\Models\Service;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithValidation;

class EmployeeImport implements ToModel, WithStartRow, SkipsEmptyRows, WithValidation, SkipsOnError, SkipsOnFailure
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
            throw new \Exception('Company is required for employee import');
        }

        $code_exist = User::where('email', $row[2])->first();
        $department_exist = Department::where('id', $row[9])->first();
        $service_exist = Service::where('id', $row[10])->first();

        $validator = Validator::make(['email' => $row[2]], [
            'email' => 'required|email',
        ]);


        if (!$code_exist) {
            if ($validator->passes()) {

                if ($department_exist && $service_exist) {

                    $user = User::create([
                        'first_name' => $row[0],
                        'last_name' => $row[1],
                        'email' => $row[2],
                        'professional_phone_number' => preg_replace('/\s+/','',$row[3]),
                        'matricule' => $row[4],
                        'position' => $row[5],
                        'net_salary' => $row[6],
                        'salary_grade' => $row[7],
                        'contract_end' => $this->transformDate($row[8]),
                        'company_id' => $this->company->id,
                        'department_id' => $row[9],
                        'service_id' => $row[10],
                        'status' => $row[12],
                        'password' => bcrypt($row[13]),
                        'remaining_leave_days' => $row[14],
                        'monthly_leave_allocation' => $row[15],
                        'author_id' => auth()->user()->id,
                        'pdf_password' => Str::random(10),
                    ]);

                    $user->assignRole($row[11]);

                    event(new EmployeeCreated($user, $row[13]));

                    return $user;
                }
            }
        }
    }

    public function transformDate($value, $format = 'Y-m-d')
    {
        try {
            return \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
        } catch (\ErrorException $e) {
            return \Carbon\Carbon::createFromFormat($format, $value);
        }
    }

    public function rules(): array
    {
        return [
            '0' => 'required|string',
            '2' => 'required|unique:users,email',
            '11' => function ($attribute, $value, $onFailure) {
                $array = ['employee', 'supervisor', 'manager'];
                if (!in_array($value, $array)) {
                    $onFailure(__('Role must be one of: employee, supervisor, manager'));
                }
            },
            // Validate company context
            '*' => function ($attribute, $value, $onFailure) {
                if (!$this->company || !$this->company->id) {
                    $onFailure(__('Company context is required for employee import'));
                }
            }
        ];
    }
}
