<?php

namespace App\Livewire\Employee\Overtime;

use App\Livewire\Traits\WithDataTable;
use Livewire\Component;
use App\Models\Overtime;
use Livewire\WithPagination;
use Illuminate\Support\Carbon;
use App\Rules\CheckEndTimeRule;
use App\Rules\CheckStartTimeRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Rules\CheckStartAndEndTimeAreSameDayRule;
use App\Rules\Overtime\CheckOverlapWorkingHoursRule;

class Index extends Component
{
    use WithDataTable;

    public ?array $selected = [];
    public bool $selectAll = false;

    // Soft delete properties
    public $activeTab = 'active';
    public $selectedOvertimesForDelete = [];
    public $selectAllForDelete = false;

    // Reactive count properties
    public $activeOvertimesCount = 0;
    public $deletedOvertimesCount = 0;

    //Create, Edit, Delete, View Post props
    public ?string $start_time = null;
    public ?string $end_time = null;
    public $reason ;
    public ?int $overtime_id = null;
    public ?Overtime $overtime = null;
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

    //Get & assign selected overtime props
    public function initData($overtime_id)
    {
        $overtime = Overtime::findOrFail($overtime_id);

        $this->overtime = $overtime;
        $this->start_time = $overtime->start_time->format('Y-m-d\TH:i');
        $this->end_time = !empty($overtime->end_time) ? $overtime->end_time->format('Y-m-d\TH:i') : '';
        $this->reason = $overtime->reason;
        $this->overtime_id = $overtime->id;
        
    }
    public function store()
    {
        if (!Gate::allows('overtime-create')) {
            return abort(401);
        }
        $this->validate([
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'reason' => 'required'
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

        auth()->user()->overtimes()->create([
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'minutes_worked' => Carbon::parse($this->start_time)->diffInMinutes(Carbon::parse($this->end_time)),
            'reason' => $this->reason,
            'company_id' => $this->company->id,
            'department_id' => $this->department->id,
            'author_id' => auth()->user()->author_id,
        ]);

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.overtime_created'), 'CreateOvertimeModal');
    }
    public function update()
    {
        if (!Gate::allows('overtime-update')) {
            return abort(401);
        }
        $this->validate([
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time', new CheckEndTimeRule, new CheckStartAndEndTimeAreSameDayRule($this->start_time)],
            'reason' => 'required'
        ]);

        $this->overtime->update([
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'minutes_worked' => Carbon::parse($this->start_time)->diffInMinutes(Carbon::parse($this->end_time)),
            'reason' => $this->reason,
            
        ]);

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.overtime_updated'), 'EditOvertimeModal');
    }

    public function delete()
    {
        if (!Gate::allows('overtime-delete')) {
            return abort(401);
        }

        if (!empty($this->overtime)) {
            $this->overtime->delete();
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.overtime_deleted'), 'DeleteModal');

        // Update counts
        $this->updateCounts();
    }

    public function bulkDelete()
    {
        if (!Gate::allows('overtime-delete')) {
            return abort(401);
        }

        if (!empty($this->selected)) {
            Overtime::whereIn('id', $this->selected)
                ->where('user_id', auth()->user()->id)
                ->delete();

            $this->selected = [];
            $this->selectAll = false;

            $this->closeModalAndFlashMessage(__('employees.selected_overtimes_deleted'), 'BulkDeleteModal');

            // Update counts
            $this->updateCounts();
        }
    }

    public function restore($overtimeId)
    {
        if (!Gate::allows('overtime-delete')) {
            return abort(401);
        }

        $overtime = Overtime::withTrashed()->findOrFail($overtimeId);

        // Check if this overtime belongs to the current user
        if ($overtime->user_id !== auth()->id()) {
            return abort(403);
        }

        $overtime->restore();

        $this->closeModalAndFlashMessage(__('employees.overtime_restored'), 'RestoreModal');

        // Update counts
        $this->updateCounts();
    }

    public function forceDelete($overtimeId)
    {
        if (!Gate::allows('overtime-delete')) {
            return abort(401);
        }

        $overtime = Overtime::withTrashed()->findOrFail($overtimeId);

        // Check if this overtime belongs to the current user
        if ($overtime->user_id !== auth()->id()) {
            return abort(403);
        }

        $overtime->forceDelete();

        $this->closeModalAndFlashMessage(__('employees.overtime_permanently_deleted'), 'ForceDeleteModal');

        // Update counts
        $this->updateCounts();
    }

    public function bulkRestore()
    {
        if (!Gate::allows('overtime-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedOvertimesForDelete)) {
            Overtime::withTrashed()
                ->whereIn('id', $this->selectedOvertimesForDelete)
                ->where('user_id', auth()->id()) // Ensure only user's own overtimes
                ->restore();

            $this->selectedOvertimesForDelete = [];

            $this->closeModalAndFlashMessage(__('employees.selected_overtimes_restored'), 'BulkRestoreModal');

            // Update counts
            $this->updateCounts();
        }
    }

    public function bulkForceDelete()
    {
        if (!Gate::allows('overtime-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedOvertimesForDelete)) {
            Overtime::withTrashed()
                ->whereIn('id', $this->selectedOvertimesForDelete)
                ->where('user_id', auth()->id()) // Ensure only user's own overtimes
                ->forceDelete();

            $this->selectedOvertimesForDelete = [];

            $this->closeModalAndFlashMessage(__('employees.selected_overtimes_permanently_deleted'), 'BulkForceDeleteModal');

            // Update counts
            $this->updateCounts();
        }
    }

    //Toggle the $selectAll on or off based on the count of selected posts
    public function updatedselectAll($value)
    {
        if ($value) {
            $this->selected = $this->getOvertimes()->pluck('id')->toArray();
        } else {
            $this->selected = [];
        }
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
        $this->selected = $this->getOvertimes()->pluck('id')->toArray();
    }

    public function selectAllVisibleForDelete()
    {
        $this->selectedOvertimesForDelete = $this->getOvertimes()->pluck('id')->toArray();
    }

    public function selectAllOvertimes()
    {
        $this->selected = auth()->user()->overtimes()->whereNull('deleted_at')->pluck('id')->toArray();
    }

    public function selectAllDeletedOvertimes()
    {
        $this->selectedOvertimesForDelete = auth()->user()->overtimes()->withTrashed()->whereNotNull('deleted_at')->pluck('id')->toArray();
    }

    private function updateCounts()
    {
        $this->activeOvertimesCount = Overtime::where('user_id', auth()->user()->id)->whereNull('deleted_at')->count();
        $this->deletedOvertimesCount = Overtime::where('user_id', auth()->user()->id)->withTrashed()->whereNotNull('deleted_at')->count();
    }

    private function getOvertimes()
    {
        $query = Overtime::search($this->query)->where('user_id', auth()->user()->id);

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
            'overtime',
            'overtime_id',
            'start_time',
            'end_time',
            'reason',
            'selected',
            'selectAll',
        ]);
    }

    public function render()
    {
        $overtimes = $this->getOvertimes();

        // Get counts from all overtimes, not just current page
        $allOvertimes = Overtime::where('user_id', auth()->user()->id);
        $pending_overtime = $allOvertimes->where('approval_status', Overtime::APPROVAL_STATUS_PENDING)->whereNull('deleted_at')->count();
        $approved_overtime = $allOvertimes->where('approval_status', Overtime::APPROVAL_STATUS_APPROVED)->whereNull('deleted_at')->count();
        $rejected_overtime = $allOvertimes->where('approval_status', Overtime::APPROVAL_STATUS_REJECTED)->whereNull('deleted_at')->count();

        return view('livewire.employee.overtime.index', compact('overtimes', 'pending_overtime', 'approved_overtime', 'rejected_overtime'))->layout('components.layouts.employee.master');
    }
}
