<?php

namespace App\Livewire\Portal\AdvanceSalaries;

use App\Exports\AdvanceSalaryExport;
use App\Livewire\Traits\WithDataTable;
use App\Mail\AdvanceSalaryManagerApprovalNotification;
use Illuminate\Support\Facades\Mail;
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
    public ?int $supervisor_approval_status = null;
    public ?string $supervisor_approval_reason = null;
    public ?int $manager_approval_status = null;
    public ?string $manager_approval_reason = null;
    public ?int $advance_salary_id = null;
    public ?string $user = null;
    public ?string $company = null;
    public ?AdvanceSalary $advance_salary = null;
    public $bulk_approval_status = true;
    public ?string $repayment_amount = null;


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
        'approval_status' => 'required_unless:role,supervisor',
        'approval_reason' => 'required',
        'supervisor_approval_status' => 'required_if:role,supervisor',
        'supervisor_approval_reason' => 'nullable',
        'manager_approval_status' => 'required_if:role,manager',
        'manager_approval_reason' => 'nullable',
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
        $this->repayment_amount = null;

        if ($this->role === 'supervisor') {
            $this->supervisor_approval_status = $advance_salary->supervisor_approval_status ?? AdvanceSalary::APPROVAL_STATUS_PENDING;
            $this->supervisor_approval_reason = $advance_salary->supervisor_approval_reason;
        } else {
            $this->manager_approval_status = $advance_salary->manager_approval_status ?? AdvanceSalary::APPROVAL_STATUS_PENDING;
            $this->manager_approval_reason = $advance_salary->manager_approval_reason;
        }
    }

    //Set Approval type
    public function initDataBulk($approval_type)
    {
        if ($approval_type == 'approve') {
            if ($this->role === 'supervisor') {
                $this->supervisor_approval_status = AdvanceSalary::APPROVAL_STATUS_APPROVED;
            } else {
                $this->manager_approval_status = AdvanceSalary::APPROVAL_STATUS_APPROVED;
                $this->approval_status = AdvanceSalary::APPROVAL_STATUS_APPROVED;
            }
            $this->bulk_approval_status = true;
        } else {
            if ($this->role === 'supervisor') {
                $this->supervisor_approval_status = AdvanceSalary::APPROVAL_STATUS_REJECTED;
            } else {
                $this->manager_approval_status = AdvanceSalary::APPROVAL_STATUS_REJECTED;
                $this->approval_status = AdvanceSalary::APPROVAL_STATUS_REJECTED;
            }
            $this->bulk_approval_status = false;
        }
    }

    //Bulk update
    public function bulkApproval()
    {
        // Check permission based on approval status
        if ($this->bulk_approval_status) {
            if (!Gate::allows('advance_salary-bulkapproval')) {
                return abort(401);
            }
        } else {
            if (!Gate::allows('advance_salary-bulkrejection')) {
                return abort(401);
            }
        }

        // Fetch records before updating for audit logging
        $advanceSalaries = AdvanceSalary::whereIn('id', $this->selectedAdvanceSalaries)->with('user')->get();
        
        // Capture old values for all records
        $affectedRecords = [];
        foreach ($advanceSalaries as $advanceSalary) {
            $affectedRecords[] = [
                'id' => $advanceSalary->id,
                'user_name' => $advanceSalary->user->name ?? 'User',
                'amount' => number_format($advanceSalary->amount) . 'XAF',
                'old_approval_status' => $advanceSalary->approval_status,
                'old_approval_reason' => $advanceSalary->approval_reason,
            ];
        }

        $updateData = $this->role === 'supervisor'
            ? [
                'supervisor_approval_status' => $this->supervisor_approval_status,
                'supervisor_approval_reason' => $this->approval_reason,
            ]
            : [
                'manager_approval_status' => $this->manager_approval_status,
                'manager_approval_reason' => $this->approval_reason,
                'approval_status' => $this->bulk_approval_status ? AdvanceSalary::APPROVAL_STATUS_APPROVED : AdvanceSalary::APPROVAL_STATUS_REJECTED,
                'approval_reason' => $this->approval_reason,
            ];
        AdvanceSalary::whereIn('id', $this->selectedAdvanceSalaries)->update($updateData);

        if ($this->role === 'supervisor' && $this->bulk_approval_status) {
            foreach ($advanceSalaries as $as) {
                $this->notifyManagers($as);
            }
        }

        // Create a single audit log entry for the bulk operation
        $actionType = $this->bulk_approval_status ? 'advanceSalary_approved' : 'advanceSalary_rejected';
        $user = auth()->user();
        
        auditLog(
            $user,
            $actionType,
            'web',
            $this->bulk_approval_status ? 'bulk_approved_advance_salaries' : 'bulk_rejected_advance_salaries',
            null, // No single model for bulk operations
            [], // Old values aggregated in metadata
            ['approval_status' => $this->approval_status, 'approval_reason' => $this->approval_reason],
            [
                'translation_key' => $this->bulk_approval_status ? 'bulk_approved_advance_salaries' : 'bulk_rejected_advance_salaries',
                'translation_params' => ['count' => count($advanceSalaries)],
                'bulk_operation' => true,
                'operation_type' => $this->bulk_approval_status ? 'bulk_approval' : 'bulk_rejection',
                'affected_count' => count($advanceSalaries),
                'affected_ids' => $advanceSalaries->pluck('id')->toArray(),
                'affected_records' => $affectedRecords,
                'new_approval_status' => $this->approval_status,
                'new_approval_reason' => $this->approval_reason,
            ]
        );

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.advance_salaries_bulk_updated'), 'EditBulkAdvanceSalaryModal');
    }


    public function update()
    {
        if (!Gate::allows('advance_salary-update')) {
            return abort(401);
        }
        $this->validate();
        $updateData = [
            'reason' => $this->reason,
            'repayment_from_month' => $this->repayment_from_month,
            'repayment_to_month' => $this->repayment_to_month,
            'beneficiary_name' => $this->beneficiary_name,
            'beneficiary_mobile_money_number' => $this->beneficiary_mobile_money_number,
            'beneficiary_id_card_number' => $this->beneficiary_id_card_number,
            'net_salary' => $this->net_salary,
        ];
        if ($this->role === 'supervisor') {
            $updateData['supervisor_approval_status'] = $this->supervisor_approval_status;
            $updateData['supervisor_approval_reason'] = $this->supervisor_approval_reason;
            if ($this->supervisor_approval_status === AdvanceSalary::APPROVAL_STATUS_REJECTED) {
                $updateData['approval_status'] = AdvanceSalary::APPROVAL_STATUS_REJECTED;
                $updateData['approval_reason'] = $this->supervisor_approval_reason;
            }
            if ($this->advance_salary->canSupervisorEditAmount()) {
                $updateData['amount'] = $this->amount;
            }
            $this->advance_salary->update($updateData);
            if ($this->supervisor_approval_status === AdvanceSalary::APPROVAL_STATUS_APPROVED) {
                $this->notifyManagers($this->advance_salary);
            }
        } else {
            $updateData['manager_approval_status'] = $this->manager_approval_status;
            $updateData['manager_approval_reason'] = $this->manager_approval_reason;
            $updateData['approval_status'] = $this->approval_status;
            $updateData['approval_reason'] = $this->approval_reason;
            $updateData['amount'] = $this->amount;
            $this->advance_salary->update($updateData);
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.advance_salary_single_updated'), 'EditAdvanceSalaryModal');
    }

    public function recordRepayment()
    {
        if (!Gate::allows('advance_salary-update')) {
            return abort(401);
        }
        if (!$this->advance_salary || $this->advance_salary->type !== AdvanceSalary::TYPE_LOAN) {
            $this->showToast(__('employees.repayment_only_for_loans'), 'danger');
            return;
        }
        if ($this->advance_salary->is_fully_repaid) {
            $this->showToast(__('employees.loan_already_fully_repaid'), 'danger');
            return;
        }
        $amount = (int) preg_replace('/[^0-9]/', '', $this->repayment_amount ?? '0');
        if ($amount <= 0) {
            $this->addError('repayment_amount', __('employees.repayment_amount_required'));
            return;
        }
        $newRepaid = $this->advance_salary->amount_repaid + $amount;
        $isFullyRepaid = $newRepaid >= $this->advance_salary->amount;
        $this->advance_salary->update([
            'amount_repaid' => min($newRepaid, $this->advance_salary->amount),
            'is_fully_repaid' => $isFullyRepaid,
        ]);
        $this->repayment_amount = null;
        $this->advance_salary->refresh();
        $this->showToast(__('employees.repayment_recorded_successfully'), 'success');
    }

    public function delete()
    {
        if (!Gate::allows('advance_salary-delete')) {
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
        if (!Gate::allows('advance_salary-restore')) {
            return abort(401);
        }

        $advanceSalary = AdvanceSalary::withTrashed()->findOrFail($advanceSalaryId);
        $advanceSalary->restore();

        $this->closeModalAndFlashMessage(__('employees.advance_salary_restored'), 'RestoreModal');
    }

    public function forceDelete($advanceSalaryId = null)
    {
        if (!Gate::allows('ticking-delete')) {
            return abort(401);
        }

        // If no advanceSalaryId provided, try to get it from selectedAdvanceSalariesForDelete
        if (!$advanceSalaryId) {
            if (!empty($this->selectedAdvanceSalariesForDelete) && is_array($this->selectedAdvanceSalariesForDelete)) {
                $advanceSalaryId = $this->selectedAdvanceSalariesForDelete[0] ?? null;
            } elseif ($this->advance_salary_id) {
                $advanceSalaryId = $this->advance_salary_id;
            } else {
                $this->showToast(__('employees.no_advance_salary_selected'), 'danger');
                return;
            }
        }

        $advanceSalary = AdvanceSalary::withTrashed()->findOrFail($advanceSalaryId);
        $advanceSalary->forceDelete();

        // Clear selection after deletion
        if (in_array($advanceSalaryId, $this->selectedAdvanceSalariesForDelete ?? [])) {
            $this->selectedAdvanceSalariesForDelete = array_diff($this->selectedAdvanceSalariesForDelete, [$advanceSalaryId]);
        }
        $this->advance_salary_id = null;

        $this->closeModalAndFlashMessage(__('employees.advance_salary_permanently_deleted'), 'ForceDeleteModal');
    }

    public function bulkDelete()
    {
        if (!Gate::allows('advance_salary-bulkdelete')) {
            return abort(401);
        }

        $targetIds = [];
        $operation = 'soft_delete';
        if (!empty($this->selectedAdvanceSalaries)) {
            $targetIds = $this->selectedAdvanceSalaries;
            $operation = 'soft_delete_active';
        } elseif (!empty($this->selectedAdvanceSalariesForDelete)) {
            $targetIds = $this->selectedAdvanceSalariesForDelete;
            $operation = 'soft_delete_deleted_tab';
        }

        $advanceSalaries = collect();
        $affectedRecords = [];
        if (!empty($targetIds)) {
            $advanceSalaries = AdvanceSalary::withTrashed()->whereIn('id', $targetIds)->with('user')->get();
            $affectedRecords = $advanceSalaries->map(function ($as) {
                return [
                    'id' => $as->id,
                    'user_name' => $as->user->name ?? 'User',
                    'amount' => $as->amount,
                    'approval_status' => $as->approval_status,
                    'approval_reason' => $as->approval_reason,
                ];
            })->toArray();
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

        if ($advanceSalaries->count() > 0) {
            auditLog(
                auth()->user(),
                'advance_salary_bulk_deleted',
                'web',
                'bulk_deleted_advance_salaries',
                null,
                [],
                [],
                [
                    'translation_key' => 'bulk_deleted_advance_salaries',
                    'translation_params' => ['count' => $advanceSalaries->count()],
                    'bulk_operation' => true,
                    'operation_type' => $operation,
                    'affected_count' => $advanceSalaries->count(),
                    'affected_ids' => $advanceSalaries->pluck('id')->toArray(),
                    'affected_records' => $affectedRecords,
                ]
            );
        }

        $this->closeModalAndFlashMessage(__('employees.selected_advance_salaries_moved_to_trash'), 'BulkDeleteModal');
    }

    public function bulkRestore()
    {
        if (!Gate::allows('advance_salary-bulkrestore')) {
            return abort(401);
        }

        $advanceSalaries = collect();
        $affectedRecords = [];
        if (!empty($this->selectedAdvanceSalariesForDelete)) {
            $advanceSalaries = AdvanceSalary::withTrashed()->whereIn('id', $this->selectedAdvanceSalariesForDelete)->with('user')->get();
            $affectedRecords = $advanceSalaries->map(function ($as) {
                return [
                    'id' => $as->id,
                    'user_name' => $as->user->name ?? 'User',
                    'amount' => $as->amount,
                    'approval_status' => $as->approval_status,
                    'approval_reason' => $as->approval_reason,
                ];
            })->toArray();
        }

        if (!empty($this->selectedAdvanceSalariesForDelete)) {
            AdvanceSalary::withTrashed()->whereIn('id', $this->selectedAdvanceSalariesForDelete)->restore();
            $this->selectedAdvanceSalariesForDelete = [];
        }

        if ($advanceSalaries->count() > 0) {
            auditLog(
                auth()->user(),
                'advance_salary_bulk_restored',
                'web',
                'bulk_restored_advance_salaries',
                null,
                [],
                [],
                [
                    'translation_key' => 'bulk_restored_advance_salaries',
                    'translation_params' => ['count' => $advanceSalaries->count()],
                    'bulk_operation' => true,
                    'operation_type' => 'bulk_restore',
                    'affected_count' => $advanceSalaries->count(),
                    'affected_ids' => $advanceSalaries->pluck('id')->toArray(),
                    'affected_records' => $affectedRecords,
                ]
            );
        }

        $this->closeModalAndFlashMessage(__('employees.selected_advance_salaries_restored'), 'BulkRestoreModal');
    }

    public function bulkForceDelete()
    {
        if (!Gate::allows('advance_salary-delete')) {
            return abort(401);
        }

        $advanceSalaries = collect();
        $affectedRecords = [];
        if (!empty($this->selectedAdvanceSalariesForDelete)) {
            $advanceSalaries = AdvanceSalary::withTrashed()->whereIn('id', $this->selectedAdvanceSalariesForDelete)->with('user')->get();
            $affectedRecords = $advanceSalaries->map(function ($as) {
                return [
                    'id' => $as->id,
                    'user_name' => $as->user->name ?? 'User',
                    'amount' => $as->amount,
                    'approval_status' => $as->approval_status,
                    'approval_reason' => $as->approval_reason,
                ];
            })->toArray();
        }

        if (!empty($this->selectedAdvanceSalariesForDelete)) {
            AdvanceSalary::withTrashed()->whereIn('id', $this->selectedAdvanceSalariesForDelete)->forceDelete();
            $this->selectedAdvanceSalariesForDelete = [];
        }

        if ($advanceSalaries->count() > 0) {
            auditLog(
                auth()->user(),
                'advance_salary_bulk_force_deleted',
                'web',
                'bulk_force_deleted_advance_salaries',
                null,
                [],
                [],
                [
                    'translation_key' => 'bulk_force_deleted_advance_salaries',
                    'translation_params' => ['count' => $advanceSalaries->count()],
                    'bulk_operation' => true,
                    'operation_type' => 'bulk_force_delete',
                    'affected_count' => $advanceSalaries->count(),
                    'affected_ids' => $advanceSalaries->pluck('id')->toArray(),
                    'affected_records' => $affectedRecords,
                ]
            );
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
        $base = AdvanceSalary::search($this->query)->with(['user', 'company'])->whereNull('deleted_at');
        $this->selectedAdvanceSalaries = match ($this->role) {
            'supervisor' => (clone $base)->supervisor()->pluck('id')->toArray(),
            'manager' => (clone $base)->manager()->pluck('id')->toArray(),
            'admin' => $base->pluck('id')->toArray(),
            default => [],
        };
        $this->updatedselectedAdvanceSalaries();
    }

    public function selectAllDeletedAdvanceSalaries()
    {
        $base = AdvanceSalary::search($this->query)->with(['user', 'company'])->withTrashed()->whereNotNull('deleted_at');
        $this->selectedAdvanceSalariesForDelete = match ($this->role) {
            'supervisor' => (clone $base)->supervisor()->pluck('id')->toArray(),
            'manager' => (clone $base)->manager()->pluck('id')->toArray(),
            'admin' => $base->pluck('id')->toArray(),
            default => [],
        };
    }

    private function notifyManagers(AdvanceSalary $advanceSalary): void
    {
        try {
            $company = $advanceSalary->company;
            foreach ($company->managers as $manager) {
                if ($manager->email) {
                    Mail::to($manager->email)->send(
                        new AdvanceSalaryManagerApprovalNotification($advanceSalary, $advanceSalary->user, $manager)
                    );
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send advance salary manager notification', [
                'advance_salary_id' => $advanceSalary->id,
                'error' => $e->getMessage(),
            ]);
        }
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
        match ($this->role) {
            'supervisor' => $query->supervisor(),
            'manager' => $query->manager(),
            'admin' => null,
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
            'repayment_amount',
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

        $supervisorScope = fn ($q) => $q->supervisor();
        $managerScope = fn ($q) => $q->manager();

        // Get counts for active advance salary records (non-deleted)
        $active_advance_salaries = match ($this->role) {
            'supervisor' => AdvanceSalary::search($this->query)->supervisor()->whereNull('deleted_at')->count(),
            'manager' => AdvanceSalary::search($this->query)->manager()->whereNull('deleted_at')->count(),
            'admin' => AdvanceSalary::search($this->query)->whereNull('deleted_at')->count(),
            default => 0,
        };

        // Get counts for deleted advance salary records
        $deleted_advance_salaries = match ($this->role) {
            'supervisor' => AdvanceSalary::search($this->query)->supervisor()->withTrashed()->whereNotNull('deleted_at')->count(),
            'manager' => AdvanceSalary::search($this->query)->manager()->withTrashed()->whereNotNull('deleted_at')->count(),
            'admin' => AdvanceSalary::search($this->query)->withTrashed()->whereNotNull('deleted_at')->count(),
            default => 0,
        };

        // Pending = supervisor sees pending their approval; manager/admin see pending manager approval
        $pending_advance_salaries_count = match ($this->role) {
            'supervisor' => AdvanceSalary::supervisor()->whereNull('deleted_at')
                ->where(function ($q) {
                    $q->whereNull('supervisor_approval_status')
                        ->orWhere('supervisor_approval_status', AdvanceSalary::APPROVAL_STATUS_PENDING);
                })->count(),
            'manager' => AdvanceSalary::manager()->whereNull('deleted_at')
                ->where('supervisor_approval_status', AdvanceSalary::APPROVAL_STATUS_APPROVED)
                ->where(function ($q) {
                    $q->whereNull('manager_approval_status')
                        ->orWhere('manager_approval_status', AdvanceSalary::APPROVAL_STATUS_PENDING);
                })->count(),
            'admin' => AdvanceSalary::whereNull('deleted_at')
                ->where('supervisor_approval_status', AdvanceSalary::APPROVAL_STATUS_APPROVED)
                ->where(function ($q) {
                    $q->whereNull('manager_approval_status')
                        ->orWhere('manager_approval_status', AdvanceSalary::APPROVAL_STATUS_PENDING);
                })->count(),
            default => 0,
        };
        $approved_advance_salaries_count = match ($this->role) {
            'supervisor' => AdvanceSalary::supervisor()->whereNull('deleted_at')->where('supervisor_approval_status', AdvanceSalary::APPROVAL_STATUS_APPROVED)->count(),
            'manager' => AdvanceSalary::manager()->whereNull('deleted_at')->where('manager_approval_status', AdvanceSalary::APPROVAL_STATUS_APPROVED)->count(),
            'admin' => AdvanceSalary::whereNull('deleted_at')->where('manager_approval_status', AdvanceSalary::APPROVAL_STATUS_APPROVED)->count(),
            default => 0,
        };
        $rejected_advance_salaries_count = match ($this->role) {
            'supervisor' => AdvanceSalary::supervisor()->whereNull('deleted_at')->where('supervisor_approval_status', AdvanceSalary::APPROVAL_STATUS_REJECTED)->count(),
            'manager' => AdvanceSalary::manager()->whereNull('deleted_at')->where('manager_approval_status', AdvanceSalary::APPROVAL_STATUS_REJECTED)->count(),
            'admin' => AdvanceSalary::whereNull('deleted_at')->where('manager_approval_status', AdvanceSalary::APPROVAL_STATUS_REJECTED)->count(),
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
