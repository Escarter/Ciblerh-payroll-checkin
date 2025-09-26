<?php

namespace App\Livewire\Portal\Roles\Partial;


use Livewire\Component;
use App\Models\Role;
use Illuminate\Support\Facades\Gate;
use App\Livewire\Traits\WithDataTable;
use Spatie\Permission\Models\Permission;
use App\Livewire\Portal\Roles\Partial\WithRolePermissions;
use App\Livewire\Portal\Roles\Partial\WithAuditLogPermissions;
use App\Livewire\Portal\Roles\Partial\WithComDeptServPermissions;
use App\Livewire\Portal\Roles\Partial\WithTickOvertimePermissions;
use App\Livewire\Portal\Roles\Partial\WithLeaveAndLeaveTypePermissions;
use App\Livewire\Portal\Roles\Partial\WithAdvanceSalAndAbsencesPermissions;
use App\Livewire\Portal\Roles\Partial\WithSettingPermissions;
use App\Livewire\Portal\Roles\Partial\WithPaySlipAndEmployeePermissions;
use App\Livewire\Portal\Roles\Partial\WithReportPermissions;

class Create extends Component
{

    use  WithComDeptServPermissions, WithRolePermissions, WithAuditLogPermissions, WithTickOvertimePermissions,
    WithAdvanceSalAndAbsencesPermissions, WithLeaveAndLeaveTypePermissions, WithSettingPermissions, WithPaySlipAndEmployeePermissions,
    WithReportPermissions;

    public $name;
    public $role;
    public $makeAdmin = false;
    public $grantGeneralSettingsPermissions = false;
    public $grantReportingPermissions = false;
    public $onlyOwnedSalesPermissions = false;

    public function store()
    {
        if (!Gate::allows('role.create')) {
            return abort(401);
        }

        $this->validate(['name' => 'required|unique:roles']);

        try {
            $role = Role::firstOrCreate(['name' => $this->name]);

            if ($this->makeAdmin) {

                $all_permissions = Permission::where('guard_name', 'web')->get();
                $role->syncPermissions($all_permissions);

            } else {
                $role->syncPermissions([
                    $this->selectedRolePermissions,
                    $this->selectedCompanyPermissions,
                    $this->selectedDepartmentPermissions,
                    $this->selectedOvertimePermissions,
                    $this->selectedTickingtPermissions,
                    $this->selectedAuditLogPermissions,
                    $this->selectedLeavePermissions,
                    $this->selectedLeaveTypePermissions,
                    $this->selectedPayslipPermissions,
                    $this->selectedEmployeePermissions,
                    $this->selectedSettingPermissions,
                    $this->selectedReportPermissions,
                   
                ]);
            }

            $this->dispatch('roleCreated');
        } catch (\Throwable $th) {
            $this->refresh(__('Quelque chose n\'a pas fonctionné !'), 'CreateRoleModal');
        }
    }
   
    public function clearFields()
    {
        $this->leaveAndLeaveTypePermissionClearFields();
        $this->compDeptServPermissionClearFields();
        $this->rolePermissionClearFields();
        $this->auditLogPermissionClearFields();
        $this->overtimeAndTickingPermissionClearFields();
        $this->advanceSalaryAndAbsencePermissionClearFields();
        $this->settingPermissionClearFields();
        $this->payslipAndEmployeePermissionClearFields();
        $this->reportPermissionClearFields();
        $this->reset(['name']);
    }

    public function render()
    {
        return view('livewire.portal.roles.partial.create');
    }
}
