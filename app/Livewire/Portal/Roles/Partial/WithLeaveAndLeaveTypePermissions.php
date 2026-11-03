<?php

namespace App\Livewire\Portal\Roles\Partial;


trait WithLeaveAndLeaveTypePermissions
{
    public $selectedLeavePermissions = [];
    public $selectAllLeavePermissions = false;
    public $LeavePermissions = [
        'common.view' => 'leave-read',
        'common.update' => 'leave-update',
        'common.delete' => 'leave-delete',
        'common.create' => 'leave-create',
        'common.import' => 'leave-import',
        'common.export' => 'leave-export',
        'common.restore' => 'leave-restore',
        'common.bulk_delete' => 'leave-bulkdelete',
        'common.bulk_restore' => 'leave-bulkrestore',
    ];

    public $selectedLeaveTypePermissions = [];
    public $selectAllLeaveTypePermissions = false;
    public $LeaveTypePermissions = [
        'common.view' => 'leave_type-read',
        'common.update' => 'leave_type-update',
        'common.delete' => 'leave_type-delete',
        'common.create' => 'leave_type-create',
        'common.import' => 'leave_type-import',
        'common.export' => 'leave_type-export',
        'common.restore' => 'leave_type-restore',
        'common.bulk_delete' => 'leave_type-bulkdelete',
        'common.bulk_restore' => 'leave_type-bulkrestore',
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
