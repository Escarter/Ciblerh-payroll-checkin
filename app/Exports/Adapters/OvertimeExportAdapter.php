<?php

namespace App\Exports\Adapters;

use App\Models\Overtime;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class OvertimeExportAdapter extends BaseExportAdapter
{
    public function getEntitySlug(): string
    {
        return 'overtimes';
    }

    public function getEntityName(): string
    {
        return __('common.overtimes');
    }

    public function getColumnDefinitions(): array
    {
        return [
            ['field' => 'employee_name',     'label' => __('common.employee'),      'default' => true],
            ['field' => 'employee_matricule', 'label' => __('common.matricule'),     'default' => true],
            ['field' => 'start_time',        'label' => __('common.start_time'),     'default' => true],
            ['field' => 'end_time',          'label' => __('common.end_time'),       'default' => true],
            ['field' => 'time_worked',       'label' => __('common.time_worked'),    'default' => true],
            ['field' => 'reason',            'label' => __('common.reason'),         'default' => true],
            ['field' => 'approval_status',   'label' => __('common.status'),         'default' => true],
            ['field' => 'company',           'label' => __('companies.company'),      'default' => true],
            ['field' => 'department',        'label' => __('departments.department'), 'default' => false],
            ['field' => 'created_at',        'label' => __('common.created_at'),     'default' => false],
        ];
    }

    protected function baseQuery(): Builder
    {
        $query = Overtime::query()->with(['user', 'company', 'department']);

        if (!empty($this->context['company_id'])) {
            $query->where('company_id', $this->context['company_id']);
        }

        if (!empty($this->context['department_id'])) {
            $query->where('department_id', $this->context['department_id']);
        }

        if (!empty($this->searchQuery)) {
            $query->where(function ($q) {
                $q->whereHas('user', function ($uq) {
                    $uq->where('first_name', 'like', "%{$this->searchQuery}%")
                       ->orWhere('last_name', 'like', "%{$this->searchQuery}%")
                       ->orWhere('matricule', 'like', "%{$this->searchQuery}%");
                });
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
                'employee_name'      => $model->user ? trim($model->user->first_name . ' ' . $model->user->last_name) : '',
                'employee_matricule' => $model->user?->matricule ?? '',
                'start_time'         => $model->start_time?->format('Y-m-d H:i') ?? '',
                'end_time'           => $model->end_time?->format('Y-m-d H:i') ?? '',
                'time_worked'        => $model->time_worked ?? '',
                'approval_status'    => $this->approvalLabel($model->approval_status ?? 0),
                'company'            => $model->company?->name ?? '',
                'department'         => $model->department?->name ?? '',
                'created_at'         => $model->created_at ? Date::dateTimeToExcel($model->created_at) : '',
                default              => $model->{$col} ?? '',
            };
        }

        return $row;
    }

    private function approvalLabel(int $status): string
    {
        return match ($status) {
            1       => __('common.approved'),
            2       => __('common.rejected'),
            default => __('common.pending'),
        };
    }
}
