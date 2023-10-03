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
            $this->selectedSettingPermissions = [
                'setting-read',
                'setting-save',
                'setting-sms',
                'setting-smtp',
            ];
        } else {
            $this->selectedSettingPermissions = [];
        }
    }
}
