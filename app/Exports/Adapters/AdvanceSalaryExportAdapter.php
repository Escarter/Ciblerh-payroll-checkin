<?php

namespace App\Exports\Adapters;

use App\Models\AdvanceSalary;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class AdvanceSalaryExportAdapter extends BaseExportAdapter
{
    public function getEntitySlug(): string
    {
        return 'advance_salaries';
    }

    public function getEntityName(): string
    {
        return __('common.advance_salaries');
    }

    public function getColumnDefinitions(): array
    {
        return [
            ['field' => 'employee_name',      'label' => __('common.employee'),          'default' => true],
            ['field' => 'employee_matricule',  'label' => __('common.matricule'),         'default' => true],
            ['field' => 'amount',             'label' => __('common.amount'),              'default' => true],
            ['field' => 'amount_in_words',    'label' => __('common.amount_in_words'),     'default' => false],
            ['field' => 'reason',             'label' => __('common.reason'),              'default' => true],
            ['field' => 'repayment_from',     'label' => __('common.repayment_from'),      'default' => true],
            ['field' => 'repayment_to',       'label' => __('common.repayment_to'),        'default' => true],
            ['field' => 'approval_status',    'label' => __('common.status'),              'default' => true],
            ['field' => 'company',            'label' => __('companies.company'),           'default' => true],
            ['field' => 'created_at',         'label' => __('common.created_at'),          'default' => false],
        ];
    }

    protected function baseQuery(): Builder
    {
        $query = AdvanceSalary::query()->with(['user', 'company']);

        if (!empty($this->context['company_id'])) {
            $query->where('company_id', $this->context['company_id']);
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
                'amount_in_words'    => $model->amount_in_words ?? '',
                'repayment_from'     => $model->repayment_from_month?->format('Y-m') ?? '',
                'repayment_to'       => $model->repayment_to_month?->format('Y-m') ?? '',
                'approval_status'    => $this->approvalLabel($model->approval_status ?? 0),
                'company'            => $model->company?->name ?? '',
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
