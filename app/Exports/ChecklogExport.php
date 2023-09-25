<?php

namespace App\Exports;

use App\Models\Ticking;
use Maatwebsite\Excel\Concerns\FromQuery;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class ChecklogExport implements FromQuery, WithMapping, WithHeadings
{
    use Exportable;

    public $query;

    public function __construct($query = '')
    {
        $this->query = $query;
    }

    public function headings(): array
    {
        return [
            'Employee Name',
            'Employee Email',
            'Employee Matricule',
            'Checkin time',
            'Checkout time',
            'Hours Worked',
            'Supervisor Approval',
            'Supervisor Approval Reason',
            'Manager Approval',
            'Manager Approval Reason',
            'Company',
            'Department',
            'Service',
            'Date',
        ];
    }

    public function query()
    {
        return Ticking::search($this->query)->orderBy('start_time','desc');
    }

    /**
     * @var Ticking $ticking
     */
    public function map($ticking): array
    {
        return [
            $ticking->user_full_name,
            $ticking->email,
            $ticking->matricule,
            $ticking->start_time,
            $ticking->end_time,
            $ticking->time_worked,
            $ticking->approvalStatusText('supervisor'),
            $ticking->supervisor_approval_reason,
            $ticking->approvalStatusText('manager'),
            $ticking->manager_approval_reason,
            $ticking->company_name,
            $ticking->department_name,
            $ticking->service_name,
            Date::dateTimeToExcel($ticking->created_at),
        ];
    }
}