<?php

namespace App\Livewire\Employee\Absences;

use App\Models\Absence;
use Livewire\Component;
use Illuminate\Support\Facades\Gate;
use App\Livewire\Traits\WithDataTable;
use Illuminate\Support\Facades\Storage;

class Index extends Component
{
    use WithDataTable;

    public ?array $selected = [];

    //Create, Edit, Delete, View Post props
    public  $absence_date;
    public  $absence_reason;
    public  $attachment;
    public ?Absence $absence = null;
    public $company;
    public $department;
    public $service;

    public function mount()
    {
        $this->company = auth()->user()->company;
        $this->department = auth()->user()->department;
        $this->service = auth()->user()->service;
    }

    public function store()
    {
        if (!Gate::allows('absence-create')) {
            return abort(401);
        }

        $this->validate([
            'absence_date' => 'required|date',
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

       $absence =  auth()->user()->absences()->create(
            [
                'company_id' => $this->company->id,
                'department_id' => $this->department->id,
                'author_id' => auth()->user()->author_id,
                'absence_date' => $this->absence_date,
                'absence_reason' => $this->absence_reason,
            ]
        );
        
        if (!empty($this->attachment)) {
            $absence->update(['attachment_path' => $this->attachment->storePublicly('absences', 'attachments')]);
        }

        $this->clearFields();
        $this->closeModalAndFlashMessage(__('employees.absence_request_submitted'), 'CreateAbsenceModal');
    }
    //Get & assign selected absence props
    public function initData($absence_id)
    {
        $absence = Absence::findOrFail($absence_id);

        $this->absence = $absence;
        $this->absence_date = $absence->absence_date;
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
            'absence_date' => 'required|date',
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
    }

    public function clearFields()
    {
        $this->reset([
            'absence',
            'absence_date',
            'absence_reason',
            'attachment',
        ]);
    }

    public function render()
    {
        if (!Gate::allows('absence-read')) {
            return abort(401);
        }

        $absences = auth()->user()->absences()->orderBy('created_at', 'desc')->paginate($this->perPage);
        
        // Get counts from all absences, not just current page
        $allAbsences = auth()->user()->absences();
        $pending_absence = $allAbsences->where('approval_status', Absence::APPROVAL_STATUS_PENDING)->count();
        $approved_absence = $allAbsences->where('approval_status', Absence::APPROVAL_STATUS_APPROVED)->count();
        $rejected_absence = $allAbsences->where('approval_status', Absence::APPROVAL_STATUS_REJECTED)->count();

        return view('livewire.employee.absences.index', compact('absences', 'pending_absence', 'approved_absence', 'rejected_absence'))->layout('components.layouts.employee.master');

    }
}
