<?php

namespace App\Livewire\Portal\Services;

use App\Models\Company;
use App\Models\Service;
use Livewire\Component;
use App\Models\Department;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Exports\ServiceExport;
use App\Imports\ServiceImport;
use App\Livewire\Traits\WithDataTable;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

class Index extends Component
{
    use WithDataTable;

    //
    public ?Department $department;
    public ?Service $service = null;
    public ?int $department_id = null;
    public ?string $name = null;
    public ?bool $is_active = null;
    public $service_file = null;
    public $selectedDepartmentId = null;

    //Update & Store Rules
    protected array $rules = [
        'name' => 'required',
    ];

    public function mount($department_uuid)
    {
        $this->department = Department::findByUuid($department_uuid);
        // $this->departments = $this->company->departments;
    }
    public function initData($service_id)
    {
        $service = Service::findOrFail($service_id);

        $this->service = $service;
        $this->name = $service->name;
        $this->is_active = $service->is_active;
        // $this->selectedDepartmentId = $service->department_id;
    }
    public function store()
    {
        if (!Gate::allows('service-create')) {
            return abort(401);
        }
        $this->validate();

        Service::create([
            'name' => $this->name,
            'department_id' => $this->department->id,
            'company_id' => $this->department->company_id,
            'author_id' => auth()->user()->id,
        ]);

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Service created successfully!'), 'CreateServiceModal');
    }

    public function update()
    {
        if (!Gate::allows('service-update')) {
            return abort(401);
        }
        $this->validate();


        $this->service->update([
            'name' => $this->name,
            'is_active' => $this->is_active,
        ]);

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Service successfully updated!'), 'EditServiceModal');
    }

    public function delete()
    {
        if (!Gate::allows('service-delete')) {
            return abort(401);
        }

        if (!empty($this->service)) {

            $this->service->delete();
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Service successfully deleted!'), 'DeleteModal');
    }

    public function import()
    {
        $this->validate([
            'service_file' => 'sometimes|nullable|mimes:xlsx,csv|max:500',
        ]);

        Excel::import(new ServiceImport($this->department), $this->service_file);

        auditLog(
            auth()->user(),
            'service_imported',
            'web',
            __('Imported excel file for services for department '). $this->department->name
        );
        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Services successfully imported!'), 'importServicesModal');
    }

    public function export()
    {
        auditLog(
            auth()->user(),
            'service_exported',
            'web',
            __('Exported excel file for services for department ') . $this->department->name
        );
        return (new ServiceExport($this->department, $this->query))->download(ucfirst($this->department->name) . '-Services-' . Str::random(5) . '.xlsx');
    }

    public function clearFields()
    {
        $this->reset([
            'name',
            'is_active',
            'selectedDepartmentId',
        ]);
    }
    public function render()
    {
        if (!Gate::allows('service-read')) {
            return abort(401);
        }
        $services = Service::search($this->query)->with(['department','company'])->where('department_id', $this->department->id)->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);
        return view('livewire.portal.services.index', [
            'services' => $services,
            'services_count' => Service::where('department_id', $this->department->id)->count(),
            'active_services' => Service::where('department_id', $this->department->id)->where('is_active', true)->count(),
            'inactive_services' => Service::where('department_id', $this->department->id)->where('is_active', false)->count(),
        ])->layout('components.layouts.dashboard');
    }

}
