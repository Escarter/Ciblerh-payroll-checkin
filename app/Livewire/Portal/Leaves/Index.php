<?php

namespace App\Livewire\Portal\Leaves;

use App\Models\Leave;
use Livewire\Component;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Livewire\Traits\WithDataTable;

class Index extends Component
{
    use WithDataTable;


    //Create, Edit, Delete, View Post props
    public ?string $start_time = null;
    public ?string $end_time = null;
    public ?string $leave_reason = null;
    public ?string $leave_type = null;
    public ?int $manager_approval_status = 1;
    public ?string $manager_approval_reason = null;
    public ?int $supervisor_approval_status = 1;
    public ?string $supervisor_approval_reason = null;
    public ?int $leave_id = null;
    public ?string $user = null;
    public ?string $role = null;
    public ?Leave $leave = null;

    //Multiple Selection props
    public array $selectedLeaves = [];
    public bool $bulkDisabled = true;
    public bool $selectAll = false;
    public $bulk_approval_status = true;
    
    // Soft delete properties
    public $activeTab = 'active';
    public $selectedLeavesForDelete = [];
    public $selectAllForDelete = false;


    //Update & Store Rules
    protected array $rules = [
        'supervisor_approval_status' => 'sometimes',
        'supervisor_approval_reason' => 'sometimes',
        'manager_approval_status' => 'sometimes',
        'manager_approval_reason' => 'sometimes',
    ];

    public function mount()
    {
        $this->role = auth()->user()->getRoleNames()->first();
    }


    //Toggle the $bulkDisabled on or off based on the count of selected posts
    public function updatedselectAll($value)
    {
        if ($value) {
            $this->selectedLeaves = match ($this->role) {
                "supervisor" => Leave::search($this->query)->supervisor()->with('user')->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage)->pluck('id')->toArray(),
                "manager" => Leave::search($this->query)->manager()->with('user')->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage)->pluck('id')->toArray(),
                "admin" => Leave::search($this->query)->with('user')->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage)->pluck('id')->toArray(),
                "default" => [],
            };
            $this->updatedselectedLeaves();
        } else {
            $this->selectedLeaves = [];
            $this->updatedselectedLeaves();
        }
    }
    //Toggle the $bulkDisabled on or off based on the count of selected posts
    public function updatedselectedLeaves()
    {
        $this->bulkDisabled = count($this->selectedLeaves) < 2;
        $this->leave = null;
    }

    //Get & assign selected leave props
    public function initData($leave_id)
    {
        $leave = Leave::findOrFail($leave_id);

        $this->leave = $leave;
        $this->start_date = $leave->start_date->format('Y-m-d');
        $this->end_date = !empty($leave->end_date) ? $leave->end_date->format('Y-m-d') : '';
        $this->leave_reason = $leave->leave_reason;
        if ($this->role === "supervisor") {
            $this->supervisor_approval_status = $leave->supervisor_approval_status;
            $this->supervisor_approval_reason = $leave->supervisor_approval_reason;
        } else {
            $this->manager_approval_status = $leave->manager_approval_status;
            $this->manager_approval_reason = $leave->manager_approval_reason;
        }
        $this->leave_type = $leave->leaveType->name;
        $this->user = $leave->user->name;
        $this->selectedLeaves = [];
    }

    //Set Approval type
    public function initDataBulk($approval_type)
    {
        if ($approval_type == 'approve') {
            $this->role === "supervisor" ?
            $this->supervisor_approval_status = Leave::SUPERVISOR_APPROVAL_APPROVED :
                $this->manager_approval_status = Leave::MANAGER_APPROVAL_APPROVED;

            $this->bulk_approval_status = true;
        } else {
            $this->role === "supervisor" ?
            $this->supervisor_approval_status = Leave::SUPERVISOR_APPROVAL_REJECTED :
                $this->manager_approval_status = Leave::MANAGER_APPROVAL_REJECTED;
            $this->bulk_approval_status = false;
        }
    }

    //Bulk update
    public function bulkApproval()
    {
        // Check permission based on approval status
        if ($this->bulk_approval_status) {
            if (!Gate::allows('leave-bulkapproval')) {
                return abort(401);
            }
        } else {
            if (!Gate::allows('leave-bulkrejection')) {
                return abort(401);
            }
        }

        // Fetch records before updating for audit logging
        $leaves = Leave::whereIn('id', $this->selectedLeaves)->with('user')->get();
        
        // Capture old values for all records
        $affectedRecords = [];
        foreach ($leaves as $leave) {
            if ($this->role === "supervisor") {
                $affectedRecords[] = [
                    'id' => $leave->id,
                    'user_name' => $leave->user->name ?? 'User',
                    'date' => $leave->start_date->format('Y-m-d'),
                    'old_supervisor_approval_status' => $leave->supervisor_approval_status,
                    'old_supervisor_approval_reason' => $leave->supervisor_approval_reason,
                ];
            } else {
                $affectedRecords[] = [
                    'id' => $leave->id,
                    'user_name' => $leave->user->name ?? 'User',
                    'date' => $leave->start_date->format('Y-m-d'),
                    'old_manager_approval_status' => $leave->manager_approval_status,
                    'old_manager_approval_reason' => $leave->manager_approval_reason,
                ];
            }
        }

        // Perform bulk update
        if ($this->role === "supervisor") {
            Leave::whereIn('id', $this->selectedLeaves)->update([
                'supervisor_approval_status' => $this->supervisor_approval_status,
                'supervisor_approval_reason' => $this->supervisor_approval_reason,
            ]);
        } else {
            Leave::whereIn('id', $this->selectedLeaves)->update([
                'manager_approval_status' => $this->manager_approval_status,
                'manager_approval_reason' => $this->manager_approval_reason,
            ]);
        }

        // Create a single audit log entry for the bulk operation
        $actionType = $this->bulk_approval_status ? 'leave_approved' : 'leave_rejected';
        $user = auth()->user();
        
        $newValues = $this->role === "supervisor" 
            ? ['supervisor_approval_status' => $this->supervisor_approval_status, 'supervisor_approval_reason' => $this->supervisor_approval_reason]
            : ['manager_approval_status' => $this->manager_approval_status, 'manager_approval_reason' => $this->manager_approval_reason];
        
        auditLog(
            $user,
            $actionType,
            'web',
            $this->bulk_approval_status ? 'bulk_approved_leaves' : 'bulk_rejected_leaves',
            null, // No single model for bulk operations
            [], // Old values aggregated in metadata
            $newValues,
            [
                'translation_key' => $this->bulk_approval_status ? 'bulk_approved_leaves' : 'bulk_rejected_leaves',
                'translation_params' => ['count' => count($leaves)],
                'bulk_operation' => true,
                'operation_type' => $this->bulk_approval_status ? 'bulk_approval' : 'bulk_rejection',
                'affected_count' => count($leaves),
                'affected_ids' => $leaves->pluck('id')->toArray(),
                'affected_records' => $affectedRecords,
                'role' => $this->role,
                'new_values' => $newValues,
            ]
        );

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('leaves.leave_successfully_updated'), 'EditBulkLeaveModal');
    }

    public function update()
    {
        if (!Gate::allows('leave-update')) {
            return abort(401);
        }
        $this->validate();

        if ($this->role === "supervisor") {
            DB::transaction(function () {
                $this->leave->update([
                    'supervisor_approval_status' => $this->supervisor_approval_status,
                    'supervisor_approval_reason' => $this->supervisor_approval_reason,
                ]);
            });
        } else {
            DB::transaction(
                function () {
                    $this->leave->update([
                        'manager_approval_status' => $this->manager_approval_status,
                        'manager_approval_reason' => $this->manager_approval_reason,
                    ]);

                    if($this->manager_approval_status === Leave::MANAGER_APPROVAL_APPROVED){

                        $this->leave->user->decrement('remaining_leave_days', Carbon::parse($this->leave->start_date)->diffInDays(Carbon::parse( $this->leave->end_date)));
                    }
                }
            );
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('leaves.leave_successfully_updated'), 'EditLeaveModal');
    }
    public function delete()
    {
        if (!Gate::allows('leave-delete')) {
            return abort(401);
        }

        if (!empty($this->leave)) {
            $this->leave->delete(); // Already using soft delete
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('leaves.leave_successfully_moved_to_trash'), 'DeleteModal');
    }

    public function restore()
    {
        if (!Gate::allows('leave-restore')) {
            return abort(401);
        }

        $leave = Leave::withTrashed()->findOrFail($this->leave_id);
        $leave->restore();

        $this->closeModalAndFlashMessage(__('leaves.leave_successfully_restored'), 'RestoreModal');
    }

    public function forceDelete($leaveId = null)
    {
        if (!Gate::allows('leave-delete')) {
            return abort(401);
        }

        // If no leaveId provided, try to get it from selectedLeavesForDelete
        if (!$leaveId) {
            if (!empty($this->selectedLeavesForDelete) && is_array($this->selectedLeavesForDelete)) {
                $leaveId = $this->selectedLeavesForDelete[0] ?? null;
            } elseif ($this->leave_id) {
                $leaveId = $this->leave_id;
            } else {
                $this->showToast(__('leaves.no_leave_selected'), 'danger');
                return;
            }
        }

        $leave = Leave::withTrashed()->findOrFail($leaveId);
        $leave->forceDelete();

        // Clear selection after deletion
        if (in_array($leaveId, $this->selectedLeavesForDelete ?? [])) {
            $this->selectedLeavesForDelete = array_diff($this->selectedLeavesForDelete, [$leaveId]);
        }
        $this->leave_id = null;

        $this->closeModalAndFlashMessage(__('leaves.leave_permanently_deleted'), 'ForceDeleteModal');
    }

    public function bulkDelete()
    {
        if (!Gate::allows('leave-bulkdelete')) {
            return abort(401);
        }

        $targetIds = [];
        $operation = 'soft_delete';
        if (!empty($this->selectedLeaves)) {
            $targetIds = $this->selectedLeaves;
            $operation = 'soft_delete_active';
        } elseif (!empty($this->selectedLeavesForDelete)) {
            $targetIds = $this->selectedLeavesForDelete;
            $operation = 'soft_delete_deleted_tab';
        }

        $leaves = collect();
        $affectedRecords = [];
        if (!empty($targetIds)) {
            $leaves = Leave::withTrashed()->whereIn('id', $targetIds)->with('user')->get();
            $affectedRecords = $leaves->map(function ($leave) {
                return [
                    'id' => $leave->id,
                    'user_name' => $leave->user->name ?? 'User',
                    'date' => $leave->start_date ? $leave->start_date->format('Y-m-d') : null,
                    'supervisor_approval_status' => $leave->supervisor_approval_status,
                    'supervisor_approval_reason' => $leave->supervisor_approval_reason,
                    'manager_approval_status' => $leave->manager_approval_status,
                    'manager_approval_reason' => $leave->manager_approval_reason,
                ];
            })->toArray();
        }

        // Handle both active tab (selectedLeaves) and deleted tab (selectedLeavesForDelete)
        if (!empty($this->selectedLeaves)) {
            // Active tab - soft delete selected items
            Leave::whereIn('id', $this->selectedLeaves)->delete(); // Soft delete
            $this->selectedLeaves = [];
            $this->selectAll = false;
        } elseif (!empty($this->selectedLeavesForDelete)) {
            // Deleted tab - already handled by existing logic
            Leave::whereIn('id', $this->selectedLeavesForDelete)->delete(); // Soft delete
            $this->selectedLeavesForDelete = [];
        }

        if ($leaves->count() > 0) {
            auditLog(
                auth()->user(),
                'leave_bulk_deleted',
                'web',
                'bulk_deleted_leaves',
                null,
                [],
                [],
                [
                    'translation_key' => 'bulk_deleted_leaves',
                    'translation_params' => ['count' => $leaves->count()],
                    'bulk_operation' => true,
                    'operation_type' => $operation,
                    'affected_count' => $leaves->count(),
                    'affected_ids' => $leaves->pluck('id')->toArray(),
                    'affected_records' => $affectedRecords,
                ]
            );
        }

        $this->closeModalAndFlashMessage(__('leaves.selected_leaves_moved_to_trash'), 'BulkDeleteModal');
    }

    public function bulkRestore()
    {
        if (!Gate::allows('leave-bulkrestore')) {
            return abort(401);
        }

        $leaves = collect();
        $affectedRecords = [];
        if (!empty($this->selectedLeavesForDelete)) {
            $leaves = Leave::withTrashed()->whereIn('id', $this->selectedLeavesForDelete)->with('user')->get();
            $affectedRecords = $leaves->map(function ($leave) {
                return [
                    'id' => $leave->id,
                    'user_name' => $leave->user->name ?? 'User',
                    'date' => $leave->start_date ? $leave->start_date->format('Y-m-d') : null,
                    'supervisor_approval_status' => $leave->supervisor_approval_status,
                    'supervisor_approval_reason' => $leave->supervisor_approval_reason,
                    'manager_approval_status' => $leave->manager_approval_status,
                    'manager_approval_reason' => $leave->manager_approval_reason,
                ];
            })->toArray();
        }

        if (!empty($this->selectedLeavesForDelete)) {
            Leave::withTrashed()->whereIn('id', $this->selectedLeavesForDelete)->restore();
            $this->selectedLeavesForDelete = [];
        }

        if ($leaves->count() > 0) {
            auditLog(
                auth()->user(),
                'leave_bulk_restored',
                'web',
                'bulk_restored_leaves',
                null,
                [],
                [],
                [
                    'translation_key' => 'bulk_restored_leaves',
                    'translation_params' => ['count' => $leaves->count()],
                    'bulk_operation' => true,
                    'operation_type' => 'bulk_restore',
                    'affected_count' => $leaves->count(),
                    'affected_ids' => $leaves->pluck('id')->toArray(),
                    'affected_records' => $affectedRecords,
                ]
            );
        }

        $this->closeModalAndFlashMessage(__('leaves.selected_leaves_restored'), 'BulkRestoreModal');
    }

    public function bulkForceDelete()
    {
        if (!Gate::allows('leave-delete')) {
            return abort(401);
        }

        $leaves = collect();
        $affectedRecords = [];
        if (!empty($this->selectedLeavesForDelete)) {
            $leaves = Leave::withTrashed()->whereIn('id', $this->selectedLeavesForDelete)->with('user')->get();
            $affectedRecords = $leaves->map(function ($leave) {
                return [
                    'id' => $leave->id,
                    'user_name' => $leave->user->name ?? 'User',
                    'date' => $leave->start_date ? $leave->start_date->format('Y-m-d') : null,
                    'supervisor_approval_status' => $leave->supervisor_approval_status,
                    'supervisor_approval_reason' => $leave->supervisor_approval_reason,
                    'manager_approval_status' => $leave->manager_approval_status,
                    'manager_approval_reason' => $leave->manager_approval_reason,
                ];
            })->toArray();
        }

        if (!empty($this->selectedLeavesForDelete)) {
            Leave::withTrashed()->whereIn('id', $this->selectedLeavesForDelete)->forceDelete();
            $this->selectedLeavesForDelete = [];
        }

        if ($leaves->count() > 0) {
            auditLog(
                auth()->user(),
                'leave_bulk_force_deleted',
                'web',
                'bulk_force_deleted_leaves',
                null,
                [],
                [],
                [
                    'translation_key' => 'bulk_force_deleted_leaves',
                    'translation_params' => ['count' => $leaves->count()],
                    'bulk_operation' => true,
                    'operation_type' => 'bulk_force_delete',
                    'affected_count' => $leaves->count(),
                    'affected_ids' => $leaves->pluck('id')->toArray(),
                    'affected_records' => $affectedRecords,
                ]
            );
        }

        $this->closeModalAndFlashMessage(__('leaves.selected_leaves_permanently_deleted'), 'BulkForceDeleteModal');
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->selectedLeavesForDelete = [];
        $this->selectAllForDelete = false;
    }

    public function toggleSelectAllForDelete()
    {
        if ($this->selectAllForDelete) {
            $this->selectedLeavesForDelete = $this->getLeaves()->pluck('id')->toArray();
        } else {
            $this->selectedLeavesForDelete = [];
        }
    }

    public function toggleLeaveSelectionForDelete($leaveId)
    {
        if (in_array($leaveId, $this->selectedLeavesForDelete)) {
            $this->selectedLeavesForDelete = array_diff($this->selectedLeavesForDelete, [$leaveId]);
        } else {
            $this->selectedLeavesForDelete[] = $leaveId;
        }

        $this->selectAllForDelete = count($this->selectedLeavesForDelete) === $this->getLeaves()->count();
    }

    public function selectAllVisible()
    {
        $this->selectedLeaves = $this->getLeaves()->pluck('id')->toArray();
    }

    public function selectAllVisibleForDelete()
    {
        $this->selectedLeavesForDelete = $this->getLeaves()->pluck('id')->toArray();
    }

    public function selectAllLeaves()
    {
        $this->selectedLeaves = match ($this->role) {
            'supervisor' => Leave::search($this->query)->supervisor()->with(['user', 'company'])->whereNull('deleted_at')->pluck('id')->toArray(),
            'manager' => Leave::search($this->query)->manager()->with(['user', 'company'])->whereNull('deleted_at')->pluck('id')->toArray(),
            'admin' => Leave::search($this->query)->with(['user', 'company'])->whereNull('deleted_at')->pluck('id')->toArray(),
            default => [],
        };
        $this->updatedselectedLeaves();
    }

    public function selectAllDeletedLeaves()
    {
        $this->selectedLeavesForDelete = match ($this->role) {
            'supervisor' => Leave::search($this->query)->supervisor()->with(['user', 'company'])->withTrashed()->whereNotNull('deleted_at')->pluck('id')->toArray(),
            'manager' => Leave::search($this->query)->manager()->with(['user', 'company'])->withTrashed()->whereNotNull('deleted_at')->pluck('id')->toArray(),
            'admin' => Leave::search($this->query)->with(['user', 'company'])->withTrashed()->whereNotNull('deleted_at')->pluck('id')->toArray(),
            default => [],
        };
    }

    private function getLeaves()
    {
        $query = Leave::search($this->query)->with(['user']);

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

    public function export()
    {
        return (new LeaveExport($this->query))->download('leaves-' . Str::random(5) . '.xlsx');
    }

    public function clearFields()
    {

        $this->reset([
            'leave',
            'leave_type',
            'user',
            'start_time',
            'end_time',
            'manager_approval_status', 'manager_approval_reason',
            'supervisor_approval_status', 'supervisor_approval_reason',
            'leave_reason',
            'selectedLeaves',
            'bulkDisabled',
            'selectAll'
        ]);
    }

    public function render()
    {
        if (!Gate::allows('leave-read')) {
            return abort(401);
        }

        $leaves = $this->getLeaves();

        // Get counts for active leave records (non-deleted)
        $active_leaves = match($this->role){
            "supervisor" => Leave::search($this->query)->supervisor()->whereNull('deleted_at')->count(),
            "manager" => Leave::search($this->query)->manager()->whereNull('deleted_at')->count(),
            "admin" => Leave::search($this->query)->whereNull('deleted_at')->count(),
           default => 0,
        };

        // Get counts for deleted leave records
        $deleted_leaves = match($this->role){
            "supervisor" => Leave::search($this->query)->supervisor()->withTrashed()->whereNotNull('deleted_at')->count(),
            "manager" => Leave::search($this->query)->manager()->withTrashed()->whereNotNull('deleted_at')->count(),
            "admin" => Leave::search($this->query)->withTrashed()->whereNotNull('deleted_at')->count(),
           default => 0,
        };

        // Get approval status counts for active records only
        $pending_leaves_count = match($this->role){
            "supervisor" => Leave::supervisor()->whereNull('deleted_at')->where('supervisor_approval_status', Leave::SUPERVISOR_APPROVAL_PENDING)->count(),
            "manager" => Leave::manager()->whereNull('deleted_at')->where('supervisor_approval_status', Leave::SUPERVISOR_APPROVAL_PENDING)->count(),
            "admin" => Leave::whereNull('deleted_at')->where('supervisor_approval_status', Leave::SUPERVISOR_APPROVAL_PENDING)->count(),
           default => 0,
        };
        $approved_leaves_count = match($this->role){
            "supervisor" => Leave::supervisor()->whereNull('deleted_at')->where('supervisor_approval_status', Leave::SUPERVISOR_APPROVAL_APPROVED)->count(),
            "manager" => Leave::manager()->whereNull('deleted_at')->where('supervisor_approval_status', Leave::SUPERVISOR_APPROVAL_APPROVED)->count(),
            "admin" => Leave::whereNull('deleted_at')->where('supervisor_approval_status', Leave::SUPERVISOR_APPROVAL_APPROVED)->count(),
           default => 0,
        };
        $rejected_leaves_count = match($this->role){
            "supervisor" => Leave::supervisor()->whereNull('deleted_at')->where('supervisor_approval_status', Leave::MANAGER_APPROVAL_REJECTED)->count(),
            "manager" => Leave::manager()->whereNull('deleted_at')->where('supervisor_approval_status', Leave::MANAGER_APPROVAL_REJECTED)->count(),
            "admin" => Leave::whereNull('deleted_at')->where('supervisor_approval_status', Leave::MANAGER_APPROVAL_REJECTED)->count(),
           default => 0,
        };

        return view('livewire.portal.leaves.index', [
            'leaves' => $leaves,
            'leaves_count' => $active_leaves, // Legacy for backward compatibility
            'active_leaves' => $active_leaves,
            'deleted_leaves' => $deleted_leaves,
            'pending_leaves_count' => $pending_leaves_count,
            'approved_leaves_count' => $approved_leaves_count,
            'rejected_leaves_count' => $rejected_leaves_count,
        ])->layout('components.layouts.dashboard');
    }
  
}
