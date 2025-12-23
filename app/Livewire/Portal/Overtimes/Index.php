<?php

namespace App\Livewire\Portal\Overtimes;

use Livewire\Component;
use App\Models\Overtime;
use Illuminate\Support\Str;
use Livewire\WithPagination;
use Illuminate\Support\Carbon;
use App\Exports\OvertimeExport;
use App\Livewire\Traits\WithDataTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class Index extends Component
{
    use WithDataTable;

    public ?array $selected = [];

    //Create, Edit, Delete, View Post props
    public ?string $start_time = null;
    public ?string $end_time = null;
    public ?string $reason = null;
    public ?int $approval_status = 1;
    public ?string $approval_reason = null;
    public ?int $overtime_id = null;
    public ?string $user = null;
    public ?Overtime $overtime = null;
    public ?string $role = null;

    //Multiple Selection props
    public array $selectedOvertimes = [];
    public bool $bulkDisabled = true;
    public bool $selectAll = false;
    public $bulk_approval_status = true;
    
    // Soft delete properties
    public $activeTab = 'active';
    public $selectedOvertimesForDelete = [];
    public $selectAllForDelete = false;


    public function mount()
    {
        $this->role = auth()->user()->getRoleNames()->first();
    }

    public function updatedselectAll($value)
    {
        if ($value) {
            $this->selectedOvertimes = match($this->role){
                "supervisor" => Overtime::search($this->query)->supervisor()->with(['user'])->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage)->pluck('id')->toArray(),
                "manager" => Overtime::search($this->query)->manager()->with(['user'])->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage)->pluck('id')->toArray(),
                "admin" => Overtime::search($this->query)->with(['user'])->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage)->pluck('id')->toArray(),
                "default"=> [],
            };
            $this->updatedselectedOvertimes();
        } else {
            $this->selectedOvertimes = [];
            $this->updatedselectedOvertimes();
        }
    }
    //Toggle the $bulkDisabled on or off based on the count of selected posts
    public function updatedselectedOvertimes()
    {
        $this->bulkDisabled = count($this->selectedOvertimes) < 2;
        $this->overtime = null;
    }

    //Get & assign selected overtime props
    public function initData($overtime_id)
    {
        $overtime = Overtime::findOrFail($overtime_id);

        $this->overtime = $overtime;
        $this->start_time = $overtime->start_time->format('Y-m-d\TH:i');
        $this->end_time = !empty($overtime->end_time) ? $overtime->end_time->format('Y-m-d\TH:i') : '';
        $this->reason = $overtime->reason;
        $this->approval_status = $overtime->approval_status;
        $this->approval_reason = $overtime->approval_reason;
        $this->overtime_id = $overtime->id;
        $this->user = $overtime->user->name;
        $this->selectedOvertimes = [];
    }

    //Set Approval type
    public function initDataBulk($approval_type)
    {
        if ($approval_type == 'approve') {
            $this->approval_status = Overtime::APPROVAL_STATUS_APPROVED;
            $this->bulk_approval_status = true;
        } else {
            $this->approval_status = Overtime::APPROVAL_STATUS_REJECTED;
            $this->bulk_approval_status = false;
        }
    }

    //Bulk update
    public function bulkApproval()
    {
        // Check permission based on approval status
        if ($this->bulk_approval_status) {
            if (!Gate::allows('overtime-bulkapproval')) {
                return abort(401);
            }
        } else {
            if (!Gate::allows('overtime-bulkrejection')) {
                return abort(401);
            }
        }

        $this->validate([
            'approval_status' => 'required',
            'approval_reason' => 'required',
        ]);

        // Fetch records before updating for audit logging
        $overtimes = Overtime::whereIn('id', $this->selectedOvertimes)->with('user')->get();
        
        // Capture old values for all records
        $affectedRecords = [];
        foreach ($overtimes as $overtime) {
            $affectedRecords[] = [
                'id' => $overtime->id,
                'user_name' => $overtime->user->name ?? 'User',
                'date' => $overtime->overtime_date,
                'old_approval_status' => $overtime->approval_status,
                'old_approval_reason' => $overtime->approval_reason,
            ];
        }

        // Perform bulk update
        Overtime::whereIn('id', $this->selectedOvertimes)->update([
            'approval_status' => $this->approval_status,
            'approval_reason' => $this->approval_reason,
        ]);

        // Create a single audit log entry for the bulk operation
        $actionType = $this->bulk_approval_status ? 'overtime_approved' : 'overtime_rejected';
        $user = auth()->user();
        
        auditLog(
            $user,
            $actionType,
            'web',
            $this->bulk_approval_status 
                ? __('audit_logs.bulk_approved_overtimes', ['count' => count($overtimes)])
                : __('audit_logs.bulk_rejected_overtimes', ['count' => count($overtimes)]),
            null, // No single model for bulk operations
            [], // Old values aggregated in metadata
            ['approval_status' => $this->approval_status, 'approval_reason' => $this->approval_reason],
            [
                'bulk_operation' => true,
                'operation_type' => $this->bulk_approval_status ? 'bulk_approval' : 'bulk_rejection',
                'affected_count' => count($overtimes),
                'affected_ids' => $overtimes->pluck('id')->toArray(),
                'affected_records' => $affectedRecords,
                'new_approval_status' => $this->approval_status,
                'new_approval_reason' => $this->approval_reason,
            ]
        );

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('overtime.overtime_updated_successfully'), 'EditBulkOvertimeModal');
    }

    public function update()
    {
        if (!Gate::allows('overtime-update')) {
            return abort(401);
        }

        $this->validate([
            'approval_status' => 'required',
            'approval_reason' => 'required',
        ]);

        $this->overtime->update([
            'approval_status' => $this->approval_status,
            'approval_reason' => $this->approval_reason,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'minutes_worked' => Carbon::parse($this->start_time)->diffInMinutes(Carbon::parse($this->end_time)),
            'reason' => $this->reason,
        ]);

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('overtime.overtime_updated_successfully'), 'EditOvertimeModal');
    }
    public function delete()
    {
        if (!Gate::allows('overtime-delete')) {
            return abort(401);
        }

        $this->overtime->delete(); // Already using soft delete

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('overtime.overtime_deleted_successfully'), 'DeleteModal');
    }

    public function restore()
    {
        if (!Gate::allows('overtime-restore')) {
            return abort(401);
        }

        $overtime = Overtime::withTrashed()->findOrFail($this->overtime_id);
        $overtime->restore();

        $this->closeModalAndFlashMessage(__('overtime.overtime_restored_successfully'), 'RestoreModal');
    }

    public function forceDelete($overtimeId = null)
    {
        if (!Gate::allows('overtime-delete')) {
            return abort(401);
        }

        // If no overtimeId provided, try to get it from selectedOvertimesForDelete
        if (!$overtimeId) {
            if (!empty($this->selectedOvertimesForDelete) && is_array($this->selectedOvertimesForDelete)) {
                $overtimeId = $this->selectedOvertimesForDelete[0] ?? null;
            } elseif ($this->overtime_id) {
                $overtimeId = $this->overtime_id;
            } else {
                $this->showToast(__('overtime.no_overtime_selected'), 'danger');
                return;
            }
        }

        $overtime = Overtime::withTrashed()->findOrFail($overtimeId);
        $overtime->forceDelete();

        // Clear selection after deletion
        if (in_array($overtimeId, $this->selectedOvertimesForDelete ?? [])) {
            $this->selectedOvertimesForDelete = array_diff($this->selectedOvertimesForDelete, [$overtimeId]);
        }
        $this->overtime_id = null;

        $this->closeModalAndFlashMessage(__('overtime.overtime_permanently_deleted'), 'ForceDeleteModal');
    }

    public function bulkDelete()
    {
        if (!Gate::allows('overtime-bulkdelete')) {
            return abort(401);
        }

        $targetIds = [];
        $operation = 'soft_delete';
        if (!empty($this->selectedOvertimes)) {
            $targetIds = $this->selectedOvertimes;
            $operation = 'soft_delete_active';
        } elseif (!empty($this->selectedOvertimesForDelete)) {
            $targetIds = $this->selectedOvertimesForDelete;
            $operation = 'soft_delete_deleted_tab';
        }

        $overtimes = collect();
        $affectedRecords = [];
        if (!empty($targetIds)) {
            $overtimes = Overtime::withTrashed()->whereIn('id', $targetIds)->with('user')->get();
            $affectedRecords = $overtimes->map(function ($overtime) {
                return [
                    'id' => $overtime->id,
                    'user_name' => $overtime->user->name ?? 'User',
                    'date' => $overtime->overtime_date,
                    'approval_status' => $overtime->approval_status,
                    'approval_reason' => $overtime->approval_reason,
                ];
            })->toArray();
        }

        // Handle both active tab (selectedOvertimes) and deleted tab (selectedOvertimesForDelete)
        if (!empty($this->selectedOvertimes)) {
            // Active tab - soft delete selected items
            Overtime::whereIn('id', $this->selectedOvertimes)->delete(); // Soft delete
            $this->selectedOvertimes = [];
            $this->selectAll = false;
        } elseif (!empty($this->selectedOvertimesForDelete)) {
            // Deleted tab - already handled by existing logic
            Overtime::whereIn('id', $this->selectedOvertimesForDelete)->delete(); // Soft delete
            $this->selectedOvertimesForDelete = [];
        }

        if ($overtimes->count() > 0) {
            auditLog(
                auth()->user(),
                'overtime_bulk_deleted',
                'web',
                __('audit_logs.bulk_deleted_overtimes', ['count' => $overtimes->count()]),
                null,
                [],
                [],
                [
                    'bulk_operation' => true,
                    'operation_type' => $operation,
                    'affected_count' => $overtimes->count(),
                    'affected_ids' => $overtimes->pluck('id')->toArray(),
                    'affected_records' => $affectedRecords,
                ]
            );
        }

        $this->closeModalAndFlashMessage(__('overtime.selected_overtimes_moved_to_trash'), 'BulkDeleteModal');
    }

    public function bulkRestore()
    {
        if (!Gate::allows('overtime-bulkrestore')) {
            return abort(401);
        }

        $overtimes = collect();
        $affectedRecords = [];
        if (!empty($this->selectedOvertimesForDelete)) {
            $overtimes = Overtime::withTrashed()->whereIn('id', $this->selectedOvertimesForDelete)->with('user')->get();
            $affectedRecords = $overtimes->map(function ($overtime) {
                return [
                    'id' => $overtime->id,
                    'user_name' => $overtime->user->name ?? 'User',
                    'date' => $overtime->overtime_date,
                    'approval_status' => $overtime->approval_status,
                    'approval_reason' => $overtime->approval_reason,
                ];
            })->toArray();
        }

        if (!empty($this->selectedOvertimesForDelete)) {
            Overtime::withTrashed()->whereIn('id', $this->selectedOvertimesForDelete)->restore();
            $this->selectedOvertimesForDelete = [];
        }

        if ($overtimes->count() > 0) {
            auditLog(
                auth()->user(),
                'overtime_bulk_restored',
                'web',
                __('audit_logs.bulk_restored_overtimes', ['count' => $overtimes->count()]),
                null,
                [],
                [],
                [
                    'bulk_operation' => true,
                    'operation_type' => 'bulk_restore',
                    'affected_count' => $overtimes->count(),
                    'affected_ids' => $overtimes->pluck('id')->toArray(),
                    'affected_records' => $affectedRecords,
                ]
            );
        }

        $this->closeModalAndFlashMessage(__('overtime.selected_overtimes_restored'), 'BulkRestoreModal');
    }

    public function bulkForceDelete()
    {
        if (!Gate::allows('overtime-delete')) {
            return abort(401);
        }

        $overtimes = collect();
        $affectedRecords = [];
        if (!empty($this->selectedOvertimesForDelete)) {
            $overtimes = Overtime::withTrashed()->whereIn('id', $this->selectedOvertimesForDelete)->with('user')->get();
            $affectedRecords = $overtimes->map(function ($overtime) {
                return [
                    'id' => $overtime->id,
                    'user_name' => $overtime->user->name ?? 'User',
                    'date' => $overtime->overtime_date,
                    'approval_status' => $overtime->approval_status,
                    'approval_reason' => $overtime->approval_reason,
                ];
            })->toArray();
        }

        if (!empty($this->selectedOvertimesForDelete)) {
            Overtime::withTrashed()->whereIn('id', $this->selectedOvertimesForDelete)->forceDelete();
            $this->selectedOvertimesForDelete = [];
        }

        if ($overtimes->count() > 0) {
            auditLog(
                auth()->user(),
                'overtime_bulk_force_deleted',
                'web',
                __('audit_logs.bulk_force_deleted_overtimes', ['count' => $overtimes->count()]),
                null,
                [],
                [],
                [
                    'bulk_operation' => true,
                    'operation_type' => 'bulk_force_delete',
                    'affected_count' => $overtimes->count(),
                    'affected_ids' => $overtimes->pluck('id')->toArray(),
                    'affected_records' => $affectedRecords,
                ]
            );
        }

        $this->closeModalAndFlashMessage(__('overtime.selected_overtimes_permanently_deleted'), 'BulkForceDeleteModal');
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->selectedOvertimesForDelete = [];
        $this->selectAllForDelete = false;
    }

    public function toggleSelectAllForDelete()
    {
        if ($this->selectAllForDelete) {
            $this->selectedOvertimesForDelete = $this->getOvertimes()->pluck('id')->toArray();
        } else {
            $this->selectedOvertimesForDelete = [];
        }
    }

    public function toggleOvertimeSelectionForDelete($overtimeId)
    {
        if (in_array($overtimeId, $this->selectedOvertimesForDelete)) {
            $this->selectedOvertimesForDelete = array_diff($this->selectedOvertimesForDelete, [$overtimeId]);
        } else {
            $this->selectedOvertimesForDelete[] = $overtimeId;
        }

        $this->selectAllForDelete = count($this->selectedOvertimesForDelete) === $this->getOvertimes()->count();
    }

    public function selectAllVisible()
    {
        $this->selectedOvertimes = $this->getOvertimes()->pluck('id')->toArray();
    }

    public function selectAllVisibleForDelete()
    {
        $this->selectedOvertimesForDelete = $this->getOvertimes()->pluck('id')->toArray();
    }

    public function selectAllDeletedOvertimes()
    {
        $this->selectedOvertimesForDelete = match ($this->role) {
            'supervisor' => Overtime::search($this->query)->supervisor()->with(['user'])->withTrashed()->whereNotNull('deleted_at')->pluck('id')->toArray(),
            'manager' => Overtime::search($this->query)->manager()->with(['user'])->withTrashed()->whereNotNull('deleted_at')->pluck('id')->toArray(),
            'admin' => Overtime::search($this->query)->with(['user'])->withTrashed()->whereNotNull('deleted_at')->pluck('id')->toArray(),
            default => [],
        };
    }

    public function selectAllOvertimes()
    {
        $query = Overtime::search($this->query)->with(['user']);

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

        $this->selectedOvertimes = $query->pluck('id')->toArray();
    }

    private function getOvertimes()
    {
        $query = Overtime::search($this->query)->with(['user']);

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
        auditLog(
            auth()->user(),
            'overtime_exported',
            'web',
            __('audit_logs.exported_entities', ['entities' => 'overtime'])
        );
        return (new OvertimeExport($this->query))->download('Overtime-' . Str::random(5) . '.xlsx');
    }

    public function clearFields()
    {
        $this->reset([
            'overtime',
            'overtime_id',
            'user',
            'start_time',
            'end_time',
            'reason',
            'approval_status',
            'approval_reason',
            'selectedOvertimes',
            'bulkDisabled',
            'selectAll'
        ]);
    }

    public function render()
    {
        if (!Gate::allows('overtime-read')) {
            return abort(401);
        }

        $overtimes = $this->getOvertimes();

        // Get counts for active overtime records (non-deleted)
        $active_overtimes = match($this->role){
            "supervisor" => Overtime::search($this->query)->supervisor()->whereNull('deleted_at')->count(),
            "manager" => Overtime::search($this->query)->manager()->whereNull('deleted_at')->count(),
            "admin" => Overtime::search($this->query)->whereNull('deleted_at')->count(),
           default => 0,
        };

        // Get counts for deleted overtime records
        $deleted_overtimes = match($this->role){
            "supervisor" => Overtime::search($this->query)->supervisor()->withTrashed()->whereNotNull('deleted_at')->count(),
            "manager" => Overtime::search($this->query)->manager()->withTrashed()->whereNotNull('deleted_at')->count(),
            "admin" => Overtime::search($this->query)->withTrashed()->whereNotNull('deleted_at')->count(),
           default => 0,
        };

        // Get approval status counts for active records only
        $pending_overtimes_count = match($this->role){
            "supervisor" => Overtime::supervisor()->whereNull('deleted_at')->where('approval_status', Overtime::APPROVAL_STATUS_PENDING)->count(),
            "manager" => Overtime::manager()->whereNull('deleted_at')->where('approval_status', Overtime::APPROVAL_STATUS_PENDING)->count(),
            "admin" => Overtime::whereNull('deleted_at')->where('approval_status', Overtime::APPROVAL_STATUS_PENDING)->count(),
           default => 0,
        };
        $approved_overtimes_count = match($this->role){ 
            "supervisor" => Overtime::supervisor()->whereNull('deleted_at')->where('approval_status', Overtime::APPROVAL_STATUS_APPROVED)->count(),
            "manager" => Overtime::manager()->whereNull('deleted_at')->where('approval_status', Overtime::APPROVAL_STATUS_APPROVED)->count(),
            "admin" => Overtime::whereNull('deleted_at')->where('approval_status', Overtime::APPROVAL_STATUS_APPROVED)->count(),
           default => 0,
        };
        $rejected_overtimes_count = match($this->role){ 
            "supervisor" => Overtime::supervisor()->whereNull('deleted_at')->where('approval_status', Overtime::APPROVAL_STATUS_REJECTED)->count(),
            "manager" => Overtime::manager()->whereNull('deleted_at')->where('approval_status', Overtime::APPROVAL_STATUS_REJECTED)->count(),
            "admin" => Overtime::whereNull('deleted_at')->where('approval_status', Overtime::APPROVAL_STATUS_REJECTED)->count(),
           default => 0,
        };

        return view('livewire.portal.overtimes.index', [
            'overtimes' => $overtimes,
            'overtimes_count' => $active_overtimes, // Legacy for backward compatibility
            'active_overtimes' => $active_overtimes,
            'deleted_overtimes' => $deleted_overtimes,
            'pending_overtimes_count' => $pending_overtimes_count,
            'approved_overtimes_count' => $approved_overtimes_count,
            'rejected_overtimes_count' => $rejected_overtimes_count,
        ])->layout('components.layouts.dashboard');
    }
}
