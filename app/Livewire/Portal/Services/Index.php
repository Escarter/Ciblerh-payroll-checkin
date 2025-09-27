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
    
    // Soft delete properties
    public $activeTab = 'active';
    public $selectedServices = [];
    public $selectAll = false;

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
        $service = Service::withTrashed()->findOrFail($service_id);

        $this->service = $service;
        $this->name = $service->name;
        $this->is_active = $service->is_active == "true" ? true : false;
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
            'is_active' => $this->is_active == "true" ? 1 : 0,
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
            $this->service->delete(); // Already using soft delete
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Service successfully moved to trash!'), 'DeleteModal');
    }

    public function restore($serviceId)
    {
        if (!Gate::allows('service-delete')) {
            return abort(401);
        }

        $service = Service::withTrashed()->findOrFail($serviceId);
        $service->restore();

        $this->closeModalAndFlashMessage(__('Service successfully restored!'), 'RestoreModal');
    }

    public function forceDelete($serviceId)
    {
        if (!Gate::allows('service-delete')) {
            return abort(401);
        }

        $service = Service::withTrashed()->findOrFail($serviceId);
        
        // Check if service has related tickings
        if ($service->tickings()->count() > 0) {
            session()->flash('error', __('Cannot permanently delete service. It has related tickings records.'));
            return;
        }
        
        $service->forceDelete();

        $this->closeModalAndFlashMessage(__('Service permanently deleted!'), 'ForceDeleteModal');
    }

    public function bulkDelete()
    {
        if (!Gate::allows('service-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedServices)) {
            Service::whereIn('id', $this->selectedServices)->delete(); // Soft delete
            $this->selectedServices = [];
        }

        $this->closeModalAndFlashMessage(__('Selected services moved to trash!'), 'BulkDeleteModal');
    }

    public function bulkRestore()
    {
        if (!Gate::allows('service-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedServices)) {
            Service::withTrashed()->whereIn('id', $this->selectedServices)->restore();
            $this->selectedServices = [];
        }

        $this->closeModalAndFlashMessage(__('Selected services restored!'), 'BulkRestoreModal');
    }

    public function bulkForceDelete()
    {
        if (!Gate::allows('service-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedServices)) {
            $servicesWithTickings = [];
            
            foreach ($this->selectedServices as $serviceId) {
                $service = Service::withTrashed()->find($serviceId);
                if ($service && $service->tickings()->count() > 0) {
                    $servicesWithTickings[] = $service->name;
                }
            }
            
            if (!empty($servicesWithTickings)) {
                $serviceNames = implode(', ', $servicesWithTickings);
                session()->flash('error', __('Cannot permanently delete the following services as they have related tickings records: ') . $serviceNames);
                return;
            }
            
            Service::withTrashed()->whereIn('id', $this->selectedServices)->forceDelete();
            $this->selectedServices = [];
        }

        $this->closeModalAndFlashMessage(__('Selected services permanently deleted!'), 'BulkForceDeleteModal');
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->selectedServices = [];
        $this->selectAll = false;
    }

    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            $this->selectedServices = $this->getServices()->pluck('id')->toArray();
        } else {
            $this->selectedServices = [];
        }
    }

    public function toggleServiceSelection($serviceId)
    {
        if (in_array($serviceId, $this->selectedServices)) {
            $this->selectedServices = array_diff($this->selectedServices, [$serviceId]);
        } else {
            $this->selectedServices[] = $serviceId;
        }
        
        $this->selectAll = count($this->selectedServices) === $this->getServices()->count();
    }

    private function getServices()
    {
        $query = Service::search($this->query)->with(['department','company'])->where('department_id', $this->department->id);

        // Add soft delete filtering based on active tab
        if ($this->activeTab === 'deleted') {
            $query->withTrashed()->whereNotNull('deleted_at');
        } else {
            $query->whereNull('deleted_at');
        }

        return $query->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);
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

        $services = $this->getServices();

        // Get counts for active services (non-deleted)
        $active_services = Service::where('department_id', $this->department->id)->whereNull('deleted_at')->where('is_active', true)->count();
        $inactive_services = Service::where('department_id', $this->department->id)->whereNull('deleted_at')->where('is_active', false)->count();
        $deleted_services = Service::where('department_id', $this->department->id)->withTrashed()->whereNotNull('deleted_at')->count();

        return view('livewire.portal.services.index', [
            'services' => $services,
            'services_count' => $active_services + $inactive_services, // Legacy for backward compatibility
            'active_services' => $active_services,
            'inactive_services' => $inactive_services,
            'deleted_services' => $deleted_services,
        ])->layout('components.layouts.dashboard');
    }

}
