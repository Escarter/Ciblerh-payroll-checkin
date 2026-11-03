<?php

namespace App\Livewire\Portal\Roles\Partial;


trait WithTickOvertimePermissions
{

    public $selectedTickingPermissions = [];
    public $selectAllTickingPermissions = false;
    public $TickingPermissions = [
        'common.view' => 'ticking-read',
        'common.update' => 'ticking-update',
        'common.delete' => 'ticking-delete',
        'common.create' => 'ticking-create',
        'common.export' => 'ticking-export',
        'common.restore' => 'ticking-restore',
        'common.bulk_delete' => 'ticking-bulkdelete',
        'common.bulk_restore' => 'ticking-bulkrestore',
    ];

    public $selectedOvertimePermissions = [];
    public $selectAllOvertimePermissions = false;
    public $OvertimePermissions = [
        'common.view' => 'overtime-read',
        'common.update' => 'overtime-update',
        'common.delete' => 'overtime-delete',
        'common.create' => 'overtime-create',
        'common.export' => 'overtime-export',
        'common.restore' => 'overtime-restore',
        'common.bulk_delete' => 'overtime-bulkdelete',
        'common.bulk_restore' => 'overtime-bulkrestore',
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
