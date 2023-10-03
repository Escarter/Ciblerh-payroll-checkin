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
        $this->checklog = null;
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
        $this->closeModalAndFlashMessage(__('Absences successfully updated!'), 'EditBulkAbsenceModal');
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
        $this->closeModalAndFlashMessage(__('Absence successfully updated!'), 'EditAbsenceModal');
    }
    public function delete()
    {
        if (!Gate::allows('absence-delete')) {
            return abort(401);
        }

        if (!empty($this->absence)) {

            $this->absence->delete();
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('Absence successfully deleted!'), 'DeleteModal');
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

        $absences = match($this->role){
            'supervisor' => Absence::search($this->query)->supervisor()->with(['user', 'company'])->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage),
            'manager' => Absence::search($this->query)->manager()->with(['user', 'company'])->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage),
            'admin' => Absence::search($this->query)->with(['user', 'company'])->orderBy($this->orderBy, $this->orderAsc)->paginate($this->perPage),
            default => [],
        };
        $absences_count = match($this->role){
            'supervisor' => Absence::search($this->query)->supervisor()->count(),
            'manager' => Absence::search($this->query)->manager()->count(),
            'admin' => Absence::search($this->query)->count(),
            default => [],
        };

        $pending_absences_count = match($this->role){
            'supervisor' => Absence::supervisor()->where('approval_status', Absence::APPROVAL_STATUS_PENDING)->count(),
            'manager' => Absence::manager()->where('approval_status', Absence::APPROVAL_STATUS_PENDING)->count(),
            'admin' => Absence::where('approval_status', Absence::APPROVAL_STATUS_PENDING)->count(),
            default => [],
        };

        $approved_absences_count = match($this->role){
            'supervisor' => Absence::supervisor()->where('approval_status', Absence::APPROVAL_STATUS_APPROVED)->count(),
            'manager' => Absence::manager()->where('approval_status', Absence::APPROVAL_STATUS_APPROVED)->count(),
            'admin' => Absence::where('approval_status', Absence::APPROVAL_STATUS_APPROVED)->count(),
            default => [], 
        };

        $rejected_absences_count = match($this->role){
            'supervisor' => Absence::supervisor()->where('approval_status', Absence::APPROVAL_STATUS_REJECTED)->count(),
            'manager' => Absence::manager()->where('approval_status', Absence::APPROVAL_STATUS_REJECTED)->count(),
            'admin' => Absence::where('approval_status', Absence::APPROVAL_STATUS_REJECTED)->count(),
            default => [],
        };
       
        return view('livewire.portal.absences.index', [
            'absences' => $absences,
            'absences_count' => $absences_count,
            'pending_absences_count' => $pending_absences_count,
            'approved_absences_count' => $approved_absences_count,
            'rejected_absences_count' => $rejected_absences_count,
        ])->layout('components.layouts.dashboard');
    }
}