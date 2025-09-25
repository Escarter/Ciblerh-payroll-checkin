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
        $this->availableRoles = Role::orderBy('name')->get()->toArray();
    }

    public function assignRoles()
    {
        if (!Gate::allows('employee-update')) {
            return abort(401);
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
