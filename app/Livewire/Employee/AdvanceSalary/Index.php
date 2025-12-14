<?php

namespace App\Livewire\Employee\AdvanceSalary;

use App\Livewire\Traits\WithDataTable;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\AdvanceSalary;
use Illuminate\Support\Facades\Gate;

class Index extends Component
{
    use WithDataTable;

    public ?array $selectedAdvanceSalaries = [];
    public bool $selectAll = false;

    // Soft delete properties
    public $activeTab = 'active';
    public $selectedAdvanceSalariesForDelete = [];
    public $selectAllForDelete = false;

    // Reactive count properties
    public $activeAdvanceSalariesCount = 0;
    public $deletedAdvanceSalariesCount = 0;

    //Create, Edit, Delete, View Post props
    public  $repayment_from_month;
    public  $repayment_to_month;
    public  $amount;
    public  $reason;
    public  $beneficiary_name;
    public  $beneficiary_id_card_number;
    public  $beneficiary_mobile_money_number;
    public  $advance_salary_id;
    public  $company_id;
    public ?AdvanceSalary $advance_salary = null;
    public $company;
    public $department;
    public $service;

    public function mount()
    {
        $this->company = auth()->user()->company;
        $this->department = auth()->user()->department;
        $this->service = auth()->user()->service;

        // Initialize counts
        $this->updateCounts();
    }

    protected $rules = [
        "amount" => "required|integer",
        "reason" => "required",
        "repayment_from_month" => "required|date|before:repayment_to_month",
        "repayment_to_month" => "required|date|after:repayment_from_month",
        "beneficiary_name" => "required",
        "beneficiary_mobile_money_number" => "required",
        "beneficiary_id_card_number" => "required",
    ];

    public function store()
    {
        if (!Gate::allows('advance_salary-create')) {
            return abort(401);
        }

        $this->validate();

        // Validate that user has required relationships
        if (empty($this->company)) {
            $this->addError('company', __('employees.not_associated_with_company'));
            return;
        }

        if (empty($this->department)) {
            $this->addError('department', __('employees.not_associated_with_department'));
            return;
        }

        auth()->user()->advanceSalaries()->create(
            [
                'company_id' => $this->company->id,
                'department_id' => $this->department->id,
                'author_id' => auth()->user()->author_id,
                'amount' => $this->amount,
                'reason' => $this->reason,
                'repayment_from_month' => $this->repayment_from_month,
                'repayment_to_month' => $this->repayment_to_month,
                'beneficiary_name' => $this->beneficiary_name,
                'beneficiary_mobile_money_number' => $this->beneficiary_mobile_money_number,
                'beneficiary_id_card_number' => $this->beneficiary_id_card_number,
            ]
        );
        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.advance_salary_recorded'), 'CreateAdvanceSalaryModal');
    }
    //Get & assign selected advance_salary props
    public function initData($advance_salary_id)
    {
        $advance_salary = AdvanceSalary::findOrFail($advance_salary_id);

        $this->advance_salary = $advance_salary;
        $this->amount = $advance_salary->amount;
        $this->reason = $advance_salary->reason;
        $this->repayment_from_month = $advance_salary->repayment_from_month->format('Y-m');
        $this->repayment_to_month = $advance_salary->repayment_to_month->format('Y-m');
        $this->beneficiary_name = $advance_salary->beneficiary_name;
        $this->beneficiary_mobile_money_number = $advance_salary->beneficiary_mobile_money_number;
        $this->beneficiary_id_card_number = $advance_salary->beneficiary_id_card_number;
        $this->advance_salary_id = $advance_salary->id;
        $this->company_id = $advance_salary->company_id;
        $this->department_id = $advance_salary->department_id;
    }


    public function update()
    {
        if (!Gate::allows('advance_salary--update')) {
            return abort(401);
        }
        $this->validate();
        $this->advance_salary->update([
            'amount' => $this->amount,
            'reason' => $this->reason,
            'repayment_from_month' => $this->repayment_from_month,
            'repayment_to_month' => $this->repayment_to_month,
            'beneficiary_name' => $this->beneficiary_name,
            'beneficiary_mobile_money_number' => $this->beneficiary_mobile_money_number,
            'beneficiary_id_card_number' => $this->beneficiary_id_card_number,
        ]);

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.advance_salary_updated'), 'EditAdvanceSalaryModal');
    }
    public function delete()
    {
        if (!Gate::allows('advance_salary--delete')) {
            return abort(401);
        }

        if (!empty($this->advance_salary)) {
            $this->advance_salary->delete();
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.advance_salary_deleted'), 'DeleteModal');

        // Update counts
        $this->updateCounts();
    }

    public function restore($advanceSalaryId)
    {
        if (!Gate::allows('advance_salary--delete')) {
            return abort(401);
        }

        $advanceSalary = AdvanceSalary::withTrashed()->findOrFail($advanceSalaryId);

        // Check if this advance salary belongs to the current user
        if ($advanceSalary->user_id !== auth()->id()) {
            return abort(403);
        }

        $advanceSalary->restore();

        $this->closeModalAndFlashMessage(__('employees.advance_salary_restored'), 'RestoreModal');

        // Update counts
        $this->updateCounts();
    }

    public function forceDelete($advanceSalaryId)
    {
        if (!Gate::allows('advance_salary--delete')) {
            return abort(401);
        }

        $advanceSalary = AdvanceSalary::withTrashed()->findOrFail($advanceSalaryId);

        // Check if this advance salary belongs to the current user
        if ($advanceSalary->user_id !== auth()->id()) {
            return abort(403);
        }

        $advanceSalary->forceDelete();

        $this->closeModalAndFlashMessage(__('employees.advance_salary_permanently_deleted'), 'ForceDeleteModal');

        // Update counts
        $this->updateCounts();
    }

    public function bulkDelete()
    {
        if (!Gate::allows('advance_salary--delete')) {
            return abort(401);
        }

        if (!empty($this->selectedAdvanceSalaries)) {
            auth()->user()->advanceSalaries()
                ->whereIn('id', $this->selectedAdvanceSalaries)
                ->where('approval_status', '!=', AdvanceSalary::APPROVAL_STATUS_APPROVED)
                ->delete();

            $this->selectedAdvanceSalaries = [];

            $this->closeModalAndFlashMessage(__('absences.selected_absences_deleted'), 'BulkDeleteModal');

            // Update counts
            $this->updateCounts();
        }
    }

    public function bulkRestore()
    {
        if (!Gate::allows('advance_salary--delete')) {
            return abort(401);
        }

        if (!empty($this->selectedAdvanceSalariesForDelete)) {
            AdvanceSalary::withTrashed()
                ->whereIn('id', $this->selectedAdvanceSalariesForDelete)
                ->where('user_id', auth()->id()) // Ensure only user's own advance salaries
                ->restore();

            $this->selectedAdvanceSalariesForDelete = [];

            $this->closeModalAndFlashMessage(__('employees.selected_advance_salaries_restored'), 'BulkRestoreModal');

            // Update counts
            $this->updateCounts();
        }
    }

    public function bulkForceDelete()
    {
        if (!Gate::allows('advance_salary--delete')) {
            return abort(401);
        }

        if (!empty($this->selectedAdvanceSalariesForDelete)) {
            AdvanceSalary::withTrashed()
                ->whereIn('id', $this->selectedAdvanceSalariesForDelete)
                ->where('user_id', auth()->id()) // Ensure only user's own advance salaries
                ->forceDelete();

            $this->selectedAdvanceSalariesForDelete = [];

            $this->closeModalAndFlashMessage(__('employees.selected_advance_salaries_permanently_deleted'), 'BulkForceDeleteModal');

            // Update counts
            $this->updateCounts();
        }
    }

    //Toggle the $selectAll on or off based on the count of selected posts
    public function updatedselectAll($value)
    {
        if ($value) {
            $this->selectedAdvanceSalaries = $this->getAdvanceSalaries()->pluck('id')->toArray();
        } else {
            $this->selectedAdvanceSalaries = [];
        }
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->selectedAdvanceSalariesForDelete = [];
        $this->selectAllForDelete = false;
    }

    public function toggleSelectAllForDelete()
    {
        if ($this->selectAllForDelete) {
            $this->selectedAdvanceSalariesForDelete = $this->getAdvanceSalaries()->pluck('id')->toArray();
        } else {
            $this->selectedAdvanceSalariesForDelete = [];
        }
    }

    public function toggleAdvanceSalarySelectionForDelete($advanceSalaryId)
    {
        if (in_array($advanceSalaryId, $this->selectedAdvanceSalariesForDelete)) {
            $this->selectedAdvanceSalariesForDelete = array_diff($this->selectedAdvanceSalariesForDelete, [$advanceSalaryId]);
        } else {
            $this->selectedAdvanceSalariesForDelete[] = $advanceSalaryId;
        }

        $this->selectAllForDelete = count($this->selectedAdvanceSalariesForDelete) === $this->getAdvanceSalaries()->count();
    }

    public function selectAllVisible()
    {
        $this->selectedAdvanceSalaries = $this->getAdvanceSalaries()->pluck('id')->toArray();
    }

    public function selectAllVisibleForDelete()
    {
        $this->selectedAdvanceSalariesForDelete = $this->getAdvanceSalaries()->pluck('id')->toArray();
    }

    public function selectAllAdvanceSalaries()
    {
        $this->selectedAdvanceSalaries = auth()->user()->advanceSalaries()->whereNull('deleted_at')->pluck('id')->toArray();
    }

    public function selectAllDeletedAdvanceSalaries()
    {
        $this->selectedAdvanceSalariesForDelete = auth()->user()->advanceSalaries()->withTrashed()->whereNotNull('deleted_at')->pluck('id')->toArray();
    }

    private function updateCounts()
    {
        $this->activeAdvanceSalariesCount = AdvanceSalary::where('user_id', auth()->user()->id)->whereNull('deleted_at')->count();
        $this->deletedAdvanceSalariesCount = AdvanceSalary::where('user_id', auth()->user()->id)->withTrashed()->whereNotNull('deleted_at')->count();
    }

    private function getAdvanceSalaries()
    {
        $query = auth()->user()->advanceSalaries()->with(['user', 'company']);

        // Add soft delete filtering based on active tab
        if ($this->activeTab === 'deleted') {
            $query->withTrashed()->whereNotNull('deleted_at');
        } else {
            $query->whereNull('deleted_at');
        }

        return $query->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);
    }

    public function clearFields()
    {
        $this->reset([
            'advance_salary',
            'advance_salary_id',
            'amount',
            'reason',
            'repayment_from_month',
            'repayment_to_month',
            'beneficiary_name',
            'beneficiary_mobile_money_number',
            'beneficiary_id_card_number',
            'selectedAdvanceSalaries',
            'selectAll',
        ]);
    }


    public function render()
    {
        if (!Gate::allows('advance_salary-read')) {
            return abort(401);
        }

        $advance_salaries = $this->getAdvanceSalaries();

        // Get counts from all advance salaries, not just current page
        $allAdvanceSalaries = auth()->user()->advanceSalaries();
        $pending_advance_salary = $allAdvanceSalaries->where('approval_status', AdvanceSalary::APPROVAL_STATUS_PENDING)->whereNull('deleted_at')->count();
        $approved_advance_salary = $allAdvanceSalaries->where('approval_status', AdvanceSalary::APPROVAL_STATUS_APPROVED)->whereNull('deleted_at')->count();
        $rejected_advance_salary = $allAdvanceSalaries->where('approval_status', AdvanceSalary::APPROVAL_STATUS_REJECTED)->whereNull('deleted_at')->count();

        return view('livewire.employee.advance-salary.index', compact('advance_salaries', 'pending_advance_salary', 'approved_advance_salary', 'rejected_advance_salary'))->layout('components.layouts.employee.master');
    }
}
