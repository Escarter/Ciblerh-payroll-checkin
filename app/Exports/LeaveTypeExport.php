<?php

namespace App\Exports;

use App\Models\LeaveType;
use Maatwebsite\Excel\Concerns\FromQuery;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class LeaveTypeExport implements FromQuery, WithMapping, WithHeadings
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
            'ID',
            'Name',
            'Description',
            'Default Number of Days',
            'Status',
            'Date',
        ];
    }

    public function query()
    {
        return LeaveType::search($this->query);
    }

    /**
     * @var LeaveType $leaveType
     */
    public function map($leaveType): array
    {
        return [
            $leaveType->id,
            $leaveType->name,
            $leaveType->description,
            $leaveType->default_number_of_days,
            $leaveType->approvalStatusText('', 'boolean'),
            Date::dateTimeToExcel($leaveType->created_at),
        ];
    }
}