<?php

namespace App\Livewire\Portal\Roles\Partial;


trait WithPaySlipAndEmployeePermissions
{
    public $selectedPayslipPermissions = [];
    public $selectAllPayslipPermissions = false;
    public $PayslipPermissions = [
        'View' => 'payslip-read',
        'Create' => 'payslip-create',
        'Delete' => 'payslip-delete',
        'Send' => 'payslip-send',
    ];

    public $selectedEmployeePermissions = [];
    public $selectAllEmployeePermissions = false;
    public $EmployeePermissions = [
        'View' => 'employe-read',
        'Create' => 'employe-create',
        'Update' => 'employe-update',
        'Delete' => 'employe-delete',
        'Import' => 'employe-import',
        'Export' => 'employe-export',
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
            $this->selectedEmployeePermissions = [
                'employee-read',
                'employee-create',
                'employee-delete',
                'employee-import',
                'employee-update',
                'employee-export',
            ];
        } else {
            $this->selectedEmployeePermissions = [];
        }
    }
    public function updatedSelectAllPayslipPermissions($value)
    {
        if ($value) {
            $this->selectedPayslipPermissions = [
                'payslip-read',
                'payslip-create',
                'payslip-delete',
                'payslip-send',
            ];
        } else {
            $this->selectedPayslipPermissions = [];
        }
    }
}
