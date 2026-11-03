<?php

namespace App\Livewire;

use App\Exports\Services\ExportService;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

class ExportWizard extends Component
{
    public string $entitySlug = '';
    public array $selectedColumns = [];
    public string $searchQuery = '';
    public string $exportFormat = 'xlsx';
    public ?int $selectedCompanyId = null;
    public ?int $selectedDepartmentId = null;
    public ?int $selectedServiceId = null;

    // Available data
    public array $availableEntities = [];
    public array $columnDefinitions = [];
    public array $companies = [];
    public array $departments = [];
    public array $services = [];

    public function mount()
    {
        $service = app(ExportService::class);
        $this->availableEntities = $service->getAvailableEntities();

        $this->companies = \App\Models\Company::orderBy('name')
            ->get(['id', 'name', 'code'])
            ->toArray();
    }

    public function updatedEntitySlug()
    {
        // Reset filters when entity changes
        $this->selectedCompanyId = null;
        $this->selectedDepartmentId = null;
        $this->selectedServiceId = null;
        $this->departments = [];
        $this->services = [];
        $this->searchQuery = '';

        if ($this->entitySlug) {
            $service = app(ExportService::class);
            $this->columnDefinitions = $service->getColumns($this->entitySlug);
            $adapter = $service->getAdapter($this->entitySlug);
            $this->selectedColumns = $adapter ? $adapter->getDefaultColumns() : [];
        } else {
            $this->columnDefinitions = [];
            $this->selectedColumns = [];
        }
    }

    public function updatedSelectedCompanyId($value)
    {
        $this->departments = [];
        $this->services = [];
        $this->selectedDepartmentId = null;
        $this->selectedServiceId = null;

        if ($value) {
            $this->departments = \App\Models\Department::where('company_id', $value)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->toArray();
        }
    }

    public function updatedSelectedDepartmentId($value)
    {
        $this->services = [];
        $this->selectedServiceId = null;

        if ($value) {
            $this->services = \App\Models\Service::where('department_id', $value)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->toArray();
        }
    }

    public function toggleColumn(string $field)
    {
        if (in_array($field, $this->selectedColumns)) {
            $this->selectedColumns = array_values(array_diff($this->selectedColumns, [$field]));
        } else {
            $this->selectedColumns[] = $field;
        }
    }

    public function selectAllColumns()
    {
        $this->selectedColumns = collect($this->columnDefinitions)->pluck('field')->toArray();
    }

    public function deselectAllColumns()
    {
        $this->selectedColumns = [];
    }

    public function export()
    {
        if (!$this->entitySlug) {
            $this->dispatch('showToast', message: __('import.select_entity_type'), type: 'danger');
            return;
        }

        if (empty($this->selectedColumns)) {
            $this->dispatch('showToast', message: __('export.select_at_least_one'), type: 'danger');
            return;
        }

        $service = app(ExportService::class);

        $context = array_filter([
            'company_id' => $this->selectedCompanyId,
            'department_id' => $this->selectedDepartmentId,
            'service_id' => $this->selectedServiceId,
        ]);

        $adapter = $service->prepareExport(
            $this->entitySlug,
            $context,
            $this->selectedColumns,
            $this->searchQuery
        );

        $filename = $adapter->getFilename($this->exportFormat);

        return Excel::download($adapter, $filename);
    }

    public function render()
    {
        return view('livewire.export-wizard')->layout('components.layouts.dashboard');
    }
}
