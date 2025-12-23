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
use App\Livewire\Portal\Roles\Partial\WithImportJobPermissions;

class Create extends Component
{

    use  WithComDeptServPermissions, WithRolePermissions, WithAuditLogPermissions, WithTickOvertimePermissions,
    WithAdvanceSalAndAbsencesPermissions, WithLeaveAndLeaveTypePermissions, WithSettingPermissions, WithPaySlipAndEmployeePermissions,
    WithReportPermissions, WithImportJobPermissions;

    public $name;
    public $role;
    public $roleId = null;
    public $isEditMode = false;
    public $makeAdmin = false;
    public $grantGeneralSettingsPermissions = false;
    public $grantReportingPermissions = false;
    public $onlyOwnedSalesPermissions = false;
    
    public $permissionSearch = '';
    public array $selectedPermissions = [];

    protected $listeners = ['clearRoleFields', 'storeRole', 'editRole'];
    
    protected $rules = [
        'name' => 'required|unique:roles',
        'selectedPermissions' => 'array',
    ];

    public function clearRoleFields()
    {
        $this->clearFields();
    }

    public function storeRole()
    {
        if ($this->isEditMode) {
            $this->update();
        } else {
            $this->store();
        }
    }
    
    public function editRole($roleId)
    {
        if (!Gate::allows('role-update')) {
            return abort(401);
        }

        $this->roleId = $roleId;
        $this->isEditMode = true;
        $this->role = Role::with('permissions')->findOrFail($roleId);
        $this->name = $this->role->name;
        
        // Load current permissions
        $rolePermissions = $this->role->permissions->pluck('name')->toArray();
        $this->selectedPermissions = $rolePermissions;
        
        // Check if all permissions are selected (admin mode)
        $allPermissions = Permission::where('guard_name', 'web')->pluck('name')->toArray();
        $this->makeAdmin = count($rolePermissions) === count($allPermissions) && count($rolePermissions) > 0;
        
        // Dispatch event to update modal UI
        $this->dispatch('editRole', $roleId);
    }
    
    public function mount()
    {
        // Initialize selectedPermissions with empty array
        // No need to load all permissions here - they're computed in render()
        $this->selectedPermissions = [];
    }
    
    private function syncPermissionsToTraits()
    {
        // Only sync if we need to maintain backward compatibility
        // Since we're using selectedPermissions as the source of truth,
        // we don't need to sync to trait arrays on every update
        // This prevents multiple Livewire events from being triggered
    }
    
    public function selectAllPermissions()
    {
        $allPermissions = Permission::where('guard_name', 'web')->pluck('name')->toArray();
        $this->selectedPermissions = array_values($allPermissions);
    }
    
    public function clearAllPermissions()
    {
        $this->selectedPermissions = [];
    }
    
    public function selectCategoryPermissions($category)
    {
        $categoryPermissions = Permission::where('guard_name', 'web')
            ->where('name', 'like', $category . '-%')
            ->pluck('name')
            ->toArray();
        $this->selectedPermissions = array_values(array_unique(array_merge($this->selectedPermissions, $categoryPermissions)));
    }
    
    public function clearCategoryPermissions($category)
    {
        $categoryPermissions = Permission::where('guard_name', 'web')
            ->where('name', 'like', $category . '-%')
            ->pluck('name')
            ->toArray();
        $this->selectedPermissions = array_values(array_diff($this->selectedPermissions, $categoryPermissions));
    }
    
    
    
    /**
     * Get translated label for a permission
     */
    public function getPermissionLabel($permissionName)
    {
        // Special cases with full translation keys
        $specialCases = [
            'report-checkin-read' => 'reports.checkin_report',
            'report-payslip-read' => 'reports.payslip_report',
            'report-export' => 'reports.export',
            'payslip-sending' => 'payslips.sending',
            'payslip-bulkresend-email' => 'payslips.bulk_resend_emails',
            'payslip-bulkresend-sms' => 'payslips.bulk_resend_sms',
            'audit_log-read_all' => 'audit_logs.read_all',
            'audit_log-read_own_only' => 'audit_logs.read_own_only',
            'setting-sms' => 'settings.sms',
            'setting-smtp' => 'settings.smtp',
            'importjob-cancel' => 'import_jobs.cancel',
        ];
        
        if (isset($specialCases[$permissionName])) {
            return __($specialCases[$permissionName]);
        }
        
        $parts = explode('-', $permissionName);
        $category = $parts[0] ?? '';
        
        // Handle multi-part actions (e.g., bulkresend-email, read_own_only)
        if (count($parts) > 2) {
            // For actions like "bulkresend-email", combine parts 1 and 2
            $action = $parts[1] . '-' . $parts[2];
        } else {
            $action = $parts[1] ?? '';
        }
        
        // Map actions to translation keys
        $actionMap = [
            'read' => 'common.view',
            'create' => 'common.create',
            'update' => 'common.update',
            'delete' => 'common.delete',
            'restore' => 'common.restore',
            'export' => 'common.export',
            'import' => 'common.import',
            'bulkdelete' => 'common.bulk_delete',
            'bulkrestore' => 'common.bulk_restore',
            'sending' => 'payslips.sending',
            'bulkresend-email' => 'payslips.bulk_resend_emails',
            'bulkresend-sms' => 'payslips.bulk_resend_sms',
            'read_all' => 'audit_logs.read_all',
            'read_own_only' => 'audit_logs.read_own_only',
            'save' => 'common.save',
            'sms' => 'settings.sms',
            'smtp' => 'settings.smtp',
            'cancel' => 'import_jobs.cancel',
        ];
        
        // Get category translation
        $categoryMap = [
            'user' => 'dashboard.user',
            'company' => 'companies.company',
            'department' => 'departments.department',
            'service' => 'services.service',
            'employee' => 'employees.employee',
            'payslip' => 'payslips.payslip',
            'audit_log' => 'audit_logs.audit_log',
            'role' => 'roles.role',
            'advance_salary' => 'common.advance_salary',
            'absence' => 'common.absences',
            'overtime' => 'common.overtime',
            'ticking' => 'common.checkins',
            'leave' => 'common.leave',
            'leave_type' => 'common.leave_types',
            'importjob' => 'common.import_jobs',
            'setting' => 'common.settings',
            'report' => 'common.reports',
            'profile' => 'common.profile',
        ];
        
        $categoryLabel = $categoryMap[$category] ?? ucfirst(str_replace('_', ' ', $category));
        $actionLabel = $actionMap[$action] ?? ucfirst(str_replace('_', ' ', $action));
        
        // If we have a translation key, use it
        if (strpos($actionLabel, '.') !== false) {
            $actionLabel = __($actionLabel);
        }
        if (strpos($categoryLabel, '.') !== false) {
            $categoryLabel = __($categoryLabel);
        }
        
        return $actionLabel . ' ' . $categoryLabel;
    }
    
    public function updatedMakeAdmin($value)
    {
        if ($value) {
            $this->selectedPermissions = Permission::where('guard_name', 'web')->pluck('name')->toArray();
        } else {
            $this->selectedPermissions = [];
        }
    }

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
                // Use selectedPermissions as the source of truth
                $role->syncPermissions($this->selectedPermissions ?? []);
            }

            $this->dispatch('roleCreated');
            $this->resetForm();
        } catch (\Throwable $th) {
            $this->refresh(__('common.something_went_wrong'), 'RoleModal');
        }
    }
    
    public function update()
    {
        if (!Gate::allows('role-update')) {
            return abort(401);
        }

        $this->validate(['name' => 'required|unique:roles,name,' . $this->roleId]);

        try {
            $this->role->update(['name' => $this->name]);

            if ($this->makeAdmin) {
                $all_permissions = Permission::where('guard_name', 'web')->get();
                $this->role->syncPermissions($all_permissions);
            } else {
                // Use selectedPermissions as the source of truth
                $this->role->syncPermissions($this->selectedPermissions ?? []);
            }

            $this->dispatch('roleUpdated');
            $this->resetForm();
        } catch (\Throwable $th) {
            $this->refresh(__('common.something_went_wrong'), 'RoleModal');
        }
    }
   
    public function clearFields()
    {
        // Reset all form fields
        $this->reset([
            'name', 
            'selectedPermissions', 
            'permissionSearch', 
            'makeAdmin', 
            'roleId', 
            'isEditMode', 
            'role'
        ]);
        
        // Reset trait arrays to prevent any issues
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
        
        // Dispatch event to reset modal UI
        $this->dispatch('clearRoleFields');
    }
    
    public function resetForm()
    {
        $this->clearFields();
    }
    
    public function render()
    {
        // Compute permissions in render method like StratagemAI does
        // This prevents continuous queries and only runs once per render
        $permissionsQuery = Permission::where('guard_name', 'web');
        
        if (!empty($this->permissionSearch)) {
            $permissionsQuery->where('name', 'like', '%' . $this->permissionSearch . '%');
        }
        
        $permissions = $permissionsQuery->get()->groupBy(function ($permission) {
            // Group by the part before the first dash
            $parts = explode('-', $permission->name);
            return $parts[0] ?? 'other';
        });
        
        return view('livewire.portal.roles.partial.create', [
            'permissions' => $permissions,
        ]);
    }
}
