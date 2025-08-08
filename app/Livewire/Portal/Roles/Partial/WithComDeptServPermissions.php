<?php

namespace App\Livewire\Portal\Roles\Partial;


trait WithComDeptServPermissions
{
    public $selectedCompanyPermissions = [];
    public $selectAllCompanyPermissions = false;
    public $CompanyPermissions = [
        'View' => 'company-read',
        'Update' => 'company-update',
        'Delete' => 'company-delete',
        'Create' => 'company-create',
        'Import' => 'company-import',
        'Export' => 'company-export',
    ];

    public $selectedDepartmentPermissions = [];
    public $selectAllDepartmentPermissions = false;
    public $DepartmentPermissions = [
        'View' => 'department-read',
        'Update' => 'department-update',
        'Delete' => 'department-delete',
        'Create' => 'department-create',
        'Import' => 'department-import',
        'Export' => 'department-export',
    ];

    public $selectedServicePermissions = [];
    public $selectAllServicePermissions = false;
    public $ServicePermissions = [
        'View' => 'service-read',
        'Update' => 'service-update',
        'Delete' => 'service-delete',
        'Create' => 'service-create',
        'Import' => 'service-import',
        'Export' => 'service-export',
    ];

    public function compDeptServPermissionClearFields()
    {
        $this->reset([
            'selectedCompanyPermissions',
            'selectAllCompanyPermissions',
            'selectedDepartmentPermissions',
            'selectAllDepartmentPermissions',
            'selectedServicePermissions',
            'selectAllServicePermissions',

        ]);
    }
    public function updatedSelectAllCompanyPermissions($value)
    {
        if ($value) {
            $this->selectedCompanyPermissions = array_values($this->CompanyPermissions);
        } else {
            $this->selectedCompanyPermissions = [];
        }
    }
    public function updatedSelectAllDepartmentPermissions($value)
    {
        if ($value) {
            $this->selectedDepartmentPermissions = array_values($this->DepartmentPermissions);
        } else {
            $this->selectedDepartmentPermissions = [];
        }
    }
    public function updatedSelectAllServicePermissions($value)
    {
        if ($value) {
            $this->selectedServicePermissions = array_values($this->ServicePermissions);
        } else {
            $this->selectedServicePermissions = [];
        }
    }

}