<?php
namespace App\Livewire\Portal\Companies;

use Livewire\Component;
use App\Models\Company;
use App\Models\User;

class AssignManager extends Component
{
    public $companies = [];
    public $managers = [];
    public $company_id;
    public $manager_id;

    public function mount()
    {
        $this->companies = Company::orderBy('name')->get();
        $this->managers = User::role('manager')->orderBy('first_name')->get();
    }

    public function assignManager()
    {
        $this->validate([
            'company_id' => 'required|exists:companies,id',
            'manager_id' => 'required|exists:users,id',
        ]);

        $company = Company::findOrFail($this->company_id);
        $manager = User::findOrFail($this->manager_id);

        $company->managers()->syncWithoutDetaching([$manager->id]);

        // UI feedback
    session()->flash('message', __('companies.manager_successfully_assigned'));
    $this->reset(['company_id', 'manager_id']);
    $this->dispatch('close-assign-manager-modal');
    }

    // Placeholder for future manager removal/unassignment
    public function removeManager($companyId, $managerId)
    {
        $company = Company::findOrFail($companyId);
        $company->managers()->detach($managerId);
        session()->flash('message', __('companies.manager_removed_from_company'));
    }

    public function render()
    {
        return view('livewire.portal.companies.assign-manager');
    }
}
