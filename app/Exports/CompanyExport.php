<?php

namespace App\Exports;

use App\Models\Company;
use Maatwebsite\Excel\Concerns\FromQuery;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class CompanyExport implements FromQuery, WithMapping, WithHeadings
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
            'Name',
            'Code',
            'Description',
            'Sector',
            'Total Departments',
            'Total Services',
            'Total Employees',
            'status',
            'Date',
        ];
    }

    public function query()
    {
        return Company::search($this->query);
    }

    /**
     * @var Department $company
     */
    public function map($company): array
    {
        return [
            $company->name,
            $company->code,
            $company->description,
            $company->sector,
            $company->departments()->count(),
            $company->services()->count(),
            $company->employees()->count(),
            $company->approvalStatusText('', 'boolean'),
            Date::dateTimeToExcel($company->created_at),
        ];
    }
}
