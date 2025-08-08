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
            $this->selectedTickingPermissions = array_values($this->TickingPermissions);
        } else {
            $this->selectedTickingPermissions = [];
        }
    }
    public function updatedSelectAllOvertimePermissions($value)
    {
        if ($value) {
            $this->selectedOvertimePermissions = array_values($this->OvertimePermissions);
        } else {
            $this->selectedOvertimePermissions = [];
        }
    }

}
