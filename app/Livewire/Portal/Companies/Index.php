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
        $company = Company::findOrFail($company_id);

        $this->company = $company;
    $this->name = $company->name;
    $this->code = $company->code;
    $this->sector = $company->sector;
    $this->description = $company->description;
    $this->company_id = $company->id;
    // Set manager_id to the first assigned manager (if any)
    $this->manager_id = $company->managers()->exists() ? $company->managers()->first()->id : null;
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
        $this->closeModalAndFlashMessage(__('Company created successfully!'), 'CreateCompanyModal');
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
        session()->flash('message', __('Company successfully updated!'));
        $this->dispatch('closeModal', id: 'EditCompanyModal');
    }

    public function delete()
    {
        if (!Gate::allows('company-delete')) {
            return abort(401);
        }

        if (!empty($this->company)) {

            $this->company->payslipProcess()->forceDelete();
            $this->company->payslips()->forceDelete();
            $this->company->employees()->forceDelete();
            $this->company->services()->forceDelete();
            $this->company->departments()->forceDelete();
            $this->company->forceDelete();
        }

        $this->clearFields();

        $this->closeModalAndFlashMessage(__('Company successfully deleted!'), 'DeleteModal');
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
        $companies = match($this->role){
            'admin' => Company::search($this->query)->with(['employees','departments','services'])->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage),
            'manager' => Company::search($this->query)->manager()->with(['employees','departments','services'])->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage),
            default=>[],
        };
        $companies_count = match($this->role){
            'admin' => Company::search($this->query)->count(),
            'manager' => Company::search($this->query)->manager()->count(),
            default=>[],
        };
        
        return view('livewire.portal.companies.index',[
            'companies' => $companies,
            'companies_count' => $companies_count,
        ])->layout('components.layouts.dashboard');
    }
}
