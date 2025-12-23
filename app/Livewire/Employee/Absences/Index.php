<?php

namespace App\Livewire\Employee\Absences;

use App\Models\Absence;
use Livewire\Component;
use Illuminate\Support\Facades\Gate;
use App\Livewire\Traits\WithDataTable;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class Index extends Component
{
    use WithDataTable;

    public ?array $selected = [];

    //Create, Edit, Delete, View Post props
    public  $start_date;
    public  $end_date;
    public  $absence_date;
    public  $absence_reason;
    public  $attachment;
    public ?Absence $absence = null;
    public $company;
    public $department;
    public $service;
    public $interval;

    // Bulk selection properties
    public array $selectedAbsences = [];
    public bool $selectAll = false;

    // Soft delete properties
    public $activeTab = 'active';
    public $selectedAbsencesForDelete = [];
    public $selectAllForDelete = false;

    // Reactive count properties
    public $activeAbsencesCount = 0;
    public $deletedAbsencesCount = 0;

    public function updatedEndDate($value)
    {
        if(!empty($value) && !empty($this->start_date))
        {
            $start = Carbon::parse($this->start_date);
            $end = Carbon::parse($this->end_date);
            $today = Carbon::today();

            if ($start->lt($today)) {
                $this->interval = __('employees.cannot_request_past_absence');
            } elseif ($end->lt($today)) {
                $this->interval = __('employees.cannot_request_past_absence');
            } elseif ($start->lte($end)) {
                $days = $start->diffInDays($end) + 1;
                $this->interval = __('employees.selected_absence_days') . '<strong>' . $days . '</strong>' . __('employees.days');
            } else {
                $this->interval = __('employees.start_date_before_end');
            }
        }
    }

    public function updatedStartDate($value)
    {
        if(!empty($value) && !empty($this->end_date))
        {
            $start = Carbon::parse($this->start_date);
            $end = Carbon::parse($this->end_date);
            $today = Carbon::today();

            if ($start->lt($today)) {
                $this->interval = __('employees.cannot_request_past_absence');
            } elseif ($end->lt($today)) {
                $this->interval = __('employees.cannot_request_past_absence');
            } elseif ($start->lte($end)) {
                $days = $start->diffInDays($end) + 1;
                $this->interval = __('employees.selected_absence_days') . '<strong>' . $days . '</strong>' . __('employees.days');
            } else {
                $this->interval = __('employees.start_date_before_end');
            }
        } elseif (!empty($value)) {
            $start = Carbon::parse($this->start_date);
            $today = Carbon::today();

            if ($start->lt($today)) {
                $this->interval = __('employees.cannot_request_past_absence');
            }
        }
    }

    public function mount()
    {
        $this->company = auth()->user()->company;
        $this->department = auth()->user()->department;
        $this->service = auth()->user()->service;

        // Initialize counts
        $this->updateCounts();
    }

    public function store()
    {
        if (!Gate::allows('absence-create')) {
            return abort(401);
        }

        $this->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'absence_reason' => 'required',
            'attachment' => 'required|mimes:png,jpg,pdf,docx'
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

        $startDate = Carbon::parse($this->start_date);
        $endDate = Carbon::parse($this->end_date);

        // Create absence records for each day in the range
        $absencesCreated = 0;
        $attachmentPath = null;

        // Store attachment once and reuse for all records
        if (!empty($this->attachment)) {
            $attachmentPath = $this->attachment->storePublicly('absences', 'attachments');
        }

        while ($startDate->lte($endDate)) {
            auth()->user()->absences()->create([
                'company_id' => $this->company->id,
                'department_id' => $this->department->id,
                'author_id' => auth()->user()->author_id,
                'absence_date' => $startDate->format('Y-m-d'),
                'absence_reason' => $this->absence_reason,
                'attachment_path' => $attachmentPath,
            ]);

            $absencesCreated++;
            $startDate->addDay();
        }

        $this->clearFields();
        $message = $absencesCreated === 1
            ? __('employees.absence_request_submitted')
            : __('employees.absences_requests_submitted', ['count' => $absencesCreated]);
        $this->closeModalAndFlashMessage($message, 'CreateAbsenceModal');
    }
    //Get & assign selected absence props
    public function initData($absence_id)
    {
        $absence = Absence::findOrFail($absence_id);

        $this->absence = $absence;
        $this->absence_date = $absence->absence_date->format('Y-m-d');
        $this->absence_reason = $absence->absence_reason;
        $this->company_id = $absence->company_id;
        $this->department_id = $absence->department_id;
    }

    public function update()
    {
        if (!Gate::allows('absence-update')) {
            return abort(401);
        }

        $this->validate([
            'absence_date' => 'required|date|after_or_equal:today',
            'absence_reason' => 'required',
            'attachment' => 'sometimes|mimes:png,jpg,pdf,docx'
        ]);

        $this->absence->update([
            'absence_date' => $this->absence_date,
            'absence_reason' => $this->absence_reason,
        ]);

        if (!empty($this->attachment)) {
            Storage::disk('attachments')->delete($this->absence->attachment_path);
            $this->absence->update(['attachment_path' => $this->attachment->storePublicly('absences', 'attachments')]);
        }
        
        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.absence_request_updated'), 'EditAbsenceModal');
    }
    public function delete()
    {
        if (!Gate::allows('absence-delete')) {
            return abort(401);
        }

        if (!empty($this->absence)) {
            auth()->user()->absences()->findOrFail($this->absence->id)->delete();
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.absence_deleted'), 'DeleteModal');

        // Update counts
        $this->updateCounts();
    }

    public function restore($absenceId)
    {
        if (!Gate::allows('absence-restore')) {
            return abort(401);
        }

        $absence = Absence::withTrashed()->findOrFail($absenceId);

        // Check if this absence belongs to the current user
        if ($absence->user_id !== auth()->id()) {
            return abort(403);
        }

        $absence->restore();

        $this->closeModalAndFlashMessage(__('employees.absence_restored'), 'RestoreModal');

        // Update counts
        $this->updateCounts();
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
            } else {
                $this->showToast(__('employees.no_absence_selected'), 'danger');
                return;
            }
        }

        $absence = Absence::withTrashed()->findOrFail($absenceId);

        // Check if this absence belongs to the current user
        if ($absence->user_id !== auth()->id()) {
            return abort(403);
        }

        $absence->forceDelete();

        // Clear selection after deletion
        if (in_array($absenceId, $this->selectedAbsencesForDelete ?? [])) {
            $this->selectedAbsencesForDelete = array_diff($this->selectedAbsencesForDelete, [$absenceId]);
        }

        $this->closeModalAndFlashMessage(__('employees.absence_permanently_deleted'), 'ForceDeleteModal');

        // Update counts
        $this->updateCounts();
    }

    // Bulk selection methods
    public function updatedselectAll($value)
    {
        if ($value) {
            $this->selectedAbsences = auth()->user()->absences()
                ->where('approval_status', '!=', Absence::APPROVAL_STATUS_APPROVED)
                ->pluck('id')
                ->toArray();
        } else {
            $this->selectedAbsences = [];
        }
    }

    public function selectAllVisible()
    {
        $this->selectedAbsences = $this->getAbsences()->pluck('id')->toArray();
    }

    public function selectAllVisibleForDelete()
    {
        $this->selectedAbsencesForDelete = $this->getAbsences()->pluck('id')->toArray();
    }

    public function selectAllAbsences()
    {
        $this->selectedAbsences = Absence::where('user_id', auth()->user()->id)->whereNull('deleted_at')->pluck('id')->toArray();
    }

    public function selectAllDeletedAbsences()
    {
        $this->selectedAbsencesForDelete = Absence::where('user_id', auth()->user()->id)->withTrashed()->whereNotNull('deleted_at')->pluck('id')->toArray();
    }

    public function bulkDelete()
    {
        if (!Gate::allows('absence-bulkdelete')) {
            return abort(401);
        }

        if (!empty($this->selectedAbsences)) {
            $absences = auth()->user()->absences()
                ->whereIn('id', $this->selectedAbsences)
                ->where('approval_status', '!=', Absence::APPROVAL_STATUS_APPROVED)
                ->get();

            $affectedRecords = $absences->map(function ($absence) {
                return [
                    'id' => $absence->id,
                    'date' => $absence->absence_date,
                    'approval_status' => $absence->approval_status,
                ];
            })->toArray();

            auth()->user()->absences()
                ->whereIn('id', $this->selectedAbsences)
                ->where('approval_status', '!=', Absence::APPROVAL_STATUS_APPROVED)
                ->delete();

            if ($absences->count() > 0) {
                auditLog(
                    auth()->user(),
                    'absence_bulk_deleted',
                    'web',
                    'bulk_deleted_absences',
                    null,
                    [],
                    [],
                    [
                        'translation_key' => 'bulk_deleted_absences',
                        'translation_params' => ['count' => $absences->count()],
                        'bulk_operation' => true,
                        'operation_type' => 'soft_delete',
                        'affected_count' => $absences->count(),
                        'affected_ids' => $absences->pluck('id')->toArray(),
                        'affected_records' => $affectedRecords,
                    ]
                );
            }

            $this->selectedAbsences = [];
            $this->selectAll = false;

            $this->closeModalAndFlashMessage(__('absences.selected_absences_deleted'), 'BulkDeleteModal');

            // Update counts
            $this->updateCounts();
        }
    }

    public function bulkRestore()
    {
        if (!Gate::allows('absence-bulkrestore')) {
            return abort(401);
        }

        if (!empty($this->selectedAbsencesForDelete)) {
            $absences = Absence::withTrashed()
                ->whereIn('id', $this->selectedAbsencesForDelete)
                ->where('user_id', auth()->id())
                ->get();

            $affectedRecords = $absences->map(function ($absence) {
                return [
                    'id' => $absence->id,
                    'date' => $absence->absence_date,
                    'approval_status' => $absence->approval_status,
                ];
            })->toArray();

            Absence::withTrashed()
                ->whereIn('id', $this->selectedAbsencesForDelete)
                ->where('user_id', auth()->id())
                ->restore();

            if ($absences->count() > 0) {
                auditLog(
                    auth()->user(),
                    'absence_bulk_restored',
                    'web',
                    'bulk_restored_absences',
                    null,
                    [],
                    [],
                    [
                        'translation_key' => 'bulk_restored_absences',
                        'translation_params' => ['count' => $absences->count()],
                        'bulk_operation' => true,
                        'operation_type' => 'bulk_restore',
                        'affected_count' => $absences->count(),
                        'affected_ids' => $absences->pluck('id')->toArray(),
                        'affected_records' => $affectedRecords,
                    ]
                );
            }

            $this->selectedAbsencesForDelete = [];

            $this->closeModalAndFlashMessage(__('employees.selected_absences_restored'), 'BulkRestoreModal');

            // Update counts
            $this->updateCounts();
        }
    }

    public function bulkForceDelete()
    {
        if (!Gate::allows('absence-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedAbsencesForDelete)) {
            $absences = Absence::withTrashed()
                ->whereIn('id', $this->selectedAbsencesForDelete)
                ->where('user_id', auth()->id())
                ->get();

            $affectedRecords = $absences->map(function ($absence) {
                return [
                    'id' => $absence->id,
                    'date' => $absence->absence_date,
                    'approval_status' => $absence->approval_status,
                ];
            })->toArray();

            Absence::withTrashed()
                ->whereIn('id', $this->selectedAbsencesForDelete)
                ->where('user_id', auth()->id())
                ->forceDelete();

            if ($absences->count() > 0) {
                auditLog(
                    auth()->user(),
                    'absence_bulk_force_deleted',
                    'web',
                    'bulk_force_deleted_absences',
                    null,
                    [],
                    [],
                    [
                        'translation_key' => 'bulk_force_deleted_absences',
                        'translation_params' => ['count' => $absences->count()],
                        'bulk_operation' => true,
                        'operation_type' => 'bulk_force_delete',
                        'affected_count' => $absences->count(),
                        'affected_ids' => $absences->pluck('id')->toArray(),
                        'affected_records' => $affectedRecords,
                    ]
                );
            }

            $this->selectedAbsencesForDelete = [];

            $this->closeModalAndFlashMessage(__('employees.selected_absences_permanently_deleted'), 'BulkForceDeleteModal');

            // Update counts
            $this->updateCounts();
        }
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

    private function updateCounts()
    {
        $this->activeAbsencesCount = Absence::where('user_id', auth()->user()->id)->whereNull('deleted_at')->count();
        $this->deletedAbsencesCount = Absence::where('user_id', auth()->user()->id)->withTrashed()->whereNotNull('deleted_at')->count();
    }

    private function getAbsences()
    {
        $query = auth()->user()->absences()->with(['user', 'company']);

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
            'absence',
            'start_date',
            'end_date',
            'absence_date',
            'absence_reason',
            'attachment',
            'interval',
            'selectedAbsences',
            'selectAll',
        ]);
    }

    public function render()
    {
        if (!Gate::allows('absence-read')) {
            return abort(401);
        }

        $absences = $this->getAbsences();

        // Get counts from all absences, not just current page
        $allAbsences = auth()->user()->absences();
        $pending_absence = $allAbsences->where('approval_status', Absence::APPROVAL_STATUS_PENDING)->whereNull('deleted_at')->count();
        $approved_absence = $allAbsences->where('approval_status', Absence::APPROVAL_STATUS_APPROVED)->whereNull('deleted_at')->count();
        $rejected_absence = $allAbsences->where('approval_status', Absence::APPROVAL_STATUS_REJECTED)->whereNull('deleted_at')->count();

        return view('livewire.employee.absences.index', compact('absences', 'pending_absence', 'approved_absence', 'rejected_absence'))->layout('components.layouts.employee.master');

    }
}
