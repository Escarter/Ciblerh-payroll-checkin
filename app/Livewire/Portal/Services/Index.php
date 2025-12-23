<?php

namespace App\Livewire\Portal\Services;

use App\Models\Company;
use App\Models\Service;
use App\Livewire\BaseImportComponent;
use App\Models\Department;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Exports\ServiceExport;
use App\Imports\ServiceImport;
use App\Livewire\Traits\WithDataTable;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

class Index extends BaseImportComponent
{
    use WithDataTable;

    protected $importType = 'services';
    protected $importPermission = 'service-create';

    // Cache for existing service data to avoid N+1 queries
    protected $existingServiceNames;

    //
    public ?Department $department;
    public ?Service $service = null;
    public ?int $service_id = null;
    public ?int $department_id = null;
    public ?string $name = null;
    public ?bool $is_active = null;
    public $service_file = null;
    public $selectedDepartmentId = null;
    public $isEditMode = false;
    
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

        $this->isEditMode = true;
        $this->service = $service;
        $this->name = $service->name;
        $this->is_active = $service->is_active == "true" ? true : false;
        // $this->selectedDepartmentId = $service->department_id;
    }
    
    public function openCreateModal()
    {
        $this->clearFields();
        $this->isEditMode = false;
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
        $this->closeModalAndFlashMessage(__('services.service_created_successfully'), 'ServiceModal');
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
        $this->closeModalAndFlashMessage(__('services.service_updated_successfully'), 'ServiceModal');
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
        $this->closeModalAndFlashMessage(__('services.service_successfully_moved_to_trash'), 'DeleteModal');
    }

    public function restore()
    {
        if (!Gate::allows('service-restore')) {
            return abort(401);
        }

        $service = Service::withTrashed()->findOrFail($this->service_id);
        $service->restore();

        $this->closeModalAndFlashMessage(__('services.service_successfully_restored'), 'RestoreModal');
    }

    public function forceDelete($serviceId = null)
    {
        if (!Gate::allows('service-delete')) {
            return abort(401);
        }

        // If no serviceId provided, try to get it from selectedServices
        if (!$serviceId) {
            if (!empty($this->selectedServices) && is_array($this->selectedServices)) {
                $serviceId = $this->selectedServices[0] ?? null;
            } elseif ($this->service_id) {
                $serviceId = $this->service_id;
            } else {
                $this->showToast(__('services.no_service_selected'), 'danger');
                return;
            }
        }

        $service = Service::withTrashed()->findOrFail($serviceId);
        
        // Check if service has related tickings
        if ($service->tickings()->count() > 0) {
            $this->showToast(__('services.cannot_permanently_delete_service'), 'danger');
            return;
        }
        
        $service->forceDelete();

        // Clear selection after deletion
        if (in_array($serviceId, $this->selectedServices ?? [])) {
            $this->selectedServices = array_diff($this->selectedServices, [$serviceId]);
        }
        $this->service_id = null;

        $this->closeModalAndFlashMessage(__('services.service_permanently_deleted'), 'ForceDeleteModal');
    }

    public function bulkDelete()
    {
        if (!Gate::allows('service-bulkdelete')) {
            return abort(401);
        }

        $targetIds = $this->selectedServices ?? [];
        $services = collect();
        $affectedRecords = [];

        if (!empty($targetIds)) {
            $services = Service::withTrashed()->whereIn('id', $targetIds)->get();
            $affectedRecords = $services->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'department_id' => $service->department_id,
                    'company_id' => $service->company_id,
                ];
            })->toArray();
        }

        if (!empty($targetIds)) {
            Service::whereIn('id', $targetIds)->delete(); // Soft delete
            $this->selectedServices = [];

            if ($services->count() > 0) {
                auditLog(
                    auth()->user(),
                    'service_bulk_deleted',
                    'web',
                    __('audit_logs.bulk_deleted_services', ['count' => $services->count()]),
                    null,
                    [],
                    [],
                    [
                        'bulk_operation' => true,
                        'operation_type' => 'soft_delete',
                        'affected_count' => $services->count(),
                        'affected_ids' => $services->pluck('id')->toArray(),
                        'affected_records' => $affectedRecords,
                    ]
                );
            }
        }

        $this->closeModalAndFlashMessage(__('services.selected_services_moved_to_trash'), 'BulkDeleteModal');
    }

    public function bulkRestore()
    {
        if (!Gate::allows('service-bulkrestore')) {
            return abort(401);
        }

        $targetIds = $this->selectedServices ?? [];
        $services = collect();
        $affectedRecords = [];

        if (!empty($targetIds)) {
            $services = Service::withTrashed()->whereIn('id', $targetIds)->get();
            $affectedRecords = $services->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'department_id' => $service->department_id,
                    'company_id' => $service->company_id,
                ];
            })->toArray();
        }

        if (!empty($targetIds)) {
            Service::withTrashed()->whereIn('id', $targetIds)->restore();
            $this->selectedServices = [];

            if ($services->count() > 0) {
                auditLog(
                    auth()->user(),
                    'service_bulk_restored',
                    'web',
                    __('audit_logs.bulk_restored_services', ['count' => $services->count()]),
                    null,
                    [],
                    [],
                    [
                        'bulk_operation' => true,
                        'operation_type' => 'bulk_restore',
                        'affected_count' => $services->count(),
                        'affected_ids' => $services->pluck('id')->toArray(),
                        'affected_records' => $affectedRecords,
                    ]
                );
            }
        }

        $this->closeModalAndFlashMessage(__('services.selected_services_restored'), 'BulkRestoreModal');
    }

    public function bulkForceDelete()
    {
        if (!Gate::allows('service-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedServices)) {
            $servicesWithTickings = [];
            $affectedRecords = [];
            
            foreach ($this->selectedServices as $serviceId) {
                $service = Service::withTrashed()->find($serviceId);
                if ($service && $service->tickings()->count() > 0) {
                    $servicesWithTickings[] = $service->name;
                } elseif ($service) {
                    $affectedRecords[] = [
                        'id' => $service->id,
                        'name' => $service->name,
                        'department_id' => $service->department_id,
                        'company_id' => $service->company_id,
                    ];
                }
            }
            
            if (!empty($servicesWithTickings)) {
                $serviceNames = implode(', ', $servicesWithTickings);
                $this->showToast(__('services.cannot_permanently_delete_services') . $serviceNames, 'danger');
                return;
            }
            
            Service::withTrashed()->whereIn('id', $this->selectedServices)->forceDelete();
            $this->selectedServices = [];

            if (!empty($affectedRecords)) {
                auditLog(
                    auth()->user(),
                    'service_bulk_force_deleted',
                    'web',
                    __('audit_logs.bulk_force_deleted_services', ['count' => count($affectedRecords)]),
                    null,
                    [],
                    [],
                    [
                        'bulk_operation' => true,
                        'operation_type' => 'bulk_force_delete',
                        'affected_count' => count($affectedRecords),
                        'affected_ids' => array_column($affectedRecords, 'id'),
                        'affected_records' => $affectedRecords,
                    ]
                );
            }
        }

        $this->closeModalAndFlashMessage(__('services.selected_services_permanently_deleted'), 'BulkForceDeleteModal');
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


    public function export()
    {
        auditLog(
            auth()->user(),
            'service_exported',
            'web',
            __('audit_logs.exported_services_for_department', ['department' => $this->department->name])
        );
        return (new ServiceExport($this->department, $this->query))->download(ucfirst($this->department->name) . '-Services-' . Str::random(5) . '.xlsx');
    }

    public function clearFields()
    {
        $this->isEditMode = false;
        $this->service = null;
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

    /**
     * Get import columns for service preview
     */
    protected function getImportColumns(): array
    {
        return $this->getPreviewColumns();
    }

    /**
     * Perform the actual service import
     */
    protected function performImport()
    {
        Excel::import(new ServiceImport($this->department, $this->autoCreateEntities), $this->service_file);

        return [
            'imported_count' => 'unknown', // Could be enhanced to return actual count
            'department_name' => $this->department->name,
            'auto_create_enabled' => $this->autoCreateEntities
        ];
    }

    /**
     * Preload validation data to optimize performance
     */
    protected function preloadValidationData(): void
    {
        // Cache existing service names per department to avoid N+1 queries during validation
        if (!isset($this->existingServiceNames)) {
            $this->existingServiceNames = [];

            // Only load services for the current department to avoid loading everything
            $departmentId = $this->department ? $this->department->id : null;

            if ($departmentId) {
                $services = Service::select('name', 'department_id')
                    ->where('department_id', $departmentId)
                    ->get()
                    ->groupBy('department_id');

                foreach ($services as $deptId => $departmentServices) {
                    $this->existingServiceNames[$deptId] = $departmentServices
                        ->pluck('name')
                        ->map(function($name) {
                            return strtolower(trim($name));
                        })
                        ->toArray();
                }
            }
        }
    }

    /**
     * Check if service name exists for a department (optimized to avoid N+1 queries)
     */
    protected function isServiceNameExists(string $name, int $departmentId): bool
    {
        return isset($this->existingServiceNames[$departmentId]) &&
               in_array(strtolower(trim($name)), $this->existingServiceNames[$departmentId]);
    }

    /**
     * Get company ID (not needed for service import)
     */
    protected function getCompanyId(): ?int
    {
        return null;
    }

    /**
     * Get department ID for import
     */
    protected function getDepartmentId(): ?int
    {
        return $this->department ? $this->department->id : null;
    }

    /**
     * Validate a single preview row for services
     */
    protected function validatePreviewRow(array $rowData, int $rowNumber): array
    {
        $errors = [];
        $warnings = [];
        $parsedData = [];

        try {
            // Validate required fields
            if (empty($rowData[0] ?? '')) {
                $errors[] = __('services.name_required');
            }

            // Check for duplicate service name in department (optimized to avoid N+1 queries)
            if (!empty($rowData[0]) && $this->isServiceNameExists($rowData[0], $this->department->id)) {
                $warnings[] = __('services.name_already_exists');
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

    public function import()
    {
        // Redirect to centralized import system
        return redirect()->route('portal.import-jobs.index')->with('message', __('common.redirected_to_centralized_import'));
    }

    /**
     * Get column definitions for preview
     */
    public function getPreviewColumns(): array
    {
        return [
            0 => __('services.name'),
        ];
    }

    /**
     * Get expected columns for field validation
     */
    protected function getExpectedColumns(): array
    {
        return [
            'name'
        ];
    }

    /**
     * Override to return correct file property for this component
     */
    protected function getFileProperty()
    {
        return $this->service_file ?? null;
    }
}
