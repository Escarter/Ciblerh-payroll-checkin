<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Company;
use App\Models\Department;
use Maatwebsite\Excel\Concerns\FromQuery;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class EmployeeExport implements FromQuery, WithMapping, WithHeadings
{
    use Exportable;

    public $company;
    public $query;

    public function __construct(?Company $company, $query = '')
    {
        $this->company = $company;
        $this->query = $query;
    }

    public function headings(): array
    {
        return [
            'First Name',
            'Last Name',
            'Matricule',
            'Email',
            'Phone Number',
            'Position',
            'Salary Grade',
            'Net Salary',
            'Company',
            'Department',
            'service',
            'status',
            'Date',
        ];
    }

    public function query()
    {
        return User::search($this->query)->when(!empty($this->company) , function($query){
             return $query->where('company_id',$this->company->id);
        });
    }

    /**
     * @var User $user
     */
    public function map($user): array
    {
        return [
            $user->first_name,
            $user->last_name,
            $user->matricule,
            $user->email,
            $user->phone_number,
            $user->position,
            $user->salary_grade,
            $user->net_salary,
            !is_null($user->company)? $user->company->name : '',
            !is_null($user->department)? $user->department->name : '',
            !is_null($user->service)? $user->service->name : '',
            $user->status_text,
            Date::dateTimeToExcel($user->created_at),
        ];
    }
}
