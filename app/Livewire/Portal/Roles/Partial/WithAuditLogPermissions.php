<?php

namespace App\Livewire\Portal\Roles\Partial;


trait WithAuditLogPermissions
{
    public $selectedAuditLogPermissions = [];
    public $selectAllAuditLogPermissions = false;
    public $AuditLogPermissions = [
        'View' => 'audit_log-view_all',
        'Delete' => 'audit_log-delete',
        'View own logs only' => 'audit_log-view_own_only',
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
            $this->selectedAuditLogPermissions = [
                'audit_log-view_own_only',
                'audit_log-view_all',
                'audit_log-delete',
            ];
        } else {
            $this->selectedAuditLogPermissions = [];
        }
    }
}
