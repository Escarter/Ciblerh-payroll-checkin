<?php

namespace App\Livewire\Portal\Employees\Partial;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Gate;
use App\Models\Role;

class UserRoles extends Component
{
    public $user;
    public $userId;
    public $userRoles = [];
    public $availableRoles = [];
    public $selectedRoles = [];
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
        $this->selectedRoles = $this->user->roles->pluck('name')->toArray();
        $this->showModal = true;
    }

    public function loadAvailableRoles()
    {
        // Role assignment permissions based on user role
        $userRole = auth()->user()->getRoleNames()->first();
        $this->availableRoles = match ($userRole) {
            'admin' => Role::orderBy('name')->get()->toArray(),
            'manager' => Role::whereIn('name', ['employee', 'supervisor'])->orderBy('name')->get()->toArray(),
            'supervisor' => Role::where('name', 'employee')->orderBy('name')->get()->toArray(), // Supervisors can only assign employee role
            default => Role::where('name', 'employee')->orderBy('name')->get()->toArray(),
        };
    }

    public function assignRoles()
    {
        if (!Gate::allows('employee-update')) {
            return abort(401);
        }

        // Validate role assignment permissions based on user role
        $userRole = auth()->user()->getRoleNames()->first();
        $allowedRoles = match ($userRole) {
            'admin' => ['admin', 'manager', 'supervisor', 'employee'],
            'manager' => ['employee', 'supervisor'],
            'supervisor' => ['employee'], // Supervisors can only assign employee role
            default => ['employee'],
        };
        
        // Check if any selected role is not allowed
        $unauthorizedRoles = array_diff($this->selectedRoles, $allowedRoles);
        if (!empty($unauthorizedRoles)) {
            $this->addError('selectedRoles', 'You do not have permission to assign the following roles: ' . implode(', ', $unauthorizedRoles));
            return;
        }

        // Validate maximum 2 roles
        if (count($this->selectedRoles) > 2) {
            $this->addError('selectedRoles', 'A user can have a maximum of 2 roles.');
            return;
        }

        // Ensure employee role is always included
        if (!in_array('employee', $this->selectedRoles)) {
            $this->selectedRoles[] = 'employee';
        }

        // Sync roles (this will remove old roles and assign new ones)
        $this->user->syncRoles($this->selectedRoles);

        // Refresh user roles
        $this->user->refresh();
        $this->userRoles = $this->user->roles->toArray();

        session()->flash('message', 'Roles updated successfully!');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['userId', 'user', 'userRoles', 'selectedRoles']);
    }

    public function render()
    {
        return view('livewire.portal.employees.partial.user-roles');
    }
}
