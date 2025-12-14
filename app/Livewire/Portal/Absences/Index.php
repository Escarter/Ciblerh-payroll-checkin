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
        Absence::whereIn('id', $this->selectedAbsences)->update([
            'approval_status' => $this->approval_status,
            'approval_reason' => $this->approval_reason,
        ]);
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
        if (!Gate::allows('absence-delete')) {
            return abort(401);
        }

        $absence = Absence::withTrashed()->findOrFail($absenceId);
        $absence->restore();

        $this->closeModalAndFlashMessage(__('absences.absence_successfully_restored'), 'RestoreModal');
    }

    public function forceDelete($absenceId)
    {
        if (!Gate::allows('absence-delete')) {
            return abort(401);
        }

        $absence = Absence::withTrashed()->findOrFail($absenceId);
        $absence->forceDelete();

        $this->closeModalAndFlashMessage(__('absences.absence_permanently_deleted'), 'ForceDeleteModal');
    }

    public function bulkDelete()
    {
        if (!Gate::allows('absence-delete')) {
            return abort(401);
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

        $this->closeModalAndFlashMessage(__('absences.selected_absence_records_moved_to_trash'), 'BulkDeleteModal');
    }

    public function bulkRestore()
    {
        if (!Gate::allows('absence-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedAbsencesForDelete)) {
            Absence::withTrashed()->whereIn('id', $this->selectedAbsencesForDelete)->restore();
            $this->selectedAbsencesForDelete = [];
        }

        $this->closeModalAndFlashMessage(__('absences.selected_absence_records_restored'), 'BulkRestoreModal');
    }

    public function bulkForceDelete()
    {
        if (!Gate::allows('absence-delete')) {
            return abort(401);
        }

        if (!empty($this->selectedAbsencesForDelete)) {
            Absence::withTrashed()->whereIn('id', $this->selectedAbsencesForDelete)->forceDelete();
            $this->selectedAbsencesForDelete = [];
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