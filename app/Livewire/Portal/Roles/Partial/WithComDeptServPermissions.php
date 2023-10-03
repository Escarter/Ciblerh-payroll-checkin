<?php

namespace App\Livewire\Portal\Roles\Partial;


trait WithComDeptServPermissions
{
    public $selectedCompanyPermissions = [];
    public $selectAllCompanyPermissions = false;
    public $CompanyPermissions = [
        'View' => 'comapany-read',
        'Update' => 'comapany-update',
        'Delete' => 'comapany-delete',
        'Create' => 'comapany-create',
        'Import' => 'comapany-import',
        'Export' => 'comapany-export',
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
            $this->selectedCompanyPermissions = [
                'comapany-create',
                'comapany-read',
                'comapany-update',
                'comapany-delete',
                'comapany-import',
                'comapany-export',
            ];
        } else {
            $this->selectedCompanyPermissions = [];
        }
    }
    public function updatedSelectAllDepartmentPermissions($value)
    {
        if ($value) {
            $this->selectedDepartmentPermissions = [
                'department-create',
                'department-read',
                'department-update',
                'department-delete',
                'department-import',
                'department-export',
            ];
        } else {
            $this->selectedDepartmentPermissions = [];
        }
    }
    public function updatedSelectAllServicePermissions($value)
    {
        if ($value) {
            $this->selectedServicePermissions = [
                'service-create',
                'service-read',
                'service-update',
                'service-delete',
                'service-import',
                'service-export',
            ];
        } else {
            $this->selectedServicePermissions = [];
        }
    }

}