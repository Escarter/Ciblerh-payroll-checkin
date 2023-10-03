<?php

namespace App\Livewire\Portal\Roles;

use App\Livewire\Traits\WithDataTable;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Gate;

class Index extends Component
{
    use WithDataTable;

    public $listeners = [
        'roleCreated'
    ];

    public function roleCreated()
    {
        $this->refresh(__('Rôle et permissions associées créés avec succès!'), 'CreateRoleModal');
    }

    //Get & assign selected advance_salary props
    public function initData($role_id)
    {
        $role = Role::findOrFail($role_id);

        $this->role = $role;
    }


    public function delete()
    {
        if (!Gate::allows('role-delete')) {
            return abort(401);
        }

        if (!empty($this->role)) {

            if(count($this->role->users) <= 0) {
                $this->role->syncPermissions([]);
                $this->role->delete();
                $this->closeModalAndFlashMessage(__('Role and permission deleted successfully!'), 'DeleteModal');
            }else{
                $this->closeModalAndFlashMessage(__('Le rôle ne peut pas être supprimé car il est toujours attribué aux utilisateurs-!'), '');
            }
        }


    }

    public function render()
    {
        if (!Gate::allows('role-read')) {
            return abort(401);
        }

        $roles = Role::with(['permissions'])->withCount('users')->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);
        $roles_count = Role::count();
        
        return view('livewire.portal.roles.index',[
            'roles'=>$roles,
            'roles_count' => $roles_count,
        ])->layout('components.layouts.dashboard');
    }
}
