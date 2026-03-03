<?php

namespace App\Exports\Adapters;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class AuditLogExportAdapter extends BaseExportAdapter
{
    public function getEntitySlug(): string
    {
        return 'audit_logs';
    }

    public function getEntityName(): string
    {
        return __('common.audit_logs');
    }

    public function getColumnDefinitions(): array
    {
        return [
            ['field' => 'user_name',    'label' => __('common.user'),        'default' => true],
            ['field' => 'action_type',  'label' => __('common.action'),      'default' => true],
            ['field' => 'description',  'label' => __('common.description'), 'default' => true],
            ['field' => 'ip_address',   'label' => __('common.ip_address'),  'default' => true],
            ['field' => 'old_values',   'label' => __('common.old_values'),  'default' => false],
            ['field' => 'new_values',   'label' => __('common.new_values'),  'default' => false],
            ['field' => 'created_at',   'label' => __('common.created_at'),  'default' => true],
        ];
    }

    protected function baseQuery(): Builder
    {
        $query = AuditLog::query()->with(['user']);

        if (!empty($this->searchQuery)) {
            $query->where(function ($q) {
                $q->where('action_type', 'like', "%{$this->searchQuery}%")
                  ->orWhere('description', 'like', "%{$this->searchQuery}%")
                  ->orWhereHas('user', function ($uq) {
                      $uq->where('first_name', 'like', "%{$this->searchQuery}%")
                         ->orWhere('last_name', 'like', "%{$this->searchQuery}%");
                  });
            });
        }

        return $query->latest();
    }

    protected function mapRow($model): array
    {
        $cols = $this->effectiveColumns();
        $row = [];

        foreach ($cols as $col) {
            $row[] = match ($col) {
                'user_name'   => $model->user ? trim($model->user->first_name . ' ' . $model->user->last_name) : __('common.system'),
                'action_type' => $model->translated_action_type ?? $model->action_type ?? '',
                'old_values'  => is_array($model->old_values) ? json_encode($model->old_values, JSON_UNESCAPED_UNICODE) : ($model->old_values ?? ''),
                'new_values'  => is_array($model->new_values) ? json_encode($model->new_values, JSON_UNESCAPED_UNICODE) : ($model->new_values ?? ''),
                'created_at'  => $model->created_at ? Date::dateTimeToExcel($model->created_at) : '',
                default       => $model->{$col} ?? '',
            };
        }

        return $row;
    }
}
