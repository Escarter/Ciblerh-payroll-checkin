<?php

namespace App\Livewire\Portal\Roles\Partial;


trait WithComDeptServPermissions
{
    public $selectedCompanyPermissions = [];
    public $selectAllCompanyPermissions = false;
    public $CompanyPermissions = [
        'common.view' => 'company-read',
        'common.update' => 'company-update',
        'common.delete' => 'company-delete',
        'common.create' => 'company-create',
        'common.import' => 'company-import',
        'common.export' => 'company-export',
        'common.restore' => 'company-restore',
        'common.bulk_delete' => 'company-bulkdelete',
        'common.bulk_restore' => 'company-bulkrestore',
    ];

    public $selectedDepartmentPermissions = [];
    public $selectAllDepartmentPermissions = false;
    public $DepartmentPermissions = [
        'common.view' => 'department-read',
        'common.update' => 'department-update',
        'common.delete' => 'department-delete',
        'common.create' => 'department-create',
        'common.import' => 'department-import',
        'common.export' => 'department-export',
        'common.restore' => 'department-restore',
        'common.bulk_delete' => 'department-bulkdelete',
        'common.bulk_restore' => 'department-bulkrestore',
    ];

    public $selectedServicePermissions = [];
    public $selectAllServicePermissions = false;
    public $ServicePermissions = [
        'common.view' => 'service-read',
        'common.update' => 'service-update',
        'common.delete' => 'service-delete',
        'common.create' => 'service-create',
        'common.import' => 'service-import',
        'common.export' => 'service-export',
        'common.restore' => 'service-restore',
        'common.bulk_delete' => 'service-bulkdelete',
        'common.bulk_restore' => 'service-bulkrestore',
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