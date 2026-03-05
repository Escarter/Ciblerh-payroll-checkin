<?php

namespace App\Livewire\Employee\Leaves;

use App\Models\Leave;
use App\Models\SupervisorDepartment;
use App\Mail\LeaveRequestSubmittedNotification;
use Livewire\Component;
use App\Models\LeaveType;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use App\Livewire\Traits\WithDataTable;
use Carbon\Carbon;

class Index extends Component
{
    use WithDataTable;

    //Create, Edit, Delete, View Post props
    public ?array $selectedLeaves = [];
    public bool $selectAll = false;

    // Soft delete properties
    public $activeTab = 'active';
    public $selectedLeavesForDelete = [];
    public $selectAllForDelete = false;

    // Reactive count properties
    public $activeLeavesCount = 0;
    public $deletedLeavesCount = 0;
    public  $start_date;
    public  $end_date;
    public  $leave_type_id;
    public  $types;
    public $leave_reason;
    public $attachment;
    public $interval;
    public ?Leave $leave = null;
    public $company;
    public $department;
    public $service;


    public function updatedEndDate($value)
    {
        if (!empty($value) && !empty($this->start_date)) {
            $start = Carbon::parse($this->start_date);
            $end = Carbon::parse($this->end_date);
            $this->interval = $start->lte($end)
                ? __('employees.selected_leave_days') . '<strong>' . ($start->diffInDays($end) + 1) . '</strong>' . __('employees.days')
                : __('employees.start_date_before_end');
        }
    }
    public function updatedStartDate($value)
    {
        if (!empty($value) && !empty($this->end_date)) {
            $start = Carbon::parse($this->start_date);
            $end = Carbon::parse($this->end_date);
            $this->interval = $start->lte($end)
                ? __('employees.selected_leave_days') . '<strong>' . ($start->diffInDays($end) + 1) . '</strong>' . __('employees.days')
                : __('employees.start_date_before_end');
        }
    }

    public function mount()
    {
        $this->types = LeaveType::select('name','id')->get();
        $this->company = auth()->user()->company;
        $this->department = auth()->user()->department;
        $this->service = auth()->user()->service;

        // Initialize counts
        $this->updateCounts();
    }
    
    public function store()
    {
        if (!Gate::allows('leave-create')) {
            return abort(401);
        }

        $this->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'leave_type_id' => 'required',
            'leave_reason' => 'required',
            'attachment' => 'nullable|file|mimes:png,jpg,jpeg,pdf,doc,docx|max:5120',
        ]);

        if (empty($this->company)) {
            $this->addError('company', __('employees.not_associated_with_company'));
            return;
        }
        if (empty($this->department)) {
            $this->addError('department', __('employees.not_associated_with_department'));
            return;
        }

        $start = Carbon::parse($this->start_date);
        $end = Carbon::parse($this->end_date);
        $requestedDays = $start->diffInDays($end) + 1;
        $user = auth()->user();
        if (($user->remaining_leave_days ?? 0) < $requestedDays) {
            $this->addError('end_date', __('leaves.insufficient_leave_balance', [
                'balance' => $user->remaining_leave_days ?? 0,
                'requested' => $requestedDays,
            ]));
            return;
        }

        $attachmentPath = null;
        if ($this->attachment) {
            $attachmentPath = $this->attachment->storePublicly('leaves', 'attachments');
        }

        $leave = $user->leaves()->create([
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'author_id' => $user->author_id ?? null,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'leave_type_id' => $this->leave_type_id,
            'leave_reason' => $this->leave_reason,
            'attachment_path' => $attachmentPath,
        ]);

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.leave_request_submitted'), 'CreateLeaveModal');
    }
    private function notifySupervisors(Leave $leave): void
    {
        try {
            $departmentId = auth()->user()->department_id;
            $supervisorDepartments = SupervisorDepartment::where('department_id', $departmentId)->with('supervisor')->get();
            foreach ($supervisorDepartments as $supDept) {
                $supervisor = $supDept->supervisor;
                if ($supervisor && $supervisor->email) {
                    Mail::to($supervisor->email)->send(new LeaveRequestSubmittedNotification(
                        $leave,
                        auth()->user(),
                        $supervisor
                    ));
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send leave notification to supervisors', [
                'leave_id' => $leave->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    //Get & assign selected absence props
    public function initData($leave_id)
    {
        $leave = Leave::findOrFail($leave_id);

        $this->leave = $leave;
        $this->start_date = $leave->start_date->format('Y-m-d');
        $this->end_date = $leave->end_date->format('Y-m-d');
        $this->leave_type_id = $leave->leave_type_id;
        $this->leave_reason = $leave->leave_reason;
    }

    public function update()
    {
        if (!Gate::allows('leave-update')) {
            return abort(401);
        }

        $this->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'leave_type_id' => 'required',
            'leave_reason' => 'required',
        ]);

        $this->leave->update([
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'leave_type_id' => $this->leave_type_id,
            'leave_reason' => $this->leave_reason,
            'supervisor_approval_status' => $this->end_date != $this->leave->end_date ? Leave::SUPERVISOR_APPROVAL_PENDING :  $this->leave->supervisor_approval_status,
            'manager_approval_status' => $this->end_date != $this->leave->end_date ? Leave::MANAGER_APPROVAL_PENDING :  $this->leave->manager_approval_status,
        ]);

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.leave_request_updated'), 'EditLeaveModal');
    }
    public function delete()
    {
        if (!Gate::allows('leave-delete')) {
            return abort(401);
        }

        if (!empty($this->leave)) {
            auth()->user()->leaves()->findOrFail($this->leave->id)->delete();
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.leave_deleted'), 'DeleteModal');

        // Update counts
        $this->updateCounts();
    }

    public function bulkDelete()
    {
        if (!Gate::allows('leave-bulkdelete')) {
            return abort(401);
        }

        if (!empty($this->selectedLeaves)) {
            $leaves = Leave::whereIn('id', $this->selectedLeaves)
                ->where('user_id', auth()->user()->id)
                ->get();

            $affectedRecords = $leaves->map(function ($leave) {
                return [
                    'id' => $leave->id,
                    'start_date' => $leave->start_date ? $leave->start_date->format('Y-m-d') : null,
                    'end_date' => $leave->end_date ? $leave->end_date->format('Y-m-d') : null,
                ];
            })->toArray();

            Leave::whereIn('id', $this->selectedLeaves)
                ->where('user_id', auth()->user()->id)
                ->delete();

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
                        'operation_type' => 'soft_delete',
                        'affected_count' => $leaves->count(),
                        'affected_ids' => $leaves->pluck('id')->toArray(),
                        'affected_records' => $affectedRecords,
                    ]
                );
            }

            $this->selectedLeaves = [];
            $this->selectAll = false;

            $this->closeModalAndFlashMessage(__('employees.selected_leaves_deleted'), 'BulkDeleteModal');

            // Update counts
            $this->updateCounts();
        }
    }

    public function restore($leaveId)
    {
        if (!Gate::allows('leave-restore')) {
            return abort(401);
        }

        $leave = Leave::withTrashed()->findOrFail($leaveId);

        // Check if this leave belongs to the current user
        if ($leave->user_id !== auth()->id()) {
            return abort(403);
        }

        $leave->restore();

        $this->closeModalAndFlashMessage(__('employees.leave_restored'), 'RestoreModal');

        // Update counts
        $this->updateCounts();
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
            } else {
                $this->showToast(__('employees.no_leave_selected'), 'danger');
                return;
            }
        }

        $leave = Leave::withTrashed()->findOrFail($leaveId);

        // Check if this leave belongs to the current user
        if ($leave->user_id !== auth()->id()) {
            return abort(403);
        }

        $leave->forceDelete();

        // Clear selection after deletion
        if (in_array($leaveId, $this->selectedLeavesForDelete ?? [])) {
            $this->selectedLeavesForDelete = array_diff($this->selectedLeavesForDelete, [$leaveId]);
        }

        $this->closeModalAndFlashMessage(__('employees.leave_permanently_deleted'), 'ForceDeleteModal');

        // Update counts
        $this->updateCounts();
    }

    public function bulkRestore()
    {
        if (!Gate::allows('leave-bulkrestore')) {
            return abort(401);
        }

        if (!empty($this->selectedLeavesForDelete)) {
            $leaves = Leave::withTrashed()
                ->whereIn('id', $this->selectedLeavesForDelete)
                ->where('user_id', auth()->id())
                ->get();

            $affectedRecords = $leaves->map(function ($leave) {
                return [
                    'id' => $leave->id,
                    'start_date' => $leave->start_date ? $leave->start_date->format('Y-m-d') : null,
                    'end_date' => $leave->end_date ? $leave->end_date->format('Y-m-d') : null,
                ];
            })->toArray();

            Leave::withTrashed()
                ->whereIn('id', $this->selectedLeavesForDelete)
                ->where('user_id', auth()->id())
                ->restore();

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

            $this->selectedLeavesForDelete = [];

            $this->closeModalAndFlashMessage(__('employees.selected_leaves_restored'), 'BulkRestoreModal');

            // Update counts
            $this->updateCounts();
        }
    }

    public function bulkForceDelete()
    {
        if (!Gate::allows('leave-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedLeavesForDelete)) {
            $leaves = Leave::withTrashed()
                ->whereIn('id', $this->selectedLeavesForDelete)
                ->where('user_id', auth()->id())
                ->get();

            $affectedRecords = $leaves->map(function ($leave) {
                return [
                    'id' => $leave->id,
                    'start_date' => $leave->start_date ? $leave->start_date->format('Y-m-d') : null,
                    'end_date' => $leave->end_date ? $leave->end_date->format('Y-m-d') : null,
                ];
            })->toArray();

            Leave::withTrashed()
                ->whereIn('id', $this->selectedLeavesForDelete)
                ->where('user_id', auth()->id())
                ->forceDelete();

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

            $this->selectedLeavesForDelete = [];

            $this->closeModalAndFlashMessage(__('employees.selected_leaves_permanently_deleted'), 'BulkForceDeleteModal');

            // Update counts
            $this->updateCounts();
        }
    }

    //Toggle the $selectAll on or off based on the count of selected posts
    public function updatedselectAll($value)
    {
        if ($value) {
            $this->selectedLeaves = $this->getLeaves()->pluck('id')->toArray();
        } else {
            $this->selectedLeaves = [];
        }
    }

    //Toggle the $selectAll on or off based on the count of selected posts
    public function updatedselectedLeaves()
    {
        // This method can be used for additional logic if needed
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
        $this->selectedLeaves = auth()->user()->leaves()->whereNull('deleted_at')->pluck('id')->toArray();
    }

    public function selectAllDeletedLeaves()
    {
        $this->selectedLeavesForDelete = auth()->user()->leaves()->withTrashed()->whereNotNull('deleted_at')->pluck('id')->toArray();
    }

    private function updateCounts()
    {
        $this->activeLeavesCount = Leave::where('user_id', auth()->user()->id)->whereNull('deleted_at')->count();
        $this->deletedLeavesCount = Leave::where('user_id', auth()->user()->id)->withTrashed()->whereNotNull('deleted_at')->count();
    }

    private function getLeaves()
    {
        $query = Leave::search($this->query)->where('user_id', auth()->user()->id);

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
            'leave',
            'start_date',
            'end_date',
            'leave_type_id',
            'leave_reason',
            'interval',
            'selectedLeaves',
            'selectAll',
        ]);
    }

    public function render()
    {
        if (!Gate::allows('leave-read')) {
            return abort(401);
        }

        $leaves = $this->getLeaves();

        // Get counts from all leaves, not just current page
        $allLeaves = Leave::where('user_id', auth()->user()->id);
        $pending_leave = $allLeaves->where('supervisor_approval_status', Leave::SUPERVISOR_APPROVAL_PENDING)->where('manager_approval_status', Leave::MANAGER_APPROVAL_PENDING)->whereNull('deleted_at')->count();
        $approved_leave = $allLeaves->where('supervisor_approval_status', Leave::SUPERVISOR_APPROVAL_APPROVED)->where('manager_approval_status', Leave::MANAGER_APPROVAL_APPROVED)->whereNull('deleted_at')->count();
        $used_leave_days = auth()->user()->used_leave_days;
        $rejected_leave = $allLeaves->where('supervisor_approval_status', Leave::SUPERVISOR_APPROVAL_REJECTED)->where('manager_approval_status', Leave::MANAGER_APPROVAL_REJECTED)->whereNull('deleted_at')->count();

        return view('livewire.employee.leaves.index', compact('leaves', 'pending_leave', 'approved_leave', 'rejected_leave', 'used_leave_days'))->layout('components.layouts.employee.master');
    }
}
