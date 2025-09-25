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
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\Collection;

class Index extends Component
{
    use WithDataTable;
    
    //
    public $services = [];
    public $roles = [];
    public $departments = [];
    public $employee = null;
    public $employee_id = null;
    public $activeTab = 'active'; // 'active' or 'deleted'
    public $selectedEmployees = [];
    public $selectAll = false;
    public $first_name = null;
    public $last_name = null;
    public $email = null;
    public $professional_phone_number = null;
    public $personal_phone_number = null;
    public $remaining_leave_days = null;
    public $monthly_leave_allocation = null;
    public $net_salary = null;
    public $contract_end = null;
    public $matricule = null;
    public $position = null;
    public $password = null;
    public $salary_grade = null;
    public $department_id = null;
    public $service_id;
    public $role_name = 'employee';
    public $selected_roles = ['employee'];
    public $status = 1;
    public $work_start_time;
    public $work_end_time;
    public $company;
    public $auth_role;
    public $date_of_birth;
    public $employee_file = null;

    //Update & Store Rules
    protected array $rules = [
        'first_name' => 'required',
        'last_name' => 'required',
        'professional_phone_number' => 'required',
        'remaining_leave_days' => 'required',
        'monthly_leave_allocation' => 'required',
        'net_salary' => 'required|integer',
        'email' => 'required|email|unique:users',
        'matricule' => 'required',
        'position' => 'required',
        'salary_grade' => 'required',
        'department_id' => 'required',
        'service_id' => 'required',
        'date_of_birth' => 'required',
        'work_start_time' => 'required|date_format:H:i',
        'work_end_time' => 'required|date_format:H:i|after:work_start_time',
        'selected_roles' => 'required|array|max:2',
    ];

    public function mount($company_uuid)
    {
        $this->company = Company::findOrFail($company_uuid);
        $this->departments = $this->company->departments;
        $this->roles = auth()->user()->getRoleNames()->first() == 'admin' ? Role::orderBy('name', 'desc')->get() : Role::whereIn('name',['employee','supervisor'])->orderBy('name','desc')->get();
        $this->password = Str::random(15);
        $this->work_start_time = Carbon::parse('08:00')->format('H:i');
        $this->work_end_time = Carbon::parse('17:30')->format('H:i');
        $this->auth_role = auth()->user()->getRoleNames()->first();
    }

    public function updatedDepartmentId($department_id)
    {
        if(!is_null($department_id)){
            $this->services = Service::where('department_id', $department_id)->get();
        }
    }

    public function toggleRole($roleName)
    {
        // Ensure employee role is always included
        if ($roleName === 'employee') {
            return; // Don't allow toggling employee role
        }

        if (in_array($roleName, $this->selected_roles)) {
            // Remove role
            $this->selected_roles = array_filter($this->selected_roles, function($role) use ($roleName) {
                return $role !== $roleName;
            });
        } else {
            // Add role if under limit
            if (count($this->selected_roles) < 2) {
                $this->selected_roles[] = $roleName;
            }
        }

        // Ensure employee role is always included
        if (!in_array('employee', $this->selected_roles)) {
            $this->selected_roles[] = 'employee';
        }
    }
 
    public function store()
    {
        if (!Gate::allows('employee-create')) {
            return abort(401);
        }
        
        // Ensure employee role is always included
        if (!in_array('employee', $this->selected_roles)) {
            $this->selected_roles[] = 'employee';
        }

        // Limit to maximum 2 roles
        $this->selected_roles = array_slice($this->selected_roles, 0, 2);
        
        $this->validate();

        $user = User::create([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'professional_phone_number' => $this->professional_phone_number,
            'personal_phone_number' => $this->personal_phone_number,
            'remaining_leave_days' => $this->remaining_leave_days,
            'monthly_leave_allocation' => $this->monthly_leave_allocation,
            'matricule' => $this->matricule,
            'position' => $this->position,
            'net_salary' => $this->net_salary,
            'salary_grade' => $this->salary_grade,
            'contract_end' => $this->contract_end,
            'company_id' => $this->company->id,
            'department_id' => $this->department_id,
            'service_id' => $this->service_id,
            'work_start_time' => $this->work_start_time,
            'work_end_time' => $this->work_end_time,
            'date_of_birth' => $this->date_of_birth,
            'status' => $this->status === "true" ? true : false,
            'password' => bcrypt($this->password),
            'pdf_password' => Str::random(10),
            'author_id' => auth()->user()->id,
        ]);

        // Assign multiple roles - use syncRoles instead of assignRole for arrays
        $user->syncRoles($this->selected_roles);

        event(new EmployeeCreated($user, $this->password));

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Employee created successfully!'), 'CreateEmployeeModal');
    }

    public function update()
    {
        if (!Gate::allows('employee-update')) {
            return abort(401);
        }
        $this->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'professional_phone_number' => 'required',
            'remaining_leave_days' => 'required',
            'monthly_leave_allocation' => 'required',
            'net_salary' => 'required|integer',
            'email' => ['required','email', Rule::unique('users')->ignore($this->employee->id)],
            'matricule' => 'required',
            'position' => 'required',
            'salary_grade' => 'required',
            'department_id' => 'required',
            'service_id' => 'required',
            'date_of_birth' => 'required|date',
            'work_start_time' => 'required|date_format:H:i',
            'work_end_time' => 'required|date_format:H:i|after:work_start_time',
        ]);

        $this->employee->update([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'professional_phone_number' => $this->professional_phone_number,
            'personal_phone_number' => $this->personal_phone_number,
            'remaining_leave_days' => $this->remaining_leave_days,
            'monthly_leave_allocation' => $this->monthly_leave_allocation,
            'matricule' => $this->matricule,
            'position' => $this->position,
            'net_salary' => $this->net_salary,
            'salary_grade' => $this->salary_grade,
            'contract_end' => $this->contract_end,
            'company_id' => $this->company->id,
            'department_id' => $this->department_id,
            'service_id' => $this->service_id,
            'work_start_time' => $this->work_start_time,
            'date_of_birth' => $this->date_of_birth,
            'work_end_time' => $this->work_end_time,
            'status' => $this->status === "true" ? true : false,
            'password' => empty($this->password) ? $this->employee->password : bcrypt($this->password),
            // 'pdf_password' => Str::random(10),
        ]);

        // Ensure employee role is always included
        if (!in_array('employee', $this->selected_roles)) {
            $this->selected_roles[] = 'employee';
        }

        // Limit to maximum 2 roles
        $this->selected_roles = array_slice($this->selected_roles, 0, 2);

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
            $this->selectAll = false;
            $this->closeModalAndFlashMessage(__('Selected employees moved to trash!'), 'BulkDeleteModal');
        }
    }

    public function bulkRestore()
    {
        if (!Gate::allows('employee-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedEmployees)) {
            User::withTrashed()->whereIn('id', $this->selectedEmployees)->restore();
            $this->selectedEmployees = [];
            $this->selectAll = false;
            $this->closeModalAndFlashMessage(__('Selected employees restored!'), 'BulkRestoreModal');
        }
    }

    public function bulkForceDelete()
    {
        if (!Gate::allows('employee-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedEmployees)) {
            User::withTrashed()->whereIn('id', $this->selectedEmployees)->forceDelete();
            $this->selectedEmployees = [];
            $this->selectAll = false;
            $this->closeModalAndFlashMessage(__('Selected employees permanently deleted!'), 'BulkForceDeleteModal');
        }
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
        
        // Update selectAll based on current selection
        $this->selectAll = count($this->selectedEmployees) === $this->getEmployees()->count();
    }

    private function getEmployees()
    {
        $baseQuery = match ($this->auth_role) {
            'supervisor' => User::search($this->query)->supervisor()->whereHas('roles', function($query) {
                $query->where('name', 'employee');
            })->where('company_id', $this->company->id),
            'manager' => User::search($this->query)->manager()->whereHas('roles', function($query) {
                $query->whereIn('name', ['employee', 'supervisor']);
            })->where('company_id', $this->company->id),
            'admin' => User::search($this->query)->whereHas('roles', function($query) {
                $query->whereIn('name', ['admin', 'employee', 'supervisor', 'manager']);
            })->where('company_id', $this->company->id),
            default => User::where('id', 0), // Return empty query for unknown roles
        };

        if ($this->activeTab === 'deleted') {
            return $baseQuery->withTrashed()
                ->whereNotNull('deleted_at')
                ->with(['roles', 'company', 'department', 'service']);
        } else {
            return $baseQuery->with(['roles', 'company', 'department', 'service']);
        }
    }

    public function initData($employee_id)
    {
        $employee = User::findOrFail($employee_id);
        $department = Department::findOrFail($employee->department_id);

        $this->employee = $employee;
        $this->first_name = $employee->first_name;
        $this->last_name = $employee->last_name;
        $this->email = $employee->email;
        $this->professional_phone_number = $employee->professional_phone_number;
        $this->personal_phone_number = $employee->personal_phone_number;
        $this->remaining_leave_days = $employee->remaining_leave_days;
        $this->monthly_leave_allocation = $employee->monthly_leave_allocation;
        $this->net_salary = $employee->net_salary;
        $this->salary_grade = $employee->salary_grade;
        $this->position = $employee->position;
        $this->matricule = $employee->matricule;
        $this->contract_end = $employee->contract_end;
        $this->work_start_time = Carbon::parse($employee->work_start_time)->format('H:i');
        $this->work_end_time = Carbon::parse($employee->work_end_time)->format('H:i');
        $this->status = $employee->status ? "true" : "false";
        $this->services = $department->services;
        $this->date_of_birth = $department->date_of_birth;
        $this->service_id = $employee->service_id;
        $this->department_id = $employee->department_id;
        $this->role_name = $employee->getRoleNames()->first();
        $this->selected_roles = $employee->getRoleNames()->toArray();
    }

    public function initDataManager($employee_id)
    {
        $employee = User::findOrFail($employee_id);

        $this->employee = $employee;
        $this->first_name = $employee->first_name;
        $this->last_name = $employee->last_name;
        $this->email = $employee->email;
        $this->phone_number = $employee->professional_phone_number;
        $this->status = $employee->status ? "true" : "false";
        $this->role_name = $employee->getRoleNames()->first();
    }

    public function updateManager()
    {
        if (!Gate::allows('employee-update')) {
            return abort(401);
        }

        $this->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'professional_phone_number' => 'required',
            'personal_phone_number' => 'required',
            'email' => 'required|email',
        ]);

        $this->employee->update([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'professional_phone_number' => $this->professional_phone_number,
            'personal_phone_number' => $this->personal_phone_number,
            'status' => $this->status === "true" ? true : false,
            'password' => empty($this->password) ? $this->employee->password : bcrypt($this->password),
        ]);

        $this->employee->assignRole($this->role_name);

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Manager updated successfully!'), 'EditManagerModal');
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
        $this->closeModalAndFlashMessage(__('Employee successfully imported!'), 'importEmployeesModal');
    }

    public function export()
    {
        auditLog(
            auth()->user(),
            'employee_exported',
            'web',
            __('Exported excel file for employees for company ') . $this->company->name
        );
        return (new EmployeeExport($this->company, $this->query))->download(ucfirst($this->company->name).'-Employees-' . Str::random(5) . '.xlsx');
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
            'email',
            'professional_phone_number',
            'personal_phone_number',
            'remaining_leave_days',
            'monthly_leave_allocation',
            'matricule',
            'position',
            'net_salary',
            'salary_grade',
            'contract_end',
            'service_id',
            'status',
            'work_start_time',
            'work_end_time',
            'date_of_birth',
            'password',
            'selected_roles',
        ]);
        // Reset to default roles
        $this->selected_roles = ['employee'];
    }
    public function render()
    {
        if (!Gate::allows('employee-read')) {
            return abort(401);
        }
        $employees = $this->getEmployees()->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);

        $employees_count = match ($this->auth_role) {
            'supervisor' => User::supervisor()->with(['roles' => fn($query) => $query->whereIn('name', ['employee'])])->where('company_id', $this->company->id)->where('status',true)->count(),
            'manager' => User::manager()->with(['roles' => fn($query) => $query->whereIn('name', ['employee', 'supervisor',])])->role([])->where('company_id', $this->company->id)->where('status',true)->count(),
            'admin' => User::with(['roles' => fn($query) => $query->whereIn('name', ['employee', 'supervisor','manager'])])->where('company_id', $this->company->id)->where('status',true)->count(),
            default => [],
        };
        $active_employees = match ($this->auth_role) {
            'supervisor' => User::supervisor()->with(['roles' => fn($query) => $query->whereIn('name', ['employee'])])->where('status', true)->where('company_id', $this->company->id)->where('status',true)->count(),
            'manager' => User::manager()->with(['roles' => fn($query) => $query->whereIn('name', ['employee', 'supervisor',])])->where('status', true)->where('company_id', $this->company->id)->where('status',true)->count(),
            'admin' => User::with(['roles' => fn($query) => $query->whereIn('name', ['employee', 'supervisor', 'manager'])])->where('status', true)->where('company_id', $this->company->id)->where('status',true)->count(),
            default => [],
        };
        $banned_employees = match ($this->auth_role) {
            'supervisor' => User::supervisor()->with(['roles' => fn($query) => $query->whereIn('name', ['employee'])])->where('status', false)->where('company_id', $this->company->id)->where('status',true)->count(),
            'manager' => User::manager()->with(['roles' => fn($query) => $query->whereIn('name', ['employee', 'supervisor'])])->where('status', false)->where('company_id', $this->company->id)->where('status',true)->count(),
            'admin' => User::with(['roles' => fn($query) => $query->whereIn('name', ['employee', 'supervisor', 'manager'])])->where('status', false)->where('company_id', $this->company->id)->where('status',true)->count(),
            default => [],
        };

        // Calculate deleted employees count
        $deleted_employees = match ($this->auth_role) {
            'supervisor' => User::withTrashed()->whereNotNull('deleted_at')->supervisor()->whereHas('roles', function($query) {
                $query->where('name', 'employee');
            })->where('company_id', $this->company->id)->count(),
            'manager' => User::withTrashed()->whereNotNull('deleted_at')->manager()->whereHas('roles', function($query) {
                $query->whereIn('name', ['employee', 'supervisor']);
            })->where('company_id', $this->company->id)->count(),
            'admin' => User::withTrashed()->whereNotNull('deleted_at')->whereHas('roles', function($query) {
                $query->whereIn('name', ['admin', 'employee', 'supervisor', 'manager']);
            })->where('company_id', $this->company->id)->count(),
            default => 0,
        };

        return view('livewire.portal.employees.index', [
            'employees' => $employees,
            'employees_count' => $employees_count,
            'active_employees' => $active_employees,
            'banned_employees' => $banned_employees,
            'deleted_employees' => $deleted_employees,
        ])->layout('components.layouts.dashboard');
    }
}
