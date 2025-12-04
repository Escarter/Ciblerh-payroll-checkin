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
            __('companies.export_name'),
            __('companies.export_code'),
            __('companies.export_description'),
            __('companies.export_sector'),
            __('companies.export_total_departments'),
            __('companies.export_total_services'),
            __('companies.export_total_employees'),
            __('companies.export_status'),
            __('companies.export_date'),
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
