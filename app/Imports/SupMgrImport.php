<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Collection;
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

class SupMgrImport implements ToModel, WithStartRow, SkipsEmptyRows, WithValidation, SkipsOnError, SkipsOnFailure
{
    use Importable, SkipsErrors, SkipsFailures;

    /**
     * @return int
     */
    public function startRow(): int
    {
        return 2;
    }


    public function __construct()
    {
        
    }
    
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {

        $code_exist = User::where('email', $row[2])->first();
        if (!$code_exist ) {

            $user = User::create([
                'first_name' => $row[0],
                'last_name' => $row[1],
                'email' => $row[2],
                'phone_number' => $row[3],
                'matricule' => $row[4],
                'position' => $row[5],
                'net_salary' => $row[6],
                'salary_grade' => $row[7],
                'contract_end' => $this->transformDate($row[8]),
                'status' => $row[9],
                'password' => bcrypt($row[10]),
                'author_id' => auth()->user()->id,
            ]);
            $user->assignRole($row[11]);

            return $user;

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
        ];
    }

    /**
     * Custom attribute names for validation error messages
     */
    public function customValidationAttributes(): array
    {
        return [
            '0' => __('employees.first_name'),
            '1' => __('employees.last_name'),
            '2' => __('employees.email'),
            '3' => __('common.phone_number'),
            '4' => __('employees.matricule'),
            '5' => __('common.position'),
            '6' => __('employees.net_salary'),
            '7' => __('employees.salary_grade'),
            '8' => __('employees.contract_end_date'),
            '9' => __('common.status'),
            '10' => __('common.password'),
            '11' => __('employees.role'),
        ];
    }
}