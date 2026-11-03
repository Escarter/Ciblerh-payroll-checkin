<?php

namespace App\Livewire\Portal\Roles\Partial;


trait WithAdvanceSalAndAbsencesPermissions
{
    public $selectedAdvanceSalaryPermissions = [];
    public $selectAllAdvanceSalaryPermissions = false;
    public $AdvanceSalaryPermissions = [
        'common.view' => 'advance_salary-read',
        'common.update' => 'advance_salary-update',
        'common.create' => 'advance_salary-create',
        'common.delete' => 'advance_salary-delete',
        'common.export' => 'advance_salary-export',
        'common.restore' => 'advance_salary-restore',
        'common.bulk_delete' => 'advance_salary-bulkdelete',
        'common.bulk_restore' => 'advance_salary-bulkrestore',
    ];

    public $selectedAbsencePermissions = [];
    public $selectAllAbsencePermissions = false;
    public $AbsencePermissions = [
        'common.view' => 'absence-read',
        'common.update' => 'absence-update',
        'common.delete' => 'absence-delete',
        'common.create' => 'absence-create',
        'common.export' => 'absence-export',
        'common.restore' => 'absence-restore',
        'common.bulk_delete' => 'absence-bulkdelete',
        'common.bulk_restore' => 'absence-bulkrestore',
    ];


    public function advanceSalaryAndAbsencePermissionClearFields()
    {
        $this->reset([
            'selectedAdvanceSalaryPermissions',
            'selectAllAdvanceSalaryPermissions',
            'selectedAbsencePermissions',
            'selectAllAbsencePermissions',
        ]);
    }
    public function updatedSelectAllAdvanceSalaryPermissions($value)
    {
        if ($value) {
            $this->selectedAdvanceSalaryPermissions = array_values($this->AdvanceSalaryPermissions);
        } else {
            $this->selectedAdvanceSalaryPermissions = [];
        }
    }
    public function updatedSelectAllAbsencePermissions($value)
    {
        if ($value) {
            $this->selectedAbsencePermissions = array_values($this->AbsencePermissions);
        } else {
            $this->selectedAbsencePermissions = [];
        }
    }
}
