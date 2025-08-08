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
        'Send' => 'payslip-sending',
    ];

    public $selectedEmployeePermissions = [];
    public $selectAllEmployeePermissions = false;
    public $EmployeePermissions = [
        'View' => 'employee-read',
        'Create' => 'employee-create',
        'Update' => 'employee-update',
        'Delete' => 'employee-delete',
        'Import' => 'employee-import',
        'Export' => 'employee-export',
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
