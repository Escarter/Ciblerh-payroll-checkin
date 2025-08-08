<?php

namespace App\Livewire\Portal\Roles\Partial;


trait WithReportPermissions
{
    public $selectedReportPermissions = [];
    public $selectAllReportPermissions = false;
    public $ReportPermissions = [
        'View Check-in Reports' => 'report-checkin-read',
        'View Payslip Reports' => 'report-payslip-read',
        'Export Reports' => 'report-export',
    ];

    public function reportPermissionClearFields()
    {
        $this->reset([
            'selectedReportPermissions',
            'selectAllReportPermissions',
        ]);
    }

    public function updatedSelectAllReportPermissions($value)
    {
        if ($value) {
            $this->selectedReportPermissions = array_values($this->ReportPermissions);
        } else {
            $this->selectedReportPermissions = [];
        }
    }
}
