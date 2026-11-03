<?php

namespace App\Exports\Adapters;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class EmployeeExportAdapter extends BaseExportAdapter
{
    public function getEntitySlug(): string
    {
        return 'employees';
    }

    public function getEntityName(): string
    {
        return __('common.employees');
    }

    public function getColumnDefinitions(): array
    {
        return [
            ['field' => 'matricule',                  'label' => __('common.matricule'),               'default' => true],
            ['field' => 'first_name',                 'label' => __('common.first_name'),              'default' => true],
            ['field' => 'last_name',                  'label' => __('common.last_name'),               'default' => true],
            ['field' => 'email',                      'label' => __('common.email'),                   'default' => true],
            ['field' => 'username',                   'label' => __('common.username'),                'default' => false],
            ['field' => 'pdf_password',               'label' => __('common.pdf_password'),            'default' => false],
            ['field' => 'professional_phone_number',  'label' => __('common.professional_phone'),      'default' => true],
            ['field' => 'personal_phone_number',      'label' => __('common.personal_phone'),          'default' => false],
            ['field' => 'position',                   'label' => __('common.position'),                'default' => true],
            ['field' => 'salary_grade',               'label' => __('common.salary_grade'),            'default' => false],
            ['field' => 'net_salary',                 'label' => __('common.net_salary'),              'default' => false],
            ['field' => 'company',                    'label' => __('companies.company'),              'default' => true],
            ['field' => 'department',                 'label' => __('departments.department'),          'default' => true],
            ['field' => 'service',                    'label' => __('services.service'),               'default' => true],
            ['field' => 'remaining_leave_days',       'label' => __('common.remaining_leave_days'),    'default' => false],
            ['field' => 'contract_end',               'label' => __('common.contract_end'),            'default' => false],
            ['field' => 'work_start_time',            'label' => __('common.work_start_time'),         'default' => false],
            ['field' => 'work_end_time',              'label' => __('common.work_end_time'),           'default' => false],
            ['field' => 'status',                     'label' => __('common.status'),                  'default' => true],
            ['field' => 'created_at',                 'label' => __('common.created_at'),              'default' => false],
        ];
    }

    protected function baseQuery(): Builder
    {
        $query = User::query()->with(['company', 'department', 'service']);

        if (!empty($this->context['company_id'])) {
            $query->where('company_id', $this->context['company_id']);
        }
        if (!empty($this->context['department_id'])) {
            $query->where('department_id', $this->context['department_id']);
        }
        if (!empty($this->context['service_id'])) {
            $query->where('service_id', $this->context['service_id']);
        }
        if (!empty($this->searchQuery)) {
            $query->where(function ($q) {
                $q->where('first_name', 'like', "%{$this->searchQuery}%")
                  ->orWhere('last_name', 'like', "%{$this->searchQuery}%")
                  ->orWhere('email', 'like', "%{$this->searchQuery}%")
                  ->orWhere('matricule', 'like', "%{$this->searchQuery}%");
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
                'company'    => $model->company?->name ?? '',
                'department' => $model->department?->name ?? '',
                'service'    => $model->service?->name ?? '',
                'status'     => $model->status_text ?? ($model->is_active ? 'Active' : 'Inactive'),
                'created_at' => $model->created_at ? Date::dateTimeToExcel($model->created_at) : '',
                default      => $model->{$col} ?? '',
            };
        }

        return $row;
    }
}
