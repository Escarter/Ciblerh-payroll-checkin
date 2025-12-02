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
    public $user;

    public function __construct(?Company $company, $query = '', $user = null)
    {
        $this->company = $company;
        $this->query = $query;
        $this->user = $user;
    }

    public function headings(): array
    {
        return [
            'Matricule',
            'First Name',
            'Last Name',
            'Email',
            'Professional phone_number',
            'Personal phone_number',
            'Position',
            'Salary Grade',
            'Net Salary',
            'Company',
            'Department',
            'service',
            'PDF_Password',
            'Remaining leave_days',
            'Monthly leave_allocation',
            'Contract_end',
            'Work start_time',
            'Work end_time',
            'status',
            'Receive SMS Notifications',
            'Created Date',
        ];
    }

    public function query()
    {
        return User::search($this->query, $this->user)->when(!empty($this->company) , function($query){
             return $query->where('company_id',$this->company->id);
        });
    }

    /**
     * @var User $user
     */
    public function map($user): array
    {
        return [
            $user->matricule,
            $user->first_name,
            $user->last_name,
            $user->email,
            $user->professional_phone_number,
            $user->personal_phone_number,
            $user->position,
            $user->salary_grade,
            $user->net_salary,
            !is_null($user->company)? $user->company->name : '',
            !is_null($user->department)? $user->department->name : '',
            !is_null($user->service)? $user->service->name : '',
            $user->pdf_password,
            $user->remaining_leave_days,
            $user->monthly_leave_allocation,
            $user->contract_end,
            $user->work_start_time,
            $user->work_end_time,
            $user->status_text,
            $user->receive_sms_notifications ? 'Yes' : 'No',
            Date::dateTimeToExcel($user->created_at),
        ];
    }
}
