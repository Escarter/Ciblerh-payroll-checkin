<?php

namespace App\Livewire\Portal\Companies;

use App\Exports\CompanyExport;
use App\Models\User;
use App\Models\Company;
use Livewire\Component;
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

class Index extends Component
{
    use WithDataTable;

    public $managers = [];
    public $supervisors;
    public $company = null;
    public $company_id = null;
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

        // If the creator is a manager, auto-assign them to the company
        $user = auth()->user();
        if ($user && $user->hasRole('manager')) {
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

    public function restore($companyId)
    {
        if (!Gate::allows('company-delete')) {
            return abort(401);
        }

        $company = Company::withTrashed()->findOrFail($companyId);
        $company->restore();

        $this->closeModalAndFlashMessage(__('companies.company_restored_successfully'), 'RestoreModal');
    }

    public function forceDelete($companyId)
    {
        if (!Gate::allows('company-delete')) {
            return abort(401);
        }

        $company = Company::withTrashed()->findOrFail($companyId);
        
        // Check if company has related records
        $hasRelatedRecords = $company->departments()->count() > 0 ||
                           $company->employees()->count() > 0 ||
                           $company->services()->count() > 0 ||
                           $company->payslips()->count() > 0 ||
                           $company->payslipProcess()->count() > 0;
        
        if ($hasRelatedRecords) {
            session()->flash('error', __('companies.cannot_permanently_delete_company'));
            return;
        }
        
        $company->forceDelete();

        $this->closeModalAndFlashMessage(__('companies.company_permanently_deleted'), 'ForceDeleteModal');
    }

    public function bulkDelete()
    {
        if (!Gate::allows('company-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedCompanies)) {
            Company::whereIn('id', $this->selectedCompanies)->delete(); // Soft delete
            $this->selectedCompanies = [];
        }

        $this->closeModalAndFlashMessage(__('companies.selected_companies_moved_to_trash'), 'BulkDeleteModal');
    }

    public function bulkRestore()
    {
        if (!Gate::allows('company-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedCompanies)) {
            Company::withTrashed()->whereIn('id', $this->selectedCompanies)->restore();
            $this->selectedCompanies = [];
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
            
            foreach ($companies as $company) {
                $hasRelatedRecords = $company->departments()->count() > 0 ||
                                   $company->employees()->count() > 0 ||
                                   $company->services()->count() > 0 ||
                                   $company->payslips()->count() > 0 ||
                                   $company->payslipProcess()->count() > 0;
                
                if ($hasRelatedRecords) {
                    $companiesWithRelatedRecords[] = $company->name;
                }
            }
            
            if (!empty($companiesWithRelatedRecords)) {
                $companyNames = implode(', ', $companiesWithRelatedRecords);
                session()->flash('error', __('companies.cannot_permanently_delete_companies') . $companyNames);
                return;
            }
            
            foreach ($companies as $company) {
                $company->forceDelete();
            }
            
            $this->selectedCompanies = [];
        }

        $this->closeModalAndFlashMessage(__('Selected companies permanently deleted!'), 'BulkForceDeleteModal');
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
        $this->validate([
            'company_file' => 'sometimes|nullable|mimes:xlsx,csv|max:500',
        ]);
        Excel::import(new CompanyImport(), $this->company_file);
        auditLog(
            auth()->user(),
            'company_imported',
            'web',
            __('Imported excel file for companies') 
        );

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Companies successfully imported!'), 'importCompaniesModal');
    }
    public function export()
    {
        auditLog(
            auth()->user(),
            'company_exported',
            'web',
            __('Exported excel file for companies') 
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
}
