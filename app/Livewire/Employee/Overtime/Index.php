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
        if (!Gate::allows('overtime-bulkdelete')) {
            return abort(401);
        }

        if (!empty($this->selected)) {
            $overtimes = Overtime::whereIn('id', $this->selected)
                ->where('user_id', auth()->user()->id)
                ->get();

            $affectedRecords = $overtimes->map(function ($overtime) {
                return [
                    'id' => $overtime->id,
                    'date' => $overtime->overtime_date,
                    'approval_status' => $overtime->approval_status,
                ];
            })->toArray();

            Overtime::whereIn('id', $this->selected)
                ->where('user_id', auth()->user()->id)
                ->delete();

            if ($overtimes->count() > 0) {
                auditLog(
                    auth()->user(),
                    'overtime_bulk_deleted',
                    'web',
                    'bulk_deleted_overtimes',
                    null,
                    [],
                    [],
                    [
                        'translation_key' => 'bulk_deleted_overtimes',
                        'translation_params' => ['count' => $overtimes->count()],
                        'bulk_operation' => true,
                        'operation_type' => 'soft_delete',
                        'affected_count' => $overtimes->count(),
                        'affected_ids' => $overtimes->pluck('id')->toArray(),
                        'affected_records' => $affectedRecords,
                    ]
                );
            }

            $this->selected = [];
            $this->selectAll = false;

            $this->closeModalAndFlashMessage(__('employees.selected_overtimes_deleted'), 'BulkDeleteModal');

            // Update counts
            $this->updateCounts();
        }
    }

    public function restore($overtimeId)
    {
        if (!Gate::allows('overtime-restore')) {
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

    public function forceDelete($overtimeId = null)
    {
        if (!Gate::allows('overtime-delete')) {
            return abort(401);
        }

        // If no overtimeId provided, try to get it from selectedOvertimesForDelete
        if (!$overtimeId) {
            if (!empty($this->selectedOvertimesForDelete) && is_array($this->selectedOvertimesForDelete)) {
                $overtimeId = $this->selectedOvertimesForDelete[0] ?? null;
            } else {
                $this->showToast(__('employees.no_overtime_selected'), 'danger');
                return;
            }
        }

        $overtime = Overtime::withTrashed()->findOrFail($overtimeId);

        // Check if this overtime belongs to the current user
        if ($overtime->user_id !== auth()->id()) {
            return abort(403);
        }

        $overtime->forceDelete();

        // Clear selection after deletion
        if (in_array($overtimeId, $this->selectedOvertimesForDelete ?? [])) {
            $this->selectedOvertimesForDelete = array_diff($this->selectedOvertimesForDelete, [$overtimeId]);
        }

        $this->closeModalAndFlashMessage(__('employees.overtime_permanently_deleted'), 'ForceDeleteModal');

        // Update counts
        $this->updateCounts();
    }

    public function bulkRestore()
    {
        if (!Gate::allows('overtime-bulkrestore')) {
            return abort(401);
        }

        if (!empty($this->selectedOvertimesForDelete)) {
            $overtimes = Overtime::withTrashed()
                ->whereIn('id', $this->selectedOvertimesForDelete)
                ->where('user_id', auth()->id())
                ->get();

            $affectedRecords = $overtimes->map(function ($overtime) {
                return [
                    'id' => $overtime->id,
                    'date' => $overtime->overtime_date,
                    'approval_status' => $overtime->approval_status,
                ];
            })->toArray();

            Overtime::withTrashed()
                ->whereIn('id', $this->selectedOvertimesForDelete)
                ->where('user_id', auth()->id())
                ->restore();

            if ($overtimes->count() > 0) {
                auditLog(
                    auth()->user(),
                    'overtime_bulk_restored',
                    'web',
                    'bulk_restored_overtimes',
                    null,
                    [],
                    [],
                    [
                        'translation_key' => 'bulk_restored_overtimes',
                        'translation_params' => ['count' => $overtimes->count()],
                        'bulk_operation' => true,
                        'operation_type' => 'bulk_restore',
                        'affected_count' => $overtimes->count(),
                        'affected_ids' => $overtimes->pluck('id')->toArray(),
                        'affected_records' => $affectedRecords,
                    ]
                );
            }

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
            $overtimes = Overtime::withTrashed()
                ->whereIn('id', $this->selectedOvertimesForDelete)
                ->where('user_id', auth()->id())
                ->get();

            $affectedRecords = $overtimes->map(function ($overtime) {
                return [
                    'id' => $overtime->id,
                    'date' => $overtime->overtime_date,
                    'approval_status' => $overtime->approval_status,
                ];
            })->toArray();

            Overtime::withTrashed()
                ->whereIn('id', $this->selectedOvertimesForDelete)
                ->where('user_id', auth()->id())
                ->forceDelete();

            if ($overtimes->count() > 0) {
                auditLog(
                    auth()->user(),
                    'overtime_bulk_force_deleted',
                    'web',
                    'bulk_force_deleted_overtimes',
                    null,
                    [],
                    [],
                    [
                        'translation_key' => 'bulk_force_deleted_overtimes',
                        'translation_params' => ['count' => $overtimes->count()],
                        'bulk_operation' => true,
                        'operation_type' => 'bulk_force_delete',
                        'affected_count' => $overtimes->count(),
                        'affected_ids' => $overtimes->pluck('id')->toArray(),
                        'affected_records' => $affectedRecords,
                    ]
                );
            }

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
