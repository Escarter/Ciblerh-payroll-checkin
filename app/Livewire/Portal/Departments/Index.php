<?php

namespace App\Livewire\Portal\Departments;

use App\Models\User;
use App\Models\Company;
use Livewire\Component;
use App\Models\Department;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Exports\DepartmentExport;
use App\Imports\DepartmentImport;
use App\Livewire\Traits\WithDataTable;
use Illuminate\Support\Facades\DB;
use App\Models\SupervisorDepartment;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

class Index extends Component
{
    use WithDataTable;

    //
    public ?Company $company;
    public ?Department $department = null;
    public ?int $department_id = null;
    public ?string $name = null;
    public $department_file = null;
    public ?bool $is_active = null;
    public $supervisors = [];
    public $supervisor_id =  null;
    public $role;
    public $isEditMode = false;
    
    // Soft delete properties
    public $activeTab = 'active';
    public $selectedDepartments = [];
    public $selectAll = false;

    //Update & Store Rules
    protected array $rules = [
        'name' => 'required',
    ];

    public function mount($company_uuid = null)
    {
        $this->role = auth()->user()->getRoleNames()->first();
        
        if ($this->role === 'supervisor') {
            // For supervisors, get their managed departments
            $this->company = null; // Supervisors don't need company context
            $this->supervisors = collect(); // Supervisors don't assign other supervisors
        } else {
            // For managers and admins, use company context
            $this->company = Company::findByUuid($company_uuid);
            $this->supervisors = User::role('supervisor')->orderBy('first_name', 'desc')->where('company_id', $this->company->id)->get();
        }
    }
    public function initData($department_id)
    {
        $department = Department::withTrashed()->findOrFail($department_id);

        $this->isEditMode = true;
        $this->supervisor_id = !empty($department->depSupervisor) ? (!empty($department->depSupervisor->supervisor) ? $department->depSupervisor->supervisor->id : '') : '';
        $this->department = $department;
        $this->department_id = $department->id;
        $this->name = $department->name;
        $this->is_active = $department->is_active;
    }
    
    public function openCreateModal()
    {
        $this->clearFields();
        $this->isEditMode = false;
    }
    public function store()
    {
        if (!Gate::allows('department-create')) {
            return abort(401);
        }
        $this->validate([
            'name' => 'required',
        ]);

        if ($this->role === 'supervisor') {
            // Supervisors can't create departments - they only manage existing ones
            session()->flash('error', __('departments.supervisors_cannot_create'));
            return;
        }
        
        $new_department = Department::create([
            'name' => $this->name,
            'company_id' => $this->company->id,
            'author_id' => auth()->user()->id,
        ]);

        if (!empty($this->supervisor_id)) {
            SupervisorDepartment::create([
                'supervisor_id' => $this->supervisor_id,
                'department_id' => $new_department->id,
            ]);
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('departments.department_created_successfully'), 'DepartmentModal');
    }

    public function update()
    {
        if (!Gate::allows('department-update')) {
            return abort(401);
        }
        // $this->validate([
        //     // 'department_id' => 'required'
        // ]);

        $this->department->update([
            'name' => $this->name,
            'is_active' => $this->is_active == true ? 1 : 0,
        ]);
        if (!empty($this->supervisor_id)) {

            SupervisorDepartment::updateOrCreate(
                [
                    'department_id' => $this->department->id,
                ],
                [
                    'supervisor_id' => $this->supervisor_id,
                    'department_id' => $this->department->id,
                ]
            );
        }
        $this->clearFields();
        $this->closeModalAndFlashMessage(__('departments.department_successfully_updated'), 'DepartmentModal');
    }

    public function assignSupervisor()
    {
        $this->validate([
            'supervisor_id' => 'required',
            'department_id' => 'required|unique:supervisor_departments,supervisor_id,NULL,id,department_id,' . $this->department_id
        ]);

        if (!empty($this->supervisor_id)) {

            SupervisorDepartment::updateOrCreate(
                [
                    'department_id' => $this->department_id,
                ],
                [
                    'supervisor_id' => $this->supervisor_id,
                    'department_id' => $this->department_id,
                ]
            );
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('departments.supervisor_successfully_assigned'), 'AssignSupModal');
    }

    public function delete()
    {
        if (!Gate::allows('department-delete')) {
            return abort(401);
        }

        if (!empty($this->department)) {
            $this->department->delete(); // Soft delete
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('departments.department_successfully_moved_to_trash'), 'DeleteModal');
    }

    public function restore()
    {
        if (!Gate::allows('department-delete')) {
            return abort(401);
        }

        $department = Department::withTrashed()->findOrFail($this->department_id);
        $department->restore();

        $this->closeModalAndFlashMessage(__('departments.department_successfully_restored'), 'RestoreModal');
    }

    public function forceDelete($departmentId)
    {
        if (!Gate::allows('department-delete')) {
            return abort(401);
        }

        $department = Department::withTrashed()->findOrFail($departmentId);
        
        // Check if department has related records
        $hasRelatedRecords = $department->services()->count() > 0 ||
                           $department->employees()->count() > 0;
        
        if ($hasRelatedRecords) {
            session()->flash('error', __('departments.cannot_permanently_delete_department'));
            return;
        }
        
        $department->forceDelete();

        $this->closeModalAndFlashMessage(__('departments.department_permanently_deleted'), 'ForceDeleteModal');
    }

    public function bulkDelete()
    {
        if (!Gate::allows('department-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedDepartments)) {
            Department::whereIn('id', $this->selectedDepartments)->delete(); // Soft delete
            $this->selectedDepartments = [];
        }

        $this->closeModalAndFlashMessage(__('departments.selected_departments_moved_to_trash'), 'BulkDeleteModal');
    }

    public function bulkRestore()
    {
        if (!Gate::allows('department-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedDepartments)) {
            Department::withTrashed()->whereIn('id', $this->selectedDepartments)->restore();
            $this->selectedDepartments = [];
        }

        $this->closeModalAndFlashMessage(__('departments.selected_departments_restored'), 'BulkRestoreModal');
    }

    public function bulkForceDelete()
    {
        if (!Gate::allows('department-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedDepartments)) {
            $departments = Department::withTrashed()->whereIn('id', $this->selectedDepartments)->get();
            $departmentsWithRelatedRecords = [];
            
            foreach ($departments as $department) {
                $hasRelatedRecords = $department->services()->count() > 0 ||
                                   $department->employees()->count() > 0;
                
                if ($hasRelatedRecords) {
                    $departmentsWithRelatedRecords[] = $department->name;
                }
            }
            
            if (!empty($departmentsWithRelatedRecords)) {
                $departmentNames = implode(', ', $departmentsWithRelatedRecords);
                session()->flash('error', __('departments.cannot_permanently_delete_departments') . $departmentNames);
                return;
            }
            
            foreach ($departments as $department) {
                $department->forceDelete();
            }
            
            $this->selectedDepartments = [];
        }

        $this->closeModalAndFlashMessage(__('departments.selected_departments_permanently_deleted'), 'BulkForceDeleteModal');
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->selectedDepartments = [];
        $this->selectAll = false;
    }

    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedDepartments = $this->getDepartments()->pluck('id')->toArray();
        } else {
            $this->selectedDepartments = [];
        }
    }

    public function toggleDepartmentSelection($departmentId)
    {
        if (in_array($departmentId, $this->selectedDepartments)) {
            $this->selectedDepartments = array_diff($this->selectedDepartments, [$departmentId]);
        } else {
            $this->selectedDepartments[] = $departmentId;
        }
        
        $this->selectAll = count($this->selectedDepartments) === $this->getDepartments()->count();
    }

    private function getDepartments()
    {
        if ($this->role === 'supervisor') {
            // For supervisors, show only their managed departments
            $query = Department::search($this->query)->supervisor();
        } else {
            // For managers and admins, show departments in the company
            $query = Department::search($this->query)->where('company_id', $this->company->id);
        }

        // Add soft delete filtering based on active tab
        if ($this->activeTab === 'deleted') {
            $query->withTrashed()->whereNotNull('deleted_at');
        } else {
            $query->whereNull('deleted_at');
        }

        // Add role-based filtering for managers
        if ($this->role === 'manager') {
            $query->manager();
        }

        return $query->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);
    }

    public function import()
    {
        $this->validate([
            'department_file' => 'sometimes|nullable|mimes:xlsx,csv|max:500',
        ]);
        Excel::import(new DepartmentImport($this->company), $this->department_file);
        auditLog(
            auth()->user(),
            'department_imported',
            'web',
            __('departments.imported_excel_file_for_departments') . $this->company->name
        );
        $this->clearFields();
        $this->closeModalAndFlashMessage(__('departments.departments_successfully_uploaded'), 'importDepartmentsModal');
    }

    public function export()
    {
        auditLog(
            auth()->user(),
            'department_exported',
            'web',
            __('departments.exported_excel_file_for_departments') . $this->company->name
        );
        return (new DepartmentExport($this->company, $this->query))->download(ucfirst($this->company->name) . '-Department-' . Str::random(5) . '.xlsx');
    }

    public function clearFields()
    {
        $this->isEditMode = false;
        $this->department = null;
        $this->reset([
            'name',
            'is_active',
            'department_id',
            'supervisor_id',
        ]);
    }
    public function render()
    {
        if (!Gate::allows('department-read')) {
            return abort(401);
        }

        $departments = $this->getDepartments();

        // Get counts for active departments (non-deleted)
        $active_departments = match ($this->role) {
            "manager" => Department::search($this->query)->manager()->where('company_id', $this->company->id)->whereNull('deleted_at')->count(),
            "admin" => Department::search($this->query)->where('company_id', $this->company->id)->whereNull('deleted_at')->count(),
            "supervisor" => Department::search($this->query)->supervisor()->whereNull('deleted_at')->count(),
           default => 0,
        };

        // Get counts for deleted departments
        $deleted_departments = match ($this->role) {
            "manager" => Department::search($this->query)->manager()->where('company_id', $this->company->id)->withTrashed()->whereNotNull('deleted_at')->count(),
            "admin" => Department::search($this->query)->where('company_id', $this->company->id)->withTrashed()->whereNotNull('deleted_at')->count(),
            "supervisor" => Department::search($this->query)->supervisor()->withTrashed()->whereNotNull('deleted_at')->count(),
           default => 0,
        };

        return view('livewire.portal.departments.index', [
            'departments' => $departments,
            'departments_count' => $active_departments, // Legacy for backward compatibility
            'active_departments' => $active_departments,
            'deleted_departments' => $deleted_departments,
        ])->layout('components.layouts.dashboard');
    }
}
