<?php

namespace App\Livewire\Portal\Employees\Partial;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class UserRoles extends Component
{
    public $user;
    public $userId;
    public $userRoles = [];
    public $availableRoles = [];
    public $selectedRole = '';
    public $showModal = false;

    protected $listeners = ['showUserRoles'];

    public function mount()
    {
        $this->loadAvailableRoles();
    }

    public function showUserRoles($userId)
    {
        if (!Gate::allows('employee-view')) {
            return abort(401);
        }

        $this->userId = $userId;
        $this->user = User::with('roles')->findOrFail($userId);
        $this->userRoles = $this->user->roles->toArray();
        $this->showModal = true;
    }

    public function loadAvailableRoles()
    {
        $this->availableRoles = Role::orderBy('name')->get()->toArray();
    }

    public function assignRole()
    {
        if (!Gate::allows('employee-update')) {
            return abort(401);
        }

        if (empty($this->selectedRole)) {
            $this->addError('selectedRole', 'Please select a role to assign.');
            return;
        }

        // Check if user already has this role
        if ($this->user->hasRole($this->selectedRole)) {
            $this->addError('selectedRole', 'User already has this role.');
            return;
        }

        // Assign the role
        $this->user->assignRole($this->selectedRole);

        // Ensure employee role is always assigned
        if (!$this->user->hasRole('employee')) {
            $this->user->assignRole('employee');
        }

        // Refresh user roles
        $this->user->refresh();
        $this->userRoles = $this->user->roles->toArray();
        $this->selectedRole = '';

        session()->flash('message', 'Role assigned successfully!');
    }

    public function removeRole($roleId)
    {
        if (!Gate::allows('employee-update')) {
            return abort(401);
        }

        $role = Role::findOrFail($roleId);
        
        // Prevent removing the employee role if it's the only role
        if ($role->name === 'employee' && $this->user->roles->count() === 1) {
            $this->addError('removeRole', 'Cannot remove employee role when it\'s the only role assigned.');
            return;
        }

        $this->user->removeRole($role);
        
        // Refresh user roles
        $this->user->refresh();
        $this->userRoles = $this->user->roles->toArray();

        session()->flash('message', 'Role removed successfully!');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['userId', 'user', 'userRoles', 'selectedRole']);
    }

    public function render()
    {
        return view('livewire.portal.employees.partial.user-roles');
    }
}
