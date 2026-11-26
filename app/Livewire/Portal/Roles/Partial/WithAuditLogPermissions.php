<?php

namespace App\Livewire\Portal\Roles\Partial;


trait WithAuditLogPermissions
{
    public $selectedAuditLogPermissions = [];
    public $selectAllAuditLogPermissions = false;
    public $AuditLogPermissions = [
        'common.view' => 'audit_log-read_all',
        'common.delete' => 'audit_log-delete',
        'audit_logs.view_own_logs_only' => 'audit_log-read_own_only',
        'common.restore' => 'audit_log-restore',
        'common.bulk_delete' => 'audit_log-bulkdelete',
        'common.bulk_restore' => 'audit_log-bulkrestore',
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
