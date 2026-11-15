<?php

namespace App\Exports\Adapters;

use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class DepartmentExportAdapter extends BaseExportAdapter
{
    public function getEntitySlug(): string
    {
        return 'departments';
    }

    public function getEntityName(): string
    {
        return __('departments.departments');
    }

    public function getColumnDefinitions(): array
    {
        return [
            ['field' => 'name',            'label' => __('common.name'),               'default' => true],
            ['field' => 'company',         'label' => __('companies.company'),          'default' => true],
            ['field' => 'supervisor',      'label' => __('common.supervisor'),          'default' => true],
            ['field' => 'total_employees', 'label' => __('common.employees'),           'default' => true],
            ['field' => 'total_services',  'label' => __('services.services'),          'default' => true],
            ['field' => 'status',          'label' => __('common.status'),              'default' => true],
            ['field' => 'created_at',      'label' => __('common.created_at'),          'default' => false],
        ];
    }

    protected function baseQuery(): Builder
    {
        $query = Department::query()
            ->with(['company', 'depSupervisor.supervisor'])
            ->withCount(['employees', 'services']);

        if (!empty($this->context['company_id'])) {
            $query->where('company_id', $this->context['company_id']);
        }

        if (!empty($this->searchQuery)) {
            $query->where('name', 'like', "%{$this->searchQuery}%");
        }

        return $query;
    }

    protected function mapRow($model): array
    {
        $cols = $this->effectiveColumns();
        $row = [];

        foreach ($cols as $col) {
            $row[] = match ($col) {
                'company'         => $model->company?->name ?? '',
                'supervisor'      => $model->depSupervisor?->supervisor?->name ?? '',
                'total_employees' => $model->employees_count ?? 0,
                'total_services'  => $model->services_count ?? 0,
                'status'          => $model->approvalStatusText('', 'boolean'),
                'created_at'      => $model->created_at ? Date::dateTimeToExcel($model->created_at) : '',
                default           => $model->{$col} ?? '',
            };
        }

        return $row;
    }
}
