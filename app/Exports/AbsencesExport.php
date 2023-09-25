<?php

namespace App\Exports;

use App\Models\Absence;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class AbsencesExport implements FromQuery, WithMapping, WithHeadings
{
    use Exportable;

    public function headings(): array
    {
        return [
            'Employee',
            'Email',
            'Absence Date',
            'Reason',
            'status',
            'Date',
        ];
    }

    public function query()
    {
        return Absence::query();
    }

    /**
     * @var Absence $absence
     */
    public function map($absence): array
    {
        return [
            $absence->user->name,
            $absence->user->email,
            Date::dateTimeToExcel($absence->absence_date),
            $absence->absence_reason,
            $absence->approval_status === Absence::APPROVAL_STATUS_APPROVED ? 'Approved' : 'Pending or Rejected',
            Date::dateTimeToExcel($absence->created_at),
        ];
    }
}
