<?php

namespace App\Exports\Adapters;

use App\Models\Payslip;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PayslipExportAdapter extends BaseExportAdapter
{
    public function getEntitySlug(): string
    {
        return 'payslips';
    }

    public function getEntityName(): string
    {
        return __('common.payslips');
    }

    public function getColumnDefinitions(): array
    {
        return [
            ['field' => 'employee_name',     'label' => __('common.employee'),         'default' => true],
            ['field' => 'employee_matricule', 'label' => __('common.matricule'),        'default' => true],
            ['field' => 'month',             'label' => __('common.month'),             'default' => true],
            ['field' => 'year',              'label' => __('common.year'),              'default' => true],
            ['field' => 'email_status',      'label' => __('common.email_status'),      'default' => true],
            ['field' => 'sms_status',        'label' => __('common.sms_status'),        'default' => false],
            ['field' => 'encryption_status', 'label' => __('common.encryption_status'), 'default' => false],
            ['field' => 'company',           'label' => __('companies.company'),         'default' => true],
            ['field' => 'department',        'label' => __('departments.department'),    'default' => false],
            ['field' => 'created_at',        'label' => __('common.created_at'),        'default' => false],
        ];
    }

    protected function baseQuery(): Builder
    {
        $query = Payslip::query()->with(['employee', 'company', 'department']);

        if (!empty($this->context['company_id'])) {
            $query->where('company_id', $this->context['company_id']);
        }

        if (!empty($this->context['department_id'])) {
            $query->where('department_id', $this->context['department_id']);
        }

        if (!empty($this->searchQuery)) {
            $query->where(function ($q) {
                $q->whereHas('employee', function ($uq) {
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
                'employee_name'      => $model->employee ? trim($model->employee->first_name . ' ' . $model->employee->last_name) : '',
                'employee_matricule' => $model->employee?->matricule ?? '',
                'email_status'       => $model->email_status_text ?? '',
                'sms_status'         => $model->sms_status_text ?? '',
                'encryption_status'  => $model->encryption_status_text ?? '',
                'company'            => $model->company?->name ?? '',
                'department'         => $model->department?->name ?? '',
                'created_at'         => $model->created_at ? Date::dateTimeToExcel($model->created_at) : '',
                default              => $model->{$col} ?? '',
            };
        }

        return $row;
    }
}
