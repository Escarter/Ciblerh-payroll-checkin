<?php

namespace App\Livewire\Portal\Roles\Partial;


trait WithAdvanceSalAndAbsencesPermissions
{
    public $selectedAdvanceSalaryPermissions = [];
    public $selectAllAdvanceSalaryPermissions = false;
    public $AdvanceSalaryPermissions = [
        'View' => 'advance_salary-read',
        'Update' => 'advance_salary-update',
        'Create' => 'advance_salary-create',
        'Delete' => 'advance_salary-delete',
        'Export' => 'advance_salary-export',
    ];

    public $selectedAbsencePermissions = [];
    public $selectAllAbsencePermissions = false;
    public $AbsencePermissions = [
        'View' => 'absence-read',
        'Update' => 'absence-update',
        'Delete' => 'absence-delete',
        'Create' => 'absence-create',
        'Export' => 'absence-export',
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
