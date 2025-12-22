<?php

namespace App\Livewire\Portal\Roles\Partial;


trait WithImportJobPermissions
{
    public $selectedImportJobPermissions = [];
    public $selectAllImportJobPermissions = false;
    public $ImportJobPermissions = [
        'View Import Jobs' => 'importjob-read',
        'Cancel Import Jobs' => 'importjob-cancel',
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