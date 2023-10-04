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
    public $status = 1;
    public $work_start_time;
    public $work_end_time;
    public $company;
    public $employee_file = null;
    public $role = null;
    public $auth_role = null;

    //Update & Store Rules
    protected array $rules = [
        'first_name' => 'required',
        'last_name' => 'required',
        'professional_phone_number' => 'required',
        'personal_phone_number' => 'required',
        'email' => 'required|email',
    ];

    public function mount()
    {

        $this->roles = auth()->user()->getRoleNames()->first() === 'admin' ? Role::orderBy('name', 'desc')->get() : Role::whereIn('name', ['employee', 'supervisor'])->orderBy('name', 'desc')->get();
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

    public function store()
    {
        if (!Gate::allows('employee-create')) {
            return abort(401);
        }
        $this->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'personal_phone_number' => 'required',
            'password' => 'required',
            'email' => 'required|email|unique:users',
        ]);

        $user = User::create([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'professional_phone_number' => $this->professional_phone_number,
            'personal_phone_number' => $this->personal_phone_number,
            'status' => $this->status === "true" ?  1 : 0,
            'password' => bcrypt($this->password),
            'pdf_password' => Str::random(10),
        ]);

        $user->assignRole($this->role_name);

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
            'status' => $this->status === "true" ?  1 : 0,
            'password' => empty($this->password) ? $this->employee->password : bcrypt($this->password),
        ]);

        $this->employee->assignRole($this->role_name);

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
            'work_end_time' => $this->work_end_time,
            'status' => $this->status === "true" ?  1 : 0,
            'password' => empty($this->password) ? $this->employee->password : bcrypt($this->password),
            'pdf_password' => Str::random(10),
        ]);

        if ($this->employee->getRoleNames()->first() != $this->role_name) {
            $this->employee->syncRoles($this->role_name);
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Employee successfully updated!'), 'EditEmployeeModal');
    }

    public function delete()
    {
        if (!Gate::allows('employee-delete')) {
            return abort(401);
        }

        if (!empty($this->employee)) {

            $this->employee->delete();
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Employee successfully deleted!'), 'DeleteModal');
    }
    public function initDataManager($employee_id)
    {
        $employee = User::findOrFail($employee_id);

        $this->employee = $employee;
        $this->first_name = $employee->first_name;
        $this->last_name = $employee->last_name;
        $this->email = $employee->email;
        $this->phone_number = $employee->professional_phone_number;
        $this->status = $employee->status;
        $this->role_name = $employee->getRoleNames()->first();
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
        $this->status = $employee->status;
        $this->service_id = $employee->service_id;
        $this->selectedDepartmentId = $employee->department_id;
        $this->role_name = $employee->getRoleNames()->first();
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
        ]);
    }
    public function render()
    {
        if (!Gate::allows('employee-read')) {
            return abort(401);
        }

        $employees  = match ($this->auth_role) {
            'supervisor' => User::search($this->query)->supervisor()->with([
                'company:id,name',
                'department:id,name',
                'service:id,name',
                'roles:id,name'
            ])->role('employee')->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage),

            'manager' => User::search($this->query)->manager()->with([
                'company:id,name',
                'department:id,name',
                'service:id,name',
                'roles:id,name'
            ])->role(['employee', 'supervisor'])->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage),

            'admin' => User::search($this->query)->with([
                'company:id,name',
                'department:id,name',
                'service:id,name',
                'roles:id,name'
            ])->role(['admin', 'employee', 'supervisor', 'manager'])->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage),
            default => [],
        };

        $employees_count = match ($this->auth_role) {
            'supervisor' => User::supervisor()->with('roles')->role(['employee'])->count(),
            'manager' => User::manager()->with('roles')->role(['employee', 'supervisor'])->count(),
            'admin' => User::with('roles')->role(['admin', 'employee', 'supervisor', 'manager'])->count(),
            default => [],
        };
        $active_employees = match ($this->auth_role) {
            'supervisor' => User::supervisor()->with('roles')->where('status', true)->role(['employee'])->count(),
            'manager' => User::manager()->with('roles')->where('status', true)->role(['employee', 'supervisor'])->count(),
            'admin' => User::with('roles')->where('status', true)->role(['admin', 'employee', 'supervisor', 'manager'])->count(),
            default => [],
        };
        $banned_employees = match ($this->auth_role) {
            'supervisor' => User::supervisor()->with('roles')->where('status', false)->role(['employee'])->count(),
            'manager' => User::manager()->with('roles')->where('status', false)->role(['employee', 'supervisor'])->count(),
            'admin' => User::with('roles')->where('status', false)->role(['admin', 'employee', 'supervisor', 'manager'])->count(),
            default => [],
        };


        return view('livewire.portal.employees.all', [
            'employees' => $employees,
            'employees_count' => $employees_count,
            'active_employees' => $active_employees,
            'banned_employees' => $banned_employees,
        ])->layout('components.layouts.dashboard');
    }
}
