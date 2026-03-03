<?php

namespace App\Exports\Adapters;

use App\Models\Leave;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class LeaveExportAdapter extends BaseExportAdapter
{
    public function getEntitySlug(): string
    {
        return 'leaves';
    }

    public function getEntityName(): string
    {
        return __('common.leaves');
    }

    public function getColumnDefinitions(): array
    {
        return [
            ['field' => 'employee_name',       'label' => __('common.employee'),          'default' => true],
            ['field' => 'employee_matricule',   'label' => __('common.matricule'),         'default' => true],
            ['field' => 'leave_type',           'label' => __('leave_types.leave_type'),   'default' => true],
            ['field' => 'start_date',           'label' => __('common.start_date'),        'default' => true],
            ['field' => 'end_date',             'label' => __('common.end_date'),          'default' => true],
            ['field' => 'number_of_days',       'label' => __('common.number_of_days'),    'default' => true],
            ['field' => 'reason',               'label' => __('common.reason'),            'default' => false],
            ['field' => 'supervisor_approval',  'label' => __('common.supervisor_approval'), 'default' => true],
            ['field' => 'manager_approval',     'label' => __('common.manager_approval'),  'default' => true],
            ['field' => 'company',              'label' => __('companies.company'),         'default' => true],
            ['field' => 'department',           'label' => __('departments.department'),    'default' => false],
            ['field' => 'created_at',           'label' => __('common.created_at'),        'default' => false],
        ];
    }

    protected function baseQuery(): Builder
    {
        $query = Leave::query()->with(['user', 'leaveType', 'company', 'department']);

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
                'leave_type'         => $model->leaveType?->name ?? '',
                'start_date'         => $model->start_date?->format('Y-m-d') ?? '',
                'end_date'           => $model->end_date?->format('Y-m-d') ?? '',
                'supervisor_approval' => $this->approvalLabel($model->supervisor_approval ?? 0),
                'manager_approval'    => $this->approvalLabel($model->manager_approval ?? 0),
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
