<?php

namespace App\Livewire\Portal\Roles\Partial;


trait WithTickOvertimePermissions
{

    public $selectedTickingPermissions = [];
    public $selectAllTickingPermissions = false;
    public $TickingPermissions = [
        'View' => 'ticking-read',
        'Update' => 'ticking-update',
        'Delete' => 'ticking-delete',
        'Create' => 'ticking-create',
        'Export' => 'ticking-export',
    ];

    public $selectedOvertimePermissions = [];
    public $selectAllOvertimePermissions = false;
    public $OvertimePermissions = [
        'View' => 'overtime-read',
        'Update' => 'overtime-update',
        'Delete' => 'overtime-delete',
        'Create' => 'overtime-create',
        'Export' => 'overtime-export',
    ];
    
    public function overtimeAndTickingPermissionClearFields()
    {
        $this->reset([
            'selectedTickingPermissions',
            'selectAllTickingPermissions',
            'selectedOvertimePermissions',
            'selectAllOvertimePermissions',
        ]);
    }

    public function updatedSelectAllTickingPermissions($value)
    {
        if ($value) {
            $this->selectedTickingPermissions = [
                'ticking-read',
                'ticking-create',
                'ticking-update',
                'ticking-delete',
                'ticking-export',
            ];
        } else {
            $this->selectedTickingPermissions = [];
        }
    }
    public function updatedSelectAllOvertimePermissions($value)
    {
        if ($value) {
            $this->selectedOvertimePermissions = [
                'overtime-read',
                'overtime-create',
                'overtime-update',
                'overtime-delete',
                'overtime-export',
            ];
        } else {
            $this->selectedOvertimePermissions = [];
        }
    }

}
