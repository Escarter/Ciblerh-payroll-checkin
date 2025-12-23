<?php

namespace App\Livewire\Portal\Roles;

use App\Livewire\Traits\WithDataTable;
use Livewire\Component;
use App\Models\Role;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;

class Index extends Component
{
    use WithDataTable;

    // Modal visibility
    public $showCreateModal = false;
    public $showEditModal = false;

    // Soft delete properties
    public $activeTab = 'active';
    public $selectedRoles = [];
    public $selectAll = false;
    public $role_id = null;

    // Role creation/editing properties
    public $roleName = '';
    public $selectedPermissions = [];
    public $editingRole = null;
    public $permissionSearch = '';
    public $makeAdmin = false;

    protected $rules = [
        'roleName' => 'required|string|max:255|unique:roles,name',
        'selectedPermissions' => 'array',
    ];

    //Get & assign selected advance_salary props
    public function initData($role_id)
    {
        $role = Role::findOrFail($role_id);

        $this->role = $role;
    }

    public function openCreateModal()
    {
        if (!Gate::allows('role-create')) {
            return abort(401);
        }
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function openEditModal($roleId)
    {
        if (!Gate::allows('role-update')) {
            return abort(401);
        }
        $this->editingRole = Role::with('permissions')->findOrFail($roleId);
        $this->roleName = $this->editingRole->name;
        $this->selectedPermissions = $this->editingRole->permissions->pluck('name')->toArray();
        
        // Check if all permissions are selected (admin mode)
        $allPermissions = Permission::where('guard_name', 'web')->pluck('name')->toArray();
        $this->makeAdmin = count($this->selectedPermissions) === count($allPermissions) && count($this->selectedPermissions) > 0;
        
        $this->showEditModal = true;
    }

    public function createRole()
    {
        if (!Gate::allows('role-create')) {
            return abort(401);
        }

        $this->validate();

        $role = Role::create(['name' => $this->roleName]);

        if ($this->makeAdmin) {
            $all_permissions = Permission::where('guard_name', 'web')->get();
            $role->syncPermissions($all_permissions);
        } else {
            if (!empty($this->selectedPermissions)) {
                $role->syncPermissions($this->selectedPermissions);
            }
        }

        $this->resetForm();
        $this->showCreateModal = false;
        $this->closeModalAndFlashMessage(__('roles.role_and_associated_permissions_created_successfully'), 'RoleModal');
    }

    public function updateRole()
    {
        if (!Gate::allows('role-update')) {
            return abort(401);
        }

        $this->validate([
            'roleName' => 'required|string|max:255|unique:roles,name,' . $this->editingRole->id,
            'selectedPermissions' => 'array',
        ]);

        $this->editingRole->update(['name' => $this->roleName]);

        if ($this->makeAdmin) {
            $all_permissions = Permission::where('guard_name', 'web')->get();
            $this->editingRole->syncPermissions($all_permissions);
        } else {
            $this->editingRole->syncPermissions($this->selectedPermissions);
        }

        $this->resetForm();
        $this->showEditModal = false;
        $this->closeModalAndFlashMessage(__('roles.role_and_permissions_updated_successfully'), 'RoleModal');
    }

    public function closeModals()
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->roleName = '';
        $this->selectedPermissions = [];
        $this->editingRole = null;
        $this->permissionSearch = '';
        $this->makeAdmin = false;
        $this->resetValidation();
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

    public function updatedMakeAdmin($value)
    {
        if ($value) {
            $this->selectedPermissions = Permission::where('guard_name', 'web')->pluck('name')->toArray();
        } else {
            $this->selectedPermissions = [];
        }
    }

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
            'bulkapproval' => 'common.bulk_approve',
            'bulkrejection' => 'common.bulk_reject',
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
            'downloadjob' => 'download_jobs.download_jobs',
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


    public function delete()
    {
        if (!Gate::allows('role-delete')) {
            return abort(401);
        }

        if (!empty($this->role)) {
            if(count($this->role->users) <= 0) {
                $this->role->syncPermissions([]);
                $this->role->delete(); // Soft delete
                $this->closeModalAndFlashMessage(__('roles.role_moved_to_trash_successfully'), 'DeleteModal');
            } else {
                $this->closeModalAndFlashMessage(__('roles.role_cannot_be_deleted_still_assigned_to_users'), '');
            }
        }
    }

    public function restore()
    {
        if (!Gate::allows('role-restore')) {
            return abort(401);
        }

        $role = Role::withTrashed()->findOrFail($this->role_id);
        $role->restore();

        $this->closeModalAndFlashMessage(__('roles.role_restored_successfully'), 'RestoreModal');
    }

    public function forceDelete($roleId = null)
    {
        if (!Gate::allows('role-delete')) {
            return abort(401);
        }

        // If no roleId provided, try to get it from selectedRoles
        if (!$roleId) {
            if (!empty($this->selectedRoles) && is_array($this->selectedRoles)) {
                $roleId = $this->selectedRoles[0] ?? null;
            } elseif ($this->role_id) {
                $roleId = $this->role_id;
            } else {
                $this->showToast(__('roles.no_role_selected'), 'danger');
                return;
            }
        }

        $role = Role::withTrashed()->findOrFail($roleId);
        $role->forceDelete();

        // Clear selection after deletion
        if (in_array($roleId, $this->selectedRoles ?? [])) {
            $this->selectedRoles = array_diff($this->selectedRoles, [$roleId]);
        }
        $this->role_id = null;

        $this->closeModalAndFlashMessage(__('roles.role_permanently_deleted'), 'ForceDeleteModal');
    }

    public function bulkDelete()
    {
        if (!Gate::allows('role-bulkdelete')) {
            return abort(401);
        }

        if (!empty($this->selectedRoles)) {
            Role::whereIn('id', $this->selectedRoles)->delete(); // Soft delete
            $this->selectedRoles = [];
            $this->selectAll = false;
        }

        $this->closeModalAndFlashMessage(__('roles.selected_roles_moved_to_trash'), 'BulkDeleteModal');
    }

    public function bulkRestore()
    {
        if (!Gate::allows('role-bulkrestore')) {
            return abort(401);
        }

        if (!empty($this->selectedRoles)) {
            Role::withTrashed()->whereIn('id', $this->selectedRoles)->restore();
            $this->selectedRoles = [];
            $this->selectAll = false;
        }

        $this->closeModalAndFlashMessage(__('roles.selected_roles_restored'), 'BulkRestoreModal');
    }

    public function bulkForceDelete()
    {
        if (!Gate::allows('role-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedRoles)) {
            Role::withTrashed()->whereIn('id', $this->selectedRoles)->forceDelete();
            $this->selectedRoles = [];
            $this->selectAll = false;
        }

        $this->closeModalAndFlashMessage(__('roles.selected_roles_permanently_deleted'), 'BulkForceDeleteModal');
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->selectedRoles = [];
        $this->selectAll = false;
    }

    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            // Deselect all
            $this->selectedRoles = [];
            $this->selectAll = false;
        } else {
            // Select all roles from current page
            $this->selectedRoles = $this->getRoles()->pluck('id')->toArray();
            $this->selectAll = true;
        }
    }

    public function toggleRoleSelection($roleId)
    {
        if (in_array($roleId, $this->selectedRoles)) {
            $this->selectedRoles = array_diff($this->selectedRoles, [$roleId]);
        } else {
            $this->selectedRoles[] = $roleId;
        }
        
        $this->selectAll = count($this->selectedRoles) === $this->getRoles()->count();
    }

    private function getRoles()
    {
        $query = Role::with(['permissions'])->withCount('users');

        // Add soft delete filtering based on active tab
        if ($this->activeTab === 'deleted') {
            $query->withTrashed()->whereNotNull('deleted_at');
        } else {
            $query->whereNull('deleted_at');
        }

        return $query->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);
    }

    /**
     * Translate permission name to a human-readable label
     */
    public function getTranslatedPermissionName($permissionName)
    {
        // Handle special cases first
        $specialCases = [
            'payslip-bulkresend-email' => ['payslips.payslip', 'payslips.bulk_resend_emails'],
            'payslip-bulkresend-sms' => ['payslips.payslip', 'payslips.bulk_resend_sms'],
            'audit_log-read_own_only' => ['reports.journal_d_audit', 'audit_logs.view_own_logs_only'],
            'audit_log-read_all' => ['reports.journal_d_audit', 'common.view'],
        ];
        
        if (isset($specialCases[$permissionName])) {
            return __($specialCases[$permissionName][0]) . ' - ' . __($specialCases[$permissionName][1]);
        }
        
        // Split permission name into module and action (e.g., "company-read" -> ["company", "read"])
        $parts = explode('-', $permissionName, 2);
        
        if (count($parts) !== 2) {
            // If it doesn't match the pattern, return as-is
            return $permissionName;
        }
        
        [$module, $action] = $parts;
        
        // Map module names to translation keys
        $moduleTranslations = [
            'user' => 'Users', // No translation key found, using direct string
            'company' => 'companies.companies',
            'department' => 'departments.departments',
            'service' => 'services.services',
            'employee' => 'employees.employees',
            'payslip' => 'payslips.payslip',
            'role' => 'roles.roles',
            'absence' => 'common.absences',
            'advance_salary' => 'common.advance_salary',
            'leave' => 'common.requested_leaves',
            'leave_type' => 'common.leave_types',
            'overtime' => 'common.overtime',
            'ticking' => 'common.checkins',
            'importjob' => 'common.import_jobs',
            'audit_log' => 'reports.journal_d_audit',
            'setting' => 'roles.settings',
            'report' => 'common.reports',
        ];
        
        // Special handling for actions with underscores (convert to translation key format)
        // e.g., "read_own_only" should map to "audit_logs.view_own_logs_only"
        $underscoreActions = [
            'read_own_only' => 'audit_logs.view_own_logs_only',
            'read_all' => 'common.view',
        ];
        
        // Map actions to translation keys (use underscore variants for actions with underscores)
        $actionTranslations = [
            'read' => 'common.view',
            'create' => 'common.create',
            'update' => 'common.update',
            'delete' => 'common.delete',
            'restore' => 'common.restore',
            'bulkdelete' => 'common.bulk_delete',
            'bulkrestore' => 'common.bulk_restore',
            'import' => 'common.import',
            'export' => 'common.export',
            'sending' => 'common.send',
            'cancel' => 'common.cancel',
            'save' => 'common.save',
            'sms' => 'settings.sms',
            'smtp' => 'settings.smtp',
        ];
        
        // Get translated module name
        if (isset($moduleTranslations[$module])) {
            // Check if it's a translation key (contains a dot) or a direct string
            if (strpos($moduleTranslations[$module], '.') !== false) {
                $translatedModule = __($moduleTranslations[$module]);
            } else {
                $translatedModule = $moduleTranslations[$module];
            }
        } else {
            $translatedModule = ucfirst(str_replace('_', ' ', $module));
        }
        
        // Get translated action name
        // Check for underscore actions first
        if (isset($underscoreActions[$action])) {
            $translatedAction = __($underscoreActions[$action]);
        } elseif (isset($actionTranslations[$action])) {
            $translatedAction = __($actionTranslations[$action]);
        } else {
            // Fallback: convert underscores and hyphens to spaces and capitalize
            $translatedAction = ucfirst(str_replace(['_', '-'], ' ', $action));
        }
        
        // Return formatted as "Module - Action"
        return $translatedModule . ' - ' . $translatedAction;
    }

    public function render()
    {
        if (!Gate::allows('role-read')) {
            return abort(401);
        }

        $roles = $this->getRoles();
        
        // Compute permissions in render method like StratagemAI does
        $permissionsQuery = Permission::where('guard_name', 'web');
        
        if (!empty($this->permissionSearch)) {
            $permissionsQuery->where('name', 'like', '%' . $this->permissionSearch . '%');
        }
        
        $permissions = $permissionsQuery->get()->groupBy(function ($permission) {
            // Group by the part before the first dash
            $parts = explode('-', $permission->name);
            return $parts[0] ?? 'other';
        });
        
        // Get counts for active roles (non-deleted)
        $active_roles = Role::whereNull('deleted_at')->count();
        
        // Get counts for deleted roles
        $deleted_roles = Role::withTrashed()->whereNotNull('deleted_at')->count();
        
        // Legacy count for backward compatibility
        $roles_count = $active_roles;
        
        return view('livewire.portal.roles.index',[
            'roles' => $roles,
            'roles_count' => $roles_count,
            'active_roles' => $active_roles,
            'deleted_roles' => $deleted_roles,
            'permissions' => $permissions,
        ])->layout('components.layouts.dashboard');
    }
}
