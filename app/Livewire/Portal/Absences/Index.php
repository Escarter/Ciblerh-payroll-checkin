<?php

namespace App\Livewire\Portal\Absences;

use App\Models\Absence;
use Livewire\Component;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use App\Exports\AbsencesExport;
use App\Livewire\Traits\WithDataTable;
use Illuminate\Support\Facades\Gate;

class Index extends Component
{
    use WithDataTable;

    public ?array $selected = [];

    //Create, Edit, Delete, View Post props
    public ?string $absence_date = null;
    public ?string $absence_reason = null;
    public $approval_status;
    public ?string $approval_reason = null;
    public ?int $absence_id = null;
    public ?string $user = null;
    public ?string $role = null;
    public ?Absence $absence = null;


    //Multiple Selection props
    public array $selectedAbsences = [];
    public bool $bulkDisabled = true;
    public bool $selectAll = false;

    public $bulk_approval_status = true;
    
    // Soft delete properties
    public $activeTab = 'active';
    public $selectedAbsencesForDelete = [];
    public $selectAllForDelete = false;


    //Update & Store Rules
    protected $rules = [
        'approval_status' => 'required|integer',
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
            $this->selectedAbsences = match ($this->role) {
                'supervisor' => Absence::search($this->query)->supervisor()->with(['user', 'company'])->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage)->pluck('id')->toArray(),
                'manager' => Absence::search($this->query)->manager()->with(['user', 'company'])->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage)->pluck('id')->toArray(),
                'admin' => Absence::search($this->query)->with(['user', 'company'])->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage)->pluck('id')->toArray(),
                default => [],
            };
                 $this->updatedselectedAbsences();
        } else {
            $this->selectedAbsences = [];
            $this->updatedselectedAbsences();
        }
    }
    //Toggle the $bulkDisabled on or off based on the count of selected posts
    public function updatedselectedAbsences()
    {
        $this->bulkDisabled = count($this->selectedAbsences) < 2;
    }

    // Enhanced selection methods
    public function selectAllVisible()
    {
        $this->selectedAbsences = $this->getAbsences()->pluck('id')->toArray();
        $this->updatedselectedAbsences();
    }

    public function selectAllAbsences()
    {
        $this->selectedAbsences = match ($this->role) {
            'supervisor' => Absence::search($this->query)->supervisor()->with(['user', 'company'])->whereNull('deleted_at')->pluck('id')->toArray(),
            'manager' => Absence::search($this->query)->manager()->with(['user', 'company'])->whereNull('deleted_at')->pluck('id')->toArray(),
            'admin' => Absence::search($this->query)->with(['user', 'company'])->whereNull('deleted_at')->pluck('id')->toArray(),
            default => [],
        };
        $this->updatedselectedAbsences();
    }

    public function selectAllDeletedAbsences()
    {
        $this->selectedAbsencesForDelete = match ($this->role) {
            'supervisor' => Absence::search($this->query)->supervisor()->with(['user', 'company'])->withTrashed()->whereNotNull('deleted_at')->pluck('id')->toArray(),
            'manager' => Absence::search($this->query)->manager()->with(['user', 'company'])->withTrashed()->whereNotNull('deleted_at')->pluck('id')->toArray(),
            'admin' => Absence::search($this->query)->with(['user', 'company'])->withTrashed()->whereNotNull('deleted_at')->pluck('id')->toArray(),
            default => [],
        };
    }

    //Get & assign selected absence props
    public function initData($absence_id)
    {
        $absence = Absence::findOrFail($absence_id);

        $this->absence = $absence;
        $this->absence_date = $absence-> absence_date->format('Y-m-d');
        $this->absence_reason = $absence->absence_reason;
        $this->approval_status = $absence->approval_status;
        $this->approval_reason = $absence->approval_reason;
        $this->absence_id = $absence->id;
        $this->user = $absence->user->name;
    }
    //Set Approval type
    public function initDataBulk($approval_type)
    {
        if ($approval_type == 'approve') {
            $this->approval_status = Absence::APPROVAL_STATUS_APPROVED;
            $this->bulk_approval_status = true;
        } else {
            $this->approval_status = Absence::APPROVAL_STATUS_REJECTED;
            $this->bulk_approval_status = false;
        }
    }

    //Bulk update
    public function bulkApproval()
    {
        // Check permission based on approval status
        if ($this->bulk_approval_status) {
            if (!Gate::allows('absence-bulkapproval')) {
                return abort(401);
            }
        } else {
            if (!Gate::allows('absence-bulkrejection')) {
                return abort(401);
            }
        }

        // Fetch records before updating for audit logging
        $absences = Absence::whereIn('id', $this->selectedAbsences)->with('user')->get();
        
        // Capture old values for all records
        $affectedRecords = [];
        foreach ($absences as $absence) {
            $affectedRecords[] = [
                'id' => $absence->id,
                'user_name' => $absence->user->name ?? 'User',
                'date' => $absence->absence_date,
                'old_approval_status' => $absence->approval_status,
                'old_approval_reason' => $absence->approval_reason,
            ];
        }

        // Perform bulk update
        Absence::whereIn('id', $this->selectedAbsences)->update([
            'approval_status' => $this->approval_status,
            'approval_reason' => $this->approval_reason,
        ]);

        // Create a single audit log entry for the bulk operation
        $actionType = $this->bulk_approval_status ? 'absence_approved' : 'absence_rejected';
        $user = auth()->user();
        $request = request();
        
        auditLog(
            $user,
            $actionType,
            'web',
            $this->bulk_approval_status 
                ? __('audit_logs.bulk_approved_absences', ['count' => count($absences)])
                : __('audit_logs.bulk_rejected_absences', ['count' => count($absences)]),
            null, // No single model for bulk operations
            [], // Old values aggregated in metadata
            ['approval_status' => $this->approval_status, 'approval_reason' => $this->approval_reason],
            [
                'bulk_operation' => true,
                'operation_type' => $this->bulk_approval_status ? 'bulk_approval' : 'bulk_rejection',
                'affected_count' => count($absences),
                'affected_ids' => $absences->pluck('id')->toArray(),
                'affected_records' => $affectedRecords,
                'new_approval_status' => $this->approval_status,
                'new_approval_reason' => $this->approval_reason,
            ]
        );

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('absences.absences_successfully_updated'), 'EditBulkAbsenceModal');
    }

    public function update()
    {
        if (!Gate::allows('absence-update')) {
            return abort(401);
        }
        $this->validate();

        $this->absence->update([
            'approval_status' => $this->approval_status,
            'approval_reason' => $this->approval_reason,
        ]);

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('absences.absence_successfully_updated'), 'EditAbsenceModal');
    }
    public function delete()
    {
        if (!Gate::allows('absence-delete')) {
            return abort(401);
        }

        if (!empty($this->absence)) {
            $this->absence->delete(); // Already using soft delete
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('absences.absence_successfully_moved_to_trash'), 'DeleteModal');
    }

    public function restore($absenceId)
    {
        if (!Gate::allows('absence-restore')) {
            return abort(401);
        }

        $absence = Absence::withTrashed()->findOrFail($absenceId);
        $absence->restore();

        $this->closeModalAndFlashMessage(__('absences.absence_successfully_restored'), 'RestoreModal');
    }

    public function forceDelete($absenceId = null)
    {
        if (!Gate::allows('absence-delete')) {
            return abort(401);
        }

        // If no absenceId provided, try to get it from selectedAbsencesForDelete
        if (!$absenceId) {
            if (!empty($this->selectedAbsencesForDelete) && is_array($this->selectedAbsencesForDelete)) {
                $absenceId = $this->selectedAbsencesForDelete[0] ?? null;
            } elseif ($this->absence_id) {
                $absenceId = $this->absence_id;
            } else {
                $this->showToast(__('absences.no_absence_selected'), 'danger');
                return;
            }
        }

        $absence = Absence::withTrashed()->findOrFail($absenceId);
        $absence->forceDelete();

        // Clear selection after deletion
        if (in_array($absenceId, $this->selectedAbsencesForDelete ?? [])) {
            $this->selectedAbsencesForDelete = array_diff($this->selectedAbsencesForDelete, [$absenceId]);
        }
        $this->absence_id = null;

        $this->closeModalAndFlashMessage(__('absences.absence_permanently_deleted'), 'ForceDeleteModal');
    }

    public function bulkDelete()
    {
        if (!Gate::allows('absence-bulkdelete')) {
            return abort(401);
        }

        // Determine which set to operate on and capture affected records
        $targetIds = [];
        $operation = 'soft_delete';
        if (!empty($this->selectedAbsences)) {
            $targetIds = $this->selectedAbsences;
            $operation = 'soft_delete_active';
        } elseif (!empty($this->selectedAbsencesForDelete)) {
            $targetIds = $this->selectedAbsencesForDelete;
            $operation = 'soft_delete_deleted_tab';
        }

        if (!empty($targetIds)) {
            $absences = Absence::withTrashed()->whereIn('id', $targetIds)->with('user')->get();
            $affectedRecords = $absences->map(function ($absence) {
                return [
                    'id' => $absence->id,
                    'user_name' => $absence->user->name ?? 'User',
                    'date' => $absence->absence_date,
                    'approval_status' => $absence->approval_status,
                    'approval_reason' => $absence->approval_reason,
                ];
            })->toArray();
        } else {
            $absences = collect();
            $affectedRecords = [];
        }

        // Handle both active tab (selectedAbsences) and deleted tab (selectedAbsencesForDelete)
        if (!empty($this->selectedAbsences)) {
            // Active tab - soft delete selected items
            Absence::whereIn('id', $this->selectedAbsences)->delete(); // Soft delete
            $this->selectedAbsences = [];
            $this->selectAll = false;
        } elseif (!empty($this->selectedAbsencesForDelete)) {
            // Deleted tab - already handled by existing logic
            Absence::whereIn('id', $this->selectedAbsencesForDelete)->delete(); // Soft delete
            $this->selectedAbsencesForDelete = [];
        }

        // Single audit log entry for the bulk delete
        if ($absences->count() > 0) {
            auditLog(
                auth()->user(),
                'absence_bulk_deleted',
                'web',
                __('audit_logs.bulk_deleted_absences', ['count' => $absences->count()]),
                null,
                [],
                [],
                [
                    'bulk_operation' => true,
                    'operation_type' => $operation,
                    'affected_count' => $absences->count(),
                    'affected_ids' => $absences->pluck('id')->toArray(),
                    'affected_records' => $affectedRecords,
                ]
            );
        }

        $this->closeModalAndFlashMessage(__('absences.selected_absence_records_moved_to_trash'), 'BulkDeleteModal');
    }

    public function bulkRestore()
    {
        if (!Gate::allows('absence-bulkrestore')) {
            return abort(401);
        }

        $absences = collect();
        $affectedRecords = [];
        if (!empty($this->selectedAbsencesForDelete)) {
            $absences = Absence::withTrashed()->whereIn('id', $this->selectedAbsencesForDelete)->with('user')->get();
            $affectedRecords = $absences->map(function ($absence) {
                return [
                    'id' => $absence->id,
                    'user_name' => $absence->user->name ?? 'User',
                    'date' => $absence->absence_date,
                    'approval_status' => $absence->approval_status,
                    'approval_reason' => $absence->approval_reason,
                ];
            })->toArray();
        }

        if (!empty($this->selectedAbsencesForDelete)) {
            Absence::withTrashed()->whereIn('id', $this->selectedAbsencesForDelete)->restore();
            $this->selectedAbsencesForDelete = [];
        }

        if ($absences->count() > 0) {
            auditLog(
                auth()->user(),
                'absence_bulk_restored',
                'web',
                __('audit_logs.bulk_restored_absences', ['count' => $absences->count()]),
                null,
                [],
                [],
                [
                    'bulk_operation' => true,
                    'operation_type' => 'bulk_restore',
                    'affected_count' => $absences->count(),
                    'affected_ids' => $absences->pluck('id')->toArray(),
                    'affected_records' => $affectedRecords,
                ]
            );
        }

        $this->closeModalAndFlashMessage(__('absences.selected_absence_records_restored'), 'BulkRestoreModal');
    }

    public function bulkForceDelete()
    {
        if (!Gate::allows('absence-delete')) {
            return abort(401);
        }

        $absences = collect();
        $affectedRecords = [];
        if (!empty($this->selectedAbsencesForDelete)) {
            $absences = Absence::withTrashed()->whereIn('id', $this->selectedAbsencesForDelete)->with('user')->get();
            $affectedRecords = $absences->map(function ($absence) {
                return [
                    'id' => $absence->id,
                    'user_name' => $absence->user->name ?? 'User',
                    'date' => $absence->absence_date,
                    'approval_status' => $absence->approval_status,
                    'approval_reason' => $absence->approval_reason,
                ];
            })->toArray();
        }

        if (!empty($this->selectedAbsencesForDelete)) {
            Absence::withTrashed()->whereIn('id', $this->selectedAbsencesForDelete)->forceDelete();
            $this->selectedAbsencesForDelete = [];
        }

        if ($absences->count() > 0) {
            auditLog(
                auth()->user(),
                'absence_bulk_force_deleted',
                'web',
                __('audit_logs.bulk_force_deleted_absences', ['count' => $absences->count()]),
                null,
                [],
                [],
                [
                    'bulk_operation' => true,
                    'operation_type' => 'bulk_force_delete',
                    'affected_count' => $absences->count(),
                    'affected_ids' => $absences->pluck('id')->toArray(),
                    'affected_records' => $affectedRecords,
                ]
            );
        }

        $this->closeModalAndFlashMessage(__('absences.selected_absence_records_permanently_deleted'), 'BulkForceDeleteModal');
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->selectedAbsencesForDelete = [];
        $this->selectAllForDelete = false;
    }

    public function toggleSelectAllForDelete()
    {
        if ($this->selectAllForDelete) {
            $this->selectedAbsencesForDelete = $this->getAbsences()->pluck('id')->toArray();
        } else {
            $this->selectedAbsencesForDelete = [];
        }
    }

    public function toggleAbsenceSelectionForDelete($absenceId)
    {
        if (in_array($absenceId, $this->selectedAbsencesForDelete)) {
            $this->selectedAbsencesForDelete = array_diff($this->selectedAbsencesForDelete, [$absenceId]);
        } else {
            $this->selectedAbsencesForDelete[] = $absenceId;
        }
        
        $this->selectAllForDelete = count($this->selectedAbsencesForDelete) === $this->getAbsences()->count();
    }

    private function getAbsences()
    {
        $query = Absence::search($this->query)->with(['user', 'company']);

        // Add soft delete filtering based on active tab
        if ($this->activeTab === 'deleted') {
            $query->withTrashed()->whereNotNull('deleted_at');
        } else {
            $query->whereNull('deleted_at');
        }

        // Add role-based filtering
        match($this->role){
            "supervisor" => $query->supervisor(),
            "manager" => $query->manager(),
            "admin" => null, // No additional filtering for admin
            default => [],
        };

        return $query->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage);
    }
  
    public function clearFields()
    {
        $this->reset([
            'absence',
            'absence_id',
            'user',
            'absence_date',
            'absence_reason',
            'approval_status',
            'approval_reason',
            'selectedAbsences',
            'bulkDisabled',
            'selectAll'
        ]);
    }

    public function export()
    {
        return (new AbsencesExport())->download('absences-' . Str::random(5) . '.xlsx');
    }

    public function render()
    {
        if (!Gate::allows('absence-read')) {
            return abort(401);
        }

        $absences = $this->getAbsences();

        // Get counts for active absence records (non-deleted)
        $active_absences = match($this->role){
            'supervisor' => Absence::search($this->query)->supervisor()->whereNull('deleted_at')->count(),
            'manager' => Absence::search($this->query)->manager()->whereNull('deleted_at')->count(),
            'admin' => Absence::search($this->query)->whereNull('deleted_at')->count(),
            default => 0,
        };

        // Get counts for deleted absence records
        $deleted_absences = match($this->role){
            'supervisor' => Absence::search($this->query)->supervisor()->withTrashed()->whereNotNull('deleted_at')->count(),
            'manager' => Absence::search($this->query)->manager()->withTrashed()->whereNotNull('deleted_at')->count(),
            'admin' => Absence::search($this->query)->withTrashed()->whereNotNull('deleted_at')->count(),
            default => 0,
        };

        // Get approval status counts for active records only
        $pending_absences_count = match($this->role){
            'supervisor' => Absence::supervisor()->whereNull('deleted_at')->where('approval_status', Absence::APPROVAL_STATUS_PENDING)->count(),
            'manager' => Absence::manager()->whereNull('deleted_at')->where('approval_status', Absence::APPROVAL_STATUS_PENDING)->count(),
            'admin' => Absence::whereNull('deleted_at')->where('approval_status', Absence::APPROVAL_STATUS_PENDING)->count(),
            default => 0,
        };

        $approved_absences_count = match($this->role){
            'supervisor' => Absence::supervisor()->whereNull('deleted_at')->where('approval_status', Absence::APPROVAL_STATUS_APPROVED)->count(),
            'manager' => Absence::manager()->whereNull('deleted_at')->where('approval_status', Absence::APPROVAL_STATUS_APPROVED)->count(),
            'admin' => Absence::whereNull('deleted_at')->where('approval_status', Absence::APPROVAL_STATUS_APPROVED)->count(),
            default => 0, 
        };

        $rejected_absences_count = match($this->role){
            'supervisor' => Absence::supervisor()->whereNull('deleted_at')->where('approval_status', Absence::APPROVAL_STATUS_REJECTED)->count(),
            'manager' => Absence::manager()->whereNull('deleted_at')->where('approval_status', Absence::APPROVAL_STATUS_REJECTED)->count(),
            'admin' => Absence::whereNull('deleted_at')->where('approval_status', Absence::APPROVAL_STATUS_REJECTED)->count(),
            default => 0,
        };
       
        return view('livewire.portal.absences.index', [
            'absences' => $absences,
            'absences_count' => $active_absences, // Legacy for backward compatibility
            'active_absences' => $active_absences,
            'deleted_absences' => $deleted_absences,
            'pending_absences_count' => $pending_absences_count,
            'approved_absences_count' => $approved_absences_count,
            'rejected_absences_count' => $rejected_absences_count,
        ])->layout('components.layouts.dashboard');
    }
}