<?php

namespace App\Exports\Adapters;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class CompanyExportAdapter extends BaseExportAdapter
{
    public function getEntitySlug(): string
    {
        return 'companies';
    }

    public function getEntityName(): string
    {
        return __('common.companies');
    }

    public function getColumnDefinitions(): array
    {
        return [
            ['field' => 'name',            'label' => __('common.name'),        'default' => true],
            ['field' => 'code',            'label' => __('common.code'),        'default' => true],
            ['field' => 'description',     'label' => __('common.description'), 'default' => true],
            ['field' => 'sector',          'label' => __('common.sector'),      'default' => true],
            ['field' => 'total_employees', 'label' => __('common.employees'),   'default' => true],
            ['field' => 'total_depts',     'label' => __('departments.departments'), 'default' => true],
            ['field' => 'status',          'label' => __('common.status'),      'default' => true],
            ['field' => 'created_at',      'label' => __('common.created_at'),  'default' => false],
        ];
    }

    protected function baseQuery(): Builder
    {
        $query = Company::query()->withCount(['employees', 'departments']);

        if (!empty($this->searchQuery)) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->searchQuery}%")
                  ->orWhere('code', 'like', "%{$this->searchQuery}%");
            });
        }

        return $query;
    }

    protected function mapRow($model): array
    {
        $cols = $this->effectiveColumns();
        $row = [];

        foreach ($cols as $col) {
            $row[] = match ($col) {
                'total_employees' => $model->employees_count ?? 0,
                'total_depts'     => $model->departments_count ?? 0,
                'status'          => $model->is_active ? __('common.active') : __('common.inactive'),
                'created_at'      => $model->created_at ? Date::dateTimeToExcel($model->created_at) : '',
                default           => $model->{$col} ?? '',
            };
        }

        return $row;
    }
}
