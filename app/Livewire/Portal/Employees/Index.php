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
use App\Livewire\Traits\WithImportPreview;
use Illuminate\Validation\Rule;
use App\Models\Role;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\Collection;

class Index extends BaseImportComponent
{
    use WithDataTable;

    protected $importType = 'employees';
    protected $importPermission = 'employee-create';

    // Cache for existing employee data to avoid N+1 queries
    protected $existingEmployeeEmails;
    
    //
    public $services = [];
    public $roles = [];
    public $departments = [];
    public $employee = null;
    public $employee_id = null;
    public $activeTab = 'active'; // 'active' or 'deleted'
    public $selectedEmployees = [];
    public $selectedEmployeesForDelete = [];
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
    public $selectedDepartmentId = null;
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
    public $receive_sms_notifications = true;
    public $receive_email_notifications = true;
    public $alternative_email = null;
    public $isEditMode = false;
    public $autoCreateEntities = false;

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

    public function mount($company_uuid = null, $department_uuid = null)
    {
        $this->auth_role = auth()->user()->getRoleNames()->first();
        
        if ($this->auth_role === 'supervisor') {
            // For supervisors, get department context
            if (!$department_uuid) {
                abort(403, __('employees.department_access_required_for_supervisors'));
            }
            $department = Department::where('uuid', $department_uuid)->firstOrFail();
            
            // Security check: Ensure supervisor has access to this department
            $hasAccess = auth()->user()->supDepartments()
                ->where('department_id', $department->id)
                ->exists();
                
            if (!$hasAccess) {
                abort(403, __('employees.no_access_to_department'));
            }
            
            $this->company = $department->company;
            $this->departments = collect([$department]); // Only show the specific department
        } else {
            // For managers and admins, use company context
            $this->company = Company::findOrFail($company_uuid);
            
            // Security check: Ensure managers can only access companies they manage
            if (auth()->user()->hasRole('manager')) {
                if (!auth()->user()->managerCompanies->contains($this->company)) {
                    abort(403, __('employees.no_permission_access_company'));
                }
            }
            
            $this->departments = $this->company->departments;
        }
        // Role assignment permissions based on user role
        $userRole = auth()->user()->getRoleNames()->first();
        $this->roles = match ($userRole) {
            'admin' => Role::orderBy('name', 'desc')->get(),
            'manager' => Role::whereIn('name', ['employee', 'supervisor'])->orderBy('name', 'desc')->get(),
            'supervisor' => Role::where('name', 'employee')->orderBy('name', 'desc')->get(), // Supervisors can only assign employee role
            default => Role::where('name', 'employee')->orderBy('name', 'desc')->get(),
        };
        $this->password = Str::random(15);
        $this->work_start_time = Carbon::parse('08:00')->format('H:i');
        $this->work_end_time = Carbon::parse('17:30')->format('H:i');

        // Initialize preview functionality
        $this->initializePreview();
    }

    public function updatedDepartmentId($department_id)
    {
        if(!is_null($department_id)){
            $this->services = Service::where('department_id', $department_id)->get();
        }
    }

    public function updatedSelectedDepartmentId($department_id)
    {
        if(!is_null($department_id)){
            $this->department_id = $department_id;
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

        // Ensure employee role is always included and first
        if (!in_array('employee', $this->selected_roles)) {
            $this->selected_roles[] = 'employee';
        }
        
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
        
        // Validate that employee role is always included
        if (!in_array('employee', $this->selected_roles)) {
            $this->addError('selected_roles', __('employees.employee_role_must_always_be_included'));
            return;
        }

        // Validate maximum 2 roles (including employee)
        if (count($this->selected_roles) > 2) {
            $this->addError('selected_roles', __('employees.user_maximum_2_roles_including_employee'));
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
            'receive_sms_notifications' => $this->receive_sms_notifications === true || $this->receive_sms_notifications === "true" || $this->receive_sms_notifications === 1,
            'receive_email_notifications' => $this->receive_email_notifications === true || $this->receive_email_notifications === "true" || $this->receive_email_notifications === 1,
            'alternative_email' => $this->alternative_email,
            'password' => bcrypt($this->password),
            'pdf_password' => Str::random(10),
            'author_id' => auth()->user()->id,
        ]);

        // Assign multiple roles - use syncRoles instead of assignRole for arrays
        $user->syncRoles($this->selected_roles);

        event(new EmployeeCreated($user, $this->password));

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.employee_created_successfully'), 'EmployeeModal');
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
            'selected_roles' => 'required|array|max:2',
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
            'receive_sms_notifications' => $this->receive_sms_notifications === true || $this->receive_sms_notifications === "true" || $this->receive_sms_notifications === 1,
            'password' => empty($this->password) ? $this->employee->password : bcrypt($this->password),
            // 'pdf_password' => Str::random(10),
        ]);

        // Validate that employee role is always included
        if (!in_array('employee', $this->selected_roles)) {
            $this->addError('selected_roles', __('employees.employee_role_must_always_be_included'));
            return;
        }

        // Validate maximum 2 roles (including employee)
        if (count($this->selected_roles) > 2) {
            $this->addError('selected_roles', __('employees.user_maximum_2_roles_including_employee'));
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
        if (!Gate::allows('employee-restore')) {
            return abort(401);
        }

        $employee = User::withTrashed()->findOrFail($this->employee_id);
        $employee->restore();

        $this->closeModalAndFlashMessage(__('employees.employee_successfully_restored'), 'RestoreModal');
    }

    public function forceDelete($employeeId = null)
    {
        if (!Gate::allows('employee-delete')) {
            return abort(401);
        }

        // If no employeeId provided, try to get it from selectedEmployees or selectedEmployeesForDelete
        if (!$employeeId) {
            if (!empty($this->selectedEmployees) && is_array($this->selectedEmployees)) {
                $employeeId = $this->selectedEmployees[0] ?? null;
            } elseif (!empty($this->selectedEmployeesForDelete) && is_array($this->selectedEmployeesForDelete)) {
                $employeeId = $this->selectedEmployeesForDelete[0] ?? null;
            } elseif ($this->employee_id) {
                $employeeId = $this->employee_id;
            } else {
                $this->showToast(__('employees.no_employee_selected'), 'danger');
                return;
            }
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

        // Clear selection after deletion
        if (in_array($employeeId, $this->selectedEmployees ?? [])) {
            $this->selectedEmployees = array_diff($this->selectedEmployees, [$employeeId]);
        }
        if (in_array($employeeId, $this->selectedEmployeesForDelete ?? [])) {
            $this->selectedEmployeesForDelete = array_diff($this->selectedEmployeesForDelete, [$employeeId]);
        }
        $this->employee_id = null;

        $this->closeModalAndFlashMessage(__('employees.employee_permanently_deleted'), 'ForceDeleteModal');
    }

    public function bulkDelete()
    {
        if (!Gate::allows('employee-bulkdelete')) {
            return abort(401);
        }

        $targetIds = $this->selectedEmployees ?? [];
        $employees = collect();
        $affectedRecords = [];

        if (!empty($targetIds)) {
            $employees = User::withTrashed()->whereIn('id', $targetIds)->get();
            $affectedRecords = $employees->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'email' => $employee->email,
                ];
            })->toArray();
        }

        if (!empty($targetIds)) {
            User::whereIn('id', $targetIds)->delete(); // Soft delete
            $this->selectedEmployees = [];
            $this->selectAll = false;

            if ($employees->count() > 0) {
                auditLog(
                    auth()->user(),
                    'employee_bulk_deleted',
                    'web',
                    'bulk_deleted_employees',
                    null,
                    [],
                    [],
                    [
                        'translation_key' => 'bulk_deleted_employees',
                        'translation_params' => ['count' => $employees->count()],
                        'bulk_operation' => true,
                        'operation_type' => 'soft_delete',
                        'affected_count' => $employees->count(),
                        'affected_ids' => $employees->pluck('id')->toArray(),
                        'affected_records' => $affectedRecords,
                    ]
                );
            }

            $this->closeModalAndFlashMessage(__('employees.selected_employees_moved_to_trash'), 'BulkDeleteModal');
        }
    }

    public function bulkRestore()
    {
        if (!Gate::allows('employee-bulkrestore')) {
            return abort(401);
        }

        $targetIds = $this->selectedEmployees ?? [];
        $employees = collect();
        $affectedRecords = [];

        if (!empty($targetIds)) {
            $employees = User::withTrashed()->whereIn('id', $targetIds)->get();
            $affectedRecords = $employees->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'email' => $employee->email,
                ];
            })->toArray();
        }

        if (!empty($targetIds)) {
            User::withTrashed()->whereIn('id', $targetIds)->restore();
            $this->selectedEmployees = [];
            $this->selectAll = false;

            if ($employees->count() > 0) {
                auditLog(
                    auth()->user(),
                    'employee_bulk_restored',
                    'web',
                    'bulk_restored_employees',
                    null,
                    [],
                    [],
                    [
                        'translation_key' => 'bulk_restored_employees',
                        'translation_params' => ['count' => $employees->count()],
                        'bulk_operation' => true,
                        'operation_type' => 'bulk_restore',
                        'affected_count' => $employees->count(),
                        'affected_ids' => $employees->pluck('id')->toArray(),
                        'affected_records' => $affectedRecords,
                    ]
                );
            }

            $this->closeModalAndFlashMessage(__('employees.selected_employees_restored'), 'BulkRestoreModal');
        }
    }

    public function bulkForceDelete()
    {
        if (!Gate::allows('employee-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedEmployees)) {
            $employees = User::withTrashed()->whereIn('id', $this->selectedEmployees)->get();
            $employeesWithRelatedRecords = [];
            $affectedRecords = [];
            
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
                } else {
                    $affectedRecords[] = [
                        'id' => $employee->id,
                        'name' => $employee->name,
                        'email' => $employee->email,
                    ];
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
            
            if (!empty($affectedRecords)) {
                auditLog(
                    auth()->user(),
                    'employee_bulk_force_deleted',
                    'web',
                    'bulk_force_deleted_employees',
                    null,
                    [],
                    [],
                    [
                        'translation_key' => 'bulk_force_deleted_employees',
                        'translation_params' => ['count' => count($affectedRecords)],
                        'bulk_operation' => true,
                        'operation_type' => 'bulk_force_delete',
                        'affected_count' => count($affectedRecords),
                        'affected_ids' => array_column($affectedRecords, 'id'),
                        'affected_records' => $affectedRecords,
                    ]
                );
            }

            $this->selectedEmployees = [];
            $this->selectAll = false;
        }

        $this->closeModalAndFlashMessage(__('employees.selected_employees_permanently_deleted'), 'BulkForceDeleteModal');
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
            $this->selectedEmployees = $this->getEmployees()
                ->orderBy($this->orderBy, $this->orderAsc)
                ->paginate($this->perPage)
                ->pluck('id')
                ->toArray();
        } else {
            $this->selectedEmployees = [];
        }
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

            // Update selectAll based on current selection (current page employees only)
            $currentPageEmployees = $this->getEmployees()
                ->orderBy($this->orderBy, $this->orderAsc)
                ->paginate($this->perPage);
            $this->selectAll = count($this->selectedEmployees) === $currentPageEmployees->count();
        }
    }

    public function selectAllVisible()
    {
        $this->selectedEmployees = $this->getEmployees()
            ->orderBy($this->orderBy, $this->orderAsc)
            ->paginate($this->perPage)
            ->pluck('id')
            ->toArray();
        $this->selectAll = true;
    }

    public function selectAllEmployees()
    {
        $baseQuery = match ($this->auth_role) {
            'supervisor' => User::search($this->query)->supervisor(),
            'manager' => User::search($this->query)->whereHas('roles', function($query) {
                $query->whereIn('name', ['employee', 'supervisor']);
            })->whereDoesntHave('roles', function($query) {
                $query->where('name', 'admin');
            })->where('company_id', $this->company->id),
            'admin' => User::search($this->query)->whereHas('roles', function($query) {
                $query->whereIn('name', ['admin', 'employee', 'supervisor', 'manager']);
            })->where('company_id', $this->company->id),
            default => User::where('id', 0), // Return empty query for unknown roles
        };

        if ($this->activeTab === 'deleted') {
            $this->selectedEmployees = $baseQuery->withTrashed()
                ->whereNotNull('deleted_at')
                ->pluck('id')
                ->toArray();
        } else {
            $this->selectedEmployees = $baseQuery->pluck('id')->toArray();
        }
        $this->selectAll = true;
    }

    public function selectAllVisibleForDelete()
    {
        $this->selectedEmployeesForDelete = $this->getEmployees()
            ->orderBy($this->orderBy, $this->orderAsc)
            ->paginate($this->perPage)
            ->pluck('id')
            ->toArray();
    }

    public function selectAllDeletedEmployees()
    {
        $baseQuery = match ($this->auth_role) {
            'supervisor' => User::search($this->query)->supervisor(),
            'manager' => User::search($this->query)->whereHas('roles', function($query) {
                $query->whereIn('name', ['employee', 'supervisor']);
            })->whereDoesntHave('roles', function($query) {
                $query->where('name', 'admin');
            })->where('company_id', $this->company->id),
            'admin' => User::search($this->query)->whereHas('roles', function($query) {
                $query->whereIn('name', ['admin', 'employee', 'supervisor', 'manager']);
            })->where('company_id', $this->company->id),
            default => User::where('id', 0), // Return empty query for unknown roles
        };

        $this->selectedEmployeesForDelete = $baseQuery->withTrashed()
            ->whereNotNull('deleted_at')
            ->pluck('id')
            ->toArray();
    }

    private function getEmployees()
    {
        $baseQuery = match ($this->auth_role) {
            'supervisor' => User::search($this->query)->supervisor(),
            'manager' => User::search($this->query)->whereHas('roles', function($query) {
                $query->whereIn('name', ['employee', 'supervisor']);
            })->whereDoesntHave('roles', function($query) {
                $query->where('name', 'admin');
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
        $employee = User::withTrashed()->findOrFail($employee_id);
        $department = Department::findOrFail($employee->department_id);

        $this->isEditMode = true;
        $this->employee_id = $employee_id;
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
        $this->date_of_birth = $employee->date_of_birth;
        $this->service_id = $employee->service_id;
        $this->department_id = $employee->department_id;
        $this->selectedDepartmentId = $employee->department_id;
        $this->role_name = $employee->getRoleNames()->first();
        $this->selected_roles = $employee->getRoleNames()->toArray();
        $this->receive_sms_notifications = $employee->receive_sms_notifications ?? true;
        $this->receive_email_notifications = $employee->receive_email_notifications ?? true;
        $this->alternative_email = $employee->alternative_email;
        
        // Ensure employee role is always included in the selected roles
        if (!in_array('employee', $this->selected_roles)) {
            $this->selected_roles[] = 'employee';
        }
        
        // Ensure employee role is first - fix the logic
        $this->selected_roles = array_unique($this->selected_roles);
        $employeeKey = array_search('employee', $this->selected_roles);
        if ($employeeKey !== false) {
            unset($this->selected_roles[$employeeKey]);
            // Reindex the array
            $this->selected_roles = array_values($this->selected_roles);
        }
        array_unshift($this->selected_roles, 'employee');

        // Refresh Choices.js for employee edit modal to avoid stale selections
        $choicesOptions = $this->roles
            ->pluck('name', 'name')
            ->map(function ($name) {
                return ucfirst($name);
            })
            ->toArray();
        $this->dispatch('refreshChoices', id: 'edit_selected_roles', options: $choicesOptions, selected: $this->selected_roles);
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

        // Ensure selected_roles is set and refresh Choices for manager modal
        $this->selected_roles = $employee->getRoleNames()->toArray();
        if (!in_array('employee', $this->selected_roles)) {
            $this->selected_roles[] = 'employee';
        }
        $this->selected_roles = array_unique($this->selected_roles);
        $employeeKey = array_search('employee', $this->selected_roles);
        if ($employeeKey !== false) {
            unset($this->selected_roles[$employeeKey]);
            $this->selected_roles = array_values($this->selected_roles);
        }
        array_unshift($this->selected_roles, 'employee');

        $choicesOptions = $this->roles
            ->pluck('name', 'name')
            ->map(function ($name) {
                return ucfirst($name);
            })
            ->toArray();
        $this->dispatch('refreshChoices', id: 'edit_manager_roles', options: $choicesOptions, selected: $this->selected_roles);
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

        // Validate role assignment permissions based on user role
        $userRole = auth()->user()->getRoleNames()->first();
        $allowedRoles = match ($userRole) {
            'admin' => ['admin', 'manager', 'supervisor', 'employee'],
            'manager' => ['employee', 'supervisor'],
            'supervisor' => ['employee'], // Supervisors can only assign employee role
            default => ['employee'],
        };
        
        if (!in_array($this->role_name, $allowedRoles)) {
            $this->showToast(__('employees.no_permission_assign_roles') . $this->role_name, 'danger');
            return;
        }
        
        $this->employee->assignRole($this->role_name);

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.manager_updated_successfully'), 'EditManagerModal');
    }
    /**
     * Perform the actual employee import
     */
    protected function performImport()
    {
        // Get department and service from context if provided
        $department = $this->selectedDepartmentId ? Department::find($this->selectedDepartmentId) : null;
        $service = $this->service_id ? Service::find($this->service_id) : null;
        
        Excel::import(new EmployeeImport($this->company, $department, $service, $this->autoCreateEntities, auth()->id(), $this->sendWelcomeEmails), $this->employee_file);

        return [
            'imported_count' => 'unknown', // Could be enhanced to return actual count
            'company_name' => $this->company->name,
            'auto_create_enabled' => $this->autoCreateEntities
        ];
    }

    /**
     * Override import method to reset step after successful import
     */
    public function import()
    {
        try {
            // Call parent import method
            parent::import();

            // Reset to upload step after successful import
            $this->resetToUpload();

        } catch (\Exception $e) {
            // Re-throw the exception to maintain danger handling
            throw $e;
        }
    }

    public function export()
    {
        auditLog(
            auth()->user(),
            'employee_exported',
            'web',
            'exported_employees_for_company',
            null,
            [],
            [],
            [
                'translation_key' => 'exported_employees_for_company',
                'translation_params' => ['company' => $this->company->name],
            ]
        );
        return (new EmployeeExport($this->company, $this->query))->download(ucfirst($this->company->name).'-Employees-' . Str::random(5) . '.xlsx');
    }


    public function toggleEmailNotifications($employeeId)
    {
        if (!Gate::allows('employee-update')) {
            return abort(401);
        }

        $employee = User::findOrFail($employeeId);
        $employee->update([
            'receive_email_notifications' => !$employee->receive_email_notifications
        ]);

        $this->showToast(
            $employee->receive_email_notifications
                ? __('employees.email_notifications_enabled')
                : __('employees.email_notifications_disabled'),
            'success'
        );
    }

    public function toggleSmsNotifications($employeeId)
    {
        if (!Gate::allows('employee-update')) {
            return abort(401);
        }

        $employee = User::findOrFail($employeeId);
        $employee->update([
            'receive_sms_notifications' => !$employee->receive_sms_notifications
        ]);

        $this->showToast(
            $employee->receive_sms_notifications
                ? __('employees.sms_notifications_enabled')
                : __('employees.sms_notifications_disabled'),
            'success'
        );
    }

    public function close()
    {
        $this->clearFields();
    }

    /**
     * Get unique context ID for caching
     */
    protected function getImportContextId()
    {
        return 'employees_' . ($this->company ? $this->company->id : 'global') . '_' . auth()->id();
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
    }

    /**
     * Check if employee email exists (optimized to avoid N+1 queries)
     */
    protected function isEmployeeEmailExists(string $email): bool
    {
        return in_array(strtolower(trim($email)), $this->existingEmployeeEmails ?? []);
    }

    /**
     * Validate a single preview row for employees
     */
    protected function validatePreviewRow(array $rowData, int $rowNumber): array
    {
        $dangers = [];
        $warnings = [];
        $parsedData = [];

        try {
            // Validate required fields
            if (empty($rowData[0] ?? '')) {
                $dangers[] = __('employees.first_name_required');
            }
            if (empty($rowData[1] ?? '')) {
                $dangers[] = __('employees.last_name_required');
            }
            if (empty($rowData[2] ?? '')) {
                $dangers[] = __('employees.email_required');
            } elseif (!validateEmail($rowData[2])['valid']) {
                $dangers[] = __('employees.email_invalid');
            }

            // Validate professional phone number
            $phoneNumber = preg_replace('/\s+/', '', $rowData[3] ?? '');
            if (empty($phoneNumber)) {
                $dangers[] = __('employees.professional_phone_required');
            } else {
                $phoneValidation = validatePhoneNumber($phoneNumber);
                if (!$phoneValidation['valid']) {
                    $dangers[] = __('employees.professional_phone_invalid');
                }
            }

            // Validate matricule
            if (empty($rowData[4] ?? '')) {
                $dangers[] = __('employees.matricule_required');
            }

            // Validate position
            if (empty($rowData[5] ?? '')) {
                $dangers[] = __('employees.position_required');
            }

            // Validate net salary
            if (empty($rowData[6] ?? '') || !is_numeric($rowData[6])) {
                $dangers[] = __('employees.net_salary_required_numeric');
            }

            // Validate salary grade
            if (empty($rowData[7] ?? '')) {
                $dangers[] = __('employees.salary_grade_required');
            }

            // Validate department (by name)
            $departmentName = $rowData[9] ?? '';
            if (empty($departmentName)) {
                $dangers[] = __('employees.department_required');
            } else {
                // Check if company is set before validating department
                if (!$this->company) {
                    $dangers[] = __('employees.company_required_for_import');
                } else {
                    $departmentResult = findDepartmentByName($departmentName, $this->company->id);
                    if (!$departmentResult['found']) {
                        $dangers[] = $departmentResult['danger'];
                    } else {
                        $parsedData[9] = $departmentResult['department']->name;
                    }
                }
            }

            // Validate service (by name, requires valid department)
            $serviceName = $rowData[10] ?? '';
            if (empty($serviceName)) {
                $dangers[] = __('employees.service_required');
            } elseif (isset($departmentResult) && $departmentResult['found']) {
                $serviceResult = findServiceByName($serviceName, $departmentResult['department']->id);
                if (!$serviceResult['found']) {
                    $dangers[] = $serviceResult['danger'];
                } else {
                    $parsedData[10] = $serviceResult['service']->name;
                }
            }

            // Validate role based on user permissions
            $role = strtolower($rowData[11] ?? '');
            $userRole = auth()->user()->getRoleNames()->first();
            $validRoles = match ($userRole) {
                'admin' => ['admin', 'manager', 'supervisor', 'employee'],
                'manager' => ['employee', 'supervisor'],
                'supervisor' => ['employee'],
                default => ['employee'],
            };
            if (empty($role) || !in_array($role, $validRoles)) {
                $dangers[] = __('employees.role_invalid');
            }

            // Validate work times if provided
            $workStartTime = $rowData[18] ?? null;
            $workEndTime = $rowData[19] ?? null;

            if ($workStartTime && !preg_match('/^\d{2}:\d{2}$/', $workStartTime)) {
                $dangers[] = __('employees.work_start_time_invalid_format');
            }
            if ($workEndTime && !preg_match('/^\d{2}:\d{2}$/', $workEndTime)) {
                $dangers[] = __('employees.work_end_time_invalid_format');
            }
            if ($workStartTime && $workEndTime && $workStartTime >= $workEndTime) {
                $dangers[] = __('employees.work_start_time_must_be_before_end');
            }

            // Validate alternative email if provided
            $alternativeEmail = $rowData[21] ?? '';
            if (!empty($alternativeEmail) && !filter_var($alternativeEmail, FILTER_VALIDATE_EMAIL)) {
                $dangers[] = __('employees.alternative_email_invalid');
            }

            // Validate personal phone number if provided
            $personalPhone = $rowData[17] ?? '';
            if (!empty($personalPhone)) {
                $personalPhoneClean = preg_replace('/\s+/', '', $personalPhone);
                $personalPhoneValidation = validatePhoneNumber($personalPhoneClean);
                if (!$personalPhoneValidation['valid']) {
                    $dangers[] = __('employees.personal_phone_invalid');
                }
            }

            // Check for duplicate email (optimized to avoid N+1 queries)
            if (!empty($rowData[2]) && $this->isEmployeeEmailExists($rowData[2])) {
                $warnings[] = __('employees.email_already_exists');
            }

        } catch (\Exception $e) {
            $dangers[] = __('common.row_validation_danger', ['danger' => $e->getMessage()]);
        }

        return [
            'valid' => empty($dangers),
            'dangers' => $dangers,
            'warnings' => $warnings,
            'parsed_data' => $parsedData
        ];
    }

    /**
     * Get column definitions for preview
     */
    public function getPreviewColumns(): array
    {
        return [
            0 => __('employees.first_name'),
            1 => __('employees.last_name'),
            2 => __('employees.email'),
            3 => __('common.prof_phone_number'),
            4 => __('employees.matricule'),
            5 => __('common.position'),
            6 => __('employees.net_salary'),
            7 => __('employees.salary_grade'),
            8 => __('employees.contract_end_date'),
            9 => __('departments.departments'),
            10 => __('services.services'),
            11 => __('employees.role'),
            12 => __('common.status'),
            13 => __('common.password'),
            14 => __('employees.remaining_leave_days'),
            15 => __('employees.monthly_leave_allocation'),
            16 => __('employees.receive_sms_notifications'),
            17 => __('common.personal_phone_number'),
            18 => __('employees.work_start_time'),
            19 => __('employees.work_end_time'),
            20 => __('employees.receive_email_notifications'),
            21 => __('employees.alternative_email'),
            22 => __('employees.date_of_birth'),
        ];
    }

    /**
     * Get import columns for department preview
     */
    protected function getImportColumns(): array
    {
        return $this->getPreviewColumns();
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
        return $this->selectedDepartmentId;
    }

    /**
     * Get service ID for import context
     */
    protected function getServiceId(): ?int
    {
        return $this->service_id;
    }

    /**
     * Get expected columns for field validation
     */
    protected function getExpectedColumns(): array
    {
        return [
            'first name',
            'last name',
            'email',
            'professional phone number',
            'matricule',
            'position',
            'net salary',
            'salary grade',
            'contract end date',
            'department',
            'service',
            'role',
            'status',
            'password',
            'remaining leave days',
            'monthly leave allocation',
            'receive sms notifications',
            'personal phone number',
            'work start time',
            'work end time',
            'receive email notifications',
            'alternative email',
            'date of birth'
        ];
    }

    public function clearFields()
    {
        $this->isEditMode = false;
        $this->employee_id = null;
        $this->employee = null;
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
            'department_id',
            'selectedDepartmentId',
            'service_id',
            'status',
            'work_start_time',
            'work_end_time',
            'date_of_birth',
            'password',
            'selected_roles',
            'receive_sms_notifications',
            'receive_email_notifications',
            'alternative_email',
            'autoCreateEntities',
            'selectedCompanyId',
        ]);
        // Reset to default roles
        $this->selected_roles = ['employee'];
        $this->receive_sms_notifications = true;
        $this->receive_email_notifications = true;
        $this->alternative_email = null;
        $this->autoCreateEntities = false;
    }
    
    public function openCreateModal()
    {
        $this->clearFields();
        $this->isEditMode = false;
    }
    public function render()
    {
        if (!Gate::allows('employee-read')) {
            return abort(401);
        }
        $employees = $this->getEmployees()->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);

        $employees_count = match ($this->auth_role) {
            'supervisor' => User::supervisor()->where('status',true)->count(),
            'manager' => User::with(['roles' => fn($query) => $query->whereIn('name', ['employee', 'supervisor'])])->whereDoesntHave('roles', function($query) {
                $query->where('name', 'admin');
            })->where('company_id', $this->company->id)->where('status',true)->count(),
            'admin' => User::with(['roles' => fn($query) => $query->whereIn('name', ['employee', 'supervisor','manager'])])->where('company_id', $this->company->id)->where('status',true)->count(),
            default => [],
        };
        $active_employees = match ($this->auth_role) {
            'supervisor' => User::supervisor()->where('status', true)->count(),
            'manager' => User::with(['roles' => fn($query) => $query->whereIn('name', ['employee', 'supervisor'])])->whereDoesntHave('roles', function($query) {
                $query->where('name', 'admin');
            })->where('company_id', $this->company->id)->where('status', true)->count(),
            'admin' => User::with(['roles' => fn($query) => $query->whereIn('name', ['employee', 'supervisor', 'manager'])])->where('status', true)->where('company_id', $this->company->id)->where('status',true)->count(),
            default => [],
        };
        $banned_employees = match ($this->auth_role) {
            'supervisor' => User::supervisor()->where('status', false)->count(),
            'manager' => User::with(['roles' => fn($query) => $query->whereIn('name', ['employee', 'supervisor'])])->whereDoesntHave('roles', function($query) {
                $query->where('name', 'admin');
            })->where('company_id', $this->company->id)->where('status', false)->count(),
            'admin' => User::with(['roles' => fn($query) => $query->whereIn('name', ['employee', 'supervisor', 'manager'])])->where('status', false)->where('company_id', $this->company->id)->where('status',true)->count(),
            default => [],
        };

        // Calculate deleted employees count
        $deleted_employees = match ($this->auth_role) {
            'supervisor' => User::withTrashed()->whereNotNull('deleted_at')->supervisor()->count(),
            'manager' => User::withTrashed()->whereNotNull('deleted_at')->whereHas('roles', function($query) {
                $query->whereIn('name', ['employee', 'supervisor']);
            })->whereDoesntHave('roles', function($query) {
                $query->where('name', 'admin');
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
     * Override to return correct file property name for this component
     */
    protected function getFilePropertyName()
    {
        return 'employee_file';
    }

    /**
     * Override base updatedFile method to prevent conflicts
     */
    public function updatedFile()
    {
        // Do nothing - we handle file validation in updatedEmployeeFile
    }
}
