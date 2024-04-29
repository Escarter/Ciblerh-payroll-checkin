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
    public $status = 1;
    public $work_start_time;
    public $work_end_time;
    public $company;
    public $auth_role;
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
        'work_start_time' => 'required|date_format:H:i',
        'work_end_time' => 'required|date_format:H:i|after:work_start_time',
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
 
    public function store()
    {
        if (!Gate::allows('employee-create')) {
            return abort(401);
        }
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
            'status' => $this->status === "true" ?  1 : 0,
            'password' => bcrypt($this->password),
            'pdf_password' => Str::random(10),
            'author_id' => auth()->user()->id,
        ]);

        $user->assignRole($this->role_name);

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
            'work_end_time' => $this->work_end_time,
            'status' => $this->status === "true" ?  1 : 0,
            'password' => empty($this->password) ? $this->employee->password : bcrypt($this->password),
            // 'pdf_password' => Str::random(10),
        ]);

        if($this->employee->getRoleNames()->first() != $this->role_name){
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

            $this->employee->forceDelete();
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Employee successfully deleted!'), 'DeleteModal');
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
        $this->status = $employee->status ;
        $this->services = $department->services;
        $this->service_id = $employee->service_id;
        $this->department_id = $employee->department_id;
        $this->role_name = $employee->getRoleNames()->first();
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
                'roles' => fn($query) => $query->whereIn('name', ['employee'])
            ])->where('company_id', $this->company->id)->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage),

            'manager' => User::search($this->query)->manager()->with([
                'company:id,name',
                'department:id,name',
                'service:id,name',
                'roles' => fn($query) => $query->whereIn('name', ['employee', 'supervisor'])
            ])->where('company_id', $this->company->id)->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage),

            'admin' => User::search($this->query)->with([
                'company:id,name',
                'department:id,name',
                'service:id,name',
                'roles' => fn($query) => $query->whereIn('name', ['employee', 'supervisor', 'manager'])
            ])->where('company_id', $this->company->id)->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage),
            default => [],
        };

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

        return view('livewire.portal.employees.index', [
            'employees' => $employees,
            'employees_count' => $employees_count,
            'active_employees' => $active_employees,
            'banned_employees' => $banned_employees,
        ])->layout('components.layouts.dashboard');
    }
}
