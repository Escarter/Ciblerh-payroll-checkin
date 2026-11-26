<?php

namespace App\Livewire\Portal\Roles\Partial;


trait WithImportJobPermissions
{
    public $selectedImportJobPermissions = [];
    public $selectAllImportJobPermissions = false;
    public $ImportJobPermissions = [
        'common.view' => 'importjob-read',
        'common.create' => 'importjob-create',
        'common.update' => 'importjob-update',
        'common.delete' => 'importjob-delete',
        'common.import' => 'importjob-import',
        'common.export' => 'importjob-export',
        'common.cancel' => 'importjob-cancel',
        'common.restore' => 'importjob-restore',
        'common.bulk_delete' => 'importjob-bulkdelete',
        'common.bulk_restore' => 'importjob-bulkrestore',
    ];

    public function importJobPermissionClearFields()
    {
        $this->reset([
            'selectedImportJobPermissions',
            'selectAllImportJobPermissions',
        ]);
    }

    public function updatedSelectAllImportJobPermissions($value)
    {
        if ($value) {
            $this->selectedImportJobPermissions = array_values($this->ImportJobPermissions);
        } else {
            $this->selectedImportJobPermissions = [];
        }
    }
}