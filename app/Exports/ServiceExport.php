<?php

namespace App\Exports;

use App\Models\Service;
use App\Models\Department;
use Maatwebsite\Excel\Concerns\FromQuery;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class ServiceExport implements FromQuery, WithMapping, WithHeadings
{
    use Exportable;

    public $department;
    public $query;

    public function __construct(?Department $department, $query = '')
    {
        $this->department = $department;
        $this->query = $query;
    }

    public function headings(): array
    {
        return [
            'Name',
            'Company',
            'Department',
            'status',
            'Date',
        ];
    }

    public function query()
    {
        return Service::search($this->query)->when(!empty($this->department), function ($query) {
            return $query->where('department_id', $this->department->id);
        });
    }

    /**
     * @var Service $service
     */
    public function map($service): array
    {
        return [
            $service->name,
            !is_null($service->company) ? $service->company->name : '',
            !is_null($service->department) ? $service->department->name : '',
            $service->approvalStatusText('', 'boolean'),
            Date::dateTimeToExcel($service->created_at),
        ];
    }
}
