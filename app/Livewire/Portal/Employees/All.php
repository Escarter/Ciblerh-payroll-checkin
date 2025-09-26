<?php

namespace App\Livewire\Portal\Employees;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Company;
use App\Models\Service;
use Livewire\Component;
use App\Models\Department;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Events\EmployeeCreated;
use App\Exports\EmployeeExport;
use App\Imports\EmployeeImport;
use App\Livewire\Traits\WithDataTable;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\Collection;

class All extends Component
{
    use WithDataTable;

    //
    public $services = [];
    public $roles = [];
    public $departments = [];
    public $employee = null;
    public $employee_id = null;
    public $first_name = null;
    public $last_name = null;
    public $email = null;
    public $professional_phone_number = null;
    public $personal_phone_number = null;
    public $net_salary = null;
    public $contract_end = null;
    public $matricule = null;
    public $position = null;
    public $password = null;
    public $salary_grade = null;
    public $selectedDepartmentId = null;
    public $service_id;
    public $role_name = 'manager';
    public $selected_roles = ['employee'];
    public $status = 1;
    public $work_start_time;
    public $work_end_time;
    public $company;
    public $date_of_birth;
    public $employee_file = null;
    public $role = null;
    public $auth_role = null;
    
    // Soft delete properties
    public $activeTab = 'active';
    public $selectedEmployees = [];
    public $selectAll = false;

    //Update & Store Rules
    protected array $rules = [
        'first_name' => 'required',
        'last_name' => 'required',
        'professional_phone_number' => 'required',
        'personal_phone_number' => 'required',
        'email' => 'required|email',
        'selected_roles' => 'required|array|max:2',
    ];

    public function mount()
    {

        $this->roles = auth()->user()->hasRole('admin') ? Role::orderBy('name', 'desc')->get() : Role::whereNotIn('name', ['admin'])->orderBy('name', 'desc')->get();
        $this->password = Str::random(15);
        $this->work_start_time = Carbon::parse('08:00')->format('H:i');
        $this->work_end_time = Carbon::parse('17:30')->format('H:i');

        $this->auth_role = auth()->user()->getRoleNames()->first();
    }
    public function updatedSelectedDepartmentId($department_id)
    {
        if (!is_null($department_id)) {
            $this->services = Service::where('department_id', $department_id)->get();
        }
    }

    public function toggleRole($roleName)
    {
        // Ensure employee role is always included and cannot be toggled
        if ($roleName === 'employee') {
            return; // Don't allow toggling employee role
        }

        if (in_array($roleName, $this->selected_roles)) {
            // Remove role
            $this->selected_roles = array_filter($this->selected_roles, function($role) use ($roleName) {
                return $role !== $roleName;
            });
        } else {
            // Add role if under limit (max 2 roles including employee)
            if (count($this->selected_roles) < 2) {
                $this->selected_roles[] = $roleName;
            } else {
                $this->addError('selected_roles', 'Maximum 2 roles allowed (including employee role).');
                return;
            }
        }

        // // Ensure employee role is always included and first
        // if (!in_array('employee', $this->selected_roles)) {
        //     $this->selected_roles[] = 'employee';
        // }
        
        // Reorder to put employee role first
        $this->selected_roles = array_unique($this->selected_roles);
        $employeeKey = array_search('employee', $this->selected_roles);
        if ($employeeKey !== false) {
            unset($this->selected_roles[$employeeKey]);
            // Reindex the array
            $this->selected_roles = array_values($this->selected_roles);
        }
        array_unshift($this->selected_roles, 'employee');
    }

    public function store()
    {
        if (!Gate::allows('employee-create')) {
            return abort(401);
        }
        $this->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'matricule' => 'required',
            'professional_phone_number' => 'required',
            'personal_phone_number' => 'required',
            'password' => 'required',
            'date_of_birth' => 'required|date',
            'email' => 'required|email|unique:users',
            'selected_roles' => 'required|array|max:2',
        ]);

        // Validate that employee role is always included
        if (!in_array('employee', $this->selected_roles)) {
            $this->addError('selected_roles', 'Employee role must always be included.');
            return;
        }

        // Validate maximum 2 roles (including employee)
        if (count($this->selected_roles) > 2) {
            $this->addError('selected_roles', 'A user can have a maximum of 2 roles (including the employee role).');
            return;
        }

        // Ensure employee role is first in the array
        $this->selected_roles = array_unique($this->selected_roles);
        $employeeKey = array_search('employee', $this->selected_roles);
        if ($employeeKey !== false) {
            unset($this->selected_roles[$employeeKey]);
            // Reindex the array
            $this->selected_roles = array_values($this->selected_roles);
        }
        array_unshift($this->selected_roles, 'employee');

        $user = User::create([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'matricule' => $this->matricule,
            'email' => $this->email,
            'professional_phone_number' => $this->professional_phone_number,
            'personal_phone_number' => $this->personal_phone_number,
            'date_of_birth' => $this->date_of_birth,
            'status' => $this->status === "true" ? true : false,
            'password' => bcrypt($this->password),
            'pdf_password' => Str::random(10),
        ]);

        // Assign multiple roles - use syncRoles instead of assignRole for arrays
        $user->syncRoles($this->selected_roles);

        event(new EmployeeCreated($user, $this->password));

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Manager created successfully!'), 'CreateManagerModal');
    }
    public function updateManager()
    {
        if (!Gate::allows('employee-update')) {
            return abort(401);
        }

        $this->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'matricule' => 'required',
            'professional_phone_number' => 'required',
            'personal_phone_number' => 'required',
            'date_of_birth' => 'required|date',
            'email' => 'required|email',
            'selected_roles' => 'required|array|max:2',
        ]);

        // Validate that employee role is always included
        if (!in_array('employee', $this->selected_roles)) {
            $this->addError('selected_roles', 'Employee role must always be included.');
            return;
        }

        // Validate maximum 2 roles (including employee)
        if (count($this->selected_roles) > 2) {
            $this->addError('selected_roles', 'A user can have a maximum of 2 roles (including the employee role).');
            return;
        }

        // Ensure employee role is first in the array
        $this->selected_roles = array_unique($this->selected_roles);
        $employeeKey = array_search('employee', $this->selected_roles);
        if ($employeeKey !== false) {
            unset($this->selected_roles[$employeeKey]);
            // Reindex the array
            $this->selected_roles = array_values($this->selected_roles);
        }
        array_unshift($this->selected_roles, 'employee');

        $this->employee->update([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'matricule' => $this->matricule,
            'email' => $this->email,
            'professional_phone_number' => $this->professional_phone_number,
            'personal_phone_number' => $this->personal_phone_number,
            'date_of_birth' => $this->date_of_birth,
            'status' => $this->status === "true" ? true : false,
            'password' => empty($this->password) ? $this->employee->password : bcrypt($this->password),
        ]);

        // Sync roles (this will remove old roles and assign new ones)
        $this->employee->syncRoles($this->selected_roles);

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Manager updated successfully!'), 'EditManagerModal');
    }

    public function update()
    {
        if (!Gate::allows('employee-update')) {
            return abort(401);
        }
        $this->validate();

        $this->employee->update([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'professional_phone_number' => $this->professional_phone_number,
            'personal_phone_number' => $this->personal_phone_number,
            'matricule' => $this->matricule,
            'position' => $this->position,
            'net_salary' => $this->net_salary,
            'salary_grade' => $this->salary_grade,
            'contract_end' => $this->contract_end,
            'company_id' => $this->company->id,
            'department_id' => $this->selectedDepartmentId,
            'service_id' => $this->service_id,
            'work_start_time' => $this->work_start_time,
            'date_of_birth' => $this->date_of_birth,
            'work_end_time' => $this->work_end_time,
            'status' => $this->status === "true" ? true : false,
            'password' => empty($this->password) ? $this->employee->password : bcrypt($this->password),
            // 'pdf_password' => Str::random(10),
        ]);

        // Validate that employee role is always included
        if (!in_array('employee', $this->selected_roles)) {
            $this->addError('selected_roles', 'Employee role must always be included.');
            return;
        }

        // Validate maximum 2 roles (including employee)
        if (count($this->selected_roles) > 2) {
            $this->addError('selected_roles', 'A user can have a maximum of 2 roles (including the employee role).');
            return;
        }

        // Ensure employee role is first in the array
        $this->selected_roles = array_unique($this->selected_roles);
        $employeeKey = array_search('employee', $this->selected_roles);
        if ($employeeKey !== false) {
            unset($this->selected_roles[$employeeKey]);
            // Reindex the array
            $this->selected_roles = array_values($this->selected_roles);
        }
        array_unshift($this->selected_roles, 'employee');

        // Sync roles (this will remove old roles and assign new ones)
        $this->employee->syncRoles($this->selected_roles);

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Employee successfully updated!'), 'EditEmployeeModal');
    }

    public function delete()
    {
        if (!Gate::allows('employee-delete')) {
            return abort(401);
        }

        if (!empty($this->employee)) {
            $this->employee->delete(); // Soft delete
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Employee successfully moved to trash!'), 'DeleteModal');
    }

    public function restore($employeeId)
    {
        if (!Gate::allows('employee-delete')) {
            return abort(401);
        }

        $employee = User::withTrashed()->findOrFail($employeeId);
        $employee->restore();

        $this->closeModalAndFlashMessage(__('Employee successfully restored!'), 'RestoreModal');
    }

    public function forceDelete($employeeId)
    {
        if (!Gate::allows('employee-delete')) {
            return abort(401);
        }

        $employee = User::withTrashed()->findOrFail($employeeId);
        $employee->forceDelete();

        $this->closeModalAndFlashMessage(__('Employee permanently deleted!'), 'ForceDeleteModal');
    }

    public function bulkDelete()
    {
        if (!Gate::allows('employee-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedEmployees)) {
            User::whereIn('id', $this->selectedEmployees)->delete(); // Soft delete
            $this->selectedEmployees = [];
        }

        $this->closeModalAndFlashMessage(__('Selected employees moved to trash!'), 'BulkDeleteModal');
    }

    public function bulkRestore()
    {
        if (!Gate::allows('employee-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedEmployees)) {
            User::withTrashed()->whereIn('id', $this->selectedEmployees)->restore();
            $this->selectedEmployees = [];
        }

        $this->closeModalAndFlashMessage(__('Selected employees restored!'), 'BulkRestoreModal');
    }

    public function bulkForceDelete()
    {
        if (!Gate::allows('employee-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedEmployees)) {
            User::withTrashed()->whereIn('id', $this->selectedEmployees)->forceDelete();
            $this->selectedEmployees = [];
        }

        $this->closeModalAndFlashMessage(__('Selected employees permanently deleted!'), 'BulkForceDeleteModal');
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->selectedEmployees = [];
        $this->selectAll = false;
    }

    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedEmployees = $this->getEmployees()->pluck('id')->toArray();
        } else {
            $this->selectedEmployees = [];
        }
    }

    public function toggleEmployeeSelection($employeeId)
    {
        if (in_array($employeeId, $this->selectedEmployees)) {
            $this->selectedEmployees = array_diff($this->selectedEmployees, [$employeeId]);
        } else {
            $this->selectedEmployees[] = $employeeId;
        }
        
        $this->selectAll = count($this->selectedEmployees) === $this->getEmployees()->count();
    }

    private function getEmployees()
    {
        $query = User::search($this->query)->with([
            'company:id,name',
            'department:id,name',
            'service:id,name',
            'roles:id,name'
        ])->whereHas('roles', function($query) {
            match ($this->auth_role) {
                'supervisor' => $query->where('name', 'employee'),
                'manager' => $query->whereIn('name', ['employee', 'supervisor']),
                'admin' => $query->whereIn('name', ['admin', 'employee', 'supervisor', 'manager']),
                default => $query->where('name', 'employee'),
            };
        });

        // Add soft delete filtering based on active tab
        if ($this->activeTab === 'deleted') {
            $query->withTrashed()->whereNotNull('deleted_at');
        } else {
            $query->whereNull('deleted_at');
        }

        // Add role-based filtering
        match ($this->auth_role) {
            'supervisor' => $query->supervisor(),
            'manager' => $query->manager(),
            'admin' => null, // No additional filtering for admin
            default => $query->supervisor(),
        };

        return $query->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);
    }
    public function initDataManager($employee_id)
    {
        $employee = User::findOrFail($employee_id);

        $this->employee = $employee;
        $this->first_name = $employee->first_name;
        $this->last_name = $employee->last_name;
        $this->matricule = $employee->matricule;
        $this->email = $employee->email;
        $this->professional_phone_number = $employee->professional_phone_number;
        $this->personal_phone_number = $employee->personal_phone_number;
        $this->date_of_birth = $employee->date_of_birth;
        $this->status = $employee->status ? "true" : "false";
        $this->role_name = $employee->getRoleNames()->first();
        
        // Reset selected_roles to the employee's actual roles
        $this->selected_roles = $employee->getRoleNames()->toArray();
        
        // Refresh Choices.js for manager edit modal to avoid stale selections
        $choicesOptions = $this->roles
            ->pluck('name', 'name')
            ->map(function ($name) {
                return ucfirst($name);
            })
            ->toArray();
        $this->dispatch('refreshChoices', id: 'edit_manager_roles', options: $choicesOptions, selected: $this->selected_roles);
        
        
        
    }

    public function initData($employee_id)
    {
        $employee = User::findOrFail($employee_id);

        $department = Department::findOrFail($employee->department_id);

        $this->company = $employee->company;
        $this->departments = $this->role === 'supervisor' ? Department::where('company_id', $this->company->id)->supervisor()->get() : $this->company->departments;
        $this->services = $department->services;
        $this->employee = $employee;
        $this->first_name = $employee->first_name;
        $this->last_name = $employee->last_name;
        $this->email = $employee->email;
        $this->professional_phone_number = $employee->professional_phone_number;
        $this->personal_phone_number = $employee->personal_phone_number;
        $this->net_salary = $employee->net_salary;
        $this->salary_grade = $employee->salary_grade;
        $this->position = $employee->position;
        $this->matricule = $employee->matricule;
        $this->contract_end = $employee->contract_end;
        $this->work_start_time = Carbon::parse($employee->work_start_time)->format('H:i');
        $this->work_end_time = Carbon::parse($employee->work_end_time)->format('H:i');
        $this->status = $employee->status ? "true" : "false";
        $this->service_id = $employee->service_id;
        $this->date_of_birth = $employee->date_of_birth;
        $this->selectedDepartmentId = $employee->department_id;
        $this->role_name = $employee->getRoleNames()->first();
        
        // Get the employee's actual roles and reset selected_roles
        $this->selected_roles = $employee->getRoleNames()->toArray();
        
        // Refresh Choices.js for employee edit modal to avoid stale selections
        $choicesOptions = $this->roles
            ->pluck('name', 'name')
            ->map(function ($name) {
                return ucfirst($name);
            })
            ->toArray();
        $this->dispatch('refreshChoices', id: 'edit_selected_roles', options: $choicesOptions, selected: $this->selected_roles);
   }

    public function import()
    {
        $this->validate([
            'employee_file' => 'sometimes|nullable|mimes:xlsx,csv|max:500',
        ]);
        
        Excel::import(new EmployeeImport($this->company), $this->employee_file);
        auditLog(
            auth()->user(),
            'employee_imported',
            'web',
            __('Imported excel file for employees for company ') . $this->company->name
        );
        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Employee successfully imported, if nothing happened make sure, the email, department, services of employees are rightly mapped.!'), 'importEmployeesModal');
    }

    public function export()
    {
        auditLog(
            auth()->user(),
            'employee_exported',
            'web',
            __('Exported excel file for all employees')
        );
        return (new EmployeeExport($this->company, $this->query))->download('All-Employees-' . Str::random(5) . '.xlsx');
    }

    public function close()
    {
        $this->clearFields();
    }

    public function clearFields()
    {
        $this->reset([
            'first_name',
            'last_name',
            'date_of_birth',
            'email',
            'professional_phone_number',
            'personal_phone_number',
            'matricule',
            'position',
            'net_salary',
            'salary_grade',
            'contract_end',
            'service_id',
            'status',
            'work_start_time',
            'work_end_time',
            'password',
            'selected_roles',
        ]);
        // Reset to default roles (only employee role)
        $this->selected_roles = ['employee'];
    }
    public function render()
    {
        if (!Gate::allows('employee-read')) {
            return abort(401);
        }

        $employees = $this->getEmployees();

        // Get counts for active employees (non-deleted)
        $active_employees = match ($this->auth_role) {
            'supervisor' => User::supervisor()->with('roles')->whereNull('deleted_at')->whereHas('roles', function($query) {
                $query->where('name', 'employee');
            })->count(),
            'manager' => User::manager()->with('roles')->whereNull('deleted_at')->whereHas('roles', function($query) {
                $query->whereIn('name', ['employee', 'supervisor']);
            })->count(),
            'admin' => User::with('roles')->whereNull('deleted_at')->whereHas('roles', function($query) {
                $query->whereIn('name', ['admin', 'employee', 'supervisor', 'manager']);
            })->count(),
            default => 0,
        };

        // Get counts for deleted employees
        $deleted_employees = match ($this->auth_role) {
            'supervisor' => User::supervisor()->with('roles')->withTrashed()->whereNotNull('deleted_at')->whereHas('roles', function($query) {
                $query->where('name', 'employee');
            })->count(),
            'manager' => User::manager()->with('roles')->withTrashed()->whereNotNull('deleted_at')->whereHas('roles', function($query) {
                $query->whereIn('name', ['employee', 'supervisor']);
            })->count(),
            'admin' => User::with('roles')->withTrashed()->whereNotNull('deleted_at')->whereHas('roles', function($query) {
                $query->whereIn('name', ['admin', 'employee', 'supervisor', 'manager']);
            })->count(),
            default => 0,
        };

        // Legacy counts for backward compatibility
        $employees_count = $active_employees;
        $banned_employees = match ($this->auth_role) {
            'supervisor' => User::supervisor()->with('roles')->whereNull('deleted_at')->where('status', false)->whereHas('roles', function($query) {
                $query->where('name', 'employee');
            })->count(),
            'manager' => User::manager()->with('roles')->whereNull('deleted_at')->where('status', false)->whereHas('roles', function($query) {
                $query->whereIn('name', ['employee', 'supervisor']);
            })->count(),
            'admin' => User::with('roles')->whereNull('deleted_at')->where('status', false)->whereHas('roles', function($query) {
                $query->whereIn('name', ['admin', 'employee', 'supervisor', 'manager']);
            })->count(),
            default => 0,
        };

        return view('livewire.portal.employees.all', [
            'employees' => $employees,
            'employees_count' => $employees_count,
            'active_employees' => $active_employees,
            'deleted_employees' => $deleted_employees,
            'banned_employees' => $banned_employees,
        ])->layout('components.layouts.dashboard');
    }
}
