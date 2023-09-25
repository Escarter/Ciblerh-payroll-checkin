<?php

namespace App\Exports;

use App\Models\Company;
use App\Models\Department;
use Maatwebsite\Excel\Concerns\FromQuery;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class DepartmentExport  implements FromQuery, WithMapping, WithHeadings
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
            'ID',
            'Name',
            'Company',
            'Total Employees',
            'Total Services',
            'Supervisor',
            'status',
            'Date',
        ];
    }

    public function query()
    {
        return Department::search($this->query)->when(!empty($this->company), function ($query) {
            return $query->where('company_id', $this->company->id);
        });
    }

    /**
     * @var Department $department
     */
    public function map($department): array
    {
        return [
            $department->id,
            $department->name,
            !is_null($department->company) ? $department->company->name : '',
            $department->employees()->count(),
            $department->services()->count(),
            !is_null($department->depSupervisor) ? (!is_null($department->depSupervisor->supervisor) ? $department->depSupervisor->supervisor->name : '') : '',
            $department->approvalStatusText('', 'boolean'),
            Date::dateTimeToExcel($department->created_at),
        ];
    }
}
