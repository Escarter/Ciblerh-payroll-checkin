<?php

namespace App\Livewire\Portal\Companies;

use App\Exports\CompanyExport;
use App\Models\User;
use App\Models\Company;
use App\Livewire\BaseImportComponent;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Imports\CompanyImport;
use App\Livewire\Traits\WithDataTable;
use App\Models\SendPayslipProcess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;

class Index extends BaseImportComponent
{
    use WithDataTable;

    protected $importType = 'companies';
    protected $importPermission = 'company-create';

    // Cache for existing company data to avoid N+1 queries
    protected $existingCompanyCodes;
    protected $existingCompanyNames;

    public $managers = [];
    public $supervisors;
    public $company = null;
    public $company_id = null;
    public $manager_id = null;
    public $role = null;
    public $code = null;
    public $name = null;
    public $sector = null;
    public $description = null;
    public $company_file = null;
    public $isEditMode = false;
    
    // Soft delete properties
    public $activeTab = 'active';
    public $selectedCompanies = [];
    public $selectAll = false;

    //Update & Store Rules
    protected array $rules = [
        'name' => 'required',
        'sector' => 'required',
    ];

    #[Computed]
    public function code()
    {
        return Str::upper(Str::random(10));
    }
    public function mount()
    {
    $this->role = auth()->user()->getRoleNames()->first();
    // Provide managers for create-company blade
    $this->managers = \App\Models\User::role('manager')->orderBy('first_name')->get();
        
    }

    public function initData($company_id)
    {
        $company = Company::withTrashed()->findOrFail($company_id);

        $this->isEditMode = true;
        $this->company = $company;
        $this->name = $company->name;
        $this->code = $company->code;
        $this->sector = $company->sector;
        $this->description = $company->description;
        $this->company_id = $company->id;
        // Set manager_id to the first assigned manager (if any)
        $this->manager_id = $company->managers()->exists() ? $company->managers()->first()->id : null;
    }
    
    public function openCreateModal()
    {
        $this->clearFields();
        $this->isEditMode = false;
    }

    public function store()
    {
        if (!Gate::allows('company-create')) {
            return abort(401);
        }
        
        $this->validate();

        $company = Company::create([
            'name' => $this->name,
            'code' => $this->code,
            'sector' => $this->sector,
            'description' => $this->description,
            'author_id' => auth()->user()->id,
        ]);

        // Assign the selected manager to the company
        if ($this->manager_id) {
            $company->managers()->sync([$this->manager_id]);
        }

        // If the creator is a manager and not already assigned, auto-assign them to the company
        $user = auth()->user();
        if ($user && $user->hasRole('manager') && $this->manager_id != $user->id) {
            $company->managers()->syncWithoutDetaching([$user->id]);
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('companies.company_created_successfully'), 'CompanyModal');
    }

    public function update()
    {
        if (!Gate::allows('company-update')) {
            return abort(401);
        }
        $this->validate();

        DB::transaction(function () {
            $this->company->update([
                'name' => $this->name,
                'code' => $this->code,
                'sector' => $this->sector,
                'description' => $this->description,
            ]);

            // Update the manager assignment
            if ($this->manager_id) {
                $this->company->managers()->sync([$this->manager_id]);
            } else {
                $this->company->managers()->detach();
            }
        });
        $this->clearFields();
        $this->closeModalAndFlashMessage(__('companies.company_updated_successfully'), 'CompanyModal');
    }

    public function delete()
    {
        if (!Gate::allows('company-delete')) {
            return abort(401);
        }

        if (!empty($this->company)) {
            $this->company->delete(); // Soft delete
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('companies.company_deleted_successfully'), 'DeleteModal');
    }

    public function restore()
    {
        if (!Gate::allows('company-restore')) {
            return abort(401);
        }

        $company = Company::withTrashed()->findOrFail($this->company_id);
        $company->restore();

        $this->closeModalAndFlashMessage(__('companies.company_restored_successfully'), 'RestoreModal');
    }

    public function forceDelete($companyId = null)
    {
        if (!Gate::allows('company-delete')) {
            return abort(401);
        }

        // If no companyId provided, try to get it from selectedCompanies
        if (!$companyId) {
            if (!empty($this->selectedCompanies) && is_array($this->selectedCompanies)) {
                $companyId = $this->selectedCompanies[0] ?? null;
            } elseif ($this->company_id) {
                $companyId = $this->company_id;
            } else {
                $this->showToast(__('companies.no_company_selected'), 'danger');
                return;
            }
        }

        $company = Company::withTrashed()->findOrFail($companyId);
        
        // Check if company has related records
        $hasRelatedRecords = $company->departments()->count() > 0 ||
                           $company->employees()->count() > 0 ||
                           $company->services()->count() > 0 ||
                           $company->payslips()->count() > 0 ||
                           $company->payslipProcess()->count() > 0;
        
        if ($hasRelatedRecords) {
            $this->showToast(__('companies.cannot_permanently_delete_company'), 'danger');
            return;
        }
        
        $company->forceDelete();

        // Clear selection after deletion
        if (in_array($companyId, $this->selectedCompanies ?? [])) {
            $this->selectedCompanies = array_diff($this->selectedCompanies, [$companyId]);
        }
        $this->company_id = null;

        $this->closeModalAndFlashMessage(__('companies.company_permanently_deleted'), 'ForceDeleteModal');
    }

    public function bulkDelete()
    {
        if (!Gate::allows('company-bulkdelete')) {
            return abort(401);
        }

        $targetIds = $this->selectedCompanies ?? [];
        $companies = collect();
        $affectedRecords = [];

        if (!empty($targetIds)) {
            $companies = Company::withTrashed()->whereIn('id', $targetIds)->get();
            $affectedRecords = $companies->map(function ($company) {
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                ];
            })->toArray();
        }

        if (!empty($targetIds)) {
            Company::whereIn('id', $targetIds)->delete(); // Soft delete
            $this->selectedCompanies = [];

            if ($companies->count() > 0) {
                auditLog(
                    auth()->user(),
                    'company_bulk_deleted',
                    'web',
                    __('audit_logs.bulk_deleted_companies', ['count' => $companies->count()]),
                    null,
                    [],
                    [],
                    [
                        'bulk_operation' => true,
                        'operation_type' => 'soft_delete',
                        'affected_count' => $companies->count(),
                        'affected_ids' => $companies->pluck('id')->toArray(),
                        'affected_records' => $affectedRecords,
                    ]
                );
            }
        }

        $this->closeModalAndFlashMessage(__('companies.selected_companies_moved_to_trash'), 'BulkDeleteModal');
    }

    public function bulkRestore()
    {
        if (!Gate::allows('company-bulkrestore')) {
            return abort(401);
        }

        $targetIds = $this->selectedCompanies ?? [];
        $companies = collect();
        $affectedRecords = [];

        if (!empty($targetIds)) {
            $companies = Company::withTrashed()->whereIn('id', $targetIds)->get();
            $affectedRecords = $companies->map(function ($company) {
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                ];
            })->toArray();
        }

        if (!empty($targetIds)) {
            Company::withTrashed()->whereIn('id', $targetIds)->restore();
            $this->selectedCompanies = [];

            if ($companies->count() > 0) {
                auditLog(
                    auth()->user(),
                    'company_bulk_restored',
                    'web',
                    __('audit_logs.bulk_restored_companies', ['count' => $companies->count()]),
                    null,
                    [],
                    [],
                    [
                        'bulk_operation' => true,
                        'operation_type' => 'bulk_restore',
                        'affected_count' => $companies->count(),
                        'affected_ids' => $companies->pluck('id')->toArray(),
                        'affected_records' => $affectedRecords,
                    ]
                );
            }
        }

        $this->closeModalAndFlashMessage(__('companies.selected_companies_restored'), 'BulkRestoreModal');
    }

    public function bulkForceDelete()
    {
        if (!Gate::allows('company-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedCompanies)) {
            $companies = Company::withTrashed()->whereIn('id', $this->selectedCompanies)->get();
            $companiesWithRelatedRecords = [];
            $affectedRecords = [];
            
            foreach ($companies as $company) {
                $hasRelatedRecords = $company->departments()->count() > 0 ||
                                   $company->employees()->count() > 0 ||
                                   $company->services()->count() > 0 ||
                                   $company->payslips()->count() > 0 ||
                                   $company->payslipProcess()->count() > 0;
                
                if ($hasRelatedRecords) {
                    $companiesWithRelatedRecords[] = $company->name;
                } else {
                    $affectedRecords[] = [
                        'id' => $company->id,
                        'name' => $company->name,
                    ];
                }
            }
            
            if (!empty($companiesWithRelatedRecords)) {
                $companyNames = implode(', ', $companiesWithRelatedRecords);
                $this->showToast(__('companies.cannot_permanently_delete_companies') . $companyNames, 'danger');
                return;
            }
            
            foreach ($companies as $company) {
                $company->forceDelete();
            }
            
            if (!empty($affectedRecords)) {
                auditLog(
                    auth()->user(),
                    'company_bulk_force_deleted',
                    'web',
                    __('audit_logs.bulk_force_deleted_companies', ['count' => count($affectedRecords)]),
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

            $this->selectedCompanies = [];
        }

        $this->closeModalAndFlashMessage(__('companies.selected_companies_permanently_deleted'), 'BulkForceDeleteModal');
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->selectedCompanies = [];
        $this->selectAll = false;
    }

    public function toggleSelectAll()
    {
        if ($this->selectAll) {
            // Deselect all
            $this->selectedCompanies = [];
            $this->selectAll = false;
        } else {
            // Select all companies from current page
            $this->selectedCompanies = $this->getCompanies()->pluck('id')->toArray();
            $this->selectAll = true;
        }
    }

    public function toggleCompanySelection($companyId)
    {
        if (in_array($companyId, $this->selectedCompanies)) {
            $this->selectedCompanies = array_diff($this->selectedCompanies, [$companyId]);
        } else {
            $this->selectedCompanies[] = $companyId;
        }
        
        $this->selectAll = count($this->selectedCompanies) === $this->getCompanies()->count();
    }

    private function getCompanies()
    {
        $query = Company::search($this->query)->with(['employees','departments','services']);

        // Add soft delete filtering based on active tab
        if ($this->activeTab === 'deleted') {
            $query->withTrashed()->whereNotNull('deleted_at');
        } else {
            $query->whereNull('deleted_at');
        }

        // Add role-based filtering
        match($this->role){
            'admin' => null, // No additional filtering for admin
            'manager' => $query->manager(),
            default => [],
        };

        return $query->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);
    }

    public function import()
    {
        // Redirect to centralized import system
        return redirect()->route('portal.import-jobs.index')->with('message', __('common.redirected_to_centralized_import'));
    }
    public function export()
    {
        auditLog(
            auth()->user(),
            'company_exported',
            'web',
            __('companies.exported_excel_file_for_companies')
        );
        return (new CompanyExport($this->query))->download('Companies-' . Str::random(5) . '.xlsx');
    }


    public function clearFields()
    {
        $this->isEditMode = false;
        $this->company = null;
        $this->reset([
            'name',
            'code',
            'sector',
            'description',
            'company_id',
            'manager_id',
        ]);
    }

    public function render()
    {
        if (!Gate::allows('company-read')) {
            return abort(401);
        }

        $companies = $this->getCompanies();

        // Get counts for active companies (non-deleted)
        $active_companies = match($this->role){
            'admin' => Company::search($this->query)->whereNull('deleted_at')->count(),
            'manager' => Company::search($this->query)->manager()->whereNull('deleted_at')->count(),
            default => 0,
        };

        // Get counts for deleted companies
        $deleted_companies = match($this->role){
            'admin' => Company::search($this->query)->withTrashed()->whereNotNull('deleted_at')->count(),
            'manager' => Company::search($this->query)->manager()->withTrashed()->whereNotNull('deleted_at')->count(),
            default => 0,
        };
        
        return view('livewire.portal.companies.index',[
            'companies' => $companies,
            'companies_count' => $active_companies, // Legacy for backward compatibility
            'active_companies' => $active_companies,
            'deleted_companies' => $deleted_companies,
        ])->layout('components.layouts.dashboard');
    }

    /**
     * Get import columns for company preview
     */
    protected function getImportColumns(): array
    {
        return $this->getPreviewColumns();
    }

    /**
     * Perform the actual company import
     */
    protected function performImport()
    {
        Excel::import(new CompanyImport(), $this->company_file);

        return [
            'imported_count' => 'unknown', // Could be enhanced to return actual count
        ];
    }

    /**
     * Preload validation data to optimize performance
     */
    protected function preloadValidationData(): void
    {
        // Cache existing company codes and names to avoid N+1 queries during validation
        // Limit results to prevent timeouts with large datasets
        if (!isset($this->existingCompanyCodes)) {
            $this->existingCompanyCodes = Company::whereNotNull('code')
                ->limit(2000) // Reasonable limit for company codes
                ->pluck('code')
                ->map(function($code) {
                    return strtolower(trim($code));
                })
                ->toArray();
        }

        if (!isset($this->existingCompanyNames)) {
            $this->existingCompanyNames = Company::whereNotNull('name')
                ->limit(2000) // Reasonable limit for company names
                ->pluck('name')
                ->map(function($name) {
                    return strtolower(trim($name));
                })
                ->toArray();
        }
    }

    /**
     * Check if company code exists (optimized to avoid N+1 queries)
     */
    protected function isCompanyCodeExists(string $code): bool
    {
        return in_array(strtolower(trim($code)), $this->existingCompanyCodes ?? []);
    }

    /**
     * Check if company name exists (optimized to avoid N+1 queries)
     */
    protected function isCompanyNameExists(string $name): bool
    {
        return in_array(strtolower(trim($name)), $this->existingCompanyNames ?? []);
    }

    /**
     * Get company ID (not needed for company import)
     */
    protected function getCompanyId(): ?int
    {
        return null;
    }

    /**
     * Get department ID (not needed for company import)
     */
    protected function getDepartmentId(): ?int
    {
        return null;
    }

    /**
     * Validate a single preview row for companies
     */
    protected function validatePreviewRow(array $rowData, int $rowNumber): array
    {
        $errors = [];
        $warnings = [];
        $parsedData = [];

        try {
            // Validate required fields
            if (empty($rowData[1] ?? '')) {
                $errors[] = __('companies.name_required');
            }
            if (empty($rowData[3] ?? '')) {
                $errors[] = __('companies.sector_required');
            }

            // Validate code uniqueness if provided (optimized to avoid N+1 queries)
            if (!empty($rowData[0]) && $this->isCompanyCodeExists($rowData[0])) {
                $warnings[] = __('companies.code_already_exists');
            }

            // Validate name uniqueness (optimized to avoid N+1 queries)
            if (!empty($rowData[1]) && $this->isCompanyNameExists($rowData[1])) {
                $warnings[] = __('companies.name_already_exists');
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
     * Get column definitions for preview
     */
    public function getPreviewColumns(): array
    {
        return [
            0 => __('companies.code'),
            1 => __('companies.name'),
            2 => __('companies.description'),
            3 => __('companies.sector'),
        ];
    }

    /**
     * Get expected columns for field validation
     */
    protected function getExpectedColumns(): array
    {
        return [
            'code',
            'name',
            'description',
            'sector'
        ];
    }

    /**
     * Override to return correct file property for this component
     */
    protected function getFileProperty()
    {
        return $this->company_file ?? null;
    }
}
