<?php

namespace App\Livewire\Employee\Leaves;

use App\Models\Leave;
use Livewire\Component;
use App\Models\LeaveType;
use Illuminate\Support\Facades\Gate;
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
    public  $leave_reason;
    public  $interval;
    public ?Leave $leave = null;
    public $company;
    public $department;
    public $service;


    public function updatedEndDate($value)
    {
        if(!empty($value))
        {
            $this->interval = Carbon::parse($this->start_date)->lt(Carbon::parse($this->end_date)) ? __('employees.selected_leave_days'). '<strong>' . Carbon::parse($value)->diffInDays(Carbon::parse($this->start_date)) . '</strong>'.__('employees.days'): __('employees.start_date_before_end');
        }
    }
    public function updatedStartDate($value)
    {
        if(!empty($value))
        {
            $this->interval = Carbon::parse($value)->lt(Carbon::parse($this->end_date)) ? __('employees.selected_leave_days'). '<strong>' . Carbon::parse($value)->diffInDays(Carbon::parse($this->end_date)) .'</strong>'.__('employees.days'): __('employees.start_date_before_end');
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
            'end_date' => 'required|date|after:start_date',
            'leave_type_id' => 'required',
            'leave_reason' => 'required',
        ]);

        // Validate that user has required relationships
        if (empty($this->company)) {
            $this->addError('company', __('employees.not_associated_with_company'));
            return;
        }

        if (empty($this->department)) {
            $this->addError('department', __('employees.not_associated_with_department'));
            return;
        }

        $leave =  auth()->user()->leaves()->create(
            [
                'company_id' => $this->company->id,
                'department_id' => $this->department->id,
                'author_id' => auth()->user()->author_id,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'leave_type_id' => $this->leave_type_id,
                'leave_reason' => $this->leave_reason,
            ]
        );

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.leave_request_submitted'), 'CreateLeaveModal');
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
            Leave::whereIn('id', $this->selectedLeaves)
                ->where('user_id', auth()->user()->id)
                ->delete();

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
            Leave::withTrashed()
                ->whereIn('id', $this->selectedLeavesForDelete)
                ->where('user_id', auth()->id()) // Ensure only user's own leaves
                ->restore();

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
            Leave::withTrashed()
                ->whereIn('id', $this->selectedLeavesForDelete)
                ->where('user_id', auth()->id()) // Ensure only user's own leaves
                ->forceDelete();

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
        $rejected_leave = $allLeaves->where('supervisor_approval_status', Leave::SUPERVISOR_APPROVAL_REJECTED)->where('manager_approval_status', Leave::MANAGER_APPROVAL_REJECTED)->whereNull('deleted_at')->count();

        return view('livewire.employee.leaves.index', compact('leaves', 'pending_leave', 'approved_leave', 'rejected_leave'))->layout('components.layouts.employee.master');
    }
}
