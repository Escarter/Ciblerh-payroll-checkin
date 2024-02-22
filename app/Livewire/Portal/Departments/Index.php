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

    //Update & Store Rules
    protected array $rules = [
        'name' => 'required',
    ];

    public function mount($company_uuid)
    {
        $this->company = Company::findByUuid($company_uuid);
        $this->supervisors = User::role('supervisor')->orderBy('first_name', 'desc')->where('company_id', $this->company->id)->get();
        $this->role = auth()->user()->getRoleNames()->first();
    }
    public function initData($department_id)
    {
        $department = Department::findOrFail($department_id);

        $this->supervisor_id = !empty($department->depSupervisor) ? (!empty($department->depSupervisor->supervisor) ? $department->depSupervisor->supervisor->id : '') : '';
        $this->department = $department;
        $this->department_id = $department->id;
        $this->name = $department->name;
        $this->is_active = $department->is_active;
    }
    public function store()
    {
        if (!Gate::allows('department-create')) {
            return abort(401);
        }
        $this->validate([
            'name' => 'required',
        ]);

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
        $this->closeModalAndFlashMessage(__('Department created successfully!'), 'CreateDepartmentModal');
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
        $this->closeModalAndFlashMessage(__('Department successfully updated!'), 'EditDepartmentModal');
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
        $this->closeModalAndFlashMessage(__('Supervisor successfully assigned!'), 'AssignSupModal');
    }

    public function delete()
    {
        if (!Gate::allows('department-delete')) {
            return abort(401);
        }

        if (!empty($this->department)) {
            $this->department->services()->forceDelete();
            $this->department->forceDelete();
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Department successfully deleted!'), 'DeleteModal');
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
            __('Imported excel file for departments for company ') . $this->company->name
        );
        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Departments successfully uploaded!'), 'importDepartmentsModal');
    }

    public function export()
    {
        auditLog(
            auth()->user(),
            'department_exported',
            'web',
            __('Exported excel file for departments for company ') . $this->company->name
        );
        return (new DepartmentExport($this->company, $this->query))->download(ucfirst($this->company->name) . '-Department-' . Str::random(5) . '.xlsx');
    }

    public function clearFields()
    {
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
        $departments = match ($this->role) {
            "manager" => Department::search($this->query)->manager()->where('company_id', $this->company->id)->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage),
            "admin" => Department::search($this->query)->where('company_id', $this->company->id)->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage),
            "supervisor" => [],
           default => [],
        };
        $departments_count = match ($this->role) {
            "manager" => Department::search($this->query)->manager()->where('company_id', $this->company->id)->count(),
            "admin" => Department::search($this->query)->where('company_id', $this->company->id)->count(),
            "supervisor" => [],
           default => [],
        };

        return view('livewire.portal.departments.index', [
            'departments' => $departments,
            'departments_count' => $departments_count,
        ])->layout('components.layouts.dashboard');
    }
}
