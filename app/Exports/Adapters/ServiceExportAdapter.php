<?php

namespace App\Exports\Adapters;

use App\Models\Service;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ServiceExportAdapter extends BaseExportAdapter
{
    public function getEntitySlug(): string
    {
        return 'services';
    }

    public function getEntityName(): string
    {
        return __('services.services');
    }

    public function getColumnDefinitions(): array
    {
        return [
            ['field' => 'name',       'label' => __('common.name'),          'default' => true],
            ['field' => 'company',    'label' => __('companies.company'),     'default' => true],
            ['field' => 'department', 'label' => __('departments.department'), 'default' => true],
            ['field' => 'status',     'label' => __('common.status'),         'default' => true],
            ['field' => 'created_at', 'label' => __('common.created_at'),     'default' => false],
        ];
    }

    protected function baseQuery(): Builder
    {
        $query = Service::query()->with(['company', 'department']);

        if (!empty($this->context['company_id'])) {
            $query->whereHas('department', fn($q) => $q->where('company_id', $this->context['company_id']));
        }
        if (!empty($this->context['department_id'])) {
            $query->where('department_id', $this->context['department_id']);
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
                'company'    => $model->company?->name ?? '',
                'department' => $model->department?->name ?? '',
                'status'     => $model->approvalStatusText('', 'boolean'),
                'created_at' => $model->created_at ? Date::dateTimeToExcel($model->created_at) : '',
                default      => $model->{$col} ?? '',
            };
        }

        return $row;
    }
}
