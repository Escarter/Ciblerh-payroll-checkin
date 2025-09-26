<?php

namespace App\Livewire\Portal\Roles;

use App\Livewire\Traits\WithDataTable;
use Livewire\Component;
use App\Models\Role;
use Illuminate\Support\Facades\Gate;

class Index extends Component
{
    use WithDataTable;

    // Soft delete properties
    public $activeTab = 'active';
    public $selectedRoles = [];
    public $selectAll = false;

    public $listeners = [
        'roleCreated',
        'roleUpdated'
    ];

    public function roleCreated()
    {
        $this->closeModalAndFlashMessage(__('Rôle et permissions associées créés avec succès!'), 'CreateRoleModal');
    }

    public function roleUpdated()
    {
        // Display success message using the same pattern as roleCreated
        session()->flash('message', __('Role and permissions updated successfully!'));
    }

    //Get & assign selected advance_salary props
    public function initData($role_id)
    {
        $role = Role::findOrFail($role_id);

        $this->role = $role;
    }

    public function editRole($role_id)
    {
        $this->dispatch('editRole', $role_id);
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
                $this->closeModalAndFlashMessage(__('Role moved to trash successfully!'), 'DeleteModal');
            } else {
                $this->closeModalAndFlashMessage(__('Le rôle ne peut pas être supprimé car il est toujours attribué aux utilisateurs-!'), '');
            }
        }
    }

    public function restore($roleId)
    {
        if (!Gate::allows('role-delete')) {
            return abort(401);
        }

        $role = Role::withTrashed()->findOrFail($roleId);
        $role->restore();

        $this->closeModalAndFlashMessage(__('Role restored successfully!'), 'RestoreModal');
    }

    public function forceDelete($roleId)
    {
        if (!Gate::allows('role-delete')) {
            return abort(401);
        }

        $role = Role::withTrashed()->findOrFail($roleId);
        $role->forceDelete();

        $this->closeModalAndFlashMessage(__('Role permanently deleted!'), 'ForceDeleteModal');
    }

    public function bulkDelete()
    {
        if (!Gate::allows('role-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedRoles)) {
            Role::whereIn('id', $this->selectedRoles)->delete(); // Soft delete
            $this->selectedRoles = [];
            $this->selectAll = false;
        }

        $this->closeModalAndFlashMessage(__('Selected roles moved to trash!'), 'BulkDeleteModal');
    }

    public function bulkRestore()
    {
        if (!Gate::allows('role-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedRoles)) {
            Role::withTrashed()->whereIn('id', $this->selectedRoles)->restore();
            $this->selectedRoles = [];
            $this->selectAll = false;
        }

        $this->closeModalAndFlashMessage(__('Selected roles restored!'), 'BulkRestoreModal');
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

        $this->closeModalAndFlashMessage(__('Selected roles permanently deleted!'), 'BulkForceDeleteModal');
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

    public function render()
    {
        if (!Gate::allows('role-read')) {
            return abort(401);
        }

        $roles = $this->getRoles();
        
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
        ])->layout('components.layouts.dashboard');
    }
}
