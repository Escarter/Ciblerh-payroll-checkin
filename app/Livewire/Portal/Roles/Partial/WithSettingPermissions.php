<?php

namespace App\Livewire\Portal\Roles\Partial;


trait WithSettingPermissions
{
    public $selectedSettingPermissions = [];
    public $selectAllSettingPermissions = false;
    public $SettingPermissions = [
        'View' => 'setting-read',
        'Save' => 'setting-save',
        'SMS' => 'setting-sms',
        'SMTP' => 'setting-smtp',
    ];

    public function settingPermissionClearFields()
    {
        $this->reset([
            'selectedSettingPermissions',
            'selectAllSettingPermissions',
        ]);
    }

    public function updatedSelectAllSettingPermissions($value)
    {
        if ($value) {
            $this->selectedSettingPermissions = array_values($this->SettingPermissions);
        } else {
            $this->selectedSettingPermissions = [];
        }
    }
}
