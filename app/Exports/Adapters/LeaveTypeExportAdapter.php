<?php

namespace App\Exports\Adapters;

use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Builder;

class LeaveTypeExportAdapter extends BaseExportAdapter
{
    public function getEntitySlug(): string
    {
        return 'leave_types';
    }

    public function getEntityName(): string
    {
        return __('leave_types.leave_types');
    }

    public function getColumnDefinitions(): array
    {
        return [
            ['field' => 'name',                   'label' => __('common.name'),                    'default' => true],
            ['field' => 'description',            'label' => __('common.description'),             'default' => true],
            ['field' => 'default_number_of_days', 'label' => __('leave_types.default_days'),       'default' => true],
            ['field' => 'status',                 'label' => __('common.status'),                  'default' => true],
        ];
    }

    protected function baseQuery(): Builder
    {
        $query = LeaveType::query();

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
                'status' => $model->is_active ? __('common.active') : __('common.inactive'),
                default  => $model->{$col} ?? '',
            };
        }

        return $row;
    }
}
