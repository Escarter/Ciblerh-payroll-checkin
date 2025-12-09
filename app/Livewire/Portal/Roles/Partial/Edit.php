<?php

namespace App\Livewire\Portal\Roles\Partial;

use Livewire\Component;
use App\Models\Role;
use Illuminate\Support\Facades\Gate;
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
use App\Livewire\Portal\Roles\Partial\WithImportJobPermissions;

class Edit extends Component
{
    use WithComDeptServPermissions, WithRolePermissions, WithAuditLogPermissions, WithTickOvertimePermissions,
        WithAdvanceSalAndAbsencesPermissions, WithLeaveAndLeaveTypePermissions, WithSettingPermissions, WithPaySlipAndEmployeePermissions, WithReportPermissions, WithImportJobPermissions;

    public $name;
    public $role;
    public $roleId;
    public $makeAdmin = false;
    public $grantGeneralSettingsPermissions = false;
    public $grantReportingPermissions = false;
    public $onlyOwnedSalesPermissions = false;

    protected $listeners = ['editRole'];

    public function mount()
    {
        // Initialize all permission arrays
        $this->clearPermissions();
    }

    public function editRole($roleId)
    {
        if (!Gate::allows('role-update')) {
            return abort(401);
        }

        $this->roleId = $roleId;
        $this->role = Role::with('permissions')->findOrFail($roleId);
        $this->name = $this->role->name;
        
        // Reset all permissions first
        $this->clearPermissions();
        
        // Load current permissions for the role
        $this->loadCurrentPermissions();
    }

    private function loadCurrentPermissions()
    {
        $rolePermissions = $this->role->permissions->pluck('name')->toArray();
        
        // Debug: Log the role permissions for debugging
        // \Log::info('Role Permissions for role ' . $this->role->name, $rolePermissions);

        // Load Company permissions
        $this->selectedCompanyPermissions = array_values(array_intersect($rolePermissions, array_values($this->CompanyPermissions)));
        $this->selectAllCompanyPermissions = count($this->selectedCompanyPermissions) === count($this->CompanyPermissions);

        // Load Department permissions
        $this->selectedDepartmentPermissions = array_values(array_intersect($rolePermissions, array_values($this->DepartmentPermissions)));
        $this->selectAllDepartmentPermissions = count($this->selectedDepartmentPermissions) === count($this->DepartmentPermissions);

        // Load Service permissions
        $this->selectedServicePermissions = array_values(array_intersect($rolePermissions, array_values($this->ServicePermissions)));
        $this->selectAllServicePermissions = count($this->selectedServicePermissions) === count($this->ServicePermissions);

        // Load Employee permissions
        $this->selectedEmployeePermissions = array_values(array_intersect($rolePermissions, array_values($this->EmployeePermissions)));
        $this->selectAllEmployeePermissions = count($this->selectedEmployeePermissions) === count($this->EmployeePermissions);

        // Load Role permissions
        $this->selectedRolePermissions = array_values(array_intersect($rolePermissions, array_values($this->RolePermissions)));
        $this->selectAllRolePermissions = count($this->selectedRolePermissions) === count($this->RolePermissions);

        // Load Overtime permissions
        $this->selectedOvertimePermissions = array_values(array_intersect($rolePermissions, array_values($this->OvertimePermissions)));
        $this->selectAllOvertimePermissions = count($this->selectedOvertimePermissions) === count($this->OvertimePermissions);

        // Load Ticking permissions
        $this->selectedTickingPermissions = array_values(array_intersect($rolePermissions, array_values($this->TickingPermissions)));
        $this->selectAllTickingPermissions = count($this->selectedTickingPermissions) === count($this->TickingPermissions);

        // Load Advance Salary permissions
        $this->selectedAdvanceSalaryPermissions = array_values(array_intersect($rolePermissions, array_values($this->AdvanceSalaryPermissions)));
        $this->selectAllAdvanceSalaryPermissions = count($this->selectedAdvanceSalaryPermissions) === count($this->AdvanceSalaryPermissions);

        // Load Absence permissions
        $this->selectedAbsencePermissions = array_values(array_intersect($rolePermissions, array_values($this->AbsencePermissions)));
        $this->selectAllAbsencePermissions = count($this->selectedAbsencePermissions) === count($this->AbsencePermissions);

        // Load Leave permissions
        $this->selectedLeavePermissions = array_values(array_intersect($rolePermissions, array_values($this->LeavePermissions)));
        $this->selectAllLeavePermissions = count($this->selectedLeavePermissions) === count($this->LeavePermissions);

        // Load Leave Type permissions
        $this->selectedLeaveTypePermissions = array_values(array_intersect($rolePermissions, array_values($this->LeaveTypePermissions)));
        $this->selectAllLeaveTypePermissions = count($this->selectedLeaveTypePermissions) === count($this->LeaveTypePermissions);

        // Load Payslip permissions
        $this->selectedPayslipPermissions = array_values(array_intersect($rolePermissions, array_values($this->PayslipPermissions)));
        $this->selectAllPayslipPermissions = count($this->selectedPayslipPermissions) === count($this->PayslipPermissions);

        // Load Audit Log permissions
        $this->selectedAuditLogPermissions = array_values(array_intersect($rolePermissions, array_values($this->AuditLogPermissions)));
        $this->selectAllAuditLogPermissions = count($this->selectedAuditLogPermissions) === count($this->AuditLogPermissions);

        // Load Setting permissions
        $this->selectedSettingPermissions = array_values(array_intersect($rolePermissions, array_values($this->SettingPermissions)));
        $this->selectAllSettingPermissions = count($this->selectedSettingPermissions) === count($this->SettingPermissions);

        // Load Report permissions
        $this->selectedReportPermissions = array_values(array_intersect($rolePermissions, array_values($this->ReportPermissions)));
        $this->selectAllReportPermissions = count($this->selectedReportPermissions) === count($this->ReportPermissions);

        // Load Import Job permissions
        $this->selectedImportJobPermissions = array_values(array_intersect($rolePermissions, array_values($this->ImportJobPermissions)));
        $this->selectAllImportJobPermissions = count($this->selectedImportJobPermissions) === count($this->ImportJobPermissions);

        // Debug: Log some loaded permissions for testing
        // \Log::info('Loaded Company Permissions', $this->selectedCompanyPermissions);
        // \Log::info('Loaded Role Permissions', $this->selectedRolePermissions);
    }

    public function update()
    {
        if (!Gate::allows('role-update')) {
            return abort(401);
        }

        $this->validate(['name' => 'required|unique:roles,name,' . $this->roleId]);

        try {
            // Update the role name
            $this->role->update(['name' => $this->name]);

            if ($this->makeAdmin) {
                // Grant ALL permissions if makeAdmin is checked
                $all_permissions = Permission::where('guard_name', 'web')->get();
                $this->role->syncPermissions($all_permissions);
            } else {
                // Use the force sync method to ensure proper permission management
                $this->forcePermissionSync();
            }

            // Refresh the role to get updated permissions from database
            $this->role = $this->role->fresh();
            
            // Important: Reload the current permissions to reflect changes in the UI immediately
            $this->loadCurrentPermissions();

            // Dispatch success event (Index component will handle the success message)
            $this->dispatch('roleUpdated');
            
            // Close the modal
            $this->dispatch('cancel', modalId: 'EditRoleModal');
            
        } catch (\Throwable $th) {
            session()->flash('error', __('common.something_went_wrong') . $th->getMessage());
        }
    }

    /**
     * Method to close modal manually (can be called from template)
     */
    public function closeModal()
    {
        $this->dispatch('cancel', modalId: 'EditRoleModal');
    }

    public function clearAllPermissions()
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
        $this->importJobPermissionClearFields();
        $this->makeAdmin = false;
    }

    public function clearFields()
    {
        $this->clearAllPermissions();
        $this->reset(['name', 'roleId']);
        // Don't reset the 'role' property as it's needed for the component to function
    }

    /**
     * Helper method to clear all permission selections
     */
    private function clearPermissions()
    {
        // Clear all permission selections
        $this->selectAllCompanyPermissions = false;
        $this->selectedCompanyPermissions = [];
        
        $this->selectAllDepartmentPermissions = false;
        $this->selectedDepartmentPermissions = [];
        
        $this->selectAllServicePermissions = false;
        $this->selectedServicePermissions = [];
        
        $this->selectAllEmployeePermissions = false;
        $this->selectedEmployeePermissions = [];
        
        $this->selectAllRolePermissions = false;
        $this->selectedRolePermissions = [];
        
        $this->selectAllOvertimePermissions = false;
        $this->selectedOvertimePermissions = [];
        
        $this->selectAllTickingPermissions = false;
        $this->selectedTickingPermissions = [];
        
        $this->selectAllAdvanceSalaryPermissions = false;
        $this->selectedAdvanceSalaryPermissions = [];
        
        $this->selectAllAbsencePermissions = false;
        $this->selectedAbsencePermissions = [];
        
        $this->selectAllLeavePermissions = false;
        $this->selectedLeavePermissions = [];
        
        $this->selectAllLeaveTypePermissions = false;
        $this->selectedLeaveTypePermissions = [];
        
        $this->selectAllPayslipPermissions = false;
        $this->selectedPayslipPermissions = [];
        
        $this->selectAllAuditLogPermissions = false;
        $this->selectedAuditLogPermissions = [];
        
        $this->selectAllSettingPermissions = false;
        $this->selectedSettingPermissions = [];
        
        // Also clear the master checkboxes
        $this->selectAllPermissions = false;
        $this->makeAdmin = false;
    }

    /**
     * Force sync all permissions and remove any unassigned ones
     */
    public function forcePermissionSync()
    {
        if (!$this->role) {
            return;
        }

        // Get currently selected permissions from the form
        $selectedPermissions = array_filter(array_merge(
            $this->selectedRolePermissions ?? [],
            $this->selectedCompanyPermissions ?? [],
            $this->selectedDepartmentPermissions ?? [],
            $this->selectedServicePermissions ?? [],
            $this->selectedEmployeePermissions ?? [],
            $this->selectedOvertimePermissions ?? [],
            $this->selectedTickingPermissions ?? [],
            $this->selectedAuditLogPermissions ?? [],
            $this->selectedLeavePermissions ?? [],
            $this->selectedLeaveTypePermissions ?? [],
            $this->selectedPayslipPermissions ?? [],
            $this->selectedAdvanceSalaryPermissions ?? [],
            $this->selectedAbsencePermissions ?? [],
            $this->selectedSettingPermissions ?? [],
            $this->selectedReportPermissions ?? [],
            $this->selectedImportJobPermissions ?? []
        ));

        // Remove duplicates and empty values
        $selectedPermissions = array_unique(array_filter($selectedPermissions));

        // Sync permissions - this will:
        // 1. Add any new permissions that are selected
        // 2. Remove any permissions that were previously assigned but are now unselected
        // 3. Keep permissions that remain selected
        $this->role->syncPermissions($selectedPermissions);

        // Optional: Log the sync operation for debugging
        // \Log::info('Permission sync for role ' . $this->role->name, [
        //     'selected_permissions' => $selectedPermissions,
        //     'total_selected' => count($selectedPermissions)
        // ]);
    }

    // Temporary debugging method - you can remove this later
    public function debugPermissions()
    {
        if ($this->role) {
            $rolePermissions = $this->role->permissions->pluck('name')->toArray();
            $allSelected = array_filter(array_merge(
                $this->selectedRolePermissions ?? [],
                $this->selectedCompanyPermissions ?? [],
                $this->selectedDepartmentPermissions ?? [],
                $this->selectedServicePermissions ?? [],
                $this->selectedEmployeePermissions ?? [],
                $this->selectedOvertimePermissions ?? [],
                $this->selectedTickingPermissions ?? [],
                $this->selectedAuditLogPermissions ?? [],
                $this->selectedLeavePermissions ?? [],
                $this->selectedLeaveTypePermissions ?? [],
                $this->selectedPayslipPermissions ?? [],
                $this->selectedAdvanceSalaryPermissions ?? [],
                $this->selectedAbsencePermissions ?? [],
                $this->selectedSettingPermissions ?? [],
                $this->selectedReportPermissions ?? []
            ));

            session()->flash('debug_info', [
                'role_permissions_count' => count($rolePermissions),
                'role_permissions' => $rolePermissions,
                'selected_permissions_count' => count($allSelected),
                'selected_permissions' => $allSelected,
                'missing_permissions' => array_diff($rolePermissions, $allSelected),
                'extra_permissions' => array_diff($allSelected, $rolePermissions)
            ]);
        }
    }

    /**
     * Get all permissions that should be removed (were assigned but no longer selected)
     */
    public function getPermissionsToRemove()
    {
        if (!$this->role) {
            return [];
        }

        $currentPermissions = $this->role->permissions->pluck('name')->toArray();
        $selectedPermissions = array_filter(array_merge(
            $this->selectedRolePermissions ?? [],
            $this->selectedCompanyPermissions ?? [],
            $this->selectedDepartmentPermissions ?? [],
            $this->selectedServicePermissions ?? [],
            $this->selectedEmployeePermissions ?? [],
            $this->selectedOvertimePermissions ?? [],
            $this->selectedTickingPermissions ?? [],
            $this->selectedAuditLogPermissions ?? [],
            $this->selectedLeavePermissions ?? [],
            $this->selectedLeaveTypePermissions ?? [],
            $this->selectedPayslipPermissions ?? [],
            $this->selectedAdvanceSalaryPermissions ?? [],
            $this->selectedAbsencePermissions ?? [],
            $this->selectedSettingPermissions ?? []
        ));

        // Find permissions that are currently assigned but not selected
        return array_diff($currentPermissions, $selectedPermissions);
    }
}
