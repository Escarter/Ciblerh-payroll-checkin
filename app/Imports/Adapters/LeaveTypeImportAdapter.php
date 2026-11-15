<?php

namespace App\Imports\Adapters;

use App\Models\LeaveType;

class LeaveTypeImportAdapter extends BaseImportAdapter
{
    public function getEntityName(): string
    {
        return __('leave_types.leave_types');
    }

    public function getEntitySlug(): string
    {
        return 'leave_types';
    }

    public function getUniqueKeys(): array
    {
        return ['name'];
    }

    public function getFieldDefinitions(): array
    {
        return [
            [
                'field' => 'name',
                'label' => __('common.name'),
                'required' => true,
                'type' => 'string',
                'aliases' => ['leave_type', 'type_conge', 'nom', 'leave_name', 'type'],
            ],
            [
                'field' => 'description',
                'label' => __('common.description'),
                'required' => false,
                'type' => 'string',
                'aliases' => ['desc', 'details', 'info'],
            ],
            [
                'field' => 'default_number_of_days',
                'label' => __('leave_types.default_number_of_days'),
                'required' => false,
                'type' => 'numeric',
                'aliases' => ['days', 'max_days', 'jours', 'nombre_jours', 'max_days_per_year', 'default_days'],
                'default' => 0,
            ],
            [
                'field' => 'is_active',
                'label' => __('common.is_active'),
                'required' => false,
                'type' => 'boolean',
                'aliases' => ['active', 'actif', 'status', 'statut'],
                'default' => true,
            ],
        ];
    }

    public function validateRow(array $row, int $rowNumber): array
    {
        $errors = [];

        $name = $this->cleanValue($row['name'] ?? null);
        if (empty($name)) {
            $errors[] = [
                'field' => 'name',
                'message' => __('validation.required', ['attribute' => __('common.name')]),
            ];
        }

        $days = $row['default_number_of_days'] ?? null;
        if ($days !== null && $days !== '' && $this->parseNumeric($days) === null) {
            $errors[] = [
                'field' => 'default_number_of_days',
                'message' => __('validation.numeric', ['attribute' => __('leave_types.default_number_of_days')]),
            ];
        }

        return $errors;
    }

    public function validateRelationships(array $row, int $rowNumber): array
    {
        // LeaveType has no FK relationships
        return [];
    }

    public function transformRow(array $row): array
    {
        return [
            'name' => $this->cleanValue($row['name']),
            'description' => $this->cleanValue($row['description'] ?? null) ?? '',
            'default_number_of_days' => $this->parseNumeric($row['default_number_of_days'] ?? null) ?? 0,
            'is_active' => $this->parseBoolean($row['is_active'] ?? null) ?? true,
            'author_id' => $this->user?->id ?? auth()->id(),
        ];
    }

    public function createOrUpdateRecord(array $data)
    {
        $lookupBy = ['name' => $data['name']];

        if ($this->importMode === self::MODE_CREATE_ONLY) {
            $existing = LeaveType::where($lookupBy)->first();
            if ($existing) {
                return null;
            }
            $leaveType = LeaveType::create($data);
            $leaveType->wasRecentlyCreated = true;
            return $leaveType;
        }

        if ($this->importMode === self::MODE_UPDATE_ONLY) {
            $existing = LeaveType::where($lookupBy)->first();
            if (!$existing) {
                return null;
            }
            $existing->update($data);
            return $existing;
        }

        return LeaveType::updateOrCreate($lookupBy, $data);
    }
}
