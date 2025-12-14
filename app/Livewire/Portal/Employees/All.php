<?php

namespace App\Livewire\Portal\Employees;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Company;
use App\Models\Service;
use App\Livewire\BaseImportComponent;
use App\Models\Department;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Events\EmployeeCreated;
use App\Exports\EmployeeExport;
use App\Imports\EmployeeImport;
use App\Livewire\Traits\WithDataTable;
use App\Models\Role;
use App\Rules\PhoneNumber;
use App\Rules\ValidEmail;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\Collection;

class All extends BaseImportComponent
{
    use WithDataTable;

    protected $importType = 'employees';
    protected $importPermission = 'employee-import';

    // Cache for existing employee data to avoid N+1 queries
    protected $existingEmployeeEmails;
    protected $existingEmployeeMatricules;

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
    public bool $receive_sms_notifications = true;
    public bool $receive_email_notifications = true;
    public $alternative_email = null;
    public $isEditMode = false;
    
    // Soft delete properties
    public $activeTab = 'active';
    public $selectedEmployees = [];
    public $selectedEmployeesForDelete = [];
    public $selectAll = false;

    //Update & Store Rules - using string-based validation to avoid new expressions in property
    protected array $rules = [
        'first_name' => 'required',
        'last_name' => 'required',
        'professional_phone_number' => 'required',
        'personal_phone_number' => 'required',
        'email' => 'required|email',
        'selected_roles' => 'required|array|max:2',
        'selectedCompanyId' => 'required_if:!company,null',
    ];

    public function mount()
    {
        $this->initializePreview();

        $this->roles = auth()->user()->hasRole('admin') ? Role::orderBy('name', 'desc')->get() : Role::whereNotIn('name', ['admin'])->orderBy('name', 'desc')->get();
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

    /**
     * Validate company selection for import
     */
    protected function validateCompanySelection(): bool
    {
        if (!$this->company && !$this->selectedCompanyId) {
            $this->addError('selectedCompanyId', __('employees.company_required_for_import'));
            return false;
        }
        return true;
    }

    /**
     * Override goToPreview to validate company selection first
     */
    public function goToPreview(): void
    {
        if (!$this->validateCompanySelection()) {
            return;
        }

        parent::goToPreview();
    }

    /**
     * Override import to validate company selection
     */
    public function import()
    {
        if (!$this->validateCompanySelection()) {
            return;
        }

        parent::import();
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
            'professional_phone_number' => ['required', new PhoneNumber()],
            'personal_phone_number' => ['required', new PhoneNumber()],
            'password' => 'required',
            'date_of_birth' => 'required|date',
            'email' => ['required', new ValidEmail(), 'unique:users'],
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

        // Format phone numbers before saving
        $professionalPhone = validatePhoneNumber($this->professional_phone_number);
        $personalPhone = validatePhoneNumber($this->personal_phone_number);
        
        if (!$professionalPhone['valid']) {
            $this->addError('professional_phone_number', $professionalPhone['error']);
            return;
        }
        
        if (!$personalPhone['valid']) {
            $this->addError('personal_phone_number', $personalPhone['error']);
            return;
        }

        $user = User::create([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'matricule' => $this->matricule,
            'email' => $this->email,
            'professional_phone_number' => $professionalPhone['formatted'],
            'personal_phone_number' => $personalPhone['formatted'],
            'date_of_birth' => $this->date_of_birth,
            'status' => $this->status === "true" ? true : false,
            'password' => bcrypt($this->password),
            'pdf_password' => Str::random(10),
        ]);

        // Assign multiple roles - use syncRoles instead of assignRole for arrays
        $user->syncRoles($this->selected_roles);

        event(new EmployeeCreated($user, $this->password));

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.manager_created_successfully'), 'CreateManagerModal');
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
            'professional_phone_number' => ['required', new PhoneNumber()],
            'personal_phone_number' => ['required', new PhoneNumber()],
            'date_of_birth' => 'required|date',
            'email' => ['required', new ValidEmail()],
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

        // Format phone numbers before updating
        $professionalPhone = validatePhoneNumber($this->professional_phone_number);
        $personalPhone = validatePhoneNumber($this->personal_phone_number);
        
        if (!$professionalPhone['valid']) {
            $this->addError('professional_phone_number', $professionalPhone['error']);
            return;
        }
        
        if (!$personalPhone['valid']) {
            $this->addError('personal_phone_number', $personalPhone['error']);
            return;
        }

        $this->employee->update([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'matricule' => $this->matricule,
            'email' => $this->email,
            'professional_phone_number' => $professionalPhone['formatted'],
            'personal_phone_number' => $personalPhone['formatted'],
            'date_of_birth' => $this->date_of_birth,
            'status' => $this->status === "true" ? true : false,
            'password' => empty($this->password) ? $this->employee->password : bcrypt($this->password),
        ]);

        // Sync roles (this will remove old roles and assign new ones)
        $this->employee->syncRoles($this->selected_roles);

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.manager_updated_successfully'), 'EditManagerModal');
    }

    public function update()
    {
        if (!Gate::allows('employee-update')) {
            return abort(401);
        }
        $this->validate();

        // Format phone numbers before updating
        $professionalPhone = validatePhoneNumber($this->professional_phone_number);
        $personalPhone = validatePhoneNumber($this->personal_phone_number);
        
        if (!$professionalPhone['valid']) {
            $this->addError('professional_phone_number', $professionalPhone['error']);
            return;
        }
        
        if (!$personalPhone['valid']) {
            $this->addError('personal_phone_number', $personalPhone['error']);
            return;
        }

        $this->employee->update([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'professional_phone_number' => $professionalPhone['formatted'],
            'personal_phone_number' => $personalPhone['formatted'],
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
            'receive_sms_notifications' => $this->receive_sms_notifications === true || $this->receive_sms_notifications === "true" || $this->receive_sms_notifications === 1,
            'receive_email_notifications' => $this->receive_email_notifications === true || $this->receive_email_notifications === "true" || $this->receive_email_notifications === 1,
            'alternative_email' => $this->alternative_email,
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
        $this->closeModalAndFlashMessage(__('employees.employee_updated_successfully'), 'EmployeeModal');
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
        $this->closeModalAndFlashMessage(__('employees.employee_successfully_moved_to_trash'), 'DeleteModal');
    }

    public function restore()
    {
        if (!Gate::allows('employee-delete')) {
            return abort(401);
        }

        $employee = User::withTrashed()->findOrFail($this->employee_id);
        $employee->restore();

        $this->closeModalAndFlashMessage(__('employees.employee_successfully_restored'), 'RestoreModal');
    }

    public function forceDelete($employeeId)
    {
        if (!Gate::allows('employee-delete')) {
            return abort(401);
        }

        $employee = User::withTrashed()->findOrFail($employeeId);
        
        // Check if employee has related records
        $hasRelatedRecords = $employee->leaves()->count() > 0 ||
                           $employee->tickings()->count() > 0 ||
                           $employee->absences()->count() > 0 ||
                           $employee->advanceSalaries()->count() > 0 ||
                           $employee->overtimes()->count() > 0 ||
                           $employee->payslips()->count() > 0 ||
                           $employee->supDepartments()->count() > 0;
        
        if ($hasRelatedRecords) {
            $this->showToast(__('employees.cannot_permanently_delete_employee'), 'danger');
            return;
        }
        
        $employee->forceDelete();

        $this->closeModalAndFlashMessage(__('employees.employee_permanently_deleted'), 'ForceDeleteModal');
    }

    public function bulkDelete()
    {
        if (!Gate::allows('employee-delete')) {
            return abort(401);
        }

        $selectedArray = $this->activeTab === 'deleted' ? $this->selectedEmployeesForDelete : $this->selectedEmployees;

        if (!empty($selectedArray)) {
            User::whereIn('id', $selectedArray)->delete(); // Soft delete
            if ($this->activeTab === 'deleted') {
                $this->selectedEmployeesForDelete = [];
            } else {
                $this->selectedEmployees = [];
            }
        }

        $this->closeModalAndFlashMessage(__('employees.selected_employees_moved_to_trash'), 'BulkDeleteModal');
    }

    public function bulkRestore()
    {
        if (!Gate::allows('employee-delete')) {
            return abort(401);
        }

        $selectedArray = $this->activeTab === 'deleted' ? $this->selectedEmployeesForDelete : $this->selectedEmployees;

        if (!empty($selectedArray)) {
            User::withTrashed()->whereIn('id', $selectedArray)->restore();
            if ($this->activeTab === 'deleted') {
                $this->selectedEmployeesForDelete = [];
            } else {
                $this->selectedEmployees = [];
            }
        }

        $this->closeModalAndFlashMessage(__('employees.selected_employees_restored'), 'BulkRestoreModal');
    }

    public function bulkForceDelete()
    {
        if (!Gate::allows('employee-delete')) {
            return abort(401);
        }

        $selectedArray = $this->activeTab === 'deleted' ? $this->selectedEmployeesForDelete : $this->selectedEmployees;

        if (!empty($selectedArray)) {
            $employees = User::withTrashed()->whereIn('id', $selectedArray)->get();
            $employeesWithRelatedRecords = [];

            foreach ($employees as $employee) {
                $hasRelatedRecords = $employee->leaves()->count() > 0 ||
                                   $employee->tickings()->count() > 0 ||
                                   $employee->absences()->count() > 0 ||
                                   $employee->advanceSalaries()->count() > 0 ||
                                   $employee->overtimes()->count() > 0 ||
                                   $employee->payslips()->count() > 0 ||
                                   $employee->supDepartments()->count() > 0;

                if ($hasRelatedRecords) {
                    $employeesWithRelatedRecords[] = $employee->name;
                }
            }

            if (!empty($employeesWithRelatedRecords)) {
                $employeeNames = implode(', ', $employeesWithRelatedRecords);
                $this->showToast(__('employees.cannot_permanently_delete_employees') . $employeeNames, 'danger');
                return;
            }

            foreach ($employees as $employee) {
                $employee->forceDelete();
            }

            if ($this->activeTab === 'deleted') {
                $this->selectedEmployeesForDelete = [];
            } else {
                $this->selectedEmployees = [];
            }
        }

        $this->closeModalAndFlashMessage(__('employees.selected_employees_permanently_deleted'), 'BulkForceDeleteModal');
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->selectedEmployees = [];
        $this->selectedEmployeesForDelete = [];
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

    public function selectAllVisible()
    {
        $this->selectedEmployees = $this->getEmployees()->pluck('id')->toArray();
    }

    public function selectAllVisibleForDelete()
    {
        $this->selectedEmployeesForDelete = $this->getEmployees()->pluck('id')->toArray();
    }

    public function selectAllEmployees()
    {
        $query = User::search($this->query)->with(['company', 'department', 'service']);

        // Add soft delete filtering based on active tab
        if ($this->activeTab === 'deleted') {
            $query->withTrashed()->whereNotNull('deleted_at');
        } else {
            $query->whereNull('deleted_at');
        }

        // Add role-based filtering
        match($this->role){
            "supervisor" => $query->whereHas('department', function ($q) {
                $q->whereIn('id', auth()->user()->supDepartments->pluck('department_id'));
            }),
            "manager" => $query->whereHas('department', function ($q) {
                $q->where('company_id', auth()->user()->company_id);
            }),
            "admin" => null, // No additional filtering for admin
            default => [],
        };

        $this->selectedEmployees = $query->pluck('id')->toArray();
    }

    public function selectAllDeletedEmployees()
    {
        $query = User::search($this->query)->with(['company', 'department', 'service']);

        // Add soft delete filtering for deleted tab
        $query->withTrashed()->whereNotNull('deleted_at');

        // Add role-based filtering
        match($this->role){
            "supervisor" => $query->whereHas('department', function ($q) {
                $q->whereIn('id', auth()->user()->supDepartments->pluck('department_id'));
            }),
            "manager" => $query->whereHas('department', function ($q) {
                $q->where('company_id', auth()->user()->company_id);
            }),
            "admin" => null, // No additional filtering for admin
            default => [],
        };

        $this->selectedEmployeesForDelete = $query->pluck('id')->toArray();
    }

    public function toggleEmployeeSelection($employeeId)
    {
        if ($this->activeTab === 'deleted') {
            if (in_array($employeeId, $this->selectedEmployeesForDelete)) {
                $this->selectedEmployeesForDelete = array_diff($this->selectedEmployeesForDelete, [$employeeId]);
            } else {
                $this->selectedEmployeesForDelete[] = $employeeId;
            }
        } else {
            if (in_array($employeeId, $this->selectedEmployees)) {
                $this->selectedEmployees = array_diff($this->selectedEmployees, [$employeeId]);
            } else {
                $this->selectedEmployees[] = $employeeId;
            }

            $this->selectAll = count($this->selectedEmployees) === $this->getEmployees()->count();
        }
    }

    private function getEmployees()
    {
        $query = User::search($this->query)->with([
            'company:id,name',
            'department:id,name',
            'service:id,name',
            'roles:id,name'
        ])->when($this->auth_role === 'supervisor', function($query) {
            $query->supervisor();
        })->when($this->auth_role === 'manager', function($query) {
            $query->whereIn('company_id', auth()->user()->managerCompanies->pluck('id'))
                  ->whereHas('roles', function($query) {
                      $query->whereIn('name', ['employee', 'supervisor']);
                  })->whereDoesntHave('roles', function($query) {
                      $query->where('name', 'admin');
                  });
        })->when($this->auth_role === 'admin', function($query) {
            $query->whereHas('roles', function($query) {
                $query->whereIn('name', ['admin', 'employee', 'supervisor', 'manager']);
            });
        })->when($this->auth_role === 'employee', function($query) {
            $query->whereHas('roles', function($query) {
                $query->where('name', 'employee');
            })->whereDoesntHave('roles', function($query) {
                $query->whereIn('name', ['admin', 'manager', 'supervisor']);
            });
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
            'manager' => $query->whereIn('company_id', auth()->user()->managerCompanies->pluck('id')),
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
        $employee = User::withTrashed()->findOrFail($employee_id);

        $department = Department::findOrFail($employee->department_id);

        $this->isEditMode = true;
        $this->employee_id = $employee_id;
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
        $this->receive_sms_notifications = (bool) ($employee->receive_sms_notifications ?? true);
        $this->receive_email_notifications = (bool) ($employee->receive_email_notifications ?? true);
        
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
   
   public function openCreateModal()
   {
       $this->clearFields();
       $this->isEditMode = false;
   }


    public function export()
    {
        auditLog(
            auth()->user(),
            'employee_exported',
            'web',
            __('employees.exported_excel_for_all_employees')
        );
        return (new EmployeeExport($this->company, $this->query))->download('All-Employees-' . Str::random(5) . '.xlsx');
    }

    public function close()
    {
        $this->clearFields();
    }

    public function clearFields()
    {
        parent::clearFields();
        $this->isEditMode = false;
        $this->employee_id = null;
        $this->employee = null;
        $this->employee_file = null;
        $this->selectedCompanyId = null;
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
            'receive_sms_notifications',
            'receive_email_notifications',
            'alternative_email',
        ]);
        // Reset to default roles (only employee role)
        $this->selected_roles = ['employee'];
        $this->receive_sms_notifications = (bool) true;
        $this->receive_email_notifications = (bool) true;
        $this->alternative_email = null;
        $this->password = Str::random(15);
    }

    /**
     * Get import columns for preview display
     */
    protected function getImportColumns(): array
    {
        return [
            0 => __('employees.first_name'),
            1 => __('employees.last_name'),
            2 => __('employees.email'),
            3 => __('employees.professional_phone'),
            4 => __('employees.matricule'),
            5 => __('employees.position'),
            6 => __('employees.net_salary'),
            7 => __('employees.salary_grade'),
            9 => __('departments.department'),
            10 => __('employees.service'),
            11 => __('common.role'),
            17 => __('employees.personal_phone'),
            18 => __('employees.work_start_time'),
            19 => __('employees.work_end_time'),
            21 => __('employees.alternative_email'),
        ];
    }

    /**
     * Preload validation data to optimize performance
     * For quick validation, we only load a sample of existing data to avoid timeouts
     */
    protected function preloadValidationData(): void
    {
        // For quick validation, only load recent/active users to avoid loading entire user table
        // This prevents timeouts while still catching most duplicates
        if (!isset($this->existingEmployeeEmails)) {
            $this->existingEmployeeEmails = User::whereNotNull('email')
                ->where('created_at', '>', now()->subMonths(12)) // Only check last 12 months
                ->orWhere(function($query) {
                    $query->whereNotNull('email')
                          ->where('status', true); // Include active users regardless of creation date
                })
                ->limit(5000) // Limit to prevent memory issues
                ->pluck('email')
                ->map(function($email) {
                    return strtolower(trim($email));
                })
                ->toArray();
        }

        if (!isset($this->existingEmployeeMatricules)) {
            $this->existingEmployeeMatricules = User::whereNotNull('matricule')
                ->where('created_at', '>', now()->subMonths(12)) // Only check last 12 months
                ->orWhere(function($query) {
                    $query->whereNotNull('matricule')
                          ->where('status', true); // Include active users regardless of creation date
                })
                ->limit(5000) // Limit to prevent memory issues
                ->pluck('matricule')
                ->map(function($matricule) {
                    return strtolower(trim($matricule));
                })
                ->toArray();
        }
    }

    /**
     * Check if employee email exists (optimized to avoid N+1 queries)
     */
    protected function isEmployeeEmailExists(string $email): bool
    {
        return in_array(strtolower(trim($email)), $this->existingEmployeeEmails ?? []);
    }

    /**
     * Check if employee matricule exists (optimized to avoid N+1 queries)
     */
    protected function isEmployeeMatriculeExists(string $matricule): bool
    {
        return in_array(strtolower(trim($matricule)), $this->existingEmployeeMatricules ?? []);
    }

    /**
     * Validate a single employee preview row
     */
    protected function validatePreviewRow(array $rowData, int $rowNumber): array
    {
        $errors = [];
        $warnings = [];
        $parsedData = [];

        try {
            // Validate required fields
            if (empty($rowData[0] ?? '')) {
                $errors[] = __('employees.first_name_required');
            }

            if (empty($rowData[1] ?? '')) {
                $errors[] = __('employees.last_name_required');
            }

            // Validate email
            if (empty($rowData[2] ?? '')) {
                $errors[] = __('employees.email_required');
            } else {
                $emailValidation = validateEmail($rowData[2]);
                if (!$emailValidation['valid']) {
                    $errors[] = $emailValidation['error'];
                } else {
                    // Check for duplicate email (optimized to avoid N+1 queries)
                    if ($this->isEmployeeEmailExists($rowData[2])) {
                        $errors[] = __('employees.email_already_exists');
                    }
                }
            }

            // Validate professional phone number
            if (empty($rowData[3] ?? '')) {
                $errors[] = __('employees.professional_phone_required');
            } else {
                $phoneValidation = validatePhoneNumber($rowData[3]);
                if (!$phoneValidation['valid']) {
                    $errors[] = $phoneValidation['error'];
                }
            }

            // Validate matricule
            if (empty($rowData[4] ?? '')) {
                $errors[] = __('employees.matricule_required');
            } else {
                // Check for duplicate matricule (optimized to avoid N+1 queries)
                if ($this->isEmployeeMatriculeExists($rowData[4])) {
                    $warnings[] = __('employees.matricule_already_exists');
                }
            }

            // Validate department
            if (empty($rowData[9] ?? '')) {
                $errors[] = __('departments.department_required');
            } else {
                // Check if company is set before validating department
                $companyId = $this->getCompanyId();
                if (!$companyId) {
                    $errors[] = __('employees.company_required_for_import');
                } else {
                    $departmentResult = findOrCreateDepartment($rowData[9], $companyId, $this->autoCreateEntities);
                    if (!$departmentResult['found']) {
                        $errors[] = $departmentResult['error'];
                    } else {
                        $parsedData[9] = $departmentResult['department']->name;
                    }
                }
            }

            // Validate service (requires valid department)
            if (empty($rowData[10] ?? '')) {
                $errors[] = __('employees.service_required');
            } elseif (!isset($departmentResult) || !$departmentResult['found']) {
                $errors[] = __('departments.department_required_for_service_import');
            } else {
                $companyId = $this->getCompanyId();
                $serviceResult = findOrCreateService($rowData[10], $departmentResult['department']->id, $companyId, $this->autoCreateEntities);
                if (!$serviceResult['found']) {
                    $errors[] = $serviceResult['error'];
                } else {
                    $parsedData[10] = $serviceResult['service']->name;
                }
            }

            // Validate role
            if (empty($rowData[11] ?? '')) {
                $errors[] = __('employees.role_required');
            } else {
                $validRoles = ['employee', 'supervisor', 'manager'];
                if (!in_array(strtolower($rowData[11]), $validRoles)) {
                    $errors[] = __('employees.invalid_role');
                }
            }

            // Validate personal phone number if provided
            if (!empty($rowData[17] ?? '')) {
                $personalPhoneValidation = validatePhoneNumber($rowData[17]);
                if (!$personalPhoneValidation['valid']) {
                    $errors[] = __('employees.personal_phone_invalid') . ': ' . $personalPhoneValidation['error'];
                }
            }

            // Validate work times
            if (!empty($rowData[18] ?? '') && !preg_match('/^\d{2}:\d{2}$/', $rowData[18])) {
                $errors[] = __('employees.invalid_work_start_time');
            }

            if (!empty($rowData[19] ?? '') && !preg_match('/^\d{2}:\d{2}$/', $rowData[19])) {
                $errors[] = __('employees.invalid_work_end_time');
            }

            // Validate alternative email if provided
            if (!empty($rowData[21] ?? '')) {
                $altEmailValidation = validateEmail($rowData[21]);
                if (!$altEmailValidation['valid']) {
                    $errors[] = __('employees.alternative_email_invalid') . ': ' . $altEmailValidation['error'];
                }
            }

        } catch (\Exception $e) {
            $errors[] = __('common.row_validation_error', ['error' => $e->getMessage()]);
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'parsed_data' => $parsedData
        ];
    }

    /**
     * Perform the actual employee import
     */
    protected function performImport()
    {
        $companyId = $this->getCompanyId();
        $company = $companyId ? \App\Models\Company::find($companyId) : null;

        Excel::import(new EmployeeImport($company, $this->autoCreateEntities), $this->employee_file);

        return [
            'imported_count' => 'unknown', // Could be improved to return actual count
            'company_name' => $company ? $company->name : 'multiple'
        ];
    }
    public function render()
    {
        if (!Gate::allows('employee-read')) {
            return abort(401);
        }

        $employees = $this->getEmployees();

        // Get counts for active employees (non-deleted)
        $active_employees = match ($this->auth_role) {
            'supervisor' => User::supervisor()->whereNull('deleted_at')->count(),
            'manager' => User::with('roles')->whereNull('deleted_at')->whereIn('company_id', auth()->user()->managerCompanies->pluck('id'))->whereHas('roles', function($query) {
                $query->whereIn('name', ['employee', 'supervisor']);
            })->whereDoesntHave('roles', function($query) {
                $query->where('name', 'admin');
            })->count(),
            'admin' => User::with('roles')->whereNull('deleted_at')->whereHas('roles', function($query) {
                $query->whereIn('name', ['admin', 'employee', 'supervisor', 'manager']);
            })->count(),
            default => 0,
        };

        // Get counts for deleted employees
        $deleted_employees = match ($this->auth_role) {
            'supervisor' => User::supervisor()->withTrashed()->whereNotNull('deleted_at')->count(),
            'manager' => User::with('roles')->withTrashed()->whereNotNull('deleted_at')->whereIn('company_id', auth()->user()->managerCompanies->pluck('id'))->whereHas('roles', function($query) {
                $query->whereIn('name', ['employee', 'supervisor']);
            })->whereDoesntHave('roles', function($query) {
                $query->where('name', 'admin');
            })->count(),
            'admin' => User::with('roles')->withTrashed()->whereNotNull('deleted_at')->whereHas('roles', function($query) {
                $query->whereIn('name', ['admin', 'employee', 'supervisor', 'manager']);
            })->count(),
            default => 0,
        };

        // Legacy counts for backward compatibility
        $employees_count = $active_employees;
        $banned_employees = match ($this->auth_role) {
            'supervisor' => User::supervisor()->whereNull('deleted_at')->where('status', false)->count(),
            'manager' => User::with('roles')->whereNull('deleted_at')->whereIn('company_id', auth()->user()->managerCompanies->pluck('id'))->where('status', false)->whereHas('roles', function($query) {
                $query->whereIn('name', ['employee', 'supervisor']);
            })->whereDoesntHave('roles', function($query) {
                $query->where('name', 'admin');
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

    /**
     * Get column definitions for preview
     */
    public function getPreviewColumns(): array
    {
        return $this->getImportColumns();
    }

    /**
     * Handle employee file upload
     */
    public function updatedEmployeeFile()
    {
        // Validate the uploaded file
        $this->validate([
            'employee_file' => 'sometimes|nullable|mimes:xlsx,xls,csv,txt|max:' . ($this->maxFileSize * 1024)
        ]);

        // Clear previous preview when new file is uploaded
        $this->clearPreview();
    }

    /**
     * Get company ID for import
     */
    protected function getCompanyId(): ?int
    {
        return $this->company ? $this->company->id : null;
    }

    /**
     * Get department ID (not needed for employee import)
     */
    protected function getDepartmentId(): ?int
    {
        return null;
    }
}
