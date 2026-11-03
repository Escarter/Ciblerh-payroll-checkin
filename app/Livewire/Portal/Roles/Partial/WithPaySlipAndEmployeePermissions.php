<?php

namespace App\Livewire\Portal\Roles\Partial;


trait WithPaySlipAndEmployeePermissions
{
    public $selectedPayslipPermissions = [];
    public $selectAllPayslipPermissions = false;
    public $PayslipPermissions = [
        'common.view' => 'payslip-read',
        'common.create' => 'payslip-create',
        'common.delete' => 'payslip-delete',
        'common.send' => 'payslip-sending',
        'common.restore' => 'payslip-restore',
        'common.bulk_delete' => 'payslip-bulkdelete',
        'common.bulk_restore' => 'payslip-bulkrestore',
        'payslips.bulk_resend_emails' => 'payslip-bulkresend-email',
        'payslips.bulk_resend_sms' => 'payslip-bulkresend-sms',
    ];

    public $selectedEmployeePermissions = [];
    public $selectAllEmployeePermissions = false;
    public $EmployeePermissions = [
        'common.view' => 'employee-read',
        'common.create' => 'employee-create',
        'common.update' => 'employee-update',
        'common.delete' => 'employee-delete',
        'common.import' => 'employee-import',
        'common.export' => 'employee-export',
        'common.restore' => 'employee-restore',
        'common.bulk_delete' => 'employee-bulkdelete',
        'common.bulk_restore' => 'employee-bulkrestore',
    ];

    public function payslipAndEmployeePermissionClearFields()
    {
        $this->reset([
            'selectedPayslipPermissions',
            'selectAllPayslipPermissions',
            'selectedEmployeePermissions',
            'selectAllEmployeePermissions',
        ]);
    }

    public function updatedSelectAllEmployeePermissions($value)
    {
        if ($value) {
            $this->selectedEmployeePermissions = array_values($this->EmployeePermissions);
        } else {
            $this->selectedEmployeePermissions = [];
        }
    }
    public function updatedSelectAllPayslipPermissions($value)
    {
        if ($value) {
            $this->selectedPayslipPermissions = array_values($this->PayslipPermissions);
        } else {
            $this->selectedPayslipPermissions = [];
        }
    }
}
