<?php

namespace App\Livewire\Portal\Roles\Partial;


trait WithRolePermissions
{
    public $selectedRolePermissions = [];
    public $selectAllRolePermissions = false;
    public $RolePermissions = [
        'View' => 'role-read',
        'Update' => 'role-update',
        'Delete' => 'role-delete',
        'Create' => 'role-create',
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
            $this->selectedRolePermissions = [
                'role-create',
                'role-read',
                'role-update',
                'role-delete',
            ];
        } else {
            $this->selectedRolePermissions = [];
        }
    }

}