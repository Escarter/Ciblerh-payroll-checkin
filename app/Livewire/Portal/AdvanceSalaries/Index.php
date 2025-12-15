<?php

namespace App\Livewire\Portal\AdvanceSalaries;

use App\Exports\AdvanceSalaryExport;
use App\Livewire\Traits\WithDataTable;
use PDF;
use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use App\Models\AdvanceSalary;
use App\Models\advance_salary;
use Illuminate\Support\Facades\Gate;
use PhpOffice\PhpSpreadsheet\Cell\AdvancedValueBinder;

class Index extends Component
{
    use WithDataTable;

    public ?array $selected = [];

    //Create, Edit, Delete, View Post props
    public ?string $repayment_from_month = null;
    public ?string $amount = null;
    public ?string $repayment_to_month = null;
    public ?string $reason = null;
    public ?string $beneficiary_name = null;
    public ?string $beneficiary_id_card_number = null;
    public ?string $beneficiary_mobile_money_number = null;
    public ?string $net_salary = null;
    public ?int $approval_status = 1;
    public ?string $approval_reason = null;
    public ?int $advance_salary_id = null;
    public ?string $user = null;
    public ?string $company = null;
    public ?AdvanceSalary $advance_salary = null;
    public $bulk_approval_status = true;


    //Multiple Selection props
    public array $selectedAdvanceSalaries = [];
    public bool $bulkDisabled = true;
    public bool $selectAll = false;
    public $role;
    
    // Soft delete properties
    public $activeTab = 'active';
    public $selectedAdvanceSalariesForDelete = [];
    public $selectAllForDelete = false;

    //Update & Store Rules
    protected array $rules = [
        'approval_status' => 'required',
        'approval_reason' => 'required',
    ];

    public function mount()
    {
        $this->role = auth()->user()->getRoleNames()->first();
    }
    //Toggle the $bulkDisabled on or off based on the count of selected posts
    public function updatedselectAll($value)
    {
        if ($value) {
            $this->selectedAdvanceSalaries = match($this->role) {
                "manager" => AdvanceSalary::search($this->query)->manager()->with(['user', 'company'])->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage)->pluck('id')->toArray(),
                "admin" => AdvanceSalary::search($this->query)->with(['user', 'company'])->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage)->pluck('id')->toArray(),
                "supervisor" => [],
               default => [],
            };
            $this->updatedselectedAdvanceSalaries();
        } else {
            $this->selectedAdvanceSalaries = [];
            $this->updatedselectedAdvanceSalaries();
        }
    }
    //Toggle the $bulkDisabled on or off based on the count of selected posts
    public function updatedselectedAdvanceSalaries()
    {
        $this->bulkDisabled = count($this->selectedAdvanceSalaries) < 2;
        $this->checklog = null;
    }

    //Get & assign selected advance_salary props
    public function initData($advance_salary_id)
    {
        $advance_salary = AdvanceSalary::findOrFail($advance_salary_id);

        $this->amount = $advance_salary->amount;
        $this->advance_salary = $advance_salary;
        $this->reason = $advance_salary->reason;
        $this->repayment_from_month = $advance_salary->repayment_from_month->format('Y-m');
        $this->repayment_to_month = $advance_salary->repayment_to_month->format('Y-m');
        $this->beneficiary_name = $advance_salary->beneficiary_name;
        $this->beneficiary_mobile_money_number = $advance_salary->beneficiary_mobile_money_number;
        $this->beneficiary_id_card_number = $advance_salary->beneficiary_id_card_number;
        $this->net_salary = $advance_salary->user->net_salary;
        $this->approval_status = $advance_salary->approval_status;
        $this->approval_reason = $advance_salary->approval_reason;
        $this->advance_salary_id = $advance_salary->id;
        $this->user = $advance_salary->user->name;
        $this->company = $advance_salary->company->name;
    }

    //Set Approval type
    public function initDataBulk($approval_type)
    {
        if ($approval_type == 'approve') {
            $this->approval_status = AdvanceSalary::APPROVAL_STATUS_APPROVED;
            $this->bulk_approval_status = true;
        } else {
            $this->approval_status = AdvanceSalary::APPROVAL_STATUS_REJECTED;
            $this->bulk_approval_status = false;
        }
    }

    //Bulk update
    public function bulkApproval()
    {
        AdvanceSalary::whereIn('id', $this->selectedAdvanceSalaries)->update([
            'approval_status' => $this->approval_status,
            'approval_reason' => $this->approval_reason,
        ]);
        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.advance_salaries_bulk_updated'), 'EditBulkAdvanceSalaryModal');
    }


    public function update()
    {
        if (!Gate::allows('ticking-update')) {
            return abort(401);
        }
        $this->validate();
        $this->advance_salary->update([
            'approval_status' => $this->approval_status,
            'amount' => $this->amount,
            'approval_reason' => $this->approval_reason,
            'reason' => $this->reason,
            'repayment_from_month' => $this->repayment_from_month,
            'repayment_to_month' => $this->repayment_to_month,
            'beneficiary_name' => $this->beneficiary_name,
            'beneficiary_mobile_money_number' => $this->beneficiary_mobile_money_number,
            'beneficiary_id_card_number' => $this->beneficiary_id_card_number,
            'net_salary' => $this->net_salary,
        ]);

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.advance_salary_single_updated'), 'EditAdvanceSalaryModal');
    }
    public function delete()
    {
        if (!Gate::allows('ticking-delete')) {
            return abort(401);
        }

        if (!empty($this->advance_salary)) {
            $this->advance_salary->delete(); // Already using soft delete
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.advance_salary_moved_to_trash'), 'DeleteModal');
    }

    public function restore($advanceSalaryId)
    {
        if (!Gate::allows('ticking-delete')) {
            return abort(401);
        }

        $advanceSalary = AdvanceSalary::withTrashed()->findOrFail($advanceSalaryId);
        $advanceSalary->restore();

        $this->closeModalAndFlashMessage(__('employees.advance_salary_restored'), 'RestoreModal');
    }

    public function forceDelete($advanceSalaryId)
    {
        if (!Gate::allows('ticking-delete')) {
            return abort(401);
        }

        $advanceSalary = AdvanceSalary::withTrashed()->findOrFail($advanceSalaryId);
        $advanceSalary->forceDelete();

        $this->closeModalAndFlashMessage(__('employees.advance_salary_permanently_deleted'), 'ForceDeleteModal');
    }

    public function bulkDelete()
    {
        if (!Gate::allows('advance_salary-delete')) {
            return abort(401);
        }

        // Handle both active tab (selectedAdvanceSalaries) and deleted tab (selectedAdvanceSalariesForDelete)
        if (!empty($this->selectedAdvanceSalaries)) {
            // Active tab - soft delete selected items
            AdvanceSalary::whereIn('id', $this->selectedAdvanceSalaries)->delete(); // Soft delete
            $this->selectedAdvanceSalaries = [];
            $this->selectAll = false;
        } elseif (!empty($this->selectedAdvanceSalariesForDelete)) {
            // Deleted tab - already handled by existing logic
            AdvanceSalary::whereIn('id', $this->selectedAdvanceSalariesForDelete)->delete(); // Soft delete
            $this->selectedAdvanceSalariesForDelete = [];
        }

        $this->closeModalAndFlashMessage(__('employees.selected_advance_salaries_moved_to_trash'), 'BulkDeleteModal');
    }

    public function bulkRestore()
    {
        if (!Gate::allows('ticking-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedAdvanceSalariesForDelete)) {
            AdvanceSalary::withTrashed()->whereIn('id', $this->selectedAdvanceSalariesForDelete)->restore();
            $this->selectedAdvanceSalariesForDelete = [];
        }

        $this->closeModalAndFlashMessage(__('employees.selected_advance_salaries_restored'), 'BulkRestoreModal');
    }

    public function bulkForceDelete()
    {
        if (!Gate::allows('ticking-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedAdvanceSalariesForDelete)) {
            AdvanceSalary::withTrashed()->whereIn('id', $this->selectedAdvanceSalariesForDelete)->forceDelete();
            $this->selectedAdvanceSalariesForDelete = [];
        }

        $this->closeModalAndFlashMessage(__('employees.selected_advance_salaries_permanently_deleted'), 'BulkForceDeleteModal');
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
        $this->selectedAdvanceSalaries = match ($this->role) {
            'supervisor' => AdvanceSalary::search($this->query)->supervisor()->with(['user', 'company'])->whereNull('deleted_at')->pluck('id')->toArray(),
            'manager' => AdvanceSalary::search($this->query)->manager()->with(['user', 'company'])->whereNull('deleted_at')->pluck('id')->toArray(),
            'admin' => AdvanceSalary::search($this->query)->with(['user', 'company'])->whereNull('deleted_at')->pluck('id')->toArray(),
            default => [],
        };
        $this->updatedselectedAdvanceSalaries();
    }

    public function selectAllDeletedAdvanceSalaries()
    {
        $this->selectedAdvanceSalariesForDelete = match ($this->role) {
            'supervisor' => AdvanceSalary::search($this->query)->supervisor()->with(['user', 'company'])->withTrashed()->whereNotNull('deleted_at')->pluck('id')->toArray(),
            'manager' => AdvanceSalary::search($this->query)->manager()->with(['user', 'company'])->withTrashed()->whereNotNull('deleted_at')->pluck('id')->toArray(),
            'admin' => AdvanceSalary::search($this->query)->with(['user', 'company'])->withTrashed()->whereNotNull('deleted_at')->pluck('id')->toArray(),
            default => [],
        };
    }

    private function getAdvanceSalaries()
    {
        $query = AdvanceSalary::search($this->query)->with(['user', 'company']);

        // Add soft delete filtering based on active tab
        if ($this->activeTab === 'deleted') {
            $query->withTrashed()->whereNotNull('deleted_at');
        } else {
            $query->whereNull('deleted_at');
        }

        // Add role-based filtering
        match($this->role){
            "manager" => $query->manager(),
            "admin" => null, // No additional filtering for admin
            "supervisor" => [], // Supervisor not supported for advance salaries
            default => [],
        };

        return $query->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);
    }

    public function export()
    {
        return (new AdvanceSalaryExport())->download('advance_salaries-' . Str::random(5) . '.xlsx');
    }

    public function clearFields()
    {
        $this->reset([
            'advance_salary',
            'advance_salary_id',
            'user',
            'amount',
            'company',
            'reason',
            'repayment_from_month',
            'repayment_to_month',
            'beneficiary_name',
            'beneficiary_mobile_money_number',
            'beneficiary_id_card_number',
            'net_salary',
            'approval_status',
            'approval_reason',
            'selectedAdvanceSalaries',
            'bulkDisabled',
            'selectAll',
        ]);
    }

    public function generatePDF($advance_salary_id)
    {
        $advance_salary = AdvanceSalary::findOrFail($advance_salary_id);
        set_time_limit(600);
        $data = [
            'title' => auth()->user()->company?->name ?? 'Company',
            'date' => date('m/d/Y'),
            'advance_salary' => $advance_salary,
        ];

        $pdf = PDF::loadView('livewire.portal.advance-salaries.printable-advance-salary', $data)->setPaper('letter', 'portrait')->setOptions(['dpi' => 96]);

        return response()->streamDownload(
            fn () => print($pdf->output()),
            __('AdvanceSalary-') . Str::random('10') . ".pdf"
        );
    }
    public function render()
    {
        if (!Gate::allows('advance_salary-read')) {
            return abort(401);
        }

        $advance_salaries = $this->getAdvanceSalaries();

        // Get counts for active advance salary records (non-deleted)
        $active_advance_salaries = match($this->role){
            "manager" => AdvanceSalary::search($this->query)->manager()->whereNull('deleted_at')->count(),
            "admin" => AdvanceSalary::search($this->query)->whereNull('deleted_at')->count(),
            "supervisor" => 0, // Supervisor not supported for advance salaries
           default => 0,
        };

        // Get counts for deleted advance salary records
        $deleted_advance_salaries = match($this->role){
            "manager" => AdvanceSalary::search($this->query)->manager()->withTrashed()->whereNotNull('deleted_at')->count(),
            "admin" => AdvanceSalary::search($this->query)->withTrashed()->whereNotNull('deleted_at')->count(),
            "supervisor" => 0, // Supervisor not supported for advance salaries
           default => 0,
        };

        // Get approval status counts for active records only
        $pending_advance_salaries_count = match($this->role){
            "manager" => AdvanceSalary::manager()->whereNull('deleted_at')->where('approval_status', AdvanceSalary::APPROVAL_STATUS_PENDING)->count(),
            "admin" => AdvanceSalary::whereNull('deleted_at')->where('approval_status', AdvanceSalary::APPROVAL_STATUS_PENDING)->count(),
            "supervisor" => 0, // Supervisor not supported for advance salaries
           default => 0,
        };
        $approved_advance_salaries_count = match($this->role){
            "manager" => AdvanceSalary::manager()->whereNull('deleted_at')->where('approval_status', AdvanceSalary::APPROVAL_STATUS_APPROVED)->count(),
            "admin" => AdvanceSalary::whereNull('deleted_at')->where('approval_status', AdvanceSalary::APPROVAL_STATUS_APPROVED)->count(),
            "supervisor" => 0, // Supervisor not supported for advance salaries
           default => 0,
        };
        $rejected_advance_salaries_count = match($this->role){
            "manager" => AdvanceSalary::manager()->whereNull('deleted_at')->where('approval_status', AdvanceSalary::APPROVAL_STATUS_REJECTED)->count(),
            "admin" => AdvanceSalary::whereNull('deleted_at')->where('approval_status', AdvanceSalary::APPROVAL_STATUS_REJECTED)->count(),
            "supervisor" => 0, // Supervisor not supported for advance salaries
           default => 0,
        };

        return view('livewire.portal.advance-salaries.index', [
            'advance_salaries' => $advance_salaries,
            'advance_salaries_count' => $active_advance_salaries, // Legacy for backward compatibility
            'active_advance_salaries' => $active_advance_salaries,
            'deleted_advance_salaries' => $deleted_advance_salaries,
            'pending_advance_salaries_count' => $pending_advance_salaries_count,
            'approved_advance_salaries_count' => $approved_advance_salaries_count,
            'rejected_advance_salaries_count' => $rejected_advance_salaries_count,
        ])->layout('components.layouts.dashboard');
    }

}
