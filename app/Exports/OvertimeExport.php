<?php

namespace App\Exports;

use App\Models\Overtime;
use Maatwebsite\Excel\Concerns\FromQuery;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class OvertimeExport implements FromQuery, WithMapping, WithHeadings
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
            'Start time',
            'End time',
            'Hours Worked',
            'Approval',
            'Approval Reason',
            'Company',
            'Department',
            'Service',
            'Date',
        ];
    }

    public function query()
    {
        return Overtime::search($this->query)->orderBy('start_time', 'desc');
    }

    /**
     * @var Overtime $overtime
     */
    public function map($overtime): array
    {
        return [
            !empty($overtime->user) ? $overtime->user->name : '',
            !empty($overtime->user) ? $overtime->user->email : '',
            !empty($overtime->user) ? $overtime->user->matricule : '',
            $overtime->start_time,
            $overtime->end_time,
            $overtime->time_worked,
            $overtime->approvalStatusText(),
            $overtime->approval_reason,
            !empty($overtime->company) ? $overtime->company->name : '',
            !empty($overtime->department) ? $overtime->department->name : '',
            !empty($overtime->service) ? $overtime->service->name : '',
            Date::dateTimeToExcel($overtime->created_at),
        ];
    }
}