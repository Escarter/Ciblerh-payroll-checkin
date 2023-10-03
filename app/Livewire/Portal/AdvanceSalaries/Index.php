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
        $this->closeModalAndFlashMessage(__('Advance salaries successfully updated!'), 'EditBulkAdvanceSalaryModal');
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
        $this->closeModalAndFlashMessage(__('Advance salary successfully updated!'), 'EditAdvanceSalaryModal');
    }
    public function delete()
    {
        if (!Gate::allows('ticking-delete')) {
            return abort(401);
        }

        if (!empty($this->advance_salary)) {

            $this->advance_salary->delete();
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Advance salary successfully deleted!'), 'DeleteModal');
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
            'title' => auth()->user()->company->company_name,
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
        $advance_salaries = match($this->role) {
                "manager" => AdvanceSalary::search($this->query)->manager()->with(['user', 'company'])->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage),
                "admin" => AdvanceSalary::search($this->query)->with(['user', 'company'])->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage),
                "supervisor" => [],
               default => [],
        };
        $advance_salaries_count = match($this->role) {
                "manager" => AdvanceSalary::search($this->query)->manager()->count(),
                "admin" => AdvanceSalary::search($this->query)->count(),
                "supervisor" => [],
               default => [],
        };
        $pending_advance_salaries_count = match($this->role) {
                "manager" => AdvanceSalary::manager()->where('approval_status', AdvanceSalary::APPROVAL_STATUS_PENDING)->count(),
                "admin" => AdvanceSalary::where('approval_status', AdvanceSalary::APPROVAL_STATUS_PENDING)->count(),
                "supervisor" => [],
               default => [],
        };
        $approved_advance_salaries_count = match($this->role) {
                "manager" => AdvanceSalary::manager()->where('approval_status', AdvanceSalary::APPROVAL_STATUS_APPROVED)->count(),
                "admin" => AdvanceSalary::where('approval_status', AdvanceSalary::APPROVAL_STATUS_APPROVED)->count(),
                "supervisor" => [],
               default => [],
        };
        $rejected_advance_salaries_count = match($this->role) {
                "manager" => AdvanceSalary::manager()->where('approval_status', AdvanceSalary::APPROVAL_STATUS_REJECTED)->count(),
                "admin" => AdvanceSalary::where('approval_status', AdvanceSalary::APPROVAL_STATUS_REJECTED)->count(),
                "supervisor" => [],
               default => [],
        };
      

        return view('livewire.portal.advance-salaries.index', [
            'advance_salaries' => $advance_salaries,
            'advance_salaries_count' => $advance_salaries_count,
            'pending_advance_salaries_count' => $pending_advance_salaries_count,
            'approved_advance_salaries_count' => $approved_advance_salaries_count,
            'rejected_advance_salaries_count' => $rejected_advance_salaries_count,
        ])->layout('components.layouts.dashboard');
    }

}
