<?php

namespace App\Livewire\Portal\Roles\Partial;


trait WithRolePermissions
{
    public $selectedRolePermissions = [];
    public $selectAllRolePermissions = false;
    public $RolePermissions = [
        'common.view' => 'role-read',
        'common.update' => 'role-update',
        'common.delete' => 'role-delete',
        'common.create' => 'role-create',
        'common.restore' => 'role-restore',
        'common.bulk_delete' => 'role-bulkdelete',
        'common.bulk_restore' => 'role-bulkrestore',
    ];

    public function rolePermissionClearFields()
    {
        $this->reset([
            'selectedRolePermissions',
            'selectAllRolePermissions',
        ]);
    }

    public function updatedSelectAllRolePermissions($value)
    {
        if ($value) {
            $this->selectedRolePermissions = array_values($this->RolePermissions);
        } else {
            $this->selectedRolePermissions = [];
        }
    }

}