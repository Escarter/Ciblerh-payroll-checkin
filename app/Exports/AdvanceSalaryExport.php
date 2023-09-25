<?php

namespace App\Exports;

use App\Models\AdvanceSalary;
use Maatwebsite\Excel\Concerns\FromQuery;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class AdvanceSalaryExport implements FromQuery, WithMapping, WithHeadings
{
    use Exportable;

    public function headings(): array
    {
        return [
            'Employee',
            'Email',
            'Phone',
            'Net Salary',
            'Company',
            'Department',
            'Amount',
            'Reason',
            'Repayment Start',
            'Repayment End',
            'Beneficiary Name',
            'Beneficiary MoMo Number',
            'Beneficiary ID Number',
            'Approval status',
            'Date',
        ];
    }

    public function query()
    {
        return AdvanceSalary::query();
    }

    /**
     * @var AdvanceSalary $advance_salary
     */
    public function map($advance_salary): array
    {
        
        return [
            $advance_salary->user->name,
            $advance_salary->user->email,
            $advance_salary->user->phone_number,
            $advance_salary->user->net_salary,
            !is_null($advance_salary->user->company) ? $advance_salary->user->company->name : '',
            !is_null($advance_salary->user->department) ? $advance_salary->user->department->name : '',
            $advance_salary->amount,
            $advance_salary->reason,
            $advance_salary->repayment_from_month->format('Y-m'),
            $advance_salary-> repayment_to_month->format('Y-m'),
            $advance_salary->beneficiary_name,
            $advance_salary->beneficiary_mobile_money_number,
            $advance_salary->beneficiary_id_card_number,
            $advance_salary->approvalStatusText(),
            Date::dateTimeToExcel($advance_salary->created_at),
        ];
    }
}
