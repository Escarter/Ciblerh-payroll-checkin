<?php

namespace App\Livewire\Portal\Roles\Partial;


trait WithLeaveAndLeaveTypePermissions
{
    public $selectedLeavePermissions = [];
    public $selectAllLeavePermissions = false;
    public $LeavePermissions = [
        'View' => 'leave-read',
        'Update' => 'leave-update',
        'Delete' => 'leave-delete',
        'Create' => 'leave-create',
        'Import' => 'leave-import',
        'Export' => 'leave-export',
    ];

    public $selectedLeaveTypePermissions = [];
    public $selectAllLeaveTypePermissions = false;
    public $LeaveTypePermissions = [
        'View' => 'leave_type-read',
        'Update' => 'leave_type-update',
        'Delete' => 'leave_type-delete',
        'Create' => 'leave_type-create',
        'Import' => 'leave_type-import',
        'Export' => 'leave_type-export',
    ];


    public function leaveAndLeaveTypePermissionClearFields()
    {
        $this->reset([
            'selectedLeavePermissions',
            'selectAllLeaveTypePermissions',
            'selectedLeaveTypePermissions',
            'selectAllLeavePermissions',
        ]);
    }

    public function updatedSelectAllLeavePermissions($value)
    {
        if ($value) {
            $this->selectedLeavePermissions = array_values($this->LeavePermissions);
        } else {
            $this->selectedLeavePermissions = [];
        }
    }
    public function updatedSelectAllLeaveTypePermissions($value)
    {
        if ($value) {
            $this->selectedLeaveTypePermissions = array_values($this->LeaveTypePermissions);
        } else {
            $this->selectedLeaveTypePermissions = [];
        }
    }
}
