<?php

namespace App\Livewire\Portal\Roles\Partial;


trait WithAuditLogPermissions
{
    public $selectedAuditLogPermissions = [];
    public $selectAllAuditLogPermissions = false;
    public $AuditLogPermissions = [
        'View' => 'audit_log-read_all',
        'Delete' => 'audit_log-delete',
        'View own logs only' => 'audit_log-read_own_only',
    ];

    public function auditLogPermissionClearFields()
    {
        $this->reset([
            'selectedAuditLogPermissions',
            'selectAllAuditLogPermissions',
        ]);
    }

    public function updatedSelectAllAuditLogPermissions($value)
    {
        if ($value) {
            $this->selectedAuditLogPermissions = array_values($this->AuditLogPermissions);
        } else {
            $this->selectedAuditLogPermissions = [];
        }
    }
}
